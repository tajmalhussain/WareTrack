<?php
/**
 * WareTrack - Warehouse Analytics & Audit Reports (PHP + MySQL)
 * Stock Movement Ledger, Category Valuations, Critical Reorder Planning, and Supplier Performance
 */

require_once __DIR__ . '/includes/functions.php';

$currentPage = 'reports';
$pageTitle = 'Warehouse Reports & Audit';
$pageSubtitle = 'Comprehensive logistics analytics, stock movement ledger, and inventory valuations.';

$activeTab = $_GET['tab'] ?? 'movements';
$searchQuery = $_GET['search'] ?? '';

$stats = getInventoryStats();
$transactions = getStockTransactions('All', 100, $searchQuery);
$categoryValuation = getCategoryValuationReport();
$lowStockPlan = getLowStockReport();
$supplierReport = getSupplierReport();

// Total inward vs outward summary from transactions
$totalInUnits = 0;
$totalOutUnits = 0;
foreach ($transactions as $tx) {
    if ($tx['transaction_type'] === 'IN') {
        $totalInUnits += (int)$tx['quantity'];
    } else {
        $totalOutUnits += (int)$tx['quantity'];
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<style>
    .reports-ribbon {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
        margin-bottom: 24px;
    }

    .report-card {
        background: var(--bg-card);
        border: 1px solid var(--border-subtle);
        border-radius: var(--radius-md);
        padding: 20px;
        display: flex;
        align-items: center;
        gap: 16px;
        transition: transform var(--transition-base);
    }

    .report-card:hover {
        transform: translateY(-2px);
        border-color: var(--border-accent);
    }

    .report-icon {
        width: 48px;
        height: 48px;
        border-radius: var(--radius-sm);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }

    .report-nav-tabs {
        display: flex;
        gap: 8px;
        background: var(--bg-surface-elevated);
        border: 1px solid var(--border-subtle);
        border-radius: var(--radius-md);
        padding: 6px;
        margin-bottom: 24px;
        overflow-x: auto;
    }

    .report-tab-btn {
        background: transparent;
        border: none;
        color: var(--text-secondary);
        padding: 10px 18px;
        border-radius: var(--radius-sm);
        font-size: 0.88rem;
        font-weight: 600;
        cursor: pointer;
        transition: all var(--transition-fast);
        display: flex;
        align-items: center;
        gap: 8px;
        white-space: nowrap;
        text-decoration: none;
    }

    .report-tab-btn:hover {
        color: var(--text-primary);
        background: rgba(255, 255, 255, 0.05);
    }

    .report-tab-btn.active {
        background: var(--primary);
        color: #ffffff;
        box-shadow: 0 4px 12px var(--primary-glow);
    }

    .report-toolbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }

    .progress-bar-wrap {
        width: 100%;
        height: 6px;
        background: var(--bg-surface);
        border-radius: var(--radius-full);
        overflow: hidden;
        margin-top: 6px;
    }

    .progress-bar-fill {
        height: 100%;
        background: var(--gradient-primary);
        border-radius: var(--radius-full);
    }

    @media print {
        .sidebar, .page-toolbar, .report-nav-tabs, .report-toolbar, .theme-toggle-btn, .mobile-menu-btn, .admin {
            display: none !important;
        }
        .main {
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
        }
        body {
            background: #ffffff !important;
            color: #000000 !important;
        }
    }

    @media (max-width: 900px) {
        .reports-ribbon {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 600px) {
        .reports-ribbon {
            grid-template-columns: 1fr;
        }
    }
</style>

<!-- Top Analytics Ribbon -->
<div class="reports-ribbon">
    <div class="report-card">
        <div class="report-icon" style="background: rgba(99, 102, 241, 0.15); color: var(--primary-light);">💰</div>
        <div>
            <h3 style="font-size: 1.35rem; font-weight: 700; color: var(--text-primary);"><?= formatCurrency($stats['inventoryValue']) ?></h3>
            <p style="font-size: 0.8rem; color: var(--text-secondary);">Total Inventory Asset Value</p>
        </div>
    </div>

    <div class="report-card">
        <div class="report-icon" style="background: var(--success-bg); color: var(--success);">📥</div>
        <div>
            <h3 style="font-size: 1.35rem; font-weight: 700; color: var(--text-primary);">+<?= number_format($stats['totalStockIn']) ?> units</h3>
            <p style="font-size: 0.8rem; color: var(--text-secondary);">Total Inward Intake</p>
        </div>
    </div>

    <div class="report-card">
        <div class="report-icon" style="background: rgba(239, 68, 68, 0.15); color: #f87171;">📤</div>
        <div>
            <h3 style="font-size: 1.35rem; font-weight: 700; color: var(--text-primary);">-<?= number_format($stats['totalStockOut']) ?> units</h3>
            <p style="font-size: 0.8rem; color: var(--text-secondary);">Total Outward Dispatched</p>
        </div>
    </div>

    <div class="report-card">
        <div class="report-icon" style="background: var(--warning-bg); color: var(--warning);">⚡</div>
        <div>
            <h3 style="font-size: 1.35rem; font-weight: 700; color: var(--text-primary);"><?= $stats['lowStock'] ?> SKUs</h3>
            <p style="font-size: 0.8rem; color: var(--text-secondary);">Actionable Reorder Alerts</p>
        </div>
    </div>
</div>

<!-- Report Navigation Tabs -->
<div class="report-nav-tabs">
    <a href="reports.php?tab=movements" class="report-tab-btn <?= $activeTab === 'movements' ? 'active' : '' ?>">
        <span>📊</span> Stock Movement Ledger
    </a>
    <a href="reports.php?tab=valuation" class="report-tab-btn <?= $activeTab === 'valuation' ? 'active' : '' ?>">
        <span>💰</span> Category Valuations
    </a>
    <a href="reports.php?tab=low_stock" class="report-tab-btn <?= $activeTab === 'low_stock' ? 'active' : '' ?>">
        <span>⚠️</span> Critical Reorder Plan
    </a>
    <a href="reports.php?tab=suppliers" class="report-tab-btn <?= $activeTab === 'suppliers' ? 'active' : '' ?>">
        <span>🏢</span> Supplier Volumes
    </a>
</div>

<!-- Controls & Export Toolbar -->
<div class="report-toolbar">
    <div>
        <h2 style="font-size: 1.25rem; color: var(--text-primary);">
            <?php 
                if ($activeTab === 'valuation') echo '💰 Inventory Valuation & Category Breakdown';
                elseif ($activeTab === 'low_stock') echo '⚠️ Critical Low Stock & Replenishment Plan';
                elseif ($activeTab === 'suppliers') echo '🏢 Vendor Supply Volume & Delivery History';
                else echo '📊 Comprehensive Stock Movements Ledger';
            ?>
        </h2>
        <span style="font-size: 0.8rem; color: var(--text-muted);">Real-time MySQL live query dataset</span>
    </div>

    <div style="display: flex; gap: 10px;">
        <button onclick="window.print()" class="btn-secondary" style="display: flex; align-items: center; gap: 8px;">
            <span>🖨️</span> Print View
        </button>

        <a href="api/export-report.php?type=<?= urlencode($activeTab) ?>" class="btn-primary" style="display: flex; align-items: center; gap: 8px; text-decoration: none;">
            <span>📥</span> Export CSV
        </a>
    </div>
</div>

<!-- TAB 1: Stock Movements Ledger -->
<?php if ($activeTab === 'movements'): ?>
    <section class="table-section">
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>Type</th>
                        <th>Ref #</th>
                        <th>Product Item</th>
                        <th>Supplier / Destination</th>
                        <th>Quantity</th>
                        <th>Valuation</th>
                        <th>Audit Purpose</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($transactions)): ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 40px; color: var(--text-muted);">
                                No transaction ledger records found.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($transactions as $t): ?>
                            <tr>
                                <td style="color: var(--text-secondary); font-size: 0.85rem; white-space: nowrap;">
                                    <?= date('M d, Y • H:i', strtotime($t['created_at'])) ?>
                                </td>
                                <td>
                                    <?php if ($t['transaction_type'] === 'IN'): ?>
                                        <span class="badge in-stock" style="font-weight: 700; padding: 3px 8px;">📥 IN</span>
                                    <?php else: ?>
                                        <span class="badge out-of-stock" style="font-weight: 700; padding: 3px 8px; background: rgba(239,68,68,0.15); color: #f87171;">📤 OUT</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span style="font-family: monospace; font-weight: 600; color: var(--text-primary);"><?= e($t['reference_no']) ?></span>
                                </td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <span style="font-size: 1.2rem;"><?= e($t['product_icon'] ?? '📦') ?></span>
                                        <div>
                                            <div style="font-weight: 600; color: var(--text-primary);"><?= e($t['product_name']) ?></div>
                                            <small style="color: var(--text-muted);"><?= e($t['product_id']) ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?= ($t['transaction_type'] === 'IN') 
                                        ? (!empty($t['supplier_name']) ? e($t['supplier_name']) : '<span style="color:var(--text-muted)">General Vendor</span>')
                                        : (!empty($t['recipient']) ? e($t['recipient']) : '<span style="color:var(--text-muted)">Fulfillment</span>')
                                    ?>
                                </td>
                                <td>
                                    <strong style="color: <?= $t['transaction_type'] === 'IN' ? 'var(--success)' : '#f87171' ?>;">
                                        <?= $t['transaction_type'] === 'IN' ? '+' : '-' ?><?= (int)$t['quantity'] ?>
                                    </strong>
                                </td>
                                <td>
                                    <?= formatCurrency($t['unit_price'] * $t['quantity']) ?>
                                </td>
                                <td style="color: var(--text-secondary); font-size: 0.85rem;">
                                    <?= e($t['reason']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

<!-- TAB 2: Inventory Valuation by Category -->
<?php elseif ($activeTab === 'valuation'): ?>
    <section class="table-section">
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Unique Items</th>
                        <th>Total Stock Units</th>
                        <th>Low Stock SKUs</th>
                        <th>Asset Valuation (₹)</th>
                        <th>Portfolio Share</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categoryValuation as $cat): ?>
                        <?php 
                            $sharePct = $stats['inventoryValue'] > 0 ? (($cat['category_value'] / $stats['inventoryValue']) * 100) : 0;
                        ?>
                        <tr>
                            <td>
                                <strong style="color: var(--text-primary); font-size: 0.95rem;"><?= e($cat['category']) ?></strong>
                            </td>
                            <td>
                                <?= (int)$cat['item_count'] ?> items
                            </td>
                            <td>
                                <strong><?= number_format($cat['total_units']) ?> units</strong>
                            </td>
                            <td>
                                <?php if ($cat['low_stock_items'] > 0): ?>
                                    <span class="badge low-stock"><?= $cat['low_stock_items'] ?> below limit</span>
                                <?php else: ?>
                                    <span class="badge in-stock">Optimal</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong style="color: var(--primary-light); font-size: 1rem;"><?= formatCurrency($cat['category_value']) ?></strong>
                            </td>
                            <td style="min-width: 140px;">
                                <div style="display: flex; justify-content: space-between; font-size: 0.8rem; color: var(--text-muted);">
                                    <span><?= round($sharePct, 1) ?>%</span>
                                </div>
                                <div class="progress-bar-wrap">
                                    <div class="progress-bar-fill" style="width: <?= min(100, max(5, $sharePct)) ?>%;"></div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

<!-- TAB 3: Critical Low Stock & Reorder Plan -->
<?php elseif ($activeTab === 'low_stock'): ?>
    <section class="table-section">
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>SKU & Product</th>
                        <th>Category</th>
                        <th>Current Units</th>
                        <th>Reorder Level</th>
                        <th>Stock Deficit</th>
                        <th>Recommended Reorder</th>
                        <th>Estimated Cost</th>
                        <th>Quick Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($lowStockPlan)): ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 40px; color: var(--success);">
                                <strong>🎉 All product stock levels are healthy! No items below reorder threshold.</strong>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($lowStockPlan as $item): ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <span style="font-size: 1.2rem;"><?= e($item['icon']) ?></span>
                                        <div>
                                            <div style="font-weight: 600; color: var(--text-primary);"><?= e($item['name']) ?></div>
                                            <small style="color: var(--text-muted);"><?= e($item['id']) ?> • <?= e($item['location']) ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td><?= e($item['category']) ?></td>
                                <td>
                                    <span class="badge <?= $item['quantity'] == 0 ? 'out-of-stock' : 'low-stock' ?>" style="font-weight: 700;">
                                        <?= (int)$item['quantity'] ?> units
                                    </span>
                                </td>
                                <td style="color: var(--text-secondary);"><?= (int)$item['min_stock'] ?> units</td>
                                <td>
                                    <strong style="color: #ef4444;">-<?= max(0, (int)$item['min_stock'] - (int)$item['quantity']) ?> units</strong>
                                </td>
                                <td>
                                    <strong style="color: var(--success);">+<?= (int)$item['recommended_reorder'] ?> units</strong>
                                </td>
                                <td>
                                    <?= formatCurrency($item['estimated_reorder_cost']) ?>
                                </td>
                                <td>
                                    <a href="stock-in.php?product_id=<?= urlencode($item['id']) ?>" class="btn-primary" style="padding: 6px 12px; font-size: 0.8rem; text-decoration: none;">
                                        <span>📥</span> Stock In
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

