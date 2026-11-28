<?php
/**
 * VAST STUDIO - Common Footer & Modals Component
 */
require_once __DIR__ . '/../../../includes/config.php';
?>
    <!-- FOOTER -->
    <footer class="store-footer">
        <div class="store-container">
            <div class="footer-grid">
                <!-- Col 1: Brand -->
                <div class="footer-col-brand">
                    <div class="footer-brand-title"><?= APP_NAME ?></div>
                    <p class="footer-desc">
                        Thương hiệu thời trang đường phố đương đại & phong cách tối giản. Định hình gu ăn mặc tự tin, chỉn chu và phong cách cho giới trẻ Việt Nam.
                    </p>
                    <div class="footer-stores-list">
                        <div class="store-loc-item">
                            <span data-icon="mapPin"></span>
                            <span><strong>Hà Nội:</strong> 148 Phố Huế, Q. Hai Bà Trưng (Flagship Store)</span>
                        </div>
                        <div class="store-loc-item">
                            <span data-icon="mapPin"></span>
                            <span><strong>TP.HCM:</strong> 72 Lê Thánh Tôn, P. Bến Nghé, Quận 1</span>
                        </div>
                        <div class="store-loc-item">
                            <span data-icon="phone"></span>
                            <span><strong>Hotline:</strong> <?= APP_HOTLINE ?> (9:00 - 22:00 hàng ngày)</span>
                        </div>
                    </div>
                </div>

                <!-- Col 2: Navigation -->
                <div>
                    <h4 class="footer-col-title">Khám Phá Shop</h4>
                    <ul class="footer-links">
                        <li><a href="products.php" class="footer-link">Tất cả sản phẩm</a></li>
                        <li><a href="products.php?category=Áo thun" class="footer-link">Áo thun Heavyweight</a></li>
                        <li><a href="products.php?category=Áo sơ mi" class="footer-link">Áo sơ mi Oxford</a></li>
                        <li><a href="products.php?category=Quần" class="footer-link">Quần Parachute & Denim</a></li>
                        <li><a href="products.php?category=Áo khoác" class="footer-link">Áo khoác Bomber</a></li>
                        <li><a href="products.php?category=Phụ kiện" class="footer-link">Mũ Beanie & Phụ kiện</a></li>
                    </ul>
                </div>

                <!-- Col 3: Policies -->
                <div>
                    <h4 class="footer-col-title">Chính Sách & Hỗ Trợ</h4>
                    <ul class="footer-links">
                        <li><a href="javascript:void(0)" onclick="Storefront.openSizeGuideModal()" class="footer-link">Hướng dẫn chọn size</a></li>
                        <li><a href="javascript:void(0)" onclick="Storefront.showToast('Miễn phí đổi size trong 30 ngày tại nhà', 'info')" class="footer-link">Chính sách đổi trả 30 ngày</a></li>
                        <li><a href="javascript:void(0)" onclick="Storefront.showToast('Miễn phí vận chuyển cho đơn từ 500.000₫', 'info')" class="footer-link">Chính sách giao hàng</a></li>
                        <li><a href="javascript:void(0)" onclick="Storefront.showToast('Bảo hành đường may, cúc áo 12 tháng', 'info')" class="footer-link">Bảo hành trọn đời đường may</a></li>
                        <li><a href="javascript:void(0)" onclick="Storefront.showToast('Chương trình tích điểm VIP Black Card', 'info')" class="footer-link">Thành viên VAST VIP Club</a></li>
                    </ul>
                </div>

                <!-- Col 4: Storefront & Admin Portal -->
                <div>
                    <h4 class="footer-col-title">Hệ Thống Quản Trị</h4>
                    <p style="font-size: 0.85rem; color: #a1a1aa; line-height: 1.5; margin-bottom: 16px;">
                        Hệ thống kết nối trực tiếp với Trung tâm Quản trị Cửa hàng (Admin Portal). Quản trị viên có thể theo dõi đơn hàng, quản lý kho sản phẩm và doanh số.
                    </p>
                    <a href="admin.php" class="footer-admin-btn">
                        <span data-icon="dashboard"></span>
                        <span>Đến Trang Quản Trị Cửa Hàng</span>
                    </a>
                </div>
            </div>

            <div class="footer-bottom-row">
                <div>© <?= APP_YEAR ?> <strong><?= APP_NAME ?></strong>. Tất cả quyền được bảo lưu. Giấy phép ĐKKD số 0109988776.</div>
                <div style="display: flex; gap: 16px;">
                    <span>Thanh toán an toàn: Vietcombank QR • MoMo • COD</span>
                </div>
            </div>
        </div>
    </footer>

    <!-- ======================================================================
         INTERACTIVE OVERLAYS & MODALS
         ====================================================================== -->

    <!-- CART DRAWER -->
    <div class="cart-drawer-overlay" id="cart-drawer-overlay" onclick="Storefront.closeCart()"></div>
    <aside class="cart-drawer" id="cart-drawer">
        <div class="cart-header">
            <h3 class="cart-title">
                <span data-icon="shoppingBag"></span> Giỏ Hàng Của Bạn
            </h3>
            <button class="cart-close-btn" onclick="Storefront.closeCart()">
                <span data-icon="x"></span>
            </button>
        </div>

        <div class="cart-shipping-meter">
            <div id="shipping-meter-text">
                Mua thêm <strong>500.000 ₫</strong> để được <strong>MIỄN PHÍ SHIP</strong>
            </div>
            <div class="meter-bar-track">
                <div class="meter-bar-fill" id="shipping-meter-fill" style="width: 0%;"></div>
            </div>
        </div>

        <div class="cart-items-container" id="cart-items-container">
            <!-- Rendered by Storefront.js -->
        </div>

        <div class="cart-footer">
            <div class="cart-coupon-box">
                <input type="text" class="cart-coupon-input" id="cart-coupon-input" placeholder="Nhập mã: VASTWELCOME...">
                <button class="btn-apply-coupon" onclick="Storefront.applyCouponCode()">Áp Dụng</button>
            </div>

            <div class="cart-totals-list">
                <div class="cart-total-row">
                    <span>Tạm tính:</span>
                    <span id="cart-summary-subtotal">0 ₫</span>
                </div>
                <div class="cart-total-row" id="cart-discount-row" style="display: none; color: var(--store-emerald);">
                    <span>Giảm giá:</span>
                    <span id="cart-summary-discount">-0 ₫</span>
                </div>
                <div class="cart-total-row">
                    <span>Phí vận chuyển:</span>
                    <span id="cart-summary-shipping">Miễn phí</span>
                </div>
                <div class="cart-total-row grand-total">
                    <span>Tổng thanh toán:</span>
                    <span id="cart-summary-total">0 ₫</span>
                </div>
            </div>

            <button class="btn-checkout-now" id="btn-cart-checkout" onclick="Storefront.openCheckoutModal()">
                Tiến Hành Đặt Hàng <span data-icon="arrowRight"></span>
            </button>
        </div>
    </aside>

    <!-- QUICK VIEW MODAL -->
    <div class="quickview-modal-overlay" id="quickview-modal-overlay" onclick="if(event.target === this) Storefront.closeQuickView()">
        <div class="quickview-dialog" id="quickview-dialog">
            <!-- Populated by Storefront.js -->
        </div>
    </div>

    <!-- CHECKOUT MODAL -->
    <div class="checkout-modal-overlay" id="checkout-modal-overlay" onclick="if(event.target === this) Storefront.closeCheckoutModal()">
        <div class="checkout-dialog" id="checkout-modal-content">
            <div class="checkout-header">
                <button class="cart-close-btn" style="float: right;" onclick="Storefront.closeCheckoutModal()">×</button>
                <h3 class="checkout-title">Thông Tin Giao Hàng & Thanh Toán</h3>
                <p style="margin: 4px 0 0; color: var(--text-muted); font-size: 0.85rem;">
                    Vui lòng xác nhận thông tin nhận hàng để VAST STUDIO chuẩn bị kiện hàng cho bạn.
                </p>
            </div>

            <form class="checkout-form" onsubmit="Storefront.submitOrderForm(event)">
                <div class="checkout-input-group">
                    <label class="checkout-input-label">Họ và tên người nhận *</label>
                    <input type="text" id="checkout-name" class="checkout-input" placeholder="Ví dụ: Nguyễn Văn A" required>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div class="checkout-input-group">
                        <label class="checkout-input-label">Số điện thoại *</label>
                        <input type="tel" id="checkout-phone" class="checkout-input" placeholder="0912 345 678" required>
                    </div>
                    <div class="checkout-input-group">
                        <label class="checkout-input-label">Email (để nhận hóa đơn)</label>
                        <input type="email" id="checkout-email" class="checkout-input" placeholder="email@gmail.com">
                    </div>
                </div>

                <div class="checkout-input-group">
                    <label class="checkout-input-label">Địa chỉ nhận hàng chi tiết *</label>
                    <input type="text" id="checkout-address" class="checkout-input" placeholder="Số nhà, tên đường, Phường/Xã, Quận/Huyện, Tỉnh/Thành phố..." required>
                </div>

                <div class="checkout-input-group">
                    <label class="checkout-input-label">Ghi chú đơn hàng (tùy chọn)</label>
                    <input type="text" id="checkout-note" class="checkout-input" placeholder="Ví dụ: Giao giờ hành chính, gọi trước 15 phút...">
                </div>

                <div class="checkout-input-group" style="margin-top: 8px;">
                    <label class="checkout-input-label">Phương thức thanh toán</label>
                    <div class="payment-methods-grid">
                        <label class="payment-method-card active" onclick="this.parentElement.querySelectorAll('.payment-method-card').forEach(c=>c.classList.remove('active')); this.classList.add('active');">
                            <input type="radio" name="paymentMethod" value="COD" checked>
                            <div>
                                <strong style="font-size: 0.88rem; display: block;">COD</strong>
                                <span style="font-size: 0.75rem; color: var(--text-muted);">Thanh toán khi nhận hàng</span>
                            </div>
                        </label>

                        <label class="payment-method-card" onclick="this.parentElement.querySelectorAll('.payment-method-card').forEach(c=>c.classList.remove('active')); this.classList.add('active');">
                            <input type="radio" name="paymentMethod" value="QR">
                            <div>
                                <strong style="font-size: 0.88rem; display: block;">Chuyển khoản QR</strong>
                                <span style="font-size: 0.75rem; color: var(--text-muted);">Vietcombank tự động</span>
                            </div>
                        </label>
                    </div>
                </div>

                <div style="margin-top: 12px; display: flex; gap: 12px;">
                    <button type="button" class="btn-hero-outline" style="flex: 1;" onclick="Storefront.closeCheckoutModal()">
                        Quay Lại Giỏ
                    </button>
                    <button type="submit" class="btn-hero-primary" style="flex: 2; justify-content: center;">
                        Xác Nhận Đặt Hàng
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- LIVE SEARCH OVERLAY MODAL -->
    <div class="search-modal-overlay" id="search-modal-overlay" onclick="if(event.target === this) Storefront.closeSearchModal()">
        <div class="search-dialog">
            <div class="search-input-box">
                <span class="search-input-icon" data-icon="search"></span>
                <input type="text" class="search-modal-input" id="search-modal-input" 
                       placeholder="Tìm kiếm mẫu áo thun, sơ mi, quần, áo khoác..." 
                       oninput="Storefront.handleLiveSearch(this.value)">
                <button class="cart-close-btn" style="position: absolute; right: 18px; top: 50%; transform: translateY(-50%);" onclick="Storefront.closeSearchModal()">
                    <span data-icon="x"></span>
                </button>
            </div>

            <div class="search-results-box" id="search-results-list">
                <div style="padding: 20px; text-align: center; color: var(--text-muted); font-size: 0.88rem;">
                    Nhập tên mẫu áo, quần, bộ sưu tập để tìm kiếm nhanh...
                </div>
            </div>
        </div>
    </div>

    <!-- SIZE GUIDE MODAL -->
    <div class="quickview-modal-overlay" id="size-guide-modal-overlay" onclick="if(event.target === this) Storefront.closeSizeGuideModal()">
        <div class="quickview-dialog" style="max-width: 650px; display: block; padding: 32px;">
            <button class="quickview-close-btn" onclick="Storefront.closeSizeGuideModal()">×</button>
            <h3 style="font-family: var(--store-font-heading); margin: 0 0 8px;">Bảng Quy Chuẩn Size VAST STUDIO</h3>
            <p style="color: var(--text-muted); font-size: 0.85rem; margin: 0 0 20px;">
                Tất cả sản phẩm VAST được may theo form Boxy / Oversized chuẩn phong cách streetwear hiện đại.
            </p>

            <h4 style="font-size: 0.95rem; margin: 16px 0 8px;">1. Bảng size Áo thun / Hoodie / Sơ mi / Áo khoác:</h4>
            <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem; text-align: left; margin-bottom: 20px;">
                <thead>
                    <tr style="border-bottom: 2px solid var(--border-color); color: var(--text-muted);">
                        <th style="padding: 8px;">Size</th>
                        <th style="padding: 8px;">Chiều cao (cm)</th>
                        <th style="padding: 8px;">Cân nặng (kg)</th>
                        <th style="padding: 8px;">Dài áo (cm)</th>
                        <th style="padding: 8px;">Rộng ngực (cm)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr style="border-bottom: 1px solid var(--border-subtle);"><td style="padding: 8px;"><strong>S</strong></td><td style="padding: 8px;">155 - 165</td><td style="padding: 8px;">48 - 58</td><td style="padding: 8px;">68</td><td style="padding: 8px;">112</td></tr>
                    <tr style="border-bottom: 1px solid var(--border-subtle);"><td style="padding: 8px;"><strong>M</strong></td><td style="padding: 8px;">165 - 173</td><td style="padding: 8px;">58 - 68</td><td style="padding: 8px;">71</td><td style="padding: 8px;">116</td></tr>
                    <tr style="border-bottom: 1px solid var(--border-subtle);"><td style="padding: 8px;"><strong>L</strong></td><td style="padding: 8px;">173 - 180</td><td style="padding: 8px;">68 - 78</td><td style="padding: 8px;">74</td><td style="padding: 8px;">120</td></tr>
                    <tr style="border-bottom: 1px solid var(--border-subtle);"><td style="padding: 8px;"><strong>XL</strong></td><td style="padding: 8px;">180 - 190</td><td style="padding: 8px;">78 - 92</td><td style="padding: 8px;">77</td><td style="padding: 8px;">126</td></tr>
                </tbody>
            </table>

            <h4 style="font-size: 0.95rem; margin: 16px 0 8px;">2. Bảng size Quần Cargo / Denim:</h4>
            <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem; text-align: left;">
                <thead>
                    <tr style="border-bottom: 2px solid var(--border-color); color: var(--text-muted);">
                        <th style="padding: 8px;">Size</th>
                        <th style="padding: 8px;">Vòng bụng (cm)</th>
                        <th style="padding: 8px;">Dài quần (cm)</th>
                        <th style="padding: 8px;">Ống quần (cm)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr style="border-bottom: 1px solid var(--border-subtle);"><td style="padding: 8px;"><strong>29 / S</strong></td><td style="padding: 8px;">74 - 76</td><td style="padding: 8px;">102</td><td style="padding: 8px;">24</td></tr>
                    <tr style="border-bottom: 1px solid var(--border-subtle);"><td style="padding: 8px;"><strong>30 / M</strong></td><td style="padding: 8px;">77 - 80</td><td style="padding: 8px;">104</td><td style="padding: 8px;">25</td></tr>
                    <tr style="border-bottom: 1px solid var(--border-subtle);"><td style="padding: 8px;"><strong>31 / L</strong></td><td style="padding: 8px;">81 - 84</td><td style="padding: 8px;">106</td><td style="padding: 8px;">26</td></tr>
                    <tr style="border-bottom: 1px solid var(--border-subtle);"><td style="padding: 8px;"><strong>32 / XL</strong></td><td style="padding: 8px;">85 - 88</td><td style="padding: 8px;">108</td><td style="padding: 8px;">27</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- WISHLIST DRAWER -->
    <div class="cart-drawer-overlay" id="wishlist-drawer-overlay" onclick="Storefront.closeWishlist()"></div>
    <aside class="wishlist-drawer" id="wishlist-drawer">
        <div class="cart-header">
            <h3 class="cart-title"><span data-icon="heart"></span> Danh Sách Yêu Thích</h3>
            <button class="cart-close-btn" onclick="Storefront.closeWishlist()"><span data-icon="x"></span></button>
        </div>
        <div class="wishlist-items-container" id="wishlist-items-container"></div>
    </aside>

    <!-- FLOATING SUPPORT & BACK TO TOP -->
    <div class="floating-support-bar">
        <div class="float-tooltip">
            <span>👋 Cần tư vấn size?</span>
        </div>
        <a href="tel:<?= preg_replace('/[^0-9]/', '', APP_HOTLINE) ?>" class="float-btn float-btn-hotline" title="Hotline <?= APP_HOTLINE ?>">
            <span data-icon="phone"></span>
        </a>
        <a href="https://zalo.me" target="_blank" class="float-btn float-btn-zalo" title="Chat Zalo">
            Zalo
        </a>
        <button class="float-btn btn-back-to-top" id="btn-back-to-top" onclick="Storefront.scrollToTop()" title="Lên đầu trang">
            ↑
        </button>
    </div>

    <!-- Scripts: Models, Icons, Storefront -->
    <?php
    $liveCatalogJson = null;
    try {
        require_once __DIR__ . '/../../models/OrderService.php';
        $liveCatalogJson = (new OrderService(getDatabaseConnection()))->getStorefrontCatalog();
    } catch (Throwable $e) {
        $liveCatalogJson = null;
    }
    ?>
    <script>
        window.__VAST_SESSION_USER__ = <?= json_encode($_SESSION['customer_user_profile'] ?? null, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        window.__VAST_LIVE_CATALOG__ = <?= json_encode($liveCatalogJson, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    </script>
    <script src="js/icons.js"></script>
    <script src="js/models/StoreModel.js"></script>
    <script src="js/storefront.js"></script>
    <?= $extraScripts ?? '' ?>
    <script>
        // Inline SVG Icon replacement
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
