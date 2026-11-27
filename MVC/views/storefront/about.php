<?php
require_once __DIR__ . '/../../../includes/config.php';
$pageTitle = "Về Chúng Tôi — VAST STUDIO | Câu Chuyện Thương Hiệu & Triết Lý Thiết Kế";
$pageDesc = "Tìm hiểu về thương hiệu thời trang đường phố VAST STUDIO: Triết lý thiết kế tối giản đương đại, kỹ nghệ chất liệu 100% cotton tự nhiên và phom dáng chuẩn Á Đông.";
$activeNav = "about";
include __DIR__ . '/../partials/header.php';
?>

<!-- 3. ABOUT CONTENT -->
    <main>
        <!-- Hero Section -->
        <section class="about-hero-section">
            <div class="store-container">
                <span class="section-label">Câu Chuyện Thương Hiệu</span>
                <h1 style="font-family: var(--store-font-heading); font-size: clamp(2.2rem, 4vw, 3.5rem); font-weight: 800; margin: 12px 0 16px; text-transform: uppercase;">
                    REDEFINING MODERN STREETWEAR
                </h1>
                <p style="color: var(--text-secondary); max-width: 680px; margin: 0 auto; font-size: 1.1rem; line-height: 1.6;">
                    <strong>VAST STUDIO</strong> sinh ra từ tình yêu mãnh liệt với thời trang đường phố tối giản và khát vọng mang đến những thiết kế chỉn chu, bền bỉ và tôn vinh phom dáng người Việt.
                </p>
            </div>
        </section>

        <!-- Story Section -->
        <div class="store-container">
            <div class="about-story-grid">
                <div>
                    <span class="section-label">Khởi nguồn sáng tạo</span>
                    <h2 style="font-family: var(--store-font-heading); font-size: 2rem; margin: 8px 0 16px;">
                        TỐI GIẢN KHÔNG ĐỒNG NGHĨA VỚI ĐƠN ĐIỆU
                    </h2>
                    <p style="color: var(--text-secondary); line-height: 1.7; font-size: 0.95rem;">
                        Trong thế giới thời trang vận động không ngừng, VAST STUDIO chọn con đường tập trung vào <strong>bản chất của trang phục</strong>: Chất lượng sợi dệt, độ chính xác của đường may, và phom dáng đứng phom tự nhiên.
                    </p>
                    <p style="color: var(--text-secondary); line-height: 1.7; font-size: 0.95rem;">
                        Chúng tôi tin rằng một chiếc áo thun Boxy hoàn hảo không cần quá nhiều họa tiết rối mắt — vẻ đẹp nằm ở độ rũ tự nhiên của chất vải cotton 280GSM, đường trần đè vai đúp đôi kim tỉ mỉ và mác kim loại mạ bạc đính tinh tế sau gáy.
                    </p>
                    <div style="margin-top: 24px;">
                        <a href="products.php" class="btn-hero-primary">
                            Khám Phá Các Thiết Kế <span data-icon="arrowRight"></span>
                        </a>
                    </div>
                </div>

                <div style="border-radius: var(--radius-lg); overflow: hidden; border: 1px solid var(--border-color); aspect-ratio: 4 / 5;">
                    <img src="https://images.unsplash.com/photo-1490481651871-ab68de25d43d?w=800&auto=format&fit=crop&q=80" alt="VAST STUDIO Editorial" style="width: 100%; height: 100%; object-fit: cover;">
                </div>
            </div>
        </div>

        <!-- 4 Core Pillars -->
        <section class="store-section" style="background: var(--bg-surface); border-top: 1px solid var(--border-color); border-bottom: 1px solid var(--border-color);">
            <div class="store-container">
                <div class="section-header-row" style="text-align: center; justify-content: center;">
                    <div class="section-title-wrap" style="align-items: center;">
                        <span class="section-label">Tiêu Chuẩn Cốt Lõi</span>
                        <h2 class="section-heading">4 TRỤ CỘT CỦA VAST STUDIO</h2>
                        <p class="section-subheading">Những nguyên tắc bất biến định hình nên từng sản phẩm đến tay khách hàng.</p>
                    </div>
                </div>

                <div class="about-values-grid">
                    <div class="about-value-card">
                        <div class="usp-icon-wrap" style="width: 48px; height: 48px;"><span data-icon="package"></span></div>
                        <h3 style="font-family: var(--store-font-heading); margin: 0; font-size: 1.15rem;">1. Phom Dáng Chuẩn Á Đông</h3>
                        <p style="color: var(--text-secondary); margin: 0; font-size: 0.9rem; line-height: 1.6;">
                            Nghiên cứu kỹ lưỡng độ rộng vai, hạ nách và chiều dài thân áo để mang lại phom dáng Boxy đứng dáng, che khuyết điểm cơ thể tối ưu.
                        </p>
                    </div>

                    <div class="about-value-card">
                        <div class="usp-icon-wrap" style="width: 48px; height: 48px;"><span data-icon="shieldCheck"></span></div>
                        <h3 style="font-family: var(--store-font-heading); margin: 0; font-size: 1.15rem;">2. Kỹ Nghệ Sợi Vải Cao Cấp</h3>
                        <p style="color: var(--text-secondary); margin: 0; font-size: 0.9rem; line-height: 1.6;">
                            100% sợi bông tự nhiên định lượng cao (Heavyweight 280-400GSM), qua xử lý chống co rút và chống xù lông, cho cảm giác mặc mát mẻ, đầm tay.
                        </p>
                    </div>

                    <div class="about-value-card">
                        <div class="usp-icon-wrap" style="width: 48px; height: 48px;"><span data-icon="rotateCcw"></span></div>
                        <h3 style="font-family: var(--store-font-heading); margin: 0; font-size: 1.15rem;">3. Bền Vững Với Thời Gian</h3>
                        <p style="color: var(--text-secondary); margin: 0; font-size: 0.9rem; line-height: 1.6;">
                            Chúng tôi tạo ra những món đồ vượt thời gian (Timeless Aesthetics), mặc đẹp từ mùa này qua mùa khác mà không sợ lỗi mốt.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Milestones Timeline -->
        <section class="store-section">
            <div class="store-container" style="max-width: 800px;">
                <div class="section-title-wrap" style="text-align: center; margin-bottom: 40px; align-items: center;">
                    <span class="section-label">Hành Trình Phát Triển</span>
                    <h2 class="section-heading">CỘT MỐC VAST STUDIO</h2>
                </div>

                <div style="display: flex; flex-direction: column; gap: 24px;">
                    <div style="background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 24px; display: flex; gap: 20px; align-items: flex-start;">
                        <span style="font-family: var(--store-font-heading); font-size: 1.6rem; font-weight: 800; color: var(--store-gold);">2024</span>
                        <div>
                            <h4 style="margin: 0 0 4px; font-size: 1.05rem;">Khởi Đầu Tại Hà Nội</h4>
                            <p style="margin: 0; color: var(--text-secondary); font-size: 0.9rem; line-height: 1.5;">
                                Giới thiệu mẫu Áo Thun VAST Heavyweight Boxy Tee đầu tiên với 500 chiếc cháy hàng trong vòng 48 giờ mở bán.
                            </p>
                        </div>
                    </div>

                    <div style="background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 24px; display: flex; gap: 20px; align-items: flex-start;">
                        <span style="font-family: var(--store-font-heading); font-size: 1.6rem; font-weight: 800; color: var(--store-gold);">2025</span>
                        <div>
                            <h4 style="margin: 0 0 4px; font-size: 1.05rem;">Mở Rộng Flagship Store TP.HCM</h4>
                            <p style="margin: 0; color: var(--text-secondary); font-size: 0.9rem; line-height: 1.5;">
                                Khai trương không gian mua sắm tối giản tại 72 Lê Thánh Tôn, Quận 1 và đạt mốc phục vụ hơn 5.000 tín đồ thời trang cả nước.
                            </p>
                        </div>
                    </div>

                    <div style="background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 24px; display: flex; gap: 20px; align-items: flex-start;">
                        <span style="font-family: var(--store-font-heading); font-size: 1.6rem; font-weight: 800; color: var(--store-gold);">2026</span>
                        <div>
                            <h4 style="margin: 0 0 4px; font-size: 1.05rem;">Ra Mắt Monochrome & Winter Outerwear Drop</h4>
                            <p style="margin: 0; color: var(--text-secondary); font-size: 0.9rem; line-height: 1.5;">
                                Đưa dòng Bomber Jacket da dạ và Wide-leg Raw Denim vào bộ sưu tập chủ đạo, hoàn thiện hệ sinh thái trang phục cao cấp VAST STUDIO.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

<?php
include __DIR__ . '/../partials/footer.php';
?>
