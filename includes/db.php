<?php
/** PDO connection (prepared statements everywhere; exceptions on error). */
function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) { return $pdo; }
    $file = ROOT_PATH . '/config/database.php';
    if (!is_file($file)) { throw new RuntimeException('db-config'); }
    $c = require $file;
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $c['host'], (int) $c['port'], $c['name']);
    try {
        $pdo = new PDO($dsn, $c['user'], $c['pass'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        error_log('[LakbayLokal] DB connect failed: ' . $e->getMessage());
        throw new RuntimeException('db-connect');
    }
    return $pdo;
}
