<?php
session_start();
require_once '../database/db.php';

if (!isset($_SESSION['user_id'])) {
    die("Только авторизованные пользователи могут оставлять отзывы.");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $product_id = $_POST['product_id'];
    $user_id = $_SESSION['user_id'];
    $rating = intval($_POST['rating']);
    $comment = trim($_POST['comment']);

    // Обработка фото
    $image_path = NULL;
    if (!empty($_FILES['image']['name'])) {
        $upload_dir = '../assets/reviews/';
        $file_name = md5(time()) . "_" . basename($_FILES['image']['name']);
        $file_path = $upload_dir . $file_name;
        move_uploaded_file($_FILES['image']['tmp_name'], $file_path);
        $image_path = str_replace('../', '', $file_path);
    }

    $stmt = $conn->prepare("INSERT INTO reviews (product_id, user_id, rating, comment, image) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$product_id, $user_id, $rating, $comment, $image_path]);

    header("Location: product.php?id=$product_id");
    exit();
}
?>
