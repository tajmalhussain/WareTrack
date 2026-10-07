<?php
/**
 * WareTrack - Delete Product API Endpoint
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/functions.php';

$method = $_SERVER['REQUEST_METHOD'];
if ($method !== 'POST' && $method !== 'DELETE') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed. Use POST or DELETE.']);
    exit;
}

$pdo = getDBConnection();
if (!$pdo) {
    http_response_code(503);
    echo json_encode(['success' => false, 'error' => 'Database connection unavailable']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}
if (empty($input['id']) && !empty($_GET['id'])) {
    $input['id'] = $_GET['id'];
}

$id = trim($input['id'] ?? '');

if (empty($id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Product ID is required']);
    exit;
}

try {
    $product = getProductById($id);
    if (!$product) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => "Product '$id' not found"]);
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM `products` WHERE `id` = :id");
    $stmt->execute([':id' => $id]);

    logActivity($id, 'DELETE', "Product '{$product['name']}' ($id) was deleted from inventory.");

    echo json_encode([
        'success' => true,
        'message' => "Product '{$product['name']}' was successfully deleted.",
        'deleted_id' => $id,
        'stats' => getInventoryStats()
    ]);
    exit;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}
