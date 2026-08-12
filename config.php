<?php
declare(strict_types=1);

$host = getenv('DB_HOST') ?: 'localhost';
$dbname = getenv('DB_NAME') ?: 'twibbon_db';
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASS') ?: '';

try {
    $server = new PDO("mysql:host={$host};charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    $safeDbName = str_replace('`', '``', $dbname);
    $server->exec("CREATE DATABASE IF NOT EXISTS `{$safeDbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

    $pdo = new PDO("mysql:host={$host};dbname={$dbname};charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    $pdo->exec("CREATE TABLE IF NOT EXISTS twibbons (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(120) NOT NULL,
        description VARCHAR(500) NOT NULL DEFAULT '',
        template_image VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(190) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role ENUM('user','admin') NOT NULL DEFAULT 'user',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $adminEmail = getenv('DEFAULT_ADMIN_EMAIL') ?: '';
    $adminPassword = getenv('DEFAULT_ADMIN_PASSWORD') ?: '';
    if ($adminEmail !== '' && $adminPassword !== '') {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
        $stmt->execute([$adminEmail]);
        if ((int) $stmt->fetchColumn() === 0) {
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'admin')");
        $stmt->execute(['Administrator Apins', $adminEmail, password_hash($adminPassword, PASSWORD_DEFAULT)]);
        }
    }

    $columns = $pdo->query('SHOW COLUMNS FROM twibbons')->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('slug', $columns, true)) $pdo->exec("ALTER TABLE twibbons ADD slug VARCHAR(64) NULL UNIQUE AFTER id");
    if (!in_array('owner_id', $columns, true)) $pdo->exec("ALTER TABLE twibbons ADD owner_id INT UNSIGNED NULL AFTER slug");
    if (!in_array('visibility', $columns, true)) $pdo->exec("ALTER TABLE twibbons ADD visibility ENUM('public','unlisted') NOT NULL DEFAULT 'public' AFTER owner_id");
    $pdo->exec("ALTER TABLE twibbons MODIFY visibility ENUM('public','unlisted','private') NOT NULL DEFAULT 'public'");
    $pdo->exec("UPDATE twibbons SET slug = CONCAT('template-', id) WHERE slug IS NULL OR slug = ''");

    if ((int) $pdo->query('SELECT COUNT(*) FROM twibbons')->fetchColumn() === 0 && is_file(__DIR__ . '/uploads/sample_template.png')) {
        $stmt = $pdo->prepare('INSERT INTO twibbons (title, description, template_image) VALUES (?, ?, ?)');
        $stmt->execute(['Template Contoh', 'Mulai membuat twibbon pertamamu dengan template ini.', 'sample_template.png']);
    }
} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    http_response_code(500);
    exit('Aplikasi belum dapat terhubung ke database. Periksa konfigurasi lalu coba lagi.');
}
