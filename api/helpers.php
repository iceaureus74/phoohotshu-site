<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/bootstrap.php';

function json_response(array $payload, int $status = 200): never {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function clean_string(mixed $value, int $max = 255): ?string {
    if ($value === null) return null;
    $value = trim((string)$value);
    if ($value === '') return null;
    return mb_substr($value, 0, $max);
}

function detect_contact_type(string $value): string {
    if (filter_var($value, FILTER_VALIDATE_EMAIL)) return 'email';
    if (preg_match('/^[0-9+\-\s()]{7,30}$/', $value)) return 'phone';
    return 'other';
}

function request_ip_hash(): string {
    $cfg = app_config();
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    return hash_hmac('sha256', $ip, $cfg['security']['app_secret']);
}

function make_rfq_no(PDO $pdo): string {
    $date = date('Ymd');
    // Random suffix avoids race conditions without relying on MAX(id).
    for ($i = 0; $i < 5; $i++) {
        $suffix = strtoupper(bin2hex(random_bytes(3)));
        $rfq = "PH-{$date}-{$suffix}";
        $st = $pdo->prepare('SELECT 1 FROM rfqs WHERE rfq_no = ? LIMIT 1');
        $st->execute([$rfq]);
        if (!$st->fetchColumn()) return $rfq;
    }
    throw new RuntimeException('Unable to generate RFQ number.');
}

function enforce_rate_limit(PDO $pdo): void {
    $cfg = app_config();
    $max = (int)$cfg['security']['max_requests_per_10_min'];
    $bucket = request_ip_hash();

    $pdo->beginTransaction();
    try {
        $st = $pdo->prepare('SELECT bucket_key, window_started_at, request_count FROM request_rate_limits WHERE bucket_key = ? FOR UPDATE');
        $st->execute([$bucket]);
        $row = $st->fetch();

        $now = new DateTimeImmutable('now');
        if (!$row) {
            $ins = $pdo->prepare('INSERT INTO request_rate_limits (bucket_key, window_started_at, request_count) VALUES (?, NOW(), 1)');
            $ins->execute([$bucket]);
            $pdo->commit();
            return;
        }

        $start = new DateTimeImmutable($row['window_started_at']);
        $elapsed = $now->getTimestamp() - $start->getTimestamp();

        if ($elapsed >= 600) {
            $up = $pdo->prepare('UPDATE request_rate_limits SET window_started_at = NOW(), request_count = 1 WHERE bucket_key = ?');
            $up->execute([$bucket]);
            $pdo->commit();
            return;
        }

        if ((int)$row['request_count'] >= $max) {
            $pdo->commit();
            json_response(['ok'=>false, 'error'=>'TOO_MANY_REQUESTS'], 429);
        }

        $up = $pdo->prepare('UPDATE request_rate_limits SET request_count = request_count + 1 WHERE bucket_key = ?');
        $up->execute([$bucket]);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}

function normalize_uploads(array $files): array {
    if (!isset($files['name'])) return [];

    $normalized = [];
    if (!is_array($files['name'])) {
        return [$files];
    }

    foreach ($files['name'] as $i => $name) {
        $normalized[] = [
            'name'     => $name,
            'type'     => $files['type'][$i] ?? '',
            'tmp_name' => $files['tmp_name'][$i] ?? '',
            'error'    => $files['error'][$i] ?? UPLOAD_ERR_NO_FILE,
            'size'     => $files['size'][$i] ?? 0,
        ];
    }
    return $normalized;
}

function validate_and_move_photos(array $files, string $rfqNo): array {
    $cfg = app_config()['uploads'];
    $items = normalize_uploads($files);
    if (count($items) > (int)$cfg['max_files']) {
        throw new RuntimeException('TOO_MANY_FILES');
    }

    $dir = rtrim($cfg['dir'], '/\\') . DIRECTORY_SEPARATOR . $rfqNo;
    if (!is_dir($dir) && !mkdir($dir, 0700, true) && !is_dir($dir)) {
        throw new RuntimeException('UPLOAD_DIR_CREATE_FAILED');
    }

    $saved = [];
    $finfo = new finfo(FILEINFO_MIME_TYPE);

    foreach ($items as $file) {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) continue;
        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('UPLOAD_ERROR');
        }

        $size = (int)$file['size'];
        if ($size <= 0 || $size > (int)$cfg['max_file_bytes']) {
            throw new RuntimeException('INVALID_FILE_SIZE');
        }

        $tmp = (string)$file['tmp_name'];
        if (!is_uploaded_file($tmp)) {
            throw new RuntimeException('INVALID_UPLOAD_SOURCE');
        }

        $mime = $finfo->file($tmp) ?: '';
        if (!isset($cfg['allowed_mime'][$mime])) {
            throw new RuntimeException('INVALID_FILE_TYPE');
        }

        $img = @getimagesize($tmp);
        if ($img === false) {
            throw new RuntimeException('INVALID_IMAGE');
        }

        $ext = $cfg['allowed_mime'][$mime];
        $stored = bin2hex(random_bytes(16)) . '.' . $ext;
        $dest = $dir . DIRECTORY_SEPARATOR . $stored;

        if (!move_uploaded_file($tmp, $dest)) {
            throw new RuntimeException('MOVE_UPLOAD_FAILED');
        }
        chmod($dest, 0600);

        $saved[] = [
            'path'          => $dest,
            'stored_name'   => $stored,
            'original_name' => mb_substr((string)$file['name'], 0, 255),
            'mime_type'     => $mime,
            'file_size'     => filesize($dest),
            'sha256'        => hash_file('sha256', $dest),
            'width_px'      => (int)$img[0],
            'height_px'     => (int)$img[1],
        ];
    }

    return $saved;
}

function cleanup_saved_files(array $saved): void {
    foreach ($saved as $f) {
        if (!empty($f['path']) && is_file($f['path'])) @unlink($f['path']);
    }
}
