<?php
session_start();
require_once __DIR__ . '/../db.php';
if (!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit; }

// Handle actions
if (isset($_POST['delete_user'])) { $pdo->prepare('DELETE FROM users WHERE id=?')->execute([(int)$_POST['delete_user']]); }
if (isset($_POST['delete_item'])) { $pdo->prepare('DELETE FROM items WHERE id=?')->execute([(int)$_POST['delete_item']]); }
if (isset($_POST['delete_feedback'])) { $pdo->prepare('DELETE FROM feedback WHERE id=?')->execute([(int)$_POST['delete_feedback']]); }
if (isset($_POST['add_category'])) {
  $n = trim($_POST['cat_name']); $s = strtolower(preg_replace('/[^a-z0-9]+/i','-',$n));
  if ($n) $pdo->prepare('INSERT IGNORE INTO categories (name,slug) VALUES (?,?)')->execute([$n,$s]);
}
if (isset($_POST['delete_category'])) { $pdo->prepare('DELETE FROM categories WHERE id=?')->execute([(int)$_POST['delete_category']]); }

$users = $pdo->query('SELECT id,name,email,created_at FROM users ORDER BY id DESC')->fetchAll();
$items = $pdo->query('SELECT i.*, u.name AS uname, c.name AS cname FROM items i JOIN users u ON u.id=i.user_id JOIN categories c ON c.id=i.category_id ORDER BY i.id DESC LIMIT 50')->fetchAll();
$cats = $pdo->query('SELECT * FROM categories ORDER BY name')->fetchAll();
$fb = $pdo->query('SELECT * FROM feedback ORDER BY id DESC LIMIT 30')->fetchAll();
$counts = [
  'users' => $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
  'items' => $pdo->query('SELECT COUNT(*) FROM items')->fetchColumn(),
  'outfits' => $pdo->query('SELECT COUNT(*) FROM outfits')->fetchColumn(),
  'feedback' => $pdo->query('SELECT COUNT(*) FROM feedback')->fetchColumn(),
];
?>
<!doctype html><html><head><meta charset="utf-8"><title>Laleh Admin</title>
<link rel="icon" href="../../frontend/assets/favicon.svg" type="image/svg+xml">
<link rel="stylesheet" href="../../frontend/css/admin.css"></head>
<body class="admin-app">
<header class="admin-top">
  <div class="brand">LALEH <span>Admin</span></div>
  <div class="who">Hi, <?= htmlspecialchars($_SESSION['admin_name']) ?> · <a href="logout.php">Sign out</a></div>
</header>
<main class="admin-main">
  <section class="stats">
    <div class="stat"><span>Users</span><b><?= $counts['users'] ?></b></div>
    <div class="stat"><span>Items</span><b><?= $counts['items'] ?></b></div>
    <div class="stat"><span>Outfits</span><b><?= $counts['outfits'] ?></b></div>
    <div class="stat"><span>Feedback</span><b><?= $counts['feedback'] ?></b></div>
  </section>

  <section class="panel">
    <h2>Users</h2>
    <table><tr><th>ID</th><th>Name</th><th>Email</th><th>Joined</th><th></th></tr>
    <?php foreach($users as $u): ?>
      <tr><td><?= $u['id'] ?></td><td><?= htmlspecialchars($u['name']) ?></td>
      <td><?= htmlspecialchars($u['email']) ?></td><td><?= $u['created_at'] ?></td>
      <td><form method="post"><button name="delete_user" value="<?= $u['id'] ?>" class="danger">Delete</button></form></td></tr>
    <?php endforeach; ?></table>
  </section>

  <section class="panel">
    <h2>Categories</h2>
    <form method="post" class="row">
      <input name="cat_name" placeholder="New category name" required>
      <button name="add_category" value="1">Add</button>
    </form>
    <ul class="cats">
      <?php foreach($cats as $c): ?>
        <li><?= htmlspecialchars($c['name']) ?>
          <form method="post" style="display:inline"><button name="delete_category" value="<?= $c['id'] ?>" class="danger sm">×</button></form>
        </li>
      <?php endforeach; ?>
    </ul>
  </section>

  <section class="panel">
    <h2>Recent Items</h2>
    <div class="grid-items">
      <?php foreach($items as $i): ?>
        <div class="ii">
          <img src="<?= htmlspecialchars($i['image']) ?>" alt="">
          <div><b><?= htmlspecialchars($i['name']) ?></b><span><?= htmlspecialchars($i['cname']) ?> · <?= htmlspecialchars($i['uname']) ?></span></div>
          <form method="post"><button name="delete_item" value="<?= $i['id'] ?>" class="danger sm">Delete</button></form>
        </div>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="panel">
    <h2>Feedback</h2>
    <?php foreach($fb as $f): ?>
      <div class="fb"><p><?= nl2br(htmlspecialchars($f['message'])) ?></p>
      <small><?= $f['created_at'] ?></small>
      <form method="post"><button name="delete_feedback" value="<?= $f['id'] ?>" class="danger sm">Delete</button></form></div>
    <?php endforeach; ?>
  </section>
</main>
</body></html>
