<?php

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With, Cookie');
header("Access-Control-Allow-Credentials: true");

include('database/connection.php');
include('database/domain.php');

// Перевірка та приведення параметрів до int
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

// Перевірка, чи передані коректні параметри
if ($user_id <= 0 || $order_id <= 0) {
    echo json_encode(['error' => 'Invalid parameters']);
    exit;
}

// Підготовка SQL-запиту для отримання товарів у замовленні
$sql_items = "SELECT 
                books.id AS book_id,
                books.name AS book_name,
                books.author AS book_author,
                books.price AS book_price,
                books.img_src,
                COUNT(order_details.book_id) AS quantity 
             FROM orders
             JOIN order_details ON orders.id = order_details.order_id
             JOIN books ON order_details.book_id = books.id
             WHERE orders.user_id = ? AND orders.id = ?
             GROUP BY books.id, books.name, books.author, books.price, books.img_src";

$stmt_items = $conn->prepare($sql_items);
if (!$stmt_items) {
    die(json_encode(['error' => 'Database error: ' . $conn->error]));
}

$stmt_items->bind_param("ii", $user_id, $order_id);
$stmt_items->execute();
$result_items = $stmt_items->get_result();

$order_items = [];
while ($row = $result_items->fetch_assoc()) {
    $order_items[] = $row;
}

$stmt_items->close();

// Підготовка SQL-запиту для деталей замовлення
$sql_details = "SELECT 
                  orders.order_date,
                  orders.total_price,
                  orders.delivery_id,
                  orders.status,
                  orders.payment_method,
                  delivery.town,
                  delivery.street,
                  delivery.street_number,
                  delivery.type,
                  users.name AS user_name
               FROM orders
               JOIN delivery ON orders.delivery_id = delivery.id
               JOIN users ON orders.user_id = users.id
               WHERE orders.user_id = ? AND orders.id = ?";

$stmt_details = $conn->prepare($sql_details);
if (!$stmt_details) {
    die(json_encode(['error' => 'Database error: ' . $conn->error]));
}

$stmt_details->bind_param("ii", $user_id, $order_id);
$stmt_details->execute();
$result_details = $stmt_details->get_result();

$order_details = $result_details->num_rows > 0 ? $result_details->fetch_assoc() : null;

$stmt_details->close();
$conn->close();

echo json_encode(['order_items' => $order_items, 'order_details' => $order_details]);
?>
