<?php

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With, Cookie');
header("Access-Control-Allow-Credentials: true");

include('database/connection.php');

$sql = "SELECT * FROM genres";
$result = $conn->query($sql);

if (!$result) {
    http_response_code(500);
    echo json_encode(['error' => 'Помилка запиту до бази даних: ' . $conn->error]);
    exit;
}

$genres = $result->fetch_all(MYSQLI_ASSOC);

$conn->close();

echo json_encode($genres);

?>
