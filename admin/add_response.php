<?php
session_start();
require_once '../database/db.php';

if (!isset($_SESSION['admin_id']) || !isset($_POST['review_id'], $_POST['response'])) {
    header("Location: reviews.php");
    exit();
}

$review_id = intval($_POST['review_id']);
$response = trim($_POST['response']);

$stmt = $conn->prepare("UPDATE reviews SET admin_response = ?, response_date = NOW() WHERE id = ?");
$stmt->execute([$response, $review_id]);

header("Location: reviews.php");
exit();
?>
