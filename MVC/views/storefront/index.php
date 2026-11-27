<?php
require_once __DIR__ . '/../../../includes/config.php';
$pageTitle = "VAST STUDIO — Trang Chủ | Modern Streetwear & Minimalist Fashion";
$pageDesc = "VAST STUDIO — Thương hiệu thời trang streetwear tối giản cao cấp. Khám phá các mẫu Boxy Tee, Oversized Shirt, Cargo Pants, Denim và Bomber Jackets chuẩn form Á Đông.";
$activeNav = "home";
include __DIR__ . '/../partials/header.php';
?>

<!-- 3. HERO EDITORIAL SECTION -->
    <section class="store-hero">
        <div class="store-container">
            <div class="hero-grid">
                <div class="hero-content">
                    <div class="hero-pill">
                        <span class="hero-pill-dot"></span>
                        <span>Spring / Summer Drop 2026</span>
                    </div>

                    <h1 class="hero-title">
                        REDEFINING <span class="highlight">MODERN STREETWEAR</span> & MINIMALIST AESTHETICS
                    </h1>

                    <p class="hero-desc">
                        Khám phá các thiết kế thời trang đường phố đương đại: Form boxy phóng khoáng, chất vải dệt kỹ thuật cao cấp 280-400GSM và đường may trần đè chuẩn form Á Đông.
                    </p>

                    <div class="hero-actions">
                        <a href="products.php" class="btn-hero-primary">
                            <span>Khám phá Bộ Sưu Tập</span>
                            <span data-icon="arrowRight"></span>
                        </a>
                        <a href="#bestsellers" class="btn-hero-outline">
                            <span>Xem Best Sellers</span>
                        </a>
                    </div>

                    <div class="hero-stats-row">
                        <div class="stat-item">
                            <span class="stat-num">150+</span>
                            <span class="stat-label">Sản phẩm sẵn có</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-num">100%</span>
                            <span class="stat-label">Cotton tự nhiên</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-num">4.9/5</span>
                            <span class="stat-label">Đánh giá 5 sao</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-num">5.000+</span>
                            <span class="stat-label">Khách hàng VIP</span>
                        </div>
                    </div>
                </div>

                <div class="hero-visual-card">
                    <img src="https://images.unsplash.com/photo-1509631179647-0177331693ae?w=900&auto=format&fit=crop&q=80" alt="VAST STUDIO Editorial Streetwear">
                    <div class="hero-floating-tag">
                        <div>
                            <p class="tag-title">Monochrome Drop 2026</p>
                            <p class="tag-subtitle">Phiên bản giới hạn thiết kế độc quyền</p>
                        </div>
                        <a href="products.php" class="tag-badge">MUA NGAY</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 4. VALUE PROPOSITIONS (USPS) -->
    <section class="section-usps">
        <div class="store-container">
            <div class="usps-grid">
                <div class="usp-card">
                    <div class="usp-icon-wrap"><span data-icon="truck"></span></div>
                    <div class="usp-info">
                        <h4>Miễn phí vận chuyển</h4>
                        <p>Freeship toàn quốc cho tất cả đơn hàng từ 500.000₫.</p>
                    </div>
                </div>

                <div class="usp-card">
                    <div class="usp-icon-wrap"><span data-icon="rotateCcw"></span></div>
                    <div class="usp-info">
                        <h4>Đổi trả trong 30 ngày</h4>
                        <p>Hỗ trợ đổi size hoặc đổi mẫu tận nơi nhanh chóng, tiện lợi.</p>
                    </div>
                </div>

                <div class="usp-card">
                    <div class="usp-icon-wrap"><span data-icon="shieldCheck"></span></div>
                    <div class="usp-info">
                        <h4>Chất lượng may kỹ nghệ</h4>
                        <p>100% vải cotton dệt kỹ thuật cao cấp, đường may trần đè 2 kim bền bỉ.</p>
                    </div>
                </div>

                <div class="usp-card">
                    <div class="usp-icon-wrap"><span data-icon="phone"></span></div>
                    <div class="usp-info">
                        <h4>Tư vấn chọn size 24/7</h4>
                        <p>Hotline 0988.123.456 hỗ trợ định hướng phong cách và fit size chuẩn xác.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 5. CATEGORIES SHOWCASE -->
    <section class="store-section">
        <div class="store-container">
            <div class="section-header-row">
                <div class="section-title-wrap">
                    <span class="section-label">Phân loại trang phục</span>
                    <h2 class="section-heading">Danh mục sản phẩm</h2>
                </div>
                <a href="products.php" class="section-view-all-link">
                    Xem tất cả danh mục <span data-icon="arrowRight"></span>
                </a>
            </div>

            <div class="categories-grid" id="home-categories-grid">
                <!-- Rendered dynamically by Storefront.js -->
            </div>
        </div>
    </section>

    <!-- 6. FEATURED COLLECTIONS -->
    <section class="store-section" id="collections" style="background: var(--bg-surface); border-top: 1px solid var(--border-color); border-bottom: 1px solid var(--border-color);">
        <div class="store-container">
            <div class="section-header-row">
                <div class="section-title-wrap">
                    <span class="section-label">Bộ sưu tập thời trang</span>
                    <h2 class="section-heading">Seasonal Collections</h2>
                    <p class="section-subheading">Từng bộ sưu tập là một câu chuyện phong cách riêng biệt được thiết kế độc quyền.</p>
                </div>
                <a href="products.php" class="section-view-all-link">
                    Khám phá toàn bộ BST <span data-icon="arrowRight"></span>
                </a>
            </div>

            <div class="collections-grid" id="home-collections-grid">
                <!-- Rendered dynamically by Storefront.js -->
            </div>
        </div>
    </section>

    <!-- 7. TOP BEST SELLERS -->
    <section class="store-section" id="bestsellers">
        <div class="store-container">
            <div class="section-header-row">
                <div class="section-title-wrap">
                    <span class="section-label">Sản phẩm được yêu thích nhất</span>
                    <h2 class="section-heading">Top Bán Chạy VAST</h2>
                    <p class="section-subheading">Những mẫu trang phục streetwear luôn trong tình trạng "cháy hàng" của VAST STUDIO.</p>
                </div>
                <a href="products.php?sort=popular" class="section-view-all-link">
                    Xem toàn bộ sản phẩm hot <span data-icon="arrowRight"></span>
                </a>
            </div>

            <div class="products-grid" id="home-bestsellers-grid">
                <!-- Rendered dynamically by Storefront.js -->
            </div>
        </div>
    </section>

    <!-- 8. PROMO DROP BANNER -->
    <div class="store-container">
        <section class="promo-banner-section">
            <div class="promo-banner-inner">
                <div class="promo-info">
                    <h2>Ưu Đãi Chào Thành Viên Mới</h2>
                    <p>Nhập mã để nhận ngay ưu đãi <strong>giảm 100.000₫</strong> cho đơn hàng từ 1.000.000₫. Áp dụng toàn bộ mẫu áo thun, hoodie, quần và áo khoác mới nhất.</p>
                    <div style="margin-top: 18px;">
                        <a href="products.php" class="btn-hero-primary" style="background: #ffffff; color: #000000;">
                            Mua sắm ngay bây giờ
                        </a>
                    </div>
                </div>

                <div class="promo-code-card">
                    <span class="code-label">MÃ GIẢM GIÁ 100K</span>
                    <span class="promo-code-val">VASTWELCOME</span>
                    <button class="btn-copy-code" onclick="Storefront.copyCoupon('VASTWELCOME')">
                        Sao chép mã ưu đãi
                    </button>
                </div>
            </div>
        </section>
    </div>

    <!-- 9. NEW ARRIVALS -->
    <section class="store-section" id="new-arrivals">
        <div class="store-container">
            <div class="section-header-row">
                <div class="section-title-wrap">
                    <span class="section-label">Mới lên kệ</span>
                    <h2 class="section-heading">Sản phẩm mới ra mắt</h2>
                    <p class="section-subheading">Cập nhật xu hướng thời trang đường phố mới nhất cho mùa này.</p>
                </div>
                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                    <button class="tab-pill-btn active" data-category="all" onclick="Storefront.renderNewArrivals('all')">Tất cả</button>
                    <button class="tab-pill-btn" data-category="Áo thun" onclick="Storefront.renderNewArrivals('Áo thun')">Áo thun</button>
                    <button class="tab-pill-btn" data-category="Áo sơ mi" onclick="Storefront.renderNewArrivals('Áo sơ mi')">Sơ mi</button>
                    <button class="tab-pill-btn" data-category="Quần" onclick="Storefront.renderNewArrivals('Quần')">Quần</button>
                </div>
            </div>

            <div class="products-grid" id="home-new-arrivals-grid">
                <!-- Rendered dynamically by Storefront.js -->
            </div>
        </div>
    </section>

    <!-- 10. LOOKBOOK GALLERY -->
    <section class="store-section" id="lookbook" style="background: var(--bg-surface); border-top: 1px solid var(--border-color);">
        <div class="store-container">
            <div class="section-header-row">
                <div class="section-title-wrap">
                    <span class="section-label">Editorial Lookbook</span>
                    <h2 class="section-heading">VAST STREETWEAR VIBES</h2>
                    <p class="section-subheading">Phong cách phối đồ tối giản, tinh tế mang đậm tinh thần tự do của giới trẻ thành thị.</p>
                </div>
            </div>

            <div class="lookbook-grid">
                <div class="lookbook-item span-2">
                    <img src="https://images.unsplash.com/photo-1521572267360-ee0c2909d518?w=800&auto=format&fit=crop&q=80" alt="Boxy Tee Look">
                    <div class="lookbook-caption">Heavyweight Boxy Tee Washed Black</div>
                </div>
                <div class="lookbook-item">
                    <img src="https://images.unsplash.com/photo-1624378439575-d8705ad7ae80?w=800&auto=format&fit=crop&q=80" alt="Cargo Look">
                    <div class="lookbook-caption">Parachute Cargo Pants Obsidian</div>
                </div>
                <div class="lookbook-item">
                    <img src="https://images.unsplash.com/photo-1542272604-787c3835535d?w=800&auto=format&fit=crop&q=80" alt="Denim Look">
                    <div class="lookbook-caption">Wide-Leg Raw Denim Indigo</div>
                </div>
                <div class="lookbook-item">
                    <img src="https://images.unsplash.com/photo-1551028719-00167b16eac5?w=800&auto=format&fit=crop&q=80" alt="Bomber Look">
                    <div class="lookbook-caption">Varsity Leather Bomber Jacket</div>
                </div>
                <div class="lookbook-item span-2">
                    <img src="https://images.unsplash.com/photo-1490481651871-ab68de25d43d?w=800&auto=format&fit=crop&q=80" alt="Minimalist Essentials">
                    <div class="lookbook-caption">Minimalist Essentials Autumn Collection</div>
                </div>
            </div>
        </div>
    </section>

    <!-- 11. CUSTOMER TESTIMONIALS -->
    <section class="store-section">
        <div class="store-container">
            <div class="section-header-row" style="text-align: center; justify-content: center;">
                <div class="section-title-wrap" style="align-items: center;">
                    <span class="section-label">Đánh giá từ khách hàng</span>
                    <h2 class="section-heading">Khách hàng nói gì về VAST?</h2>
                    <p class="section-subheading">Hơn 5.000 tín đồ streetwear trên toàn quốc đã lựa chọn trang phục VAST STUDIO.</p>
                </div>
            </div>

            <div class="testimonials-grid">
                <div class="testimonial-card">
                    <div class="testimonial-stars">★★★★★</div>
                    <p class="testimonial-quote">
                        "Áo Boxy Tee chất vải 280GSM đỉnh thật sự, giặt máy không bị bai nhão cổ áo. Form áo đứng dáng, che khuyết điểm vai cực tốt. Mình đã mua đủ 3 màu."
                    </p>
                    <div class="testimonial-author">
                        <div class="author-avatar">HM</div>
                        <div class="author-details">
                            <h5>Hoàng Minh</h5>
                            <span>UI/UX Designer, Hà Nội</span>
                        </div>
                    </div>
                </div>

                <div class="testimonial-card">
                    <div class="testimonial-stars">★★★★★</div>
                    <p class="testimonial-quote">
                        "Quần Parachute Cargo có thể rút bo gấu linh hoạt theo phong cách ống suông hoặc ống bo cực kỳ ưng ý. Giao hàng hỏa tốc trong 24h, đóng gói hộp đen rất sang trọng."
                    </p>
                    <div class="testimonial-author">
                        <div class="author-avatar">TL</div>
                        <div class="author-details">
                            <h5>Thảo Ly</h5>
                            <span>Content Creator, TP.HCM</span>
                        </div>
                    </div>
                </div>

                <div class="testimonial-card">
                    <div class="testimonial-stars">★★★★★</div>
                    <p class="testimonial-quote">
                        "Bomber da dạ cầm nặng tay, đường thêu xù 3D sắc sảo. So với các brand local khác thì độ hoàn thiện của VAST STUDIO vượt trội hoàn toàn so với mức giá."
                    </p>
                    <div class="testimonial-author">
                        <div class="author-avatar">TH</div>
                        <div class="author-details">
                            <h5>Tuấn Hưng</h5>
                            <span>Architect, Đà Nẵng</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 12. FLAGSHIP SHOWROOMS & NEWSLETTER -->
    <section class="store-section" id="flagship" style="background: var(--bg-surface); border-top: 1px solid var(--border-color);">
        <div class="store-container">
            <div class="newsletter-section">
                <span class="section-label">Gia nhập cộng đồng VAST</span>
                <h2 style="font-family: var(--store-font-heading); font-size: 2rem; margin: 0; text-transform: uppercase;">
                    Nhận Voucher Giảm 10% Cho Đơn Hàng Đầu Tiên
                </h2>
                <p style="color: var(--text-secondary); max-width: 540px; margin: 0; font-size: 0.95rem;">
                    Đăng ký email để nhận thông báo sớm nhất về các đợt mở bán Limited Drop và đặc quyền dành riêng cho thành viên VAST Black Card.
                </p>
                <form class="newsletter-form" onsubmit="event.preventDefault(); Storefront.showToast('Cảm ơn bạn đã đăng ký! Mã giảm giá 10% đã gửi vào hộp thư.', 'success'); this.reset();">
                    <input type="email" class="newsletter-input" placeholder="Nhập địa chỉ email của bạn..." required>
                    <button type="submit" class="btn-newsletter-submit">Đăng Ký Ngay</button>
                </form>
            </div>
        </div>
    </section>

<?php
include __DIR__ . '/../partials/footer.php';
?>
