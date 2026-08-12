<?php
declare(strict_types=1);
require __DIR__ . '/config.php';
header('Content-Type: application/xml; charset=UTF-8');
$base = rtrim(getenv('APP_URL') ?: 'https://bingkaiin.apinsdigital.my.id', '/');
$items = $pdo->query("SELECT slug, created_at FROM twibbons WHERE visibility = 'public' ORDER BY created_at DESC")->fetchAll();
echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url><loc><?=htmlspecialchars($base . '/', ENT_XML1)?></loc><changefreq>weekly</changefreq><priority>1.0</priority></url>
  <?php foreach ($items as $item): ?>
  <url><loc><?=htmlspecialchars($base . '/t/' . rawurlencode($item['slug']), ENT_XML1)?></loc><lastmod><?=date('c', strtotime($item['created_at']))?></lastmod><changefreq>monthly</changefreq><priority>0.8</priority></url>
  <?php endforeach ?>
</urlset>
