<?php

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With, Cookie');
header("Access-Control-Allow-Credentials: true");

include('database/connection.php');
include('database/domain.php');

$genre = $_GET['genre'] ?? null;

$sql = "SELECT id, name, author, description, price, img_src FROM books WHERE genre = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(["error" => "SQL error"]);
    http_response_code(500);
    exit;
}

$stmt->bind_param("s", $genre);
$stmt->execute();
$result = $stmt->get_result();

$books = [];

while ($row = $result->fetch_assoc()) {
    $books[] = $row;
}

$stmt->close();
$conn->close();

echo json_encode($books);
?>

