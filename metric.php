<?php
declare(strict_types=1);require __DIR__.'/config.php';
if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);exit;}
$id=(int)($_POST['id']??0);$type=$_POST['type']??'';$columns=['share'=>'share_count','use'=>'use_count','download'=>'download_count'];
if(!$id||!isset($columns[$type])){http_response_code(422);exit;}
$pdo->prepare("UPDATE twibbons SET {$columns[$type]}={$columns[$type]}+1 WHERE id=?")->execute([$id]);http_response_code(204);
