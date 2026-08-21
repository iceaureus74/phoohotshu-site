<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';
admin_require_login();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}
if (!admin_verify_csrf($_POST['csrf'] ?? null)) {
    http_response_code(403);
    exit('Invalid CSRF token');
}

$id = filter_input(INPUT_POST, 'rfq_id', FILTER_VALIDATE_INT);
$status = trim((string)($_POST['status'] ?? ''));
$note = trim((string)($_POST['note'] ?? ''));

$allowed = ['new','reviewing','need_more_info','site_visit','quoted','follow_up','won','lost'];
if (!$id || !in_array($status, $allowed, true)) {
    http_response_code(422);
    exit('Invalid input');
}
$note = mb_substr($note, 0, 500);

$pdo = db();
$pdo->beginTransaction();
try {
    $st = $pdo->prepare('SELECT status FROM rfqs WHERE id = ? FOR UPDATE');
    $st->execute([$id]);
    $old = $st->fetchColumn();
    if ($old === false) {
        throw new RuntimeException('RFQ not found');
    }

    $up = $pdo->prepare('UPDATE rfqs SET status = ? WHERE id = ?');
    $up->execute([$status, $id]);

    $hist = $pdo->prepare(
        'INSERT INTO rfq_status_history (rfq_id, old_status, new_status, note, changed_by)
         VALUES (?, ?, ?, ?, ?)'
    );
    $hist->execute([$id, $old, $status, $note ?: null, $_SESSION['admin_user'] ?? 'admin']);

    $pdo->commit();
    header('Location: /admin/rfq.php?id=' . urlencode((string)$id));
    exit;
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    exit('Update failed');
}
