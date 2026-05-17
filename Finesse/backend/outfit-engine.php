<?php
session_start();
require_once __DIR__ . '/db.php';
if (!isset($_SESSION['user_id'])) json_out(['ok'=>false,'msg'=>'Auth required']);
$uid = (int)$_SESSION['user_id'];
$action = $_GET['action'] ?? 'recommend';

// Color harmony rules (very simple but effective for demo)
$harmony = [
  'black'  => ['white','beige','gold','gray','ivory','red'],
  'white'  => ['black','beige','gold','navy','gray'],
  'beige'  => ['black','white','gold','brown','ivory'],
  'ivory'  => ['black','beige','gold','navy'],
  'gold'   => ['black','white','beige','navy'],
  'gray'   => ['black','white','navy','beige'],
  'navy'   => ['white','beige','gold','gray'],
  'red'    => ['black','white','gold'],
  'brown'  => ['beige','white','gold'],
];

function pickOne($arr){ return $arr ? $arr[array_rand($arr)] : null; }

function fetchByCat($pdo,$uid,$slug){
  $st = $pdo->prepare('SELECT i.* FROM items i JOIN categories c ON c.id=i.category_id WHERE i.user_id=? AND c.slug=?');
  $st->execute([$uid,$slug]); return $st->fetchAll();
}

if ($action === 'recommend') {
  $weather = strtolower($_GET['weather'] ?? 'mild'); // hot|cold|rain|mild
  $temp = (float)($_GET['temp'] ?? 20);

  $top = fetchByCat($pdo,$uid,'tops');
  $bot = fetchByCat($pdo,$uid,'bottoms');
  $dress = fetchByCat($pdo,$uid,'dresses');
  $outer = fetchByCat($pdo,$uid,'outerwear');
  $shoes = fetchByCat($pdo,$uid,'shoes');
  $acc = fetchByCat($pdo,$uid,'accessories');

  $picks = [];
  if ($weather === 'hot' || $temp >= 26) {
    if ($dress) $picks[] = pickOne($dress);
    else { if($top)$picks[]=pickOne($top); if($bot)$picks[]=pickOne($bot); }
  } elseif ($weather === 'cold' || $temp <= 10) {
    if($top)$picks[]=pickOne($top);
    if($bot)$picks[]=pickOne($bot);
    if($outer)$picks[]=pickOne($outer);
  } elseif ($weather === 'rain') {
    if($top)$picks[]=pickOne($top);
    if($bot)$picks[]=pickOne($bot);
    if($outer)$picks[]=pickOne($outer);
  } else {
    if($top)$picks[]=pickOne($top); if($bot)$picks[]=pickOne($bot);
  }
  if($shoes)$picks[]=pickOne($shoes);
  if($acc)$picks[]=pickOne($acc);

  json_out(['ok'=>true,'picks'=>array_values(array_filter($picks)),'weather'=>$weather,'temp'=>$temp]);
}

if ($action === 'save') {
  $name = clean($_POST['name'] ?? 'Untitled Look');
  $ids = $_POST['item_ids'] ?? '[]';
  $st = $pdo->prepare('INSERT INTO outfits (user_id,name,item_ids) VALUES (?,?,?)');
  $st->execute([$uid,$name,$ids]);
  json_out(['ok'=>true,'id'=>$pdo->lastInsertId()]);
}

if ($action === 'list') {
  $st = $pdo->prepare('SELECT * FROM outfits WHERE user_id=? ORDER BY created_at DESC');
  $st->execute([$uid]);
  json_out(['ok'=>true,'outfits'=>$st->fetchAll()]);
}

json_out(['ok'=>false]);
