<?php
require_once __DIR__ . '/includes/config.php';
$pageTitle = "Thanh Toán Đơn Hàng — VAST STUDIO | Xác Nhận Mua Hàng Nhanh Chóng";
$pageDesc = "Trang thanh toán đặt hàng thời trang VAST STUDIO. Hỗ trợ thanh toán khi nhận hàng (COD), chuyển khoản VietQR tự động, bảo mật tối đa và giao hàng toàn quốc.";
$activeNav = "";
include __DIR__ . '/includes/header.php';
?>

<!-- 3. CHECKOUT HERO & STEPS -->
    <main class="checkout-page-wrapper">
        <div class="store-container">
            <!-- Breadcrumbs -->
            <nav class="breadcrumbs" aria-label="Breadcrumb">
                <a href="index.php">Trang chủ</a>
                <span>/</span>
                <a href="products.php">Sản phẩm</a>
                <span>/</span>
                <span style="color: var(--text-primary); font-weight: 600;">Thanh toán đơn hàng</span>
            </nav>

            <!-- Checkout Step Progress Indicator -->
            <div class="checkout-steps-bar">
                <div class="checkout-step-item done" onclick="Storefront.openCart()">
                    <div class="checkout-step-num"><span data-icon="check"></span></div>
                    <div class="checkout-step-label">1. Giỏ hàng</div>
                </div>
                <div class="checkout-step-divider active"></div>
                <div class="checkout-step-item active" id="step-indicator-checkout">
                    <div class="checkout-step-num">2</div>
                    <div class="checkout-step-label">2. Giao hàng & Thanh toán</div>
                </div>
                <div class="checkout-step-divider" id="step-divider-success"></div>
                <div class="checkout-step-item" id="step-indicator-success">
                    <div class="checkout-step-num">3</div>
                    <div class="checkout-step-label">3. Hoàn tất đặt hàng</div>
                </div>
            </div>

            <!-- DYNAMIC VIEW CONTAINER (CHECKOUT FORM OR SUCCESS STATE OR EMPTY STATE) -->
            <div id="checkout-main-content">
                <!-- Populated dynamically by Storefront.initCheckoutPage() -->
            </div>
        </div>
    </main>

<?php
include __DIR__ . '/includes/footer.php';
?>
