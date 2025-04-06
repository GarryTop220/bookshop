<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With, Cookie');
header("Access-Control-Allow-Credentials: true");

include('database/connection.php');

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
        delivery.type,
        orders.user_id
        FROM orders
        JOIN order_details ON orders.id = order_details.order_id
        JOIN delivery ON orders.delivery_id = delivery.id
        GROUP BY orders.id;";

$result = $conn->query($sql);
$orders = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
}

echo json_encode(['orders' => $orders]);
?>
