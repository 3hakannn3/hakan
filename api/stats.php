<?php
require_once __DIR__.'/../src/DB.php'; header('Content-Type: application/json; charset=utf-8');
$pdo = connectDB();
$kisiler = $pdo->query('SELECT COUNT(*) FROM kisiler')->fetchColumn();
$tarlalar = $pdo->query('SELECT COUNT(*) FROM tarlalar')->fetchColumn();
$ekili = $pdo->query('SELECT COALESCE(SUM(alan_decimal),0) FROM tarlalar')->fetchColumn();
$bos = 0; // placeholder - can compute based on some logic
echo json_encode(['kisiler'=>intval($kisiler),'tarlalar'=>intval($tarlalar),'ekili_alan'=>floatval($ekili),'bos_alan'=>floatval($bos)]);