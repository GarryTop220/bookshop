<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With, Cookie');
header("Access-Control-Allow-Credentials: true");

include('database/connection.php');
include('database/domain.php');

function log_error($message) {
    error_log($message);
    return json_encode(['error' => $message]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Валідація вхідних даних
    $required_fields = ['name', 'author', 'description', 'price', 'genre'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            die(log_error("Необхідне поле '$field' відсутнє"));
        }
    }

    if (empty($_FILES['image'])) {
        die(log_error("Зображення не завантажено"));
    }

    // Отримання даних
    $name = trim($_POST['name']);
    $author = trim($_POST['author']);
    $description = trim($_POST['description']);
    $price = (float)$_POST['price'];
    $genre = trim($_POST['genre']);
    $isNew = isset($_POST['isNew']) ? (int)$_POST['isNew'] : 0;

    // Визначення структури папок
    $genres = [
        'Детективи' => "detective",
        'Фентезі' => "fantasy",
        'Трилери та жахи' => "thrillers",
        'Романтична проза' => "love_novels",
        'Комікси' => "comics"
    ];
    
    $genre_folder = $genres[$genre] ?? 'other';
    
    // Шлях для зберігання - використовуємо /tmp/ на Railway
    $storage_path = '/tmp/storage/books/';
    $target_dir = $storage_path . $genre_folder . '/';

    // Створення директорій
    if (!file_exists($storage_path)) {
        if (!mkdir($storage_path, 0777, true)) {
            die(log_error("Не вдалося створити кореневу директорію для зберігання"));
        }
    }
    
    if (!file_exists($target_dir)) {
        if (!mkdir($target_dir, 0777, true)) {
            die(log_error("Не вдалося створити директорію для жанру"));
        }
    }

    // Обробка зображення
    $image = $_FILES['image'];
    $ext = strtolower(pathinfo($image['name'], PATHINFO_EXTENSION));
    $new_filename = md5(uniqid()) . '.' . $ext;
    $target_file = $target_dir . $new_filename;

    // Валідація зображення
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    if (!in_array($ext, $allowed)) {
        die(log_error("Дозволені лише JPG, JPEG, PNG та GIF файли"));
    }

    if (!getimagesize($image['tmp_name'])) {
        die(log_error("Файл не є зображенням"));
    }

    if ($image['size'] > 5000000) {
        die(log_error("Розмір файлу перевищує 5MB"));
    }

    // Завантаження файлу
    if (!move_uploaded_file($image['tmp_name'], $target_file)) {
        die(log_error("Помилка завантаження файлу: " . error_get_last()['message']));
    }

    // Збереження в БД
    try {
        // Формуємо URL через static.php
        $img_src = $domain . '/static.php/storage/books/' . $genre_folder . '/' . $new_filename;
        
        $stmt = $conn->prepare("INSERT INTO books(name, author, description, price, genre, img_src, is_new) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssdssi", $name, $author, $description, $price, $genre, $img_src, $isNew);
        
        if (!$stmt->execute()) {
            unlink($target_file);
            die(log_error("Помилка бази даних: " . $stmt->error));
        }

        echo json_encode([
            'success' => true,
            'message' => 'Книга додана успішно',
            'image_url' => $img_src
        ]);

    } catch (Exception $e) {
        if (file_exists($target_file)) unlink($target_file);
        die(log_error("Системна помилка: " . $e->getMessage()));
    } finally {
        if (isset($stmt)) $stmt->close();
        $conn->close();
    }
} else {
    die(log_error("Недопустимий метод запиту"));
}
?>