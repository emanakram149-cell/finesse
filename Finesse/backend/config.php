<?php
/**
 * Finesse — Application Configuration
 *
 * ⚠️  NEVER commit real credentials to version control.
 *     All secrets must come from environment variables.
 */

function env_str(string $key, string $default = ''): string
{
    $v = getenv($key);
    if ($v === false) {
        return $default;
    }
    $v = trim((string)$v);
    return $v !== '' ? $v : $default;
}

/** First non-empty env var from a list. */
function env_str_first(array $keys, string $default = ''): string
{
    foreach ($keys as $key) {
        $v = env_str($key, '');
        if ($v !== '') {
            return $v;
        }
    }
    return $default;
}

/* ── Database ─────────────────────────────────────────────────── */
// Railway injects MYSQL* variables automatically.
// You can also set DB_* variables manually in any other host.
define('DB_HOST', env_str_first(['MYSQLHOST',     'DB_HOST'],     'localhost'));
define('DB_PORT', env_str_first(['MYSQLPORT',     'DB_PORT'],     '3306'));
define('DB_NAME', env_str_first(['MYSQLDATABASE', 'DB_DATABASE', 'DB_NAME'], 'railway'));
define('DB_USER', env_str_first(['MYSQLUSER',     'DB_USER'],     'root'));
define('DB_PASS', env_str_first(['MYSQLPASSWORD', 'DB_PASS'],     ''));

// Optional TLS (Railway MySQL supports it)
define('DB_SSL_CA',     env_str('MYSQL_SSL_CA',     ''));
define('DB_SSL_CERT',   env_str('MYSQL_SSL_CERT',   ''));
define('DB_SSL_KEY',    env_str('MYSQL_SSL_KEY',    ''));
define('DB_SSL_VERIFY', env_str('MYSQL_SSL_VERIFY', ''));

/* ── Session ──────────────────────────────────────────────────── */
// Railway containers have an ephemeral filesystem — sessions stored in /tmp
// survive between requests on the same instance but not across restarts.
// For production persistence set SESSION_SAVE_PATH to a shared directory
// or switch to database sessions.
$_sessionPath = env_str('SESSION_SAVE_PATH', '');
if ($_sessionPath !== '') {
    if (!is_dir($_sessionPath)) {
        @mkdir($_sessionPath, 0700, true);
    }
    session_save_path($_sessionPath);
}
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
// Use Secure cookies only when running over HTTPS
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    ini_set('session.cookie_secure', '1');
}

/* ── Site / Upload paths ──────────────────────────────────────── */
// IMPORTANT: Set SITE_URL to your Railway public URL, e.g.
//   https://finesse-production.up.railway.app
// Without this, uploaded image URLs will point to localhost and break.
function detect_site_url(): string {
    // Prefer explicit env var
    $env = env_str('SITE_URL', '');
    if ($env !== '') return rtrim($env, '/');

    // Auto-detect from request (works on Railway)
    if (!empty($_SERVER['HTTP_HOST'])) {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        return $scheme . '://' . $_SERVER['HTTP_HOST'];
    }

    return 'http://localhost';
}

define('SITE_URL',   detect_site_url());
define('UPLOAD_DIR', __DIR__ . '/../frontend/assets/uploads/');
define('UPLOAD_URL', SITE_URL . '/frontend/assets/uploads/');

if (!file_exists(UPLOAD_DIR)) {
    @mkdir(UPLOAD_DIR, 0755, true);
}

/* ── External APIs ────────────────────────────────────────────── */
define('WEATHER_API_KEY',     env_str('WEATHER_API_KEY',     ''));
define('RECAPTCHA_SITE_KEY',  env_str('RECAPTCHA_SITE_KEY',  ''));
define('RECAPTCHA_SECRET_KEY',env_str('RECAPTCHA_SECRET_KEY',''));
