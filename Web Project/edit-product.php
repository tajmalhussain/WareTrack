<?php
/**
 * WareTrack - Edit Product (PHP + MySQL)
 * Allows modifying existing inventory items in the database.
 */

require_once __DIR__ . '/includes/functions.php';

$currentPage = 'products';
$pageTitle = 'Edit Inventory Item';
$pageSubtitle = 'Update stock levels, pricing, category, and warehouse storage bin.';

$id = trim($_GET['id'] ?? ($_POST['productSku'] ?? ''));
$product = getProductById($id);

$successMessage = '';
$errorMessage = '';
$pdo = getDBConnection();

// Process POST update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$product) {
        $errorMessage = "Product '$id' not found in database.";
    } elseif (!$pdo) {
        $errorMessage = "Database connection offline.";
    } else {
        $name = trim($_POST['productName'] ?? '');
        $category = trim($_POST['productCategory'] ?? 'Furniture');
        $quantity = isset($_POST['productQty']) ? (int)$_POST['productQty'] : 0;
        $minStock = isset($_POST['productMinStock']) ? (int)$_POST['productMinStock'] : 10;
        $price = isset($_POST['productPrice']) ? (float)$_POST['productPrice'] : 0.00;
        $location = trim($_POST['productLocation'] ?? 'General Storage');
        $icon = trim($_POST['productIcon'] ?? '📦');
        $supplierId = !empty($_POST['supplierId']) ? trim($_POST['supplierId']) : null;

        if (empty($name)) {
            $errorMessage = 'Product name cannot be empty.';
        } else {
            try {
                $stmt = $pdo->prepare("
                    UPDATE `products`
                    SET `name` = :name,
                        `category` = :category,
                        `quantity` = :quantity,
                        `min_stock` = :min_stock,
                        `price` = :price,
                        `location` = :location,
                        `icon` = :icon,
                        `supplier_id` = :supplier_id
                    WHERE `id` = :id
                ");
                $stmt->execute([
                    ':name'        => $name,
                    ':category'    => $category,
                    ':quantity'    => $quantity,
                    ':min_stock'   => $minStock,
                    ':price'       => $price,
                    ':location'    => $location,
                    ':icon'        => $icon,
                    ':supplier_id' => $supplierId,
                    ':id'          => $id
                ]);

                logActivity($id, 'UPDATE', "Updated details for '$name' ($id).");
                $successMessage = "Product '$name' ($id) updated successfully in MySQL!";
                // Refresh product data
                $product = getProductById($id);
            } catch (Exception $e) {
                $errorMessage = "Update failed: " . $e->getMessage();
            }
        }
    }
}

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

<?php if (!$product): ?>
    <div class="empty-state" style="margin-top: 40px;">
        <div class="empty-state-icon">⚠️</div>
        <h3>Product Not Found</h3>
        <p>No product with ID <code><?= e($id) ?></code> was found in the database.</p>
        <a href="products.php" class="btn-primary" style="margin-top: 16px; display: inline-block;">← Back to Catalogue</a>
    </div>
