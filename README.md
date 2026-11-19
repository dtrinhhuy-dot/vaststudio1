# VAST STUDIO — THƯƠNG HIỆU THỜI TRANG STREETWEAR & HỆ THỐNG QUẢN TRỊ (CHUẨN KIẾN TRÚC MVC)

Hệ thống website thương mại điện tử **VAST STUDIO** được thiết kế và tổ chức chuẩn mực theo mô hình kiến trúc **MVC (Model — View — Controller)**, phân tách hoàn toàn giữa Dữ liệu nghiệp vụ, Giao diện hiển thị và Luồng điều khiển.

Hệ thống bao gồm đầy đủ **Giao diện Khách hàng (Storefront: Trang chủ, Danh sách sản phẩm, Chi tiết sản phẩm, Tra cứu đơn hàng, Giới thiệu, Liên hệ Showroom, Giỏ hàng, Yêu thích)** và **Giao diện Quản trị (Admin Portal)**, đồng bộ dữ liệu thời gian thực thông qua `StoreModel` & `LocalStorage`.

---

## 🌐 CÁC TRANG CHÍNH TRONG HỆ THỐNG

| Trang | File | Mô tả chi tiết |
|---|---|---|
| **Trang Chủ** | `index.html` | Giao diện thời trang streetwear: Hero section, Phân loại danh mục, BST mùa mới, Top bán chạy, Sản phẩm mới, Lookbook, Đánh giá, Giỏ hàng drawer, Wishlist drawer. |
| **Trang Danh Sách Sản Phẩm** | `products.html` | Danh mục sản phẩm chuyên sâu: Lọc theo Danh mục, Bộ sưu tập, Kích cỡ (Size), Gam màu, Khoảng giá slider, Sắp xếp (Bán chạy, Mới nhất, Giá tăng/giảm), Đổi chế độ xem (Lưới 4 cột, 3 cột, Danh sách). |
| **Trang Chi Tiết Sản Phẩm** | `product-detail.html` | Trang chi tiết chuẩn SEO: Gallery ảnh góc chụp, chọn màu/size, thông số kỹ thuật (GSM, cotton dệt thoi), hướng dẫn bảo quản, đánh giá có sao và gợi ý phối đồ (Complete The Look). |
| **Trang Tra Cứu Đơn Hàng** | `order-tracking.html` | Tra cứu theo Mã đơn (`VAST-xxxx`) hoặc SĐT: Timeline 4 giai đoạn trực quan, chi tiết kiện hàng, thanh toán, hủy đơn khi còn chờ xử lý. |
| **Trang Giới Thiệu** | `about.html` | Câu chuyện thương hiệu VAST STUDIO, triết lý thiết kế tối giản, 4 trụ cột chất liệu và cột mốc phát triển. |
| **Trang Liên Hệ & Showroom** | `contact.html` | Địa chỉ 2 showroom Flagship tại Hà Nội & TP.HCM, giờ mở cửa, form liên hệ gửi tin nhắn CSKH trực tuyến. |
| **Trang Quản Trị** | `admin.html` | Dashboard KPI, Biểu đồ doanh thu 7d/30d, Quản lý sản phẩm (CRUD), Quản lý đơn hàng, In phiếu hóa đơn, Quản lý Voucher & Cài đặt shop. |

---

## 🏛️ CẤU TRÚC THƯ MỤC DỰ ÁN

