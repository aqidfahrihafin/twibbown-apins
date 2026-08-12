<?php
declare(strict_types=1);
require __DIR__ . '/config.php';
require __DIR__ . '/functions.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? 'create';
    if ($action === 'user_role') {
        $userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
        $role = ($_POST['role'] ?? '') === 'admin' ? 'admin' : 'user';
        if ($userId === (int) current_user()['id']) {
            flash('error', 'Role akun yang sedang digunakan tidak dapat diubah.');
        } else {
            $pdo->prepare('UPDATE users SET role = ? WHERE id = ?')->execute([$role, $userId]);
            flash('success', 'Hak akses pengguna berhasil diperbarui.');
        }
    } elseif ($action === 'delete_user') {
        $userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
        if ($userId === (int) current_user()['id']) {
            flash('error', 'Akun yang sedang digunakan tidak dapat dihapus.');
        } else {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare('SELECT template_image FROM twibbons WHERE owner_id = ?'); $stmt->execute([$userId]);
            $files = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $pdo->prepare('DELETE FROM twibbons WHERE owner_id = ?')->execute([$userId]);
            $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$userId]);
            $pdo->commit();
            foreach ($files as $file) { $path = __DIR__ . '/uploads/' . basename($file); if (is_file($path)) @unlink($path); }
            flash('success', 'Pengguna dan seluruh templatenya berhasil dihapus.');
        }
    } elseif ($action === 'delete') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $stmt = $pdo->prepare('SELECT template_image FROM twibbons WHERE id = ?'); $stmt->execute([$id]); $item = $stmt->fetch();
        if ($item) {
            $pdo->prepare('DELETE FROM twibbons WHERE id = ?')->execute([$id]);
            $path = __DIR__ . '/uploads/' . basename($item['template_image']);
            if ($item['template_image'] !== 'sample_template.png' && is_file($path)) @unlink($path);
            flash('success', 'Template berhasil dihapus.');
        }
    } else {
        $title = trim((string)($_POST['title'] ?? ''));
        $description = trim((string)($_POST['description'] ?? ''));
        try {
            if ($title === '' || mb_strlen($title) > 120) throw new RuntimeException('Judul wajib diisi dan maksimal 120 karakter.');
            if (mb_strlen($description) > 500) throw new RuntimeException('Deskripsi maksimal 500 karakter.');
            $fileName = store_image($_FILES['template_image'] ?? [], __DIR__ . '/uploads');
            $pdo->prepare("INSERT INTO twibbons (slug, owner_id, visibility, title, description, template_image) VALUES (?, ?, 'public', ?, ?, ?)")->execute([random_slug(), current_user()['id'], $title, $description, $fileName]);
            flash('success', 'Template baru berhasil dipublikasikan.');
        } catch (Throwable $e) { flash('error', $e->getMessage()); }
    }
    header('Location: ' . (str_starts_with($action, 'user') || $action === 'delete_user' ? 'users' : 'admin')); exit;
}
$flash = pull_flash();
$twibbons = $pdo->query('SELECT * FROM twibbons ORDER BY created_at DESC')->fetchAll();
$users = $pdo->query("SELECT u.*, COUNT(t.id) AS template_count FROM users u LEFT JOIN twibbons t ON t.owner_id = u.id GROUP BY u.id ORDER BY u.created_at DESC")->fetchAll();
?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Kelola Template — Twibbo</title><link rel="preconnect" href="https://fonts.googleapis.com"><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"><link rel="stylesheet" href="style.css"><link rel="stylesheet" href="teal.css"></head><body>
<header class="site-header"><div class="container nav"><a class="brand" href="./"><span class="brand-mark">B</span>Bingkaiin</a><nav class="nav-links"><a href="./">Beranda</a><a href="dashboard">Dashboard</a><a href="users">Pengguna</a><a class="active" href="admin">Template</a></nav></div></header>
<main class="container"><div class="page-head"><h1 class="page-title">Kelola template</h1><p class="subtitle">Tambahkan desain baru dan atur koleksi yang tampil di beranda.</p></div><?php if ($flash): ?><div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div><?php endif ?>
<div class="admin-layout"><section class="panel"><h2>Template baru</h2><p class="panel-note">Gunakan PNG transparan agar foto terlihat di balik bingkai.</p><form method="post" enctype="multipart/form-data"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="create"><div class="field"><label for="title">Judul template</label><input id="title" name="title" maxlength="120" required placeholder="Contoh: Hari Kemerdekaan"></div><div class="field"><label for="description">Deskripsi singkat</label><textarea id="description" name="description" rows="3" maxlength="500" placeholder="Ceritakan kegunaan template ini"></textarea></div><div class="field"><label>Gambar template</label><label class="dropzone" id="dropzone"><input type="file" id="template_image" name="template_image" accept="image/png,image/jpeg,image/webp" required><strong id="fileLabel">Pilih atau jatuhkan gambar</strong><small>PNG, JPG, atau WebP · Maksimal 8 MB</small><img class="image-preview" id="imagePreview" alt="Pratinjau gambar"></label></div><button class="btn btn-primary" type="submit">Publikasikan template</button></form></section>
<section class="panel"><h2>Koleksi saat ini</h2><p class="panel-note"><?= count($twibbons) ?> template tersedia untuk pengguna.</p><?php if (!$twibbons): ?><div class="empty">Belum ada template.</div><?php endif ?><?php foreach($twibbons as $item): ?><div class="admin-item"><img src="uploads/<?= e(basename($item['template_image'])) ?>" alt=""><div><h3><?= e($item['title']) ?></h3><p>Ditambahkan <?= date('d M Y', strtotime($item['created_at'])) ?></p></div><form method="post" onsubmit="return confirm('Hapus template ini? Tindakan ini tidak dapat dibatalkan.')"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$item['id'] ?>"><button class="btn btn-danger btn-sm" type="submit" aria-label="Hapus <?= e($item['title']) ?>">Hapus</button></form></div><?php endforeach ?></section></div></main>
<footer class="footer"><div class="container">© <?= date('Y') ?> Twibbo</div></footer><script>const input=document.querySelector('#template_image'),zone=document.querySelector('#dropzone'),label=document.querySelector('#fileLabel'),preview=document.querySelector('#imagePreview');function showFile(file){if(!file)return;label.textContent=file.name;preview.src=URL.createObjectURL(file);preview.style.display='block'}input.addEventListener('change',()=>showFile(input.files[0]));['dragenter','dragover'].forEach(e=>zone.addEventListener(e,x=>{x.preventDefault();zone.classList.add('dragging')}));['dragleave','drop'].forEach(e=>zone.addEventListener(e,x=>{x.preventDefault();zone.classList.remove('dragging')}));zone.addEventListener('drop',e=>{if(e.dataTransfer.files.length){input.files=e.dataTransfer.files;showFile(input.files[0])}});</script></body></html>
