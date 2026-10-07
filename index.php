<?php
/**
 * WareTrack - Dynamic Warehouse Dashboard (PHP + MySQL)
 * Key Telemetry, Instant Actions, Recent Inventory, and Live In/Out Movements
 */

require_once __DIR__ . '/includes/functions.php';

$currentPage = 'dashboard';
$pageTitle = 'Warehouse Dashboard';
$pageSubtitle = 'Real-time inventory telemetry and smart logistics management.';

// Get dynamic metrics from MySQL database
$stats = getInventoryStats();
$recentProducts = getProductsList('All', '', 'All');
$recentMovements = getStockTransactions('All', 5, '');

require_once __DIR__ . '/includes/header.php';
?>

<!-- Key Metrics / Statistics -->
<section class="stats" aria-label="Inventory Statistics">

    <div class="card">
        <div class="card-icon">📦</div>
        <div class="card-content">
            <h3 id="totalProducts"><?= number_format($stats['totalProducts']) ?></h3>
            <p>Total Products</p>
            <span class="card-trend trend-info">✦ Active SKUs in MySQL</span>
        </div>
    </div>

    <div class="card">
        <div class="card-icon">📊</div>
        <div class="card-content">
            <h3 id="totalStock"><?= number_format($stats['totalStock']) ?></h3>
            <p>Total Stock</p>
            <span class="card-trend trend-up">↑ Live Unit Count</span>
        </div>
    </div>

    <div class="card">
        <div class="card-icon">⚠️</div>
        <div class="card-content">
            <h3 id="lowStock"><?= number_format($stats['lowStock']) ?></h3>
            <p>Low Stock</p>
            <span class="card-trend <?= $stats['lowStock'] > 0 ? 'trend-warning' : 'trend-info' ?>">
                <?= $stats['lowStock'] > 0 ? '⚡ Action Required' : '✦ All Levels Optimal' ?>
            </span>
        </div>
    </div>

    <div class="card">
        <div class="card-icon">💰</div>
        <div class="card-content">
            <h3 id="inventoryValue"><?= formatCurrency($stats['inventoryValue']) ?></h3>
            <p>Inventory Value</p>
            <span class="card-trend trend-up">✦ Total Asset Value</span>
        </div>
    </div>

</section>

<!-- Quick Actions Grid -->
<section class="quick-actions" aria-label="Quick Actions">
    <h2>⚡ Warehouse Operations</h2>

    <div class="action-container" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">

        <a href="stock-in.php" class="action-card" style="border-left: 4px solid var(--success);">
            <span>📥</span>
            <h3>Stock In</h3>
            <p>Receive inbound supplier orders and replenish stock.</p>
        </a>

        <a href="stock-out.php" class="action-card" style="border-left: 4px solid #ef4444;">
            <span>📤</span>
            <h3>Stock Out</h3>
            <p>Dispatch customer orders with negative stock prevention.</p>
        </a>

        <a href="add-product.php" class="action-card" style="border-left: 4px solid var(--primary);">
            <span>➕</span>
            <h3>Add Product</h3>
            <p>Create and catalog a new item in MySQL database.</p>
        </a>

        <a href="suppliers.php" class="action-card" style="border-left: 4px solid #c084fc;">
            <span>🏢</span>
            <h3>Suppliers</h3>
            <p>Manage vendor partners, contacts, and supply lines.</p>
        </a>

        <a href="reports.php" class="action-card" style="border-left: 4px solid var(--info);">
            <span>📈</span>
            <h3>Reports & Audit</h3>
            <p>Analyze transaction ledgers, valuations, and CSV exports.</p>
        </a>

        <a href="products.php" class="action-card" style="border-left: 4px solid #f59e0b;">
            <span>📦</span>
            <h3>Catalogue</h3>
            <p>Inspect master inventory with live search & filters.</p>
        </a>

    </div>
</section>

