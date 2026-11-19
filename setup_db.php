<?php
/**
 * VAST STUDIO — Automated Database Installer & Seed Runner
 * Run from CLI: php setup_db.php
 * Or open in browser: http://localhost:8000/setup_db.php
 */

header('Content-Type: text/html; charset=UTF-8');

$isCli = (php_sapi_name() === 'cli');

$dbHost = 'localhost';
$dbPort = '3306';
$dbUser = 'root';
$dbPass = '';
$dbName = 'vast_studio';

$sqlFile = __DIR__ . '/database.sql';
$messages = [];
$mysqlSuccess = false;
$sqliteSuccess = false;

// 1. Try connecting to MySQL Server
try {
    $dsn = "mysql:host={$dbHost};port={$dbPort};charset=utf8mb4";
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // Create DB
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
    $pdo->exec("USE `{$dbName}`;");

    // Read and execute database.sql
    if (file_exists($sqlFile)) {
        $sqlContent = file_get_contents($sqlFile);
        $pdo->exec($sqlContent);
        $mysqlSuccess = true;
        $messages[] = "✅ [MySQL] Đã tạo CSDL `{$dbName}` và nạp toàn bộ 9 bảng dữ liệu mẫu thành công!";
    } else {
        $messages[] = "⚠️ [MySQL] Không tìm thấy file `database.sql` để nạp dữ liệu.";
    }
} catch (Exception $e) {
    $messages[] = "ℹ️ [MySQL] Chưa bật MySQL (hoặc Laragon chưa Start All): " . $e->getMessage();
}

