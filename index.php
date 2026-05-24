<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

$services = DB::run('SELECT * FROM services WHERE is_active = 1 ORDER BY sort_order, id')->fetchAll();
$page_title = 'ネット予約';
require __DIR__ . '/includes/public_header.php';
?>
<section class="hero">
  <p class="lead">RESERVATION</p>
  <h1>ご予約</h1>
  <p>ご希望のメニューをお選びください。24時間いつでもご予約いただけます。</p>
</section>

<ul class="rail">
  <li class="on"><span class="n">01</span>メニュー</li>
  <li><span class="n">02</span>日付</li>
  <li><span class="n">03</span>時間</li>
  <li><span class="n">04</span>お客様情報</li>
</ul>

<h2 class="sec-t">メニューを選ぶ</h2>
<p class="sec-d">所要時間・料金をご確認のうえ、お選びください。</p>

<?php foreach ($services as $s): ?>
  <a class="svc" href="booking.php?service=<?= (int) $s['id'] ?>">
    <div>
      <div class="nm"><?= e($s['name']) ?></div>
      <div class="ds"><?= e($s['description']) ?></div>
    </div>
    <div class="meta">
      <div class="pr"><?= e(yen((int) $s['price'])) ?></div>
      <div class="mn">約<?= (int) $s['duration_min'] ?>分</div>
    </div>
  </a>
<?php endforeach; ?>

<?php require __DIR__ . '/includes/public_footer.php'; ?>
