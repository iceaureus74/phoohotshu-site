<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';
admin_require_login();

$pdo = db();

$status = trim((string)($_GET['status'] ?? ''));
$allowed = ['new','reviewing','need_more_info','site_visit','quoted','follow_up','won','lost'];
$params = [];
$where = '';
if ($status !== '' && in_array($status, $allowed, true)) {
    $where = 'WHERE status = ?';
    $params[] = $status;
}

$sql = "SELECT id, rfq_no, status, customer_name, contact_value, city, district, area_ping,
               request_type, product_interest, created_at
        FROM rfqs
        $where
        ORDER BY created_at DESC
        LIMIT 200";
$st = $pdo->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll();

$counts = [];
foreach ($allowed as $s) {
    $q = $pdo->prepare('SELECT COUNT(*) FROM rfqs WHERE status = ?');
    $q->execute([$s]);
    $counts[$s] = (int)$q->fetchColumn();
}

$labels = [
'new'=>'New','reviewing'=>'Reviewing','need_more_info'=>'Need More Info','site_visit'=>'Site Visit',
'quoted'=>'Quoted','follow_up'=>'Follow-up','won'=>'Won','lost'=>'Lost'
];
?><!doctype html>
<html lang="zh-Hant">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>RFQ Dashboard｜鋪好厝</title>
<style>
body{font-family:system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;background:#f4f6f4;color:#18221d;margin:0}
.wrap{width:min(1180px,94vw);margin:28px auto}.top{display:flex;justify-content:space-between;gap:18px;align-items:center}
a{color:#173c31;text-decoration:none}.logout{padding:9px 12px;border:1px solid #cad3cd;border-radius:10px;background:#fff}
.cards{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin:22px 0}.card{background:#fff;border:1px solid #dfe5e1;border-radius:14px;padding:14px}
.card b{font-size:22px;display:block}.filters{display:flex;gap:8px;flex-wrap:wrap;margin:18px 0}.filters a{background:#fff;border:1px solid #dfe5e1;padding:8px 10px;border-radius:999px}
table{width:100%;border-collapse:collapse;background:#fff;border:1px solid #dfe5e1;border-radius:16px;overflow:hidden}
th,td{padding:12px;border-bottom:1px solid #e8ece9;text-align:left;font-size:14px}th{background:#eef3ef}.pill{display:inline-block;padding:4px 8px;border-radius:999px;background:#eef3ef}
@media(max-width:800px){.cards{grid-template-columns:1fr 1fr}table{display:block;overflow:auto}.top{align-items:flex-start}}
</style>
</head><body>
<div class="wrap">
<div class="top"><div><h1>RFQ Dashboard</h1><p>最新 200 筆施工詢問</p></div><a class="logout" href="/admin/logout.php">登出</a></div>

<div class="cards">
<?php foreach ($counts as $k=>$c): ?>
<div class="card"><small><?=htmlspecialchars($labels[$k])?></small><b><?=$c?></b></div>
<?php endforeach; ?>
</div>

<div class="filters">
<a href="/admin/">全部</a>
<?php foreach ($labels as $k=>$label): ?>
<a href="/admin/?status=<?=urlencode($k)?>"><?=htmlspecialchars($label)?> (<?=$counts[$k]?>)</a>
<?php endforeach; ?>
</div>

<table>
<thead><tr><th>RFQ</th><th>狀態</th><th>客戶</th><th>地區</th><th>坪數</th><th>需求</th><th>時間</th></tr></thead>
<tbody>
<?php foreach ($rows as $r): ?>
<tr>
<td><a href="/admin/rfq.php?id=<?=(int)$r['id']?>"><?=htmlspecialchars($r['rfq_no'])?></a></td>
<td><span class="pill"><?=htmlspecialchars($labels[$r['status']] ?? $r['status'])?></span></td>
<td><?=htmlspecialchars($r['customer_name'])?><br><small><?=htmlspecialchars($r['contact_value'])?></small></td>
<td><?=htmlspecialchars(trim(($r['city']??'').' '.($r['district']??'')))?></td>
<td><?=htmlspecialchars((string)($r['area_ping'] ?? ''))?></td>
<td><?=htmlspecialchars((string)($r['request_type'] ?: $r['product_interest']))?></td>
<td><?=htmlspecialchars($r['created_at'])?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</body></html>
