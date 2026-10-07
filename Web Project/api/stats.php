<?php
/**
 * WareTrack - Inventory KPI Statistics Endpoint
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/functions.php';

$stats = getInventoryStats();
echo json_encode([
    'success' => true,
    'stats' => $stats
]);
