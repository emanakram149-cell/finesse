<?php
session_start();
require_once __DIR__ . '/db.php';

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(['ok' => false, 'msg' => 'POST only']);
}

$msg   = trim($_POST['message'] ?? '');
$words = str_word_count($msg);
if ($words < 1 || $words > 250) {
    json_out(['ok' => false, 'msg' => 'Message must be 1–250 words']);
}

// user_id is null for guests — that's intentional (public feedback form)
$uid = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;

$st = $pdo->prepare('INSERT INTO feedback (user_id, message) VALUES (?, ?)');
$st->execute([$uid, clean($msg)]);
json_out(['ok' => true]);
