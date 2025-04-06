<?php

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With, Cookie');
header("Access-Control-Allow-Credentials: true");

include('database/connection.php');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = $_GET['user_id'];

$sql = "SELECT 
        orders.id AS order_id,
        orders.order_date,
        orders.total_price,
        orders.delivery_id,
        orders.status,
        orders.payment_method,
        delivery.town,
        delivery.street,
        delivery.street_number,
        delivery.type
        FROM orders
        JOIN order_details ON orders.id = order_details.order_id
        JOIN delivery ON orders.delivery_id = delivery.id
        WHERE orders.user_id = ?
        GROUP BY orders.id;";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);

$stmt->execute();
$result = $stmt->get_result();

$orders = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
}

$stmt->close();
$conn->close();

echo json_encode(['orders' => $orders]);
?>
