<?php
declare(strict_types=1);

/**
 * 共通ブートストラップ。全エントリポイントの先頭で読み込む。
 * セッション（安全なCookie属性）・DB・ヘルパ・設定をまとめて初期化する。
 */

error_reporting(E_ALL);
ini_set('display_errors', '0'); // 本番では画面に出さない（ログへ）

require __DIR__ . '/db.php';
require __DIR__ . '/functions.php';

$GLOBALS['app_config'] = require __DIR__ . '/config.php';

// --- セッション（HttpOnly / SameSite / 本番はSecure）---
$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
session_name($GLOBALS['app_config']['session_name'] ?? 'app_sess');
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'httponly' => true,
    'secure'   => $secure,
    'samesite' => 'Lax',
]);
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require __DIR__ . '/csrf.php';
require __DIR__ . '/auth.php';

/** 店舗設定をまとめて取得（settingsテーブル → 連想配列） */
function settings(): array
{
    static $cache = null;
    if ($cache !== null) return $cache;
    $cache = [];
    foreach (DB::run('SELECT k, v FROM settings')->fetchAll() as $row) {
        $cache[$row['k']] = $row['v'];
    }
    return $cache;
}
