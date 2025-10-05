<?php
require_once __DIR__.'/../src/DB.php'; header('Content-Type: application/json; charset=utf-8');
$pdo = connectDB(); $method = $_SERVER['REQUEST_METHOD'];
if ($method==='GET') {
    // alan_id param -> liste tarlalar + son durum
    $alan = $_GET['alan_id'] ?? null;
    if (!$alan){ echo json_encode([]); exit; }
    $stmt = $pdo->query("SELECT t.id as tarla_id, t.baslik as tarla_baslik, (SELECT deger FROM tarla_durum_log l WHERE l.tarla_id=t.id AND l.durum_key=(SELECT isim FROM alan_tanimlari WHERE id=$alan) ORDER BY l.created_at DESC LIMIT 1) AS deger FROM tarlalar t");
    echo json_encode($stmt->fetchAll()); exit;
}
if ($method==='POST') {
    $d = json_decode(file_get_contents('php://input'), true);
    // d: alan_id, checked: [tarla ids]
    $alan = $d['alan_id']; $checked = $d['checked'] ?? [];
    $stmtName = $pdo->prepare('SELECT isim FROM alan_tanimlari WHERE id=?'); $stmtName->execute([$alan]); $isim = $stmtName->fetchColumn();
    // basit: tüm tarlalar için log ekle; checked ise deger=1
    $all = $pdo->query('SELECT id FROM tarlalar')->fetchAll(PDO::FETCH_COLUMN);
    $ins = $pdo->prepare('INSERT INTO tarla_durum_log (tarla_id, durum_key, deger) VALUES (?,?,?)');
    foreach($all as $tid){ $val = in_array($tid, $checked) ? '1' : ''; $ins->execute([$tid, $isim, $val]); }
    echo json_encode(['ok'=>true]); exit;
}
http_response_code(405);
