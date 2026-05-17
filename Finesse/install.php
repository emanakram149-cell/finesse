<?php
require_once __DIR__ . '/backend/config.php';

$results = [];

function run($pdo, $label, $sql) {
    global $results;
    try {
        $pdo->exec($sql);
        $results[] = ['ok' => true, 'label' => $label];
    } catch (PDOException $e) {
        $results[] = ['ok' => false, 'label' => $label, 'err' => $e->getMessage()];
    }
}

try {
    $pdo = new PDO(
        sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME),
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    run($pdo, 'users table', "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(150) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        avatar VARCHAR(255) DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");

    run($pdo, 'categories table', "CREATE TABLE IF NOT EXISTS categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(80) NOT NULL UNIQUE,
        slug VARCHAR(80) NOT NULL UNIQUE
    ) ENGINE=InnoDB");

    run($pdo, 'categories data', "INSERT IGNORE INTO categories (name, slug) VALUES
        ('Dresses','dresses'),('Tops','tops'),('Bottoms','bottoms'),
        ('Jumpsuits & Rompers','jumpsuits'),('Outerwear','outerwear'),
        ('Handbags','handbags'),('Jewelry','jewelry'),('Shoes','shoes'),
        ('Accessories','accessories'),('Swimsuits','swimsuits'),('Sets','sets')");

    run($pdo, 'items table', "CREATE TABLE IF NOT EXISTS items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        category_id INT NOT NULL,
        name VARCHAR(120) NOT NULL,
        color VARCHAR(40) DEFAULT NULL,
        style_tag VARCHAR(60) DEFAULT NULL,
        image VARCHAR(255) NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");

    run($pdo, 'outfits table', "CREATE TABLE IF NOT EXISTS outfits (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        name VARCHAR(120) NOT NULL,
        item_ids TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");

    run($pdo, 'planner table', "CREATE TABLE IF NOT EXISTS planner (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        outfit_id INT DEFAULT NULL,
        date DATE NOT NULL,
        note VARCHAR(255) DEFAULT NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (outfit_id) REFERENCES outfits(id) ON DELETE SET NULL
    ) ENGINE=InnoDB");

    run($pdo, 'feedback table', "CREATE TABLE IF NOT EXISTS feedback (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT DEFAULT NULL,
        message VARCHAR(1500) NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");

    run($pdo, 'admin table', "CREATE TABLE IF NOT EXISTS admin (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(150) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        name VARCHAR(100) NOT NULL DEFAULT 'Admin'
    ) ENGINE=InnoDB");

    run($pdo, 'admin user', "INSERT IGNORE INTO admin (email, password, name) VALUES
        ('admin@finesse.com', '\$2y\$10\$sBIJwuP7TEBMvc1rDx0zWOzw49x5pATtyWxq1Zgdz1rLMcVge1ofy', 'Laleh Admin')");

    $allOk = !in_array(false, array_column($results, 'ok'));

} catch (PDOException $e) {
    $connError = $e->getMessage();
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Finesse — Database Setup</title>
<style>
body { font-family: sans-serif; max-width: 600px; margin: 60px auto; padding: 0 20px; background: #0f172a; color: #e2e8f0; }
h1 { font-size: 22px; margin-bottom: 24px; }
.row { display: flex; align-items: center; gap: 12px; padding: 10px 0; border-bottom: 1px solid #1e293b; }
.icon { font-size: 18px; }
.label { flex: 1; font-size: 14px; }
.err { font-size: 12px; color: #f87171; margin-top: 4px; }
.success-box { background: #064e3b; border: 1px solid #059669; border-radius: 8px; padding: 16px 20px; margin-top: 24px; }
.error-box { background: #7f1d1d; border: 1px solid #dc2626; border-radius: 8px; padding: 16px 20px; margin-top: 24px; }
.warn { background: #78350f; border: 1px solid #d97706; border-radius: 8px; padding: 12px 16px; margin-top: 20px; font-size: 13px; }
</style>
</head>
<body>
<h1>🗄️ Finesse — Database Setup</h1>

<?php if (isset($connError)): ?>
<div class="error-box">
    <b>❌ Database connection failed</b><br>
    <small><?= htmlspecialchars($connError) ?></small>
</div>
<?php else: ?>
    <?php foreach ($results as $r): ?>
    <div class="row">
        <span class="icon"><?= $r['ok'] ? '✅' : '❌' ?></span>
        <div class="label">
            <?= htmlspecialchars($r['label']) ?>
            <?php if (!$r['ok']): ?>
            <div class="err"><?= htmlspecialchars($r['err']) ?></div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>

    <?php if ($allOk): ?>
    <div class="success-box">
        <b>✅ All tables created successfully!</b><br><br>
        Website ab kaam karti hai →
        <a href="/frontend/index.html" style="color:#34d399">finesse-main-production.up.railway.app/frontend/index.html</a>
    </div>
    <?php else: ?>
    <div class="error-box"><b>❌ Kuch errors aaye — upar dekho</b></div>
    <?php endif; ?>
<?php endif; ?>

<div class="warn">
    ⚠️ <b>Kaam ho jaane ke baad yeh file delete kar dena:</b> GitHub se <code>install.php</code> hatao aur redeploy karo.
</div>
</body>
</html>
