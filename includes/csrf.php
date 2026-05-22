<?php
declare(strict_types=1);

/**
 * CSRF対策。セッションごとにトークンを発行し、POST時に検証する。
 */

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

/** フォームに埋め込む hidden input */
function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

/** POSTトークンを検証。失敗時は 419 で停止 */
function csrf_check(): void
{
    $sent = $_POST['_csrf'] ?? '';
    $real = $_SESSION['_csrf'] ?? '';
    // 空トークン同士が一致してしまうのを防ぐため、サーバ側トークンの存在も必須にする
    if ($real === '' || !is_string($sent) || !hash_equals($real, $sent)) {
        http_response_code(419);
        exit('不正なリクエストです（CSRFトークン不一致）。前のページに戻ってやり直してください。');
    }
}
