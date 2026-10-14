<?php
/**
 * Authentication and role-based authorization.
 * The user row (role + status) is re-read from the database on EVERY request,
 * so a changed role/status takes effect immediately and nothing is trusted
 * from the browser.
 */
function current_user(): ?array
{
    static $loaded = false, $user = null;
    if ($loaded) { return $user; }
    $loaded = true;
    $uid = $_SESSION['uid'] ?? null;
    if (!$uid) { return null; }
    $st = db()->prepare('SELECT user_id, username, role, full_name, email, phone, status, created_at FROM users WHERE user_id = :id');
    $st->execute([':id' => (int) $uid]);
    $row = $st->fetch();
    if (!$row || $row['status'] === 'rejected') {
        logout_user();
        return null;
    }
    return $user = $row;
}

function login_user(int $userId): void
{
    session_regenerate_id(true);            // prevents session fixation
    $_SESSION['uid'] = $userId;
    unset($_SESSION['csrf'], $_SESSION['fails']);
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', ['expires' => time() - 3600, 'path' => $p['path'], 'secure' => $p['secure'], 'httponly' => true, 'samesite' => 'Lax']);
    }
    if (session_status() === PHP_SESSION_ACTIVE) { session_destroy(); }
}

/** Relative URL of the current request, validated for use as ?next= */
function current_request_next(): ?string
{
    $rel = ltrim(substr($_SERVER['SCRIPT_NAME'] ?? '', strlen(BASE_URL)), '/');
    if (!empty($_SERVER['QUERY_STRING'])) { $rel .= '?' . $_SERVER['QUERY_STRING']; }
    return safe_next($rel);
}

/** Pages for signed-in travellers (role = user). Admins are refused. */
function require_user(): array
{
    $u = current_user();
    if (!$u) {
        flash('info', 'Please log in to continue.');
        $next = current_request_next();
        redirect('user/login.php' . ($next ? '?next=' . urlencode($next) : ''));
    }
    if ($u['role'] !== 'user') {
        render_error_page(403, 'Traveller area', 'This area is for traveller accounts. Administrators manage the system from the admin panel.');
    }
    return $u;
}

/** Restricted features (booking) need an Approved account. */
function require_approved_user(): array
{
    $u = require_user();
    if ($u['status'] !== 'approved') {
        flash('error', $u['status'] === 'pending'
            ? 'Your account is still waiting for administrator approval. Booking opens once it is approved.'
            : 'Your account is currently suspended, so booking is unavailable. Please contact LakbayLokal.');
        redirect('user/dashboard.php');
    }
    return $u;
}

/** Admin panel: role must be admin, verified server-side on every request. */
function require_admin(): array
{
    $u = current_user();
    if (!$u) { redirect('admin/login.php'); }
    if ($u['role'] !== 'admin' || $u['status'] !== 'approved') {
        render_error_page(403, 'Access denied', 'You do not have permission to open this page.');
    }
    return $u;
}

/** Simple per-session login throttle (5 failures / 10 minutes). */
function login_throttled(string $bucket): bool
{
    $now = time();
    $recent = array_filter($_SESSION['fails'][$bucket] ?? [], fn($t) => $t > $now - 600);
    $_SESSION['fails'][$bucket] = $recent;
    return count($recent) >= 5;
}
function login_failed(string $bucket): void { $_SESSION['fails'][$bucket][] = time(); }

/**
 * Shared login handler. $role is 'user' or 'admin' - the account's stored role
 * must match the login route, otherwise the attempt is rejected with the same
 * generic message as a wrong password.
 */
function attempt_login(string $role, string $username, string $password, ?string &$error): ?array
{
    $bucket = 'login_' . $role;
    if (login_throttled($bucket)) {
        $error = 'Too many failed attempts. Please wait a few minutes and try again.';
        return null;
    }
    $st = db()->prepare('SELECT user_id, password, role, status FROM users WHERE username = :u LIMIT 1');
    $st->execute([':u' => $username]);
    $row = $st->fetch();
    // Always run a hash check so timing does not reveal whether the username exists.
    $hash = $row ? $row['password'] : password_hash('placeholder-password', PASSWORD_DEFAULT);
    $ok = password_verify($password, $hash);
    if (!$row || !$ok || $row['role'] !== $role) {
        login_failed($bucket);
        $error = 'Invalid username or password.';
        return null;
    }
    if ($row['status'] === 'rejected') {
        $error = 'This account is not active. Please contact LakbayLokal for help.';
        return null;
    }
    if (password_needs_rehash($row['password'], PASSWORD_DEFAULT)) {
        $up = db()->prepare('UPDATE users SET password = :p WHERE user_id = :id');
        $up->execute([':p' => password_hash($password, PASSWORD_DEFAULT), ':id' => $row['user_id']]);
    }
    login_user((int) $row['user_id']);
    return $row;
}
