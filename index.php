<?php
declare(strict_types=1);

$route = trim((string) ($_GET['route'] ?? ''), '/');
$appBasePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php')), '/');
if ($appBasePath === '.' || $appBasePath === '/') $appBasePath = '';

// Shared document head for every routed page: stable asset base + Tailwind.
ob_start(static function (string $html) use ($route, $appBasePath): string {
    $sharedHead = '<base href="' . htmlspecialchars($appBasePath . '/', ENT_QUOTES, 'UTF-8') . '">'
        . '<script src="https://cdn.tailwindcss.com"></script>'
        . '<script src="tailwind.config.js"></script>'
        . '<link rel="stylesheet" href="brand-v2.css">';
    $privateRoutes = ['login','register','dashboard','admin','users','privacy','profile'];
    if (in_array($route, $privateRoutes, true)) $sharedHead .= '<meta name="robots" content="noindex,nofollow">';
    if (str_starts_with($route, 't/') && (($GLOBALS['twibbon']['visibility'] ?? '') !== 'public')) $sharedHead .= '<meta name="robots" content="noindex,follow">';
    $html = str_replace(
        ['Twibbo', '<span class="brand-mark">T</span>', 'href="home.php"', 'href="login.php"', 'href="register.php"', 'href="dashboard.php"', 'href="admin.php"', 'href="logout.php"'],
        ['Bingkaiin', '<span class="brand-mark">B</span>', 'href="./"', 'href="login"', 'href="register"', 'href="dashboard"', 'href="admin"', 'href="logout"'],
        $html
    );
    $html = preg_replace('#create\.php\?s=([a-zA-Z0-9-]+)#', 't/$1', $html) ?? $html;
    if (function_exists('current_user') && preg_match('#<header class="site-header">.*?</header>#s', $html)) {
        $user = current_user();
        $active = static fn(string $name): string => $route === $name ? ' class="active"' : '';
        $links = '<a' . ($route === '' ? ' class="active"' : '') . ' href="./">Beranda</a>';
        if ($user) {
            $links .= '<a' . $active('dashboard') . ' href="dashboard">Dashboard</a>';
            if (($user['role'] ?? '') === 'admin') {
                $links .= '<a' . $active('admin') . ' href="admin">Template Publik</a>';
                $links .= '<a' . $active('users') . ' href="users">Pengguna</a>';
            } else {
                $links .= '<a' . $active('privacy') . ' href="privacy">Privasi</a>';
            }
            $initial = e(strtoupper(mb_substr((string)($user['name'] ?? 'A'), 0, 1)));
            $profileClass = $route === 'profile' ? 'nav-profile active' : 'nav-profile';
            $links .= '<a class="' . $profileClass . '" href="profile"><span class="nav-avatar">' . $initial . '</span><span>Profil</span></a><a class="nav-logout" href="logout">Keluar</a>';
        } else {
            $links .= '<a href="./#templates">Template</a><a' . $active('login') . ' href="login">Masuk</a><a' . $active('register') . ' href="register">Daftar</a>';
        }
        $header = '<header class="site-header"><div class="container nav"><a class="brand brand-lockup" href="./"><span class="brand-mark"><i></i>B</span><span class="brand-copy"><strong>Bingkaiin</strong><small>by Apins Digital</small></span></a><button class="menu-toggle" type="button" aria-expanded="false" aria-controls="mainNav" aria-label="Buka menu"><span></span><span></span><span></span></button><nav class="nav-links" id="mainNav" aria-label="Navigasi utama"><span class="mobile-menu-label">Jelajahi Bingkaiin</span>' . $links . '<small class="mobile-menu-brand">Kampanye kreatif, lebih mudah dibagikan.</small></nav></div></header>';
        $html = preg_replace('#<header class="site-header">.*?</header>#s', $header, $html, 1) ?? $html;
    }
    $html = preg_replace_callback('#<div class="alert alert-(success|error)"([^>]*)>(.*?)</div>#s', static function(array $m): string {
        $icon = $m[1] === 'success' ? '✓' : '!';
        $title = $m[1] === 'success' ? 'Berhasil' : 'Terjadi masalah';
        return '<div class="app-alert app-alert-' . $m[1] . '" role="alert"><span class="alert-icon">' . $icon . '</span><div><strong>' . $title . '</strong><p>' . $m[3] . '</p></div><button type="button" aria-label="Tutup notifikasi" onclick="this.parentElement.remove()">×</button></div>';
    }, $html) ?? $html;
    $html = preg_replace('#\s+onsubmit="return confirm\(&#039;([^&]+)&#039;\)"#', ' data-confirm="$1"', $html) ?? $html;
    $html = preg_replace('#\s+onsubmit="return confirm\(\'([^\']+)\'\)"#', ' data-confirm="$1"', $html) ?? $html;
    $modal = '<div class="confirm-modal" id="confirmModal" hidden><div class="confirm-backdrop" data-modal-close></div><div class="confirm-dialog" role="dialog" aria-modal="true" aria-labelledby="confirmTitle"><span class="confirm-symbol">!</span><h2 id="confirmTitle">Konfirmasi tindakan</h2><p id="confirmMessage">Apakah Anda yakin ingin melanjutkan?</p><div class="confirm-actions"><button type="button" class="btn btn-secondary" data-modal-close>Batal</button><button type="button" class="btn btn-danger confirm-submit">Ya, lanjutkan</button></div></div></div>';
    $script = '<script>document.querySelectorAll(".app-alert").forEach(function(a){setTimeout(function(){a.classList.add("alert-leave");setTimeout(function(){a.remove()},260)},5000)});(function(){const toggle=document.querySelector(".menu-toggle"),nav=document.querySelector(".nav-links");if(toggle&&nav){toggle.addEventListener("click",function(){const open=nav.classList.toggle("menu-open");this.classList.toggle("is-open",open);this.setAttribute("aria-expanded",String(open));this.setAttribute("aria-label",open?"Tutup menu":"Buka menu")});nav.querySelectorAll("a").forEach(a=>a.addEventListener("click",()=>{nav.classList.remove("menu-open");toggle.classList.remove("is-open");toggle.setAttribute("aria-expanded","false")}))}})();(function(){const modal=document.getElementById("confirmModal"),message=document.getElementById("confirmMessage"),submit=modal&&modal.querySelector(".confirm-submit");let target=null,lastFocus=null;function close(){if(!modal)return;modal.classList.remove("is-open");setTimeout(()=>modal.hidden=true,180);if(lastFocus)lastFocus.focus()}document.querySelectorAll("form[data-confirm]").forEach(form=>form.addEventListener("submit",function(e){if(this.dataset.confirmed)return;e.preventDefault();target=this;lastFocus=document.activeElement;message.textContent=this.dataset.confirm||"Apakah Anda yakin ingin melanjutkan?";modal.hidden=false;requestAnimationFrame(()=>{modal.classList.add("is-open");submit.focus()})}));modal&&modal.querySelectorAll("[data-modal-close]").forEach(x=>x.addEventListener("click",close));submit&&submit.addEventListener("click",function(){if(target){target.dataset.confirmed="1";target.requestSubmit()}});document.addEventListener("keydown",e=>{if(e.key==="Escape"&&modal&&!modal.hidden)close()})})();</script>';
    $html = str_replace('</body>', $modal . $script . '</body>', $html);
    return str_replace('<head>', '<head>' . $sharedHead, $html);
});

if (preg_match('#^t/([a-z0-9][a-z0-9-]{2,63})$#', $route, $match)) {
    $_GET['s'] = $match[1];
    require __DIR__ . '/create.php';
    exit;
}

if (preg_match('#^admin/template/([0-9]+)/edit$#', $route, $match)) {
    $_GET['id'] = $match[1];
    require __DIR__ . '/template-edit.php';
    exit;
}

$routes = [
    '' => 'home.php',
    'login' => 'login.php',
    'register' => 'register.php',
    'dashboard' => 'dashboard.php',
    'admin' => 'templates.php',
    'users' => 'users.php',
    'privacy' => 'privacy.php',
    'profile' => 'profile.php',
    'logout' => 'logout.php',
    'sitemap.xml' => 'sitemap.php',
];

if (isset($routes[$route])) {
    require __DIR__ . '/' . $routes[$route];
    exit;
}

http_response_code(404);
require __DIR__ . '/functions.php';
$title = 'Halaman tidak ditemukan';
require __DIR__ . '/not-found.php';
