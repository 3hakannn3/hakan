<?php
// Kurulum scripti - SQL dosyasını çalıştırır ve gerekli DB'yi oluşturur.
// Güvenlik: bu scripti kurulum tamamlandıktan sonra silin veya erişimi kısıtlayın.

if (php_sapi_name() === 'cli') {
    echo "Bu script web üzerinden çalıştırılmalıdır.\n";
    exit;
}

function render_form($msg='') {
    echo '<!doctype html><html><head><meta charset="utf-8"><title>Kurulum</title></head><body>';
    if($msg) echo '<div style="padding:8px;background:#fee;border:1px solid #fbb;margin-bottom:12px;">'.htmlspecialchars($msg).'</div>';
    echo '<h2>Veritabanı Ayarları</h2>';
    echo '<form method="post">';
    echo 'Host: <input name="host" value="'.htmlspecialchars($_POST["host"] ?? "127.0.0.1").'"><br>';
    echo 'Kullanıcı: <input name="user" value="'.htmlspecialchars($_POST["user"] ?? "root").'"><br>';
    echo 'Parola: <input name="pass" type="password" value="'.htmlspecialchars($_POST["pass"] ?? "").'"><br>';
    echo 'Veritabanı adı: <input name="name" value="'.htmlspecialchars($_POST["name"] ?? "tarla_panel").'"><br><br>';
    echo '<button type="submit" name="run">Kur</button>';
    echo '</form>';
    echo '</body></html>';
    exit;
}

if(!isset($_POST['run'])) {
    render_form();
}

$host = $_POST['host'] ?? '127.0.0.1';
$user = $_POST['user'] ?? 'root';
$pass = $_POST['pass'] ?? '';
$dbname = $_POST['name'] ?? 'tarla_panel';

try {
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
} catch (Exception $e) {
    render_form('MySQL bağlantısı kurulamadı: ' . $e->getMessage());
}

// create database if not exists
try {
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbname}` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
} catch (Exception $e) {
    render_form('Veritabanı oluşturulamadı: ' . $e->getMessage());
}

// now connect to the created database
try {
    $pdo = new PDO("mysql:host=$host;dbname={$dbname};charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
} catch (Exception $e) {
    render_form('Veritabanına bağlanılamadı: ' . $e->getMessage());
}

$sqlFile = __DIR__ . '/sql/schema.sql';
if(!file_exists($sqlFile)) {
    render_form('schema.sql bulunamadı: ' . $sqlFile);
}

$sql = file_get_contents($sqlFile);
// split statements by semicolon followed by newline - basic splitter
$statements = preg_split('/;\\s*\\n/', $sql);
$executed = 0;
$errors = [];
foreach($statements as $stmt) {
    $stmt = trim($stmt);
    if(!$stmt) continue;
    try {
        $pdo->exec($stmt);
        $executed++;
    } catch (Exception $e) {
        // collect error but continue
        $errors[] = $e->getMessage() . " -- STATEMENT: " . substr($stmt,0,200);
    }
}

// write config.php if not exists
$configPath = __DIR__ . '/config.php';
if(!file_exists($configPath)) {
    $cfg = "<?php\nreturn [\n  'db' => [\n    'host' => '".addslashes($host)."',\n    'name' => '".addslashes($dbname)."',\n    'user' => '".addslashes($user)."',\n    'pass' => '".addslashes($pass)."',\n    'charset' => 'utf8mb4',\n  ],\n];\n";
    file_put_contents($configPath, $cfg);
    $cfg_written = true;
} else {
    $cfg_written = false;
}

echo '<!doctype html><html><head><meta charset="utf-8"><title>Kurulum Sonucu</title></head><body>';
echo "<h2>Kurulum tamamlandı</h2>";
echo "<p>Çalıştırılan ifadeler: {$executed}</p>";
if($errors) {
    echo '<h3>Hatalar (devam eden işlemler için önemli olabilir)</h3><pre>'.htmlspecialchars(implode("\n\n", $errors)).'</pre>';
}
if($cfg_written) {
    echo "<p>config.php oluşturuldu.</p>";
} else {
    echo "<p>config.php zaten mevcut - üzerinde değişiklik yapılmadı.</p>";
}
echo "<p>Kurulum tamamlandıktan sonra güvenlik için <b>kurulum.php</b> dosyasını silin veya erişimi kısıtlayın.</p>";
echo '<p><a href="giris.php">Giriş sayfasına git</a></p>';
echo '</body></html>';
