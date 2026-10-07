<?php
/**
 * WareTrack - Stock Out REST API
 * Handles recording outward stock dispatches in MySQL with stock validation
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/functions.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $search = $_GET['search'] ?? '';
    $transactions = getStockTransactions('OUT', 50, $search);
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
    $reason     = trim($data['reason'] ?? 'Customer Order');
    $recipient  = trim($data['recipient'] ?? '');
    $refNo      = trim($data['reference_no'] ?? '');
    $notes      = trim($data['notes'] ?? '');

    if (empty($productId)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Product SKU is required.']);
        exit;
    }

    if ($quantity <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Outward quantity must be greater than zero.']);
        exit;
    }

    $res = processStockOut($productId, $quantity, $reason, $recipient, $refNo, $notes);
    if ($res['success']) {
        echo json_encode([
            'success'   => true,
            'message'   => "Successfully dispatched {$quantity} units of {$res['product']}.",
            'newQty'    => $res['newQty'],
            'reference' => $res['ref'],
            'isLow'     => $res['isLow']
        ]);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $res['error']]);
    }
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'error' => 'Method not allowed']);
