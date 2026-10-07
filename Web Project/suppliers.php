<?php
/**
 * WareTrack - Suppliers Directory (PHP + MySQL)
 * Vendor Management, Procurement Partner Directory, and Status Tracking
 */

require_once __DIR__ . '/includes/functions.php';

$currentPage = 'suppliers';
$pageTitle = 'Suppliers Directory';
$pageSubtitle = 'Manage logistics vendors, procurement partners, and supplier catalogues.';

$message = '';
$messageType = '';

// Handle Direct POST to add supplier
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_supplier') {
    $res = createSupplier($_POST);
    if ($res['success']) {
        $message = "Supplier '{$_POST['name']}' ({$res['id']}) successfully added to MySQL database.";
        $messageType = 'success';
    } else {
        $message = "Error adding supplier: " . $res['error'];
        $messageType = 'error';
    }
}

// Handle Direct GET to delete supplier
if (isset($_GET['delete_id'])) {
    $delRes = deleteSupplier($_GET['delete_id']);
    if ($delRes['success']) {
        $message = "Supplier ID '{$_GET['delete_id']}' has been removed from database.";
        $messageType = 'success';
    }
}

$categoryFilter = $_GET['category'] ?? 'All';
$statusFilter = $_GET['status'] ?? 'All';
$searchQuery = $_GET['search'] ?? '';

$suppliers = getSuppliersList($categoryFilter, $statusFilter, $searchQuery);
$nextSupplierId = generateNextSupplierId();

