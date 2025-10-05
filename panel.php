<?php
session_start();
require_once __DIR__ . '/src/DB.php';
if (!isset($_SESSION['kullanici_id'])) { header('Location: giris.php'); exit; }
?>
<!doctype html><html><head><meta charset='utf-8'><title>Panel - Tarla</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/style.css">
</head><body>
<div class="app-root">
  <aside class="sidebar">
    <div class="logo">Tarla Panel</div>
    <ul class="nav">
      <li><a href="#dashboard" data-key="dashboard">Gösterge Paneli</a></li>
      <li><a href="#kisiler" data-key="kisiler">Kişiler</a></li>
      <li><a href="#tarlalar" data-key="tarlalar">Tarlalar</a></li>
      <li><a href="#durumlar" data-key="durumlar">Durumlar</a></li>
      <li><a href="#ayarlar" data-key="ayarlar">Ayarlar ▾</a>
        <ul class="sub">
          <li><a href="#alanlar_kisi" data-key="alanlar_kisi">Kişi Alanları</a></li>
          <li><a href="#alanlar_tarla" data-key="alanlar_tarla">Tarla Alanları</a></li>
          <li><a href="#alanlar_durum" data-key="alanlar_durum">Tarla Durum Alanları</a></li>
        </ul>
      </li>
    </ul>
    <div class="sidebar-foot"><a href="cikis.php" class="btn small">Çıkış</a></div>
  </aside>
  <main class="main">
    <header class="topbar"><h1 id="page-title">Gösterge Paneli</h1></header>
    <section id="content-area"></section>
  </main>
</div>
<div id="modal" class="modal"><div class="modal-box"></div></div>
<div class="toast" id="toast"></div>
<script src="assets/app.js"></script>
</body></html>
