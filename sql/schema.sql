-- ============================================================
--  予約システム  スキーマ + 初期データ（MySQL 8.0 / utf8mb4）
--  使い方:  mysql -u <user> -p <db> < sql/schema.sql
-- ============================================================
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS bookings;
DROP TABLE IF EXISTS services;
DROP TABLE IF EXISTS settings;
DROP TABLE IF EXISTS admin_users;

SET FOREIGN_KEY_CHECKS = 1;

-- 管理者 -------------------------------------------------------
CREATE TABLE admin_users (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  email         VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  name          VARCHAR(100) NOT NULL,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- メニュー -----------------------------------------------------
CREATE TABLE services (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  name         VARCHAR(100) NOT NULL,
  duration_min INT NOT NULL,
  price        INT NOT NULL,
  description  VARCHAR(255) NOT NULL DEFAULT '',
  is_active    TINYINT(1) NOT NULL DEFAULT 1,
  sort_order   INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 予約 ---------------------------------------------------------
CREATE TABLE bookings (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  code           VARCHAR(20) NOT NULL UNIQUE,
  service_id     INT NOT NULL,
  customer_name  VARCHAR(100) NOT NULL,
  customer_tel   VARCHAR(30)  NOT NULL,
  customer_email VARCHAR(190) NOT NULL DEFAULT '',
  note           VARCHAR(500) NOT NULL DEFAULT '',
  booking_date   DATE NOT NULL,
  start_time     TIME NOT NULL,
  end_time       TIME NOT NULL,
  status         ENUM('confirmed','cancelled','done') NOT NULL DEFAULT 'confirmed',
  created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_date (booking_date, status),
  CONSTRAINT fk_booking_service FOREIGN KEY (service_id) REFERENCES services(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 店舗設定（key/value）---------------------------------------
CREATE TABLE settings (
  k VARCHAR(50) PRIMARY KEY,
  v VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  初期データ
-- ============================================================

-- ログイン: admin@yui-salon.example / yui-admin-2026
INSERT INTO admin_users (email, password_hash, name) VALUES
('admin@yui-salon.example', '$2y$12$5DWD7GvJmLODH2w4sJXbsuC36/HXRwfXdvGeYQgOnWVZppVboA782', '店長');

INSERT INTO services (name, duration_min, price, description, is_active, sort_order) VALUES
('カット',          60,  4400, 'シャンプー・ブロー込み',          1, 1),
('カット＋カラー',  120, 9900, '似合わせカラーとカット',          1, 2),
('パーマ',          120, 8800, 'デジタル／コールド選択可',        1, 3),
('トリートメント',  30,  3300, '髪質改善・サラサラ仕上げ',        1, 4),
('ヘッドスパ',      45,  4400, '頭皮ケア・極上のリラックス',      1, 5);

INSERT INTO settings (k, v) VALUES
('shop_name',      '結 -YUI- Hair & Spa'),
('open_time',      '10:00'),
('close_time',     '19:00'),
('slot_interval',  '30'),
('seats',          '1'),
('closed_days',    '2');   -- 0=日 1=月 ... 6=土 （火曜定休）

-- サンプル予約（常に「今日」基準。火曜は定休なので避けた曜日に入る想定）
INSERT INTO bookings (code, service_id, customer_name, customer_tel, customer_email, note, booking_date, start_time, end_time, status) VALUES
('YUI-AB1001', 1, '佐藤 美咲', '090-1111-0001', '', '',                 DATE_ADD(CURDATE(), INTERVAL 1 DAY), '10:00:00', '11:00:00', 'confirmed'),
('YUI-AB1002', 2, '鈴木 健',   '080-2222-0002', '', 'カラー明るめ希望', DATE_ADD(CURDATE(), INTERVAL 1 DAY), '13:00:00', '15:00:00', 'confirmed'),
('YUI-AB1003', 5, '高橋 由美', '070-3333-0003', '', '',                 DATE_ADD(CURDATE(), INTERVAL 1 DAY), '16:00:00', '16:45:00', 'confirmed'),
('YUI-AB1004', 4, '田中 翔',   '090-4444-0004', '', '',                 DATE_ADD(CURDATE(), INTERVAL 3 DAY), '11:00:00', '11:30:00', 'confirmed'),
('YUI-AB1005', 3, '渡辺 彩',   '080-5555-0005', '', '',                 DATE_ADD(CURDATE(), INTERVAL 3 DAY), '14:00:00', '16:00:00', 'confirmed'),
('YUI-AB1006', 1, '伊藤 大輔', '070-6666-0006', '', '',                 DATE_ADD(CURDATE(), INTERVAL 4 DAY), '10:30:00', '11:30:00', 'confirmed');
