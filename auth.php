<!DOCTYPE html>
<html lang="vi" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tài Khoản Thành Viên — VAST STUDIO | Đăng Ký & Đăng Nhập</title>
    <meta name="description" content="Đăng ký và đăng nhập tài khoản thành viên VAST STUDIO. Nhận ngay voucher 100K cho thành viên mới, tích điểm nâng hạng VIP, theo dõi lịch sử đơn hàng và lưu thông tin thanh toán nhanh.">
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/storefront.css">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' rx='20' fill='%23000'/><text x='50' y='68' font-size='60' font-family='sans-serif' font-weight='900' fill='%23fff' text-anchor='middle'>V</text></svg>">
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
                        <span data-icon="phone"></span> Hotline: 0988.123.456
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
                        <span class="brand-main-title">VAST STUDIO</span>
                        <span class="brand-sub-title">Streetwear Core</span>
                    </div>
                </a>

                <!-- Nav Menu Links -->
                <nav>
                    <ul class="store-nav-menu">
                        <li><a href="index.php" class="nav-link">Trang chủ</a></li>
                        <li><a href="products.php" class="nav-link">Sản phẩm <span class="nav-badge-hot">Hot</span></a></li>
                        <li><a href="about.php" class="nav-link">Giới thiệu</a></li>
                        <li><a href="order-tracking.php" class="nav-link">Tra cứu đơn</a></li>
                        <li><a href="contact.php" class="nav-link">Liên hệ & Store</a></li>
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

                    <a href="auth.php" class="action-icon-btn active" id="header-auth-btn" title="Tài khoản thành viên">
                        <span data-icon="user"></span>
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

    <!-- 4. FOOTER -->
    <footer class="store-footer">
        <div class="store-container">
            <div class="footer-grid">
                <div class="footer-col brand-col">
                    <div class="store-brand" style="margin-bottom: 16px;">
                        <div class="brand-monogram">V</div>
                        <div class="brand-text-block">
                            <span class="brand-main-title">VAST STUDIO</span>
                            <span class="brand-sub-title">Streetwear Core</span>
                        </div>
                    </div>
                    <p class="footer-desc">
                        Thương hiệu thời trang đường phố cao cấp, tôn vinh phom dáng tối giản hiện đại và chất liệu bền vững theo thời gian.
                    </p>
                    <div class="footer-contact-info">
                        <div><span data-icon="mapPin"></span> 128 Phố Huế, Quận Hai Bà Trưng, Hà Nội</div>
                        <div><span data-icon="phone"></span> 0988.123.456 (8:30 - 22:00)</div>
                    </div>
                </div>

                <div class="footer-col">
                    <h4 class="footer-heading">Mua Sắm</h4>
                    <ul class="footer-links">
                        <li><a href="products.php">Tất cả sản phẩm</a></li>
                        <li><a href="products.html?cat=ao">Áo thun & Sơ mi Boxy</a></li>
                        <li><a href="products.html?cat=quan">Quần Cargo & Denim</a></li>
                        <li><a href="products.html?cat=khoac">Áo khoác Streetwear</a></li>
                        <li><a href="products.html?filter=hot">Sản phẩm Best-Seller</a></li>
                    </ul>
                </div>

                <div class="footer-col">
                    <h4 class="footer-heading">Chính Sách & Hỗ Trợ</h4>
                    <ul class="footer-links">
                        <li><a href="order-tracking.php">Tra cứu tiến độ đơn hàng</a></li>
                        <li><a href="about.php">Chính sách đổi trả 30 ngày</a></li>
                        <li><a href="about.php">Bảng quy chuẩn chọn size</a></li>
                        <li><a href="contact.php">Hệ thống cửa hàng VAST</a></li>
                        <li><a href="admin.php">Trang quản trị cửa hàng</a></li>
                    </ul>
                </div>

                <div class="footer-col">
                    <h4 class="footer-heading">Cam Kết Thương Hiệu</h4>
                    <div class="footer-benefits">
                        <div class="footer-benefit-item">
                            <span data-icon="shieldCheck"></span>
                            <div>
                                <strong>100% Chính Hãng</strong>
                                <span>Chất vải tuyển chọn cao cấp</span>
                            </div>
                        </div>
                        <div class="footer-benefit-item">
                            <span data-icon="rotateCcw"></span>
                            <div>
                                <strong>Đổi Trả 30 Ngày</strong>
                                <span>Miễn phí đổi size tận nhà</span>
                            </div>
                        </div>
                        <div class="footer-benefit-item">
                            <span data-icon="truck"></span>
                            <div>
                                <strong>Freeship Đơn Từ 500K</strong>
                                <span>Giao hàng nhanh toàn quốc</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="footer-bottom-row">
                <div>© 2026 <strong>VAST STUDIO</strong>. Tất cả quyền được bảo lưu.</div>
                <div>Thanh toán bảo mật: VietQR • Vietcombank • MoMo • COD</div>
            </div>
        </div>
    </footer>

    <!-- MODALS & DRAWERS -->
    <div class="cart-drawer-overlay" id="cart-drawer-overlay" onclick="Storefront.closeCart()"></div>
    <aside class="cart-drawer" id="cart-drawer">
        <div class="cart-header">
            <h3 class="cart-title"><span data-icon="shoppingBag"></span> Giỏ Hàng Của Bạn</h3>
            <button class="cart-close-btn" onclick="Storefront.closeCart()"><span data-icon="x"></span></button>
        </div>
        <div class="cart-shipping-meter">
            <div id="shipping-meter-text">Mua thêm để được FREESHIP</div>
            <div class="meter-bar-track"><div class="meter-bar-fill" id="shipping-meter-fill"></div></div>
        </div>
        <div class="cart-items-container" id="cart-items-container"></div>
        <div class="cart-footer">
            <div class="cart-totals-list">
                <div class="cart-total-row"><span>Tạm tính:</span><span id="cart-summary-subtotal">0 ₫</span></div>
                <div class="cart-total-row grand-total"><span>Tổng:</span><span id="cart-summary-total">0 ₫</span></div>
            </div>
            <button class="btn-checkout-now" id="btn-cart-checkout" onclick="Storefront.openCheckoutModal()">
                Tiến Hành Đặt Hàng <span data-icon="arrowRight"></span>
            </button>
        </div>
    </aside>

    <div class="cart-drawer-overlay" id="wishlist-drawer-overlay" onclick="Storefront.closeWishlist()"></div>
    <aside class="wishlist-drawer" id="wishlist-drawer">
        <div class="cart-header">
            <h3 class="cart-title"><span data-icon="heart"></span> Danh Sách Yêu Thích</h3>
            <button class="cart-close-btn" onclick="Storefront.closeWishlist()"><span data-icon="x"></span></button>
        </div>
        <div class="wishlist-items-container" id="wishlist-items-container"></div>
    </aside>

    <div class="search-modal-overlay" id="search-modal-overlay" onclick="if(event.target === this) Storefront.closeSearchModal()">
        <div class="search-dialog">
            <div class="search-input-box">
                <span class="search-input-icon" data-icon="search"></span>
                <input type="text" class="search-modal-input" id="search-modal-input" placeholder="Tìm kiếm nhanh..." oninput="Storefront.handleLiveSearch(this.value)">
                <button class="cart-close-btn" style="position: absolute; right: 18px; top: 50%; transform: translateY(-50%);" onclick="Storefront.closeSearchModal()"><span data-icon="x"></span></button>
            </div>
            <div class="search-results-box" id="search-results-list"></div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="js/icons.js"></script>
    <script src="js/models/StoreModel.js"></script>
    <script src="js/storefront.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('[data-icon]').forEach(el => {
                const iconName = el.getAttribute('data-icon');
                if (window.ICONS && window.ICONS[iconName]) {
                    el.innerHTML = window.ICONS[iconName];
                }
            });
        });
    </script>
</body>
</html>

