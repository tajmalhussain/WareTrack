<?php
/**
 * WareTrack - Global Header & Navigation Template
 * Expects variables:
 *  $currentPage: 'dashboard' | 'products' | 'add-product' | 'edit-product' | 'suppliers' | 'stock-in' | 'stock-out' | 'reports'
 *  $pageTitle: string
 *  $pageSubtitle: string
 */

require_once __DIR__ . '/functions.php';

$currentPage = $currentPage ?? 'dashboard';
$pageTitle = $pageTitle ?? 'Warehouse Dashboard';
$pageSubtitle = $pageSubtitle ?? 'Real-time inventory telemetry and smart logistics management.';

$dbStatus = checkDBConnection();
$currentUser = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="WareTrack - Complete MySQL Powered Warehouse Inventory & Logistics System">
    <title><?= e($pageTitle) ?> | WareTrack System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>

    <!-- Sidebar Navigation -->
    <aside class="sidebar">
        <div class="logo">
            <div class="logo-icon-wrap">📦</div>
            <span>WareTrack</span>
            <div class="logo-badge">v2.5</div>
        </div>

        <nav>
            <ul>
                <li class="<?= $currentPage === 'dashboard' ? 'active' : '' ?>">
                    <a href="index.php">
                        <span class="nav-icon">🏠</span>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="<?= ($currentPage === 'products' || $currentPage === 'add-product' || $currentPage === 'edit-product') ? 'active' : '' ?>">
                    <a href="products.php">
                        <span class="nav-icon">📦</span>
                        <span>Products</span>
                    </a>
                </li>
                <li class="<?= $currentPage === 'suppliers' ? 'active' : '' ?>">
                    <a href="suppliers.php">
                        <span class="nav-icon">🏢</span>
                        <span>Suppliers</span>
                    </a>
                </li>
                <li class="<?= $currentPage === 'stock-in' ? 'active' : '' ?>">
                    <a href="stock-in.php">
                        <span class="nav-icon">📥</span>
                        <span>Stock In</span>
                    </a>
                </li>
                <li class="<?= $currentPage === 'stock-out' ? 'active' : '' ?>">
                    <a href="stock-out.php">
                        <span class="nav-icon">📤</span>
                        <span>Stock Out</span>
                    </a>
                </li>
                <li class="<?= $currentPage === 'reports' ? 'active' : '' ?>">
                    <a href="reports.php">
                        <span class="nav-icon">📈</span>
                        <span>Reports</span>
                    </a>
                </li>
            </ul>
        </nav>

        <div class="sidebar-bottom">
            <p>Warehouse Depot #04</p>
            <small>MySQL Active • Central Node</small>
            <div class="sidebar-live-pill" style="<?= !$dbStatus['connected'] ? 'border-color: rgba(239, 68, 68, 0.4); background: rgba(239, 68, 68, 0.1);' : '' ?>">
                <span class="sidebar-live-dot" style="<?= !$dbStatus['connected'] ? 'background: #ef4444; box-shadow: 0 0 8px #ef4444;' : '' ?>"></span>
                <span><?= $dbStatus['connected'] ? 'MySQL Database Active' : 'DB Disconnected (Demo Mode)' ?></span>
            </div>
            <a href="logout.php" style="display: flex; align-items: center; justify-content: center; gap: 8px; margin-top: 12px; color: var(--text-muted); font-size: 0.8rem; text-decoration: none; padding: 6px; border-radius: var(--radius-sm); transition: var(--transition-fast);" onmouseover="this.style.color='#ef4444'" onmouseout="this.style.color='var(--text-muted)'">
                <span>🚪</span> Sign Out
            </a>
        </div>
    </aside>

    <!-- Main Content Area -->
    <main class="main">

        <?php if (!$dbStatus['connected']): ?>
            <div style="background: rgba(239, 68, 68, 0.12); border: 1px solid rgba(239, 68, 68, 0.35); border-radius: var(--radius-md); padding: 14px 20px; margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; gap: 16px;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <span style="font-size: 1.4rem;">⚠️</span>
                    <div>
                        <strong style="color: #f87171; display: block; font-size: 0.95rem;">MySQL Database Disconnected</strong>
                        <small style="color: var(--text-secondary);">Application is operating with cached demo data. To connect real MySQL, import <code>database.sql</code> into XAMPP/MAMP/MariaDB. Error: <?= e($dbStatus['error']) ?></small>
                    </div>
                </div>
                <a href="database.sql" download class="btn-secondary" style="font-size: 0.8rem; padding: 6px 12px; white-space: nowrap;">
                    Download database.sql
                </a>
            </div>
        <?php endif; ?>

        <!-- Top Header Bar -->
        <header>
            <div style="display: flex; align-items: center; gap: 14px;">
                <button class="mobile-menu-btn" id="mobileMenuBtn" aria-label="Toggle menu">
                    ☰
                </button>
                <div>
                    <h1><?= e($pageTitle) ?></h1>
                    <p><?= e($pageSubtitle) ?></p>
                </div>
            </div>

            <div class="header-right">
                <?php if ($currentPage === 'dashboard'): ?>
                    <div class="header-search">
                        <span class="search-icon">🔍</span>
                        <input type="text" id="dashboardSearch" placeholder="Search SKU, product, aisle..." aria-label="Search inventory">
                    </div>
                <?php endif; ?>

                <button class="theme-toggle-btn" id="themeToggleBtn" title="Toggle Theme" aria-label="Toggle dark/light mode">
                    ☀️
                </button>

                <!-- User Profile & Quick Logout -->
                <div class="admin" title="Signed in as <?= e($currentUser['full_name']) ?> (Click to switch or logout)" style="cursor: pointer;" onclick="toggleUserDropdown(event)">
                    <div class="admin-avatar"><?= e($currentUser['initials']) ?></div>
                    <div class="admin-info">
                        <span class="name"><?= e($currentUser['full_name']) ?></span>
                        <span class="role"><?= e($currentUser['role']) ?></span>
                    </div>
                    <span style="font-size: 0.75rem; color: var(--text-muted); margin-left: 4px;">▾</span>

                    <!-- Dropdown -->
                    <div id="userDropdown" style="display: none; position: absolute; top: calc(100% + 8px); right: 0; background: var(--bg-surface-elevated); border: 1px solid var(--border-subtle); border-radius: var(--radius-md); box-shadow: var(--shadow-lg); width: 220px; z-index: 100; overflow: hidden;">
                        <div style="padding: 12px 16px; border-bottom: 1px solid var(--border-subtle);">
                            <div style="font-weight: 600; color: var(--text-primary); font-size: 0.9rem;"><?= e($currentUser['full_name']) ?></div>
                            <div style="font-size: 0.75rem; color: var(--text-muted);"><?= e($currentUser['email']) ?></div>
                        </div>
                        <a href="login.php" style="display: flex; align-items: center; gap: 10px; padding: 10px 16px; color: var(--text-secondary); text-decoration: none; font-size: 0.85rem; transition: background 0.2s;" onmouseover="this.style.background='var(--bg-card-hover)'" onmouseout="this.style.background='transparent'">
                            <span>🔄</span> Switch Account
                        </a>
                        <a href="logout.php" style="display: flex; align-items: center; gap: 10px; padding: 10px 16px; color: #ef4444; text-decoration: none; font-size: 0.85rem; border-top: 1px solid var(--border-subtle); transition: background 0.2s;" onmouseover="this.style.background='rgba(239,68,68,0.1)'" onmouseout="this.style.background='transparent'">
                            <span>🚪</span> Sign Out
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <script>
            function toggleUserDropdown(e) {
                e.stopPropagation();
                const dropdown = document.getElementById('userDropdown');
                if (dropdown) {
                    dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
                }
            }
            document.addEventListener('click', function() {
                const dropdown = document.getElementById('userDropdown');
                if (dropdown) dropdown.style.display = 'none';
            });
        </script>
