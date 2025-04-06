<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With, Cookie');
header("Access-Control-Allow-Credentials: true");

include('database/connection.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    $order_id=$data['order_id'];
    $status = 'Доставлено';

    $sql = "UPDATE orders SET status = ? WHERE id = ?;";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $status, $order_id);
    if ($stmt->execute()) {
        echo json_encode(['message' => 'Товар доставлено']);
    } else {
        echo json_encode(['error' => 'Помилка при оновлені статусу']);
    }
}
$stmt->close();
$conn->close();
?>
