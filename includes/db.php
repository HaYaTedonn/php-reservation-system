<?php
declare(strict_types=1);

/**
 * PDO データベース接続（シングルトン）。
 * 例外モード・連想配列フェッチ・エミュレーション無効で、安全な既定値に。
 */
final class DB
{
    private static ?PDO $pdo = null;

    public static function conn(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $cfg = require __DIR__ . '/config.php';
        $d   = $cfg['db'];

        $dsn = sprintf(
            '%s:host=%s;port=%d;dbname=%s;charset=%s',
            $d['driver'], $d['host'], $d['port'], $d['name'], $d['charset']
        );

        self::$pdo = new PDO($dsn, $d['user'], $d['pass'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);

        return self::$pdo;
    }

    /** プリペアド実行のショートカット */
    public static function run(string $sql, array $params = []): PDOStatement
    {
        $stmt = self::conn()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
}
