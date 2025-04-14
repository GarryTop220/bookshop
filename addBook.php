<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With, Cookie');
header("Access-Control-Allow-Credentials: true");

include('database/connection.php');
include('database/domain.php');

function log_error($message, $details = []) {
    error_log($message . ' ' . json_encode($details));
    return json_encode(['error' => $message, 'details' => $details], JSON_UNESCAPED_UNICODE);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Перевірка обов'язкових полів
        $required_fields = ['name', 'author', 'description', 'price', 'genre'];
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("Необхідне поле '$field' відсутнє");
            }
        }

        if (empty($_FILES['image'])) {
            throw new Exception("Зображення не завантажено");
        }

        // Обробка вхідних даних
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
        $storage_path = '/tmp/books/';  // Основний шлях для зберігання
        $target_dir = $storage_path . $genre_folder . '/';

        // Створення директорій
        if (!file_exists($storage_path)) {
            if (!mkdir($storage_path, 0777, true)) {
                throw new Exception("Не вдалося створити кореневу директорію для зберігання");
            }
        }
        
        if (!file_exists($target_dir)) {
            if (!mkdir($target_dir, 0777, true)) {
                throw new Exception("Не вдалося створити директорію для жанру");
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
            throw new Exception("Дозволені лише JPG, JPEG, PNG та GIF файли");
        }

        if (!getimagesize($image['tmp_name'])) {
            throw new Exception("Файл не є зображенням");
        }

        if ($image['size'] > 5000000) {
            throw new Exception("Розмір файлу перевищує 5MB");
        }

        // Завантаження файлу
        if (!move_uploaded_file($image['tmp_name'], $target_file)) {
            $error = error_get_last();
            throw new Exception("Помилка завантаження файлу: " . ($error['message'] ?? 'невідома помилка'));
        }

        // Додаткова перевірка після завантаження
        if (!file_exists($target_file)) {
            throw new Exception("Файл не збережено на сервері");
        }

        if (filesize($target_file) === 0) {
            unlink($target_file);
            throw new Exception("Збережений файл має нульовий розмір");
        }

        // Формування URL через static.php
        $img_src = $domain . '/static.php/books/' . $genre_folder . '/' . $new_filename;

        // Запис в базу даних
        $stmt = $conn->prepare("INSERT INTO books(name, author, description, price, genre, img_src, is_new) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssdssi", $name, $author, $description, $price, $genre, $img_src, $isNew);
        
        if (!$stmt->execute()) {
            throw new Exception("Помилка бази даних: " . $stmt->error);
        }

        // Успішна відповідь
        echo json_encode([
            'success' => true,
            'message' => 'Книга додана успішно',
            'data' => [
                'name' => $name,
                'author' => $author,
                'image_url' => $img_src,
                'file_path' => $target_file // Для налагодження
            ]
        ], JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        // Видалення файлу у разі помилки
        if (isset($target_file) && file_exists($target_file)) {
            unlink($target_file);
        }
        
        // Логування помилки
        error_log("Помилка в addBook.php: " . $e->getMessage());
        
        // Відповідь з помилкою
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString() // Тільки для розробки
        ], JSON_UNESCAPED_UNICODE);
        
        http_response_code(400);
    } finally {
        if (isset($stmt)) $stmt->close();
        $conn->close();
    }
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Метод не дозволений'
    ], JSON_UNESCAPED_UNICODE);
    
    http_response_code(405);
}
?>