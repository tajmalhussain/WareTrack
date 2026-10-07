<?php
/**
 * WareTrack - Stock In Operations (PHP + MySQL)
 * Intake Procurement Shipments, Batch Receiving, and Direct MySQL Stock Increment
 */

require_once __DIR__ . '/includes/functions.php';

$currentPage = 'stock-in';
$pageTitle = 'Stock Inward (Receive)';
$pageSubtitle = 'Record inbound purchase orders, replenish product inventory, and verify consignments.';

$message = '';
$messageType = '';

// Handle Direct POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'process_stock_in') {
    $productId  = trim($_POST['product_id'] ?? '');
    $quantity   = (int)($_POST['quantity'] ?? 0);
    $supplierId = !empty($_POST['supplier_id']) ? trim($_POST['supplier_id']) : null;
    $refNo      = trim($_POST['reference_no'] ?? '');
    $unitPrice  = !empty($_POST['unit_price']) ? (float)$_POST['unit_price'] : null;
    $notes      = trim($_POST['notes'] ?? '');

    $res = processStockIn($productId, $quantity, $supplierId, $refNo, $unitPrice, $notes);
    if ($res['success']) {
        $message = "Successfully received +{$quantity} units of '{$res['product']}' (Ref: {$res['ref']}). New warehouse stock: {$res['newQty']} units.";
        $messageType = 'success';
    } else {
        $message = "Inward operation failed: " . $res['error'];
        $messageType = 'error';
    }
}

// Fetch dependencies
$products = getProductsList('All', '', 'All');
$suppliers = getSuppliersList('All', 'All', '');
$recentInward = getStockTransactions('IN', 15, '');

// Preselected product or supplier from query string
$selectedProdId = $_GET['product_id'] ?? ($products[0]['id'] ?? '');
$selectedSuppId = $_GET['supplier_id'] ?? '';

// Generate new reference ID
$defaultPoRef = 'PO-' . date('Y') . '-' . rand(1000, 9999);

require_once __DIR__ . '/includes/header.php';
?>

<style>
    .stock-grid-layout {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 28px;
        align-items: start;
        margin-bottom: 32px;
    }

    .form-panel {
        background: var(--bg-card);
        border: 1px solid var(--border-subtle);
        border-radius: var(--radius-lg);
        padding: 30px;
        box-shadow: var(--shadow-sm);
    }

    .preview-panel {
        background: var(--bg-card);
        border: 1px solid var(--border-subtle);
        border-radius: var(--radius-lg);
        padding: 30px;
        position: sticky;
        top: 24px;
    }

    .form-section-title {
        font-size: 1.15rem;
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: 6px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .form-section-desc {
        font-size: 0.85rem;
        color: var(--text-secondary);
        margin-bottom: 24px;
    }

    .field-row {
        margin-bottom: 18px;
    }

    .field-row label {
        display: block;
        font-size: 0.82rem;
        font-weight: 600;
        color: var(--text-secondary);
        margin-bottom: 6px;
    }

    .field-row input,
    .field-row select,
    .field-row textarea {
        width: 100%;
        background: var(--bg-surface-elevated);
        border: 1px solid var(--border-subtle);
        border-radius: var(--radius-md);
        padding: 11px 14px;
        color: var(--text-primary);
        font-size: 0.92rem;
        outline: none;
        transition: all var(--transition-fast);
    }

    .field-row input:focus,
    .field-row select:focus,
    .field-row textarea:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px var(--primary-glow);
    }

    .qty-quick-buttons {
        display: flex;
        gap: 8px;
        margin-top: 8px;
    }

    .qty-pill {
        background: var(--bg-surface);
        border: 1px solid var(--border-subtle);
        border-radius: var(--radius-sm);
        padding: 5px 12px;
        font-size: 0.8rem;
        color: var(--text-secondary);
        cursor: pointer;
        transition: all var(--transition-fast);
    }

    .qty-pill:hover {
        border-color: var(--primary);
        color: var(--primary-light);
        background: rgba(99, 102, 241, 0.1);
    }

    .preview-stat-box {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 14px;
        margin: 20px 0;
    }

    .p-stat-card {
        background: var(--bg-surface-elevated);
        border: 1px solid var(--border-subtle);
        border-radius: var(--radius-md);
        padding: 16px;
        text-align: center;
    }

    .p-stat-card small {
        font-size: 0.75rem;
        color: var(--text-muted);
        display: block;
        text-transform: uppercase;
        margin-bottom: 4px;
    }

    .p-stat-card strong {
        font-size: 1.4rem;
        color: var(--text-primary);
    }

    @media (max-width: 960px) {
        .stock-grid-layout {
            grid-template-columns: 1fr;
        }
        .preview-panel {
            position: static;
        }
    }
