<?php
$request_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file_path = '/tmp/storage' . $request_path;

error_log("Requested file: " . $file_path); // Для дебагу

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
echo 'Файл не знайдено: ' . $request_path;
?>