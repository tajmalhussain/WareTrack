<?php
/**
 * WareTrack - Restock API Endpoint
 * Handles individual quick restock (+20/+25) and batch restocking of low-stock items.
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed. Use POST.']);
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

$isBatch = !empty($input['batch']) || (!empty($input['action']) && $input['action'] === 'batch');

if ($isBatch) {
    $amount = isset($input['amount']) ? (int)$input['amount'] : 30;

    try {
        // Find how many items are low stock
        $stmtFind = $pdo->query("SELECT `id`, `name`, `quantity`, `min_stock` FROM `products` WHERE `quantity` <= `min_stock`");
        $lowItems = $stmtFind->fetchAll();

        if (empty($lowItems)) {
            echo json_encode([
                'success' => true,
                'message' => 'All items currently have healthy stock levels. No restock needed.',
                'affected_rows' => 0,
                'stats' => getInventoryStats()
            ]);
            exit;
        }

        $stmt = $pdo->prepare("UPDATE `products` SET `quantity` = `quantity` + :amount WHERE `quantity` <= `min_stock`");
        $stmt->execute([':amount' => $amount]);
        $affected = $stmt->rowCount();

        logActivity('SYSTEM', 'BATCH_RESTOCK', "Batch restocked $affected low-stock products by +$amount units each.");

        echo json_encode([
            'success' => true,
            'message' => "Successfully replenished stock for $affected items (+$amount each).",
            'affected_rows' => $affected,
            'stats' => getInventoryStats()
        ]);
        exit;
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

// Individual restock
$id = trim($input['id'] ?? ($input['product_id'] ?? ''));
$amount = isset($input['amount']) ? (int)$input['amount'] : 20;

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

    $stmt = $pdo->prepare("UPDATE `products` SET `quantity` = `quantity` + :amount WHERE `id` = :id");
    $stmt->execute([':amount' => $amount, ':id' => $id]);

    $updatedProduct = getProductById($id);
    logActivity($id, 'RESTOCK', "Quick restocked by +$amount units. New quantity: {$updatedProduct['quantity']}.");

    echo json_encode([
        'success' => true,
        'message' => "Added +$amount units to {$product['name']}.",
        'product' => $updatedProduct,
        'stats' => getInventoryStats()
    ]);
    exit;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}
