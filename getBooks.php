<?php

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With, Cookie');
header("Access-Control-Allow-Credentials: true");

include('database/connection.php');

$sql = "SELECT name, author, description,
        price, genre, is_new, img_src
        FROM books";

$result = $conn->query($sql);

if (!$result) {
    echo json_encode(['error' => 'Database query failed']);
    exit;
}

$books = [];

while ($row = $result->fetch_assoc()) {
    $books[] = $row;
}

$conn->close();

echo json_encode($books);
?>
