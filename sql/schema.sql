-- Veritabanı kurulum dosyası
-- 'install.php' hangi veritabanına bağlanmışsa oraya yükleyecek

CREATE TABLE IF NOT EXISTS kullanici_auth (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  full_name VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS kisiler (
  id INT AUTO_INCREMENT PRIMARY KEY,
  isim VARCHAR(255) NOT NULL,
  telefon VARCHAR(50),
  ekstra JSON DEFAULT (JSON_OBJECT()),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS tarlalar (
  id INT AUTO_INCREMENT PRIMARY KEY,
  kisi_id INT NOT NULL,
  baslik VARCHAR(255) DEFAULT '',
  alan_decimal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  lokasyon VARCHAR(255),
  ekstra JSON DEFAULT (JSON_OBJECT()),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (kisi_id) REFERENCES kisiler(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS alan_tanimlari (
  id INT AUTO_INCREMENT PRIMARY KEY,
  kapsam ENUM('kisi','tarla','durum') NOT NULL,
  isim VARCHAR(100) NOT NULL,
  label VARCHAR(255) NOT NULL,
  tip ENUM('text','number','select','textarea','checkbox') NOT NULL DEFAULT 'text',
  options JSON DEFAULT NULL,
  required TINYINT(1) DEFAULT 0,
  sort_order INT DEFAULT 0
);

CREATE TABLE IF NOT EXISTS tarla_durum_log (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tarla_id INT NOT NULL,
  durum_key VARCHAR(100) NOT NULL,
  deger VARCHAR(255) DEFAULT NULL,
  notlar TEXT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (tarla_id) REFERENCES tarlalar(id) ON DELETE CASCADE
);