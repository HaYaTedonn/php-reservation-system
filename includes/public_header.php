<?php
/** 顧客向け共通ヘッダー。$page_title を任意で設定。 */
$st = settings();
$shop = $st['shop_name'] ?? '予約';
?><!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e(($page_title ?? '') !== '' ? $page_title . '｜' . $shop : $shop) ?></title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header class="site-head">
  <div class="wrap">
    <div class="brand"><span class="en">HAIR &amp; SPA</span><?= e($shop) ?></div>
    <div class="nav">ご予約 / RESERVATION</div>
  </div>
</header>
<main class="wrap">
<?php foreach ((flash() ?? []) as $f): ?>
  <div class="alert"><?= e($f['m']) ?></div>
<?php endforeach; ?>
