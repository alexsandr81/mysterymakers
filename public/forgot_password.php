<?php
session_start();
require_once '../database/db.php';
require_once '../PHPMailer/src/PHPMailer.php';
require_once '../PHPMailer/src/SMTP.php';
require_once '../PHPMailer/src/Exception.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email']);

    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $reset_token = bin2hex(random_bytes(32)); // Генерируем токен
        $stmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_token_created_at = NOW() WHERE email = ?");
        $stmt->execute([$reset_token, $email]);

        $reset_link = "http://localhost/mysterymakers/public/reset_password.php?token=$reset_token";

        // Настройка PHPMailer
        $mail = new PHPMailer(true);
        try {
            // Настройки SMTP
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'alexsandr81aa@gmail.com';
            $mail->Password = 'kgjf ayxi xwcg yqaq'; // Убедись, что пароль приложения актуален
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
            $_SESSION['message'] = "Ссылка для сброса пароля отправлена на ваш email!";
            header("Location: forgot_password.php");
            exit();
        } catch (Exception $e) {
            $error = "Ошибка отправки письма: {$mail->ErrorInfo}";
        }
    } else {
        $error = "Email не найден!";
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Восстановление пароля</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>

<?php include 'header.php'; ?>

<main>
    <h2>Восстановление пароля</h2>

    <?php if (isset($_SESSION['message'])): ?>
        <p style="color: green;"><?php echo htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?></p>
    <?php endif; ?>
    <?php if (isset($error)): ?>
        <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <form method="POST" action="">
        <label>Email:</label>
        <input type="email" name="email" required><br><br>
        <button type="submit">Отправить ссылку</button>
    </form>
</main>

<?php include 'footer.php'; ?>

</body>
</html>