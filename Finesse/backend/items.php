<?php
session_start();
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['user_id'])) json_out(['ok' => false, 'msg' => 'Auth required']);

$uid = (int) $_SESSION['user_id'];
$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

if ($action === 'categories') {
    $st = $pdo->query('SELECT id, name, slug FROM categories ORDER BY name ASC');
    json_out(['ok' => true, 'categories' => $st->fetchAll()]);
}

if ($action === 'list') {
    $category = trim($_GET['category'] ?? '');
    $q = trim($_GET['q'] ?? '');

    $sql = 'SELECT i.id, i.name, i.color, i.style_tag, i.image, i.created_at, c.name AS category, c.slug
            FROM items i
            JOIN categories c ON c.id = i.category_id
            WHERE i.user_id = ?';
    $params = [$uid];

    if ($category !== '') {
        $sql .= ' AND c.slug = ?';
        $params[] = $category;
    }
    if ($q !== '') {
        $sql .= ' AND i.name LIKE ?';
        $params[] = '%' . $q . '%';
    }

    $sql .= ' ORDER BY i.created_at DESC';
    $st = $pdo->prepare($sql);
    $st->execute($params);
    json_out(['ok' => true, 'items' => $st->fetchAll()]);
}

if ($action === 'add') {
    $name = clean($_POST['name'] ?? '');
    $categoryId = (int) ($_POST['category_id'] ?? 0);
    $color = clean($_POST['color'] ?? '');
    $styleTag = clean($_POST['style_tag'] ?? '');

    if ($name === '' || $categoryId <= 0 || !isset($_FILES['image'])) {
        json_out(['ok' => false, 'msg' => 'Missing required fields']);
    }

    $img = $_FILES['image'];
    if (($img['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        json_out(['ok' => false, 'msg' => 'Image upload failed']);
    }

    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    $mime = mime_content_type($img['tmp_name']) ?: '';
    if (!isset($allowed[$mime])) {
        json_out(['ok' => false, 'msg' => 'Invalid image type']);
    }

    $filename = 'item_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $allowed[$mime];
    $target = UPLOAD_DIR . $filename;
    if (!move_uploaded_file($img['tmp_name'], $target)) {
        json_out(['ok' => false, 'msg' => 'Could not save image']);
    }

    $imageUrl = UPLOAD_URL . $filename;
    $st = $pdo->prepare(
        'INSERT INTO items (user_id, category_id, name, color, style_tag, image)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $st->execute([$uid, $categoryId, $name, $color ?: null, $styleTag ?: null, $imageUrl]);

    json_out(['ok' => true, 'id' => (int) $pdo->lastInsertId()]);
}

if ($action === 'delete') {
    $id = (int) ($_POST['id'] ?? 0);
    if ($id <= 0) json_out(['ok' => false, 'msg' => 'Invalid item id']);

    $st = $pdo->prepare('DELETE FROM items WHERE id = ? AND user_id = ?');
    $st->execute([$id, $uid]);
    json_out(['ok' => true]);
}

json_out(['ok' => false, 'msg' => 'Unknown action']);