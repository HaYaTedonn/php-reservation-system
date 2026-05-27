<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
require_login();

$admin_page = 'settings'; $admin_title = '営業設定';

if (is_post()) {
    csrf_check();
    $shop  = param('shop_name');
    $open  = param('open_time');
    $close = param('close_time');
    $step  = (int) param('slot_interval');
    $seats = (int) param('seats');
    $closed = $_POST['closed_days'] ?? [];

    $err = '';
    if ($shop === '' || mb_strlen($shop) > 100) $err = '店舗名をご確認ください。';
    elseif (!preg_match('/^\d{2}:\d{2}$/', $open) || !preg_match('/^\d{2}:\d{2}$/', $close)) $err = '営業時間の形式が正しくありません。';
    elseif (to_min($open) >= to_min($close)) $err = '開店時刻は閉店時刻より前にしてください。';
    elseif (!in_array($step, [15, 20, 30, 60], true)) $err = '予約間隔が正しくありません。';
    elseif ($seats < 1 || $seats > 20) $err = '席数は1〜20で入力してください。';

    if ($err) {
        flash($err, 'error');
    } else {
        $closedCsv = implode(',', array_map('intval', array_filter((array)$closed, fn($x) => $x !== '')));
        $pairs = [
            'shop_name' => $shop, 'open_time' => $open, 'close_time' => $close,
            'slot_interval' => (string)$step, 'seats' => (string)$seats, 'closed_days' => $closedCsv,
        ];
        foreach ($pairs as $k => $v) {
            DB::run('INSERT INTO settings (k, v) VALUES (?, ?) ON DUPLICATE KEY UPDATE v = VALUES(v)', [$k, $v]);
        }
        flash('営業設定を保存しました。');
    }
    redirect('settings.php');
}

$st = settings();
$closedDays = array_map('intval', array_filter(explode(',', $st['closed_days'] ?? ''), fn($x) => $x !== ''));
$wd = ['日', '月', '火', '水', '木', '金', '土'];

require __DIR__ . '/../includes/admin_header.php';
?>
<div class="head"><h1>営業設定</h1></div>
<p class="desc">営業時間・予約間隔・定休日などを設定します。予約画面の空き表示に反映されます。</p>

<form method="post" action="settings.php" class="card-form" style="max-width:560px">
  <?= csrf_field() ?>
  <div class="row-form" style="margin-bottom:14px">
    <div class="fld" style="flex:1 1 100%"><label>店舗名</label><input name="shop_name" value="<?= e($st['shop_name'] ?? '') ?>" maxlength="100" style="width:100%"></div>
  </div>
  <div class="row-form" style="margin-bottom:14px">
    <div class="fld"><label>開店</label><input name="open_time" type="time" value="<?= e($st['open_time'] ?? '10:00') ?>"></div>
    <div class="fld"><label>閉店</label><input name="close_time" type="time" value="<?= e($st['close_time'] ?? '19:00') ?>"></div>
    <div class="fld"><label>予約間隔</label>
      <select name="slot_interval">
        <?php foreach ([15,20,30,60] as $iv): ?>
          <option value="<?= $iv ?>" <?= (int)($st['slot_interval'] ?? 30)===$iv?'selected':'' ?>><?= $iv ?>分</option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="fld"><label>席数（同時対応）</label><input name="seats" type="number" min="1" max="20" value="<?= e((string)($st['seats'] ?? 1)) ?>" style="width:90px"></div>
  </div>
  <div class="fld" style="margin-bottom:16px">
    <label>定休日</label>
    <div style="display:flex;gap:14px;flex-wrap:wrap;padding-top:4px">
      <?php for ($i=0;$i<7;$i++): ?>
        <label style="display:flex;align-items:center;gap:5px;font-size:14px;color:var(--ink)">
          <input type="checkbox" name="closed_days[]" value="<?= $i ?>" <?= in_array($i,$closedDays,true)?'checked':'' ?>> <?= $wd[$i] ?>
        </label>
      <?php endfor; ?>
    </div>
  </div>
  <button class="btn">保存する</button>
</form>

<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
