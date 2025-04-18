<?php
session_start();
require_once '../database/db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin') {
    header("Location: index.php?error=access_denied");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name'])) {
    $name = trim($_POST['name']);
    if (empty($name)) {
        echo json_encode(['success' => false, 'message' => 'Пустое имя']);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO sizes (name) VALUES (?)");
    if ($stmt->execute([$name])) {
        echo json_encode(['success' => true, 'id' => $conn->lastInsertId(), 'name' => $name]);
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'Ошибка базы данных']);

