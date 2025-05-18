<?php
session_start();
require_once '../database/db.php';
require_once '../includes/security.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin') {
    header("Location: index.php?error=access_denied");
    exit();
}

// Генерация CSRF-токена
$csrf_token = generateCsrfToken();

$id = $_GET['id'] ?? 0;
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    die("Товар не найден!");
}

$categories = $conn->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$subcategories = $conn->query("SELECT * FROM subcategories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$sizes = $conn->query("SELECT * FROM sizes ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$materials = $conn->query("SELECT * FROM materials ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

$current_images = json_decode($product['images'], true) ?? [];

$errors = [];
$success = isset($_GET['success']) ? "Изменения сохранены успешно!" : '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Проверка CSRF-токена
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        $errors[] = "Недействительный запрос.";
    } else {
        // Регенерация CSRF-токена
        generateCsrfToken();

        // Обновление данных товара
        if (isset($_POST['name'])) {
            $name = trim($_POST['name']);
            $description = trim($_POST['description']);
            $price = floatval($_POST['price']);
            $category_id = intval($_POST['category']);
            $subcategory_id = intval($_POST['subcategory']);
            $size_id = intval($_POST['size']);
            $material_id = intval($_POST['material']);
            $sku = trim($_POST['sku']);
            $stock = intval($_POST['stock']);
            $seo_title = trim($_POST['seo_title']);
            $seo_description = trim($_POST['seo_description']);
            $seo_keywords = trim($_POST['seo_keywords']);

            // Валидация
            if (empty($name)) $errors[] = "Название обязательно.";
            if ($price <= 0) $errors[] = "Цена должна быть больше 0.";
            if (!$category_id) $errors[] = "Выберите категорию.";
            if (!$subcategory_id) $errors[] = "Выберите подкатегорию.";
            if ($stock < 0) $errors[] = "Количество не может быть отрицательным.";
            if (empty($sku)) $errors[] = "Артикул обязателен.";

            // Генерация slug
            $slug = mb_strtolower(trim($name), 'UTF-8');
            $slug = preg_replace('/[^\p{L}\p{N}]+/u', '-', $slug);
            $slug = trim($slug, '-');

            if (empty($errors)) {
                try {
                    $stmt = $conn->prepare("
                        UPDATE products 
                        SET name=?, description=?, price=?, category_id=?, subcategory=?, size=?, material=?, sku=?, stock=?, 
                            seo_title=?, seo_description=?, seo_keywords=?, slug=? 
                        WHERE id=?");
                    $stmt->execute([
                        $name, $description, $price, $category_id, $subcategory_id, $size_id, $material_id, $sku, $stock,
                        $seo_title, $seo_description, $seo_keywords, $slug, $id
                    ]);

                    require_once 'log_helper.php';
                    log_admin_action($_SESSION['admin_id'], "Изменил товар ID: $id");
                } catch (Exception $e) {
                    $errors[] = "Ошибка базы данных: " . $e->getMessage();
                }
            }
        }

        // Удаление выбранных изображений
        if (isset($_POST['delete_images'])) {
            $images_to_delete = $_POST['delete_images'];
            foreach ($images_to_delete as $img) {
                if (file_exists("../" . $img)) {
                    unlink("../" . $img);
                }
            }
            $new_images = array_values(array_diff($current_images, $images_to_delete));
            $stmt = $conn->prepare("UPDATE products SET images = ? WHERE id = ?");
            $stmt->execute([json_encode($new_images), $id]);
            $current_images = $new_images;
        }
        // Добавление новых изображений
        if (!empty($_FILES['images']['name'][0])) {
            $upload_dir = __DIR__ . '/../assets/products/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

            $image_paths = $current_images;
            if (count($_FILES['images']['name']) + count($image_paths) > 5) {
                $errors[] = "Можно загрузить максимум 5 изображений.";
            } else {
                foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                    if ($_FILES['images']['size'][$key] > 0) {
                        $file = $_FILES['images'];
                        $file_ext = strtolower(pathinfo($file['name'][$key], PATHINFO_EXTENSION));
                        $allowed_ext = ['jpg', 'jpeg', 'png'];
                        $max_size = 5 * 1024 * 1024; // 2 МБ

                        if (!in_array($file_ext, $allowed_ext)) {
                            $errors[] = "Недопустимый формат файла: {$file['name'][$key]} (только JPEG/PNG).";
                        } elseif ($file['size'][$key] > $max_size) {
                            $errors[] = "Файл {$file['name'][$key]} превышает 2 МБ.";
                        } else {
                            // Уменьшение размера изображения
                            $image = null;
                            if ($file_ext === 'jpg' || $file_ext === 'jpeg') {
                                $image = imagecreatefromjpeg($file['tmp_name'][$key]);
                            } elseif ($file_ext === 'png') {
                                $image = imagecreatefrompng($file['tmp_name'][$key]);
                            }

                            if ($image) {
                                $max_width = 800;
                                $max_height = 800;
                                $width = imagesx($image);
                                $height = imagesy($image);
                                $ratio = min($max_width / $width, $max_height / $height, 1);
                                $new_width = (int)($width * $ratio);
                                $new_height = (int)($height * $ratio);

                                $new_image = imagecreatetruecolor($new_width, $new_height);
                                if ($file_ext === 'png') {
                                    imagealphablending($new_image, false);
                                    imagesavealpha($new_image, true);
                                }
                                imagecopyresampled($new_image, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

                                $file_name = md5(uniqid(rand(), true)) . "." . $file_ext;
                                $file_path = $upload_dir . $file_name;

                                if ($file_ext === 'jpg' || $file_ext === 'jpeg') {
                                    imagejpeg($new_image, $file_path, 80);
                                } else {
                                    imagepng($new_image, $file_path, 8);
                                }

                                imagedestroy($image);
                                imagedestroy($new_image);
                                $image_paths[] = 'assets/products/' . $file_name;
                            } else {
                                $errors[] = "Ошибка обработки файла: {$file['name'][$key]}.";
                            }
                        }
                    }
                }

                if (empty($errors)) {
                    $stmt = $conn->prepare("UPDATE products SET images = ? WHERE id = ?");
                    $stmt->execute([json_encode($image_paths), $id]);
                    $current_images = $image_paths;
                }
            }
        }

        if (empty($errors)) {
            header("Location: edit_product.php?id=$id&success=1");
            exit();
        }
    }

    $_SESSION['form_errors'] = $errors;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактировать товар - MysteryMakers</title>
    <link rel="stylesheet" href="/mysterymakers/assets/css/styles.css">
</head>
<body>

<h2>Редактировать товар</h2>

<?php if ($success): ?>
    <p class="success"><?= htmlspecialchars($success); ?></p>
<?php endif; ?>
<?php if (!empty($errors)): ?>
    <?php foreach ($errors as $error): ?>
        <p class="error"><?= htmlspecialchars($error); ?></p>
    <?php endforeach; ?>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token); ?>">
    <label>Название:</label>
    <input type="text" name="name" value="<?= htmlspecialchars($product['name']); ?>" required><br><br>

    <label>Описание:</label>
    <textarea name="description" required><?= htmlspecialchars($product['description']); ?></textarea><br><br>

    <label>Цена (грн):</label>
    <input type="number" name="price" step="0.01" value="<?= $product['price']; ?>" required><br><br>

    <label>Категория:</label>
    <select name="category" id="category" onchange="loadSubcategories(this.value)" required>
        <option value="">Выберите категорию</option>
        <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat['id']; ?>" <?= ($product['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                <?= htmlspecialchars($cat['name']); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <button type="button" onclick="openModal('modal-category')">➕</button><br><br>

<label>Подкатегория:</label>
<select name="subcategory" id="subcategory" required>
    <option value="">Выберите подкатегорию</option>
    <?php foreach ($subcategories as $sub): ?>
        <option value="<?= $sub['id']; ?>" <?= ($product['subcategory'] == $sub['id']) ? 'selected' : ''; ?>>
            <?= htmlspecialchars($sub['name']); ?>
        </option>
    <?php endforeach; ?>
</select>
<button type="button" onclick="openModal('modal-subcategory')">➕</button><br><br>

<label>Размер:</label>
<select name="size" required>
    <option value="">Выберите размер</option>
    <?php foreach ($sizes as $size): ?>
        <option value="<?= $size['id']; ?>" <?= ($product['size'] == $size['id']) ? 'selected' : ''; ?>>
            <?= htmlspecialchars($size['name']); ?>
        </option>
    <?php endforeach; ?>
</select>
<button type="button" onclick="openModal('modal-size')">➕</button><br><br>

<label>Материал:</label>
<select name="material" required>
    <option value="">Выберите материал</option>
    <?php foreach ($materials as $material): ?>
        <option value="<?= $material['id']; ?>" <?= ($product['material'] == $material['id']) ? 'selected' : ''; ?>>
            <?= htmlspecialchars($material['name']); ?>
        </option>
    <?php endforeach; ?>
</select>
<button type="button" onclick="openModal('modal-material')">➕</button><br><br>

<label>Артикул (SKU):</label>
<input type="text" name="sku" value="<?= htmlspecialchars($product['sku']); ?>" required><br><br>

<label>Количество:</label>
<input type="number" name="stock" value="<?= $product['stock']; ?>" required><br><br>

<label>SEO Title:</label>
<input type="text" name="seo_title" value="<?= htmlspecialchars($product['seo_title']); ?>"><br><br>

<label>SEO Description:</label>
<textarea name="seo_description"><?= htmlspecialchars($product['seo_description']); ?></textarea><br><br>

<label>SEO Keywords:</label>
<input type="text" name="seo_keywords" value="<?= htmlspecialchars($product['seo_keywords']); ?>"><br><br>

<h3>Изображения товара</h3>
<div class="image-preview">
    <?php foreach ($current_images as $img): ?>
        <div>
            <img src="/mysterymakers/<?= $img; ?>" width="100"><br>
            <input type="checkbox" name="delete_images[]" value="<?= $img; ?>">
        </div>
    <?php endforeach; ?>
</div>
<button type="submit">🗑 Удалить выбранные изображения</button><br><br>

<h3>Добавить новые изображения (до 5 файлов)</h3>
<input type="file" name="images[]" multiple accept="image/jpeg,image/png"><br><br>

<button type="submit">💾 Сохранить изменения</button>
</form>

<!-- Модальные окна -->
<div class="modal" id="modal-category">
<div class="modal-content">
    <h3>Добавить категорию</h3>
    <input type="text" id="input-category" placeholder="Название категории">
    <button onclick="addEntity('category')">Добавить</button>
    <button onclick="closeModal('modal-category')">Отмена</button>
</div>
</div>

<div class="modal" id="modal-subcategory">
<div class="modal-content">
    <h3>Добавить подкатегорию</h3>
    <input type="text" id="input-subcategory" placeholder="Название подкатегории">
    <button onclick="addEntity('subcategory')">Добавить</button>
    <button onclick="closeModal('modal-subcategory')">Отмена</button>
</div>
</div>

<div class="modal" id="modal-size">
<div class="modal-content">
    <h3>Добавить размер</h3>
    <input type="text" id="input-size" placeholder="Название размера">
    <button onclick="addEntity('size')">Добавить</button>
    <button onclick="closeModal('modal-size')">Отмена</button>
</div>
</div>

<div class="modal" id="modal-material">
<div class="modal-content">
    <h3>Добавить материал</h3>
    <input type="text" id="input-material" placeholder="Название материала">
    <button onclick="addEntity('material')">Добавить</button>
    <button onclick="closeModal('modal-material')">Отмена</button>
</div>
</div>

<script>
function loadSubcategories(categoryId) {
let subcategorySelect = document.getElementById("subcategory");
subcategorySelect.innerHTML = '<option value="">Загрузка...</option>';

if (!categoryId) {
    subcategorySelect.innerHTML = '<option value="">Сначала выберите категорию</option>';
    return;
}

fetch("get_subcategories.php?category_id=" + categoryId)
    .then(response => response.json())
    .then(data => {
        subcategorySelect.innerHTML = '<option value="">Выберите подкатегорию</option>';
        data.forEach(subcat => {
            let option = document.createElement("option");
            option.value = subcat.id;
            option.textContent = subcat.name;
            if (subcat.id == "<?= $product['subcategory']; ?>") {
                option.selected = true;
            }
            subcategorySelect.appendChild(option);
        });
    })
    .catch(error => console.error("Ошибка загрузки подкатегорий:", error));
}

document.addEventListener("DOMContentLoaded", function () {
let selectedCategory = document.getElementById("category").value;
if (selectedCategory) {
    loadSubcategories(selectedCategory);
}
});

function openModal(id) {
document.getElementById(id).style.display = 'block';
}
function closeModal(id) {
document.getElementById(id).style.display = 'none';
}
function addEntity(type) {
const input = document.getElementById('input-' + type);
const value = input.value.trim();
if (!value) return alert("Введите значение!");

let body = 'name=' + encodeURIComponent(value);

if (type === 'subcategory') {
    const categorySelect = document.getElementById('category');
    const categoryId = categorySelect.value;
    if (!categoryId) {
        alert("Сначала выберите категорию!");
        return;
    }
    body += '&category_id=' + encodeURIComponent(categoryId);
}

fetch('add_' + type + '.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: body
})
.then(res => res.json())
.then(data => {
    if (data.success) {
        const select = document.getElementById(type);
        const option = document.createElement("option");
        option.value = data.id;
        option.text = data.name;
        option.selected = true;
        select.appendChild(option);
        closeModal('modal-' + type);
        input.value = '';
    } else {
        alert("Ошибка при добавлении: " + (data.message || "неизвестная ошибка"));
    }
})
.catch(() => alert("Ошибка сети"));
}
</script>

</body>
</html>