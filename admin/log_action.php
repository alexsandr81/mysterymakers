<?php
// Запускаем сессию, только если она не активна
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../database/db.php';

function logAdminAction($conn, $admin_id, $action, $details = null) {
    $stmt = $conn->prepare("INSERT INTO admin_logs (admin_id, action, details) VALUES (?, ?, ?)");
    $stmt->execute([$admin_id, $action, $details]);
}
?>