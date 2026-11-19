# VAST STUDIO — THƯƠNG HIỆU THỜI TRANG STREETWEAR & HỆ THỐNG QUẢN TRỊ (CHUẨN KIẾN TRÚC MVC)

Hệ thống website thương mại điện tử **VAST STUDIO** được thiết kế và tổ chức chuẩn mực theo mô hình kiến trúc **MVC (Model — View — Controller)**, phân tách hoàn toàn giữa Dữ liệu nghiệp vụ, Giao diện hiển thị và Luồng điều khiển.

Hệ thống bao gồm đầy đủ **Giao diện Khách hàng (Storefront: Trang chủ & Trang sản phẩm)** và **Giao diện Quản trị (Admin Portal)**, đồng bộ dữ liệu thời gian thực thông qua `StoreModel` & `LocalStorage`.

---

## 🌐 CÁC TRANG CHÍNH TRONG HỆ THỐNG

| Trang | File | Mô tả chi tiết |
|---|---|---|
| **Trang Chủ** | `index.html` (hoặc `home.html`) | Giao diện thời trang streetwear cao cấp: Hero section, Banner BST, Phân loại danh mục, Top bán chạy, Sản phẩm mới, Lookbook, Đánh giá, Giỏ hàng drawer, Quick View. |
| **Trang Danh Sách Sản Phẩm** | `products.html` (hoặc `shop.html`) | Danh mục sản phẩm chuyên sâu: Lọc theo Danh mục, Bộ sưu tập, Kích cỡ (Size), Gam màu, Khoảng giá slider, Sắp xếp (Bán chạy, Mới nhất, Giá tăng/giảm), Đổi chế độ xem (Lưới 4 cột, 3 cột, Danh sách). |
| **Trang Quản Trị** | `admin.html` | Hệ thống quản trị cửa hàng: Dashboard KPI, Biểu đồ doanh thu 7d/30d, Quản lý sản phẩm (CRUD), Quản lý đơn hàng, In phiếu hóa đơn, Quản lý Voucher & Cài đặt shop. |

---

## 🏛️ CẤU TRÚC THƯ MỤC DỰ ÁN

```
d:\vaststudio\
├── index.html                  # [V] Trang chủ Khách hàng (Storefront Home)
├── products.html               # [V] Trang danh sách sản phẩm & Bộ lọc (Shop Catalog)
├── admin.html                  # [V] Trang quản trị cửa hàng (Admin Dashboard)
├── home.html                   # [V] Redirect alias -> index.html
├── shop.html                   # [V] Redirect alias -> products.html
│
├── css\
│   ├── styles.css              # Design System, Theme Dark/Light, Tokens toàn cục
│   └── storefront.css          # Giao diện Trang chủ, Trang shop, Giỏ hàng, Modal
│
├── js\
│   ├── icons.js                # Thư viện Vector Icons SVG tích hợp sẵn
│   ├── storefront.js           # [C/V] Điều khiển tương tác Trang chủ & Trang sản phẩm
│   │
│   ├── models\                 # [M] - MODEL LAYER: Quản lý dữ liệu & nghiệp vụ
│   │   └── StoreModel.js       # Quản lý Sản phẩm, Giỏ hàng, Wishlist, Đơn hàng, Voucher, LocalStorage
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
   - Khi khách hàng xem `index.html` hoặc `products.html`, sản phẩm mới hoặc giá mới sẽ hiển thị ngay lập tức.
2. **Khách hàng đặt hàng trên `index.html` hoặc `products.html`**:
   - Đơn hàng được tạo kèm mã `VAST-XXXX` (VD: `VAST-9826`).
   - Tồn kho sản phẩm tự động giảm, số lượt bán tự động tăng.
   - Quản trị viên mở `admin.html` sẽ thấy ngay đơn hàng mới xuất hiện trong danh sách Đơn Hàng cần xử lý!
3. **Mã giảm giá (Coupons)**:
   - Các mã ưu đãi: `VASTWELCOME` (giảm 100k), `FREESHIP` (freeship 300k), `SUMMER50K` (-50k), `VIPMEMBER` (giảm 15%) áp dụng tức thì tại giỏ hàng.

---

## 🚀 HƯỚNG DẪN KHỞI CHẠY HỆ THỐNG

### Cách 1: Khởi chạy bằng Node.js Server (Khuyên dùng)
Mở Terminal tại thư mục dự án và chạy:
```bash
npm start
```
hoặc:
```bash
node server.js
```
- **Trang chủ Khách hàng**: [http://localhost:3000](http://localhost:3000)
- **Trang danh sách sản phẩm**: [http://localhost:3000/products.html](http://localhost:3000/products.html)
- **Trang quản trị Admin**: [http://localhost:3000/admin.html](http://localhost:3000/admin.html)

### Cách 2: Mở trực tiếp trên Trình duyệt
- Nhấp đúp chuột vào file **[index.html](file:///d:/vaststudio/index.html)** để xem Trang chủ.
- Nhấp đúp chuột vào file **[products.html](file:///d:/vaststudio/products.html)** để xem Trang sản phẩm.
- Nhấp đúp chuột vào file **[admin.html](file:///d:/vaststudio/admin.html)** để xem Trang quản trị.

---

© 2026 **VAST STUDIO**. Redefining Modern Streetwear & Minimalist Aesthetics.
