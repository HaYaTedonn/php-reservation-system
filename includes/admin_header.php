<?php
/** 管理画面 共通ヘッダー（要ログイン）。$admin_page で現在ページを指定。 */
require_login();
$me = current_admin();
$st = settings();
$nav = [
    'dashboard' => ['dashboard.php', 'ダッシュボード'],
    'bookings'  => ['bookings.php', '予約一覧'],
    'services'  => ['services.php', 'メニュー管理'],
    'settings'  => ['settings.php', '営業設定'],
];
?><!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e(($admin_title ?? '管理') . '｜' . ($st['shop_name'] ?? '')) ?></title>
<link rel="stylesheet" href="../assets/admin.css">
</head>
<body>
<div class="shell">
  <aside class="side">
    <div class="logo"><span class="en">ADMIN</span><?= e($st['shop_name'] ?? '管理') ?></div>
    <nav>
      <?php foreach ($nav as $key => $n): ?>
        <a class="<?= ($admin_page ?? '') === $key ? 'on' : '' ?>" href="<?= e($n[0]) ?>"><?= e($n[1]) ?></a>
      <?php endforeach; ?>
    </nav>
    <div class="who"><?= e($me['name']) ?> でログイン中<br><a href="logout.php">ログアウト</a></div>
  </aside>
  <main>
    <?php foreach ((flash() ?? []) as $f): ?>
      <div class="flash <?= $f['t'] === 'error' ? 'error' : '' ?>"><?= e($f['m']) ?></div>
    <?php endforeach; ?>
