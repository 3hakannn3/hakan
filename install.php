<?php
// install.php — Tarla Yönetim Paneli Kurulumu
// PHP 8.3 uyumlu - Türkçe arayüz - Admin hesabı oluşturma dahildir.

function e($s){ return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

$projectRoot = __DIR__;
$configPath = $projectRoot . DIRECTORY_SEPARATOR . 'config.php';
$sqlPath = $projectRoot . '/sql/schema.sql';

function print_step($num, $title, $ok, $msg=''){
    $cls = $ok ? 'ok' : 'fail';
    echo "<div class='step {$cls}'><div class='step-num'>{$num}</div><div class='step-body'>";
    echo "<div class='step-title'>".e($title)."</div>";
    if($msg) echo "<div class='step-msg'>".nl2br(e($msg))."</div>";
    echo "</div></div>";
    flush(); @ob_flush();
}

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    @set_time_limit(0);

    $db_host = trim($_POST['db_host']);
    $db_user = trim($_POST['db_user']);
    $db_pass = $_POST['db_pass'] ?? '';
    $db_name = trim($_POST['db_name']);
    $db_prefix = preg_replace('/[^a-z0-9_]/i', '', ($_POST['db_prefix'] ?? ''));
    $admin_user = trim($_POST['admin_user']);
    $admin_pass = $_POST['admin_pass'] ?? '';

    ?>
    <!doctype html>
    <html><head><meta charset="utf-8"><title>Kurulum — Devam Ediyor</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <style>
    body{font-family:system-ui; background:#f4f7fb; margin:0; padding:24px;}
    .wrap{max-width:920px;margin:0 auto;}
    .card{background:white;border-radius:12px;box-shadow:0 6px 18px rgba(15,23,42,0.08);padding:24px;}
    .step{display:flex;gap:12px;padding:10px;border-radius:8px;margin-bottom:10px;border:1px solid rgba(0,0,0,0.05);}
    .step-num{width:36px;height:36px;border-radius:8px;background:linear-gradient(135deg,#2563eb,#1e40af);color:white;display:flex;align-items:center;justify-content:center;font-weight:700;}
    .step.ok{border-left:4px solid #10b981;}
    .step.fail{border-left:4px solid #ef4444;}
    .step-title{font-weight:600;}
    .step-msg{color:#6b7280;font-size:13px;margin-top:4px;}
    .btn{background:#2563eb;color:white;padding:10px 16px;border:none;border-radius:10px;font-weight:600;cursor:pointer;}
    </style></head><body>
    <div class="wrap"><div class="card">
    <h1>Kurulum İşlemde</h1>
    <div class="steps">
    <?php

    // 1️⃣ Veritabanına bağlan
    print_step(1, 'Veritabanına bağlanılıyor...', false);
    $mysqli = @new mysqli($db_host, $db_user, $db_pass);
    if($mysqli->connect_errno){
        print_step(1, 'Bağlantı başarısız', false, $mysqli->connect_error);
        exit;
    } else {
        print_step(1, 'Veritabanına bağlandı', true, "Sunucu: {$db_host}");
    }

    // 2️⃣ Veritabanını seç veya oluştur
    if(!$mysqli->select_db($db_name)){
        $create = "CREATE DATABASE `{$db_name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
        if($mysqli->query($create)){
            print_step(2, 'Veritabanı oluşturuldu', true);
            $mysqli->select_db($db_name);
        } else {
            print_step(2, 'Veritabanı oluşturulamadı', false, $mysqli->error);
            exit;
        }
    } else {
        print_step(2, 'Veritabanı seçildi', true);
    }

    // 3️⃣ SQL import
    if(file_exists($sqlPath)){
        $sql = file_get_contents($sqlPath);
        if($mysqli->multi_query($sql)){
            do { if($res = $mysqli->store_result()){ $res->free(); } } while($mysqli->more_results() && $mysqli->next_result());
            print_step(3, 'SQL içe aktarıldı', true, basename($sqlPath));
        } else {
            print_step(3, 'SQL içe aktarılamadı', false, $mysqli->error);
        }
    } else {
        print_step(3, 'SQL dosyası bulunamadı', false, 'sql/schema.sql dosyası eksik.');
    }

    // 4️⃣ config.php oluştur
    $config = "<?php\nreturn [\n    'db' => [\n        'host' => ".var_export($db_host,true).",\n        'user' => ".var_export($db_user,true).",\n        'pass' => ".var_export($db_pass,true).",\n        'name' => ".var_export($db_name,true).",\n        'prefix' => ".var_export($db_prefix,true).",\n        'charset' => 'utf8mb4',\n    ],\n];\n";

    if(file_exists($configPath)){
        $bak = $configPath.'.bak.'.date('YmdHis');
        @copy($configPath,$bak);
        print_step(4, 'config.php yedeklendi', true, "Yedek: ".basename($bak));
    }
    if(file_put_contents($configPath, $config)){
        print_step(4, 'config.php oluşturuldu', true);
    } else {
        print_step(4, 'config.php oluşturulamadı', false, 'Yazma iznini kontrol edin.');
    }

    // 5️⃣ Admin kullanıcı oluştur
    $checkUser = $mysqli->query("SELECT id FROM kullanici_auth WHERE username='{$admin_user}' LIMIT 1");
    if($checkUser && $checkUser->num_rows === 0){
        $hash = password_hash($admin_pass, PASSWORD_DEFAULT);
        $stmt = $mysqli->prepare("INSERT INTO kullanici_auth (username, password_hash, full_name) VALUES (?, ?, 'Yönetici')");
        $stmt->bind_param('ss', $admin_user, $hash);
        if($stmt->execute()){
            print_step(5, 'Admin kullanıcı oluşturuldu', true, "Kullanıcı: {$admin_user}");
        } else {
            print_step(5, 'Admin oluşturulamadı', false, $stmt->error);
        }
    } else {
        print_step(5, 'Admin zaten mevcut', true);
    }

    $mysqli->close();
    ?>
    </div>
    <p>Kurulum tamamlandı. Lütfen <b>install.php</b> dosyasını silin veya erişimi kapatın.</p>
    <a href="./" class="btn">Site Ana Sayfasına Git</a>
    </div></div></body></html>
    <?php
    exit;
}
?>

<!doctype html>
<html><head><meta charset="utf-8"><title>Tarla Yönetim Paneli — Kurulum</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<style>
body{font-family:system-ui; background:#eef2f7; margin:0; padding:24px;}
.wrap{max-width:600px;margin:40px auto;}
.card{background:white;padding:24px;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,0.06);}
input{width:100%;padding:10px;margin-top:4px;margin-bottom:12px;border:1px solid #ccc;border-radius:8px;}
.btn{background:#2563eb;color:white;border:none;padding:10px 16px;border-radius:10px;cursor:pointer;font-weight:600;}
</style></head><body>
<div class="wrap"><div class="card">
  <h1>Tarla Yönetim Paneli — Kurulum</h1>
  <form method="post">
    <label>Veritabanı Sunucusu:</label>
    <input type="text" name="db_host" value="localhost" required>
    <label>Kullanıcı Adı:</label>
    <input type="text" name="db_user" value="root" required>
    <label>Şifre:</label>
    <input type="password" name="db_pass">
    <label>Veritabanı Adı:</label>
    <input type="text" name="db_name" required>
    <label>Tablo Öneki (isteğe bağlı):</label>
    <input type="text" name="db_prefix">
    <hr>
    <label>Admin Kullanıcı Adı:</label>
    <input type="text" name="admin_user" value="admin" required>
    <label>Admin Şifre:</label>
    <input type="password" name="admin_pass" required>
    <button class="btn">Kurulumu Başlat</button>
  </form>
</div></div>
</body></html>