<!-- Recent Inventory Table -->
<section class="table-section" aria-label="Recent Inventory Table">

    <div class="section-header">
        <div>
            <h2>Recent Inventory</h2>
            <p>Live inventory tracking with health meters and quick restocking</p>
        </div>

        <div class="section-header-actions">
            <div class="table-filter-tabs">
                <button class="filter-tab active" data-category="All">All</button>
                <button class="filter-tab" data-category="Electronics">Electronics</button>
                <button class="filter-tab" data-category="Furniture">Furniture</button>
                <button class="filter-tab" data-category="Storage">Storage</button>
                <button class="filter-tab" data-category="Safety">Safety</button>
            </div>

            <a href="products.php" class="view-btn">
                <span>View All</span>
                <span>→</span>
            </a>
        </div>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Product ID</th>
                    <th>Product & Location</th>
                    <th>Category</th>
                    <th>Stock Level</th>
                    <th>Unit Price</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="dashboardProducts">
                <?php if (empty($recentProducts)): ?>
                    <tr>
                        <td colspan="7">
                            <div class="empty-state">
                                <p>No products available. Click "Add Product" to create your first warehouse SKU!</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php 
                    $displayItems = array_slice($recentProducts, 0, 5);
                    foreach ($displayItems as $item): 
                        $status = getStockStatus($item['quantity'], $item['min_stock']);
                        $stockPercentage = min(100, round(($item['quantity'] / max(1, $item['min_stock'] * 3)) * 100));
                        $meterColorClass = $item['quantity'] <= 0 ? 'empty' : ($item['quantity'] <= $item['min_stock'] ? 'low' : 'healthy');
                    ?>
                        <tr data-category="<?= e($item['category']) ?>" data-id="<?= e($item['id']) ?>">
                            <td><span class="sku-badge"><?= e($item['id']) ?></span></td>
                            <td>
                                <div class="product-info-cell">
                                    <span class="product-icon"><?= e($item['icon']) ?></span>
                                    <div>
                                        <div class="product-name"><?= e($item['name']) ?></div>
                                        <div class="product-location">📍 <?= e($item['location']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><span class="category-tag"><?= e($item['category']) ?></span></td>
                            <td>
                                <div class="stock-meter-wrap">
                                    <div class="stock-meta-info">
                                        <span class="stock-qty-val"><?= (int)$item['quantity'] ?> units</span>
                                        <span class="stock-min-hint">Min: <?= (int)$item['min_stock'] ?></span>
                                    </div>
                                    <div class="stock-meter-track">
                                        <div class="stock-meter-fill <?= $meterColorClass ?>" style="width: <?= $stockPercentage ?>%;"></div>
                                    </div>
                                </div>
                            </td>
                            <td><span class="price-text"><?= formatCurrency($item['price']) ?></span></td>
                            <td>
                                <span class="status-badge <?= $status['class'] ?>">
                                    <span class="status-badge-dot"></span>
                                    <?= $status['label'] ?>
                                </span>
                            </td>
                            <td>
                                <div class="row-actions">
                                    <a href="stock-in.php?product_id=<?= urlencode($item['id']) ?>" class="btn-icon-action" title="Stock In">
                                        📥
                                    </a>
                                    <a href="stock-out.php?product_id=<?= urlencode($item['id']) ?>" class="btn-icon-action" title="Stock Out">
                                        📤
                                    </a>
                                    <a href="edit-product.php?id=<?= urlencode($item['id']) ?>" class="btn-icon-action" title="Edit Product">
                                        ✏️
                                    </a>
                                    <button class="btn-icon-action danger" title="Delete Product" onclick="deleteProduct('<?= e($item['id']) ?>')">
                                        🗑️
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</section>

<!-- Recent Stock Movements Feed -->
<section class="table-section" aria-label="Recent Stock Movements">
    <div class="section-header">
        <div>
            <h2>Recent Movements Ledger</h2>
            <p>Chronological stream of inward consignments and outward releases</p>
        </div>
        <a href="reports.php?tab=movements" class="view-btn">
            <span>Full Ledger</span>
            <span>→</span>
        </a>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Ref #</th>
                    <th>Type</th>
                    <th>Product Item</th>
                    <th>Party / Destination</th>
                    <th>Units</th>
                    <th>Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentMovements)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 24px; color: var(--text-muted);">
                            No recent stock transactions recorded.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recentMovements as $mv): ?>
                        <tr>
                            <td><span style="font-family: monospace; font-weight: 600; color: var(--primary-light);"><?= e($mv['reference_no']) ?></span></td>
                            <td>
                                <?php if ($mv['transaction_type'] === 'IN'): ?>
                                    <span class="badge in-stock" style="font-weight: 700; padding: 2px 8px;">📥 IN</span>
                                <?php else: ?>
                                    <span class="badge out-of-stock" style="font-weight: 700; padding: 2px 8px; background: rgba(239,68,68,0.15); color: #f87171;">📤 OUT</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <span><?= e($mv['product_icon'] ?? '📦') ?></span>
                                    <strong style="color: var(--text-primary);"><?= e($mv['product_name']) ?></strong>
                                </div>
                            </td>
                            <td>
                                <?= $mv['transaction_type'] === 'IN' ? e($mv['supplier_name'] ?: 'Vendor Delivery') : e($mv['recipient'] ?: 'Fulfillment') ?>
                            </td>
                            <td>
                                <strong style="color: <?= $mv['transaction_type'] === 'IN' ? 'var(--success)' : '#f87171' ?>;">
                                    <?= $mv['transaction_type'] === 'IN' ? '+' : '-' ?><?= (int)$mv['quantity'] ?>
                                </strong>
                            </td>
                            <td style="color: var(--text-secondary); font-size: 0.85rem;">
                                <?= date('M d, H:i', strtotime($mv['created_at'])) ?>
                            </td>
                            <td>
                                <a href="reports.php?tab=movements" style="color: var(--primary-light); text-decoration: none; font-size: 0.82rem;">Audit View →</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
