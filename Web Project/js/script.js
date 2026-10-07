/**
 * WareTrack - Dynamic Warehouse Management JavaScript (Stage 2)
 * Connects to PHP / MySQL backend with real-time AJAX operations,
 * instant DOM updates, and graceful client-side fallback.
 */

// Default Seed Products (Used for client-side demo fallback)
const INITIAL_PRODUCTS = [
    {
        id: "PRD-1001",
        name: "Ergonomic Mesh Task Chair",
        category: "Furniture",
        quantity: 45,
        minStock: 15,
        price: 8499,
        location: "Aisle 3 - Bay B",
        icon: "🪑"
    },
    {
        id: "PRD-1002",
        name: "Wireless 2D Barcode Scanner",
        category: "Electronics",
        quantity: 6,
        minStock: 12,
        price: 3499,
        location: "Aisle 1 - Bin 04",
        icon: "📱"
    },
    {
        id: "PRD-1003",
        name: "Heavy Duty Steel Pallet Rack",
        category: "Storage",
        quantity: 24,
        minStock: 5,
        price: 15999,
        location: "Warehouse Yard C",
        icon: "🏗️"
    },
    {
        id: "PRD-1004",
        name: "Direct Thermal Shipping Labels",
        category: "Supplies",
        quantity: 4,
        minStock: 20,
        price: 499,
        location: "Packaging Bay 2",
        icon: "🏷️"
    },
    {
        id: "PRD-1005",
        name: "Industrial Hydraulic Pallet Jack",
        category: "Machinery",
        quantity: 12,
        minStock: 4,
        price: 26500,
        location: "Dock 4 Equipment",
        icon: "🚜"
    },
    {
        id: "PRD-1006",
        name: "Cat6 Ethernet Spool (305m)",
        category: "Electronics",
        quantity: 38,
        minStock: 10,
        price: 6200,
        location: "Aisle 2 - Shelf A",
        icon: "🔌"
    },
    {
        id: "PRD-1007",
        name: "ANSI High-Visibility Vests (10pk)",
        category: "Safety",
        quantity: 0,
        minStock: 15,
        price: 1299,
        location: "Safety Locker B",
        icon: "🦺"
    },
    {
        id: "PRD-1008",
        name: "ESD Antistatic Cleanroom Desk",
        category: "Furniture",
        quantity: 18,
        minStock: 5,
        price: 19800,
        location: "Assembly Line 1",
        icon: "🔬"
    }
];

const STORAGE_KEY = "waretrack_inventory_items";
const THEME_KEY = "waretrack_theme";

// Format Currency in INR
function formatCurrency(amount) {
    return new Intl.NumberFormat('en-IN', {
        style: 'currency',
        currency: 'INR',
        maximumFractionDigits: 0
    }).format(amount);
}

// LocalStorage Getters & Setters (Fallback Mode)
function getProducts() {
    try {
        const stored = localStorage.getItem(STORAGE_KEY);
        if (stored) {
            return JSON.parse(stored);
        }
    } catch (e) {
        console.error("Failed to read localStorage:", e);
    }
    localStorage.setItem(STORAGE_KEY, JSON.stringify(INITIAL_PRODUCTS));
    return [...INITIAL_PRODUCTS];
}

function saveProducts(products) {
    try {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(products));
    } catch (e) {
        console.error("Failed to save to localStorage:", e);
    }
}

// Quick Toast Notification
function showToast(title, description, type = "info") {
    let container = document.getElementById("toastContainer");
    if (!container) {
        container = document.createElement("div");
        container.id = "toastContainer";
        container.className = "toast-container";
        document.body.appendChild(container);
    }

    const toast = document.createElement("div");
    toast.className = `toast toast-${type}`;
    
    let icon = "🔔";
    if (type === "success") icon = "✅";
    if (type === "warning") icon = "⚠️";
    if (type === "danger") icon = "🚨";

    toast.innerHTML = `
        <span class="toast-icon">${icon}</span>
        <div class="toast-body">
            <div class="toast-title">${escapeHtml(title)}</div>
            <div class="toast-desc">${escapeHtml(description)}</div>
        </div>
    `;

    container.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = "0";
        toast.style.transform = "translateX(50px)";
        toast.style.transition = "all 0.3s ease";
        setTimeout(() => toast.remove(), 300);
    }, 3800);
}

// Determine stock status
function getStockStatus(qty, minStock) {
    const q = Number(qty);
    const m = Number(minStock);
    if (q <= 0) return { label: "Out of Stock", class: "out-of-stock" };
    if (q <= m) return { label: "Low Stock", class: "low-stock" };
    return { label: "In Stock", class: "in-stock" };
}

