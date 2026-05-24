<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

$code = param('code');
$b = DB::run(
    'SELECT b.*, s.name AS service_name, s.price
       FROM bookings b JOIN services s ON s.id = b.service_id
      WHERE b.code = ?',
    [$code]
)->fetch();

if (!$b) {
    flash('予約が見つかりませんでした。', 'error');
    redirect('index.php');
}

$page_title = 'ご予約完了';
require __DIR__ . '/includes/public_header.php';
?>
<div class="done">
  <div class="seal">結</div>
  <h1>ご予約ありがとうございます</h1>
  <p>下記の内容で承りました。当日のご来店をお待ちしております。<br>（実装版では確認メール／SMSを自動送信します）</p>
</div>

<div class="ticket">
  <div class="code">予約番号　<?= e($b['code']) ?></div>
  <div class="row"><span>お名前</span><b><?= e($b['customer_name']) ?> 様</b></div>
  <div class="row"><span>メニュー</span><b><?= e($b['service_name']) ?></b></div>
  <div class="row"><span>日時</span><b><?= (int) substr($b['booking_date'],5,2) ?>月<?= (int) substr($b['booking_date'],8,2) ?>日（<?= weekday_ja($b['booking_date']) ?>） <?= e(substr($b['start_time'],0,5)) ?></b></div>
  <div class="row"><span>料金</span><b><?= e(yen((int)$b['price'])) ?></b></div>
  <?php if ($b['note'] !== ''): ?><div class="row"><span>ご要望</span><b><?= e($b['note']) ?></b></div><?php endif; ?>
</div>

<div class="btnrow">
  <a class="btn ghost block" href="index.php">トップへ戻る</a>
</div>

<?php require __DIR__ . '/includes/public_footer.php'; ?>
