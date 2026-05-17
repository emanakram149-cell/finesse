<?php
require_once __DIR__ . '/config.php';

try {
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    // Enable MySQL TLS if configured (best effort; silently ignored if driver lacks constants).
    if (defined('DB_SSL_CA') && DB_SSL_CA !== '' && defined('PDO::MYSQL_ATTR_SSL_CA')) {
        $options[PDO::MYSQL_ATTR_SSL_CA] = DB_SSL_CA;
        if (defined('DB_SSL_CERT') && DB_SSL_CERT !== '' && defined('PDO::MYSQL_ATTR_SSL_CERT')) {
            $options[PDO::MYSQL_ATTR_SSL_CERT] = DB_SSL_CERT;
        }
        if (defined('DB_SSL_KEY') && DB_SSL_KEY !== '' && defined('PDO::MYSQL_ATTR_SSL_KEY')) {
            $options[PDO::MYSQL_ATTR_SSL_KEY] = DB_SSL_KEY;
        }
        if (defined('DB_SSL_VERIFY') && defined('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT')) {
            $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = (string)DB_SSL_VERIFY !== '0';
        }
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        DB_HOST ?: 'localhost',
        DB_PORT ?: '3306',
        DB_NAME ?: 'finesse'
    );

    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    // Show detailed error only in development; hide it in production.
    $debug = (getenv('APP_ENV') === 'development');
    echo json_encode([
        'ok'  => false,
        'msg' => 'Database connection failed',
        'detail' => $debug ? $e->getMessage() : null,
    ]);
    exit;
}

function clean(string $s): string
{
    return htmlspecialchars(trim($s), ENT_QUOTES, 'UTF-8');
}

function json_out(array $d): never
{
    header('Content-Type: application/json');
    echo json_encode($d);
    exit;
}