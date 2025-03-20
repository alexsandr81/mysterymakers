<?php
require_once '../database/db.php';

if (!isset($_GET['category_id'])) {
    echo json_encode([]);
    exit();
}

$category_id = intval($_GET['category_id']);

$stmt = $conn->prepare("SELECT * FROM subcategories WHERE category_id = ? ORDER BY name ASC");
$stmt->execute([$category_id]);
$subcategories = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($subcategories);
?>
