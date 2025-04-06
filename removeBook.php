<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With, Cookie');
header("Access-Control-Allow-Credentials: true");

include('database/connection.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //перевірка наявності даних
    $data = json_decode(file_get_contents("php://input"), true);
    $bookId = $data['id'] ?? $_POST['id'] ?? null;
    
    if (!$bookId) {
        die(json_encode(['error' => 'Не надіслано ID книги.']));
    }

    //отримання шляху до зображення книги для видалення
    $img_sql = "SELECT img_src FROM books WHERE id = ?";
    $img_stmt = $conn->prepare($img_sql);
    $img_stmt->bind_param("i", $bookId);
    $img_stmt->execute();
    $img_result = $img_stmt->get_result();

    if ($img_result->num_rows > 0) {
        $img_row = $img_result->fetch_assoc();
        $img_src = __DIR__ . "/../" . $img_row['img_src'];

        //видалення зображення з сервера
        if (file_exists($img_src)) {
            unlink($img_src);
        }
    } else {
        die(json_encode(['error' => 'Книга з таким ID не знайдена.']));
    }

    //видалення книги з бази даних
    $sql = "DELETE FROM books WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $bookId);
    
    if ($stmt->execute()) {
        echo json_encode(['message' => 'Книга успішно видалена.']);
    } else {
        echo json_encode(['error' => 'Помилка при видаленні книги: ' . $stmt->error]);
    }

    $stmt->close();
    $img_stmt->close();
    $conn->close();
}
?>
