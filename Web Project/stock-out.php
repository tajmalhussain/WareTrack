<?php
/**
 * WareTrack - Stock Out Operations (PHP + MySQL)
 * Warehouse Dispatch, Customer Order Fulfillment, Damage Write-offs, and Negative Stock Prevention
 */

require_once __DIR__ . '/includes/functions.php';

$currentPage = 'stock-out';
$pageTitle = 'Stock Outward (Dispatch)';
$pageSubtitle = 'Authorize inventory release, process outbound orders, and update stock balances.';

$message = '';
$messageType = '';

// Handle Direct POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'process_stock_out') {
    $productId  = trim($_POST['product_id'] ?? '');
    $quantity   = (int)($_POST['quantity'] ?? 0);
    $reason     = trim($_POST['reason'] ?? 'Customer Order');
    $recipient  = trim($_POST['recipient'] ?? '');
    $refNo      = trim($_POST['reference_no'] ?? '');
    $notes      = trim($_POST['notes'] ?? '');

    $res = processStockOut($productId, $quantity, $reason, $recipient, $refNo, $notes);
    if ($res['success']) {
        $warningTxt = $res['isLow'] ? ' ⚠️ Warning: Item is now below safe reorder threshold!' : '';
        $message = "Successfully dispatched -{$quantity} units of '{$res['product']}' (Ref: {$res['ref']}). Remaining stock: {$res['newQty']} units.{$warningTxt}";
        $messageType = $res['isLow'] ? 'warning' : 'success';
    } else {
        $message = "Dispatch blocked: " . $res['error'];
        $messageType = 'error';
    }
}

// Fetch dependencies
$products = getProductsList('All', '', 'All');
$recentOutward = getStockTransactions('OUT', 15, '');

// Preselected product
$selectedProdId = $_GET['product_id'] ?? ($products[0]['id'] ?? '');

