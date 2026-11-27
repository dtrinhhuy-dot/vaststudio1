<?php
require_once __DIR__ . '/../../../includes/config.php';
$pageTitle = "Tài Khoản Thành Viên — VAST STUDIO | Đăng Ký & Đăng Nhập";
$pageDesc = "Đăng ký và đăng nhập tài khoản thành viên VAST STUDIO. Nhận ngay voucher 100K cho thành viên mới, tích điểm nâng hạng VIP, theo dõi lịch sử đơn hàng và lưu thông tin thanh toán nhanh.";
$activeNav = "";
include __DIR__ . '/../partials/header.php';
?>

<!-- 3. AUTH / PROFILE MAIN CONTENT -->
    <main class="auth-page-wrapper">
        <div class="store-container">
            <!-- Breadcrumbs -->
            <nav class="breadcrumbs" aria-label="Breadcrumb">
                <a href="index.php">Trang chủ</a>
                <span>/</span>
                <span style="color: var(--text-primary); font-weight: 600;" id="auth-breadcrumb-current">Tài khoản thành viên</span>
            </nav>

            <!-- DYNAMIC VIEW CONTAINER: AUTH FORM OR PROFILE DASHBOARD -->
            <div id="auth-main-container">
                <!-- Rendered dynamically by Storefront.initAuthPage() -->
            </div>
        </div>
    </main>

<?php
include __DIR__ . '/../partials/footer.php';
?>