// Update KPI Stats Cards in DOM
function updateStatsInDOM(stats) {
    if (!stats) return;

    const totalProductsEl = document.getElementById("totalProducts");
    const totalStockEl = document.getElementById("totalStock");
    const lowStockEl = document.getElementById("lowStock");
    const inventoryValueEl = document.getElementById("inventoryValue");

    if (totalProductsEl && stats.totalProducts !== undefined) {
        totalProductsEl.textContent = Number(stats.totalProducts).toLocaleString();
    }
    if (totalStockEl && stats.totalStock !== undefined) {
        totalStockEl.textContent = Number(stats.totalStock).toLocaleString();
    }
    if (lowStockEl && stats.lowStock !== undefined) {
        lowStockEl.textContent = Number(stats.lowStock).toLocaleString();
    }
    if (inventoryValueEl && stats.inventoryValue !== undefined) {
        inventoryValueEl.textContent = formatCurrency(stats.inventoryValue);
    }

    // Ribbon counts on catalogue page
    const rTotal = document.getElementById("ribbonTotal");
    const rHealthy = document.getElementById("ribbonHealthy");
    const rLow = document.getElementById("ribbonLow");
    const rOut = document.getElementById("ribbonOut");

    if (rTotal && stats.totalProducts !== undefined) rTotal.textContent = stats.totalProducts;
    if (rHealthy && stats.healthyStock !== undefined) rHealthy.textContent = stats.healthyStock;
    if (rLow && stats.lowStock !== undefined) rLow.textContent = stats.lowStock;
    if (rOut && stats.outOfStock !== undefined) rOut.textContent = stats.outOfStock;
}

// Update a row in the DOM after restock
function updateRowInDOM(productId, product) {
    const rows = document.querySelectorAll(`tr[data-id="${productId}"]`);
    rows.forEach(row => {
        const qty = Number(product.quantity);
        const minStock = Number(product.min_stock || product.minStock || 10);
        const status = getStockStatus(qty, minStock);

        // Update text
        const stockText = row.querySelector(".stock-count-text, .stock-meter-header span:first-child");
        if (stockText) stockText.textContent = `${qty} units`;

        // Update progress bar
        const maxRef = Math.max(minStock * 2.5, qty, 50);
        const percentage = Math.min(100, Math.round((qty / maxRef) * 100));
        const barFill = row.querySelector(".stock-bar-fill");
        if (barFill) {
            barFill.style.width = `${percentage}%`;
            barFill.className = `stock-bar-fill ${qty <= 0 ? 'stock-fill-low' : (qty <= minStock ? 'stock-fill-mid' : 'stock-fill-high')}`;
        }

        // Update status badge
        const badge = row.querySelector(".status-badge");
        if (badge) {
            badge.className = `status-badge ${status.class}`;
            badge.innerHTML = `<span class="status-badge-dot"></span> ${status.label}`;
        }

        // Update row dataset
        row.setAttribute("data-status", status.class);
    });
}

// Remove row from DOM
function removeRowFromDOM(productId) {
    const rows = document.querySelectorAll(`tr[data-id="${productId}"]`);
    rows.forEach(row => {
        row.style.transition = "opacity 0.3s ease, transform 0.3s ease";
        row.style.opacity = "0";
        row.style.transform = "translateX(20px)";
        setTimeout(() => row.remove(), 300);
    });
}

// AJAX Quick Restock
async function quickRestock(productId, amount = 20) {
    try {
        const resp = await fetch('api/restock.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: productId, amount: amount })
        });

        if (resp.ok) {
            const data = await resp.json();
            if (data.success) {
                showToast("Stock Replenished", data.message, "success");
                if (data.product) updateRowInDOM(productId, data.product);
                if (data.stats) updateStatsInDOM(data.stats);
                return;
            }
        }
    } catch (e) {
        console.warn("API restock failed, falling back to local state:", e);
    }

    // Local fallback
    const products = getProducts();
    const item = products.find(p => p.id === productId);
    if (item) {
        item.quantity += amount;
        saveProducts(products);
        updateRowInDOM(productId, item);
        showToast("Stock Replenished", `Added +${amount} units to ${item.name}`, "success");
    }
}

// AJAX Delete Product
async function deleteProduct(productId) {
    if (!confirm(`Are you sure you want to permanently delete item "${productId}" from inventory?`)) {
        return;
    }

    try {
        const resp = await fetch('api/delete.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: productId })
        });

        if (resp.ok) {
            const data = await resp.json();
            if (data.success) {
                showToast("Product Removed", data.message, "warning");
                removeRowFromDOM(productId);
                if (data.stats) updateStatsInDOM(data.stats);
                return;
            }
        }
    } catch (e) {
        console.warn("API delete failed, falling back to local state:", e);
    }

    // Local fallback
    let products = getProducts();
    const item = products.find(p => p.id === productId);
    if (item) {
        products = products.filter(p => p.id !== productId);
        saveProducts(products);
        removeRowFromDOM(productId);
        showToast("Product Removed", `"${item.name}" was deleted from inventory`, "warning");
    }
}

