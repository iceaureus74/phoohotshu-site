<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/email.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    json_response(['ok'=>false, 'error'=>'METHOD_NOT_ALLOWED'], 405);
}

// Honeypot: field must remain empty.
if (!empty($_POST['website'] ?? '')) {
    json_response(['ok'=>true]); // silent discard for bots
}

$pdo = db();
enforce_rate_limit($pdo);

$name = clean_string($_POST['customer_name'] ?? null, 120);
$contact = clean_string($_POST['contact_value'] ?? null, 190);
$consent = (int)($_POST['consent_privacy'] ?? 0) === 1;

if (!$name || !$contact) {
    json_response(['ok'=>false, 'error'=>'MISSING_REQUIRED_FIELDS'], 422);
}
if (!$consent) {
    json_response(['ok'=>false, 'error'=>'PRIVACY_CONSENT_REQUIRED'], 422);
}

$area = $_POST['area_ping'] ?? null;
$areaPing = null;
if ($area !== null && $area !== '') {
    if (!is_numeric($area) || (float)$area < 0 || (float)$area > 100000) {
        json_response(['ok'=>false, 'error'=>'INVALID_AREA'], 422);
    }
    $areaPing = round((float)$area, 2);
}

$saved = [];
$pdo->beginTransaction();

try {
    $rfqNo = make_rfq_no($pdo);

    $sql = "INSERT INTO rfqs (
        rfq_no, customer_name, contact_value, contact_type,
        city, district, address_text, area_ping, space_type, request_type, product_interest,
        existing_floor, floor_issue, furniture_condition, preferred_timing, customer_note,
        landing_page, source_page, referrer_url,
        utm_source, utm_medium, utm_campaign, utm_content, utm_term,
        consent_privacy, consent_case_publication,
        ip_hash, user_agent
    ) VALUES (
        :rfq_no, :customer_name, :contact_value, :contact_type,
        :city, :district, :address_text, :area_ping, :space_type, :request_type, :product_interest,
        :existing_floor, :floor_issue, :furniture_condition, :preferred_timing, :customer_note,
        :landing_page, :source_page, :referrer_url,
        :utm_source, :utm_medium, :utm_campaign, :utm_content, :utm_term,
        1, :consent_case_publication,
        :ip_hash, :user_agent
    )";

    $st = $pdo->prepare($sql);
    $st->execute([
        ':rfq_no' => $rfqNo,
        ':customer_name' => $name,
        ':contact_value' => $contact,
        ':contact_type' => detect_contact_type($contact),
        ':city' => clean_string($_POST['city'] ?? null, 80),
        ':district' => clean_string($_POST['district'] ?? null, 80),
        ':address_text' => clean_string($_POST['address_text'] ?? null, 255),
        ':area_ping' => $areaPing,
        ':space_type' => clean_string($_POST['space_type'] ?? null, 80),
        ':request_type' => clean_string($_POST['request_type'] ?? null, 120),
        ':product_interest' => clean_string($_POST['product_interest'] ?? null, 120),
        ':existing_floor' => clean_string($_POST['existing_floor'] ?? null, 120),
        ':floor_issue' => clean_string($_POST['floor_issue'] ?? null, 160),
        ':furniture_condition' => clean_string($_POST['furniture_condition'] ?? null, 120),
        ':preferred_timing' => clean_string($_POST['preferred_timing'] ?? null, 120),
        ':customer_note' => clean_string($_POST['customer_note'] ?? null, 5000),
        ':landing_page' => clean_string($_POST['landing_page'] ?? null, 255),
        ':source_page' => clean_string($_POST['source_page'] ?? null, 255),
        ':referrer_url' => clean_string($_POST['referrer_url'] ?? null, 500),
        ':utm_source' => clean_string($_POST['utm_source'] ?? null, 120),
        ':utm_medium' => clean_string($_POST['utm_medium'] ?? null, 120),
        ':utm_campaign' => clean_string($_POST['utm_campaign'] ?? null, 160),
        ':utm_content' => clean_string($_POST['utm_content'] ?? null, 160),
        ':utm_term' => clean_string($_POST['utm_term'] ?? null, 160),
        ':consent_case_publication' => (int)($_POST['consent_case_publication'] ?? 0) === 1 ? 1 : 0,
        ':ip_hash' => request_ip_hash(),
        ':user_agent' => clean_string($_SERVER['HTTP_USER_AGENT'] ?? null, 500),
    ]);

    $rfqId = (int)$pdo->lastInsertId();

    if (!empty($_FILES['photos'])) {
        $saved = validate_and_move_photos($_FILES['photos'], $rfqNo);

        if ($saved) {
            $photoStmt = $pdo->prepare(
                "INSERT INTO rfq_photos
                (rfq_id, stored_name, original_name, mime_type, file_size_bytes, sha256, width_px, height_px)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            );
            foreach ($saved as $photo) {
                $photoStmt->execute([
                    $rfqId,
                    $photo['stored_name'],
                    $photo['original_name'],
                    $photo['mime_type'],
                    $photo['file_size'],
                    $photo['sha256'],
                    $photo['width_px'],
                    $photo['height_px'],
                ]);
            }
        }
    }

    $hist = $pdo->prepare(
        "INSERT INTO rfq_status_history (rfq_id, old_status, new_status, note, changed_by)
         VALUES (?, NULL, 'new', 'RFQ created', 'system')"
    );
    $hist->execute([$rfqId]);

    $pdo->commit();

    // Email is notification only. A failed email never rolls back a saved RFQ.
    $emailResult = send_rfq_notification([
        'rfq_no' => $rfqNo,
        'customer_name' => $name,
        'contact_value' => $contact,
        'city' => clean_string($_POST['city'] ?? null, 80),
        'district' => clean_string($_POST['district'] ?? null, 80),
        'area_ping' => $areaPing,
        'request_type' => clean_string($_POST['request_type'] ?? null, 120),
        'existing_floor' => clean_string($_POST['existing_floor'] ?? null, 120),
        'floor_issue' => clean_string($_POST['floor_issue'] ?? null, 160),
        'source_page' => clean_string($_POST['source_page'] ?? null, 255),
    ]);

    json_response([
        'ok' => true,
        'rfq_no' => $rfqNo,
        'photo_count' => count($saved),
        'email_notified' => !empty($emailResult['ok']),
    ], 201);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    cleanup_saved_files($saved);

    error_log('[RFQ submit] ' . $e->getMessage());
    json_response(['ok'=>false, 'error'=>'SUBMIT_FAILED'], 500);
}