// Summary counts
$totalSuppliers = count($suppliers);
$activeCount = 0;
$preferredCount = 0;
foreach ($suppliers as $s) {
    if ($s['status'] === 'Active') $activeCount++;
    if ($s['status'] === 'Preferred') $preferredCount++;
}

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
        min-width: 260px;
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
        padding: 10px 14px;
        color: var(--text-primary);
        font-size: 0.9rem;
        outline: none;
        cursor: pointer;
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
        gap: 14px;
    }

    .chip-icon {
        width: 44px;
        height: 44px;
        border-radius: var(--radius-sm);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.4rem;
        background: rgba(99, 102, 241, 0.12);
    }

    .supplier-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .supplier-card {
        background: var(--bg-card);
        border: 1px solid var(--border-subtle);
        border-radius: var(--radius-md);
        padding: 22px;
        position: relative;
        transition: all var(--transition-base);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    .supplier-card:hover {
        border-color: var(--border-accent);
        transform: translateY(-3px);
        box-shadow: var(--shadow-md);
    }

    .card-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        margin-bottom: 12px;
    }

    .supplier-title {
        font-size: 1.15rem;
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: 4px;
    }

    .supplier-id-tag {
        font-family: monospace;
        font-size: 0.75rem;
        padding: 3px 8px;
        border-radius: var(--radius-sm);
        background: var(--bg-surface-elevated);
        color: var(--primary-light);
        border: 1px solid var(--border-subtle);
    }

    .supplier-details {
        margin: 14px 0;
        display: flex;
        flex-direction: column;
        gap: 8px;
        font-size: 0.88rem;
    }

    .detail-row {
        display: flex;
        align-items: center;
        gap: 10px;
        color: var(--text-secondary);
    }

    .detail-row span:first-child {
        width: 20px;
        text-align: center;
        font-size: 1rem;
    }

    .card-actions {
        display: flex;
        gap: 10px;
        margin-top: 18px;
        padding-top: 16px;
        border-top: 1px solid var(--border-subtle);
    }

    .badge-status {
        padding: 3px 10px;
        border-radius: var(--radius-full);
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .badge-preferred {
        background: rgba(168, 85, 247, 0.15);
        color: #c084fc;
        border: 1px solid rgba(168, 85, 247, 0.3);
    }

    .badge-active {
        background: var(--success-bg);
        color: var(--success);
        border: 1px solid var(--success-border);
    }

    .badge-on-hold {
        background: var(--warning-bg);
        color: var(--warning);
        border: 1px solid var(--warning-border);
    }

    /* Modal Styling */
    .modal-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.75);
        backdrop-filter: blur(8px);
        z-index: 1000;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    .modal-overlay.active {
        display: flex;
    }

    .modal-content {
        background: var(--bg-surface-elevated);
        border: 1px solid var(--border-hover);
        border-radius: var(--radius-lg);
        width: 100%;
        max-width: 580px;
        max-height: 90vh;
        overflow-y: auto;
        padding: 32px;
        box-shadow: var(--shadow-lg);
        animation: scaleUp 0.25s ease-out;
    }

    @keyframes scaleUp {
        from { transform: scale(0.95); opacity: 0; }
        to { transform: scale(1); opacity: 1; }
    }

    @media (max-width: 900px) {
        .summary-ribbon {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 600px) {
        .summary-ribbon {
            grid-template-columns: 1fr;
        }
        .supplier-grid {
            grid-template-columns: 1fr;
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

<!-- Summary Ribbon -->
<div class="summary-ribbon">
    <div class="ribbon-chip">
        <div class="chip-icon">🏢</div>
        <div>
            <h4 style="font-size: 1.3rem; font-weight: 700; color: var(--text-primary);"><?= $totalSuppliers ?></h4>
            <span style="font-size: 0.8rem; color: var(--text-secondary);">Total Vendors</span>
        </div>
    </div>
    <div class="ribbon-chip">
        <div class="chip-icon" style="background: var(--success-bg); color: var(--success);">✨</div>
        <div>
            <h4 style="font-size: 1.3rem; font-weight: 700; color: var(--text-primary);"><?= $activeCount ?></h4>
            <span style="font-size: 0.8rem; color: var(--text-secondary);">Active Partners</span>
        </div>
    </div>
    <div class="ribbon-chip">
        <div class="chip-icon" style="background: rgba(168, 85, 247, 0.15); color: #c084fc;">⭐</div>
        <div>
            <h4 style="font-size: 1.3rem; font-weight: 700; color: var(--text-primary);"><?= $preferredCount ?></h4>
            <span style="font-size: 0.8rem; color: var(--text-secondary);">Preferred Suppliers</span>
        </div>
    </div>
    <div class="ribbon-chip">
        <div class="chip-icon" style="background: rgba(6, 182, 212, 0.15); color: var(--info);">📦</div>
        <div>
            <h4 style="font-size: 1.3rem; font-weight: 700; color: var(--text-primary);">8 SKUs</h4>
            <span style="font-size: 0.8rem; color: var(--text-secondary);">Catalogue Linkages</span>
        </div>
    </div>
</div>

<!-- Controls Toolbar -->
<div class="page-toolbar">
    <form method="GET" action="suppliers.php" class="filter-controls" id="filterForm">
        <div class="search-box-wide">
            <span class="icon">🔍</span>
            <input type="text" name="search" placeholder="Search supplier name, contact, email..." value="<?= e($searchQuery) ?>" oninput="debounceSearch()">
        </div>

        <select name="category" class="filter-select" onchange="document.getElementById('filterForm').submit()">
            <option value="All" <?= $categoryFilter === 'All' ? 'selected' : '' ?>>All Categories</option>
            <option value="Machinery & Storage" <?= $categoryFilter === 'Machinery & Storage' ? 'selected' : '' ?>>Machinery & Storage</option>
            <option value="Electronics" <?= $categoryFilter === 'Electronics' ? 'selected' : '' ?>>Electronics</option>
            <option value="Furniture" <?= $categoryFilter === 'Furniture' ? 'selected' : '' ?>>Furniture</option>
            <option value="Safety" <?= $categoryFilter === 'Safety' ? 'selected' : '' ?>>Safety</option>
            <option value="Supplies" <?= $categoryFilter === 'Supplies' ? 'selected' : '' ?>>Supplies</option>
        </select>

        <select name="status" class="filter-select" onchange="document.getElementById('filterForm').submit()">
            <option value="All" <?= $statusFilter === 'All' ? 'selected' : '' ?>>All Statuses</option>
            <option value="Active" <?= $statusFilter === 'Active' ? 'selected' : '' ?>>Active Only</option>
            <option value="Preferred" <?= $statusFilter === 'Preferred' ? 'selected' : '' ?>>Preferred</option>
            <option value="On Hold" <?= $statusFilter === 'On Hold' ? 'selected' : '' ?>>On Hold</option>
        </select>

        <?php if (!empty($searchQuery) || $categoryFilter !== 'All' || $statusFilter !== 'All'): ?>
            <a href="suppliers.php" class="btn-secondary" style="padding: 10px 14px; font-size: 0.85rem; text-decoration: none;">Reset</a>
        <?php endif; ?>
    </form>

    <button onclick="openAddSupplierModal()" class="btn-primary" style="display: flex; align-items: center; gap: 8px;">
        <span>➕</span> Add Supplier
    </button>
</div>

<!-- Suppliers Directory Cards -->
<div class="supplier-grid">
    <?php if (empty($suppliers)): ?>
        <div style="grid-column: 1 / -1; text-align: center; padding: 60px 20px; background: var(--bg-card); border-radius: var(--radius-md); border: 1px dashed var(--border-hover);">
            <div style="font-size: 2.5rem; margin-bottom: 12px;">🏢</div>
            <h3 style="color: var(--text-primary); margin-bottom: 6px;">No Suppliers Found</h3>
            <p style="color: var(--text-secondary); margin-bottom: 20px;">No vendor partners match your current filter criteria.</p>
            <button onclick="openAddSupplierModal()" class="btn-primary">Register New Supplier</button>
        </div>
    <?php else: ?>
        <?php foreach ($suppliers as $s): ?>
            <?php 
                $badgeClass = 'badge-active';
                if ($s['status'] === 'Preferred') $badgeClass = 'badge-preferred';
                if ($s['status'] === 'On Hold') $badgeClass = 'badge-on-hold';
            ?>
            <div class="supplier-card">
                <div>
                    <div class="card-top">
                        <div>
                            <span class="supplier-id-tag"><?= e($s['id']) ?></span>
                            <h3 class="supplier-title" style="margin-top: 6px;"><?= e($s['name']) ?></h3>
                            <span style="font-size: 0.8rem; color: var(--primary-light); background: rgba(99,102,241,0.1); padding: 2px 8px; border-radius: 4px;"><?= e($s['category']) ?></span>
                        </div>
                        <span class="badge-status <?= $badgeClass ?>"><?= e($s['status']) ?></span>
                    </div>

                    <div class="supplier-details">
                        <div class="detail-row">
                            <span>👤</span>
                            <span><strong><?= e($s['contact_name']) ?></strong> (Lead Representative)</span>
                        </div>
                        <div class="detail-row">
                            <span>✉️</span>
                            <a href="mailto:<?= e($s['email']) ?>" style="color: var(--text-secondary); text-decoration: none;"><?= e($s['email']) ?></a>
                        </div>
                        <div class="detail-row">
                            <span>📞</span>
                            <span><?= e($s['phone']) ?></span>
                        </div>
                        <div class="detail-row">
                            <span>📍</span>
                            <span><?= e($s['address']) ?></span>
                        </div>
                    </div>
                </div>

                <div class="card-actions">
                    <a href="stock-in.php?supplier_id=<?= urlencode($s['id']) ?>" class="btn-primary" style="flex: 1; justify-content: center; font-size: 0.82rem; padding: 8px 12px; text-decoration: none;">
                        <span>📥</span> Stock In
                    </a>
                    <button onclick="confirmDeleteSupplier('<?= e($s['id']) ?>', '<?= e(addslashes($s['name'])) ?>')" class="btn-secondary" style="padding: 8px 12px; color: #ef4444;" title="Delete Supplier">
                        🗑️
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Add Supplier Modal -->
<div id="addSupplierModal" class="modal-overlay">
    <div class="modal-content">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <div>
                <h2 style="font-size: 1.4rem; color: var(--text-primary);">🏢 Register New Supplier</h2>
                <p style="font-size: 0.85rem; color: var(--text-secondary);">Direct insert to MySQL <code>suppliers</code> table</p>
            </div>
            <button onclick="closeAddSupplierModal()" style="background: none; border: none; font-size: 1.5rem; color: var(--text-muted); cursor: pointer;">&times;</button>
        </div>

        <form method="POST" action="suppliers.php" style="display: flex; flex-direction: column; gap: 16px;">
            <input type="hidden" name="action" value="add_supplier">

            <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 14px;">
                <div>
                    <label style="display: block; font-size: 0.82rem; font-weight: 600; color: var(--text-secondary); margin-bottom: 6px;">Supplier ID</label>
                    <input type="text" name="id" value="<?= e($nextSupplierId) ?>" required style="width: 100%; background: var(--bg-surface); border: 1px solid var(--border-subtle); border-radius: var(--radius-md); padding: 10px 14px; color: var(--primary-light); font-family: monospace; font-weight: bold;">
                </div>
                <div>
                    <label style="display: block; font-size: 0.82rem; font-weight: 600; color: var(--text-secondary); margin-bottom: 6px;">Company Name *</label>
                    <input type="text" name="name" placeholder="e.g. Acme Industrial Corp" required style="width: 100%; background: var(--bg-surface); border: 1px solid var(--border-subtle); border-radius: var(--radius-md); padding: 10px 14px; color: var(--text-primary);">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                <div>
                    <label style="display: block; font-size: 0.82rem; font-weight: 600; color: var(--text-secondary); margin-bottom: 6px;">Contact Person *</label>
                    <input type="text" name="contact_name" placeholder="Full Name" required style="width: 100%; background: var(--bg-surface); border: 1px solid var(--border-subtle); border-radius: var(--radius-md); padding: 10px 14px; color: var(--text-primary);">
                </div>
                <div>
                    <label style="display: block; font-size: 0.82rem; font-weight: 600; color: var(--text-secondary); margin-bottom: 6px;">Supply Category</label>
                    <select name="category" style="width: 100%; background: var(--bg-surface); border: 1px solid var(--border-subtle); border-radius: var(--radius-md); padding: 10px 14px; color: var(--text-primary);">
                        <option value="Electronics">Electronics</option>
                        <option value="Furniture">Furniture</option>
                        <option value="Machinery & Storage">Machinery & Storage</option>
                        <option value="Safety">Safety</option>
                        <option value="Supplies">Supplies</option>
                    </select>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                <div>
                    <label style="display: block; font-size: 0.82rem; font-weight: 600; color: var(--text-secondary); margin-bottom: 6px;">Email Address *</label>
                    <input type="email" name="email" placeholder="sales@vendor.com" required style="width: 100%; background: var(--bg-surface); border: 1px solid var(--border-subtle); border-radius: var(--radius-md); padding: 10px 14px; color: var(--text-primary);">
                </div>
                <div>
                    <label style="display: block; font-size: 0.82rem; font-weight: 600; color: var(--text-secondary); margin-bottom: 6px;">Phone Number *</label>
                    <input type="tel" name="phone" placeholder="+91 98765 00000" required style="width: 100%; background: var(--bg-surface); border: 1px solid var(--border-subtle); border-radius: var(--radius-md); padding: 10px 14px; color: var(--text-primary);">
                </div>
            </div>

            <div>
                <label style="display: block; font-size: 0.82rem; font-weight: 600; color: var(--text-secondary); margin-bottom: 6px;">Warehouse / Office Address</label>
                <input type="text" name="address" placeholder="Physical street address or industrial sector" required style="width: 100%; background: var(--bg-surface); border: 1px solid var(--border-subtle); border-radius: var(--radius-md); padding: 10px 14px; color: var(--text-primary);">
            </div>

            <div>
                <label style="display: block; font-size: 0.82rem; font-weight: 600; color: var(--text-secondary); margin-bottom: 6px;">Partnership Status</label>
                <select name="status" style="width: 100%; background: var(--bg-surface); border: 1px solid var(--border-subtle); border-radius: var(--radius-md); padding: 10px 14px; color: var(--text-primary);">
                    <option value="Active">Active</option>
                    <option value="Preferred">Preferred Partner</option>
                    <option value="On Hold">On Hold / Under Review</option>
                </select>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 14px;">
                <button type="button" onclick="closeAddSupplierModal()" class="btn-secondary">Cancel</button>
                <button type="submit" class="btn-primary">Save to Database</button>
            </div>
        </form>
    </div>
</div>

<script>
    let searchTimeout = null;
    function debounceSearch() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            document.getElementById('filterForm').submit();
        }, 500);
    }

    function openAddSupplierModal() {
        document.getElementById('addSupplierModal').classList.add('active');
    }

    function closeAddSupplierModal() {
        document.getElementById('addSupplierModal').classList.remove('active');
    }

    function confirmDeleteSupplier(id, name) {
        if (confirm(`Are you sure you want to remove supplier "${name}" (${id}) from MySQL? Associated products will be unlinked.`)) {
            window.location.href = `suppliers.php?delete_id=${encodeURIComponent(id)}`;
        }
    }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
