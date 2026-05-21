<?php
declare(strict_types=1);

/** HTMLエスケープ（出力時のXSS対策） */
function e(?string $s): string
{
    return htmlspecialchars($s ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** リダイレクトして終了 */
function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

/** フラッシュメッセージ（次のリクエストで1回だけ表示） */
function flash(string $msg = null, string $type = 'info')
{
    if ($msg !== null) {
        $_SESSION['_flash'][] = ['t' => $type, 'm' => $msg];
        return null;
    }
    $f = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $f;
}

/** 金額表示 */
function yen(int $n): string
{
    return '¥' . number_format($n);
}

/** POSTか */
function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

/** GET/POST文字列の取得（trim済み） */
function param(string $key, string $default = ''): string
{
    $v = $_POST[$key] ?? $_GET[$key] ?? $default;
    return is_string($v) ? trim($v) : $default;
}

/** 予約コード生成（接頭辞 + ランダム英数字） */
function gen_code(string $prefix): string
{
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // 紛らわしい文字を除外
    $s = '';
    for ($i = 0; $i < 7; $i++) {
        $s .= $alphabet[random_int(0, strlen($alphabet) - 1)];
    }
    return $prefix . '-' . $s;
}

/* ====================================================================
 *  予約枠ロジック（営業時間・所要時間・定休日・席数を考慮）
 * ==================================================================== */

/** 'HH:MM' → 分 */
function to_min(string $hm): int
{
    [$h, $m] = array_map('intval', explode(':', $hm));
    return $h * 60 + $m;
}

/** 分 → 'HH:MM:SS' */
function to_time(int $min): string
{
    return sprintf('%02d:%02d:00', intdiv($min, 60), $min % 60);
}

/** その日付が定休日か（settings.closed_days = "2,0" 等） */
function is_closed(string $date, array $st): bool
{
    $wd = (int) date('w', strtotime($date)); // 0=日..6=土
    $closed = array_filter(array_map('intval', explode(',', $st['closed_days'] ?? '')), fn($x) => $x !== null);
    return in_array($wd, $closed, true);
}

/**
 * 指定日・サービスで予約可能な開始時刻のリストを返す（'HH:MM'）。
 * 既存の confirmed/done 予約との重なりを席数で判定。
 */
function available_slots(string $date, array $service, array $st): array
{
    if (is_closed($date, $st)) return [];

    $open  = to_min($st['open_time']  ?? '10:00');
    $close = to_min($st['close_time'] ?? '19:00');
    $step  = max(5, (int) ($st['slot_interval'] ?? 30));
    $seats = max(1, (int) ($st['seats'] ?? 1));
    $dur   = (int) $service['duration_min'];

    // 当日の有効予約を取得（キャンセル以外）
    $rows = DB::run(
        'SELECT start_time, end_time FROM bookings WHERE booking_date = ? AND status <> "cancelled"',
        [$date]
    )->fetchAll();

    $busy = array_map(fn($r) => [to_min(substr($r['start_time'], 0, 5)), to_min(substr($r['end_time'], 0, 5))], $rows);

    $slots = [];
    for ($t = $open; $t + $dur <= $close; $t += $step) {
        $start = $t;
        $end   = $t + $dur;
        $overlap = 0;
        foreach ($busy as [$bs, $be]) {
            if ($start < $be && $bs < $end) $overlap++;
        }
        if ($overlap < $seats) {
            $slots[] = sprintf('%02d:%02d', intdiv($t, 60), $t % 60);
        }
    }
    return $slots;
}

/** 空き状況マーク（◯/△/×/休） */
function day_mark(string $date, array $service, array $st): string
{
    if (is_closed($date, $st)) return '休';
    $n = count(available_slots($date, $service, $st));
    if ($n === 0) return '×';
    if ($n <= 4) return '△';
    return '◯';
}

/** 次のN日分の日付配列（'Y-m-d'） */
function next_dates(int $n): array
{
    $out = [];
    for ($i = 0; $i < $n; $i++) {
        $out[] = date('Y-m-d', strtotime("+$i day"));
    }
    return $out;
}

/** 曜日（日本語1文字） */
function weekday_ja(string $date): string
{
    return ['日', '月', '火', '水', '木', '金', '土'][(int) date('w', strtotime($date))];
}
