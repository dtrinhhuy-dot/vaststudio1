-- =============================================================================
-- VAST STUDIO — DATABASE SCHEMA & SEED DATA (MySQL / MariaDB)
-- E-Commerce Fashion Management System
-- Character Set: utf8mb4 | Collation: utf8mb4_unicode_ci
-- =============================================================================

CREATE DATABASE IF NOT EXISTS `vast_studio` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `vast_studio`;

-- Disable foreign key checks for clean drops & imports
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `order_items`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `reviews`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `collections`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `customers`;
DROP TABLE IF EXISTS `coupons`;
DROP TABLE IF EXISTS `settings`;

SET FOREIGN_KEY_CHECKS = 1;

-- -----------------------------------------------------------------------------
-- 1. TABLE: categories (Danh mục sản phẩm)
-- -----------------------------------------------------------------------------
CREATE TABLE `categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL UNIQUE,
    `description` TEXT,
    `icon` VARCHAR(50) DEFAULT 'products',
    `status` ENUM('active', 'inactive') DEFAULT 'active',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `icon`) VALUES
(1, 'Áo thun', 'ao-thun', 'Áo thun phong cách streetwear, heavyweight cotton định lượng cao', 'products'),
(2, 'Áo sơ mi', 'ao-so-mi', 'Áo sơ mi thiết kế oversized, oxford cao cấp', 'products'),
(3, 'Hoodie & Sweater', 'hoodie-sweater', 'Áo nỉ chui đầu, nỉ bông wash vintage dày dặn', 'products'),
(4, 'Quần', 'quan', 'Quần ống suông, parachute cargo, wide-leg raw denim', 'products'),
(5, 'Áo khoác', 'ao-khoac', 'Áo khoác bomber da dạ, varsity jacket, trench coat', 'products'),
(6, 'Phụ kiện', 'phu-kien', 'Mũ beanie, túi đeo chéo, thắt lưng streetwear', 'tag');

-- -----------------------------------------------------------------------------
-- 2. TABLE: collections (Bộ sưu tập theo mùa / concept)
-- -----------------------------------------------------------------------------
CREATE TABLE `collections` (
    `id` VARCHAR(20) PRIMARY KEY,
    `name` VARCHAR(150) NOT NULL,
    `slug` VARCHAR(150) NOT NULL UNIQUE,
    `description` TEXT,
    `banner` VARCHAR(255),
    `products_count` INT DEFAULT 0,
    `featured` TINYINT(1) DEFAULT 0,
    `status` ENUM('active', 'inactive') DEFAULT 'active',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `collections` (`id`, `name`, `slug`, `description`, `banner`, `products_count`, `featured`) VALUES
('COL-001', 'Monochrome Season 2026', 'monochrome-2026', 'Tối giản với gam màu đen trắng và xám khói kinh điển, đề cao form dáng cấu trúc và chất liệu dệt bền vững.', 'https://images.unsplash.com/photo-1490481651871-ab68de25d43d?w=800&auto=format&fit=crop&q=80', 14, 1),
('COL-002', 'Streetwear Core', 'streetwear-core', 'Trái tim của VAST STUDIO: Heavyweight Tees, Parachute Pants, và Wide-leg Denim đậm chất đường phố đương đại.', 'https://images.unsplash.com/photo-1509631179647-0177331693ae?w=800&auto=format&fit=crop&q=80', 22, 1),
('COL-003', 'Minimalist Essentials', 'minimalist-essentials', 'Những món đồ tinh tế không bao giờ lỗi mốt: Sơ mi Oxford drop-shoulder, quần tây ống đứng và áo knit cao cấp.', 'https://images.unsplash.com/photo-1489987707025-afc232f7ea0f?w=800&auto=format&fit=crop&q=80', 18, 0),
('COL-004', 'Winter Outerwear Drop', 'winter-outerwear', 'Bộ sưu tập áo khoác Bomber da dạ, Trench coat tối giản và Puffer jackets đón gió mùa.', 'https://images.unsplash.com/photo-1544441893-675973e31985?w=800&auto=format&fit=crop&q=80', 9, 1);

-- -----------------------------------------------------------------------------
-- 3. TABLE: products (Danh sách sản phẩm thời trang)
-- -----------------------------------------------------------------------------
CREATE TABLE `products` (
    `id` VARCHAR(20) PRIMARY KEY,
    `name` VARCHAR(200) NOT NULL,
    `sku` VARCHAR(50) UNIQUE,
    `category` VARCHAR(100) NOT NULL,
    `collection` VARCHAR(150),
    `price` DECIMAL(12, 0) NOT NULL,
    `original_price` DECIMAL(12, 0) DEFAULT 0,
    `stock` INT NOT NULL DEFAULT 0,
    `sizes` VARCHAR(100) DEFAULT 'S, M, L, XL',
    `colors` VARCHAR(255) DEFAULT 'Đen, Trắng',
    `image` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `status` ENUM('active', 'inactive', 'out_of_stock') DEFAULT 'active',
    `sales_count` INT DEFAULT 0,
    `rating` DECIMAL(2, 1) DEFAULT 5.0,
    `is_hot` TINYINT(1) DEFAULT 0,
    `is_new` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `products` (`id`, `name`, `sku`, `category`, `collection`, `price`, `original_price`, `stock`, `sizes`, `colors`, `image`, `description`, `sales_count`, `rating`, `is_hot`) VALUES
('PRD-001', 'Áo Thun VAST Heavyweight Boxy Tee', 'VAST-TSH-01', 'Áo thun', 'Streetwear Core', 380000, 450000, 45, 'S, M, L, XL', 'Đen Washed, Trắng Vintage, Xám Ti-tan', 'https://images.unsplash.com/photo-1521572267360-ee0c2909d518?w=500&auto=format&fit=crop&q=80', 'Chất liệu 100% Cotton 280GSM dày dặn, form boxy đứng dáng, đường may trần đè kỹ thuật cao. Thêu logo VAST viền kim loại cao cấp sau gáy.', 142, 5.0, 1),
('PRD-002', 'Áo Sơ Mi VAST Oversized Oxford', 'VAST-SHR-02', 'Áo sơ mi', 'Minimalist Essentials', 520000, 590000, 28, 'M, L, XL', 'Xanh Pastel, Trắng Sữa, Kẻ Sọc Nhuyễn', 'https://images.unsplash.com/photo-1596755094514-f87e34085b2c?w=500&auto=format&fit=crop&q=80', 'Chất vải Oxford dệt mật độ cao chống nhăn nhẹ, thiết kế drop-shoulder hiện đại, cúc xà cừ khắc chìm chữ VAST sang trọng.', 98, 4.9, 1),
('PRD-003', 'Hoodie VAST Acid Wash Vintage Drop', 'VAST-HD-03', 'Hoodie & Sweater', 'Monochrome Season 2026', 690000, 790000, 18, 'S, M, L, XL', 'Xám Bạc Acid, Đen Tuyển', 'https://images.unsplash.com/photo-1556905055-8f358a7a47b2?w=500&auto=format&fit=crop&q=80', 'Nỉ bông lót cào cao cấp 400GSM siêu ấm. Xử lý wash hiệu ứng sờn rách nghệ thuật, mũ 2 lớp phồng đứng, túi kangaroo giấu zip.', 86, 5.0, 1),
('PRD-004', 'Quần Parachute Cargo Pants VAST', 'VAST-PNT-04', 'Quần', 'Streetwear Core', 620000, 690000, 32, 'M, L, XL', 'Đen Obsidian, Xanh Rêu Sage, Be Cát', 'https://images.unsplash.com/photo-1624378439575-d8705ad7ae80?w=500&auto=format&fit=crop&q=80', 'Chất vải dù chống nước nhẹ (water-repellent), khóa rút gấu đa năng chuyển đổi giữa ống suông và ống bo thời thượng. 6 túi tiện dụng.', 110, 4.8, 1),
('PRD-005', 'Quần Wide-Leg Raw Denim VAST', 'VAST-DNM-05', 'Quần', 'Streetwear Core', 680000, 750000, 12, '29, 30, 31, 32', 'Xanh Indigo Chàm, Đen Tuyển', 'https://images.unsplash.com/photo-1542272604-787c3835535d?w=500&auto=format&fit=crop&q=80', 'Denim dệt thoi 13oz bền bỉ chuẩn phong cách Nhật Bản. Form ống suông rộng tôn dáng, tag da bò VAST STUDIO dập nhiệt sau cạp.', 64, 4.9, 0),
('PRD-006', 'Áo Khoác Varsity Bomber Da & Dạ VAST', 'VAST-JKT-06', 'Áo khoác', 'Winter Outerwear Drop', 1250000, 1450000, 6, 'M, L', 'Đen Phối Trắng, Xanh Lục Bảo', 'https://images.unsplash.com/photo-1551028719-00167b16eac5?w=500&auto=format&fit=crop&q=80', 'Thân áo dạ cừu tổng hợp giữ nhiệt, tay da PU lì chống nổ cao cấp. Hình thêu xù logo VAST nổi 3D trước ngực và lưng áo.', 39, 5.0, 1),
('PRD-007', 'Áo Len Dệt Kim Textured Knit Sweater', 'VAST-SWT-07', 'Hoodie & Sweater', 'Minimalist Essentials', 580000, 650000, 3, 'M, L', 'Kem Vanilla, Nâu Mocha', 'https://images.unsplash.com/photo-1620799140408-edc6dcb6d633?w=500&auto=format&fit=crop&q=80', 'Chất sợi len dệt quả trám mềm mịn không dặm ngứa. Form oversize trẻ trung, phối đồ cực chuẩn mùa lạnh.', 47, 4.7, 0),
('PRD-008', 'Mũ Beanie VAST Metal Badge', 'VAST-ACC-08', 'Phụ kiện', 'Minimalist Essentials', 220000, 280000, 55, 'Freesize', 'Đen, Xám Khói, Nâu', 'https://images.unsplash.com/photo-1576871337632-b9aef4c17ab9?w=500&auto=format&fit=crop&q=80', 'Len gân co giãn 4 chiều ôm form đầu chuẩn. Đính tag kim loại mạ bạc chống gỉ tinh tế mang tính biểu tượng của hãng.', 168, 4.9, 1);

-- -----------------------------------------------------------------------------
-- 4. TABLE: customers (Khách hàng & Tài khoản thành viên)
-- -----------------------------------------------------------------------------
CREATE TABLE `customers` (
    `id` VARCHAR(20) PRIMARY KEY,
    `name` VARCHAR(150) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `phone` VARCHAR(20) NOT NULL UNIQUE,
    `password` VARCHAR(255) DEFAULT '123456',
    `city` VARCHAR(100) DEFAULT 'Hà Nội',
    `address` TEXT,
    `orders_count` INT DEFAULT 0,
    `total_spent` DECIMAL(14, 0) DEFAULT 0,
    `tier` VARCHAR(50) DEFAULT 'Standard',
    `status` ENUM('active', 'locked') DEFAULT 'active',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `customers` (`id`, `name`, `email`, `phone`, `password`, `city`, `orders_count`, `total_spent`, `tier`) VALUES
('CUS-101', 'Nguyễn Hoàng Minh', 'hoangminh.design@gmail.com', '0912 345 678', '123456', 'Hà Nội', 8, 9450000, 'VIP Black Card'),
('CUS-102', 'Trần Thảo Ly', 'thaoly.tran@outlook.com', '0978 998 877', '123456', 'TP.HCM', 5, 4820000, 'Gold Member'),
('CUS-103', 'Lê Tuấn Hưng', 'tuanhung.le@gmail.com', '0903 112 233', '123456', 'Đà Nẵng', 3, 2950000, 'Silver Member'),
('CUS-104', 'Phạm Đức Anh', 'ducanh.arch@gmail.com', '0934 556 789', '123456', 'TP.HCM', 12, 16800000, 'VIP Black Card'),
('CUS-105', 'Đặng Ngọc Quỳnh', 'quynh.dang@gmail.com', '0918 776 554', '123456', 'Cần Thơ', 2, 1120000, 'Standard');

-- -----------------------------------------------------------------------------
-- 5. TABLE: coupons (Mã giảm giá / Voucher)
-- -----------------------------------------------------------------------------
CREATE TABLE `coupons` (
    `id` VARCHAR(20) PRIMARY KEY,
    `code` VARCHAR(50) NOT NULL UNIQUE,
    `description` VARCHAR(255),
    `type` ENUM('fixed', 'percent') DEFAULT 'fixed',
    `value` DECIMAL(12, 0) NOT NULL,
    `min_order` DECIMAL(12, 0) DEFAULT 0,
    `usage_limit` INT DEFAULT 1000,
    `usage_count` INT DEFAULT 0,
    `expiry_date` DATE DEFAULT '2026-12-31',
    `status` ENUM('active', 'expired', 'disabled') DEFAULT 'active',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `coupons` (`id`, `code`, `description`, `type`, `value`, `min_order`, `usage_limit`, `usage_count`, `expiry_date`, `status`) VALUES
('CPN-01', 'VASTWELCOME', 'Ưu đãi chào mừng thành viên mới giảm 100k cho đơn từ 500k', 'fixed', 100000, 500000, 500, 284, '2026-12-31', 'active'),
('CPN-02', 'VAST50', 'Giảm trực tiếp 50K cho đơn hàng từ 300K', 'fixed', 50000, 300000, 500, 115, '2026-12-31', 'active'),
('CPN-03', 'FREESHIP', 'Miễn phí vận chuyển toàn quốc cho đơn từ 300k', 'fixed', 30000, 300000, 1000, 890, '2026-12-31', 'active'),
('CPN-04', 'VIP15', 'Giảm 15% tổng giá trị đơn hàng cho khách VIP', 'percent', 15, 800000, 200, 72, '2026-12-31', 'active');

-- -----------------------------------------------------------------------------
-- 6. TABLE: orders (Đơn đặt hàng)
-- -----------------------------------------------------------------------------
CREATE TABLE `orders` (
    `id` VARCHAR(30) PRIMARY KEY,
    `customer_name` VARCHAR(150) NOT NULL,
    `customer_phone` VARCHAR(20) NOT NULL,
    `customer_email` VARCHAR(150),
    `shipping_address` TEXT NOT NULL,
    `shipping_method` VARCHAR(100) DEFAULT 'Giao Tiêu Chuẩn (2-3 ngày)',
    `subtotal` DECIMAL(14, 0) NOT NULL,
    `shipping_fee` DECIMAL(10, 0) DEFAULT 0,
    `discount` DECIMAL(10, 0) DEFAULT 0,
    `coupon_code` VARCHAR(50),
    `total` DECIMAL(14, 0) NOT NULL,
    `payment_method` VARCHAR(100) DEFAULT 'COD (Thanh toán khi nhận hàng)',
    `payment_status` ENUM('Chưa thanh toán', 'Đã thanh toán', 'Đã hoàn tiền') DEFAULT 'Chưa thanh toán',
    `order_status` ENUM('pending', 'processing', 'shipping', 'completed', 'cancelled') DEFAULT 'pending',
    `note` TEXT,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `orders` (`id`, `customer_name`, `customer_phone`, `customer_email`, `shipping_address`, `shipping_method`, `subtotal`, `shipping_fee`, `discount`, `coupon_code`, `total`, `payment_method`, `payment_status`, `order_status`, `note`, `created_at`) VALUES
('VAST-9825', 'Nguyễn Hoàng Minh', '0912 345 678', 'hoangminh.design@gmail.com', 'Tầng 8, Tòa Keangnam Landmark 72, Phạm Hùng, Q. Nam Từ Liêm, Hà Nội', 'Giao Tiêu Chuẩn (2-3 ngày)', 1380000, 0, 100000, 'VASTWELCOME', 1280000, 'Chuyển khoản VietQR (Vietcombank)', 'Đã thanh toán', 'pending', 'Giao giờ hành chính, gọi trước 15 phút giúp mình nhé.', '2026-11-14 14:20:00'),
('VAST-9824', 'Trần Thảo Ly', '0978 998 877', 'thaoly.tran@outlook.com', 'Căn hộ 12B, Chung cư Masteri Thảo Điền, P. Thảo Điền, TP. Thủ Đức, TP.HCM', 'Giao Hỏa Tốc Express (2-4 giờ)', 740000, 50000, 0, NULL, 790000, 'Chuyển khoản VietQR (Vietcombank)', 'Đã thanh toán', 'processing', 'Đóng gói cẩn thận giúp mình làm quà tặng bạn.', '2026-11-14 10:45:00'),
('VAST-9823', 'Lê Tuấn Hưng', '0903 112 233', 'tuanhung.le@gmail.com', '45/12 Nguyễn Văn Linh, P. Nam Dương, Q. Hải Châu, Đà Nẵng', 'Giao Tiêu Chuẩn (2-3 ngày)', 1250000, 0, 100000, 'VASTWELCOME', 1150000, 'COD (Thanh toán khi nhận hàng)', 'Chưa thanh toán', 'shipping', 'Giao buổi chiều sau 17h.', '2026-11-13 18:30:00'),
('VAST-9822', 'Phạm Đức Anh', '0934 556 789', 'ducanh.arch@gmail.com', '15 Lê Duẩn, P. Bến Nghé, Quận 1, TP.HCM', 'Giao Tiêu Chuẩn (2-3 ngày)', 1370000, 0, 0, NULL, 1370000, 'Ví điện tử MoMo', 'Đã thanh toán', 'completed', 'Để trước cửa nhà nếu không có ai.', '2026-11-12 09:15:00');

-- -----------------------------------------------------------------------------
-- 7. TABLE: order_items (Chi tiết mặt hàng trong đơn)
-- -----------------------------------------------------------------------------
CREATE TABLE `order_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` VARCHAR(30) NOT NULL,
    `product_id` VARCHAR(20) NOT NULL,
    `name` VARCHAR(200) NOT NULL,
    `size` VARCHAR(20) NOT NULL,
    `color` VARCHAR(50) NOT NULL,
    `price` DECIMAL(12, 0) NOT NULL,
    `quantity` INT NOT NULL DEFAULT 1,
    `image` VARCHAR(255),
    CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `order_items` (`order_id`, `product_id`, `name`, `size`, `color`, `price`, `quantity`, `image`) VALUES
('VAST-9825', 'PRD-001', 'Áo Thun VAST Heavyweight Boxy Tee', 'L', 'Đen Washed', 380000, 2, 'https://images.unsplash.com/photo-1521572267360-ee0c2909d518?w=500&auto=format&fit=crop&q=80'),
('VAST-9825', 'PRD-004', 'Quần Parachute Cargo Pants VAST', 'L', 'Đen Obsidian', 620000, 1, 'https://images.unsplash.com/photo-1624378439575-d8705ad7ae80?w=500&auto=format&fit=crop&q=80'),
('VAST-9824', 'PRD-002', 'Áo Sơ Mi VAST Oversized Oxford', 'M', 'Trắng Sữa', 520000, 1, 'https://images.unsplash.com/photo-1596755094514-f87e34085b2c?w=500&auto=format&fit=crop&q=80'),
('VAST-9824', 'PRD-008', 'Mũ Beanie VAST Metal Badge', 'Freesize', 'Đen', 220000, 1, 'https://images.unsplash.com/photo-1576871337632-b9aef4c17ab9?w=500&auto=format&fit=crop&q=80'),
('VAST-9823', 'PRD-006', 'Áo Khoác Varsity Bomber Da & Dạ VAST', 'L', 'Đen Phối Trắng', 1250000, 1, 'https://images.unsplash.com/photo-1551028719-00167b16eac5?w=500&auto=format&fit=crop&q=80'),
('VAST-9822', 'PRD-003', 'Hoodie VAST Acid Wash Vintage Drop', 'XL', 'Xám Bạc Acid', 690000, 1, 'https://images.unsplash.com/photo-1556905055-8f358a7a47b2?w=500&auto=format&fit=crop&q=80'),
('VAST-9822', 'PRD-005', 'Quần Wide-Leg Raw Denim VAST', '31', 'Xanh Indigo Chàm', 680000, 1, 'https://images.unsplash.com/photo-1542272604-787c3835535d?w=500&auto=format&fit=crop&q=80');

-- -----------------------------------------------------------------------------
-- 8. TABLE: reviews (Nhận xét & đánh giá sản phẩm)
-- -----------------------------------------------------------------------------
CREATE TABLE `reviews` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `product_id` VARCHAR(20) NOT NULL,
    `author` VARCHAR(100) NOT NULL,
    `city` VARCHAR(50) DEFAULT 'Việt Nam',
    `rating` INT NOT NULL DEFAULT 5,
    `variant` VARCHAR(100),
    `content` TEXT NOT NULL,
    `created_at` DATE DEFAULT (CURRENT_DATE),
    CONSTRAINT `fk_reviews_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `reviews` (`product_id`, `author`, `city`, `rating`, `variant`, `content`, `created_at`) VALUES
