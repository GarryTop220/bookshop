<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With, Cookie');
header("Access-Control-Allow-Credentials: true");

include('database/connection.php');
include('database/domain.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //перевірка наявності обов'язкових даних
    if (empty($_POST['name']) || empty($_POST['author']) || empty($_POST['description']) || empty($_POST['price']) || empty($_POST['genre']) || empty($_FILES['image'])) {
        die(json_encode(['error' => 'Не всі необхідні дані були надіслані.']));
    }

    $name = $_POST['name'];
    $author = $_POST['author'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $genre = $_POST['genre'];
    $isNew = isset($_POST['isNew']) ? $_POST['isNew'] : 0;

    $genres = [
        'Детективи' => "detective",
        'Фентезі' => "fantasy",
        'Трилери та жахи' => "thrillers",
        'Романтична проза' => "love_novels",
        'Комікси' => "comics"
    ];
    $target_dir = __DIR__ . "/src/assets/photo_books/" . ($genres[$genre] ?? '') . "/";
    

    //перевірка наявності директорії і створення її за потреби
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    //перевірка та переміщення зображення на сервер
    $target_file = $target_dir . basename($_FILES["image"]["name"]);
    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    //перевірка, чи файл є зображенням
    $check = getimagesize($_FILES["image"]["tmp_name"]);
    if ($check === false) {
        die(json_encode(['error' => 'Файл не є зображенням.']));
    }

    //перевірка розміру файлу
    if ($_FILES["image"]["size"] > 5000000) {
        die(json_encode(['error' => 'Файл зображення занадто великий.']));
    }

    //дозволені формати файлів
    if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif") {
        die(json_encode(['error' => 'Дозволені лише JPG, JPEG, PNG та GIF файли.']));
    }

    //збереження зображення
    if (!move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
        die(json_encode(['error' => 'Сталася помилка при завантаженні зображення.']));
    }

    $img_relative_path = "src/assets/photo_books/" . ($genres[$genre] ?? '') . "/" . basename($_FILES["image"]["name"]);
    $img_src = $domain . $img_relative_path;

    //вставка книги в таблицю book 
    $sql = "INSERT INTO books(name, author, description, price, genre, img_src, is_new) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssdssi", $name, $author, $description, $price, $genre, $img_src, $isNew);

    if ($stmt->execute()) {
        echo json_encode(['message' => 'Книга успішно додана.']);
    } else {
        echo json_encode(['error' => 'Помилка при додаванні книги: ' . $stmt->error]);
    }

    $stmt->close();
    if (isset($insert_genre_stmt)) {
        $insert_genre_stmt->close();
    }
    $conn->close();
}
?>
