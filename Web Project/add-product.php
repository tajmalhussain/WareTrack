<?php
/**
 * WareTrack - Add Product (PHP + MySQL)
 * Handles both server-side POST processing and real-time interactive preview.
 */

require_once __DIR__ . '/includes/functions.php';

$currentPage = 'add-product';
$pageTitle = 'Catalog New Inventory Item';
$pageSubtitle = 'Register new supplies with automated SKU assignment and threshold rules.';

$successMessage = '';
$errorMessage = '';
$pdo = getDBConnection();

// Handle standard HTTP POST submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = trim($_POST['productSku'] ?? '');
    $name = trim($_POST['productName'] ?? '');
    $category = trim($_POST['productCategory'] ?? 'Furniture');
    $quantity = isset($_POST['productQty']) ? (int)$_POST['productQty'] : 0;
    $minStock = isset($_POST['productMinStock']) ? (int)$_POST['productMinStock'] : 10;
    $price = isset($_POST['productPrice']) ? (float)$_POST['productPrice'] : 0.00;
    $location = trim($_POST['productLocation'] ?? 'General Storage');
    $icon = trim($_POST['productIcon'] ?? '📦');
    $supplierId = !empty($_POST['supplierId']) ? trim($_POST['supplierId']) : null;

    if (empty($id)) {
        $id = generateNextSku();
    }

    if (empty($name)) {
        $errorMessage = 'Product name is required.';
    } elseif (!$pdo) {
        $errorMessage = 'Cannot save product: Database connection is offline.';
    } else {
        // Check for duplicate SKU
        $existing = getProductById($id);
        if ($existing) {
            $errorMessage = "Product SKU '$id' already exists. Please choose or generate another ID.";
        } else {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO `products` (`id`, `name`, `category`, `quantity`, `min_stock`, `price`, `location`, `icon`, `supplier_id`)
                    VALUES (:id, :name, :category, :quantity, :min_stock, :price, :location, :icon, :supplier_id)
                ");
                $stmt->execute([
                    ':id'          => $id,
                    ':name'        => $name,
                    ':category'    => $category,
                    ':quantity'    => $quantity,
                    ':min_stock'   => $minStock,
                    ':price'       => $price,
                    ':location'    => $location,
                    ':icon'        => $icon,
                    ':supplier_id' => $supplierId
                ]);

                logActivity($id, 'CREATE', "Product '$name' registered in database with initial stock of $quantity.");
                $successMessage = "Product '$name' ($id) cataloged successfully into MySQL database!";
            } catch (Exception $e) {
                $errorMessage = "Database Error: " . $e->getMessage();
            }
        }
    }
}

$suggestedSku = generateNextSku();
$suppliers = getSuppliersList('All', 'All', '');

require_once __DIR__ . '/includes/header.php';
?>

<style>
    .form-layout {
        display: grid;
        grid-template-columns: 1.35fr 1fr;
        gap: 32px;
        align-items: start;
    }

    .form-container-card {
        background: var(--bg-card);
        border: 1px solid var(--border-subtle);
        border-radius: var(--radius-lg);
        padding: 32px;
        box-shadow: var(--shadow-md);
    }

    .preview-sticky {
        position: sticky;
        top: 24px;
    }

    .preview-card-display {
        background: var(--bg-surface);
        border: 1px solid var(--border-hover);
        border-radius: var(--radius-lg);
        padding: 28px;
        box-shadow: var(--shadow-lg);
        position: relative;
        overflow: hidden;
    }

    .preview-card-display::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: var(--gradient-primary);
    }

    .preview-header {
        display: flex;
        align-items: center;
        gap: 16px;
        margin-bottom: 24px;
    }

    .preview-avatar {
        width: 58px;
        height: 58px;
        border-radius: var(--radius-md);
        background: var(--gradient-primary);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        box-shadow: 0 4px 14px rgba(99, 102, 241, 0.35);
    }

    .preview-sku-badge {
        font-family: monospace;
        font-size: 0.8rem;
        color: var(--text-muted);
        background: rgba(255, 255, 255, 0.05);
        padding: 3px 8px;
        border-radius: 6px;
        display: inline-block;
        margin-bottom: 4px;
    }

    .preview-title {
        font-family: 'Outfit', sans-serif;
        font-size: 1.3rem;
        font-weight: 700;
        color: var(--text-primary);
    }

    .preview-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
        background: var(--bg-surface-elevated);
        padding: 18px;
        border-radius: var(--radius-md);
        border: 1px solid var(--border-subtle);
        margin-bottom: 20px;
    }

    .preview-item small {
        display: block;
        color: var(--text-muted);
        font-size: 0.75rem;
        text-transform: uppercase;
        font-weight: 600;
        margin-bottom: 4px;
    }

    .preview-item strong {
        font-size: 1rem;
        color: var(--text-primary);
    }

    .auto-sku-btn {
        font-size: 0.75rem;
        padding: 4px 10px;
        border-radius: 6px;
        background: rgba(99, 102, 241, 0.15);
        color: var(--primary-light);
        border: 1px solid var(--border-accent);
        cursor: pointer;
        margin-left: 8px;
    }

    @media (max-width: 960px) {
        .form-layout {
            grid-template-columns: 1fr;
        }
        .preview-sticky {
            position: relative;
            top: 0;
        }
    }