<?php else: ?>

    <?php if ($successMessage): ?>
        <div style="background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.4); border-radius: var(--radius-md); padding: 14px 20px; margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <span style="font-size: 1.3rem;">✅</span>
                <span style="color: #34d399; font-weight: 600;"><?= e($successMessage) ?></span>
            </div>
            <a href="products.php" class="btn-primary" style="padding: 6px 14px; font-size: 0.85rem;">View in Catalogue →</a>
        </div>
    <?php endif; ?>

    <?php if ($errorMessage): ?>
        <div style="background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.4); border-radius: var(--radius-md); padding: 14px 20px; margin-bottom: 24px; display: flex; align-items: center; gap: 10px;">
            <span style="font-size: 1.3rem;">⚠️</span>
            <span style="color: #f87171; font-weight: 600;"><?= e($errorMessage) ?></span>
        </div>
    <?php endif; ?>

    <div class="form-layout">

        <!-- Edit Product Form -->
        <div class="form-container-card">
            <form id="editProductForm" method="POST" action="edit-product.php?id=<?= urlencode($id) ?>">
                <div class="form-grid">

                    <div class="form-group form-group-full">
                        <label for="productName">Product Name *</label>
                        <input type="text" name="productName" id="productName" required value="<?= e($product['name']) ?>" autocomplete="off">
                    </div>

                    <div class="form-group">
                        <label for="productSku">Product ID / SKU (Fixed Primary Key)</label>
                        <input type="text" name="productSku" id="productSku" readonly value="<?= e($product['id']) ?>" style="font-family: monospace; opacity: 0.75; cursor: not-allowed;">
                    </div>

                    <div class="form-group">
                        <label for="productCategory">Category *</label>
                        <select name="productCategory" id="productCategory" required>
                            <?php 
                                $categories = ['Electronics', 'Furniture', 'Storage', 'Supplies', 'Machinery', 'Safety'];
                                foreach ($categories as $cat):
                            ?>
                                <option value="<?= $cat ?>" <?= $product['category'] === $cat ? 'selected' : '' ?>><?= $cat ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="productQty">Current Stock Units *</label>
                        <input type="number" name="productQty" id="productQty" min="0" value="<?= (int)$product['quantity'] ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="productMinStock">Reorder Threshold (Min Stock) *</label>
                        <input type="number" name="productMinStock" id="productMinStock" min="1" value="<?= (int)$product['min_stock'] ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="productPrice">Unit Price (₹ INR) *</label>
                        <input type="number" name="productPrice" id="productPrice" min="0" value="<?= (float)$product['price'] ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="productIcon">Item Icon / Emoji</label>
                        <select name="productIcon" id="productIcon">
                            <?php 
                                $icons = [
                                    '📦' => '📦 Standard Box',
                                    '🪑' => '🪑 Furniture / Desk',
                                    '📱' => '📱 Tech Gadget',
                                    '🏗️' => '🏗️ Heavy Storage',
                                    '🏷️' => '🏷️ Labels / Supplies',
                                    '🚜' => '🚜 Industrial Machine',
                                    '🔌' => '🔌 Cables & Power',
                                    '🦺' => '🦺 Safety Gear',
                                    '🔬' => '🔬 Lab / Cleanroom'
                                ];
                                foreach ($icons as $ico => $label):
                            ?>
                                <option value="<?= $ico ?>" <?= $product['icon'] === $ico ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="supplierId">Primary Supplier Partner</label>
                        <select name="supplierId" id="supplierId">
                            <option value="">-- General / Warehouse Direct --</option>
                            <?php foreach ($suppliers as $sup): ?>
                                <option value="<?= e($sup['id']) ?>" <?= ($product['supplier_id'] === $sup['id']) ? 'selected' : '' ?>>
                                    <?= e($sup['name']) ?> (<?= e($sup['id']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group form-group-full">
                        <label for="productLocation">Warehouse Location / Bin</label>
                        <input type="text" name="productLocation" id="productLocation" value="<?= e($product['location']) ?>">
                    </div>

                </div>

                <div style="display: flex; gap: 14px; margin-top: 32px;">
                    <button type="submit" class="btn-primary" style="flex: 1;">
                        💾 Save Changes to MySQL
                    </button>
                    <a href="products.php" class="btn-secondary" style="text-decoration: none; display: flex; align-items: center; justify-content: center;">
                        Cancel
                    </a>
                </div>
            </form>
        </div>

        <!-- Sticky Live Preview Card -->
        <div class="preview-sticky">
            <div style="margin-bottom: 12px; font-weight: 700; color: var(--text-secondary); font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em;">
                👁️ Live Update Preview
            </div>

            <div class="preview-card-display">
                <div class="preview-header">
                    <div class="preview-avatar" id="prevIcon"><?= e($product['icon'] ?: '📦') ?></div>
                    <div>
                        <span class="preview-sku-badge" id="prevSku"><?= e($product['id']) ?></span>
                        <h3 class="preview-title" id="prevName"><?= e($product['name']) ?></h3>
                        <div style="font-size: 0.8rem; color: var(--text-secondary);" id="prevLocation">📍 <?= e($product['location'] ?: 'General Storage') ?></div>
                    </div>
                </div>

                <div class="preview-grid">
                    <div class="preview-item">
                        <small>Category</small>
                        <strong id="prevCategory"><?= e($product['category']) ?></strong>
                    </div>
                    <div class="preview-item">
                        <small>Unit Price</small>
                        <strong id="prevPrice" style="color: var(--primary-light);"><?= formatCurrency($product['price']) ?></strong>
                    </div>
                    <div class="preview-item">
                        <small>Stock In Hand</small>
                        <strong id="prevQty"><?= (int)$product['quantity'] ?> units</strong>
                    </div>
                    <div class="preview-item">
                        <small>Reorder Limit</small>
                        <strong id="prevMinStock"><?= (int)$product['min_stock'] ?> units</strong>
                    </div>
                </div>

                <div style="background: var(--bg-surface-elevated); padding: 14px; border-radius: var(--radius-md); display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 0.85rem; color: var(--text-secondary);">Stock Health:</span>
                    <?php $st = getStockStatus($product['quantity'], $product['min_stock']); ?>
                    <span class="status-badge <?= $st['class'] ?>" id="prevStatus">
                        <span class="status-badge-dot"></span>
                        <?= $st['label'] ?>
                    </span>
                </div>
            </div>
        </div>

    </div>

    <script>
        function updateLivePreview() {
            const nameVal = document.getElementById("productName").value.trim();
            const catVal = document.getElementById("productCategory").value;
            const qtyVal = parseInt(document.getElementById("productQty").value, 10) || 0;
            const minVal = parseInt(document.getElementById("productMinStock").value, 10) || 1;
            const priceVal = parseFloat(document.getElementById("productPrice").value) || 0;
            const iconVal = document.getElementById("productIcon").value;
            const locVal = document.getElementById("productLocation").value.trim();

            document.getElementById("prevName").textContent = nameVal || "Item Name Preview";
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

        ["productName", "productCategory", "productQty", "productMinStock", "productPrice", "productIcon", "productLocation"]
            .forEach(id => {
                const el = document.getElementById(id);
                if (el) {
                    el.addEventListener("input", updateLivePreview);
                    el.addEventListener("change", updateLivePreview);
                }
            });
    </script>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
