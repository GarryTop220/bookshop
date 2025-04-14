<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With, Cookie');
header("Access-Control-Allow-Credentials: true");

include('database/connection.php');

if ($conn->connect_error) {
    die(json_encode(['error' => 'Connection failed: ' . $conn->connect_error]));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);

    if (!isset($data['user_id'], $data['cart_items'], $data['total_price'], $data['deliveryType'], $data['paymentType'], $data['town'], $data['street'], $data['street_number'], $data['card'])) {
        echo json_encode(["message" => "Всі поля мають бути заповнені"]);
        $conn->close();
        exit();
    }

    $user_id = $data['user_id'];
    $cart_items = $data['cart_items'];
    $total_price = $data['total_price'];
    $deliveryType = $data['deliveryType'];
    $paymentType = $data['paymentType'];
    $town = $data['town'];
    $street = $data['street'];
    $street_number = $data['street_number'];
    $card = $data['card'];

    $last4Digits = substr($card, -4);

    // Початок транзакції
    $conn->begin_transaction();

    try {
        // Додавання доставки
        $sql = "INSERT INTO delivery (town, street, street_number, type) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssis", $town, $street, $street_number, $deliveryType);
        if (!$stmt->execute()) {
            throw new Exception("Помилка створення доставки");
        }
        $delivery_id = $stmt->insert_id;
        $stmt->close();

        // Додавання замовлення
        $sql = "INSERT INTO orders (user_id, total_price, delivery_id, payment_method, card) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("idiss", $user_id, $total_price, $delivery_id, $paymentType, $last4Digits);
        if (!$stmt->execute()) {
            throw new Exception("Помилка створення замовлення");
        }
        $order_id = $stmt->insert_id;
        $stmt->close();

        // Перевірка наявності книг перед додаванням до замовлення
        $checkSql = "SELECT id FROM books WHERE id = ?";
        $checkStmt = $conn->prepare($checkSql);
        
        // Додавання деталей замовлення
        $detailSql = "INSERT INTO order_details (order_id, book_id) VALUES (?, ?)";
        $detailStmt = $conn->prepare($detailSql);
        
        foreach ($cart_items as $cart_item) {
            if (!isset($cart_item['book_id']) || !is_numeric($cart_item['book_id'])) {
                throw new Exception("Некоректний ідентифікатор книги");
            }
            
            $book_id = $cart_item['book_id'];
            
            // Перевірка чи книга існує
            $checkStmt->bind_param("i", $book_id);
            $checkStmt->execute();
            $checkStmt->store_result();
            
            if ($checkStmt->num_rows == 0) {
                throw new Exception("Книга з ID $book_id не існує");
            }
            
            // Додавання кількості екземплярів книги
            for($i = 0; $i < $cart_item['quantity']; $i++) {
                $detailStmt->bind_param("ii", $order_id, $book_id);
                if (!$detailStmt->execute()) {
                    throw new Exception("Помилка додавання книги до замовлення");
                }
            }
        }
        
        $checkStmt->close();
        $detailStmt->close();

        // Очищення кошика
        if (!empty($cart_items)) {
            $cart_id = $cart_items[0]['cart_id'];
            
            $deleteSql = "DELETE FROM cart_details WHERE cart_id = ?";
            $deleteStmt = $conn->prepare($deleteSql);
            $deleteStmt->bind_param("i", $cart_id);
            if (!$deleteStmt->execute()) {
                throw new Exception('Помилка видалення книги з кошика');
            }
            $deleteStmt->close();
            
            $updateSql = "UPDATE cart SET total_price = ? WHERE id = ?";
            $updateStmt = $conn->prepare($updateSql);
            $new_total_price = 0;
            $updateStmt->bind_param("di", $new_total_price, $cart_id);
            if (!$updateStmt->execute()) {
                throw new Exception('Помилка оновлення загальної суми');
            }
            $updateStmt->close();
        }

        // Підтвердження транзакції
        $conn->commit();
        echo json_encode(['message' => 'Замовлення оформлене успішно']);
        
    } catch (Exception $e) {
        // Відкат транзакції у разі помилки
        $conn->rollback();
        echo json_encode(['message' => $e->getMessage()]);
        
        // Закриття з'єднання
        if (isset($stmt) && $stmt) $stmt->close();
        if (isset($checkStmt) && $checkStmt) $checkStmt->close();
        if (isset($detailStmt) && $detailStmt) $detailStmt->close();
        if (isset($deleteStmt) && $deleteStmt) $deleteStmt->close();
        if (isset($updateStmt) && $updateStmt) $updateStmt->close();
        
        $conn->close();
        exit();
    }
}

$conn->close();
?>