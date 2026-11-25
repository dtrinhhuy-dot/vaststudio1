<?php
require_once __DIR__ . '/includes/config.php';
$pageTitle = "Liên Hệ & Hệ Thống Showroom — VAST STUDIO | Cửa Hàng Thời Trang";
$pageDesc = "Thông tin liên hệ, hotline tư vấn và địa chỉ 2 showroom Flagship Store của VAST STUDIO tại Hà Nội và TP.HCM.";
$activeNav = "contact";
include __DIR__ . '/includes/header.php';
?>

<!-- 3. MAIN CONTACT CONTENT -->
    <main class="store-container" style="padding-top: 30px; padding-bottom: 80px;">
        <!-- Breadcrumbs -->
        <nav class="breadcrumbs" aria-label="Breadcrumb">
            <a href="index.php">Trang chủ</a>
            <span>/</span>
            <span style="color: var(--text-primary); font-weight: 600;">Liên hệ & Hệ thống Showroom</span>
        </nav>

        <div class="section-title-wrap" style="margin-bottom: 32px;">
            <span class="section-label">Kết Nối Trực Tiếp</span>
            <h1 style="font-family: var(--store-font-heading); font-size: 2.2rem; font-weight: 800; margin: 6px 0; text-transform: uppercase;">
                HỆ THỐNG STORE & LIÊN HỆ
            </h1>
            <p style="color: var(--text-secondary); margin: 0; font-size: 0.95rem;">
                Ghé thăm không gian mua sắm tối giản của VAST STUDIO hoặc gửi tin nhắn để được đội ngũ CSKH tư vấn hỗ trợ.
            </p>
        </div>

        <div class="contact-grid">
            <!-- Left: 2 Flagship Storefronts Info -->
            <div>
                <h3 style="font-family: var(--store-font-heading); margin: 0 0 20px; font-size: 1.3rem;">
                    SHOWROOM FLAGSHIP TRỰC TIẾP
                </h3>

                <!-- Store 1: Hanoi -->
                <div class="showroom-card">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                        <div>
                            <span class="col-badge" style="background: var(--store-gold); color: #ffffff;">Hà Nội Flagship</span>
                            <h4 style="margin: 8px 0 4px; font-size: 1.15rem; color: var(--text-primary);">VAST STUDIO Phố Huế</h4>
                        </div>
                        <span style="font-size: 0.8rem; color: var(--store-emerald); font-weight: 700;">● Đang mở cửa</span>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 8px; font-size: 0.88rem; color: var(--text-secondary); margin-top: 6px;">
                        <div style="display: flex; align-items: flex-start; gap: 8px;">
                            <span data-icon="mapPin" style="color: var(--text-primary);"></span>
                            <span><strong>Địa chỉ:</strong> 148 Phố Huế, Q. Hai Bà Trưng, Hà Nội</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span data-icon="clock" style="color: var(--text-primary);"></span>
                            <span><strong>Giờ mở cửa:</strong> 09:00 - 22:00 (Thứ 2 - Chủ Nhật)</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span data-icon="phone" style="color: var(--text-primary);"></span>
                            <span><strong>Hotline:</strong> 0988.123.456 (Nhánh 1)</span>
                        </div>
                    </div>
                </div>

                <!-- Store 2: Hcm -->
                <div class="showroom-card">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                        <div>
                            <span class="col-badge" style="background: #3b82f6; color: #ffffff;">TP.HCM Flagship</span>
                            <h4 style="margin: 8px 0 4px; font-size: 1.15rem; color: var(--text-primary);">VAST STUDIO Lê Thánh Tôn</h4>
                        </div>
                        <span style="font-size: 0.8rem; color: var(--store-emerald); font-weight: 700;">● Đang mở cửa</span>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 8px; font-size: 0.88rem; color: var(--text-secondary); margin-top: 6px;">
                        <div style="display: flex; align-items: flex-start; gap: 8px;">
                            <span data-icon="mapPin" style="color: var(--text-primary);"></span>
                            <span><strong>Địa chỉ:</strong> 72 Lê Thánh Tôn, P. Bến Nghé, Quận 1, TP.HCM</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span data-icon="clock" style="color: var(--text-primary);"></span>
                            <span><strong>Giờ mở cửa:</strong> 09:00 - 22:00 (Thứ 2 - Chủ Nhật)</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span data-icon="phone" style="color: var(--text-primary);"></span>
                            <span><strong>Hotline:</strong> 0988.123.456 (Nhánh 2)</span>
                        </div>
                    </div>
                </div>

                <div style="background: var(--bg-surface); border: 1px dashed var(--border-color); border-radius: var(--radius-md); padding: 18px 24px; font-size: 0.85rem; color: var(--text-secondary);">
                    💡 <strong>Đặc quyền khi ghé thăm Showroom:</strong> Phòng thử đồ VIP cách âm rộng rãi, quầy nước welcome drink miễn phí và tư vấn phối đồ streetwear 1-1 từ stylist của hãng.
                </div>
            </div>

            <!-- Right: Interactive Contact Form -->
            <div style="background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 36px;">
                <h3 style="font-family: var(--store-font-heading); margin: 0 0 8px; font-size: 1.3rem;">
                    GỬI YÊU CẦU CHO VAST STUDIO
                </h3>
                <p style="color: var(--text-muted); font-size: 0.88rem; margin: 0 0 24px;">
                    Chúng tôi sẽ phản hồi thư của bạn trong vòng tối đa 2 giờ làm việc.
                </p>

                <form onsubmit="event.preventDefault(); Storefront.showToast('Cảm ơn bạn! Tin nhắn đã được gửi đến ban quản trị VAST STUDIO.', 'success'); this.reset();" style="display: flex; flex-direction: column; gap: 16px;">
                    <div class="checkout-input-group">
                        <label class="checkout-input-label">Họ và tên *</label>
                        <input type="text" class="checkout-input" placeholder="Ví dụ: Hoàng Minh" required>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div class="checkout-input-group">
                            <label class="checkout-input-label">Số điện thoại *</label>
                            <input type="tel" class="checkout-input" placeholder="0912 345 678" required>
                        </div>
                        <div class="checkout-input-group">
                            <label class="checkout-input-label">Email *</label>
                            <input type="email" class="checkout-input" placeholder="email@gmail.com" required>
                        </div>
                    </div>

                    <div class="checkout-input-group">
                        <label class="checkout-input-label">Chủ đề cần hỗ trợ</label>
                        <select class="checkout-input" style="cursor: pointer;">
                            <option value="size">Tư vấn chọn size & phối đồ</option>
                            <option value="order">Tra cứu & hỗ trợ đơn hàng</option>
                            <option value="return">Yêu cầu đổi size / đổi mẫu tận nhà</option>
                            <option value="b2b">Hợp tác kinh doanh / Nhượng quyền B2B</option>
                            <option value="other">Góp ý khác</option>
                        </select>
                    </div>

                    <div class="checkout-input-group">
                        <label class="checkout-input-label">Nội dung tin nhắn *</label>
                        <textarea class="checkout-input" rows="4" placeholder="Nhập chi tiết nội dung bạn muốn trao đổi..." required style="resize: vertical;"></textarea>
                    </div>

                    <button type="submit" class="btn-hero-primary" style="justify-content: center; padding: 14px; font-size: 0.95rem; margin-top: 4px;">
                        Gửi Yêu Cầu Hỗ Trợ
                    </button>
                </form>
            </div>
        </div>
    </main>

<?php
include __DIR__ . '/includes/footer.php';
?>
