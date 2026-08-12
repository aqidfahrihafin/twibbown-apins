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
    $userColumns = $pdo->query('SHOW COLUMNS FROM users')->fetchAll(PDO::FETCH_COLUMN);
    foreach ([
        'username' => "VARCHAR(60) NULL UNIQUE AFTER name",
        'bio' => "VARCHAR(300) NOT NULL DEFAULT '' AFTER role",
        'avatar' => "VARCHAR(255) NULL AFTER bio",
        'website' => "VARCHAR(255) NOT NULL DEFAULT '' AFTER avatar",
        'is_verified' => "TINYINT(1) NOT NULL DEFAULT 0 AFTER website",
        'status' => "ENUM('active','suspended') NOT NULL DEFAULT 'active' AFTER is_verified",
        'google_id' => "VARCHAR(255) NULL UNIQUE AFTER status"
    ] as $column => $definition) if (!in_array($column, $userColumns, true)) $pdo->exec("ALTER TABLE users ADD {$column} {$definition}");
    $pdo->exec("UPDATE users SET username = CONCAT('creator-', id) WHERE username IS NULL OR username = ''");

    $templateColumns = $pdo->query('SHOW COLUMNS FROM twibbons')->fetchAll(PDO::FETCH_COLUMN);
    foreach ([
        'category_id' => "INT UNSIGNED NULL AFTER owner_id",
        'tags' => "VARCHAR(300) NOT NULL DEFAULT '' AFTER description",
        'view_count' => "INT UNSIGNED NOT NULL DEFAULT 0 AFTER tags",
        'use_count' => "INT UNSIGNED NOT NULL DEFAULT 0 AFTER view_count",
        'download_count' => "INT UNSIGNED NOT NULL DEFAULT 0 AFTER use_count",
        'share_count' => "INT UNSIGNED NOT NULL DEFAULT 0 AFTER download_count",
        'is_featured' => "TINYINT(1) NOT NULL DEFAULT 0 AFTER share_count",
        'moderation_status' => "ENUM('pending','approved','rejected') NOT NULL DEFAULT 'approved' AFTER is_featured"
    ] as $column => $definition) if (!in_array($column, $templateColumns, true)) $pdo->exec("ALTER TABLE twibbons ADD {$column} {$definition}");

    $pdo->exec("CREATE TABLE IF NOT EXISTS categories (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,name VARCHAR(80) NOT NULL,slug VARCHAR(90) NOT NULL UNIQUE,icon VARCHAR(20) NOT NULL DEFAULT '✦',created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $pdo->exec("CREATE TABLE IF NOT EXISTS template_likes (user_id INT UNSIGNED NOT NULL,template_id INT UNSIGNED NOT NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,PRIMARY KEY(user_id,template_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $pdo->exec("CREATE TABLE IF NOT EXISTS template_favorites (user_id INT UNSIGNED NOT NULL,template_id INT UNSIGNED NOT NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,PRIMARY KEY(user_id,template_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $pdo->exec("CREATE TABLE IF NOT EXISTS template_ratings (user_id INT UNSIGNED NOT NULL,template_id INT UNSIGNED NOT NULL,rating TINYINT UNSIGNED NOT NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,PRIMARY KEY(user_id,template_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $pdo->exec("CREATE TABLE IF NOT EXISTS follows (follower_id INT UNSIGNED NOT NULL,creator_id INT UNSIGNED NOT NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,PRIMARY KEY(follower_id,creator_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $pdo->exec("CREATE TABLE IF NOT EXISTS reports (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,reporter_id INT UNSIGNED NOT NULL,template_id INT UNSIGNED NOT NULL,reason VARCHAR(500) NOT NULL,status ENUM('open','resolved','dismissed') NOT NULL DEFAULT 'open',created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $pdo->exec("CREATE TABLE IF NOT EXISTS articles (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,title VARCHAR(160) NOT NULL,slug VARCHAR(180) NOT NULL UNIQUE,excerpt VARCHAR(300) NOT NULL DEFAULT '',content MEDIUMTEXT NOT NULL,status ENUM('draft','published') NOT NULL DEFAULT 'draft',created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $pdo->exec("CREATE TABLE IF NOT EXISTS notifications (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,user_id INT UNSIGNED NOT NULL,message VARCHAR(255) NOT NULL,url VARCHAR(255) NOT NULL DEFAULT '',is_read TINYINT(1) NOT NULL DEFAULT 0,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    if ((int)$pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn() === 0) {
        $seed = [['Nasional','nasional','🇮🇩'],['Pendidikan','pendidikan','🎓'],['Komunitas','komunitas','🤝'],['Keagamaan','keagamaan','✦'],['Ulang Tahun','ulang-tahun','🎉'],['Musik & Fandom','musik-fandom','♫'],['Olahraga','olahraga','⚡'],['Bisnis & Brand','bisnis-brand','◆']];
        $stmt=$pdo->prepare('INSERT INTO categories(name,slug,icon) VALUES(?,?,?)'); foreach($seed as $row)$stmt->execute($row);
    }
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
