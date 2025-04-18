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

// --- Обрабатываем сохранение товара (добавление/удаление изображений) ---
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Обновление данных товара
    if (isset($_POST['name'])) {
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $price = floatval($_POST['price']);
        $category_id = intval($_POST['category']);
        $category = trim($_POST['category']);

        $subcategory_id = intval($_POST['subcategory']);
        $size_id = intval($_POST['size']);
        $material_id = intval($_POST['material']);
        $sku = trim($_POST['sku']);
        $stock = intval($_POST['stock']);
        $seo_title = trim($_POST['seo_title']);
        $seo_description = trim($_POST['seo_description']);
        $seo_keywords = trim($_POST['seo_keywords']);
        
     header("Location: products.php");
     require_once 'log_helper.php';
 log_admin_action($_SESSION['admin_id'], "Изменил товар ID: $id");
     exit();
    
        // Генерация slug (ЧПУ-ссылки)
        $slug = trim($_POST['name']);
$slug = mb_strtolower($slug, 'UTF-8'); // Приводим к нижнему регистру
$slug = preg_replace('/[^\p{L}\p{N}]+/u', '-', $slug); // Убираем лишние символы
$slug = trim($slug, '-'); // Удаляем лишние дефисы в начале и конце

        
        $stmt = $conn->prepare("UPDATE products 
SET name=?, description=?, price=?, category=?, stock=?, 
    seo_title=?, seo_description=?, seo_keywords=?, slug=? 
WHERE id=?");

$stmt->execute([$name, $description, $price, $category, $stock, 
                $seo_title, $seo_description, $seo_keywords, $slug, $id]);
}

    // Удаление выбранных изображений
    if (isset($_POST['delete_images'])) {
        $images_to_delete = $_POST['delete_images'];

        foreach ($images_to_delete as $img) {
            if (file_exists("../" . $img)) {
                unlink("../" . $img);
            }
        }

        // Обновляем изображения в базе
        $new_images = array_values(array_diff($current_images, $images_to_delete));
        $stmt = $conn->prepare("UPDATE products SET images = ? WHERE id = ?");
        $stmt->execute([json_encode($new_images), $id]);

        // Обновляем переменную для отображения оставшихся фото
        $current_images = $new_images;
    }

    // Добавление новых изображений
    if (!empty($_FILES['images']['name'][0])) {
        $upload_dir = __DIR__ . '/../assets/products/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $image_paths = $current_images;
        foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['images']['size'][$key] > 0) {
                $file_ext = strtolower(pathinfo($_FILES['images']['name'][$key], PATHINFO_EXTENSION));
                $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
                if (!in_array($file_ext, $allowed_ext)) {
                    die("Недопустимый формат файла: {$_FILES['images']['name'][$key]}");
                }

                $file_name = md5(uniqid(rand(), true)) . "." . $file_ext;
                $file_path = $upload_dir . $file_name;
                if (move_uploaded_file($tmp_name, $file_path)) {
                    $image_paths[] = 'assets/products/' . $file_name;
                } else {
                    die("Ошибка загрузки файла: {$_FILES['images']['name'][$key]}");
                }
            }
        }

        // Оставляем максимум 5 фото
        $image_paths = array_slice($image_paths, 0, 5);

        // Обновляем изображения в базе
        $stmt = $conn->prepare("UPDATE products SET images = ? WHERE id = ?");
        $stmt->execute([json_encode($image_paths), $id]);

        // Обновляем переменную для отображения загруженных фото
        $current_images = $image_paths;
    }

    // Перенаправление после успешного сохранения
    header("Location: edit_product.php?id=$id&success=1");
    exit();
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Редактировать товар</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<h2>Редактировать товар</h2>

<?php if (isset($_GET['success'])): ?>
    <p style="color: green;">✔ Изменения сохранены успешно!</p>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">
    <label>Название:</label>
    <input type="text" name="name" value="<?= htmlspecialchars($product['name']); ?>" required><br><br>

    <label>Описание:</label>
    <textarea name="description" required><?= htmlspecialchars($product['description']); ?></textarea><br><br>

    <label>Цена:</label>
    <input type="number" name="price" step="0.01" value="<?= $product['price']; ?>" required><br><br>

    <label></label>
<select name="category" id="category" onchange="loadSubcategories(this.value)">
    <option value="">Выберите категорию</option>
    <?php foreach ($categories as $cat): ?>
        <option value="<?= $cat['id']; ?>" <?= ($product['category'] == $cat['id']) ? 'selected' : ''; ?>>
            <?= htmlspecialchars($cat['name']); ?>
        </option>
    <?php endforeach; ?>
</select>
<a href="categories.php">➕</a><br><br>

    <label></label>
    <select name="subcategory" id="subcategory" required>
    <option value="">Подкатегория:</option>
        <?php foreach ($subcategories as $sub): ?>
            <option value="<?= $sub['id']; ?>"><?= htmlspecialchars($sub['name']); ?></option>
        <?php endforeach; ?>
    </select>
    <a href="subcategories.php">➕</a>
    <br><br>

    <label></label>
    <select name="size" required>
        <option value="">Размер:</option>
        <?php foreach ($sizes as $size): ?>
            <option value="<?= $size['id']; ?>"><?= htmlspecialchars($size['name']); ?></option>
        <?php endforeach; ?>
    </select>
    <a href="sizes.php">➕</a>
    <br><br>

    <label></label>
    <select name="material" required>
        <option value="">материал:</option>
        <?php foreach ($materials as $material): ?>
            <option value="<?= $material['id']; ?>"><?= htmlspecialchars($material['name']); ?></option>
        <?php endforeach; ?>
    </select>
    <a href="materials.php">➕</a>
    <br><br>

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
    <div>
        <?php foreach ($current_images as $img): ?>
            <div style="display: inline-block; margin-right: 10px; text-align: center;">
                <img src="/mysterymakers/<?= $img; ?>" width="100"><br>
                <input type="checkbox" name="delete_images[]" value="<?= $img; ?>">
            </div>
        <?php endforeach; ?>
    </div>
    <button type="submit">🗑 Удалить выбранные изображения</button>

    <h3>Добавить новые изображения (до 5 файлов)</h3>
    <input type="file" name="images[]" multiple accept="image/*"><br><br>

    <button type="submit">💾 Сохранить изменения</button>
</form>
<script>
function loadSubcategories(categoryId) {
    let subcategorySelect = document.getElementById("subcategory");

    // Очищаем старые значения
    subcategorySelect.innerHTML = '<option value="">Загрузка...</option>';

    if (!categoryId) {
        subcategorySelect.innerHTML = '<option value="">Сначала выберите категорию</option>';
        return;
    }

    // Запрос к серверу
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

// Загружаем подкатегории при загрузке страницы
document.addEventListener("DOMContentLoaded", function () {
    let selectedCategory = document.getElementById("category").value;
    if (selectedCategory) {
        loadSubcategories(selectedCategory);
    }
});
</script>

</body>
</html>
