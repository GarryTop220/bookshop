<?php

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With, Cookie');
header("Access-Control-Allow-Credentials: true");

include('database/connection.php');

$id = intval($_GET['id']);
$sql = "SELECT name, author, description, price, genre, is_new, img_src
        FROM books 
        WHERE id = ?;";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);

$stmt->execute();
$result = $stmt->get_result();

$book = null;

if ($result->num_rows > 0) {
    $book = $result->fetch_assoc();
}

$conn->close();

echo json_encode($book);
?>
