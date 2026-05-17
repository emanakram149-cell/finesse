<?php
session_start();
require_once __DIR__ . '/db.php';
if (!isset($_SESSION['user_id'])) json_out(['ok'=>false,'msg'=>'Auth']);
$uid = (int)$_SESSION['user_id'];
$action = $_GET['action'] ?? 'list';

if ($action === 'list') {
  $st = $pdo->prepare('SELECT p.*, o.name AS outfit_name FROM planner p LEFT JOIN outfits o ON o.id=p.outfit_id WHERE p.user_id=? ORDER BY p.date DESC');
  $st->execute([$uid]);
  json_out(['ok'=>true,'entries'=>$st->fetchAll()]);
}
if ($action === 'add') {
  $date = $_POST['date'] ?? date('Y-m-d');
  $oid = (int)($_POST['outfit_id'] ?? 0) ?: null;
  $note = clean($_POST['note'] ?? '');
  $st = $pdo->prepare('INSERT INTO planner (user_id,outfit_id,date,note) VALUES (?,?,?,?)');
  $st->execute([$uid,$oid,$date,$note]);
  json_out(['ok'=>true]);
}
if ($action === 'delete') {
  $id = (int)$_POST['id'];
  $st = $pdo->prepare('DELETE FROM planner WHERE id=? AND user_id=?');
  $st->execute([$id,$uid]);
  json_out(['ok'=>true]);
}
json_out(['ok'=>false]);
