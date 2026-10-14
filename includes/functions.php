<?php
/** Shared helpers: escaping, routing, flash messages, validation, formatting. */

function e($v): string { return htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function url(string $path = ''): string { return BASE_URL . '/' . ltrim($path, '/'); }
function asset(string $path): string { return url('assets/' . ltrim($path, '/')); }
function redirect(string $path): void { header('Location: ' . url($path)); exit; }
function is_post(): bool { return ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'; }
function post_str(string $k, string $d = ''): string { $v = $_POST[$k] ?? $d; return is_string($v) ? trim($v) : $d; }
function get_str(string $k, string $d = ''): string { $v = $_GET[$k] ?? $d; return is_string($v) ? trim($v) : $d; }
function get_int(string $k, int $d = 0): int { $v = $_GET[$k] ?? null; return (is_string($v) && ctype_digit($v)) ? (int) $v : $d; }
function post_int(string $k, int $d = 0): int { $v = $_POST[$k] ?? null; return (is_string($v) && ctype_digit($v)) ? (int) $v : $d; }

/* ---------- flash messages ---------- */
function flash(string $type, string $msg): void { $_SESSION['flash'][] = ['type' => $type, 'msg' => $msg]; }
function take_flashes(): array { $f = $_SESSION['flash'] ?? []; unset($_SESSION['flash']); return $f; }

/* ---------- domain lists (single source of truth) ---------- */
function categories(): array
{
    return [
        'nature'    => 'Nature & Mountains',
        'beach'     => 'Beaches & Islands',
        'heritage'  => 'Heritage & Culture',
        'adventure' => 'Adventure',
        'food'      => 'Local Food & Experiences',
    ];
}
function user_statuses(): array { return ['pending', 'approved', 'rejected', 'suspended']; }
function booking_statuses(): array { return ['pending', 'confirmed', 'cancelled', 'completed']; }
/** Allowed booking status transitions (admin). */
function booking_transitions(): array
{
    return [
        'pending'   => ['confirmed', 'cancelled'],
        'confirmed' => ['completed', 'cancelled'],
        'cancelled' => [],
        'completed' => [],
    ];
}
function badge(string $status): string
{
    return '<span class="badge badge--' . e($status) . '">' . e(ucfirst($status)) . '</span>';
}

/* ---------- formatting ---------- */
function money($n): string { return cfg('currency_symbol', '₱') . number_format((float) $n, 2); }
function fmt_date(?string $s, string $f = 'M j, Y'): string { return $s ? date($f, strtotime($s)) : '—'; }
function fmt_datetime(?string $s): string { return $s ? date('M j, Y g:i A', strtotime($s)) : '—'; }

/* ---------- validation ---------- */
function valid_email(string $s): bool { return strlen($s) <= 150 && filter_var($s, FILTER_VALIDATE_EMAIL) !== false; }
function valid_phone(string $s): bool { return (bool) preg_match('/^[0-9+()\-\s]{7,20}$/', $s); }
function valid_date(string $s): bool
{
    $d = DateTime::createFromFormat('!Y-m-d', $s);
    $err = DateTime::getLastErrors();
    return $d && $d->format('Y-m-d') === $s && (!$err || ($err['warning_count'] + $err['error_count']) === 0);
}
/** Returns an error message or null. */
function password_problem(string $pw): ?string
{
    if (strlen($pw) < 8) { return 'Password must be at least 8 characters.'; }
    if (strlen($pw) > 72) { return 'Password must be 72 characters or fewer.'; }
    if (!preg_match('/[A-Za-z]/', $pw) || !preg_match('/[0-9]/', $pw)) { return 'Password must include at least one letter and one number.'; }
    return null;
}
/** Only same-site relative targets are accepted for ?next= redirects. */
function safe_next(?string $n): ?string
{
    if (!$n) { return null; }
    return preg_match('#^(?:(?:user|admin)/)?[a-z0-9_\-]+\.php(?:\?[A-Za-z0-9=&_\-]*)?$#', $n) ? $n : null;
}

/* ---------- images ---------- */
const IMAGE_PATH_RE = '#^assets/images/destinations/(?:[A-Za-z0-9_-]+/)*[A-Za-z0-9._-]+\.(?:jpe?g|png|webp)$#i';
/** Public URL for a stored image path, or null when unset/missing (UI then shows a placeholder). */
function image_url(?string $path): ?string
{
    if (!$path || !preg_match(IMAGE_PATH_RE, $path)) { return null; }
    $full = ROOT_PATH . '/' . $path;
    return is_file($full) ? url($path) . '?v=' . filemtime($full) : null;
}
/** Deliberate placeholder block used when a destination has no image yet. */
function image_or_placeholder(?string $path, string $alt, int $hue = 200, string $class = ''): string
{
    $src = image_url($path);
    if ($src) {
        return '<img class="' . e($class) . '" src="' . e($src) . '" alt="' . e($alt) . '" loading="lazy">';
    }
    return '<span class="ph ' . e($class) . '" style="--hue:' . (int) $hue . '" role="img" aria-label="' . e($alt) . ' (image not added yet)"><span>Photo coming soon</span></span>';
}
function hue_for(int $id): int { return (($id * 47) + 190) % 360; }

/* ---------- forms ---------- */
function form_field(array $o): string
{
    $name = $o['name']; $type = $o['type'] ?? 'text'; $errors = $o['errors'] ?? [];
    $id = 'f_' . $name; $req = !empty($o['required']); $err = $errors[$name] ?? '';
    $hint = $o['hint'] ?? '';
    $attrs = '';
    foreach (($o['attrs'] ?? []) as $k => $v) { $attrs .= ' ' . e($k) . ($v === true ? '' : '="' . e($v) . '"'); }
    $desc = [];
    if ($hint) { $desc[] = $id . '_hint'; }
    if ($err)  { $desc[] = $id . '_err'; }
    $common = ' id="' . $id . '" name="' . e($name) . '"' . ($req ? ' required' : '')
        . ($err ? ' aria-invalid="true"' : '') . ($desc ? ' aria-describedby="' . implode(' ', $desc) . '"' : '') . $attrs;
    $h = '<div class="field' . ($err ? ' has-error' : '') . (!empty($o['class']) ? ' ' . e($o['class']) : '') . '">';
    $h .= '<label for="' . $id . '">' . e($o['label']) . ($req ? ' <span class="req" aria-hidden="true">*</span>' : '') . '</label>';
    $value = $o['value'] ?? '';
    if ($type === 'textarea') {
        $h .= '<textarea' . $common . '>' . e($value) . '</textarea>';
    } elseif ($type === 'select') {
        $h .= '<select' . $common . '>';
        foreach (($o['options'] ?? []) as $val => $text) {
            $h .= '<option value="' . e($val) . '"' . ((string) $val === (string) $value ? ' selected' : '') . '>' . e($text) . '</option>';
        }
        $h .= '</select>';
    } else {
        $h .= '<input type="' . e($type) . '"' . $common . ' value="' . e($value) . '">';
    }
    if ($hint) { $h .= '<p class="hint" id="' . $id . '_hint">' . e($hint) . '</p>'; }
    if ($err)  { $h .= '<p class="field-error" id="' . $id . '_err" role="alert">' . e($err) . '</p>'; }
    return $h . '</div>';
}
function error_summary(array $errors): string
{
    if (!$errors) { return ''; }
    return '<div class="alert alert--error" role="alert"><strong>Please fix the highlighted fields.</strong></div>';
}

/* ---------- pagination ---------- */
function paginate(int $total, int $perPage): array
{
    $pages = max(1, (int) ceil($total / $perPage));
    $page  = min(max(1, get_int('page', 1)), $pages);
    return [$page, $pages, ($page - 1) * $perPage];
}
function pagination_html(int $page, int $pages, array $params = []): string
{
    if ($pages < 2) { return ''; }
    unset($params['page']);
    $link = function (int $p, string $label, string $cls = '') use ($params, $page) {
        $q = http_build_query(array_merge($params, ['page' => $p]));
        return '<a class="pager__link ' . $cls . '" href="?' . e($q) . '"' . ($p === $page && $cls ? ' aria-current="page"' : '') . '>' . $label . '</a>';
    };
    $h = '<nav class="pager" aria-label="Pagination">';
    if ($page > 1) { $h .= $link($page - 1, 'Prev'); }
    for ($i = max(1, $page - 2); $i <= min($pages, $page + 2); $i++) { $h .= $link($i, (string) $i, $i === $page ? 'is-current' : ''); }
    if ($page < $pages) { $h .= $link($page + 1, 'Next'); }
    return $h . '</nav>';
}
/** Pull allow-listed string params from the query string (for filters / links). */
function keep_params(array $keys): array
{
    $out = [];
    foreach ($keys as $k) { $v = get_str($k); if ($v !== '') { $out[$k] = $v; } }
    return $out;
}
/** Escape LIKE wildcards in a user keyword. */
function like(string $s): string { return '%' . addcslashes($s, '%_\\') . '%'; }

/* ---------- misc ---------- */
function log_activity(?int $userId, string $action, string $details = ''): void
{
    $st = db()->prepare('INSERT INTO activity_logs (user_id, action, details) VALUES (:u, :a, :d)');
    $st->execute([':u' => $userId, ':a' => $action, ':d' => mb_substr($details, 0, 255)]);
}
function generate_reference(): string
{
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $st = db()->prepare('SELECT 1 FROM bookings WHERE reference = :r');
    for ($try = 0; $try < 6; $try++) {
        $s = '';
        for ($i = 0; $i < 6; $i++) { $s .= $chars[random_int(0, strlen($chars) - 1)]; }
        $ref = 'LL-' . date('ymd') . '-' . $s;
        $st->execute([':r' => $ref]);
        if (!$st->fetchColumn()) { return $ref; }
    }
    throw new RuntimeException('Could not generate a booking reference.');
}
function render_error_page(int $code, string $title, string $message): void
{
    http_response_code($code);
    $pageTitle = $title;
    include ROOT_PATH . '/includes/header.php';
    echo '<main class="wrap page-pad notice-page"><p class="eyebrow">Error ' . (int) $code . '</p><h1 class="display">' . e($title) . '</h1><p class="lead">' . e($message) . '</p>'
       . '<p><a class="btn btn--amber" href="' . e(url('index.php')) . '">Back to home</a></p></main>';
    include ROOT_PATH . '/includes/footer.php';
    exit;
}

/** First published destination image that actually exists (used as auth-screen art). */
function auth_backdrop(): ?string
{
    $rows = db()->query("SELECT image_path FROM items WHERE status = 'published' AND image_path IS NOT NULL ORDER BY item_id LIMIT 8")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($rows as $p) { $src = image_url($p); if ($src) { return $src; } }
    return null;
}
