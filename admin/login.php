<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';

admin_start_session();
if (admin_is_logged_in()) {
    header('Location: /admin/');
    exit;
}

$error = '';
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if (admin_login($username, $password)) {
        header('Location: /admin/');
        exit;
    }
    $error = '帳號或密碼錯誤。';
}
?><!doctype html>
<html lang="zh-Hant">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>鋪好厝 Admin Login</title>
<style>
body{font-family:system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;background:#f4f6f4;color:#17221d;margin:0;display:grid;place-items:center;min-height:100vh}
.card{width:min(92vw,420px);background:#fff;border:1px solid #dfe5e1;border-radius:18px;padding:28px;box-shadow:0 18px 55px #0001}
h1{margin:0 0 18px;font-size:28px}.field{display:grid;gap:6px;margin:14px 0}input{font:inherit;padding:12px 14px;border:1px solid #ccd5cf;border-radius:12px}
button{width:100%;padding:12px 14px;border:0;border-radius:12px;background:#173c31;color:#fff;font-weight:800;cursor:pointer}.err{background:#fff0ee;color:#9a3d32;padding:10px 12px;border-radius:10px}
small{color:#6e7a73}
</style>
</head><body>
<form class="card" method="post" autocomplete="off">
<h1>鋪好厝管理後台</h1>
<p><small>RFQ / 私人照片管理</small></p>
<?php if ($error): ?><div class="err"><?=htmlspecialchars($error,ENT_QUOTES,'UTF-8')?></div><?php endif; ?>
<div class="field"><label>帳號</label><input name="username" required autofocus></div>
<div class="field"><label>密碼</label><input name="password" type="password" required></div>
<button type="submit">登入</button>
</form>
</body></html>
