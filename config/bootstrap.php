<?php
declare(strict_types=1);

function app_config(): array {
    static $config;
    if ($config !== null) return $config;

    $path = __DIR__ . '/app.php';
    if (!is_file($path)) {
        throw new RuntimeException('Missing config/app.php');
    }
    $config = require $path;
    return $config;
}

function db(): PDO {
    static $pdo;
    if ($pdo instanceof PDO) return $pdo;

    $cfg = app_config()['db'];
    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $cfg['host'],
        $cfg['port'],
        $cfg['name'],
        $cfg['charset']
    );

    $pdo = new PDO($dsn, $cfg['user'], $cfg['pass'], [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    return $pdo;
}
