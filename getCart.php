<?php

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With, Cookie');
header("Access-Control-Allow-Credentials: true");

include('database/connection.php');
include('database/domain.php');

$user_id = $_GET['user_id'];

$sql = "SELECT 
            cart.id AS cart_id,
            cart.user_id,
            cart.total_price,
            cart_details.id AS cart_details_id,
            cart_details.book_id,
            COUNT(cart_details.book_id) AS quantity,
            books.price,
            books.name,
            books.img_src,
            books.author
        FROM cart
        JOIN cart_details ON cart.id = cart_details.cart_id
        JOIN books ON cart_details.book_id = books.id
        WHERE cart.user_id = ?
        GROUP BY 
            cart.id,
            cart.user_id,
            cart.total_price,
            cart_details.id,
            cart_details.book_id,
            books.price,
            books.name,
            books.img_src,
            books.author";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Помилка підготовки запиту: " . $conn->error);
}

$stmt->bind_param("i", $user_id);

if (!$stmt->execute()) {
    die("Помилка виконання запиту: " . $stmt->error);
}

$result = $stmt->get_result();

$cart_items = [];
$total_price = 0;

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $cart_items[] = $row;
        $total_price = $row['total_price'];
    }
}

$stmt->close();
$conn->close();

echo json_encode([
    'success' => true,
    'cart_items' => $cart_items,
    'total_price' => $total_price
]);


?>