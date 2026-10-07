<?php
/**
 * WareTrack - Stock In REST API
 * Handles recording inward stock movements in MySQL
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/functions.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $search = $_GET['search'] ?? '';
    $transactions = getStockTransactions('IN', 50, $search);
    echo json_encode([
        'success'      => true,
        'count'        => count($transactions),
        'transactions' => $transactions
    ]);
    exit;
}

if ($method === 'POST') {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true) ?: $_POST;

    $productId  = trim($data['product_id'] ?? '');
    $quantity   = (int)($data['quantity'] ?? 0);
    $supplierId = !empty($data['supplier_id']) ? trim($data['supplier_id']) : null;
    $refNo      = trim($data['reference_no'] ?? '');
    $unitPrice  = isset($data['unit_price']) ? (float)$data['unit_price'] : null;
    $notes      = trim($data['notes'] ?? '');

    if (empty($productId)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Product SKU is required.']);
        exit;
    }

    if ($quantity <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Inward quantity must be greater than zero.']);
        exit;
    }

    $res = processStockIn($productId, $quantity, $supplierId, $refNo, $unitPrice, $notes);
    if ($res['success']) {
        echo json_encode([
            'success'   => true,
            'message'   => "Successfully received {$quantity} units of {$res['product']}.",
            'newQty'    => $res['newQty'],
            'reference' => $res['ref']
        ]);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $res['error']]);
    }
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'error' => 'Method not allowed']);
