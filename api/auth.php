<?php
session_start();
require_once __DIR__.'/../src/DB.php';
$method = $_SERVER['REQUEST_METHOD'];
header('Content-Type: application/json; charset=utf-8');
if ($method === 'DELETE') { session_destroy(); echo json_encode(['ok'=>true]); exit; }
echo json_encode(['ok'=>true]);