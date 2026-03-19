<?php
/**
 * API: /api/search.php
 * Returns JSON results for live search (header search bar)
 * GET ?q=termo&limit=5
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: same-origin');

require_once __DIR__ . '/../includes/db.php';

$q     = trim($_GET['q'] ?? '');
$limit = min(10, max(1, (int)($_GET['limit'] ?? 5)));

if (strlen($q) < 2) {
    echo json_encode(['results' => [], 'total' => 0]);
    exit;
}

$like = "%$q%";
$results = [];

// Posts
$posts = dbFetchAll($pdo,
    "SELECT 'post' AS type, title, slug, excerpt AS description, published_at AS date
     FROM posts WHERE status='published' AND (title LIKE ? OR excerpt LIKE ?)
     ORDER BY published_at DESC LIMIT ?",
    [$like, $like, $limit]);

foreach ($posts as $p) {
    $results[] = [
        'type'        => 'Notícia',
        'type_icon'   => 'fa-newspaper',
        'title'       => $p['title'],
        'description' => $p['description'] ? mb_substr(strip_tags($p['description']), 0, 80).'…' : '',
        'url'         => '/pages/post.php?slug='.urlencode($p['slug']),
        'date'        => $p['date'] ? date('d/m/Y', strtotime($p['date'])) : '',
    ];
}

// Events
$events = dbFetchAll($pdo,
    "SELECT 'event' AS type, title, slug, description, event_date AS date
     FROM events WHERE status='published' AND event_date >= CURDATE() AND (title LIKE ? OR description LIKE ?)
     ORDER BY event_date ASC LIMIT ?",
    [$like, $like, $limit]);

foreach ($events as $e) {
    $results[] = [
        'type'        => 'Evento',
        'type_icon'   => 'fa-calendar',
        'title'       => $e['title'],
        'description' => $e['description'] ? mb_substr(strip_tags($e['description']), 0, 80).'…' : '',
        'url'         => '/pages/evento.php?slug='.urlencode($e['slug']),
        'date'        => $e['date'] ? date('d/m/Y', strtotime($e['date'])) : '',
    ];
}

// Documents
$docs = dbFetchAll($pdo,
    "SELECT id, title, description, publication_date AS date, category
     FROM documents WHERE is_public=1 AND status='active' AND (title LIKE ? OR description LIKE ?)
     ORDER BY publication_date DESC LIMIT ?",
    [$like, $like, $limit]);

foreach ($docs as $d) {
    $results[] = [
        'type'        => 'Documento',
        'type_icon'   => 'fa-file-pdf',
        'title'       => $d['title'],
        'description' => $d['description'] ? mb_substr($d['description'], 0, 80).'…' : ($d['category'] ?? ''),
        'url'         => '/pages/download.php?id='.$d['id'],
        'date'        => $d['date'] ? date('d/m/Y', strtotime($d['date'])) : '',
    ];
}

// Sort by relevance: title matches first
usort($results, function($a, $b) use ($q) {
    $aTitle = stripos($a['title'], $q) !== false ? 0 : 1;
    $bTitle = stripos($b['title'], $q) !== false ? 0 : 1;
    return $aTitle - $bTitle;
});

$results = array_slice($results, 0, $limit * 2);

echo json_encode([
    'results' => $results,
    'total'   => count($results),
    'query'   => $q,
]);
