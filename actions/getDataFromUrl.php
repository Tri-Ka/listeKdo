<?php
include '../config.php';

require_once __DIR__ . '/OpenGraph.php';

$productUrl = isset($_GET['productUrl']) ? trim((string) $_GET['productUrl']) : '';
if ('' === $productUrl) {
    http_response_code(400);
    echo json_encode(array());
    exit;
}

$og = new OpenGraph();
$datas = $og->fetch($productUrl);
$payload = array();

if ($datas instanceof OpenGraph) {
    $payload = iterator_to_array($datas, true);
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
exit;
