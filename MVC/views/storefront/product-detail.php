<?php
require_once __DIR__ . '/../../../includes/config.php';
$pageTitle = "Chi Tiết Sản Phẩm — VAST STUDIO | Modern Streetwear";
$pageDesc = "VAST STUDIO — Modern Streetwear";
$activeNav = "products";
include __DIR__ . '/../partials/header.php';
?>

<!-- 3. MAIN PRODUCT DETAIL CONTENT -->
    <main class="pdp-wrapper">
        <div class="store-container">
            <!-- Breadcrumbs -->
            <nav class="breadcrumbs" aria-label="Breadcrumb">
                <a href="index.php">Trang chủ</a>
                <span>/</span>
                <a href="products.php">Sản phẩm</a>
                <span>/</span>
                <span id="pdp-breadcrumb-name" style="color: var(--text-primary); font-weight: 600;">Chi tiết sản phẩm</span>
            </nav>

            <!-- PDP Main Layout (2 Columns: Gallery + Purchase Info) -->
            <div class="pdp-layout">
                <!-- Left: Multi-photo Gallery -->
                <div class="pdp-gallery-wrap">
                    <div class="pdp-main-image-box">
                        <img id="pdp-main-img" src="" alt="VAST STUDIO Product Image" loading="eager">
                    </div>
                    <div class="pdp-thumbs-row" id="pdp-thumbs-container">
                        <!-- Populated by Storefront.js -->
                    </div>
                </div>

                <!-- Right: Product Info & Actions -->
                <div class="pdp-info-wrap">
                    <div>
                        <span class="pdp-category-tag" id="pdp-category-tag">Áo thun • Streetwear Core</span>
                        <h1 class="pdp-title" id="pdp-title">Tên sản phẩm</h1>
                        
                        <div class="pdp-meta-row" style="margin-top: 8px;">
                            <span class="pdp-rating-badge">
                                ★★★★★ <span style="color: var(--text-primary); margin-left: 4px;">4.9</span>
                            </span>
                            <span>•</span>
                            <span id="pdp-sku">SKU: VAST-01</span>
                            <span>•</span>
                            <span id="pdp-stock-status">● Còn hàng</span>
                        </div>
                    </div>

                    <!-- Price Row -->
                    <div class="pdp-price-row">
                        <span class="pdp-price-current" id="pdp-price">0 ₫</span>
                        <span class="pdp-price-old" id="pdp-old-price" style="display: none;">0 ₫</span>
                        <span class="pdp-savings-pill" id="pdp-savings" style="display: none;">Tiết kiệm</span>
                    </div>

                    <!-- Brief Description -->
                    <p id="pdp-desc" style="color: var(--text-secondary); line-height: 1.6; margin: 0; font-size: 0.95rem;">
                        Mô tả sản phẩm...
                    </p>

                    <!-- Color Selector -->
                    <div class="quickview-option-group">
                        <span class="option-group-label">
                            Màu sắc: <strong id="pdp-selected-color-label">Đen Washed</strong>
                        </span>
                        <div class="quickview-pills-row" id="pdp-colors-container">
                            <!-- Populated by Storefront.js -->
                        </div>
                    </div>

                    <!-- Size Selector -->
                    <div class="quickview-option-group">
                        <div class="option-group-label">
                            <span>Kích thước: <strong id="pdp-selected-size-label">L</strong></span>
                            <a href="javascript:void(0)" onclick="Storefront.openSizeGuideModal()" style="color: var(--store-gold); text-decoration: underline; font-size: 0.8rem; font-weight: 700;">
                                Bảng chọn size chuẩn
                            </a>
                        </div>
                        <div class="quickview-pills-row" id="pdp-sizes-container">
                            <!-- Populated by Storefront.js -->
                        </div>
                    </div>

                    <!-- Quantity & Action Buttons -->
                    <div style="display: flex; gap: 14px; align-items: center; margin-top: 6px;">
                        <div class="qty-control" style="height: 52px;">
                            <button class="qty-btn" style="width: 42px; height: 52px; font-size: 1rem;" onclick="Storefront.adjustPDPQty(-1)">-</button>
                            <span class="qty-value" id="pdp-qty-display" style="width: 46px; font-size: 1.05rem;">1</span>
                            <button class="qty-btn" style="width: 42px; height: 52px; font-size: 1rem;" onclick="Storefront.adjustPDPQty(1)">+</button>
                        </div>

                        <button class="btn-pdp-add-cart" onclick="Storefront.submitPDPAddToCart()">
                            <span data-icon="shoppingBag"></span> Thêm Vào Giỏ
                        </button>

                        <button class="btn-pdp-buy-now" onclick="Storefront.submitPDPBuyNow()">
                            Mua Ngay
                        </button>

                        <button class="action-icon-btn" id="pdp-btn-wishlist" style="width: 52px; height: 52px; border: 1px solid var(--border-color);" 
                                onclick="Storefront.toggleWishlist(Storefront.state.pdpProduct.id, this)" title="Lưu vào yêu thích">
                            <span data-icon="heart"></span>
                        </button>
                    </div>

                    <!-- Commitments Box -->
                    <div class="pdp-commitments-box">
                        <div class="pdp-commit-item">
                            <span data-icon="truck" style="color: var(--store-emerald);"></span>
                            <span><strong>Miễn phí vận chuyển:</strong> Toàn quốc cho đơn hàng từ 500.000₫</span>
                        </div>
                        <div class="pdp-commit-item">
                            <span data-icon="rotateCcw" style="color: var(--store-gold);"></span>
                            <span><strong>Đổi trả 30 ngày:</strong> Hỗ trợ đổi size tận nhà nhanh chóng</span>
                        </div>
                        <div class="pdp-commit-item">
                            <span data-icon="shieldCheck" style="color: #38bdf8;"></span>
                            <span><strong>Bảo hành chính hãng:</strong> Trọn đời đường may và cúc áo</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PDP Tabbed Information Section -->
            <div class="pdp-tabs-container">
                <div class="pdp-tabs-header">
                    <button class="pdp-tab-btn active" onclick="Storefront.switchPDPTab('details', this)">Mô Tả & Thiết Kế</button>
                    <button class="pdp-tab-btn" onclick="Storefront.switchPDPTab('specs', this)">Thông Số & Chất Liệu</button>
                    <button class="pdp-tab-btn" onclick="Storefront.switchPDPTab('care', this)">Hướng Dẫn Bảo Quản</button>
                    <button class="pdp-tab-btn" onclick="Storefront.switchPDPTab('reviews', this)">Đánh Giá Khách Hàng</button>
                </div>

                <!-- Tab 1: Chi tiết & Thiết kế -->
                <div class="pdp-tab-content pdp-tab-pane" id="pdp-pane-details">
                    <h3 style="font-family: var(--store-font-heading); margin: 0 0 12px; color: var(--text-primary);">
                        TRIẾT LÝ THIẾT KẾ ĐƯƠNG ĐẠI
                    </h3>
                    <p>
                        Mỗi thiết kế tại <strong>VAST STUDIO</strong> đều trải qua quy trình nghiên cứu phom dáng tỉ mỉ dựa trên nhân trắc học người Á Đông. Form dáng <em>Boxy Drop-shoulder</em> mang lại sự phóng khoáng, thoải mái tối đa cho người mặc nhưng vẫn giữ được độ đứng dáng sang trọng.
                    </p>
                    <p>
                        Điểm nhấn nhận diện thương hiệu là chi tiết tag kim loại mạ bạc đính tinh tế sau gáy áo hoặc túi quần, kết hợp cùng kỹ thuật trần đè 2 kim gia cố nách áo và cạp quần để đảm bảo độ bền tối ưu qua hàng trăm lần giặt.
                    </p>
                </div>

                <!-- Tab 2: Thông số kỹ thuật -->
                <div class="pdp-tab-content pdp-tab-pane" id="pdp-pane-specs" style="display: none;">
                    <h3 style="font-family: var(--store-font-heading); margin: 0 0 12px; color: var(--text-primary);">
                        THÔNG SỐ KỸ THUẬT SẢN PHẨM
                    </h3>
                    <table class="pdp-specs-table">
                        <tbody>
                            <tr><td>Chất liệu</td><td>100% Sợi Cotton dệt thoi mật độ cao (Heavyweight 280-400GSM)</td></tr>
                            <tr><td>Xử lý bề mặt</td><td>Xử lý wash xơ sợi chống co rút, bề mặt mịn màng không xơ lông</td></tr>
                            <tr><td>Kỹ thuật may</td><td>May trần đè 2 kim kỹ thuật cao, viền cổ dệt gân 1x1 co giãn hồi form</td></tr>
                            <tr><td>Chi tiết nhận diện</td><td>Tag kim loại dập nổi logo VAST đính sau gáy</td></tr>
                            <tr><td>Xuất xứ</td><td>Tự hào thiết kế & sản xuất tại Việt Nam</td></tr>
                        </tbody>
                    </table>
                </div>

                <!-- Tab 3: Hướng dẫn bảo quản -->
                <div class="pdp-tab-content pdp-tab-pane" id="pdp-pane-care" style="display: none;">
                    <h3 style="font-family: var(--store-font-heading); margin: 0 0 12px; color: var(--text-primary);">
                        HƯỚNG DẪN GIẶT & BẢO QUẢN
                    </h3>
                    <ul style="padding-left: 20px; line-height: 1.8;">
                        <li>Nên giặt bằng nước lạnh hoặc nước ấm dưới 30°C để giữ màu sắc bền đẹp nhất.</li>
                        <li>Lộn trái áo khi giặt và phơi để bảo vệ bề mặt sợi vải và chi tiết thêu.</li>
                        <li>Không sử dụng thuốc tẩy có chứa clo hoặc chất làm mềm vải quá nồng.</li>
                        <li>Phơi ở nơi thoáng mát râm mát, tránh ánh nắng trực tiếp gay gắt.</li>
                        <li>Ủi ở nhiệt độ trung bình (dưới 150°C), không ủi trực tiếp lên tag kim loại.</li>
                    </ul>
                </div>

                <!-- Tab 4: Đánh giá khách hàng -->
                <div class="pdp-tab-content pdp-tab-pane" id="pdp-pane-reviews" style="display: none;">
                    <div class="reviews-grid-wrap">
                        <!-- Score Summary -->
                        <div class="review-summary-card">
                            <span class="review-score-big" id="pdp-avg-rating">4.9</span>
                            <div style="color: #f59e0b; font-size: 1.2rem;">★★★★★</div>
                            <span style="font-size: 0.85rem; color: var(--text-muted);" id="pdp-review-count">Đánh giá thực tế</span>
                            
                            <!-- Write Review Form -->
                            <div style="width: 100%; border-top: 1px solid var(--border-color); padding-top: 16px; margin-top: 12px; text-align: left;">
                                <h4 style="margin: 0 0 10px; font-size: 0.95rem;">Viết đánh giá của bạn</h4>
                                <form id="pdp-review-form" onsubmit="Storefront.handleReviewSubmit(event)" style="display: flex; flex-direction: column; gap: 8px;">
                                    <input type="text" id="review-input-name" class="checkout-input" placeholder="Tên của bạn *" required style="padding: 8px 12px; font-size: 0.85rem;">
                                    <input type="text" id="review-input-city" class="checkout-input" placeholder="Thành phố (ví dụ: Hà Nội)" style="padding: 8px 12px; font-size: 0.85rem;">
                                    <select id="review-input-rating" class="checkout-input" style="padding: 8px 12px; font-size: 0.85rem; cursor: pointer;">
                                        <option value="5">★★★★★ (5 sao - Cực kỳ hài lòng)</option>
                                        <option value="4">★★★★☆ (4 sao - Rất tốt)</option>
                                        <option value="3">★★★☆☆ (3 sao - Tạm ổn)</option>
                                    </select>
                                    <textarea id="review-input-content" class="checkout-input" rows="3" placeholder="Chia sẻ cảm nhận về form dáng, chất vải..." required style="padding: 8px 12px; font-size: 0.85rem; resize: none;"></textarea>
                                    <button type="submit" class="btn-hero-primary" style="padding: 10px; font-size: 0.85rem; justify-content: center;">
                                        Gửi Đánh Giá
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Reviews List -->
                        <div class="reviews-list-col" id="pdp-reviews-list">
                            <!-- Populated by Storefront.js -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- Related Products Section (Complete The Look) -->
            <section class="store-section" style="padding-top: 0;">
                <div class="section-header-row">
                    <div class="section-title-wrap">
                        <span class="section-label">Gợi ý phối đồ</span>
                        <h2 class="section-heading">COMPLETE THE LOOK</h2>
                        <p class="section-subheading">Các thiết kế chuẩn streetwear phối đồ cực chất cùng sản phẩm này.</p>
                    </div>
                    <a href="products.php" class="section-view-all-link">
                        Xem tất cả sản phẩm <span data-icon="arrowRight"></span>
                    </a>
                </div>

                <div class="products-grid" id="pdp-related-grid">
                    <!-- Populated by Storefront.js -->
                </div>
            </section>
        </div>
    </main>

<?php
include __DIR__ . '/../partials/footer.php';
?>
