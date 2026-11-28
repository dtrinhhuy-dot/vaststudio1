<?php
require_once __DIR__ . '/../../../includes/config.php';
$pageTitle = "Tra Cứu Đơn Hàng — VAST STUDIO | Theo Dõi Tiến Độ Giao Hàng";
$pageDesc = "Tra cứu trạng thái và tiến độ giao hàng đơn hàng thời trang VAST STUDIO nhanh chóng bằng mã đơn hoặc số điện thoại.";
$activeNav = "tracking";
include __DIR__ . '/../partials/header.php';
?>

<!-- 3. MAIN TRACKING CONTENT -->
    <main class="tracking-wrapper">
        <div class="store-container">
            <!-- Breadcrumbs -->
            <nav class="breadcrumbs" aria-label="Breadcrumb">
                <a href="index.php">Trang chủ</a>
                <span>/</span>
                <span style="color: var(--text-primary); font-weight: 600;">Tra cứu đơn hàng</span>
            </nav>

            <!-- Search Hero Card -->
            <div class="tracking-hero-card">
                <span class="section-label">Hệ Thống Theo Dõi Vận Chuyển</span>
                <h1 style="font-family: var(--store-font-heading); font-size: 2.2rem; font-weight: 800; margin: 8px 0; text-transform: uppercase;">
                    TRA CỨU TIẾN ĐỘ ĐƠN HÀNG
                </h1>
                <p style="color: var(--text-secondary); margin: 0; font-size: 0.95rem;">
                    Nhập mã đơn hàng (ví dụ: <strong>VAST-9825</strong>) hoặc số điện thoại người nhận để kiểm tra trạng thái đóng gói và lịch trình vận chuyển.
                </p>

                <form class="tracking-search-form" onsubmit="Storefront.handleTrackingSearch(event)">
                    <input type="text" id="tracking-query-input" class="tracking-input" 
                           placeholder="Nhập mã đơn (VAST-xxxx) hoặc số điện thoại..." required>
                    <button type="submit" class="btn-hero-primary" style="padding: 14px 28px; font-size: 0.95rem;">
                        <span data-icon="search"></span> Tra Cứu Ngay
                    </button>
                </form>

                <div class="tracking-demo-pills">
                    <span>Tra cứu nhanh đơn mẫu:</span>
                    <button type="button" class="demo-pill-btn" onclick="Storefront.quickTrackDemo('VAST-9825')">VAST-9825</button>
                    <button type="button" class="demo-pill-btn" onclick="Storefront.quickTrackDemo('VAST-9824')">VAST-9824</button>
                    <button type="button" class="demo-pill-btn" onclick="Storefront.quickTrackDemo('VAST-9823')">VAST-9823</button>
                    <button type="button" class="demo-pill-btn" onclick="Storefront.quickTrackDemo('VAST-9822')">VAST-9822</button>
                </div>
            </div>

            <!-- Tracking Result Section -->
            <div id="tracking-result-container">
                <div style="text-align: center; padding: 40px 20px; color: var(--text-muted);">
                    <div style="font-size: 3.5rem; margin-bottom: 12px;">📦</div>
                    <h3 style="font-family: var(--store-font-heading); margin: 0 0 6px; color: var(--text-primary);">
                        Chưa có thông tin tra cứu
                    </h3>
                    <p style="margin: 0; font-size: 0.9rem;">
                        Hãy nhập mã đơn hàng của bạn ở trên hoặc bấm vào các mẫu đơn thử nhanh để xem tiến độ.
                    </p>
                </div>
            </div>
        </div>
    </main>

<?php
include __DIR__ . '/../partials/footer.php';
?>
