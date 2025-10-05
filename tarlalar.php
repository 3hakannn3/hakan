<?php
require_once __DIR__.'/../src/DB.php'; header('Content-Type: application/json; charset=utf-8');
$pdo = connectDB(); $method = $_SERVER['REQUEST_METHOD'];
if ($method==='GET') {
    if (isset($_GET['id'])){ $stmt=$pdo->prepare('SELECT * FROM tarlalar WHERE id=?'); $stmt->execute([$_GET['id']]); echo json_encode($stmt->fetch()); exit; }
    if (isset($_GET['kisi_id'])){ $stmt=$pdo->prepare('SELECT * FROM tarlalar WHERE kisi_id=?'); $stmt->execute([$_GET['kisi_id']]); echo json_encode($stmt->fetchAll()); exit; }
    $stmt = $pdo->query('SELECT t.*, (SELECT isim FROM kisiler k WHERE k.id=t.kisi_id) AS kisi_adi FROM tarlalar t ORDER BY t.created_at DESC');
    echo json_encode($stmt->fetchAll()); exit;
}
if ($method==='POST') {
    $d=json_decode(file_get_contents('php://input'), true);
    $stmt=$pdo->prepare('INSERT INTO tarlalar (kisi_id, baslik, alan_decimal, lokasyon, ekstra) VALUES (?,?,?,?,?)');
    $stmt->execute([$d['kisi_id'],$d['baslik'],$d['alan_decimal'],$d['lokasyon'] ?? '', json_encode($d['ekstra'] ?? [])]);
    echo json_encode(['ok'=>true,'id'=>$pdo->lastInsertId()]); exit;
}
if ($method==='DELETE') { $id=$_GET['id']??null; if(!$id){http_response_code(400);echo json_encode(['error'=>'id']);exit;} $stmt=$pdo->prepare('DELETE FROM tarlalar WHERE id=?'); $stmt->execute([$id]); echo json_encode(['ok'=>true]); exit; }
if ($method==='PUT'){ $id=$_GET['id']??null; if(!$id){http_response_code(400);echo json_encode(['error'=>'id']);exit;} $d=json_decode(file_get_contents('php://input'), true); $stmt=$pdo->prepare('UPDATE tarlalar SET kisi_id=?, baslik=?, alan_decimal=?, lokasyon=?, ekstra=? WHERE id=?'); $stmt->execute([$d['kisi_id'],$d['baslik'],$d['alan_decimal'],$d['lokasyon'] ?? '', json_encode($d['ekstra'] ?? []), $id]); echo json_encode(['ok'=>true]); exit; }
http_response_code(405);