</style>

<!-- Alert Banner -->
<?php if (!empty($message)): ?>
    <div style="background: <?= $messageType === 'success' ? 'var(--success-bg)' : 'var(--danger-bg)' ?>; border: 1px solid <?= $messageType === 'success' ? 'var(--success-border)' : 'var(--danger-border)' ?>; color: <?= $messageType === 'success' ? 'var(--success)' : 'var(--danger)' ?>; padding: 14px 20px; border-radius: var(--radius-md); margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between;">
        <div style="display: flex; align-items: center; gap: 10px;">
            <span><?= $messageType === 'success' ? '✅' : '⚠️' ?></span>
            <span><?= e($message) ?></span>
        </div>
        <button onclick="this.parentElement.remove()" style="background: none; border: none; color: inherit; font-size: 1.2rem; cursor: pointer;">&times;</button>
    </div>
<?php endif; ?>

<div class="stock-grid-layout">

    <!-- Form Column -->
    <div class="form-panel">
        <div class="form-section-title">
            <span>📥</span> Receive Inward Consignment
        </div>
        <div class="form-section-desc">
            Directly increments product stock in MySQL and appends to transaction log.
        </div>

        <form method="POST" action="stock-in.php" id="stockInForm">
            <input type="hidden" name="action" value="process_stock_in">

            <div class="field-row">
                <label for="productSelect">Select Product SKU & Item *</label>
                <select id="productSelect" name="product_id" required onchange="updateProductPreview()">
                    <?php foreach ($products as $p): ?>
                        <option value="<?= e($p['id']) ?>" 
                                data-name="<?= e($p['name']) ?>" 
                                data-qty="<?= (int)$p['quantity'] ?>" 
                                data-min="<?= (int)$p['min_stock'] ?>" 
                                data-price="<?= (float)$p['price'] ?>" 
                                data-loc="<?= e($p['location']) ?>" 
                                data-icon="<?= e($p['icon']) ?>" 
                                data-cat="<?= e($p['category']) ?>"
                                data-supplier="<?= e($p['supplier_id'] ?? '') ?>"
                                <?= $p['id'] === $selectedProdId ? 'selected' : '' ?>>
                            <?= e($p['id']) ?> - <?= e($p['name']) ?> (Current: <?= (int)$p['quantity'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field-row">
                <label for="supplierSelect">Origin Supplier / Vendor *</label>
                <select id="supplierSelect" name="supplier_id">
                    <option value="">-- General / Warehouse Direct --</option>
                    <?php foreach ($suppliers as $s): ?>
                        <option value="<?= e($s['id']) ?>" <?= ($s['id'] === $selectedSuppId) ? 'selected' : '' ?>>
                            <?= e($s['id']) ?> - <?= e($s['name']) ?> (<?= e($s['category']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                <div class="field-row">
                    <label for="quantityInput">Inward Quantity (Units) *</label>
                    <input type="number" id="quantityInput" name="quantity" min="1" value="20" required oninput="calculateProjectedStock()">
                    <div class="qty-quick-buttons">
                        <button type="button" class="qty-pill" onclick="setQty(10)">+10</button>
                        <button type="button" class="qty-pill" onclick="setQty(25)">+25</button>
                        <button type="button" class="qty-pill" onclick="setQty(50)">+50</button>
                        <button type="button" class="qty-pill" onclick="setQty(100)">+100</button>
                    </div>
                </div>

                <div class="field-row">
                    <label for="refNoInput">Purchase Order / Ref # *</label>
                    <input type="text" id="refNoInput" name="reference_no" value="<?= e($defaultPoRef) ?>" required>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                <div class="field-row">
                    <label for="unitPriceInput">Unit Purchase Price (₹)</label>
                    <input type="number" step="0.01" id="unitPriceInput" name="unit_price" placeholder="Auto-fetched" oninput="calculateProjectedStock()">
                </div>

                <div class="field-row">
                    <label for="dateInput">Consignment Date</label>
                    <input type="text" id="dateInput" value="<?= date('Y-m-d H:i') ?>" readonly style="color: var(--text-muted);">
                </div>
            </div>

            <div class="field-row">
                <label for="notesInput">Inspection & Receiving Remarks</label>
                <textarea id="notesInput" name="notes" rows="2" placeholder="e.g. Inspected on Dock 3, packaging intact, QA barcode attached."></textarea>
            </div>

            <button type="submit" class="btn-primary" style="width: 100%; padding: 14px; font-size: 1rem; justify-content: center; margin-top: 10px;">
                <span>📥</span> Confirm & Post Stock In (MySQL)
            </button>
        </form>
    </div>

    <!-- Live Preview Column -->
    <div class="preview-panel">
        <div class="form-section-title">
            <span>🔍</span> Stock Impact Telemetry
        </div>
        <div class="form-section-desc">
            Live preview of product status after inward transaction.
        </div>

        <div style="display: flex; align-items: center; gap: 16px; padding: 16px; background: var(--bg-surface-elevated); border-radius: var(--radius-md); border: 1px solid var(--border-subtle);">
            <div id="previewIcon" style="font-size: 2.2rem; width: 56px; height: 56px; background: rgba(99,102,241,0.15); border-radius: var(--radius-sm); display: flex; align-items: center; justify-content: center;">📦</div>
            <div>
                <h3 id="previewName" style="color: var(--text-primary); font-size: 1.05rem; margin-bottom: 4px;">Loading...</h3>
                <div style="font-size: 0.8rem; color: var(--text-muted); display: flex; gap: 12px;">
                    <span id="previewSku">SKU: --</span>
                    <span id="previewCat">Category: --</span>
                </div>
            </div>
        </div>

        <div class="preview-stat-box">
            <div class="p-stat-card">
                <small>Current In-Hand</small>
                <strong id="previewCurrentQty">0</strong>
                <span style="font-size: 0.75rem; color: var(--text-muted); display: block;">units</span>
            </div>
            <div class="p-stat-card" style="border-color: var(--success-border); background: var(--success-bg);">
                <small style="color: var(--success);">Projected Stock</small>
                <strong id="previewProjectedQty" style="color: var(--success);">0</strong>
                <span style="font-size: 0.75rem; color: var(--success); display: block;">units (+<span id="previewAddedQty">0</span>)</span>
            </div>
        </div>

        <div style="background: var(--bg-surface-elevated); border-radius: var(--radius-md); padding: 16px; border: 1px solid var(--border-subtle); display: flex; flex-direction: column; gap: 10px; font-size: 0.86rem;">
            <div style="display: flex; justify-content: space-between;">
                <span style="color: var(--text-secondary);">Bin Location:</span>
                <strong id="previewLoc" style="color: var(--text-primary);">--</strong>
            </div>
            <div style="display: flex; justify-content: space-between;">
                <span style="color: var(--text-secondary);">Unit Valuation:</span>
                <strong id="previewPrice" style="color: var(--text-primary);">₹0</strong>
            </div>
            <div style="display: flex; justify-content: space-between; border-top: 1px solid var(--border-subtle); padding-top: 8px;">
                <span style="color: var(--text-secondary);">Total Consignment Value:</span>
                <strong id="previewTotalValue" style="color: var(--primary-light);">₹0</strong>
            </div>
        </div>
    </div>

</div>

<!-- Recent Inward Shipments Ledger -->
<section class="table-section">
    <div class="section-header">
        <div>
            <h2>Recent Inward Shipments</h2>
            <p>Direct audit log of incoming purchase consignments in MySQL</p>
        </div>
    </div>

    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Ref #</th>
                    <th>Product Item</th>
                    <th>Supplier Partner</th>
                    <th>Units In</th>
                    <th>Unit Cost</th>
                    <th>Date Received</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentInward)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 30px; color: var(--text-muted);">
                            No inward shipments recorded yet.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recentInward as $tx): ?>
                        <tr>
                            <td>
                                <span style="font-family: monospace; font-weight: 600; color: var(--primary-light);"><?= e($tx['reference_no']) ?></span>
                            </td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <span style="font-size: 1.2rem;"><?= e($tx['product_icon'] ?? '📦') ?></span>
                                    <div>
                                        <div style="font-weight: 600; color: var(--text-primary);"><?= e($tx['product_name']) ?></div>
                                        <small style="color: var(--text-muted);"><?= e($tx['product_id']) ?></small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?= !empty($tx['supplier_name']) ? e($tx['supplier_name']) : '<span style="color:var(--text-muted)">General Vendor</span>' ?>
                            </td>
                            <td>
                                <span class="badge in-stock" style="font-weight: 700;">+<?= (int)$tx['quantity'] ?></span>
                            </td>
                            <td>
                                <?= formatCurrency($tx['unit_price']) ?>
                            </td>
                            <td style="color: var(--text-secondary); font-size: 0.85rem;">
                                <?= date('M d, Y H:i', strtotime($tx['created_at'])) ?>
                            </td>
                            <td style="color: var(--text-muted); font-size: 0.85rem;">
                                <?= e($tx['notes'] ?: 'Stock intake verified') ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<script>
    function setQty(val) {
        document.getElementById('quantityInput').value = val;
        calculateProjectedStock();
    }

    function updateProductPreview() {
        const select = document.getElementById('productSelect');
        const opt = select.options[select.selectedIndex];
        if (!opt) return;

        const name = opt.getAttribute('data-name');
        const qty = parseInt(opt.getAttribute('data-qty') || '0', 10);
        const price = parseFloat(opt.getAttribute('data-price') || '0');
        const loc = opt.getAttribute('data-loc');
        const icon = opt.getAttribute('data-icon');
        const cat = opt.getAttribute('data-cat');
        const sku = opt.value;
        const suppId = opt.getAttribute('data-supplier');

        document.getElementById('previewIcon').textContent = icon || '📦';
        document.getElementById('previewName').textContent = name;
        document.getElementById('previewSku').textContent = 'SKU: ' + sku;
        document.getElementById('previewCat').textContent = 'Category: ' + cat;
        document.getElementById('previewLoc').textContent = loc;
        document.getElementById('previewCurrentQty').textContent = qty;
        document.getElementById('previewPrice').textContent = '₹' + price.toLocaleString('en-IN');
        document.getElementById('unitPriceInput').value = price;

        // Auto-select linked supplier if available
        if (suppId) {
            const suppSelect = document.getElementById('supplierSelect');
            if (suppSelect) suppSelect.value = suppId;
        }

        calculateProjectedStock();
    }

    function calculateProjectedStock() {
        const select = document.getElementById('productSelect');
        const opt = select.options[select.selectedIndex];
        if (!opt) return;

        const currentQty = parseInt(opt.getAttribute('data-qty') || '0', 10);
        const addedQty = parseInt(document.getElementById('quantityInput').value || '0', 10);
        const unitPrice = parseFloat(document.getElementById('unitPriceInput').value || '0');

        const projected = currentQty + addedQty;
        document.getElementById('previewAddedQty').textContent = addedQty;
        document.getElementById('previewProjectedQty').textContent = projected;

        const totalBatchVal = addedQty * unitPrice;
        document.getElementById('previewTotalValue').textContent = '₹' + Math.round(totalBatchVal).toLocaleString('en-IN');
    }

    // Init on load
    document.addEventListener('DOMContentLoaded', updateProductPreview);
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
