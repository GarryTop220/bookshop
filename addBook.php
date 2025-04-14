<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With, Cookie');
header("Access-Control-Allow-Credentials: true");

include('database/connection.php');
include('database/domain.php');

// Функція для логування помилок
function log_error($message) {
    error_log($message);
    return json_encode(['error' => $message]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Перевірка наявності обов'язкових даних
    $required_fields = ['name', 'author', 'description', 'price', 'genre'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            die(log_error("Необхідне поле '$field' відсутнє."));
        }
    }

    if (empty($_FILES['image'])) {
        die(log_error("Зображення не було завантажено."));
    }

    // Отримання даних з форми
    $name = trim($_POST['name']);
    $author = trim($_POST['author']);
    $description = trim($_POST['description']);
    $price = (float)$_POST['price'];
    $genre = trim($_POST['genre']);
    $isNew = isset($_POST['isNew']) ? (int)$_POST['isNew'] : 0;

    // Визначення шляхів для зображень
    $genres = [
        'Детективи' => "detective",
        'Фентезі' => "fantasy",
        'Трилери та жахи' => "thrillers",
        'Романтична проза' => "love_novels",
        'Комікси' => "comics"
    ];

    $genre_folder = $genres[$genre] ?? 'other';
    $base_dir = __DIR__ . '/src/assets/photo_books/';
    $target_dir = $base_dir . $genre_folder . '/';

    // Створення директорій, якщо вони не існують
    if (!file_exists($base_dir)) {
        if (!mkdir($base_dir, 0777, true)) {
            die(log_error("Не вдалося створити базову директорію для зображень."));
        }
    }

    if (!file_exists($target_dir)) {
        if (!mkdir($target_dir, 0777, true)) {
            die(log_error("Не вдалося створити директорію для жанру."));
        }
    }

    // Перевірка прав доступу
    if (!is_writable($target_dir)) {
        die(log_error("Директорія не доступна для запису: " . $target_dir));
    }

    // Обробка зображення
    $image = $_FILES['image'];
    $original_name = basename($image['name']);
    $file_extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
    $new_filename = uniqid('book_', true) . '.' . $file_extension;
    $target_file = $target_dir . $new_filename;

    // Перевірка типу файлу
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
    if (!in_array($file_extension, $allowed_types)) {
        die(log_error("Дозволені лише JPG, JPEG, PNG та GIF файли."));
    }

    // Перевірка, чи файл є зображенням
    if (!getimagesize($image['tmp_name'])) {
        die(log_error("Файл не є зображенням."));
    }

    // Перевірка розміру файлу (макс. 5MB)
    if ($image['size'] > 5000000) {
        die(log_error("Файл зображення занадто великий. Максимальний розмір: 5MB."));
    }

    // Переміщення завантаженого файлу
    if (!move_uploaded_file($image['tmp_name'], $target_file)) {
        $error = error_get_last();
        die(log_error("Помилка при завантаженні зображення: " . ($error['message'] ?? 'невідома помилка')));
    }

    // Формування URL зображення
    $img_relative_path = "src/assets/photo_books/$genre_folder/$new_filename";
    $img_src = $domain . $img_relative_path;

    // Додавання книги до бази даних
    try {
        $sql = "INSERT INTO books(name, author, description, price, genre, img_src, is_new) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssdssi", $name, $author, $description, $price, $genre, $img_src, $isNew);
        
        if (!$stmt->execute()) {
            // Якщо помилка в БД, видаляємо завантажене зображення
            unlink($target_file);
            die(log_error("Помилка при додаванні книги: " . $stmt->error));
        }

        echo json_encode([
            'success' => true,
            'message' => 'Книга успішно додана.',
            'book' => [
                'name' => $name,
                'author' => $author,
                'img_src' => $img_src
            ]
        ]);

    } catch (Exception $e) {
        unlink($target_file);
        die(log_error("Виникла помилка: " . $e->getMessage()));
    } finally {
        if (isset($stmt)) $stmt->close();
        $conn->close();
    }
} else {
    die(log_error("Метод не дозволений."));
}
?>