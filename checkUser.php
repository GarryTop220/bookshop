<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With, Cookie');
header("Access-Control-Allow-Credentials: true");

include('database/connection.php');

$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['name']) && isset($data['password']) && isset($data['role']) && isset($data['email'])) {
    $name = $conn->real_escape_string($data['name']);
    $email = $conn->real_escape_string($data['email']);
    $password = $data['password'];
    $role = $data['role'];
    
    if($role==='admin'){
        $sql = "SELECT * FROM users WHERE (name='$name' OR email='$email') AND (role = '$role' OR role = 'registered');";
    }
    else{
        $sql = "SELECT * FROM users WHERE (name='$name' OR email='$email') AND role = '$role';";
    }
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $token = bin2hex(random_bytes(16));
            echo json_encode(["message" => "Користувач знайдений", "token" => $token, "user" => $user]);
        } else {
            echo json_encode(["message" => "Користувача не знайдено"]);
        }
    } else {
        echo json_encode(["message" => "Користувача не знайдено"]);
    }
} else {
    echo json_encode(["message" => "Неправильні вхідні дані"]);
}

$conn->close();
?>