</style>

<?php if ($successMessage): ?>
    <div style="background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.4); border-radius: var(--radius-md); padding: 14px 20px; margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between;">
        <div style="display: flex; align-items: center; gap: 10px;">
            <span style="font-size: 1.3rem;">✅</span>
            <span style="color: #34d399; font-weight: 600;"><?= e($successMessage) ?></span>
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="products.php" class="btn-primary" style="padding: 6px 14px; font-size: 0.85rem;">View in Products →</a>
            <a href="add-product.php" class="btn-secondary" style="padding: 6px 14px; font-size: 0.85rem;">+ Add Another</a>
        </div>
    </div>
<?php endif; ?>

<?php if ($errorMessage): ?>
    <div style="background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.4); border-radius: var(--radius-md); padding: 14px 20px; margin-bottom: 24px; display: flex; align-items: center; gap: 10px;">
        <span style="font-size: 1.3rem;">⚠️</span>
        <span style="color: #f87171; font-weight: 600;"><?= e($errorMessage) ?></span>
    </div>
<?php endif; ?>

<div class="form-layout">

    <!-- Product Form Card -->
    <div class="form-container-card">
        <form id="addProductForm" method="POST" action="add-product.php">
            <div class="form-grid">

                <div class="form-group form-group-full">
                    <label for="productName">Product Name *</label>
                    <input type="text" name="productName" id="productName" required placeholder="e.g. Ergonomic Office Desk Stand" autocomplete="off">
                </div>

                <div class="form-group">
                    <label for="productSku">
                        Product ID / SKU *
                        <button type="button" class="auto-sku-btn" id="genSkuBtn">🎲 Generate</button>
                    </label>
                    <input type="text" name="productSku" id="productSku" required value="<?= e($suggestedSku) ?>" style="font-family: monospace;">
                </div>

                <div class="form-group">
                    <label for="productCategory">Category *</label>
                    <select name="productCategory" id="productCategory" required>
                        <option value="Electronics">Electronics</option>
                        <option value="Furniture" selected>Furniture</option>
                        <option value="Storage">Storage</option>
                        <option value="Supplies">Supplies</option>
                        <option value="Machinery">Machinery</option>
                        <option value="Safety">Safety</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="productQty">Initial Stock Units *</label>
                    <input type="number" name="productQty" id="productQty" min="0" value="25" required>
                </div>

                <div class="form-group">
                    <label for="productMinStock">Reorder Threshold (Min Stock) *</label>
                    <input type="number" name="productMinStock" id="productMinStock" min="1" value="10" required>
                </div>

                <div class="form-group">
                    <label for="productPrice">Unit Price (₹ INR) *</label>
                    <input type="number" name="productPrice" id="productPrice" min="0" value="4999" required>
                </div>

                <div class="form-group">
                    <label for="productIcon">Item Icon / Emoji</label>
                    <select name="productIcon" id="productIcon">
                        <option value="📦">📦 Standard Box</option>
                        <option value="🪑" selected>🪑 Furniture / Desk</option>
                        <option value="📱">📱 Tech Gadget</option>
                        <option value="🏗️">🏗️ Heavy Storage</option>
                        <option value="🏷️">🏷️ Labels / Supplies</option>
                        <option value="🚜">🚜 Industrial Machine</option>
                        <option value="🔌">🔌 Cables & Power</option>
                        <option value="🦺">🦺 Safety Gear</option>
                        <option value="🔬">🔬 Lab / Cleanroom</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="supplierId">Primary Supplier Partner</label>
                    <select name="supplierId" id="supplierId">
                        <option value="">-- General / Warehouse Direct --</option>
                        <?php foreach ($suppliers as $sup): ?>
                            <option value="<?= e($sup['id']) ?>"><?= e($sup['name']) ?> (<?= e($sup['id']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group form-group-full">
                    <label for="productLocation">Warehouse Location / Bin</label>
                    <input type="text" name="productLocation" id="productLocation" placeholder="e.g. Aisle 4 - Rack C-08" value="Aisle 4 - Rack C-08">
                </div>

            </div>

            <div style="display: flex; gap: 14px; margin-top: 32px;">
                <button type="submit" class="btn-primary" style="flex: 1;">
                    💾 Save & Register in MySQL
                </button>
                <button type="reset" class="btn-secondary" id="resetBtn">
                    Reset Form
                </button>
            </div>
        </form>
    </div>

    <!-- Sticky Live Preview Card -->
    <div class="preview-sticky">
        <div style="margin-bottom: 12px; font-weight: 700; color: var(--text-secondary); font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em;">
            👁️ Real-Time Item Preview
        </div>

        <div class="preview-card-display">
            <div class="preview-header">
                <div class="preview-avatar" id="prevIcon">🪑</div>
                <div>
                    <span class="preview-sku-badge" id="prevSku"><?= e($suggestedSku) ?></span>
                    <h3 class="preview-title" id="prevName">Ergonomic Office Desk Stand</h3>
                    <div style="font-size: 0.8rem; color: var(--text-secondary);" id="prevLocation">📍 Aisle 4 - Rack C-08</div>
                </div>
            </div>

            <div class="preview-grid">
                <div class="preview-item">
                    <small>Category</small>
                    <strong id="prevCategory">Furniture</strong>
                </div>
                <div class="preview-item">
                    <small>Unit Price</small>
                    <strong id="prevPrice" style="color: var(--primary-light);">₹4,999</strong>
                </div>
                <div class="preview-item">
                    <small>Stock In Hand</small>
                    <strong id="prevQty">25 units</strong>
                </div>
                <div class="preview-item">
                    <small>Reorder Limit</small>
                    <strong id="prevMinStock">10 units</strong>
                </div>
            </div>

            <div style="background: var(--bg-surface-elevated); padding: 14px; border-radius: var(--radius-md); display: flex; justify-content: space-between; align-items: center;">
                <span style="font-size: 0.85rem; color: var(--text-secondary);">Calculated Health Status:</span>
                <span class="status-badge in-stock" id="prevStatus">
                    <span class="status-badge-dot"></span>
                    In Stock
                </span>
            </div>
        </div>
    </div>

</div>

<script>
    // Live Item Preview Updater
    function updateLivePreview() {
        const nameVal = document.getElementById("productName").value.trim();
        const skuVal = document.getElementById("productSku").value.trim();
        const catVal = document.getElementById("productCategory").value;
        const qtyVal = parseInt(document.getElementById("productQty").value, 10) || 0;
        const minVal = parseInt(document.getElementById("productMinStock").value, 10) || 1;
        const priceVal = parseFloat(document.getElementById("productPrice").value) || 0;
        const iconVal = document.getElementById("productIcon").value;
        const locVal = document.getElementById("productLocation").value.trim();

        document.getElementById("prevName").textContent = nameVal || "Item Name Preview";
        document.getElementById("prevSku").textContent = skuVal || "PRD-XXXX";
        document.getElementById("prevIcon").textContent = iconVal;
        document.getElementById("prevCategory").textContent = catVal;
        document.getElementById("prevLocation").textContent = "📍 " + (locVal || "General Storage");
        document.getElementById("prevQty").textContent = qtyVal + " units";
        document.getElementById("prevMinStock").textContent = minVal + " units";
        document.getElementById("prevPrice").textContent = "₹" + priceVal.toLocaleString('en-IN');

        const statusBadge = document.getElementById("prevStatus");
        if (qtyVal <= 0) {
            statusBadge.className = "status-badge out-of-stock";
            statusBadge.innerHTML = '<span class="status-badge-dot"></span> Out of Stock';
        } else if (qtyVal <= minVal) {
            statusBadge.className = "status-badge low-stock";
            statusBadge.innerHTML = '<span class="status-badge-dot"></span> Low Stock';
        } else {
            statusBadge.className = "status-badge in-stock";
            statusBadge.innerHTML = '<span class="status-badge-dot"></span> In Stock';
        }
    }

    // Bind inputs to preview update
    ["productName", "productSku", "productCategory", "productQty", "productMinStock", "productPrice", "productIcon", "productLocation"]
        .forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                el.addEventListener("input", updateLivePreview);
                el.addEventListener("change", updateLivePreview);
            }
        });

    // Random SKU generator button
    document.getElementById("genSkuBtn")?.addEventListener("click", () => {
        const rand = "PRD-" + Math.floor(1000 + Math.random() * 9000);
        const skuInput = document.getElementById("productSku");
        if (skuInput) {
            skuInput.value = rand;
            updateLivePreview();
        }
    });

    // Initial update
    updateLivePreview();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
