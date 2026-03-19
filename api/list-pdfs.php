<?php
/**
 * API: /api/list-pdfs.php
 * Lista PDFs de uma página específica (?slug=...) ou todos (?all=1)
 * Fonte primária: tabela documents (integrado com download.php?id=X&view=1)
 * Fallback: filesystem (para PDFs não cadastrados no BD)
 */
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json; charset=utf-8');

$slug = trim($_GET['slug'] ?? '');
$all  = !empty($_GET['all']);

// ── Busca na tabela documents ──────────────────────────────────────────────
try {
    if ($all) {
        $rows = dbFetchAll($pdo,
            "SELECT id, title, file_name, file_size, document_type, category, publication_date
             FROM documents
             WHERE status='active' AND is_public=1 AND file_type='pdf'
             ORDER BY created_at DESC
             LIMIT 200"
        );
    } elseif ($slug) {
        $rows = dbFetchAll($pdo,
            "SELECT id, title, file_name, file_size, document_type, category, publication_date
             FROM documents
             WHERE status='active' AND is_public=1 AND file_type='pdf'
               AND (document_type=? OR category=?)
             ORDER BY created_at DESC
             LIMIT 100",
            [$slug, $slug]
        );
    } else {
        echo json_encode(['success'=>false,'message'=>'slug obrigatório']);
        exit;
    }

    $files = array_map(function($r) {
        return [
            'doc_id'  => (int)$r['id'],
            'name'    => $r['title'] ?: $r['file_name'],
            'url'     => $base . '/download.php?id=' . $r['id'],
            'raw_url' => $base . '/download.php?id=' . $r['id'],
            'size'    => (int)$r['file_size'],
            'date'    => $r['publication_date'],
        ];
    }, $rows ?: []);

} catch (Exception $e) {
    // BD unavailable: fallback to filesystem
    $files = [];
}

// ── Filesystem fallback (PDFs uploaded directly, not in BD) ───────────────
if (empty($files)) {
    $baseDir = __DIR__ . '/../uploads/documents/';
    $baseUrl = '/uploads/documents/';

    if ($all && is_dir($baseDir)) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($baseDir, FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if ($file->isFile() && strtolower($file->getExtension()) === 'pdf') {
                $relPath = str_replace([$baseDir,'\\'], ['','/'], $file->getRealPath());
                $files[] = [
                    'doc_id'  => null,
                    'name'    => $file->getFilename(),
                    'url'     => $baseUrl . $relPath,
                    'raw_url' => $baseUrl . $relPath,
                    'size'    => $file->getSize(),
                    'date'    => null,
                ];
            }
        }
        usort($files, fn($a,$b) => strcmp($b['date']??'', $a['date']??''));

    } elseif ($slug) {
        $dir = $baseDir . $slug . '/';
        if (is_dir($dir)) {
            foreach (glob($dir . '*.pdf') as $path) {
                $files[] = [
                    'doc_id'  => null,
                    'name'    => basename($path),
                    'url'     => $baseUrl . $slug . '/' . basename($path),
                    'raw_url' => $baseUrl . $slug . '/' . basename($path),
                    'size'    => filesize($path),
                    'date'    => null,
                ];
            }
        }
    }
}

echo json_encode(['success' => true, 'files' => $files]);