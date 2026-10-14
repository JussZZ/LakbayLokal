<?php

/**
 *   php cli/create-account.php admin <username> <password> "<Full name>" <email>
 *   php cli/create-account.php user  <username> <password> "<Full name>" <email> 
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
define('ROOT_PATH', dirname(__DIR__));
require ROOT_PATH . '/config/app.php';
require ROOT_PATH . '/includes/db.php';
function cfg(string $k, $d = null)
{
    return $d;
}

[$script, $role, $username, $password, $name, $email] = array_pad($argv, 6, null);
if (!in_array($role, ['admin', 'user'], true) || !$username || !$password || !$name || !$email) {
    fwrite(STDERR, "Usage: php cli/create-account.php <admin|user> <username> <password> \"<Full name>\" <email>\n");
    exit(1);
}
if (!preg_match('/^[A-Za-z0-9_]{4,30}$/', $username)) {
    fwrite(STDERR, "Username: 4-30 letters, numbers or underscore.\n");
    exit(1);
}
if (strlen($password) < 8 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
    fwrite(STDERR, "Password: at least 8 characters with a letter and a number.\n");
    exit(1);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Invalid email.\n");
    exit(1);
}
try {
    $st = db()->prepare("INSERT INTO users (username, password, role, full_name, email, status) VALUES (:u, :p, :r, :n, :e, 'approved')");
    $st->execute([':u' => $username, ':p' => password_hash($password, PASSWORD_DEFAULT), ':r' => $role, ':n' => $name, ':e' => $email]);
    echo "Created $role account '$username'.\n";
} catch (Throwable $e) {
    fwrite(STDERR, "Could not create the account: " . ($e instanceof PDOException && $e->getCode() === '23000' ? 'username or email already exists.' : $e->getMessage()) . "\n");
    exit(1);
}
