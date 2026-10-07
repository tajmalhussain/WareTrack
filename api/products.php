<?php
/**
 * WareTrack - Products REST API Endpoint
 * GET: Retrieve list of products with filtering
 * POST: Create a new inventory product
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/functions.php';

$method = $_SERVER['REQUEST_METHOD'];
$pdo = getDBConnection();

if (!$pdo) {
    http_response_code(503);
    echo json_encode([
        'success' => false,
        'error' => 'Database connection unavailable'
    ]);
    exit;
}

if ($method === 'GET') {
    $category = $_GET['category'] ?? 'All';
    $search = $_GET['search'] ?? '';
    $status = $_GET['status'] ?? 'All';

    $products = getProductsList($category, $search, $status);
    $stats = getInventoryStats();

    echo json_encode([
        'success' => true,
        'count' => count($products),
        'products' => $products,
        'stats' => $stats
    ]);
    exit;
}

if ($method === 'POST') {
    // Read JSON body or POST form data
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }

    $id = trim($input['id'] ?? '');
    $name = trim($input['name'] ?? '');
    $category = trim($input['category'] ?? 'General');
    $quantity = isset($input['quantity']) ? (int)$input['quantity'] : 0;
    $minStock = isset($input['min_stock']) ? (int)$input['min_stock'] : (isset($input['minStock']) ? (int)$input['minStock'] : 10);
    $price = isset($input['price']) ? (float)$input['price'] : 0.00;
    $location = trim($input['location'] ?? 'General Storage');
    $icon = trim($input['icon'] ?? '📦');

    if (empty($id)) {
        $id = generateNextSku();
    }

    if (empty($name)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Product name is required'
        ]);
        exit;
    }

    // Check if ID already exists
    $existing = getProductById($id);
    if ($existing) {
        http_response_code(409);
        echo json_encode([
            'success' => false,
            'error' => "Product SKU '$id' already exists in inventory."
        ]);
        exit;
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO `products` (`id`, `name`, `category`, `quantity`, `min_stock`, `price`, `location`, `icon`)
            VALUES (:id, :name, :category, :quantity, :min_stock, :price, :location, :icon)
        ");
        $stmt->execute([
            ':id'        => $id,
            ':name'      => $name,
            ':category'  => $category,
            ':quantity'  => $quantity,
            ':min_stock' => $minStock,
            ':price'     => $price,
            ':location'  => $location,
            ':icon'      => $icon
        ]);

        logActivity($id, 'CREATE', "Product '$name' cataloged with initial stock $quantity.");

        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => "Product '$name' registered successfully.",
            'product' => getProductById($id)
        ]);
        exit;
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to save product: ' . $e->getMessage()
        ]);
        exit;
    }
}

http_response_code(405);
echo json_encode(['success' => false, 'error' => 'Method not allowed']);
