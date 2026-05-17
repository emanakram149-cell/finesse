<?php
/**
 * Finesse — Global Search Endpoint
 * Place at:  Aatif/backend/search.php
 *
 * GET  ?q=query        → search items, outfits, planner entries
 * GET  ?q=&clear=1     → returns empty (used to reset UI)
 *
 * Requires active session (user must be logged in).
 * Returns: { ok, results: [...], query, counts: { items, outfits, plans } }
 */

session_start();
require_once __DIR__ . '/db.php';

/* ── auth guard ─────────────────────────────────────────────── */
if (!isset($_SESSION['user_id'])) {
    json_out(['ok' => false, 'msg' => 'Not authenticated']);
}
$uid = (int)$_SESSION['user_id'];

/* ── get & validate query ───────────────────────────────────── */
$q = trim($_GET['q'] ?? '');

// Return empty results for very short queries
if (mb_strlen($q) < 2) {
    json_out(['ok' => true, 'results' => [], 'query' => $q,
              'counts' => ['items' => 0, 'outfits' => 0, 'plans' => 0]]);
}

// Sanitize the query (no SQL injection — we use prepared statements,
// but we still clean whitespace and limit length)
$q    = mb_substr($q, 0, 80);
$like = '%' . $q . '%';

$results = [];
$counts  = ['items' => 0, 'outfits' => 0, 'plans' => 0];

/* ── 1. Search closet ITEMS ─────────────────────────────────── */
// Searches: item name, color, style tag, category name
$st = $pdo->prepare(
    'SELECT i.id, i.name, i.color, i.style_tag, i.image,
            c.name AS category, c.slug
     FROM items i
     JOIN categories c ON c.id = i.category_id
     WHERE i.user_id = ?
       AND (i.name LIKE ? OR i.color LIKE ? OR i.style_tag LIKE ? OR c.name LIKE ?)
     ORDER BY i.created_at DESC
     LIMIT 6'
);
$st->execute([$uid, $like, $like, $like, $like]);
$items = $st->fetchAll();
$counts['items'] = count($items);

foreach ($items as $item) {
    $sub  = $item['category'];
    $sub .= $item['color']     ? ' · ' . ucfirst($item['color'])     : '';
    $sub .= $item['style_tag'] ? ' · ' . ucfirst($item['style_tag']) : '';
    $results[] = [
        'type'  => 'item',
        'id'    => (int)$item['id'],
        'title' => $item['name'],
        'sub'   => $sub,
        'image' => $item['image'],
        'icon'  => '❖',
        'url'   => 'closet.html',
        'slug'  => $item['slug'],
    ];
}

/* ── 2. Search saved OUTFITS ────────────────────────────────── */
$st = $pdo->prepare(
    'SELECT id, name, created_at
     FROM outfits
     WHERE user_id = ? AND name LIKE ?
     ORDER BY created_at DESC
     LIMIT 4'
);
$st->execute([$uid, $like]);
$outfits = $st->fetchAll();
$counts['outfits'] = count($outfits);

foreach ($outfits as $o) {
    $results[] = [
        'type'  => 'outfit',
        'id'    => (int)$o['id'],
        'title' => $o['name'],
        'sub'   => 'Saved Look · ' . date('M j, Y', strtotime($o['created_at'])),
        'image' => null,
        'icon'  => '✦',
        'url'   => 'diva.html',
    ];
}

/* ── 3. Search PLANNER entries ──────────────────────────────── */
// Searches: planner note, outfit name, date
$st = $pdo->prepare(
    'SELECT p.id, p.date, p.note, o.name AS outfit_name
     FROM planner p
     LEFT JOIN outfits o ON o.id = p.outfit_id
     WHERE p.user_id = ?
       AND (p.note LIKE ? OR o.name LIKE ? OR p.date LIKE ?)
     ORDER BY p.date DESC
     LIMIT 4'
);
$st->execute([$uid, $like, $like, $like]);
$plans = $st->fetchAll();
$counts['plans'] = count($plans);

foreach ($plans as $p) {
    $title = $p['outfit_name'] ?? 'Custom plan';
    $sub   = $p['date'];
    $sub  .= $p['note'] ? ' · ' . $p['note'] : '';
    $results[] = [
        'type'  => 'plan',
        'id'    => (int)$p['id'],
        'title' => $title,
        'sub'   => $sub,
        'image' => null,
        'icon'  => '▣',
        'url'   => 'planner.html',
    ];
}

/* ── respond ─────────────────────────────────────────────────── */
json_out([
    'ok'      => true,
    'results' => $results,
    'query'   => $q,
    'counts'  => $counts,
    'total'   => count($results),
]);