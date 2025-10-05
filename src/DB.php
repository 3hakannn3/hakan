<?php
function getConfig() {
    if (!file_exists(__DIR__ . '/../config.php')) {
        die('config.php bulunamadı. kurulum çalıştırın: kurulum.php');
    }
    return require __DIR__ . '/../config.php';
}
function connectDB(){
    $cfg = getConfig();
    $db = $cfg['db'];
    $dsn = "mysql:host={$db['host']};dbname={$db['name']};charset={$db['charset']}";
    $pdo = new PDO($dsn, $db['user'], $db['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    return $pdo;
}
?>