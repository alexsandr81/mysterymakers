<?php
require_once '../database/db.php';

function log_admin_action($admin_id, $action) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO admin_logs (admin_id, action) VALUES (?, ?)");
    $stmt->execute([$admin_id, $action]);
}
?>