// Helpers for Products Catalogue
function quickRestockCatalog(id, amt) {
    quickRestock(id, amt);
}

function deleteProductCatalog(id) {
    deleteProduct(id);
}

// Stock Alert Modal Operations
function showStockAlert() {
    let modal = document.getElementById("stockAlertModal");
    if (!modal) {
        modal = createStockAlertModal();
    }
    populateStockAlerts();
    modal.classList.add("open");
}

function closeStockAlert() {
    const modal = document.getElementById("stockAlertModal");
    if (modal) {
        modal.classList.remove("open");
    }
}

function createStockAlertModal() {
    const modal = document.createElement("div");
    modal.id = "stockAlertModal";
    modal.className = "modal-backdrop";
    modal.innerHTML = `
        <div class="modal-card">
            <div class="modal-header">
                <h3>⚠️ Critical Stock Alerts</h3>
                <button class="modal-close-btn" onclick="closeStockAlert()">✕</button>
            </div>
            <div class="modal-body" id="stockAlertList">
                <div style="text-align: center; padding: 20px; color: var(--text-muted);">Loading live alerts...</div>
            </div>
            <div class="modal-footer">
                <button class="btn-secondary" onclick="closeStockAlert()">Close</button>
                <button class="btn-primary" onclick="restockAllLowStock()">Restock All Low Stock (+30)</button>
            </div>
        </div>
    `;

    modal.addEventListener("click", (e) => {
        if (e.target === modal) closeStockAlert();
    });

    document.body.appendChild(modal);
    return modal;
}

async function populateStockAlerts() {
    const listContainer = document.getElementById("stockAlertList");
    if (!listContainer) return;

    let lowStockItems = [];

    // Try fetching from API
    try {
        const resp = await fetch('api/products.php');
        if (resp.ok) {
            const data = await resp.json();
            if (data.products) {
                lowStockItems = data.products.filter(p => Number(p.quantity) <= Number(p.min_stock || p.minStock));
            }
        }
    } catch (e) {
        // Local fallback
        const products = getProducts();
        lowStockItems = products.filter(p => Number(p.quantity) <= Number(p.minStock));
    }

    if (lowStockItems.length === 0) {
        listContainer.innerHTML = `
            <div class="empty-state">
                <div class="empty-state-icon">✅</div>
                <h4>All Stocks Healthy!</h4>
                <p>No products currently below their minimum threshold in MySQL.</p>
            </div>
        `;
        return;
    }

    listContainer.innerHTML = lowStockItems.map(item => `
        <div class="alert-item">
            <div class="alert-info">
                <h4>${item.icon || '📦'} ${escapeHtml(item.name)}</h4>
                <p>
                    Current Stock: <strong style="color: var(--warning);">${item.quantity}</strong> units 
                    (Threshold: ${item.min_stock || item.minStock})
                </p>
                <div style="font-size: 0.76rem; color: var(--text-muted); margin-top: 4px;">
                    📍 ${escapeHtml(item.location || 'General Storage')} | SKU: ${escapeHtml(item.id)}
                </div>
            </div>
            <button class="btn-restock" onclick="quickRestock('${escapeHtml(item.id)}', 30)">
                Restock +30
            </button>
        </div>
    `).join("");
}

// Batch Restock All Low Stock
async function restockAllLowStock() {
    try {
        const resp = await fetch('api/restock.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'batch', amount: 30 })
        });

        if (resp.ok) {
            const data = await resp.json();
            if (data.success) {
                showToast("Batch Replenished", data.message, "success");
                closeStockAlert();
                setTimeout(() => window.location.reload(), 700);
                return;
            }
        }
    } catch (e) {
        console.warn("API batch restock failed:", e);
    }

    // Local fallback
    const products = getProducts();
    let restocked = 0;
    products.forEach(p => {
        if (p.quantity <= p.minStock) {
            p.quantity += 30;
            restocked++;
        }
    });

    if (restocked > 0) {
        saveProducts(products);
        showToast("Batch Restocked", `Replenished stock for ${restocked} items (+30 each)`, "success");
        closeStockAlert();
        setTimeout(() => window.location.reload(), 700);
    } else {
        showToast("No Action Needed", "All items have sufficient stock levels", "info");
    }
}

