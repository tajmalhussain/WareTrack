<?php
/**
 * WareTrack - Suppliers REST API
 * Handles GET, POST, and DELETE for suppliers in MySQL
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/functions.php';

$method = $_SERVER['REQUEST_METHOD'];

// GET: List or single supplier
if ($method === 'GET') {
    if (isset($_GET['id'])) {
        $supplier = getSupplierById($_GET['id']);
        if ($supplier) {
            echo json_encode(['success' => true, 'supplier' => $supplier]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Supplier not found']);
        }
        exit;
    }

    $category = $_GET['category'] ?? 'All';
    $status   = $_GET['status'] ?? 'All';
    $search   = $_GET['search'] ?? '';

    $list = getSuppliersList($category, $status, $search);
    echo json_encode([
        'success'   => true,
        'count'     => count($list),
        'suppliers' => $list
    ]);
    exit;
}

// POST: Add new supplier or update
if ($method === 'POST') {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true) ?: $_POST;

    if (empty($data['name'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Company Name is required.']);
        exit;
    }

    $res = createSupplier($data);
    if ($res['success']) {
        echo json_encode(['success' => true, 'id' => $res['id'], 'message' => 'Supplier successfully registered in MySQL.']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $res['error']]);
    }
    exit;
}

// DELETE: Delete supplier
if ($method === 'DELETE' || ($method === 'POST' && isset($_POST['_method']) && $_POST['_method'] === 'DELETE')) {
    $id = $_GET['id'] ?? null;
    if (!$id) {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        $id = $data['id'] ?? null;
    }

    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Supplier ID is required.']);
        exit;
    }

    $res = deleteSupplier($id);
    if ($res['success']) {
        echo json_encode(['success' => true, 'message' => "Supplier {$id} deleted from MySQL."]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $res['error']]);
    }
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'error' => 'Method not allowed']);
