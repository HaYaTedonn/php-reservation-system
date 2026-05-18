<?php
/**
 * 設定ファイルのサンプル。
 * これを config.php としてコピーし、ご自身の環境に合わせて編集してください。
 *   cp includes/config.sample.php includes/config.php
 * （config.php は .gitignore 済み。資格情報はコミットしません）
 */
return [
    'db' => [
        'driver'   => 'mysql',
        'host'     => 'localhost',
        'port'     => 3306,
        'name'     => 'yoyaku',
        'user'     => 'your_db_user',
        'pass'     => 'your_db_password',
        'charset'  => 'utf8mb4',
    ],
    // セッションCookie名（同一ホストで複数アプリを動かす場合に分離）
    'session_name' => 'yui_sess',
    // 予約コードの接頭辞
    'code_prefix'  => 'YUI',
];
