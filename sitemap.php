<?php
declare(strict_types=1);
require __DIR__ . '/config.php';
header('Content-Type: application/xml; charset=UTF-8');
$base = rtrim(getenv('APP_URL') ?: 'https://bingkaiin.apinsdigital.my.id', '/');
$items = $pdo->query("SELECT slug,created_at FROM twibbons WHERE visibility='public' AND moderation_status='approved' ORDER BY created_at DESC")->fetchAll();
$creators = $pdo->query("SELECT DISTINCT u.username,u.created_at FROM users u JOIN twibbons t ON t.owner_id=u.id WHERE u.status='active' AND t.visibility='public' AND t.moderation_status='approved'")->fetchAll();
$articles = $pdo->query("SELECT slug,updated_at FROM articles WHERE status='published'")->fetchAll();
echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url><loc><?=htmlspecialchars($base . '/', ENT_XML1)?></loc><changefreq>weekly</changefreq><priority>1.0</priority></url>
  <url><loc><?=htmlspecialchars($base . '/explore', ENT_XML1)?></loc><changefreq>daily</changefreq><priority>0.9</priority></url>
  <?php foreach ($items as $item): ?>
  <url><loc><?=htmlspecialchars($base . '/t/' . rawurlencode($item['slug']), ENT_XML1)?></loc><lastmod><?=date('c', strtotime($item['created_at']))?></lastmod><changefreq>monthly</changefreq><priority>0.8</priority></url>
  <?php endforeach ?>
  <?php foreach ($creators as $creator): ?>
  <url><loc><?=htmlspecialchars($base.'/creator/'.rawurlencode($creator['username']),ENT_XML1)?></loc><lastmod><?=date('c',strtotime($creator['created_at']))?></lastmod><priority>0.7</priority></url>
  <?php endforeach ?>
  <?php foreach ($articles as $article): ?>
  <url><loc><?=htmlspecialchars($base.'/article/'.rawurlencode($article['slug']),ENT_XML1)?></loc><lastmod><?=date('c',strtotime($article['updated_at']))?></lastmod><priority>0.6</priority></url>
  <?php endforeach ?>
</urlset>
