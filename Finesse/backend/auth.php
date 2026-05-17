<?php
session_start();
require_once __DIR__ . '/db.php'; // db.php already pulls in config.php

// Allow same-origin AJAX
header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

function recaptcha_enabled(): bool {
    return defined('RECAPTCHA_SITE_KEY') && RECAPTCHA_SITE_KEY !== ''
        && defined('RECAPTCHA_SECRET_KEY') && RECAPTCHA_SECRET_KEY !== '';
}

function captcha_new(): array {
    $forceMath = ($_GET['force'] ?? '') === 'math';

    if (!$forceMath && recaptcha_enabled()) {
        $_SESSION['captcha_mode'] = 'recaptcha';
        return ['ok' => true, 'type' => 'recaptcha', 'siteKey' => RECAPTCHA_SITE_KEY];
    }

    $_SESSION['captcha_mode'] = 'math';
    $a = random_int(2, 12);
    $b = random_int(1, 9);
    $op = random_int(0, 1) ? '+' : '-';
    if ($op === '-' && $b > $a) {
        [$a, $b] = [$b, $a];
    }
    $ans = $op === '+' ? ($a + $b) : ($a - $b);
    $_SESSION['captcha_answer'] = (string)$ans;
    $_SESSION['captcha_ts'] = time();
    return ['ok' => true, 'type' => 'math', 'question' => "$a $op $b = ?"];
}

function captcha_verify(): void {
    // Prefer Google reCAPTCHA if configured
    $mode = (string)($_SESSION['captcha_mode'] ?? '');
    if ($mode !== 'math' && recaptcha_enabled()) {
        $token = trim((string)($_POST['g-recaptcha-response'] ?? ''));
        if ($token === '') json_out(['ok' => false, 'msg' => 'Please complete the reCAPTCHA']);
        $payload = http_build_query([
            'secret' => RECAPTCHA_SECRET_KEY,
            'response' => $token,
            'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
        ]);
        $ctx = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => $payload,
                'timeout' => 10,
            ]
        ]);
        $raw = @file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, $ctx);
        $res = $raw ? json_decode($raw, true) : null;
        if (empty($res['success'])) {
            json_out(['ok' => false, 'msg' => 'reCAPTCHA verification failed']);
        }
        // If recaptcha passes, clear math mode if it was previously set
        unset($_SESSION['captcha_mode']);
        return;
    }

    $expected = $_SESSION['captcha_answer'] ?? null;
    $ts = (int)($_SESSION['captcha_ts'] ?? 0);
    $given = trim((string)($_POST['captcha'] ?? ''));
    if (!$expected || !$ts) {
        json_out(['ok' => false, 'msg' => 'Captcha required. Refresh and try again.']);
    }
    if (time() - $ts > 600) {
        unset($_SESSION['captcha_answer'], $_SESSION['captcha_ts']);
        json_out(['ok' => false, 'msg' => 'Captcha expired. Refresh and try again.']);
    }
    $givenNorm = preg_replace('/\s+/', '', $given);
    if ($givenNorm === '' || !hash_equals((string)$expected, $givenNorm)) {
        json_out(['ok' => false, 'msg' => 'Incorrect captcha']);
    }
    unset($_SESSION['captcha_answer'], $_SESSION['captcha_ts'], $_SESSION['captcha_mode']);
}

switch ($action) {

    case 'captcha': {
        json_out(captcha_new());
    }

    case 'signup': {
        captcha_verify();
        $name     = clean($_POST['name'] ?? '');
        $email    = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $password = $_POST['password'] ?? '';

        if (!$name || !$email || strlen($password) < 6) {
            json_out(['ok' => false, 'msg' => 'Please fill all fields correctly (password min 6 chars)']);
        }
        if (strlen($password) > 64) {
            json_out(['ok' => false, 'msg' => 'Password too long']);
        }

        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            json_out(['ok' => false, 'msg' => 'Email already registered']);
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare('INSERT INTO users (name, email, password) VALUES (?, ?, ?)');
        $stmt->execute([$name, $email, $hash]);

        $_SESSION['user_id']   = (int) $pdo->lastInsertId();
        $_SESSION['user_name'] = $name;
        json_out(['ok' => true, 'redirect' => 'dashboard.html']);
    }

    case 'login': {
        captcha_verify();
        $email    = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $password = $_POST['password'] ?? '';

        if (!$email || !$password) {
            json_out(['ok' => false, 'msg' => 'Missing credentials']);
        }

        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $u = $stmt->fetch();

        if (!$u || !password_verify($password, $u['password'])) {
            json_out(['ok' => false, 'msg' => 'Invalid email or password']);
        }

        $_SESSION['user_id']   = (int) $u['id'];
        $_SESSION['user_name'] = $u['name'];
        json_out(['ok' => true, 'redirect' => 'dashboard.html']);
    }

    case 'logout': {
        session_destroy();
        // Return JSON so JS can redirect, or redirect directly
        header('Location: ../frontend/index.html');
        exit;
    }

    case 'me': {
        if (!isset($_SESSION['user_id'])) {
            json_out(['ok' => false]);
        }
        json_out([
            'ok'   => true,
            'id'   => $_SESSION['user_id'],
            'name' => $_SESSION['user_name'],
        ]);
    }

    default:
        json_out(['ok' => false, 'msg' => 'Unknown action']);
}