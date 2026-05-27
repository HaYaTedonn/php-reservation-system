<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
require_login();

$admin_page = 'services'; $admin_title = 'メニュー管理';

if (is_post()) {
    csrf_check();
    $action = param('action');

    if ($action === 'save') {
        $id    = (int) param('id');
        $name  = param('name');
        $dur   = (int) param('duration_min');
        $price = (int) param('price');
        $desc  = param('description');

        $err = '';
        if ($name === '' || mb_strlen($name) > 100) $err = 'メニュー名をご確認ください。';
        elseif ($dur < 5 || $dur > 600) $err = '所要時間は5〜600分で入力してください。';
        elseif ($price < 0) $err = '料金が正しくありません。';

        if ($err) {
            flash($err, 'error');
        } elseif ($id > 0) {
            DB::run('UPDATE services SET name=?, duration_min=?, price=?, description=? WHERE id=?',
                [$name, $dur, $price, $desc, $id]);
            flash('メニューを更新しました。');
        } else {
            $max = (int) DB::run('SELECT COALESCE(MAX(sort_order),0) m FROM services')->fetch()['m'];
            DB::run('INSERT INTO services (name, duration_min, price, description, is_active, sort_order) VALUES (?,?,?,?,1,?)',
                [$name, $dur, $price, $desc, $max + 1]);
            flash('メニューを追加しました。');
        }
    } elseif ($action === 'toggle') {
        DB::run('UPDATE services SET is_active = 1 - is_active WHERE id = ?', [(int) param('id')]);
        flash('公開状態を変更しました。');
    } elseif ($action === 'delete') {
        $id = (int) param('id');
        $used = (int) DB::run('SELECT COUNT(*) c FROM bookings WHERE service_id = ?', [$id])->fetch()['c'];
        if ($used > 0) {
            // 予約実績があるものは削除せず非公開に（参照整合性を守る）
            DB::run('UPDATE services SET is_active = 0 WHERE id = ?', [$id]);
            flash('予約実績があるため、削除ではなく非公開にしました。');
        } else {
            DB::run('DELETE FROM services WHERE id = ?', [$id]);
            flash('メニューを削除しました。');
        }
    }
    redirect('services.php');
}

$services = DB::run('SELECT * FROM services ORDER BY sort_order, id')->fetchAll();
$edit = null;
if (($eid = (int) param('edit')) > 0) {
    $edit = DB::run('SELECT * FROM services WHERE id = ?', [$eid])->fetch() ?: null;
}

require __DIR__ . '/../includes/admin_header.php';
?>
<div class="head"><h1>メニュー管理</h1></div>
<p class="desc">予約画面に表示されるメニュー・料金・所要時間を管理します。</p>

<div class="card-form">
  <h3><?= $edit ? 'メニューを編集' : 'メニューを追加' ?></h3>
  <form method="post" action="services.php" class="row-form">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="id" value="<?= $edit ? (int)$edit['id'] : 0 ?>">
    <div class="fld" style="flex:2 1 200px"><label>メニュー名</label><input name="name" value="<?= e($edit['name'] ?? '') ?>" maxlength="100"></div>
    <div class="fld"><label>所要(分)</label><input name="duration_min" type="number" min="5" max="600" value="<?= e((string)($edit['duration_min'] ?? 60)) ?>" style="width:90px"></div>
    <div class="fld"><label>料金(円)</label><input name="price" type="number" min="0" value="<?= e((string)($edit['price'] ?? 0)) ?>" style="width:110px"></div>
    <div class="fld" style="flex:2 1 220px"><label>説明</label><input name="description" value="<?= e($edit['description'] ?? '') ?>" maxlength="255"></div>
    <div class="fld"><label>&nbsp;</label><button class="btn"><?= $edit ? '更新' : '追加' ?></button></div>
    <?php if ($edit): ?><div class="fld"><label>&nbsp;</label><a class="btn ghost" href="services.php">取消</a></div><?php endif; ?>
  </form>
</div>

<div class="panel">
  <table>
    <thead><tr><th>メニュー</th><th>所要</th><th>料金</th><th>公開</th><th style="text-align:right">操作</th></tr></thead>
    <tbody>
    <?php foreach ($services as $s): ?>
      <tr>
        <td><b><?= e($s['name']) ?></b><div class="muted"><?= e($s['description']) ?></div></td>
        <td>約<?= (int)$s['duration_min'] ?>分</td>
        <td><?= e(yen((int)$s['price'])) ?></td>
        <td>
          <form class="inline" method="post" action="services.php">
            <?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
            <button class="toggle <?= $s['is_active'] ? 'on' : 'off' ?>"><?= $s['is_active'] ? '公開中' : '非公開' ?></button>
          </form>
        </td>
        <td style="text-align:right;white-space:nowrap">
          <a class="btn sm ghost" href="services.php?edit=<?= (int)$s['id'] ?>">編集</a>
          <form class="inline" method="post" action="services.php" onsubmit="return confirm('このメニューを削除しますか？')">
            <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
            <button class="btn sm danger">削除</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
