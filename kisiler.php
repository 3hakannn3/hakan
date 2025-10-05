<?php
require_once __DIR__.'/../src/DB.php'; header('Content-Type: application/json; charset=utf-8');
$pdo = connectDB();
$method = $_SERVER['REQUEST_METHOD'];
if ($method==='GET') {
    if (isset($_GET['id'])){
        $stmt = $pdo->prepare('SELECT * FROM kisiler WHERE id=?'); $stmt->execute([$_GET['id']]); echo json_encode($stmt->fetch()); exit;
    }
    $stmt = $pdo->query('SELECT k.*, (SELECT COUNT(*) FROM tarlalar t WHERE t.kisi_id = k.id) AS tarla_sayisi, (SELECT COALESCE(SUM(alan_decimal),0) FROM tarlalar t WHERE t.kisi_id = k.id) AS toplam_alan FROM kisiler k ORDER BY k.isim');
    echo json_encode($stmt->fetchAll()); exit;
}
if ($method==='POST') {
    $d = json_decode(file_get_contents('php://input'), true);
    $stmt = $pdo->prepare('INSERT INTO kisiler (isim, telefon, ekstra) VALUES (?,?,?)');
    $stmt->execute([$d['isim'],$d['telefon'], json_encode($d['ekstra'] ?? [])]);
    echo json_encode(['ok'=>true,'id'=>$pdo->lastInsertId()]); exit;
}
if ($method==='DELETE') {
    $id = $_GET['id'] ?? null; if(!$id){ http_response_code(400); echo json_encode(['error'=>'id gerekli']); exit; }
    $stmt = $pdo->prepare('DELETE FROM kisiler WHERE id=?'); $stmt->execute([$id]); echo json_encode(['ok'=>true]); exit;
}
if ($method==='PUT') {
    $id = $_GET['id'] ?? null; if(!$id){ http_response_code(400); echo json_encode(['error'=>'id gerekli']); exit; }
    $d = json_decode(file_get_contents('php://input'), true);
    $stmt = $pdo->prepare('UPDATE kisiler SET isim=?, telefon=?, ekstra=? WHERE id=?');
    $stmt->execute([$d['isim'],$d['telefon'], json_encode($d['ekstra'] ?? []), $id]);
    echo json_encode(['ok'=>true]); exit;
}
http_response_code(405);