// 2. Also initialize SQLite Database (Portable Zero-Setup Fallback)
try {
    $dbDir = __DIR__ . '/database';
    if (!is_dir($dbDir)) {
        mkdir($dbDir, 0777, true);
    }
    $sqlitePath = $dbDir . '/vast_studio.sqlite';
    $sqlitePdo = new PDO("sqlite:" . $sqlitePath);
    $sqlitePdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create minimal schema for SQLite
    $sqlitePdo->exec("
        CREATE TABLE IF NOT EXISTS categories (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            slug TEXT UNIQUE,
            description TEXT,
            icon TEXT DEFAULT 'products'
        );

        CREATE TABLE IF NOT EXISTS collections (
            id TEXT PRIMARY KEY,
            name TEXT NOT NULL,
            slug TEXT UNIQUE,
            description TEXT,
            banner TEXT,
            products_count INTEGER DEFAULT 0,
            featured INTEGER DEFAULT 0
        );

        CREATE TABLE IF NOT EXISTS products (
            id TEXT PRIMARY KEY,
            name TEXT NOT NULL,
            sku TEXT UNIQUE,
            category TEXT NOT NULL,
            collection TEXT,
            price REAL NOT NULL,
            original_price REAL DEFAULT 0,
            stock INTEGER DEFAULT 0,
            sizes TEXT,
            colors TEXT,
            image TEXT,
            description TEXT,
            status TEXT DEFAULT 'active',
            sales_count INTEGER DEFAULT 0,
            rating REAL DEFAULT 5.0,
            is_hot INTEGER DEFAULT 0
        );

        CREATE TABLE IF NOT EXISTS customers (
            id TEXT PRIMARY KEY,
            name TEXT NOT NULL,
            email TEXT UNIQUE,
            phone TEXT UNIQUE,
            password TEXT DEFAULT '123456',
            city TEXT DEFAULT 'Hà Nội',
            orders_count INTEGER DEFAULT 0,
            total_spent REAL DEFAULT 0,
            tier TEXT DEFAULT 'Standard'
        );

        CREATE TABLE IF NOT EXISTS coupons (
            id TEXT PRIMARY KEY,
            code TEXT UNIQUE,
            description TEXT,
            type TEXT DEFAULT 'fixed',
            value REAL NOT NULL,
            min_order REAL DEFAULT 0,
            usage_limit INTEGER DEFAULT 1000,
            usage_count INTEGER DEFAULT 0,
            status TEXT DEFAULT 'active'
        );

        CREATE TABLE IF NOT EXISTS orders (
            id TEXT PRIMARY KEY,
            customer_name TEXT NOT NULL,
            customer_phone TEXT NOT NULL,
            customer_email TEXT,
            shipping_address TEXT NOT NULL,
            shipping_method TEXT,
            subtotal REAL NOT NULL,
            shipping_fee REAL DEFAULT 0,
            discount REAL DEFAULT 0,
            coupon_code TEXT,
            total REAL NOT NULL,
            payment_method TEXT,
            payment_status TEXT DEFAULT 'Chưa thanh toán',
            order_status TEXT DEFAULT 'pending',
            note TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
    ");

    // Populate SQLite if empty
    $count = $sqlitePdo->query("SELECT count(*) FROM products")->fetchColumn();
    if ($count == 0) {
        $sqlitePdo->exec("
            INSERT OR IGNORE INTO categories (id, name, slug, description) VALUES
            (1, 'Áo thun', 'ao-thun', 'Áo thun streetwear'),
            (2, 'Áo sơ mi', 'ao-so-mi', 'Áo sơ mi oversized'),
            (3, 'Hoodie & Sweater', 'hoodie-sweater', 'Hoodie cao cấp'),
            (4, 'Quần', 'quan', 'Quần ống rộng và cargo'),
            (5, 'Áo khoác', 'ao-khoac', 'Áo khoác streetwear');

            INSERT OR IGNORE INTO products (id, name, sku, category, collection, price, original_price, stock, sizes, colors, image, description, sales_count, rating, is_hot) VALUES
            ('PRD-001', 'Áo Thun VAST Heavyweight Boxy Tee', 'VAST-TSH-01', 'Áo thun', 'Streetwear Core', 380000, 450000, 45, 'S, M, L, XL', 'Đen Washed, Trắng Vintage', 'https://images.unsplash.com/photo-1521572267360-ee0c2909d518?w=500&auto=format&fit=crop&q=80', '100% Cotton 280GSM dày dặn chuẩn form boxy.', 142, 5.0, 1),
            ('PRD-002', 'Áo Sơ Mi VAST Oversized Oxford', 'VAST-SHR-02', 'Áo sơ mi', 'Minimalist Essentials', 520000, 590000, 28, 'M, L, XL', 'Xanh Pastel, Trắng Sữa', 'https://images.unsplash.com/photo-1596755094514-f87e34085b2c?w=500&auto=format&fit=crop&q=80', 'Chất vải Oxford dệt mật độ cao chống nhăn nhẹ.', 98, 4.9, 1),
            ('PRD-003', 'Hoodie VAST Acid Wash Vintage Drop', 'VAST-HD-03', 'Hoodie & Sweater', 'Monochrome Season 2026', 690000, 790000, 18, 'S, M, L, XL', 'Xám Bạc Acid, Đen', 'https://images.unsplash.com/photo-1556905055-8f358a7a47b2?w=500&auto=format&fit=crop&q=80', 'Nỉ bông lót cào cao cấp 400GSM siêu ấm.', 86, 5.0, 1),
            ('PRD-004', 'Quần Parachute Cargo Pants VAST', 'VAST-PNT-04', 'Quần', 'Streetwear Core', 620000, 690000, 32, 'M, L, XL', 'Đen Obsidian, Be Cát', 'https://images.unsplash.com/photo-1624378439575-d8705ad7ae80?w=500&auto=format&fit=crop&q=80', 'Chất vải dù chống nước nhẹ, khóa rút gấu đa năng.', 110, 4.8, 1);

            INSERT OR IGNORE INTO customers (id, name, email, phone, password, city, tier, orders_count, total_spent) VALUES
            ('CUS-101', 'Nguyễn Hoàng Minh', 'hoangminh.design@gmail.com', '0912 345 678', '123456', 'Hà Nội', 'VIP Black Card', 8, 9450000),
            ('CUS-102', 'Trần Thảo Ly', 'thaoly.tran@outlook.com', '0978 998 877', '123456', 'TP.HCM', 'Gold Member', 5, 4820000);

            INSERT OR IGNORE INTO coupons (id, code, description, type, value, min_order, usage_limit, usage_count) VALUES
            ('CPN-01', 'VASTWELCOME', 'Ưu đãi chào mừng thành viên mới giảm 100k', 'fixed', 100000, 500000, 500, 284),
            ('CPN-02', 'FREESHIP', 'Miễn phí vận chuyển toàn quốc', 'fixed', 30000, 300000, 1000, 890);
        ");
    }
    $sqliteSuccess = true;
    $messages[] = "✅ [SQLite] Đã tạo cơ sở dữ liệu SQLite dự phòng tại `database/vast_studio.sqlite` (Hoạt động 100% không cần bật MySQL)!";
} catch (Exception $sqe) {
    $messages[] = "⚠️ [SQLite Error] " . $sqe->getMessage();
}

if ($isCli) {
    echo "\n=======================================================\n";
    echo "       VAST STUDIO — DATABASE SETUP COMPLETED          \n";
    echo "=======================================================\n\n";
    foreach ($messages as $msg) {
        echo $msg . "\n";
    }
    echo "\n";
    exit(0);
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Khởi Tạo Database — VAST STUDIO</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #09090b; color: #f4f4f5; padding: 40px 20px; line-height: 1.6; }
        .box { max-width: 640px; margin: 0 auto; background: #18181b; border: 1px solid #27272a; border-radius: 12px; padding: 32px; box-shadow: 0 10px 25px rgba(0,0,0,0.5); }
        h1 { font-size: 1.6rem; margin-top: 0; color: #d97706; text-transform: uppercase; letter-spacing: -0.02em; }
        .msg { padding: 12px 16px; border-radius: 8px; margin-bottom: 12px; font-size: 0.92rem; background: #27272a; }
        .success { border-left: 4px solid #10b981; }
        .info { border-left: 4px solid #3b82f6; }
        .btn { display: inline-block; padding: 12px 24px; background: #f4f4f5; color: #09090b; text-decoration: none; font-weight: 700; border-radius: 8px; margin-top: 20px; }
        .btn:hover { background: #d97706; color: #fff; }
    </style>
</head>
<body>
    <div class="box">
        <h1>Khởi Tạo Database VAST STUDIO</h1>
        <p style="color: #a1a1aa; font-size: 0.95rem;">Trạng thái thiết lập cơ sở dữ liệu hệ thống:</p>
        
        <?php foreach ($messages as $msg): ?>
            <div class="msg <?= strpos($msg, '✅') !== false ? 'success' : 'info' ?>">
                <?= htmlspecialchars($msg) ?>
            </div>
        <?php endforeach; ?>

        <div style="margin-top: 24px; padding-top: 16px; border-top: 1px dashed #3f3f46;">
            <strong>Thông tin kết nối CSDL (config/db.php):</strong>
            <ul style="font-size: 0.88rem; color: #a1a1aa; padding-left: 20px;">
                <li>MySQL Database: <code style="color:#d97706;">vast_studio</code></li>
                <li>MySQL Host / Port: <code style="color:#d97706;">localhost:3306</code></li>
                <li>MySQL User / Pass: <code style="color:#d97706;">root</code> / <em>(trống)</em></li>
                <li>File SQL gốc: <code style="color:#d97706;">database.sql</code></li>
                <li>SQLite dự phòng: <code style="color:#d97706;">database/vast_studio.sqlite</code></li>
            </ul>
        </div>

        <a href="index.php" class="btn">Đi Đến Trang Chủ VAST STUDIO →</a>
    </div>
</body>
</html>
