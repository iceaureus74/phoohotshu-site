<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/bootstrap.php';

function admin_start_session(): void {
    $cfg = app_config()['admin'] ?? [];
    $name = $cfg['session_name'] ?? 'PHOOHOTSHU_ADMIN';
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_name($name);
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
        session_start();
    }
}

function admin_is_logged_in(): bool {
    admin_start_session();
    if (empty($_SESSION['admin_logged_in'])) return false;

    $cfg = app_config()['admin'] ?? [];
    $ttl = (int)($cfg['session_lifetime_seconds'] ?? 28800);
    $last = (int)($_SESSION['admin_last_seen'] ?? 0);
    if ($last > 0 && time() - $last > $ttl) {
        admin_logout();
        return false;
    }

    $_SESSION['admin_last_seen'] = time();
    return true;
}

function admin_require_login(): void {
    if (!admin_is_logged_in()) {
        header('Location: /admin/login.php');
        exit;
    }
}

function admin_login(string $username, string $password): bool {
    admin_start_session();
    $cfg = app_config()['admin'] ?? [];

    $expectedUser = (string)($cfg['username'] ?? '');
    $hash = (string)($cfg['password_hash'] ?? '');

    if ($username === '' || $password === '' || $hash === '') return false;
    if (!hash_equals($expectedUser, $username)) return false;
    if (!password_verify($password, $hash)) return false;

    session_regenerate_id(true);
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_user'] = $username;
    $_SESSION['admin_last_seen'] = time();
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return true;
}

function admin_logout(): void {
    admin_start_session();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'] ?? '', $p['secure'], $p['httponly']);
    }
    session_destroy();
}

function admin_csrf_token(): string {
    admin_start_session();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function admin_verify_csrf(?string $token): bool {
    admin_start_session();
    return is_string($token)
        && !empty($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}
