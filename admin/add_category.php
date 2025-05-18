<?php
session_start();
require_once '../database/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin') {
    header("Location: index.php?error=access_denied");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['name'])) {
    $name = trim($_POST['name']);
    if (empty($name)) {
        echo json_encode(['success' => false, 'message' => 'Название обязательно']);
        exit();
    }

    $seo_title = $name;
    $seo_description = "Описание категории $name";
    $seo_keywords = str_replace(' ', ',', $name);

    function transliterate($text) {
        $replace = [
            'а'=>'a','б'=>'b','в'=>'v','г'=>'g','д'=>'d','е'=>'e','ё'=>'e','ж'=>'zh','з'=>'z',
            'и'=>'i','й'=>'y','к'=>'k','л'=>'l','м'=>'m','н'=>'n','о'=>'o','п'=>'p','р'=>'r',
            'с'=>'s','т'=>'t','у'=>'u','ф'=>'f','х'=>'h','ц'=>'ts','ч'=>'ch','ш'=>'sh','щ'=>'sch',
            'ъ'=>'','ы'=>'y','ь'=>'','э'=>'e','ю'=>'yu','я'=>'ya'
        ];
        return strtr(mb_strtolower($text), $replace);
    }

    $slug = transliterate($name);
    $slug = preg_replace('/[^a-z0-9-]+/', '-', strtolower($slug));
    $slug = trim($slug, '-');

    $stmt = $conn->prepare("SELECT COUNT(*) FROM categories WHERE slug = ?");
    $stmt->execute([$slug]);
    if ($stmt->fetchColumn() > 0) {
        $slug .= '-' . time(); // уникализация
    }

    $stmt = $conn->prepare("INSERT INTO categories (name, seo_title, seo_description, seo_keywords, slug) 
                            VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$name, $seo_title, $seo_description, $seo_keywords, $slug]);

    $newId = $conn->lastInsertId();
    echo json_encode([
        'success' => true,
        'id' => $newId,
        'name' => $name
    ]);
    exit();
}

echo json_encode(['success' => false, 'message' => 'Некорректный запрос']);
exit();
