<?php
session_start();
require_once '../database/db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$admin_id = $_SESSION['admin_id'];
$edit_id = $_GET['id'] ?? 0;

// Получаем данные администратора, которого хотим изменить
$stmt = $conn->prepare("SELECT * FROM admins WHERE id = ?");
$stmt->execute([$edit_id]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$admin) {
    die("Ошибка: Администратор не найден!");
}

// Запрещаем изменять роль суперадминистратора, если это последний супер
$check_stmt = $conn->prepare("SELECT COUNT(*) FROM admins WHERE role = 'superadmin'");
$check_stmt->execute();
$superadmin_count = $check_stmt->fetchColumn();

if ($admin['role'] == 'superadmin' && $superadmin_count == 1) {
    die("Ошибка: Нельзя изменить роль последнего суперадмина!");
}

// Обновление роли
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $new_role = $_POST['role'];

    // Защита: суперадмин может менять любые роли, но обычный админ не может менять суперадмина
    if ($admin_id != 1 && $new_role == 'superadmin') {
        die("Ошибка: Только супер-администратор может назначать суперадмина!");
    }

    $stmt = $conn->prepare("UPDATE admins SET role = ? WHERE id = ?");
    $stmt->execute([$new_role, $edit_id]);

    header("Location: admins.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Изменить роль администратора</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<h2>Изменить роль администратора</h2>

<form method="POST">
    <label>Новая роль:</label>
    <select name="role">
        <option value="admin" <?= $admin['role'] == 'admin' ? 'selected' : ''; ?>>Администратор</option>
        <option value="moderator" <?= $admin['role'] == 'moderator' ? 'selected' : ''; ?>>Модератор</option>
        <option value="superadmin" <?= $admin['role'] == 'superadmin' ? 'selected' : ''; ?>>Суперадмин</option>
    </select><br><br>

    <button type="submit">Сохранить</button>
</form>

</body>
</html>
