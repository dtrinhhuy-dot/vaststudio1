<?php
/**
 * VAST STUDIO - Common Header Component
 * Expected variables:
 * - $pageTitle: Page title string
 * - $pageDesc: Meta description string
 * - $activeNav: Active navigation identifier ('home', 'products', 'collections', 'about', 'tracking', 'contact')
 * - $extraHead: Optional extra HTML inside <head>
 */

require_once __DIR__ . '/../../../includes/config.php';

$pageTitle = $pageTitle ?? 'VAST STUDIO — Modern Streetwear & Minimalist Fashion';
$pageDesc = $pageDesc ?? 'VAST STUDIO — Thương hiệu thời trang streetwear tối giản cao cấp chuẩn form Á Đông.';
$activeNav = $activeNav ?? '';
?>
<!DOCTYPE html>
<html lang="vi" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <meta name="description" content="<?= htmlspecialchars($pageDesc) ?>">
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/storefront.css">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' rx='20' fill='%23000'/><text x='50' y='68' font-size='60' font-family='sans-serif' font-weight='900' fill='%23fff' text-anchor='middle'>V</text></svg>">
    <?= $extraHead ?? '' ?>
</head>
<body class="store-body">

    <!-- 1. ANNOUNCEMENT TOPBAR -->
    <div class="announcement-bar">
        <div class="store-container">
            <div class="announcement-inner">
                <div class="announcement-text">
                    <span>🔥 Miễn phí vận chuyển cho đơn từ 500K</span>
                    <span>|</span>
                    <span>Nhập mã <span class="announcement-code" onclick="Storefront.copyCoupon('VASTWELCOME')" title="Nhấp để sao chép mã">VASTWELCOME</span> giảm ngay 100K</span>
                </div>
                <div class="announcement-links">
                    <a href="order-tracking.php" class="announcement-link">
                        <span data-icon="truck"></span> Tra cứu đơn hàng
                    </a>
                    <span class="announcement-link">
                        <span data-icon="phone"></span> Hotline: <?= APP_HOTLINE ?>
                    </span>
                    <a href="admin.php" class="admin-access-badge" title="Chuyển đến Trang Quản Trị Cửa Hàng">
                        <span data-icon="dashboard"></span> Trang Quản Trị
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. MAIN NAVIGATION HEADER -->
    <header class="store-header">
        <div class="store-container">
            <div class="store-header-inner">
                <!-- Brand Monogram & Name -->
                <a href="index.php" class="store-brand">
                    <div class="brand-monogram">V</div>
                    <div class="brand-text-block">
                        <span class="brand-main-title"><?= APP_NAME ?></span>
                        <span class="brand-sub-title"><?= APP_SLOGAN ?></span>
                    </div>
                </a>

                <!-- Nav Menu Links -->
                <nav>
                    <ul class="store-nav-menu">
                        <li><a href="index.php" class="nav-link <?= navActive($activeNav, 'home') ?>">Trang chủ</a></li>
                        <li><a href="products.php" class="nav-link <?= navActive($activeNav, 'products') ?>">Sản phẩm <span class="nav-badge-hot">Hot</span></a></li>
                        <li><a href="products.php#collections" class="nav-link <?= navActive($activeNav, 'collections') ?>">Bộ sưu tập</a></li>
                        <li><a href="about.php" class="nav-link <?= navActive($activeNav, 'about') ?>">Giới thiệu</a></li>
                        <li><a href="order-tracking.php" class="nav-link <?= navActive($activeNav, 'tracking') ?>">Tra cứu đơn</a></li>
                        <li><a href="contact.php" class="nav-link <?= navActive($activeNav, 'contact') ?>">Liên hệ & Store</a></li>
                    </ul>
                </nav>

                <!-- Header Actions -->
                <div class="store-header-actions">
                    <button class="action-icon-btn" title="Tìm kiếm sản phẩm" onclick="Storefront.openSearchModal()">
                        <span data-icon="search"></span>
                    </button>

                    <button class="action-icon-btn" id="theme-toggle-btn" title="Đổi giao diện Sáng / Tối" onclick="Storefront.toggleTheme()">
                        <span id="theme-toggle-icon" data-icon="sun"></span>
                    </button>

                    <button class="action-icon-btn" title="Danh sách yêu thích" onclick="Storefront.openWishlist()">
                        <span data-icon="heart"></span>
                        <span class="action-badge" id="header-wishlist-badge" style="display: none;">0</span>
                    </button>

                    <button class="btn-header-cart" onclick="Storefront.openCart()">
                        <span data-icon="shoppingBag"></span>
                        <span>Giỏ hàng</span>
                        <span class="action-badge" id="header-cart-badge" style="display: none;">0</span>
                    </button>

                    <?php
                    $sessionCustomer = $_SESSION['customer_user_profile'] ?? null;
                    $headerInitial = '';
                    if (is_array($sessionCustomer) && !empty($sessionCustomer['name'])) {
                        $trimmedName = trim((string)$sessionCustomer['name']);
                        $firstChar = function_exists('mb_substr') ? mb_substr($trimmedName, 0, 1) : substr($trimmedName, 0, 1);
                        $headerInitial = function_exists('mb_strtoupper') ? mb_strtoupper($firstChar) : strtoupper($firstChar);
                    }
                    ?>
                    <a href="auth.php" class="action-icon-btn" id="header-auth-btn" title="<?= $headerInitial !== '' ? htmlspecialchars($sessionCustomer['name'] . ' — Tài khoản thành viên', ENT_QUOTES, 'UTF-8') : 'Tài khoản thành viên' ?>">
                        <?php if ($headerInitial !== ''): ?>
                            <span class="header-user-avatar"><?= htmlspecialchars($headerInitial, ENT_QUOTES, 'UTF-8') ?></span>
                        <?php else: ?>
                            <span data-icon="user"></span>
                        <?php endif; ?>
                    </a>

                    <a href="admin.php" class="action-icon-btn" title="Quản trị Shop" style="margin-left: 4px;">
                        <span data-icon="settings"></span>
                    </a>

                    <button class="mobile-nav-toggle" onclick="Storefront.openCart()">
                        <span data-icon="menu"></span>
                    </button>
                </div>
            </div>
        </div>
    </header>
