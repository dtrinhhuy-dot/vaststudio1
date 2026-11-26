# VAST STUDIO — THƯƠNG HIỆU THỜI TRANG STREETWEAR & HỆ THỐNG QUẢN TRỊ (CHUẨN KIẾN TRÚC MVC)

Hệ thống website thương mại điện tử **VAST STUDIO** gồm storefront và trang quản trị. Trang quản trị dùng MVC thuần PHP, lưu dữ liệu MySQL và xử lý biểu mẫu bằng request GET/POST; storefront hiện vẫn dùng JavaScript và LocalStorage.

Hệ thống bao gồm đầy đủ **Giao diện Khách hàng (Storefront: Trang chủ, Danh sách sản phẩm, Chi tiết sản phẩm, Tra cứu đơn hàng, Giới thiệu, Liên hệ Showroom, Giỏ hàng, Yêu thích)** và **Giao diện Quản trị (Admin Portal)**.

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
| **Trang Quản Trị** | `admin.php` | MVC thuần PHP: Dashboard, CRUD sản phẩm, trạng thái/chi tiết đơn hàng, bộ sưu tập, khách hàng, voucher và cài đặt shop. |

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
├── admin.html                  # Alias chuyển tiếp đến admin.php
├── admin.php                   # [C] Điểm vào trang quản trị PHP
├── views\
│   └── admin.php               # [V] Giao diện quản trị PHP
├── includes\
│   ├── AdminController.php     # [C] Điều phối request và thao tác quản trị
│   ├── AdminStoreModel.php     # [M] Truy vấn dữ liệu quản trị MySQL
│   └── database.php            # Kết nối PDO
├── database\
│   ├── schema.sql              # Schema MySQL
│   ├── setup.php               # Khởi tạo database/schema
│   └── create-admin.php        # CLI tạo tài khoản role admin
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
├── server.js                   # Khởi chạy PHP development server
├── package.json                # Cấu hình dự án & Scripts
└── README.md                   # Tài liệu hướng dẫn
```

---

## 🔄 DỮ LIỆU STOREFRONT VÀ ADMIN

1. **Quản trị viên thao tác trên `admin.php`**:
   - Sản phẩm, đơn hàng, bộ sưu tập, khách hàng, voucher và cài đặt được đọc/ghi vào MySQL.
   - Mỗi thao tác gửi biểu mẫu lên PHP và tải lại trang; trang quản trị không nạp JavaScript.
2. **Storefront**:
   - Đơn hàng được tạo kèm mã `VAST-XXXX` (VD: `VAST-9826`).
   - Tồn kho sản phẩm tự động giảm, số lượt bán tự động tăng.
   - Khách hàng có thể sao chép mã đơn và vào ngay `order-tracking.html` để theo dõi tiến độ giao hàng!
   - Storefront hiện dùng LocalStorage riêng; đồng bộ dữ liệu giữa storefront và MySQL chưa được triển khai.
3. **Mã giảm giá (Coupons)**:
   - Các mã ưu đãi: `VASTWELCOME` (giảm 100k), `FREESHIP` (freeship 300k), `SUMMER50K` (-50k), `VIPMEMBER` (giảm 15%) áp dụng tức thì tại giỏ hàng.

---

## 🚀 HƯỚNG DẪN KHỞI CHẠY HỆ THỐNG

### Cách 1: Khởi chạy bằng Node.js Server (Khuyên dùng)
Mở Command Prompt và chạy:
```cmd
cd /d C:\laragon\www\vaststudio
php database\setup.php
npm start
```
- Bật MySQL trong Laragon trước. `setup.php` sẽ tạo schema trên database mới hoặc nhận diện database cũ và giữ nguyên các bảng/dữ liệu hiện có.
- Truy cập từ `localhost`/`127.0.0.1` không cần đăng nhập. Khi truy cập từ máy khác, tạo tài khoản quản trị bằng cách cấp `ADMIN_INITIAL_PASSWORD` qua môi trường (tối thiểu 12 ký tự), sau đó chạy `php database/create-admin.php "VAST Admin" admin@example.com`. Tài khoản không có role `admin` không thể vào trang quản trị.
- Có thể cấu hình kết nối qua `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` (mặc định: `127.0.0.1:3306`, `vaststudio`, `root`, mật khẩu rỗng).
- **Trang chủ Khách hàng**: [http://localhost:3000](http://localhost:3000)
- **Trang danh sách sản phẩm**: [http://localhost:3000/products.html](http://localhost:3000/products.html)
- **Trang chi tiết sản phẩm**: [http://localhost:3000/product-detail.html?id=PRD-001](http://localhost:3000/product-detail.html?id=PRD-001)
- **Trang tra cứu đơn hàng**: [http://localhost:3000/order-tracking.html](http://localhost:3000/order-tracking.html)
- **Trang giới thiệu thương hiệu**: [http://localhost:3000/about.html](http://localhost:3000/about.html)
- **Trang liên hệ & Showroom**: [http://localhost:3000/contact.html](http://localhost:3000/contact.html)
- **Trang quản trị Admin**: [http://localhost:3000/admin.php](http://localhost:3000/admin.php)

### Cách 2: Mở trực tiếp trên Trình duyệt
- [index.html](file:///d:/vaststudio/index.html) — Trang Chủ
- [products.html](file:///d:/vaststudio/products.html) — Trang Danh Sách Sản Phẩm
- [product-detail.html](file:///d:/vaststudio/product-detail.html) — Trang Chi Tiết Sản Phẩm
- [order-tracking.html](file:///d:/vaststudio/order-tracking.html) — Trang Tra Cứu Đơn Hàng
- [about.html](file:///d:/vaststudio/about.html) — Trang Giới Thiệu
- [contact.html](file:///d:/vaststudio/contact.html) — Trang Liên Hệ & Showroom
- Các trang PHP và cơ sở dữ liệu cần chạy qua máy chủ PHP; không mở `admin.php` bằng `file://`.

---

© 2026 **VAST STUDIO**. Redefining Modern Streetwear & Minimalist Aesthetics.
