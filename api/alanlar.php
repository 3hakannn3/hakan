<?php
require_once __DIR__.'/../src/DB.php'; header('Content-Type: application/json; charset=utf-8');
$pdo = connectDB(); $method = $_SERVER['REQUEST_METHOD'];
if ($method==='GET') {
    if (isset($_GET['scope'])){ $scope = $_GET['scope']; $stmt=$pdo->prepare('SELECT * FROM alan_tanimlari WHERE kapsam=? ORDER BY sort_order'); $stmt->execute([$scope]); echo json_encode($stmt->fetchAll()); exit; }
    echo json_encode([]); exit;
}
if ($method==='POST') {
    $d = json_decode(file_get_contents('php://input'), true);
    $opts = $d['options'] ? json_encode(array_map('trim', explode(',', $d['options']))) : null;
    $stmt=$pdo->prepare('INSERT INTO alan_tanimlari (kapsam, isim, label, tip, options, required) VALUES (?,?,?,?,?,?)');
    $stmt->execute([$d['kapsam'] ?? 'kisi', preg_replace('/\s+/','_',strtolower($d['label'])), $d['label'], $d['tip'], $opts, 0]);
    echo json_encode(['ok'=>true,'id'=>$pdo->lastInsertId()]); exit;
}
if ($method==='DELETE'){ $id=$_GET['id']??null; if(!$id){http_response_code(400);echo json_encode(['error'=>'id']);exit;} $stmt=$pdo->prepare('DELETE FROM alan_tanimlari WHERE id=?'); $stmt->execute([$id]); echo json_encode(['ok'=>true]); exit; }
http_response_code(405);
