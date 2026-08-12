<?php
declare(strict_types=1);
require __DIR__ . '/config.php'; require __DIR__ . '/functions.php';
if (logged_in()) { header('Location: dashboard'); exit; }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf(); $name=trim((string)($_POST['name']??'')); $email=strtolower(trim((string)($_POST['email']??''))); $password=(string)($_POST['password']??'');
    try {
        if (mb_strlen($name)<2 || mb_strlen($name)>100) throw new RuntimeException('Nama harus terdiri dari 2–100 karakter.');
        if (!filter_var($email,FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Alamat email tidak valid.');
        if (strlen($password)<8) throw new RuntimeException('Password minimal 8 karakter.');
        if ($password !== ($_POST['password_confirmation']??'')) throw new RuntimeException('Konfirmasi password tidak sama.');
        $role='user';
        $username=unique_username($pdo,$name);$stmt=$pdo->prepare('INSERT INTO users(name,username,email,password,role) VALUES(?,?,?,?,?)'); $stmt->execute([$name,$username,$email,password_hash($password,PASSWORD_DEFAULT),$role]);
        session_regenerate_id(true); $_SESSION['user']=['id'=>(int)$pdo->lastInsertId(),'name'=>$name,'email'=>$email,'role'=>$role];
        flash('success','Akun berhasil dibuat.'); header('Location: dashboard'); exit;
    } catch(PDOException $e){$error=$e->getCode()==='23000'?'Email sudah terdaftar.':'Pendaftaran gagal. Coba kembali.';} catch(Throwable $e){$error=$e->getMessage();}
}
?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Daftar — Twibbo</title><link rel="stylesheet" href="style.css"><link rel="stylesheet" href="teal.css"><link rel="stylesheet" href="auth.css"></head><body><main class="auth-page"><section class="auth-visual"><a class="brand" href="home.php"><span class="brand-mark">T</span>Twibbo by Apins Digital</a><div class="auth-copy"><span class="eyebrow">Mulai secara gratis</span><h1>Buat kampanyemu lebih mudah dibagikan.</h1><p>Unggah template, dapatkan tautan unik, dan bagikan ke komunitasmu dalam hitungan menit.</p></div><small>© <?= date('Y') ?> Apins Digital</small></section><section class="auth-form-wrap"><div class="auth-card"><h2>Buat akun</h2><p class="subtitle">Kelola template dan tautan kampanyemu.</p><?php if($error):?><div class="alert alert-error" style="margin-top:20px"><?=e($error)?></div><?php endif?><form method="post"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><div class="field"><label for="name">Nama lengkap</label><input id="name" name="name" required maxlength="100" autocomplete="name" value="<?=e($_POST['name']??'')?>"></div><div class="field"><label for="email">Email</label><input id="email" name="email" type="email" required autocomplete="email" value="<?=e($_POST['email']??'')?>"></div><div class="field"><label for="password">Password</label><input id="password" name="password" type="password" required minlength="8" autocomplete="new-password"></div><div class="field"><label for="password_confirmation">Ulangi password</label><input id="password_confirmation" name="password_confirmation" type="password" required minlength="8" autocomplete="new-password"></div><button class="btn btn-primary" style="width:100%">Daftar sekarang</button></form><p class="auth-switch">Sudah punya akun? <a href="login.php">Masuk</a></p></div></section></main></body></html>
