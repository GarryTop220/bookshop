<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With, Cookie');
header("Access-Control-Allow-Credentials: true");

include('database/connection.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);

    //перевірка наявності обов'язкових даних
    if (empty($data['id']) || empty($data['role'])) {
        die(json_encode(['error' => 'Не всі необхідні дані були надіслані.']));
    }
    $id = $data['id'];
    $role = $data['role'];

    $sql = "UPDATE users SET role = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $role, $id);

    if ($stmt->execute()) {
        echo json_encode(['message' => 'Роль успішно оновлена']);
    } else {
        echo json_encode(['error' => 'Помилка при оновленні ролі: ' . $stmt->error]);
    }

    $stmt->close();
    $conn->close();
}

?>
