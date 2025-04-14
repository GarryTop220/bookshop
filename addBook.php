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
    return json_encode(['error' => $message, 'details' => $details]);
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
    $storage_path = '/tmp/storage/books/';
    $target_dir = $storage_path . $genre_folder . '/';

    // Створення директорій
    if (!file_exists($storage_path)) {
        if (!mkdir($storage_path, 0777, true)) {
            die(log_error("Не вдалося створити кореневу директорію для зберігання", [
                'path' => $storage_path,
                'permissions' => substr(sprintf('%o', fileperms(dirname($storage_path))), -4)
            ]));
        }
    }
    
    if (!file_exists($target_dir)) {
        if (!mkdir($target_dir, 0777, true)) {
            die(log_error("Не вдалося створити директорію для жанру", [
                'path' => $target_dir,
                'parent_permissions' => substr(sprintf('%o', fileperms($storage_path)), -4)
            ]));
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

    // Завантаження файлу з детальним логуванням
    error_log("Спроба завантажити файл з temp_path: {$image['tmp_name']} до {$target_file}");
    
    if (!move_uploaded_file($image['tmp_name'], $target_file)) {
        $error = error_get_last();
        die(log_error("Помилка завантаження файлу", [
            'error_details' => $error,
            'source_path' => $image['tmp_name'],
            'target_path' => $target_file,
            'target_dir_exists' => file_exists($target_dir),
            'target_dir_writable' => is_writable($target_dir),
            'free_space' => disk_free_space($target_dir)
        ]));
    }

    // Додаткова перевірка після завантаження
    if (!file_exists($target_file)) {
        die(log_error("Файл не збережено на сервері", [
            'expected_path' => $target_file,
            'directory_listing' => scandir($target_dir)
        ]));
    }

    // Перевірка розміру збереженого файлу
    if (filesize($target_file) === 0) {
        unlink($target_file);
        die(log_error("Збережений файл має нульовий розмір"));
    }

    // Формування URL через static.php
    $img_src = $domain . '/static.php/storage/books/' . $genre_folder . '/' . $new_filename;

    // Збереження в БД
    try {
        $stmt = $conn->prepare("INSERT INTO books(name, author, description, price, genre, img_src, is_new) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssdssi", $name, $author, $description, $price, $genre, $img_src, $isNew);
        
        if (!$stmt->execute()) {
            unlink($target_file);
            die(log_error("Помилка бази даних", [
                'db_error' => $stmt->error,
                'query' => $stmt->errno
            ]));
        }

        // Додаткова перевірка, чи файл досі існує після запису в БД
        if (!file_exists($target_file)) {
            error_log("ПОПЕРЕДЖЕННЯ: Файл зник після запису в БД: {$target_file}");
        }

        echo json_encode([
            'success' => true,
            'message' => 'Книга додана успішно',
            'image_url' => $img_src,
            'local_path' => $target_file, // Тільки для налагодження
            'file_info' => [
                'size' => filesize($target_file),
                'mime' => mime_content_type($target_file)
            ]
        ]);

    } catch (Exception $e) {
        if (file_exists($target_file)) {
            unlink($target_file);
        }
        die(log_error("Системна помилка", [
            'exception' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]));
    } finally {
        if (isset($stmt)) $stmt->close();
        $conn->close();
    }
} else {
    die(log_error("Недопустимий метод запиту"));
}
?>