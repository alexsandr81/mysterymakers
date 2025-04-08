<?php
session_start();
require_once '../database/db.php'; // Подключаем базу данных
require_once 'C:/xampp/htdocs/mysterymakers/PHPMailer/src/PHPMailer.php';
require_once 'C:/xampp/htdocs/mysterymakers/PHPMailer/src/SMTP.php';
require_once 'C:/xampp/htdocs/mysterymakers/PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email']);

    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $reset_token = bin2hex(random_bytes(32)); // Генерируем токен
        $stmt = $conn->prepare("UPDATE users SET reset_token = ? WHERE email = ?");
        $stmt->execute([$reset_token, $email]);

        $reset_link = "http://localhost/mysterymakers/public/reset_password.php?token=$reset_token";

        // Настройка PHPMailer
        $mail = new PHPMailer(true);
        try {
            // Настройки SMTP (пример для Gmail, настрой под свой сервер)
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com'; // Укажи свой SMTP-сервер
            $mail->SMTPAuth = true;
            $mail->Username = 'alexsandr81aa@gmail.com'; // Твой email
            $mail->Password = 'kgjf ayxi xwcg yqaq'; // Пароль приложения или SMTP-пароль
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            // Отправитель и получатель
            $mail->setFrom('your_email@gmail.com', 'MysteryMakers');
            $mail->addAddress($email);

            // Кодировка и тема
            $mail->CharSet = 'UTF-8';
            $mail->Subject = 'Сброс пароля для MysteryMakers';
            $mail->isHTML(true);
            $mail->Body = "<p>Здравствуйте!</p><p>Вы запросили сброс пароля. Перейдите по ссылке, чтобы установить новый пароль:</p><p><a href='$reset_link'>$reset_link</a></p><p>Если вы не запрашивали сброс, проигнорируйте это письмо.</p>";
            $mail->AltBody = "Здравствуйте! Вы запросили сброс пароля. Перейдите по ссылке: $reset_link. Если вы не запрашивали сброс, проигнорируйте это письмо.";

            $mail->send();
            echo "<p>Ссылка для сброса пароля отправлена на ваш email!</p>";
        } catch (Exception $e) {
            echo "<p style='color:red;'>Ошибка отправки письма: {$mail->ErrorInfo}</p>";
        }
    } else {
        echo "<p style='color:red;'>Email не найден!</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Восстановление пароля</title>
</head>
<body>

<h2>Восстановление пароля</h2>

<form method="POST" action="">
    <label>Email:</label>
    <input type="email" name="email" required><br><br>
    <button type="submit">Отправить ссылку</button>
</form>

</body>
</html>
<?php include 'footer.php'; ?>