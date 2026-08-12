<?php
declare(strict_types=1);

// Shared hosting tidak selalu menyediakan ekstensi mbstring.
if (!function_exists('mb_strlen')) {
    function mb_strlen(string $value, ?string $encoding = null): int
    {
        return preg_match_all('/./us', $value, $matches) ?: 0;
    }
}
if (!function_exists('mb_substr')) {
    function mb_substr(string $value, int $start, ?int $length = null, ?string $encoding = null): string
    {
        preg_match_all('/./us', $value, $matches);
        return implode('', array_slice($matches[0] ?? [], $start, $length));
    }
}

function route_url(string $route = ''): string
{
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
    $base = rtrim(dirname($script), '/.');
    return ($base === '' ? '' : $base) . '/' . ltrim($route, '/');
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_token(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    return $_SESSION['csrf_token'] ??= bin2hex(random_bytes(32));
}

function verify_csrf(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(419);
        exit('Sesi formulir berakhir. Muat ulang halaman dan coba lagi.');
    }
}

function store_image(array $file, string $directory): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) throw new RuntimeException('Pilih gambar yang ingin diunggah.');
    if (($file['size'] ?? 0) > 8 * 1024 * 1024) throw new RuntimeException('Ukuran gambar maksimal 8 MB.');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($extensions[$mime]) || @getimagesize($file['tmp_name']) === false) throw new RuntimeException('Gunakan gambar JPG, PNG, atau WebP yang valid.');
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) throw new RuntimeException('Folder upload tidak dapat dibuat.');
    $name = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
    if (!move_uploaded_file($file['tmp_name'], $directory . '/' . $name)) throw new RuntimeException('Gambar gagal disimpan. Coba kembali.');
    return $name;
}

function flash(string $type, string $message): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    $_SESSION['flash'] = compact('type', 'message');
}

function pull_flash(): ?array
{
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

// Alias konsisten untuk halaman baru dan halaman lama.
function set_flash(string $type, string $message): void
{
    flash($type, $message);
}

function get_flash(): ?array
{
    return pull_flash();
}

function redirect(string $route): never
{
    header('Location: ' . route_url($route));
    exit;
}

function slugify(string $value): string
{
    $value = trim($value);
    if (function_exists('iconv')) {
        $converted = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        if ($converted !== false) $value = $converted;
    }
    $value = strtolower((string) preg_replace('/[^a-zA-Z0-9]+/', '-', $value));
    return trim($value, '-') ?: 'item-' . bin2hex(random_bytes(3));
}

function sanitize_rich_text(string $value): string
{
    $value = strip_tags(trim($value), '<p><br><strong><b><em><i><ul><ol><li>');
    // Toolbar tidak membutuhkan atribut HTML; buang atribut hasil tempelan dari aplikasi lain.
    $value = preg_replace('/<(p|br|strong|b|em|i|ul|ol|li)\b[^>]*>/i', '<$1>', $value) ?? $value;
    return trim($value);
}

function render_rich_text(string $value): string
{
    $safe = sanitize_rich_text($value);
    return preg_match('/<\/?(?:p|br|strong|b|em|i|ul|ol|li)\b/i', $safe) ? $safe : nl2br(e($safe));
}

function current_user(): ?array
{
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    return isset($_SESSION['user']) && is_array($_SESSION['user']) ? $_SESSION['user'] : null;
}

function logged_in(): bool { return current_user() !== null; }
function is_admin(): bool { return (current_user()['role'] ?? '') === 'admin'; }

function require_login(): void
{
    if (!logged_in()) { flash('error', 'Silakan masuk untuk melanjutkan.'); header('Location: ' . route_url('login')); exit; }
}

function require_admin(): void
{
    require_login();
    if (!is_admin()) { http_response_code(403); exit('Akses hanya tersedia untuk administrator.'); }
}

function random_slug(): string { return bin2hex(random_bytes(8)); }

function compact_number(int $number): string
{
    if ($number >= 1000000) return rtrim(rtrim(number_format($number / 1000000, 1), '0'), '.') . ' jt';
    if ($number >= 1000) return rtrim(rtrim(number_format($number / 1000, 1), '0'), '.') . ' rb';
    return (string)$number;
}

function unique_username(PDO $pdo, string $name): string
{
    $base = strtolower(trim((string)preg_replace('/[^a-zA-Z0-9]+/', '-', $name), '-')) ?: 'creator';
    $candidate=$base;$i=1;$stmt=$pdo->prepare('SELECT COUNT(*) FROM users WHERE username=?');
    do{$stmt->execute([$candidate]);if(!(int)$stmt->fetchColumn())return $candidate;$candidate=$base.'-'.$i++;}while($i<1000);
    return $base.'-'.bin2hex(random_bytes(3));
}

function template_metrics_sql(): string
{
    return "(SELECT COUNT(*) FROM template_likes l WHERE l.template_id=t.id) like_count,
      (SELECT COUNT(*) FROM template_favorites f WHERE f.template_id=t.id) favorite_count,
      (SELECT COALESCE(AVG(r.rating),0) FROM template_ratings r WHERE r.template_id=t.id) rating_avg,
      (SELECT COUNT(*) FROM template_ratings r WHERE r.template_id=t.id) rating_count";
}
