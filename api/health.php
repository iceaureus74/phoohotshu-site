<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

try {
    $pdo = db();
    $pdo->query('SELECT 1');
    http_response_code(200);
    echo json_encode(['ok'=>true, 'db'=>'connected'], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(503);
    echo json_encode(['ok'=>false, 'db'=>'unavailable'], JSON_UNESCAPED_UNICODE);
}
