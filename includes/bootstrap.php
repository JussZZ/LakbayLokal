<?php
/**
 * Loaded first by every page: config, session, helpers, error handling.
 */
if (defined('LL_BOOT')) { return; }
define('LL_BOOT', true);
define('ROOT_PATH', dirname(__DIR__));

$GLOBALS['LL_CONFIG'] = require ROOT_PATH . '/config/app.php';
function cfg(string $key, $default = null) { return $GLOBALS['LL_CONFIG'][$key] ?? $default; }

ini_set('display_errors', cfg('debug') ? '1' : '0');
error_reporting(E_ALL);
date_default_timezone_set((string) cfg('timezone', 'Asia/Manila'));

// Base URL: configured value or auto-detected from the document root.
$__base = (string) cfg('base_url', '');
if ($__base === '') {
    $docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : false;
    $root    = realpath(ROOT_PATH);
    if ($docRoot && $root) {
        $docRoot = rtrim(str_replace('\\', '/', $docRoot), '/');
        $root    = str_replace('\\', '/', $root);
        if (strpos($root, $docRoot) === 0) { $__base = substr($root, strlen($docRoot)); }
    }
}
define('BASE_URL', rtrim($__base, '/'));

require_once ROOT_PATH . '/includes/functions.php';
require_once ROOT_PATH . '/includes/db.php';
require_once ROOT_PATH . '/includes/csrf.php';
require_once ROOT_PATH . '/includes/auth.php';

set_exception_handler(function (Throwable $e) {
    error_log('[LakbayLokal] ' . $e);
    $code = $e->getMessage();
    if ($code === 'db-config') {
        $msg = 'The database is not configured yet. Copy config/database.example.php to config/database.php and enter your local credentials.';
    } elseif ($code === 'db-connect') {
        $msg = 'Could not connect to the database. Check that MySQL is running, that lakbaylokal.sql was imported, and that config/database.php is correct.';
    } else {
        $msg = cfg('debug') ? $e->getMessage() : 'Something went wrong on our side. Please try again in a moment.';
    }
    if (!headers_sent()) { http_response_code(500); }
    echo '<!doctype html><meta charset="utf-8"><title>Server error</title>'
       . '<body style="font-family:system-ui,sans-serif;background:#0a0e11;color:#eef1f3;display:grid;place-items:center;min-height:100vh;margin:0">'
       . '<div style="max-width:34rem;padding:2rem"><h1 style="color:#f2a81d">Something went wrong</h1><p>'
       . htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') . '</p></div></body>';
    exit;
});

// Sessions: HttpOnly, SameSite, strict mode, Secure when served over HTTPS.
$__https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
session_name((string) cfg('session_name', 'LLSESSID'));
session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => $__https, 'httponly' => true, 'samesite' => 'Lax']);
session_start();

header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
