<?php
session_start();
require_once '../database/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Нет доступа']);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['name'], $_POST['category_id'])) {
    $name = trim($_POST['name']);
    $category_id = intval($_POST['category_id']);

    if (empty($name) || $category_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Неверные данные']);
        exit();
    }

    $stmt = $conn->prepare("INSERT INTO subcategories (name, category_id) VALUES (?, ?)");
    $stmt->execute([$name, $category_id]);

    $newId = $conn->lastInsertId();
    echo json_encode([
        'success' => true,
        'id' => $newId,
        'name' => $name,
        'category_id' => $category_id
    ]);
    exit();
}

echo json_encode(['success' => false, 'message' => 'Некорректный запрос']);
exit();
