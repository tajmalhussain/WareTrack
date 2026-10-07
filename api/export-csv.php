<?php
/**
 * WareTrack - Dynamic MySQL CSV Export Endpoint
 */

require_once __DIR__ . '/../includes/functions.php';

$pdo = getDBConnection();

$filename = 'WareTrack_Inventory_' . date('Y-m-d_His') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');

// UTF-8 BOM for Excel compatibility
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Header row
fputcsv($output, ['Product ID', 'Product Name', 'Category', 'Quantity', 'Min Stock', 'Price (INR)', 'Warehouse Location', 'Last Updated']);

if ($pdo) {
    try {
        $stmt = $pdo->query("SELECT `id`, `name`, `category`, `quantity`, `min_stock`, `price`, `location`, `updated_at` FROM `products` ORDER BY `category`, `name`");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, [
                $row['id'],
                $row['name'],
                $row['category'],
                $row['quantity'],
                $row['min_stock'],
                $row['price'],
                $row['location'],
                $row['updated_at']
            ]);
        }
    } catch (Exception $e) {
        fputcsv($output, ['Error fetching database records: ' . $e->getMessage()]);
    }
} else {
    fputcsv($output, ['Database offline. Could not stream records.']);
}

fclose($output);
exit;
