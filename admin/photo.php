<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';
admin_require_login();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) { http_response_code(400); exit('Invalid photo id'); }

$pdo = db();
$st = $pdo->prepare(
    'SELECT p.*, r.rfq_no
     FROM rfq_photos p
     JOIN rfqs r ON r.id = p.rfq_id
     WHERE p.id = ?'
);
$st->execute([$id]);
$p = $st->fetch();
if (!$p) { http_response_code(404); exit('Photo not found'); }

$cfg = app_config()['uploads'];
$base = realpath($cfg['dir']);
if ($base === false) { http_response_code(500); exit('Storage unavailable'); }

$file = $base . DIRECTORY_SEPARATOR . $p['rfq_no'] . DIRECTORY_SEPARATOR . $p['stored_name'];
$real = realpath($file);

if ($real === false || !str_starts_with($real, $base . DIRECTORY_SEPARATOR) || !is_file($real)) {
    http_response_code(404);
    exit('File not found');
}

header('Content-Type: ' . $p['mime_type']);
header('Content-Length: ' . filesize($real));
header('Content-Disposition: inline; filename="private-photo-' . (int)$p['id'] . '"');
header('Cache-Control: private, no-store, max-age=0');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');
header('X-Robots-Tag: noindex, nofollow, noarchive');
readfile($real);
exit;
