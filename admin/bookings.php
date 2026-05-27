<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
require_login();

$admin_page = 'bookings'; $admin_title = '予約一覧';

// --- ステータス変更（POST） ---
if (is_post()) {
    csrf_check();
    $id     = (int) param('id');
    $action = param('action');
    $map = ['cancel' => 'cancelled', 'done' => 'done', 'reopen' => 'confirmed'];
    if (isset($map[$action]) && $id > 0) {
        DB::run('UPDATE bookings SET status = ? WHERE id = ?', [$map[$action], $id]);
        flash('予約のステータスを更新しました。');
    }
    redirect('bookings.php?date=' . urlencode(param('date', date('Y-m-d'))));
}

$date = param('date', date('Y-m-d'));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) $date = date('Y-m-d');

$list = DB::run(
    'SELECT b.*, s.name service_name, s.price
       FROM bookings b JOIN services s ON s.id = b.service_id
      WHERE b.booking_date = ?
      ORDER BY b.start_time', [$date]
)->fetchAll();

// 日付バー用の件数
$counts = [];
foreach (DB::run(
    'SELECT booking_date, COUNT(*) c FROM bookings WHERE status <> "cancelled" GROUP BY booking_date'
)->fetchAll() as $r) { $counts[$r['booking_date']] = (int) $r['c']; }

require __DIR__ . '/../includes/admin_header.php';
?>
<div class="head"><h1>予約一覧</h1></div>
<p class="desc">日付を選ぶと、その日の予約が時間順に表示されます。</p>

<div class="datebar">
  <?php foreach (next_dates(14) as $d): ?>
    <a class="<?= $d === $date ? 'on' : '' ?>" href="bookings.php?date=<?= $d ?>">
      <div class="d"><?= (int) substr($d,8,2) ?></div>
      <div class="w"><?= weekday_ja($d) ?>・<?= $counts[$d] ?? 0 ?></div>
    </a>
  <?php endforeach; ?>
</div>

<div class="panel">
  <?php if (!$list): ?>
    <div class="empty">この日の予約はありません。</div>
  <?php else: ?>
    <table>
      <thead><tr><th>時間</th><th>お客様</th><th>メニュー</th><th>状態</th><th style="text-align:right">操作</th></tr></thead>
      <tbody>
      <?php foreach ($list as $b): ?>
        <tr>
          <td class="time"><?= e(substr($b['start_time'],0,5)) ?><div class="muted">〜<?= e(substr($b['end_time'],0,5)) ?></div></td>
          <td>
            <?= e($b['customer_name']) ?> 様
            <div class="muted"><?= e($b['customer_tel']) ?><?= $b['customer_email']!=='' ? ' / '.e($b['customer_email']) : '' ?></div>
            <?php if ($b['note'] !== ''): ?><div class="muted">ご要望：<?= e($b['note']) ?></div><?php endif; ?>
            <div class="muted">予約番号 <?= e($b['code']) ?></div>
          </td>
          <td><span class="tag"><?= e($b['service_name']) ?></span><div class="muted"><?= e(yen((int)$b['price'])) ?></div></td>
          <td><span class="st <?= e($b['status']) ?>"><?= ['confirmed'=>'確定','cancelled'=>'取消','done'=>'来店済'][$b['status']] ?></span></td>
          <td style="text-align:right;white-space:nowrap">
            <?php if ($b['status'] === 'confirmed'): ?>
              <form class="inline" method="post" action="bookings.php" onsubmit="return confirm('来店済にしますか？')">
                <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$b['id'] ?>"><input type="hidden" name="date" value="<?= e($date) ?>"><input type="hidden" name="action" value="done">
                <button class="btn sm ghost">来店済</button>
              </form>
              <form class="inline" method="post" action="bookings.php" onsubmit="return confirm('この予約を取り消しますか？')">
                <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$b['id'] ?>"><input type="hidden" name="date" value="<?= e($date) ?>"><input type="hidden" name="action" value="cancel">
                <button class="btn sm danger">取消</button>
              </form>
            <?php else: ?>
              <form class="inline" method="post" action="bookings.php">
                <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$b['id'] ?>"><input type="hidden" name="date" value="<?= e($date) ?>"><input type="hidden" name="action" value="reopen">
                <button class="btn sm ghost">確定に戻す</button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