// Instant Filter & Live Search for Dashboard & Catalogue
function initFiltersAndSearch() {
    const dashSearch = document.getElementById("dashboardSearch");
    const catSearch = document.getElementById("catalogueSearch");
    const catFilter = document.getElementById("categoryFilter");
    const statusFilter = document.getElementById("statusFilter");
    const tabs = document.querySelectorAll(".filter-tab");

    let activeCategory = "All";

    // Dashboard Search Input
    if (dashSearch) {
        dashSearch.addEventListener("input", (e) => {
            filterTableRows(e.target.value, activeCategory, "All");
        });
    }

    // Dashboard Filter Tabs
    tabs.forEach(tab => {
        tab.addEventListener("click", () => {
            tabs.forEach(t => t.classList.remove("active"));
            tab.classList.add("active");
            activeCategory = tab.getAttribute("data-category") || "All";
            const q = dashSearch ? dashSearch.value : "";
            filterTableRows(q, activeCategory, "All");
        });
    });

    // Catalogue Toolbar Controls
    const runCatalogueFilter = () => {
        const q = catSearch ? catSearch.value : "";
        const c = catFilter ? catFilter.value : "All";
        const s = statusFilter ? statusFilter.value : "All";
        filterTableRows(q, c, s);
    };

    if (catSearch) catSearch.addEventListener("input", runCatalogueFilter);
    if (catFilter) catFilter.addEventListener("change", runCatalogueFilter);
    if (statusFilter) statusFilter.addEventListener("change", runCatalogueFilter);
}

// Universal table rows filter
function filterTableRows(searchQuery = "", category = "All", status = "All") {
    const q = (searchQuery || "").toLowerCase().trim();
    const rows = document.querySelectorAll("tbody tr[data-id]");

    let visibleCount = 0;

    rows.forEach(row => {
        const rowName = row.getAttribute("data-name") || "";
        const rowId = (row.getAttribute("data-id") || "").toLowerCase();
        const rowLoc = row.getAttribute("data-location") || "";
        const rowCat = row.getAttribute("data-category") || "";
        const rowStat = row.getAttribute("data-status") || "";

        const matchesQuery = !q || rowName.includes(q) || rowId.includes(q) || rowLoc.includes(q);
        const matchesCategory = category === "All" || rowCat.toLowerCase() === category.toLowerCase();
        const matchesStatus = status === "All" || rowStat === status;

        if (matchesQuery && matchesCategory && matchesStatus) {
            row.style.display = "";
            visibleCount++;
        } else {
            row.style.display = "none";
        }
    });

    // Handle empty state message if needed
    const tbody = document.querySelector("tbody");
    let emptyRow = document.getElementById("dynamicEmptyRow");

    if (visibleCount === 0 && rows.length > 0) {
        if (!emptyRow && tbody) {
            emptyRow = document.createElement("tr");
            emptyRow.id = "dynamicEmptyRow";
            emptyRow.innerHTML = `
                <td colspan="7">
                    <div class="empty-state">
                        <div class="empty-state-icon">🔍</div>
                        <h4>No Matching Products Found</h4>
                        <p>No inventory items match your search and filter criteria.</p>
                    </div>
                </td>
            `;
            tbody.appendChild(emptyRow);
        }
    } else if (emptyRow) {
        emptyRow.remove();
    }
}

// Theme Switcher
function initTheme() {
    const currentTheme = localStorage.getItem(THEME_KEY) || "dark";
    document.documentElement.setAttribute("data-theme", currentTheme);
    updateThemeIcon(currentTheme);

    const toggleBtn = document.getElementById("themeToggleBtn");
    if (toggleBtn) {
        toggleBtn.addEventListener("click", () => {
            const current = document.documentElement.getAttribute("data-theme") || "dark";
            const next = current === "dark" ? "light" : "dark";
            document.documentElement.setAttribute("data-theme", next);
            localStorage.setItem(THEME_KEY, next);
            updateThemeIcon(next);
            showToast("Theme Changed", `Switched to ${next === "dark" ? "Dark" : "Light"} mode`, "info");
        });
    }
}

function updateThemeIcon(theme) {
    const toggleBtn = document.getElementById("themeToggleBtn");
    if (toggleBtn) {
        toggleBtn.innerHTML = theme === "dark" ? "☀️" : "🌙";
        toggleBtn.title = theme === "dark" ? "Switch to Light Mode" : "Switch to Dark Mode";
    }
}

// Mobile Sidebar Toggle
function initMobileMenu() {
    const mobileBtn = document.getElementById("mobileMenuBtn");
    const sidebar = document.querySelector(".sidebar");
    if (mobileBtn && sidebar) {
        mobileBtn.addEventListener("click", () => {
            sidebar.classList.toggle("open");
        });

        document.addEventListener("click", (e) => {
            if (!sidebar.contains(e.target) && !mobileBtn.contains(e.target) && sidebar.classList.contains("open")) {
                sidebar.classList.remove("open");
            }
        });
    }
}

// Escape HTML utility
function escapeHtml(text) {
    if (!text) return "";
    return text.toString()
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

// Initialize on DOM load
document.addEventListener("DOMContentLoaded", () => {
    initTheme();
    initMobileMenu();
    initFiltersAndSearch();
});
