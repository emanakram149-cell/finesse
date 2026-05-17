<?php
session_start();
require_once __DIR__ . '/../db.php';
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim($_POST['email'] ?? '');
  $pass = $_POST['password'] ?? '';
  $st = $pdo->prepare('SELECT * FROM admin WHERE email=?');
  $st->execute([$email]);
  $a = $st->fetch();
  if ($a && password_verify($pass, $a['password'])) {
    $_SESSION['admin_id'] = $a['id'];
    $_SESSION['admin_name'] = $a['name'];
    header('Location: dashboard.php'); exit;
  }
  $err = 'Invalid credentials';
}
?>
<!doctype html><html><head><meta charset="utf-8"><title>Laleh Admin</title>
<link rel="icon" href="../../frontend/assets/favicon.svg" type="image/svg+xml">
<link rel="stylesheet" href="../../frontend/css/admin.css"></head>
<body class="admin-login">
  <form method="post" class="admin-card">
    <h1>LALEH</h1><p class="sub">Admin Console</p>
    <?php if($err) echo '<div class="err">'.htmlspecialchars($err).'</div>'; ?>
    <input name="email" type="email" placeholder="Admin email" required>
    <input name="password" type="password" placeholder="Password" required>
    <button>Sign In</button>
  </form>
</body></html>
