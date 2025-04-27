<!DOCTYPE html>
<html lang="ru">

<head>
  <meta charset="UTF-8">
  <title>Личный кабинет</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      margin: 0;
      background-color: #f8f9fa;
    }

    .container {
      display: flex;
      min-height: 100vh;
    }

    .sidebar {
      width: 250px;
      background-color: #ffffff;
      padding: 20px;
      box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
    }

    .sidebar h2 {
      font-size: 18px;
      margin-bottom: 20px;
    }

    .sidebar a {
      display: block;
      margin-bottom: 15px;
      color: #333;
      text-decoration: none;
    }

    .sidebar a:hover {
      color: #007bff;
    }

    .main-content {
      flex-grow: 1;
      padding: 40px;
      background-color: #f1f3f5;
    }

    .profile-section {
      background: #fff;
      padding: 20px;
      margin-bottom: 20px;
      border-radius: 8px;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
      position: relative;
    }

    .profile-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      cursor: pointer;
    }

    .profile-header h3 {
      margin: 0;
      font-size: 20px;
    }

    .profile-content {
      margin-top: 15px;
      display: none;
    }

    .profile-item {
      margin-bottom: 10px;
    }

    .profile-item strong {
      display: inline-block;
      width: 150px;
    }

    .edit-button {
      display: inline-block;
      margin-top: 15px;
      padding: 10px 20px;
      background-color: #00a046;
      color: #fff;
      text-decoration: none;
      border-radius: 5px;
      font-size: 14px;
    }

    .edit-button:hover {
      background-color: #008a3c;
    }

    .footer-links {
      margin-top: 30px;
    }

    .footer-links a {
      margin-right: 15px;
      text-decoration: none;
      color: #555;
    }
  </style>
</head>

<body>

  <div class="container">
    <div class="sidebar">
      <h2>Меню</h2>

      <a href="#">Заказы</a>

      <a href="#">Корзина</a>
      <a href="#">Списки желаний</a>

      <a href="#">Просмотренные товары</a>

      <a href="#">Отзывы</a>

    </div>

    <div class="main-content">

      <!-- Мой аккаунт -->
      <div class="profile-section">
        <div class="profile-header" onclick="toggleSection(this)">
          <h3>Мой аккаунт</h3>
          <span>➤</span>
        </div>
        <div class="profile-content">
          <div class="profile-item"><strong>Логин (телефон):</strong> +38 (066) 421 94 97</div>
          <div class="profile-item"><strong>Электронная почта (логин):</strong> alexandr81@ukr.net</div>
          <div class="profile-item"><strong>Фамилия:</strong> Чепур</div>
          <div class="profile-item"><strong>Имя:</strong> Александр</div>
          <div class="profile-item"><strong>Отчество:</strong> Николаевич</div>

          <a href="#" class="edit-button">Редактировать</a>
        </div>
      </div>







      <!-- Адрес доставки -->
      <div class="profile-section">
        <div class="profile-header" onclick="toggleSection(this)">
          <h3>Адрес доставки</h3>
          <span>➤</span>
        </div>
        <div class="profile-content">
          <a href="#" class="edit-button">Редактировать</a>
        </div>
      </div>

      <div class="footer-links">

        <a href="#">Связать с Google</a>

      </div>

    </div>
  </div>

  <script>
    function toggleSection(header) {
      const content = header.nextElementSibling;
      const icon = header.querySelector('span');
      if (content.style.display === "block") {
        content.style.display = "none";
        icon.textContent = "➤";
      } else {
        content.style.display = "block";
        icon.textContent = "▼";
      }
    }
  </script>

</body>

</html>