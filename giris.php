<?php
session_start();
require_once __DIR__ . '/src/DB.php';
if (isset($_SESSION['kullanici_id'])) {
    header('Location: panel.php'); exit;
}
$err='';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo = connectDB();
    $username = $_POST['username']; $password = $_POST['password'];
    $stmt = $pdo->prepare('SELECT * FROM kullanici_auth WHERE username = ? LIMIT 1'); $stmt->execute([$username]);
    $u = $stmt->fetch();
    if ($u && password_verify($password, $u['password_hash'])) {
        $_SESSION['kullanici_id'] = $u['id']; $_SESSION['kullanici_username'] = $u['username'];
        header('Location: panel.php'); exit;
    } else { $err = 'Hatalı kullanıcı veya şifre'; }
}
?>
<!doctype html><html><head><meta charset='utf-8'><title>Giriş - Tarla Panel</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/style.css"></head><body>
<div class="login-box">
  <h2>Tarla Yönetim Paneli</h2>
  <?php if($err) echo '<div class="hata">'.htmlspecialchars($err).'</div>'; ?>
  <form method="post">
    <input name="username" placeholder="Kullanıcı" required>
    <input name="password" type="password" placeholder="Şifre" required>
    <button type="submit" class="btn">Giriş</button>
  </form>
  <p>Kurulum için <a href="kurulum.php">buraya</a> tıklayın.</p>
</div>
</body></html>
