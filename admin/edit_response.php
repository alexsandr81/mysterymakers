<?php
session_start();
require_once '../database/db.php';

if (!isset($_SESSION['admin_id']) || !isset($_GET['id'])) {
    header("Location: reviews.php");
    exit();
}

$id = intval($_GET['id']);
$stmt = $conn->prepare("SELECT admin_response FROM reviews WHERE id = ?");
$stmt->execute([$id]);
$review = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$review) {
    die("Отзыв не найден!");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $response = trim($_POST['response']);
    $stmt = $conn->prepare("UPDATE reviews SET admin_response = ?, response_date = NOW() WHERE id = ?");
    $stmt->execute([$response, $id]);

    header("Location: reviews.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Редактировать ответ</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<h2>Редактировать ответ администратора</h2>

<form method="POST">
    <textarea name="response" required><?= htmlspecialchars($review['admin_response']); ?></textarea>
    <button type="submit">💾 Сохранить</button>
</form>

</body>
</html>
