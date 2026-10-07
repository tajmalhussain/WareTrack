<?php
/**
 * WareTrack - Products Catalogue (PHP + MySQL)
 */

require_once __DIR__ . '/includes/functions.php';

$currentPage = 'products';
$pageTitle = 'Products Catalogue';
$pageSubtitle = 'Comprehensive inventory registry, stock adjustment, and aisle mapping.';

$stats = getInventoryStats();
$allProducts = getProductsList('All', '', 'All');

require_once __DIR__ . '/includes/header.php';
?>

<style>
    .page-toolbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        margin-bottom: 24px;
        flex-wrap: wrap;
    }

    .filter-controls {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
        flex: 1;
    }

    .search-box-wide {
        position: relative;
        min-width: 280px;
        flex: 1;
    }

    .search-box-wide input {
        width: 100%;
        background: var(--bg-surface-elevated);
        border: 1px solid var(--border-subtle);
        border-radius: var(--radius-md);
        padding: 10px 16px 10px 38px;
        color: var(--text-primary);
        font-size: 0.9rem;
        outline: none;
        transition: all var(--transition-fast);
    }

    .search-box-wide input:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px var(--primary-glow);
    }

    .search-box-wide .icon {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-muted);
        pointer-events: none;
    }

    .filter-select {
        background: var(--bg-surface-elevated);
        border: 1px solid var(--border-subtle);
        border-radius: var(--radius-md);
        padding: 10px 16px;
        color: var(--text-primary);
        font-size: 0.9rem;
        outline: none;
        cursor: pointer;
    }

    .filter-select:focus {
        border-color: var(--primary);
    }

    .summary-ribbon {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
        margin-bottom: 24px;
    }

    .ribbon-chip {
        background: var(--bg-card);
        border: 1px solid var(--border-subtle);
        border-radius: var(--radius-md);
        padding: 16px;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .ribbon-chip h4 {
        font-size: 1.35rem;
        font-family: 'Outfit', sans-serif;
        font-weight: 700;
    }

    .ribbon-chip p {
        font-size: 0.8rem;
        color: var(--text-secondary);
    }

    @media (max-width: 900px) {
        .summary-ribbon {
            grid-template-columns: repeat(2, 1fr);
        }
    }
</style>

<!-- Summary Mini Ribbon -->
<div class="summary-ribbon">
    <div class="ribbon-chip">
        <span style="font-size: 1.6rem;">📦</span>
        <div>
            <h4 id="ribbonTotal"><?= (int)$stats['totalProducts'] ?></h4>
            <p>Total Catalogue Items</p>
        </div>
    </div>
    <div class="ribbon-chip">
        <span style="font-size: 1.6rem; color: var(--success);">🟢</span>
        <div>
            <h4 id="ribbonHealthy" style="color: var(--success);"><?= (int)$stats['healthyStock'] ?></h4>
            <p>Optimal Stock</p>
        </div>
    </div>
    <div class="ribbon-chip">
        <span style="font-size: 1.6rem; color: var(--warning);">🟡</span>
        <div>
            <h4 id="ribbonLow" style="color: var(--warning);"><?= (int)$stats['lowStock'] ?></h4>
            <p>Low Stock Warning</p>
        </div>
    </div>
    <div class="ribbon-chip">
        <span style="font-size: 1.6rem; color: var(--danger);">🔴</span>
        <div>
            <h4 id="ribbonOut" style="color: var(--danger);"><?= (int)$stats['outOfStock'] ?></h4>
            <p>Critical Out of Stock</p>
        </div>
    </div>
</div>

<!-- Filter & Search Toolbar -->
<div class="page-toolbar">
    <div class="filter-controls">
        <div class="search-box-wide">
            <span class="icon">🔍</span>
            <input type="text" id="catalogueSearch" placeholder="Search by name, SKU ID, or aisle bin...">
        </div>

        <select id="categoryFilter" class="filter-select">
            <option value="All">All Categories</option>
            <option value="Electronics">Electronics</option>
            <option value="Furniture">Furniture</option>
            <option value="Storage">Storage</option>
            <option value="Supplies">Supplies</option>
            <option value="Machinery">Machinery</option>
            <option value="Safety">Safety</option>
        </select>

        <select id="statusFilter" class="filter-select">
            <option value="All">All Stock Statuses</option>
            <option value="in-stock">In Stock</option>
            <option value="low-stock">Low Stock</option>
            <option value="out-of-stock">Out of Stock</option>
        </select>

        <a href="api/export-csv.php" class="btn-secondary" id="exportBtn" style="padding: 10px 16px; font-size: 0.88rem; display: inline-flex; align-items: center; gap: 6px; text-decoration: none;">
            <span>📥</span> Export CSV
        </a>

        <a href="add-product.php" class="btn-primary" style="padding: 10px 16px; font-size: 0.88rem;">
            <span>➕</span> Add Product
        </a>
    </div>
</div>

<!-- Master Products Table -->
<div class="table-section" style="padding: 20px;">
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
            <tbody id="catalogueTableBody">
                <?php if (empty($allProducts)): ?>
                    <tr>
                        <td colspan="7">
                            <div class="empty-state">
                                <div class="empty-state-icon">📦</div>
                                <h4>No Matching Products Found</h4>
                                <p>Try clearing filters or add a new product to MySQL.</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($allProducts as $item): ?>
                        <?php
                            $status = getStockStatus($item['quantity'], $item['min_stock']);
                            $maxRef = max($item['min_stock'] * 2.5, $item['quantity'], 50);
                            $percentage = min(100, round(($item['quantity'] / $maxRef) * 100));

                            $fillClass = "stock-fill-high";
                            if ($item['quantity'] <= $item['min_stock']) $fillClass = "stock-fill-mid";
                            if ($item['quantity'] <= 0) $fillClass = "stock-fill-low";
                        ?>
                        <tr data-id="<?= e($item['id']) ?>" data-category="<?= e($item['category']) ?>" data-status="<?= $status['class'] ?>" data-name="<?= e(strtolower($item['name'])) ?>" data-location="<?= e(strtolower($item['location'])) ?>">
                            <td><span class="badge-sku"><?= e($item['id']) ?></span></td>
                            <td>
                                <div class="product-cell">
                                    <div class="product-avatar"><?= e($item['icon'] ?: '📦') ?></div>
                                    <div class="product-meta">
                                        <span class="product-title"><?= e($item['name']) ?></span>
                                        <span class="product-sku">📍 <?= e($item['location'] ?: 'General Storage') ?></span>
                                    </div>
                                </div>
                            </td>
                            <td><span class="category-tag"><?= e($item['category']) ?></span></td>
                            <td>
                                <div class="stock-meter-cell">
                                    <div class="stock-meter-header">
                                        <span class="stock-count-text"><?= (int)$item['quantity'] ?> units</span>
                                        <span style="color: var(--text-muted); font-size: 0.72rem;">Min: <?= (int)$item['min_stock'] ?></span>
                                    </div>
                                    <div class="stock-bar-track">
                                        <div class="stock-bar-fill <?= $fillClass ?>" style="width: <?= $percentage ?>%"></div>
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
                                    <a href="stock-in.php?product_id=<?= urlencode($item['id']) ?>" class="btn-icon-action" title="Stock In (Receive)">
                                        📥
                                    </a>
                                    <a href="stock-out.php?product_id=<?= urlencode($item['id']) ?>" class="btn-icon-action" title="Stock Out (Dispatch)">
                                        📤
                                    </a>
                                    <a href="edit-product.php?id=<?= urlencode($item['id']) ?>" class="btn-icon-action" title="Edit Product">
                                        ✏️
                                    </a>
                                    <button class="btn-icon-action danger" title="Delete Product" onclick="deleteProductCatalog('<?= e($item['id']) ?>')">
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
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
