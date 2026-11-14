# VAST STUDIO — HỆ THỐNG QUẢN TRỊ CỬA HÀNG THỜI TRANG (CHUẨN KIẾN TRÚC MVC)

Hệ thống quản trị **VAST STUDIO** được thiết kế và tổ chức chuẩn mực theo mô hình kiến trúc **MVC (Model — View — Controller)**, phân tách hoàn toàn giữa Dữ liệu nghiệp vụ, Giao diện hiển thị và Luồng điều khiển.

---

## 🏛️ CHI TIẾT CẤU TRÚC KIẾN TRÚC MVC CỦA DỰ ÁN

```
d:\vast studio\
├── index.html                  # Khung View chính và các cấu trúc Modal
├── css\
│   └── styles.css              # Design System, Theme Dark/Light, Responsive
│
├── js\
│   ├── icons.js                # Thư viện Vector Icons SVG tích hợp
│   │
│   ├── models\                 # [M] - MODEL LAYER: Quản lý dữ liệu & nghiệp vụ
│   │   └── StoreModel.js       # Thực thể Sản phẩm, Đơn hàng, Voucher, Khách hàng, LocalStorage
│   │
│   ├── views\                  # [V] - VIEW LAYER: Kết xuất giao diện (DOM Rendering)
│   │   ├── CommonView.js       # Toast notifications, Quản lý Modal, Đổi Theme
│   │   ├── DashboardView.js    # Biểu đồ SVG, thẻ thống kê KPI, danh sách bán chạy
│   │   ├── ProductView.js      # Bảng sản phẩm, form thêm/sửa, badge tồn kho
│   │   └── OrderView.js        # Bảng đơn hàng, popup chi tiết, in hóa đơn giao hàng
│   │
│   ├── controllers\            # [C] - CONTROLLER LAYER: Điều khiển & Xử lý nghiệp vụ
│   │   ├── ProductController.js# Bắt sự kiện form sản phẩm, tìm kiếm, lọc kho, xóa
│   │   ├── OrderController.js  # Xử lý cập nhật trạng thái đơn, lọc tab, in phiếu
│   │   ├── DashboardController.js # Tính toán số liệu thống kê, chuyển đổi biểu đồ 7d/30d
│   │   └── AppController.js    # Điều hướng chuyển tab, cài đặt shop, sự kiện toàn cục
│   │
│   └── app.js                  # Entry Point: Bootstrap ứng dụng & kết nối các tầng MVC
│
├── server.js                   # Web Server tĩnh Node.js (Zero-dependency)
├── package.json                # Cấu hình dự án & Scripts
└── README.md                   # Tài liệu hướng dẫn
```

---

## 🔄 NGUYÊN LÝ HOẠT ĐỘNG CỦA MÔ HÌNH MVC TẠI VAST STUDIO

1. **Người dùng tương tác (User Interaction)**:
   - Khi người dùng click nút, nhập ô tìm kiếm, chọn lọc sản phẩm hoặc đổi trạng thái đơn hàng trên giao diện (`View`).
2. **Controller tiếp nhận xử lý (Controller Execution)**:
   - Sự kiện được chuyển đến Controller tương ứng (`ProductController`, `OrderController`,...). Controller kiểm tra dữ liệu đầu vào và quyết định hành động nghiệp vụ.
3. **Model cập nhật trạng thái (Model Update)**:
   - Controller gọi các hàm trong `StoreModel` để thêm/sửa/xóa hoặc truy vấn dữ liệu. Model tự động đồng bộ hóa xuống `localStorage`.
4. **View kết xuất lại màn hình (View Re-render)**:
   - Sau khi Model cập nhật thành công, Controller điều khiển `View` kết xuất lại giao diện mới nhất và kích hoạt `CommonView.showToast` để thông báo cho người dùng.

---

## 🚀 HƯỚNG DẪN KHỞI CHẠY HỆ THỐNG

### Cách 1: Mở trực tiếp (Nhanh nhất)
Nhấp đúp chuột vào file **[index.html](file:///d:/vast%20studio/index.html)** để mở ngay trên trình duyệt web.

### Cách 2: Khởi chạy qua Node.js Server
Mở Terminal / Command Prompt tại thư mục `d:\vast studio`:
```bash
npm start
```
hoặc:
```bash
node server.js
```
Truy cập: **`http://localhost:3000`**

---

© 2026 **VAST STUDIO**. Redefining Modern Streetwear & Minimalist Aesthetics.