<!-- TAB 4: Supplier Performance Volume -->
<?php elseif ($activeTab === 'suppliers'): ?>
    <section class="table-section">
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Supplier Vendor</th>
                        <th>Category Line</th>
                        <th>Contact Lead</th>
                        <th>Cataloged SKUs</th>
                        <th>Inward Units Delivered</th>
                        <th>Procurement Volume (₹)</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($supplierReport as $sup): ?>
                        <tr>
                            <td>
                                <strong style="color: var(--text-primary); display: block; font-size: 0.95rem;"><?= e($sup['name']) ?></strong>
                                <small style="color: var(--text-muted); font-family: monospace;"><?= e($sup['id']) ?></small>
                            </td>
                            <td><?= e($sup['category']) ?></td>
                            <td>
                                <div><?= e($sup['contact_name']) ?></div>
                                <small style="color: var(--text-muted);"><?= e($sup['phone']) ?></small>
                            </td>
                            <td><?= (int)$sup['cataloged_products'] ?> SKUs</td>
                            <td>
                                <strong>+<?= number_format($sup['total_inward_units']) ?> units</strong>
                            </td>
                            <td>
                                <strong style="color: var(--primary-light);"><?= formatCurrency($sup['total_procurement_value']) ?></strong>
                            </td>
                            <td>
                                <span class="badge in-stock"><?= e($sup['status']) ?></span>
                            </td>
                            <td>
                                <a href="stock-in.php?supplier_id=<?= urlencode($sup['id']) ?>" class="btn-secondary" style="padding: 6px 12px; font-size: 0.8rem; text-decoration: none;">
                                    <span>📥</span> Order In
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
