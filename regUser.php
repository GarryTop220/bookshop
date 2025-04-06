<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With, Cookie');
header("Access-Control-Allow-Credentials: true");

include('database/connection.php');

$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['name']) && isset($data['password']) && isset($data['email']) && isset($data['role'])) {
    $name = $conn->real_escape_string($data['name']);
    $password = $conn->real_escape_string($data['password']);
    $email = $conn->real_escape_string($data['email']);
    $role = $conn->real_escape_string($data['role']);

    $checkUser = "SELECT * FROM users WHERE name='$name' OR email='$email'";
    $result = $conn->query($checkUser);

    if ($result->num_rows > 0) {
        echo json_encode(["message" => "Логін або email вже існує"]);
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (name, password, email, role) VALUES ('$name', '$hashed_password', '$email', '$role')";
        if ($conn->query($sql) === TRUE) {
            $userId = $conn->insert_id;
            $sql1 = "SELECT created_at FROM users WHERE id='$userId'";
            $result1 = $conn->query($sql1);
            $created_at = null;
            if ($result1->num_rows > 0) {
                $row = $result1->fetch_assoc();
                $created_at = $row['created_at'];  
            }
            $user = [
                'id' => $userId,
                'name' => $name,
                'password' => $hashed_password,
                'email' => $email,
                'role' => $role,
                'created_at' => $created_at
            ];
            $token = bin2hex(random_bytes(16));
            echo json_encode(["message" => "Користувач зареєстрований успішно", "token" => $token, "user" => $user]);
        } else {
            echo json_encode(["message" => "Помилка при реєстрації: " . $conn->error]);
        }
    }
} else {
    echo json_encode(["message" => "Неправильні вхідні дані"]);
}

$conn->close();
?>