```
d:\vaststudio\
├── index.html                  # [V] Trang chủ Khách hàng (Storefront Home)
├── products.html               # [V] Trang danh sách sản phẩm & Bộ lọc (Shop Catalog)
├── product-detail.html         # [V] Trang chi tiết sản phẩm chuyên sâu (Product Detail)
├── order-tracking.html         # [V] Trang tra cứu đơn hàng & Timeline vận chuyển
├── about.html                  # [V] Trang giới thiệu & Câu chuyện thương hiệu
├── contact.html                # [V] Trang liên hệ & Hệ thống Showroom trực tiếp
├── admin.html                  # [V] Trang quản trị cửa hàng (Admin Dashboard)
├── home.html                   # [V] Redirect alias -> index.html
├── shop.html                   # [V] Redirect alias -> products.html
│
├── css\
│   ├── styles.css              # Design System, Theme Dark/Light, Tokens toàn cục
│   └── storefront.css          # Giao diện Trang chủ, PDP, Tracking, Giỏ hàng, Wishlist, Floating widgets
│
├── js\
│   ├── icons.js                # Thư viện Vector Icons SVG tích hợp sẵn
│   ├── storefront.js           # [C/V] Điều khiển tương tác toàn bộ Storefront (PDP, Cart, Wishlist, Tracking)
│   │
│   ├── models\                 # [M] - MODEL LAYER: Quản lý dữ liệu & nghiệp vụ
│   │   └── StoreModel.js       # Quản lý Sản phẩm, Giỏ hàng, Wishlist, Đơn hàng, Đánh giá, Voucher, LocalStorage
│   │
│   ├── views\                  # [V] - VIEW LAYER: Kết xuất giao diện Admin
│   │   ├── CommonView.js       # Toast notifications, Quản lý Modal, Đổi Theme
│   │   ├── DashboardView.js    # Biểu đồ SVG, thẻ thống kê KPI, danh sách bán chạy
│   │   ├── ProductView.js      # Bảng sản phẩm, form thêm/sửa, badge tồn kho
│   │   └── OrderView.js        # Bảng đơn hàng, popup chi tiết, in hóa đơn giao hàng
│   │
│   ├── controllers\            # [C] - CONTROLLER LAYER: Xử lý nghiệp vụ Admin
│   │   ├── ProductController.js# Form sản phẩm, tìm kiếm, lọc kho, xóa
│   │   ├── OrderController.js  # Cập nhật trạng thái đơn, lọc tab, in phiếu
│   │   ├── DashboardController.js # Thống kê doanh thu, biểu đồ 7d/30d
│   │   └── AppController.js    # Điều hướng chuyển tab, cài đặt shop
│   │
│   └── app.js                  # Entry Point Admin Bootstrap & Global Helpers
│
├── server.js                   # Web Server tĩnh Node.js (Zero-dependency)
├── package.json                # Cấu hình dự án & Scripts
└── README.md                   # Tài liệu hướng dẫn
```

---

## 🔄 LUỒNG TƯƠNG TÁC ĐỒNG BỘ 2 CHIỀU GIỮA STOREFRONT & ADMIN

1. **Quản trị viên thêm/sửa sản phẩm trên `admin.html`**:
   - Dữ liệu lưu tức thời vào `StoreModel`.
   - Các trang `index.html`, `products.html`, `product-detail.html` cập nhật sản phẩm mới ngay lập tức.
2. **Khách hàng đặt hàng trên Storefront**:
   - Đơn hàng được tạo kèm mã `VAST-XXXX` (VD: `VAST-9826`).
   - Tồn kho sản phẩm tự động giảm, số lượt bán tự động tăng.
   - Quản trị viên mở `admin.html` sẽ thấy ngay đơn hàng mới xuất hiện trong danh sách Đơn Hàng cần xử lý!
   - Khách hàng có thể sao chép mã đơn và vào ngay `order-tracking.html` để theo dõi tiến độ giao hàng!
3. **Mã giảm giá (Coupons)**:
   - Các mã ưu đãi: `VASTWELCOME` (giảm 100k), `FREESHIP` (freeship 300k), `SUMMER50K` (-50k), `VIPMEMBER` (giảm 15%) áp dụng tức thì tại giỏ hàng.

---

## 🚀 HƯỚNG DẪN KHỞI CHẠY HỆ THỐNG

### Cách 1: Khởi chạy bằng Node.js Server (Khuyên dùng)
Mở Terminal tại thư mục dự án và chạy:
```bash
npm start
# hoặc: node server.js
```
- **Trang chủ Khách hàng**: [http://localhost:3000](http://localhost:3000)
- **Trang danh sách sản phẩm**: [http://localhost:3000/products.html](http://localhost:3000/products.html)
- **Trang chi tiết sản phẩm**: [http://localhost:3000/product-detail.html?id=PRD-001](http://localhost:3000/product-detail.html?id=PRD-001)
- **Trang tra cứu đơn hàng**: [http://localhost:3000/order-tracking.html](http://localhost:3000/order-tracking.html)
- **Trang giới thiệu thương hiệu**: [http://localhost:3000/about.html](http://localhost:3000/about.html)
- **Trang liên hệ & Showroom**: [http://localhost:3000/contact.html](http://localhost:3000/contact.html)
- **Trang quản trị Admin**: [http://localhost:3000/admin.html](http://localhost:3000/admin.html)

### Cách 2: Mở trực tiếp trên Trình duyệt
- [index.html](file:///d:/vaststudio/index.html) — Trang Chủ
- [products.html](file:///d:/vaststudio/products.html) — Trang Danh Sách Sản Phẩm
- [product-detail.html](file:///d:/vaststudio/product-detail.html) — Trang Chi Tiết Sản Phẩm
- [order-tracking.html](file:///d:/vaststudio/order-tracking.html) — Trang Tra Cứu Đơn Hàng
- [about.html](file:///d:/vaststudio/about.html) — Trang Giới Thiệu
- [contact.html](file:///d:/vaststudio/contact.html) — Trang Liên Hệ & Showroom
- [admin.html](file:///d:/vaststudio/admin.html) — Trang Quản Trị Cửa Hàng

---

© 2026 **VAST STUDIO**. Redefining Modern Streetwear & Minimalist Aesthetics.
