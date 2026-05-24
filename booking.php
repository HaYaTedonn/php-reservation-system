<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

$st  = settings();
$cfg = $GLOBALS['app_config'];

/* --- サービスの取得・検証 --- */
$serviceId = (int) param('service', '0');
$service = DB::run('SELECT * FROM services WHERE id = ? AND is_active = 1', [$serviceId])->fetch();
if (!$service) {
    flash('メニューが選択されていません。', 'error');
    redirect('index.php');
}

$dateMin = date('Y-m-d');
$dateMax = date('Y-m-d', strtotime('+13 day'));

/* ========================================================
 *  POST: 予約の確定（CSRF・バリデーション・重複チェック）
 * ======================================================== */
$errors = [];
if (is_post()) {
    csrf_check();

    $date = param('date');
    $time = param('time');
    $name = param('name');
    $tel  = param('tel');
    $mail = param('email');
    $note = param('note');

    // 入力検証（サーバー側）
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || $date < $dateMin || $date > $dateMax) {
        $errors[] = '日付が正しくありません。';
    } elseif (is_closed($date, $st)) {
        $errors[] = '選択された日は定休日です。';
    }
    if (!preg_match('/^\d{2}:\d{2}$/', $time)) {
        $errors[] = '時間が正しくありません。';
    }
    if ($name === '' || mb_strlen($name) > 100) {
        $errors[] = 'お名前をご入力ください。';
    }
    if (!preg_match('/^[0-9\-+() ]{8,20}$/', $tel)) {
        $errors[] = '電話番号をご確認ください。';
    }
    if ($mail !== '' && !filter_var($mail, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'メールアドレスの形式が正しくありません。';
    }
    if (mb_strlen($note) > 500) {
        $errors[] = 'ご要望は500文字以内でご入力ください。';
    }

    // 枠がまだ空いているか（最終確認） + 確定をトランザクションで
    if (!$errors) {
        if (!in_array($time, available_slots($date, $service, $st), true)) {
            $errors[] = '申し訳ありません。その時間はちょうど埋まってしまいました。別の時間をお選びください。';
        } else {
            $pdo = DB::conn();
            try {
                $pdo->beginTransaction();

                // 競合する予約をロックして二重予約を防止
                $dur   = (int) $service['duration_min'];
                $start = to_time(to_min($time));
                $end   = to_time(to_min($time) + $dur);
                $seats = max(1, (int) ($st['seats'] ?? 1));

                $conflict = DB::run(
                    'SELECT COUNT(*) c FROM bookings
                     WHERE booking_date = ? AND status <> "cancelled"
                       AND start_time < ? AND end_time > ?
                     FOR UPDATE',
                    [$date, $end, $start]
                )->fetch();

                if ((int) $conflict['c'] >= $seats) {
                    $pdo->rollBack();
                    $errors[] = 'その時間はちょうど埋まってしまいました。別の時間をお選びください。';
                } else {
                    // ユニークな予約コードを確保
                    do {
                        $code = gen_code($cfg['code_prefix'] ?? 'RSV');
                        $dup = DB::run('SELECT 1 FROM bookings WHERE code = ?', [$code])->fetch();
                    } while ($dup);

                    DB::run(
                        'INSERT INTO bookings
                          (code, service_id, customer_name, customer_tel, customer_email, note, booking_date, start_time, end_time, status)
                         VALUES (?,?,?,?,?,?,?,?,?,"confirmed")',
                        [$code, $service['id'], $name, $tel, $mail, $note, $date, $start, $end]
                    );
                    $pdo->commit();
                    redirect('complete.php?code=' . urlencode($code));
                }
            } catch (Throwable $ex) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $errors[] = '予約処理でエラーが発生しました。時間をおいて再度お試しください。';
            }
        }
    }
    // エラー時はフォーム値を保持して下で再表示
    $selDate = $date; $selTime = $time;
}

/* ========================================================
 *  GET: 現在のステップを決定して表示
 * ======================================================== */
$selDate = $selDate ?? param('date');
$selTime = $selTime ?? param('time');

$validDate = preg_match('/^\d{4}-\d{2}-\d{2}$/', $selDate) && $selDate >= $dateMin && $selDate <= $dateMax && !is_closed($selDate, $st);
$slots = $validDate ? available_slots($selDate, $service, $st) : [];
$validTime = $validDate && in_array($selTime, $slots, true);

$step = !$validDate ? 'date' : (!$validTime ? 'time' : 'form');
$page_title = 'ご予約';
require __DIR__ . '/includes/public_header.php';
?>
<section class="hero" style="padding:34px 0 20px">
  <p class="lead">RESERVATION</p>
  <h1><?= e($service['name']) ?></h1>
  <p><?= e($service['description']) ?>　（約<?= (int) $service['duration_min'] ?>分・<?= e(yen((int)$service['price'])) ?>）</p>
</section>

