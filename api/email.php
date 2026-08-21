<?php
declare(strict_types=1);

function send_rfq_notification(array $rfq): array {
    $cfg = app_config()['email'] ?? [];
    if (empty($cfg['enabled'])) {
        return ['ok'=>false, 'skipped'=>true, 'reason'=>'disabled'];
    }

    if (($cfg['provider'] ?? '') !== 'resend') {
        return ['ok'=>false, 'skipped'=>true, 'reason'=>'unsupported_provider'];
    }

    $apiKey = trim((string)($cfg['resend_api_key'] ?? ''));
    $from = trim((string)($cfg['from'] ?? ''));
    $to = trim((string)($cfg['to'] ?? ''));

    if ($apiKey === '' || $from === '' || $to === '') {
        return ['ok'=>false, 'skipped'=>true, 'reason'=>'missing_config'];
    }

    $subject = '[鋪好厝 RFQ] ' . ($rfq['rfq_no'] ?? 'New RFQ');
    $safe = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');

    $html = '<h2>新的塑膠地磚／施工詢問</h2>'
        . '<p><b>RFQ：</b>' . $safe($rfq['rfq_no'] ?? '') . '</p>'
        . '<p><b>客戶：</b>' . $safe($rfq['customer_name'] ?? '') . '</p>'
        . '<p><b>聯絡：</b>' . $safe($rfq['contact_value'] ?? '') . '</p>'
        . '<p><b>地區：</b>' . $safe(trim(($rfq['city'] ?? '') . ' ' . ($rfq['district'] ?? ''))) . '</p>'
        . '<p><b>坪數：</b>' . $safe($rfq['area_ping'] ?? '') . '</p>'
        . '<p><b>需求：</b>' . $safe($rfq['request_type'] ?? '') . '</p>'
        . '<p><b>原地面：</b>' . $safe($rfq['existing_floor'] ?? '') . '</p>'
        . '<p><b>問題：</b>' . $safe($rfq['floor_issue'] ?? '') . '</p>'
        . '<p><b>來源頁：</b>' . $safe($rfq['source_page'] ?? '') . '</p>'
        . '<p><a href="https://phoohotshu.tw/admin/">登入後台查看完整資料與私人照片</a></p>';

    $payload = json_encode([
        'from' => $from,
        'to' => [$to],
        'subject' => $subject,
        'html' => $html,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    $ch = curl_init('https://api.resend.com/emails');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json',
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 10,
    ]);

    $body = curl_exec($ch);
    $errno = curl_errno($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    if ($errno !== 0 || $status < 200 || $status >= 300) {
        error_log('[RFQ email] delivery failed: HTTP ' . $status . ' body=' . (string)$body);
        return ['ok'=>false, 'status'=>$status];
    }

    return ['ok'=>true, 'status'=>$status];
}
