<?php
header('Access-Control-Allow-Origin: *');

// Отримуємо шлях з URL
$request_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Видаляємо /static.php з початку шляху
$file_relative_path = preg_replace('/^\/static\.php/', '', $request_path);

// Абсолютний шлях до файлу
$storage_base = '/tmp';
$file_path = $storage_base . $file_relative_path;

// Для дебагу
error_log("Static.php: Шукаємо файл за шляхом: " . $file_path);

if (file_exists($file_path) && is_file($file_path)) {
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

// Якщо файл не знайдено
http_response_code(404);
header('Content-Type: application/json');
echo json_encode([
    'error' => 'Файл не знайдено',
    'searched_path' => $file_path,
    'request_uri' => $_SERVER['REQUEST_URI']
], JSON_UNESCAPED_UNICODE);
?>