// Generate new reference ID
$defaultSoRef = 'SO-' . date('Y') . '-' . rand(1000, 9999);

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

    .stock-warning-box {
        background: rgba(239, 68, 68, 0.15);
        border: 1px solid rgba(239, 68, 68, 0.4);
        color: #f87171;
        padding: 12px 16px;
        border-radius: var(--radius-md);
        font-size: 0.85rem;
        display: none;
        align-items: center;
        gap: 10px;
        margin-top: 14px;
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
    <div style="background: <?= $messageType === 'success' ? 'var(--success-bg)' : ($messageType === 'warning' ? 'var(--warning-bg)' : 'var(--danger-bg)') ?>; border: 1px solid <?= $messageType === 'success' ? 'var(--success-border)' : ($messageType === 'warning' ? 'var(--warning-border)' : 'var(--danger-border)') ?>; color: <?= $messageType === 'success' ? 'var(--success)' : ($messageType === 'warning' ? 'var(--warning)' : 'var(--danger)') ?>; padding: 14px 20px; border-radius: var(--radius-md); margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between;">
        <div style="display: flex; align-items: center; gap: 10px;">
            <span><?= $messageType === 'success' ? '✅' : ($messageType === 'warning' ? '⚡' : '⚠️') ?></span>
            <span><?= e($message) ?></span>
        </div>
        <button onclick="this.parentElement.remove()" style="background: none; border: none; color: inherit; font-size: 1.2rem; cursor: pointer;">&times;</button>
    </div>
<?php endif; ?>

<div class="stock-grid-layout">

    <!-- Form Column -->
    <div class="form-panel">
        <div class="form-section-title">
            <span>📤</span> Authorize Stock Dispatch
        </div>
        <div class="form-section-desc">
            Deducts quantity from MySQL inventory with real-time stock check.
        </div>

        <form method="POST" action="stock-out.php" id="stockOutForm" onsubmit="return validateStockOutForm()">
            <input type="hidden" name="action" value="process_stock_out">

            <div class="field-row">
                <label for="productSelect">Select Product SKU to Dispatch *</label>
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
                                <?= $p['id'] === $selectedProdId ? 'selected' : '' ?>>
                            <?= e($p['id']) ?> - <?= e($p['name']) ?> (Available: <?= (int)$p['quantity'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                <div class="field-row">
                    <label for="quantityInput">Dispatch Quantity *</label>
                    <input type="number" id="quantityInput" name="quantity" min="1" value="2" required oninput="calculateRemainingStock()">
                    <div class="qty-quick-buttons">
                        <button type="button" class="qty-pill" onclick="setQty(1)">1</button>
                        <button type="button" class="qty-pill" onclick="setQty(5)">5</button>
                        <button type="button" class="qty-pill" onclick="setQty(10)">10</button>
                        <button type="button" class="qty-pill" onclick="setMaxQty()">Max</button>
                    </div>
                </div>

                <div class="field-row">
                    <label for="reasonSelect">Dispatch Purpose / Reason *</label>
                    <select id="reasonSelect" name="reason" required>
                        <option value="Customer Order">Customer Order Fulfillment</option>
                        <option value="Internal Transfer">Internal Dept Transfer</option>
                        <option value="Damaged / Scrap">Damaged / Scrap Write-off</option>
                        <option value="Expired Goods">Expired Goods Disposal</option>
                        <option value="Sample / Demo">Demonstration Sample</option>
                        <option value="Return to Vendor">Return to Supplier</option>
                    </select>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                <div class="field-row">
                    <label for="refNoInput">Dispatch Order / Waybill # *</label>
                    <input type="text" id="refNoInput" name="reference_no" value="<?= e($defaultSoRef) ?>" required>
                </div>

                <div class="field-row">
                    <label for="recipientInput">Recipient / Destination Unit *</label>
                    <input type="text" id="recipientInput" name="recipient" placeholder="e.g. Apex Corp Mumbai / Dept C" required>
                </div>
            </div>

            <div class="field-row">
                <label for="notesInput">Dispatch Notes & Carrier</label>
                <textarea id="notesInput" name="notes" rows="2" placeholder="e.g. Released via BlueDart air-express, signed off by bay supervisor."></textarea>
            </div>

            <div id="stockValidationAlert" class="stock-warning-box">
                <span>⚠️</span>
                <span id="stockValidationMsg">Requested quantity exceeds available stock!</span>
            </div>

            <button type="submit" id="submitDispatchBtn" class="btn-primary" style="width: 100%; padding: 14px; font-size: 1rem; justify-content: center; margin-top: 10px; background: linear-gradient(135deg, #ef4444 0%, #f97316 100%);">
                <span>📤</span> Authorize & Release Stock Out (MySQL)
            </button>
        </form>
    </div>

    <!-- Live Preview Column -->
    <div class="preview-panel">
        <div class="form-section-title">
            <span>🛡️</span> Inventory Reserve Check
        </div>
        <div class="form-section-desc">
            Monitors reorder limit to prevent unexpected warehouse stock-outs.
        </div>

        <div style="display: flex; align-items: center; gap: 16px; padding: 16px; background: var(--bg-surface-elevated); border-radius: var(--radius-md); border: 1px solid var(--border-subtle);">
            <div id="previewIcon" style="font-size: 2.2rem; width: 56px; height: 56px; background: rgba(239,68,68,0.15); border-radius: var(--radius-sm); display: flex; align-items: center; justify-content: center;">📦</div>
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
                <small>Available Stock</small>
                <strong id="previewCurrentQty">0</strong>
                <span style="font-size: 0.75rem; color: var(--text-muted); display: block;">units in bay</span>
            </div>
            <div class="p-stat-card" id="projectedCard">
                <small id="projectedLabel">Remaining Stock</small>
                <strong id="previewRemainingQty">0</strong>
                <span style="font-size: 0.75rem; display: block;" id="projectedSub">-<span id="previewDeductedQty">0</span> units</span>
            </div>
        </div>

        <div style="background: var(--bg-surface-elevated); border-radius: var(--radius-md); padding: 16px; border: 1px solid var(--border-subtle); display: flex; flex-direction: column; gap: 10px; font-size: 0.86rem;">
            <div style="display: flex; justify-content: space-between;">
                <span style="color: var(--text-secondary);">Bin Location:</span>
                <strong id="previewLoc" style="color: var(--text-primary);">--</strong>
            </div>
            <div style="display: flex; justify-content: space-between;">
                <span style="color: var(--text-secondary);">Safety Reorder Limit:</span>
                <strong id="previewMinStock" style="color: var(--warning);">10 units</strong>
            </div>
            <div style="display: flex; justify-content: space-between; border-top: 1px solid var(--border-subtle); padding-top: 8px;">
                <span style="color: var(--text-secondary);">Dispatch Asset Value:</span>
                <strong id="previewDispatchValue" style="color: #f87171;">₹0</strong>
            </div>
        </div>
    </div>

</div>

<!-- Recent Outward Dispatches Ledger -->
<section class="table-section">
    <div class="section-header">
        <div>
            <h2>Recent Outward Dispatches</h2>
            <p>Direct audit log of outgoing shipments and fulfillment releases in MySQL</p>
        </div>
    </div>

    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Ref #</th>
                    <th>Product Item</th>
                    <th>Purpose / Reason</th>
                    <th>Recipient / Customer</th>
                    <th>Units Out</th>
                    <th>Date Dispatched</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentOutward)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 30px; color: var(--text-muted);">
                            No outward dispatches recorded yet.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recentOutward as $tx): ?>
                        <tr>
                            <td>
                                <span style="font-family: monospace; font-weight: 600; color: #f87171;"><?= e($tx['reference_no']) ?></span>
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
                                <span style="font-size: 0.85rem; color: var(--primary-light); background: rgba(99,102,241,0.12); padding: 3px 8px; border-radius: 4px;">
                                    <?= e($tx['reason']) ?>
                                </span>
                            </td>
                            <td>
                                <strong style="color: var(--text-primary);"><?= e($tx['recipient'] ?: 'Direct Dispatch') ?></strong>
                            </td>
                            <td>
                                <span class="badge out-of-stock" style="font-weight: 700; background: rgba(239,68,68,0.15); color: #f87171; border: 1px solid rgba(239,68,68,0.3);">
                                    -<?= (int)$tx['quantity'] ?>
                                </span>
                            </td>
                            <td style="color: var(--text-secondary); font-size: 0.85rem;">
                                <?= date('M d, Y H:i', strtotime($tx['created_at'])) ?>
                            </td>
                            <td style="color: var(--text-muted); font-size: 0.85rem;">
                                <?= e($tx['notes'] ?: 'Released from warehouse dock') ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<script>
    let currentAvailable = 0;

    function setQty(val) {
        document.getElementById('quantityInput').value = val;
        calculateRemainingStock();
    }

    function setMaxQty() {
        document.getElementById('quantityInput').value = Math.max(1, currentAvailable);
        calculateRemainingStock();
    }

    function updateProductPreview() {
        const select = document.getElementById('productSelect');
        const opt = select.options[select.selectedIndex];
        if (!opt) return;

        const name = opt.getAttribute('data-name');
        currentAvailable = parseInt(opt.getAttribute('data-qty') || '0', 10);
        const minStock = parseInt(opt.getAttribute('data-min') || '10', 10);
        const price = parseFloat(opt.getAttribute('data-price') || '0');
        const loc = opt.getAttribute('data-loc');
        const icon = opt.getAttribute('data-icon');
        const cat = opt.getAttribute('data-cat');
        const sku = opt.value;

        document.getElementById('previewIcon').textContent = icon || '📦';
        document.getElementById('previewName').textContent = name;
        document.getElementById('previewSku').textContent = 'SKU: ' + sku;
        document.getElementById('previewCat').textContent = 'Category: ' + cat;
        document.getElementById('previewLoc').textContent = loc;
        document.getElementById('previewCurrentQty').textContent = currentAvailable;
        document.getElementById('previewMinStock').textContent = minStock + ' units';

        calculateRemainingStock();
    }

    function calculateRemainingStock() {
        const select = document.getElementById('productSelect');
        const opt = select.options[select.selectedIndex];
        if (!opt) return;

        const minStock = parseInt(opt.getAttribute('data-min') || '10', 10);
        const price = parseFloat(opt.getAttribute('data-price') || '0');
        const requested = parseInt(document.getElementById('quantityInput').value || '0', 10);

        const remaining = currentAvailable - requested;
        document.getElementById('previewDeductedQty').textContent = requested;
        document.getElementById('previewRemainingQty').textContent = Math.max(0, remaining);

        const dispatchVal = requested * price;
        document.getElementById('previewDispatchValue').textContent = '₹' + Math.round(dispatchVal).toLocaleString('en-IN');

        const card = document.getElementById('projectedCard');
        const alertBox = document.getElementById('stockValidationAlert');
        const submitBtn = document.getElementById('submitDispatchBtn');

        if (requested > currentAvailable) {
            alertBox.style.display = 'flex';
            document.getElementById('stockValidationMsg').textContent = `Cannot dispatch ${requested} units! Only ${currentAvailable} units are in stock.`;
            card.style.background = 'rgba(239, 68, 68, 0.2)';
            card.style.borderColor = 'rgba(239, 68, 68, 0.5)';
            document.getElementById('previewRemainingQty').style.color = '#ef4444';
            submitBtn.disabled = true;
            submitBtn.style.opacity = '0.5';
            submitBtn.style.cursor = 'not-allowed';
        } else {
            alertBox.style.display = 'none';
            submitBtn.disabled = false;
            submitBtn.style.opacity = '1';
            submitBtn.style.cursor = 'pointer';

            if (remaining <= minStock) {
                card.style.background = 'rgba(245, 158, 11, 0.15)';
                card.style.borderColor = 'rgba(245, 158, 11, 0.4)';
                document.getElementById('previewRemainingQty').style.color = '#f59e0b';
            } else {
                card.style.background = 'var(--bg-surface-elevated)';
                card.style.borderColor = 'var(--border-subtle)';
                document.getElementById('previewRemainingQty').style.color = 'var(--text-primary)';
            }
        }
    }

    function validateStockOutForm() {
        const requested = parseInt(document.getElementById('quantityInput').value || '0', 10);
        if (requested > currentAvailable) {
            alert(`Insufficient stock! You cannot dispatch ${requested} units because only ${currentAvailable} units exist in stock.`);
            return false;
        }
        return true;
    }

    // Init on load
    document.addEventListener('DOMContentLoaded', updateProductPreview);
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