('PRD-001', 'Hoàng Minh', 'Hà Nội', 5, 'Size L • Đen Washed', 'Chất vải dệt rất đầm tay, mặc đứng form chuẩn boxy. Giặt máy không bị xù lông hay bai cổ áo, xứng đáng từng đồng.', '2026-11-12'),
('PRD-002', 'Thảo Ly', 'TP.HCM', 5, 'Size M • Trắng Vintage', 'Giao hàng siêu nhanh, đóng gói hộp đen xịn sò như đồ luxury. Đường may trần đè 2 kim kỹ lưỡng, rất tôn dáng.', '2026-11-05'),
('PRD-004', 'Đức Anh', 'Đà Nẵng', 5, 'Size XL • Xám Ti-tan', 'Phối với quần wide leg denim cực kỳ hợp gu. Sẽ tiếp tục ủng hộ VAST STUDIO ở các drop sau!', '2026-10-28');

-- -----------------------------------------------------------------------------
-- 9. TABLE: settings (Cấu hình hệ thống & thông tin cửa hàng)
-- -----------------------------------------------------------------------------
CREATE TABLE `settings` (
    `id` INT PRIMARY KEY DEFAULT 1,
    `shop_name` VARCHAR(150) DEFAULT 'VAST STUDIO',
    `tagline` VARCHAR(255) DEFAULT 'Modern Streetwear & Minimalist Aesthetics',
    `hotline` VARCHAR(20) DEFAULT '0988.123.456',
    `email` VARCHAR(150) DEFAULT 'contact@vaststudio.vn',
    `address` TEXT DEFAULT '128 Phố Huế, Q. Hai Bà Trưng, Hà Nội',
    `free_ship_threshold` DECIMAL(12, 0) DEFAULT 500000,
    `standard_ship_fee` DECIMAL(10, 0) DEFAULT 30000,
    `express_ship_fee` DECIMAL(10, 0) DEFAULT 50000,
    `bank_name` VARCHAR(100) DEFAULT 'Vietcombank',
    `bank_branch` VARCHAR(100) DEFAULT 'Thăng Long',
    `bank_account_no` VARCHAR(50) DEFAULT '9988 7766 5500',
    `bank_account_name` VARCHAR(150) DEFAULT 'CONG TY TNHH VAST STUDIO VIETNAM',
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `settings` (`id`, `shop_name`, `tagline`, `hotline`, `email`, `address`, `free_ship_threshold`, `standard_ship_fee`, `express_ship_fee`, `bank_name`, `bank_branch`, `bank_account_no`, `bank_account_name`) VALUES
(1, 'VAST STUDIO', 'Modern Streetwear & Minimalist Aesthetics', '0988.123.456', 'contact@vaststudio.vn', '128 Phố Huế, Q. Hai Bà Trưng, Hà Nội', 500000, 30000, 50000, 'Vietcombank', 'Thăng Long', '9988 7766 5500', 'CONG TY TNHH VAST STUDIO VIETNAM');
