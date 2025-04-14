<?php
// Отримуємо шлях з URL
$request_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Видаляємо /static.php з початку шляху
$file_relative_path = str_replace('/static.php', '', $request_path);

// Абсолютний шлях до файлу (змініть на свій реальний шлях)
$storage_base = '/tmp/storage'; // або '/data/storage' якщо використовуєте том
$file_path = $storage_base . $file_relative_path;

// Для дебагу - можна пізніше видалити
error_log("Шукаємо файл: " . $file_path);

if (file_exists($file_path)) {
    $mime_types = [
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif'
    ];
    
    $ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
    
    if (isset($mime_types[$ext])) {
        header('Content-Type: ' . $mime_types[$ext]);
        readfile($file_path);
        exit;
    }
}

http_response_code(404);
echo 'Файл не знайдено. Шукали за шляхом: ' . $file_path;
?>