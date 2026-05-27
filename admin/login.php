<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';

if (current_admin()) {
    redirect('dashboard.php');
}

$error = '';
if (is_post()) {
    csrf_check();
    $email = param('email');
    $pass  = param('password');
    if (attempt_login($email, $pass)) {
        redirect('dashboard.php');
    }
    $error = 'メールアドレスまたはパスワードが正しくありません。';
}

$st = settings();
?><!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>店舗管理ログイン｜<?= e($st['shop_name'] ?? '') ?></title>
<link rel="stylesheet" href="../assets/admin.css">
</head>
<body>
<div class="login">
  <form class="box" method="post" action="login.php">
    <?= csrf_field() ?>
    <div class="brand"><?= e($st['shop_name'] ?? '管理') ?></div>
    <div class="en">ADMIN LOGIN</div>
    <?php if ($error): ?><div class="flash error" style="margin-bottom:10px"><?= e($error) ?></div><?php endif; ?>
    <label>メールアドレス</label>
    <input type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" autofocus>
    <label>パスワード</label>
    <input type="password" name="password">
    <button class="btn" type="submit">ログイン</button>
    <div class="hint">デモ用ログイン<br>admin@yui-salon.example ／ yui-admin-2026</div>
  </form>
</div>
</body>
</html>
