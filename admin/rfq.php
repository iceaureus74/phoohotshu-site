<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';
admin_require_login();

$pdo = db();
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) { http_response_code(400); exit('Invalid RFQ id'); }

$st = $pdo->prepare('SELECT * FROM rfqs WHERE id = ?');
$st->execute([$id]);
$rfq = $st->fetch();
if (!$rfq) { http_response_code(404); exit('RFQ not found'); }

$photos = $pdo->prepare('SELECT * FROM rfq_photos WHERE rfq_id = ? ORDER BY id');
$photos->execute([$id]);
$photos = $photos->fetchAll();

$hist = $pdo->prepare('SELECT * FROM rfq_status_history WHERE rfq_id = ? ORDER BY created_at DESC, id DESC');
$hist->execute([$id]);
$hist = $hist->fetchAll();

$labels = [
'new'=>'New','reviewing'=>'Reviewing','need_more_info'=>'Need More Info','site_visit'=>'Site Visit',
'quoted'=>'Quoted','follow_up'=>'Follow-up','won'=>'Won','lost'=>'Lost'
];
$csrf = admin_csrf_token();
?><!doctype html>
<html lang="zh-Hant"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow">
<title><?=htmlspecialchars($rfq['rfq_no'])?>｜鋪好厝 Admin</title>
<style>
body{font-family:system-ui;background:#f4f6f4;color:#18221d;margin:0}.wrap{width:min(980px,94vw);margin:28px auto}
.panel{background:#fff;border:1px solid #dfe5e1;border-radius:16px;padding:18px;margin:14px 0}.grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}.kv{padding:10px;background:#f8faf8;border-radius:10px}.kv b{display:block;font-size:12px;color:#67736d;margin-bottom:4px}
select,textarea,button{font:inherit}textarea{width:100%;min-height:90px}button{padding:10px 14px;border:0;border-radius:10px;background:#173c31;color:#fff;font-weight:800}
.photo{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:10px;border:1px solid #e3e8e4;border-radius:10px;margin:8px 0}
a{color:#173c31;text-decoration:none}@media(max-width:700px){.grid{grid-template-columns:1fr}}
</style></head><body>
<div class="wrap">
<p><a href="/admin/">← 返回 Dashboard</a></p>
<h1><?=htmlspecialchars($rfq['rfq_no'])?></h1>

<div class="panel">
<form method="post" action="/admin/update-status.php">
<input type="hidden" name="csrf" value="<?=htmlspecialchars($csrf)?>">
<input type="hidden" name="rfq_id" value="<?=(int)$rfq['id']?>">
<label>狀態
<select name="status">
<?php foreach ($labels as $k=>$label): ?>
<option value="<?=htmlspecialchars($k)?>" <?=$rfq['status']===$k?'selected':''?>><?=htmlspecialchars($label)?></option>
<?php endforeach; ?>
</select></label>
<p><label>內部備註<br><textarea name="note" maxlength="500"></textarea></label></p>
<button type="submit">更新狀態</button>
</form>
</div>

<div class="panel grid">
<?php
$fields = [
'customer_name'=>'客戶','contact_value'=>'聯絡方式','city'=>'縣市','district'=>'行政區','address_text'=>'地址文字',
'area_ping'=>'坪數','space_type'=>'空間','request_type'=>'需求','product_interest'=>'產品興趣',
'existing_floor'=>'原地面','floor_issue'=>'地坪問題','furniture_condition'=>'家具','preferred_timing'=>'希望施工時間',
'customer_note'=>'客戶備註','landing_page'=>'Landing Page','source_page'=>'Source Page','referrer_url'=>'Referrer',
'utm_source'=>'UTM Source','utm_medium'=>'UTM Medium','utm_campaign'=>'UTM Campaign','created_at'=>'建立時間'
];
foreach ($fields as $key=>$label):
?>
<div class="kv"><b><?=htmlspecialchars($label)?></b><?=nl2br(htmlspecialchars((string)($rfq[$key] ?? '')))?></div>
<?php endforeach; ?>
</div>

<div class="panel"><h2>私人照片</h2>
<?php if (!$photos): ?><p>沒有照片。</p><?php endif; ?>
<?php foreach ($photos as $p): ?>
<div class="photo">
<div><b><?=htmlspecialchars($p['original_name'] ?: $p['stored_name'])?></b><br><small><?=htmlspecialchars($p['mime_type'])?>｜<?=number_format(((int)$p['file_size_bytes'])/1024,1)?> KB｜<?=htmlspecialchars((string)$p['width_px'])?>×<?=htmlspecialchars((string)$p['height_px'])?></small></div>
<a href="/admin/photo.php?id=<?=(int)$p['id']?>" target="_blank" rel="noopener">授權檢視</a>
</div>
<?php endforeach; ?>
</div>

<div class="panel"><h2>狀態歷程</h2>
<?php foreach ($hist as $h): ?>
<p><b><?=htmlspecialchars((string)$h['new_status'])?></b>｜<?=htmlspecialchars((string)$h['created_at'])?><br><small><?=htmlspecialchars((string)($h['note'] ?? ''))?></small></p>
<?php endforeach; ?>
</div>
</div>
</body></html>
