<?php
/**
 * WareTrack - Dynamic Reports CSV Export Stream
 * Direct MySQL stream to .csv format for transactions and inventory
 */

require_once __DIR__ . '/../includes/functions.php';

$type = $_GET['type'] ?? 'movements';
$filename = 'waretrack_' . $type . '_' . date('Y-m-d_His') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');
// Add UTF-8 BOM for Microsoft Excel compatibility
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

if ($type === 'valuation') {
    // Inventory Valuation CSV
    fputcsv($output, ['Category', 'Item Count', 'Total Stock Units', 'Low Stock Items', 'Category Value (INR)']);
    $categories = getCategoryValuationReport();
    foreach ($categories as $c) {
        fputcsv($output, [
            $c['category'],
            $c['item_count'],
            $c['total_units'],
            $c['low_stock_items'],
            number_format((float)$c['category_value'], 2, '.', '')
        ]);
    }
} elseif ($type === 'low_stock') {
    // Critical Reorder CSV
    fputcsv($output, ['SKU', 'Product Name', 'Category', 'Current Qty', 'Min Threshold', 'Recommended Reorder', 'Estimated Cost (INR)', 'Supplier']);
    $lowItems = getLowStockReport();
    foreach ($lowItems as $item) {
        fputcsv($output, [
            $item['id'],
            $item['name'],
            $item['category'],
            $item['quantity'],
            $item['min_stock'],
            $item['recommended_reorder'],
            number_format((float)$item['estimated_reorder_cost'], 2, '.', ''),
            $item['supplier_name'] ?: 'None'
        ]);
    }
} else {
    // Stock Movements Ledger CSV (Default)
    fputcsv($output, ['ID', 'Type', 'Reference No', 'SKU', 'Product Name', 'Supplier / Recipient', 'Quantity', 'Unit Price (INR)', 'Reason', 'Timestamp', 'Notes']);
    $transactions = getStockTransactions('All', 1000, '');
    foreach ($transactions as $t) {
        $partner = ($t['transaction_type'] === 'IN') ? ($t['supplier_name'] ?: 'General Vendor') : ($t['recipient'] ?: 'Direct Dispatch');
        fputcsv($output, [
            $t['id'],
            $t['transaction_type'],
            $t['reference_no'],
            $t['product_id'],
            $t['product_name'],
            $partner,
            $t['quantity'],
            number_format((float)$t['unit_price'], 2, '.', ''),
            $t['reason'],
            $t['created_at'],
            $t['notes']
        ]);
    }
}

fclose($output);
exit;
