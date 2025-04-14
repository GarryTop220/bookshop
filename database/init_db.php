<?php
$HOST = getenv('MYSQLHOST') ?: 'mysql.railway.internal';
$USER = getenv('MYSQLUSER') ?: 'root';
$PASS = getenv('MYSQLPASSWORD') ?: 'aXGnEnpUJZUgJHBZTNSOlpisswEMAtCr';
$DB   = getenv('MYSQLDATABASE') ?: 'railway';
$PORT = getenv('MYSQLPORT') ?: '3306';

$conn = mysqli_connect($HOST, $USER, $PASS, $DB, $PORT)
    or die("Connection error: " . mysqli_connect_error());
mysqli_set_charset($conn, "utf8");

$tables = [
    'order_details', 'orders', 'delivery', 
    'cart_details', 'cart', 'books', 'genres', 'users'
];

// 1. Видаляємо всі таблиці
foreach ($tables as $table) {
    $conn->query("DROP TABLE IF EXISTS $table");
    echo "Таблиця $table видалена<br>";
}

$sql = <<<SQL
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'registered', 'user') NOT NULL DEFAULT 'registered',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS genres (
    name VARCHAR(255) PRIMARY KEY
);

CREATE TABLE IF NOT EXISTS books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    author VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    genre VARCHAR(255) NOT NULL,
    is_new TINYINT(1) NOT NULL DEFAULT 0,
    img_src VARCHAR(500) NOT NULL DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (genre) REFERENCES genres(name) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total_price DECIMAL(10,2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS cart_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cart_id INT NOT NULL,
    book_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cart_id) REFERENCES cart(id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS delivery (
    id INT AUTO_INCREMENT PRIMARY KEY,
    town VARCHAR(255) NOT NULL,
    street VARCHAR(255) NOT NULL,
    street_number INT NOT NULL,
    type ENUM('standard', 'express') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    delivery_id INT NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    card VARCHAR(4) NOT NULL,
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('Очікується', 'Обробляється', 'Доставлено', 'Скасовано') DEFAULT 'Очікується',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (delivery_id) REFERENCES delivery(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS order_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    book_id INT NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE
);

INSERT IGNORE INTO users (name, email, password, role) VALUES 
('admin', 'admin@mail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

INSERT IGNORE INTO genres (name) VALUES 
('Детективи'), 
('Фентезі'), 
('Трилери та жахи'), 
('Романтична проза'), 
('Комікси');
SQL;

if ($conn->multi_query($sql)) {
    do {
        if ($result = $conn->store_result()) {
            $result->free();
        }
    } while ($conn->more_results() && $conn->next_result());
    echo "Таблиці успішно створені та заповнені!";
} else {
    echo "Помилка: " . $conn->error;
}

$conn->close();
?>