<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';

$admin_page = 'dashboard'; $admin_title = 'ダッシュボード';

$today    = date('Y-m-d');
$weekEnd  = date('Y-m-d', strtotime('+6 day'));

$todayCnt = (int) DB::run(
    'SELECT COUNT(*) c FROM bookings WHERE booking_date = ? AND status <> "cancelled"', [$today]
)->fetch()['c'];

$week = DB::run(
    'SELECT COUNT(*) c, COALESCE(SUM(s.price),0) rev
       FROM bookings b JOIN services s ON s.id = b.service_id
      WHERE b.booking_date BETWEEN ? AND ? AND b.status <> "cancelled"',
    [$today, $weekEnd]
)->fetch();

$total = (int) DB::run('SELECT COUNT(*) c FROM bookings WHERE status <> "cancelled"')->fetch()['c'];

$todays = DB::run(
    'SELECT b.*, s.name service_name, s.price
       FROM bookings b JOIN services s ON s.id = b.service_id
      WHERE b.booking_date = ? AND b.status <> "cancelled"
      ORDER BY b.start_time', [$today]
)->fetchAll();

$upcoming = DB::run(
    'SELECT b.*, s.name service_name
       FROM bookings b JOIN services s ON s.id = b.service_id
      WHERE b.booking_date > ? AND b.status = "confirmed"
      ORDER BY b.booking_date, b.start_time LIMIT 6', [$today]
)->fetchAll();

require __DIR__ . '/../includes/admin_header.php';
?>
<div class="head"><h1>ダッシュボード</h1></div>
<p class="desc"><?= date('Y年n月j日') ?>（<?= weekday_ja($today) ?>）の状況です。</p>

<div class="kpis">
  <div class="kpi main"><div class="l">本日の予約</div><div class="v"><?= $todayCnt ?> 件</div></div>
  <div class="kpi"><div class="l">今週の予約</div><div class="v"><?= (int)$week['c'] ?> 件</div></div>
  <div class="kpi"><div class="l">今週の売上（見込）</div><div class="v"><?= e(yen((int)$week['rev'])) ?></div></div>
  <div class="kpi"><div class="l">予約 累計</div><div class="v"><?= $total ?> 件</div></div>
</div>

<div class="head"><h1 style="font-size:18px">本日の予約</h1><div class="sp"></div><a class="btn sm ghost" href="bookings.php">予約一覧へ</a></div>
<div class="panel" style="margin:10px 0 26px">
  <?php if (!$todays): ?>
    <div class="empty">本日の予約はありません。</div>
  <?php else: ?>
    <table>
      <thead><tr><th>時間</th><th>お客様</th><th>メニュー</th><th>連絡先</th></tr></thead>
      <tbody>
      <?php foreach ($todays as $b): ?>
        <tr>
          <td class="time"><?= e(substr($b['start_time'],0,5)) ?></td>
          <td><?= e($b['customer_name']) ?> 様<?= $b['note'] !== '' ? '<div class="muted">'.e($b['note']).'</div>' : '' ?></td>
          <td><span class="tag"><?= e($b['service_name']) ?></span></td>
          <td class="muted"><?= e($b['customer_tel']) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<div class="head"><h1 style="font-size:18px">これからのご予約</h1></div>
<div class="panel" style="margin-top:10px">
  <?php if (!$upcoming): ?>
    <div class="empty">先の予約はまだありません。</div>
  <?php else: ?>
    <table>
      <thead><tr><th>日付</th><th>時間</th><th>お客様</th><th>メニュー</th></tr></thead>
      <tbody>
      <?php foreach ($upcoming as $b): ?>
        <tr>
          <td><?= (int) substr($b['booking_date'],5,2) ?>/<?= (int) substr($b['booking_date'],8,2) ?>（<?= weekday_ja($b['booking_date']) ?>）</td>
          <td class="time"><?= e(substr($b['start_time'],0,5)) ?></td>
          <td><?= e($b['customer_name']) ?> 様</td>
          <td><span class="tag"><?= e($b['service_name']) ?></span></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
