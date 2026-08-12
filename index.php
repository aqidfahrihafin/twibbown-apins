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
        . '<link rel="stylesheet" href="brand-v2.css?v=20260813-4">';
    $privateRoutes = ['login','register','dashboard','karya-baru','admin','users','privacy','profile','favorites','moderation','categories','notifications','social-action','metric'];
    if (in_array($route, $privateRoutes, true)) $sharedHead .= '<meta name="robots" content="noindex,nofollow">';
    if (str_starts_with($route, 't/') && (($GLOBALS['twibbon']['visibility'] ?? '') !== 'public')) $sharedHead .= '<meta name="robots" content="noindex,follow">';
    $html = str_replace(
        ['Bingkaiinn','Bingkaiin','Twibbo','Kelola Karya','Kelola karya','<span class="brand-mark">T</span>','<span class="brand-mark">B</span>','href="home.php"','href="login.php"','href="register.php"','href="dashboard.php"','href="admin.php"','href="logout.php"'],
        ['Semarakin','Semarakin','Semarakin','Semua Karya','Semua karya','<span class="brand-mark">S</span>','<span class="brand-mark">S</span>','href="./"','href="login"','href="register"','href="dashboard"','href="admin"','href="logout"'],
        $html
    );
    $html = preg_replace('#create\.php\?s=([a-zA-Z0-9-]+)#', 't/$1', $html) ?? $html;
    if (function_exists('current_user') && preg_match('#<header class="site-header">.*?</header>#s', $html)) {
        $user = current_user();
        $active = static fn(string $name): string => $route === $name ? ' class="active"' : '';
        $links = '<a' . ($route === '' ? ' class="active"' : '') . ' href="./">Beranda</a><a' . $active('explore') . ' href="explore">Jelajahi</a>';
        if ($user) {
            $workActive = in_array($route, ['dashboard','karya-baru'], true) ? ' class="active"' : '';
            $links .= '<a' . $workActive . ' href="dashboard">Karya Saya</a>';
            $links .= '<a' . $active('favorites') . ' href="favorites">Tersimpan</a>';
            if (($user['role'] ?? '') === 'admin') {
                $links .= '<span class="mobile-menu-section">Kelola platform</span>';
                $links .= '<a' . $active('admin') . ' href="admin">Semua Karya</a>';
                $links .= '<a' . $active('moderation') . ' href="moderation">Moderasi</a>';
                $links .= '<a' . $active('categories') . ' href="categories">Kategori</a>';
                $links .= '<a' . $active('users') . ' href="users">Pengguna</a>';
            } else {
                $links .= '<a' . $active('privacy') . ' href="privacy">Privasi</a>';
            }
            $initial = e(strtoupper(mb_substr((string)($user['name'] ?? 'A'), 0, 1)));
            $profileClass = $route === 'profile' ? 'nav-profile active' : 'nav-profile';
            $links .= '<span class="mobile-menu-section">Akun</span><a' . $active('notifications') . ' href="notifications" aria-label="Notifikasi">Notifikasi</a><a class="' . $profileClass . '" href="profile"><span class="nav-avatar">' . $initial . '</span><span>Profil</span></a><a class="nav-logout" href="logout">Keluar</a>';
        } else {
            $links .= '<span class="mobile-menu-section">Akun</span><a' . $active('login') . ' href="login">Masuk</a><a class="nav-register' . ($route === 'register' ? ' active' : '') . '" href="register">Daftar gratis</a>';
        }
        $header = '<header class="site-header"><div class="container nav"><a class="brand brand-lockup" href="./"><span class="brand-mark"><i></i>S</span><span class="brand-copy"><strong>Semarakin</strong><small>by Apins Digital</small></span></a><button class="menu-toggle" type="button" aria-expanded="false" aria-controls="mainNav" aria-label="Buka menu"><span></span><span></span><span></span></button><nav class="nav-links" id="mainNav" aria-label="Navigasi utama"><span class="mobile-menu-label">Jelajahi Semarakin</span>' . $links . '<small class="mobile-menu-brand">Kampanye kreatif, lebih mudah dibagikan.</small></nav></div></header>';
        $html = preg_replace('#<header class="site-header">.*?</header>#s', $header, $html, 1) ?? $html;
    }
    $html = preg_replace_callback('#<div class="alert alert-(success|error)"([^>]*)>(.*?)</div>#s', static function(array $m): string {
        $icon = $m[1] === 'success' ? '✓' : '!';
        $title = $m[1] === 'success' ? 'Berhasil' : 'Terjadi masalah';
        return '<div class="app-alert app-alert-' . $m[1] . '" role="alert"><span class="alert-icon">' . $icon . '</span><div><strong>' . $title . '</strong><p>' . $m[3] . '</p></div><button type="button" aria-label="Tutup notifikasi" onclick="this.parentElement.remove()">×</button></div>';
    }, $html) ?? $html;
    if ($route === 'login') {
        $google = '<div class="auth-divider"><span>atau</span></div><a class="google-login-btn" href="google/login"><svg viewBox="0 0 24 24" aria-hidden="true"><path fill="#4285F4" d="M21.6 12.2c0-.7-.1-1.4-.2-2H12v3.9h5.4a4.6 4.6 0 01-2 3v2.5h3.3c1.9-1.8 2.9-4.4 2.9-7.4z"/><path fill="#34A853" d="M12 22c2.7 0 5-.9 6.7-2.4l-3.3-2.5c-.9.6-2.1 1-3.4 1a5.9 5.9 0 01-5.5-4.1H3.1v2.6A10 10 0 0012 22z"/><path fill="#FBBC05" d="M6.5 14a6 6 0 010-3.9V7.4H3.1a10 10 0 000 9.2L6.5 14z"/><path fill="#EA4335" d="M12 6c1.5 0 2.8.5 3.9 1.5l2.9-2.8A9.7 9.7 0 0012 2a10 10 0 00-8.9 5.4l3.4 2.7A5.9 5.9 0 0112 6z"/></svg><span>Lanjutkan dengan Google</span></a>';
        $html = str_replace('<p class="auth-switch">', $google . '<p class="auth-switch">', $html);
    }
    if (str_starts_with($route, 't/') && function_exists('render_rich_text')) {
        $html = preg_replace_callback('#<p class="subtitle">(.*?)</p>#s', static function(array $m): string {
            return '<div class="subtitle rich-description">' . render_rich_text(html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8')) . '</div>';
        }, $html, 1) ?? $html;
    }
    $html = preg_replace('#\s+onsubmit="return confirm\(&#039;([^&]+)&#039;\)"#', ' data-confirm="$1"', $html) ?? $html;
    $html = preg_replace('#\s+onsubmit="return confirm\(\'([^\']+)\'\)"#', ' data-confirm="$1"', $html) ?? $html;
    $modal = '<div class="confirm-modal" id="confirmModal" hidden><div class="confirm-backdrop" data-modal-close></div><div class="confirm-dialog" role="dialog" aria-modal="true" aria-labelledby="confirmTitle"><span class="confirm-symbol">!</span><h2 id="confirmTitle">Konfirmasi tindakan</h2><p id="confirmMessage">Apakah Anda yakin ingin melanjutkan?</p><div class="confirm-actions"><button type="button" class="btn btn-secondary" data-modal-close>Batal</button><button type="button" class="btn btn-danger confirm-submit">Ya, lanjutkan</button></div></div></div>';
    $script = '<script>document.querySelectorAll(".app-alert").forEach(function(a){setTimeout(function(){a.classList.add("alert-leave");setTimeout(function(){a.remove()},260)},5000)});(function(){const toggle=document.querySelector(".menu-toggle"),nav=document.querySelector(".nav-links");if(toggle&&nav){toggle.addEventListener("click",function(){const open=nav.classList.toggle("menu-open");this.classList.toggle("is-open",open);this.setAttribute("aria-expanded",String(open));this.setAttribute("aria-label",open?"Tutup menu":"Buka menu")});nav.querySelectorAll("a").forEach(a=>a.addEventListener("click",()=>{nav.classList.remove("menu-open");toggle.classList.remove("is-open");toggle.setAttribute("aria-expanded","false")}))}})();(function(){const modal=document.getElementById("confirmModal"),message=document.getElementById("confirmMessage"),submit=modal&&modal.querySelector(".confirm-submit");let target=null,lastFocus=null;function close(){if(!modal)return;modal.classList.remove("is-open");setTimeout(()=>modal.hidden=true,180);if(lastFocus)lastFocus.focus()}document.querySelectorAll("form[data-confirm]").forEach(form=>form.addEventListener("submit",function(e){if(this.dataset.confirmed)return;e.preventDefault();target=this;lastFocus=document.activeElement;message.textContent=this.dataset.confirm||"Apakah Anda yakin ingin melanjutkan?";modal.hidden=false;requestAnimationFrame(()=>{modal.classList.add("is-open");submit.focus()})}));modal&&modal.querySelectorAll("[data-modal-close]").forEach(x=>x.addEventListener("click",close));submit&&submit.addEventListener("click",function(){if(target){target.dataset.confirmed="1";target.requestSubmit()}});document.addEventListener("keydown",e=>{if(e.key==="Escape"&&modal&&!modal.hidden)close()})})();</script>';
    $metricScript = '<script>(function(){function send(id,type){if(!id)return;fetch("metric",{method:"POST",headers:{"Content-Type":"application/x-www-form-urlencoded"},body:new URLSearchParams({id:id,type:type}),keepalive:true}).catch(()=>{})}document.querySelectorAll("[data-copy-url][data-template-id]").forEach(x=>x.addEventListener("click",()=>send(x.dataset.templateId,"share")));const photo=document.querySelector("#photoInput"),download=document.querySelector("#download");if(photo)photo.addEventListener("change",()=>send(document.body.dataset.templateId,"use"),{once:true});if(download)download.addEventListener("click",()=>send(document.body.dataset.templateId,"download"));})();</script>';
    $editorScript = '<script>(function(){const textarea=document.querySelector("#description");if(!textarea||document.querySelector(".simple-editor"))return;const shell=document.createElement("div");shell.className="simple-editor";shell.innerHTML=`<div class="editor-toolbar" role="toolbar" aria-label="Format deskripsi"><button type="button" data-command="bold" title="Tebal"><b>B</b></button><button type="button" data-command="italic" title="Miring"><i>I</i></button><button type="button" data-command="insertUnorderedList" title="Daftar poin">• Daftar</button><button type="button" data-command="insertOrderedList" title="Daftar nomor">1. Daftar</button><button type="button" data-command="removeFormat" title="Hapus format">Hapus format</button></div><div class="editor-content" contenteditable="true" role="textbox" aria-multiline="true" data-placeholder="Ceritakan tujuan kampanye ini"></div><div class="editor-status"><span>Editor sederhana</span><span class="editor-count">0/500</span></div>`;textarea.hidden=true;textarea.before(shell);const area=shell.querySelector(".editor-content"),count=shell.querySelector(".editor-count");area.innerHTML=textarea.value;function sync(){textarea.value=area.innerHTML;const n=(area.textContent||"").trim().length;count.textContent=n+"/500";count.classList.toggle("limit",n>500)}shell.querySelectorAll("[data-command]").forEach(btn=>btn.addEventListener("mousedown",e=>{e.preventDefault();area.focus();document.execCommand(btn.dataset.command,false,null);sync()}));area.addEventListener("input",sync);textarea.form&&textarea.form.addEventListener("submit",sync);sync()})();</script>';
    if ($route !== 'karya-baru') $editorScript = '';
    $html = str_replace('</body>', $modal . $script . $metricScript . $editorScript . '</body>', $html);
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
if (preg_match('#^creator/([a-z0-9-]+)$#', $route, $match)) { $_GET['username']=$match[1];require __DIR__.'/creator.php';exit; }
if (preg_match('#^article/([a-z0-9-]+)$#', $route, $match)) { $_GET['slug']=$match[1];require __DIR__.'/article.php';exit; }

$routes = [
    '' => 'home.php',
    'login' => 'login.php',
    'google/login' => 'google-login.php',
    'google/callback' => 'google-callback.php',
    'register' => 'register.php',
    'dashboard' => 'dashboard.php',
    'karya-baru' => 'karya-baru.php',
    'admin' => 'templates.php',
    'users' => 'users.php',
    'privacy' => 'privacy.php',
    'profile' => 'profile.php',
    'logout' => 'logout.php',
    'sitemap.xml' => 'sitemap.php',
    'explore' => 'explore.php',
    'social-action' => 'social-action.php',
    'favorites' => 'favorites.php',
    'moderation' => 'moderation.php',
    'categories' => 'categories.php',
    'notifications' => 'notifications.php',
    'metric' => 'metric.php',
];

if (isset($routes[$route])) {
    require __DIR__ . '/' . $routes[$route];
    exit;
}

http_response_code(404);
require __DIR__ . '/functions.php';
$title = 'Halaman tidak ditemukan';
require __DIR__ . '/not-found.php';