<ul class="rail">
  <li class="done"><span class="n">01</span>メニュー</li>
  <li class="<?= $step==='date'?'on':'done' ?>"><span class="n">02</span>日付</li>
  <li class="<?= $step==='time'?'on':($step==='form'?'done':'') ?>"><span class="n">03</span>時間</li>
  <li class="<?= $step==='form'?'on':'' ?>"><span class="n">04</span>お客様情報</li>
</ul>

<?php foreach ($errors as $er): ?><div class="alert"><?= e($er) ?></div><?php endforeach; ?>

<?php if ($step === 'date'): ?>
  <h2 class="sec-t">日付を選ぶ</h2>
  <p class="sec-d">◯ 空きあり　△ 残りわずか　× 満　休 定休</p>
  <div class="grid-d">
    <?php foreach (next_dates(14) as $d):
        $mk = day_mark($d, $service, $st);
        $off = ($mk === '休' || $mk === '×');
        $cls = $mk==='◯'?'mk-ok':($mk==='△'?'mk-few':'mk-no');
        $wd = weekday_ja($d); $wcls = $wd==='土'?'sat':($wd==='日'?'sun':'');
    ?>
      <a class="day <?= $off?'off':'' ?>" <?= $off?'':'href="booking.php?service='.(int)$service['id'].'&date='.$d.'"' ?>>
        <div class="dd"><?= (int) substr($d, 8, 2) ?></div>
        <div class="ww <?= $wcls ?>"><?= $wd ?></div>
        <div class="mk <?= $cls ?>"><?= $mk ?></div>
      </a>
    <?php endforeach; ?>
  </div>

<?php elseif ($step === 'time'): ?>
  <div class="chosen">
    <b><?= (int) substr($selDate,5,2) ?>月<?= (int) substr($selDate,8,2) ?>日（<?= weekday_ja($selDate) ?>）</b>
    <a class="edit" href="booking.php?service=<?= (int)$service['id'] ?>">日付を選び直す</a>
  </div>
  <h2 class="sec-t">時間を選ぶ</h2>
  <p class="sec-d">ご希望の開始時刻をお選びください。</p>
  <?php if ($slots): ?>
    <div class="grid-t">
      <?php foreach ($slots as $t): ?>
        <a class="slot" href="booking.php?service=<?= (int)$service['id'] ?>&date=<?= e($selDate) ?>&time=<?= e($t) ?>"><?= e($t) ?></a>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <p class="note-empty">この日は空き枠がありません。別の日をお選びください。</p>
  <?php endif; ?>

<?php else: /* form */ ?>
  <div class="chosen">
    <b><?= (int) substr($selDate,5,2) ?>月<?= (int) substr($selDate,8,2) ?>日（<?= weekday_ja($selDate) ?>） <?= e($selTime) ?></b>
    <a class="edit" href="booking.php?service=<?= (int)$service['id'] ?>&date=<?= e($selDate) ?>">時間を選び直す</a>
  </div>

  <h2 class="sec-t">お客様情報</h2>
  <p class="sec-d">ご予約内容をご確認のうえ、ご入力ください。</p>

  <div class="summary">
    <div class="row"><span>メニュー</span><b><?= e($service['name']) ?></b></div>
    <div class="row"><span>日時</span><b><?= (int) substr($selDate,5,2) ?>月<?= (int) substr($selDate,8,2) ?>日 <?= e($selTime) ?></b></div>
    <div class="row"><span>所要時間</span><b>約<?= (int) $service['duration_min'] ?>分</b></div>
    <div class="row total"><span>料金</span><b><?= e(yen((int)$service['price'])) ?></b></div>
  </div>

  <form method="post" action="booking.php">
    <?= csrf_field() ?>
    <input type="hidden" name="service" value="<?= (int)$service['id'] ?>">
    <input type="hidden" name="date" value="<?= e($selDate) ?>">
    <input type="hidden" name="time" value="<?= e($selTime) ?>">
    <div class="two">
      <div class="field"><label>お名前<span class="req">必須</span></label><input name="name" value="<?= e($_POST['name'] ?? '') ?>" placeholder="山田 花子" maxlength="100"></div>
      <div class="field"><label>電話番号<span class="req">必須</span></label><input name="tel" value="<?= e($_POST['tel'] ?? '') ?>" placeholder="090-0000-0000"></div>
    </div>
    <div class="field"><label>メールアドレス</label><input name="email" value="<?= e($_POST['email'] ?? '') ?>" placeholder="example@mail.com"></div>
    <div class="field"><label>ご要望（任意）</label><textarea name="note" maxlength="500" placeholder="例）カラーは明るめ希望です"><?= e($_POST['note'] ?? '') ?></textarea></div>
    <div class="btnrow">
      <a class="btn ghost" href="booking.php?service=<?= (int)$service['id'] ?>&date=<?= e($selDate) ?>">戻る</a>
      <button class="btn block" type="submit">この内容で予約する</button>
    </div>
  </form>
<?php endif; ?>

<?php require __DIR__ . '/includes/public_footer.php'; ?>
