<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../includes/database.php';

$db = getDatabaseConnection();
$collections = [
    ['name' => 'Streetwear Core', 'slug' => 'streetwear-core', 'description' => 'Các thiết kế streetwear chủ đạo của VAST STUDIO.'],
    ['name' => 'Minimalist Essentials', 'slug' => 'minimalist-essentials', 'description' => 'Những món đồ tối giản, dễ phối và bền dáng.'],
    ['name' => 'Monochrome Season 2026', 'slug' => 'monochrome-2026', 'description' => 'Bộ sưu tập đen, trắng và xám khói.'],
    ['name' => 'Winter Outerwear Drop', 'slug' => 'winter-outerwear', 'description' => 'Các thiết kế áo khoác cho mùa lạnh.'],
];

$products = [
    ['id' => 'PRD-001', 'name' => 'Áo Thun VAST Heavyweight Boxy Tee', 'sku' => 'VAST-TSH-01', 'category' => 'Áo thun', 'collection' => 'streetwear-core', 'price' => 380000, 'original_price' => 450000, 'stock' => 45, 'sizes' => ['S', 'M', 'L', 'XL'], 'colors' => ['Đen Washed', 'Trắng Vintage', 'Xám Ti-tan'], 'image' => 'https://images.unsplash.com/photo-1521572267360-ee0c2909d518?w=500&auto=format&fit=crop&q=80', 'description' => 'Cotton 280GSM, form boxy đứng dáng, thêu logo VAST sau gáy.', 'sales_count' => 142, 'created_at' => '2026-09-10'],
    ['id' => 'PRD-002', 'name' => 'Áo Sơ Mi VAST Oversized Oxford', 'sku' => 'VAST-SHR-02', 'category' => 'Áo sơ mi', 'collection' => 'minimalist-essentials', 'price' => 520000, 'original_price' => 590000, 'stock' => 28, 'sizes' => ['M', 'L', 'XL'], 'colors' => ['Xanh Pastel', 'Trắng Sữa', 'Kẻ Sọc Nhuyễn'], 'image' => 'https://images.unsplash.com/photo-1596755094514-f87e34085b2c?w=500&auto=format&fit=crop&q=80', 'description' => 'Vải Oxford chống nhăn nhẹ, thiết kế drop-shoulder hiện đại.', 'sales_count' => 98, 'created_at' => '2026-09-15'],
    ['id' => 'PRD-003', 'name' => 'Hoodie VAST Acid Wash Vintage Drop', 'sku' => 'VAST-HD-03', 'category' => 'Hoodie & Sweater', 'collection' => 'monochrome-2026', 'price' => 690000, 'original_price' => 790000, 'stock' => 18, 'sizes' => ['S', 'M', 'L', 'XL'], 'colors' => ['Xám Bạc Acid', 'Đen Tuyển'], 'image' => 'https://images.unsplash.com/photo-1516826957135-700dedea698c?w=500&auto=format&fit=crop&q=80', 'description' => 'Nỉ bông 400GSM, xử lý wash hiệu ứng vintage.', 'sales_count' => 86, 'created_at' => '2026-09-20'],
    ['id' => 'PRD-004', 'name' => 'Quần Parachute Cargo Pants VAST', 'sku' => 'VAST-PNT-04', 'category' => 'Quần', 'collection' => 'streetwear-core', 'price' => 620000, 'original_price' => 690000, 'stock' => 32, 'sizes' => ['M', 'L', 'XL'], 'colors' => ['Đen Obsidian', 'Xanh Rêu Sage', 'Be Cát'], 'image' => 'https://images.unsplash.com/photo-1624378439575-d8705ad7ae80?w=500&auto=format&fit=crop&q=80', 'description' => 'Vải dù chống nước nhẹ, gấu quần có khóa rút đa năng.', 'sales_count' => 110, 'created_at' => '2026-09-22'],
    ['id' => 'PRD-005', 'name' => 'Quần Wide-Leg Raw Denim VAST', 'sku' => 'VAST-DNM-05', 'category' => 'Quần', 'collection' => 'streetwear-core', 'price' => 680000, 'original_price' => 750000, 'stock' => 12, 'sizes' => ['29', '30', '31', '32'], 'colors' => ['Xanh Indigo Chàm', 'Đen Tuyển'], 'image' => 'https://images.unsplash.com/photo-1542272604-787c3835535d?w=500&auto=format&fit=crop&q=80', 'description' => 'Denim dệt thoi 13oz, form ống suông rộng.', 'sales_count' => 64, 'created_at' => '2026-10-01'],
    ['id' => 'PRD-006', 'name' => 'Áo Khoác Varsity Bomber VAST', 'sku' => 'VAST-JKT-06', 'category' => 'Áo khoác', 'collection' => 'winter-outerwear', 'price' => 1250000, 'original_price' => 1450000, 'stock' => 6, 'sizes' => ['M', 'L'], 'colors' => ['Đen Phối Trắng', 'Xanh Lục Bảo'], 'image' => 'https://images.unsplash.com/photo-1551028719-00167b16eac5?w=500&auto=format&fit=crop&q=80', 'description' => 'Áo khoác varsity phối tay da PU, thêu logo VAST.', 'sales_count' => 39, 'created_at' => '2026-10-05'],
    ['id' => 'PRD-007', 'name' => 'Áo Len Dệt Kim Textured Knit Sweater', 'sku' => 'VAST-SWT-07', 'category' => 'Hoodie & Sweater', 'collection' => 'minimalist-essentials', 'price' => 580000, 'original_price' => 650000, 'stock' => 3, 'sizes' => ['M', 'L'], 'colors' => ['Kem Vanilla', 'Nâu Mocha'], 'image' => 'https://images.unsplash.com/photo-1525450824786-227cbef70703?w=500&auto=format&fit=crop&q=80', 'description' => 'Len dệt mềm, form oversize cho mùa lạnh.', 'sales_count' => 47, 'created_at' => '2026-10-12'],
    ['id' => 'PRD-008', 'name' => 'Mũ Beanie VAST Metal Badge', 'sku' => 'VAST-ACC-08', 'category' => 'Phụ kiện', 'collection' => 'minimalist-essentials', 'price' => 220000, 'original_price' => 280000, 'stock' => 55, 'sizes' => ['Freesize'], 'colors' => ['Đen', 'Xám Khói', 'Nâu'], 'image' => 'https://images.unsplash.com/photo-1576871337632-b9aef4c17ab9?w=500&auto=format&fit=crop&q=80', 'description' => 'Mũ len gân co giãn, đính tag kim loại VAST.', 'sales_count' => 168, 'created_at' => '2026-10-15'],
];

$customers = [
    ['id' => 'CUS-101', 'name' => 'Nguyễn Hoàng Minh', 'email' => 'hoangminh.design@gmail.com', 'phone' => '0912 345 678', 'address' => 'Tầng 8, Tòa Keangnam Landmark 72, Phạm Hùng, Hà Nội', 'tier' => 'VIP Black Card', 'total_spent' => 9450000, 'joined_at' => '2025-08-10'],
    ['id' => 'CUS-102', 'name' => 'Trần Thảo Ly', 'email' => 'thaoly.tran@outlook.com', 'phone' => '0978 998 877', 'address' => 'Chung cư Masteri Thảo Điền, TP.HCM', 'tier' => 'Gold Member', 'total_spent' => 4820000, 'joined_at' => '2025-10-21'],
    ['id' => 'CUS-103', 'name' => 'Lê Tuấn Hưng', 'email' => 'tuanhung.le@gmail.com', 'phone' => '0903 112 233', 'address' => 'Quận Hải Châu, Đà Nẵng', 'tier' => 'Silver Member', 'total_spent' => 2950000, 'joined_at' => '2026-01-03'],
    ['id' => 'CUS-104', 'name' => 'Phạm Đức Anh', 'email' => 'ducanh.arch@gmail.com', 'phone' => '0934 556 789', 'address' => 'Quận 1, TP.HCM', 'tier' => 'VIP Black Card', 'total_spent' => 16800000, 'joined_at' => '2025-05-14'],
    ['id' => 'CUS-105', 'name' => 'Đặng Ngọc Quỳnh', 'email' => 'quynh.dang@gmail.com', 'phone' => '0918 776 554', 'address' => 'Ninh Kiều, Cần Thơ', 'tier' => 'Standard', 'total_spent' => 1120000, 'joined_at' => '2026-03-19'],
];

$orders = [
    ['id' => 'VAST-9825', 'customer_id' => 'CUS-101', 'customer_name' => 'Nguyễn Hoàng Minh', 'customer_phone' => '0912 345 678', 'customer_email' => 'hoangminh.design@gmail.com', 'shipping_address' => 'Tầng 8, Tòa Keangnam Landmark 72, Phạm Hùng, Q. Nam Từ Liêm, Hà Nội', 'subtotal' => 1380000, 'shipping_fee' => 0, 'discount' => 100000, 'coupon_code' => 'VASTWELCOME', 'total' => 1280000, 'payment_method' => 'Chuyển khoản QR (Vietcombank)', 'payment_status' => 'paid', 'order_status' => 'pending', 'note' => 'Giao giờ hành chính, gọi trước 15 phút giúp mình nhé.', 'created_at' => '2026-11-14 14:20:00', 'items' => [['product_id' => 'PRD-001', 'name' => 'Áo Thun VAST Heavyweight Boxy Tee', 'size' => 'L', 'color' => 'Đen Washed', 'price' => 380000, 'quantity' => 2], ['product_id' => 'PRD-004', 'name' => 'Quần Parachute Cargo Pants VAST', 'size' => 'L', 'color' => 'Đen Obsidian', 'price' => 620000, 'quantity' => 1]]],
    ['id' => 'VAST-9824', 'customer_id' => 'CUS-102', 'customer_name' => 'Trần Thảo Ly', 'customer_phone' => '0978 998 877', 'customer_email' => 'thaoly.tran@outlook.com', 'shipping_address' => 'Căn hộ 12B, Chung cư Masteri Thảo Điền, TP. Thủ Đức, TP.HCM', 'subtotal' => 740000, 'shipping_fee' => 0, 'discount' => 0, 'coupon_code' => null, 'total' => 740000, 'payment_method' => 'Ví điện tử MoMo', 'payment_status' => 'paid', 'order_status' => 'processing', 'note' => 'Đóng gói hộp quà VAST giúp mình nhé.', 'created_at' => '2026-11-14 11:45:00', 'items' => [['product_id' => 'PRD-002', 'name' => 'Áo Sơ Mi VAST Oversized Oxford', 'size' => 'M', 'color' => 'Trắng Sữa', 'price' => 520000, 'quantity' => 1], ['product_id' => 'PRD-008', 'name' => 'Mũ Beanie VAST Metal Badge', 'size' => 'Freesize', 'color' => 'Đen', 'price' => 220000, 'quantity' => 1]]],
    ['id' => 'VAST-9823', 'customer_id' => 'CUS-103', 'customer_name' => 'Lê Tuấn Hưng', 'customer_phone' => '0903 112 233', 'customer_email' => 'tuanhung.le@gmail.com', 'shipping_address' => '45/12 Nguyễn Văn Linh, Q. Hải Châu, Đà Nẵng', 'subtotal' => 1250000, 'shipping_fee' => 0, 'discount' => 187500, 'coupon_code' => 'VIPMEMBER', 'total' => 1062500, 'payment_method' => 'COD (Thanh toán khi nhận hàng)', 'payment_status' => 'pending', 'order_status' => 'shipping', 'note' => '', 'created_at' => '2026-11-13 18:10:00', 'items' => [['product_id' => 'PRD-006', 'name' => 'Áo Khoác Varsity Bomber VAST', 'size' => 'L', 'color' => 'Đen Phối Trắng', 'price' => 1250000, 'quantity' => 1]]],
    ['id' => 'VAST-9822', 'customer_id' => 'CUS-104', 'customer_name' => 'Phạm Đức Anh', 'customer_phone' => '0934 556 789', 'customer_email' => 'ducanh.arch@gmail.com', 'shipping_address' => '15 Lê Duẩn, Quận 1, TP.HCM', 'subtotal' => 1370000, 'shipping_fee' => 0, 'discount' => 50000, 'coupon_code' => 'SUMMER50K', 'total' => 1320000, 'payment_method' => 'Chuyển khoản QR (Vietcombank)', 'payment_status' => 'paid', 'order_status' => 'completed', 'note' => 'Đã nhận đủ hàng.', 'created_at' => '2026-11-12 09:30:00', 'items' => [['product_id' => 'PRD-003', 'name' => 'Hoodie VAST Acid Wash Vintage Drop', 'size' => 'XL', 'color' => 'Xám Bạc Acid', 'price' => 690000, 'quantity' => 1], ['product_id' => 'PRD-005', 'name' => 'Quần Wide-Leg Raw Denim VAST', 'size' => '31', 'color' => 'Xanh Indigo Chàm', 'price' => 680000, 'quantity' => 1]]],
    ['id' => 'VAST-9821', 'customer_id' => 'CUS-105', 'customer_name' => 'Hoàng Kim Chi', 'customer_phone' => '0981 223 344', 'customer_email' => 'kimchi.fashion@gmail.com', 'shipping_address' => 'Số 88 Hàng Bài, Q. Hoàn Kiếm, Hà Nội', 'subtotal' => 580000, 'shipping_fee' => 30000, 'discount' => 0, 'coupon_code' => null, 'total' => 610000, 'payment_method' => 'COD (Thanh toán khi nhận hàng)', 'payment_status' => 'refunded', 'order_status' => 'cancelled', 'note' => 'Khách đổi ý, chờ đợt restock màu Nâu Mocha.', 'created_at' => '2026-11-11 16:15:00', 'items' => [['product_id' => 'PRD-007', 'name' => 'Áo Len Dệt Kim Textured Knit Sweater', 'size' => 'M', 'color' => 'Kem Vanilla', 'price' => 580000, 'quantity' => 1]]],
];

$coupons = [
    ['id' => 'CPN-01', 'code' => 'VASTWELCOME', 'description' => 'Giảm 100.000₫ cho đơn từ 1.000.000₫', 'type' => 'fixed', 'value' => 100000, 'min_order' => 1000000, 'usage_limit' => 500, 'used_count' => 284, 'expiry_date' => '2026-12-31'],
    ['id' => 'CPN-02', 'code' => 'FREESHIP', 'description' => 'Hỗ trợ phí vận chuyển cho đơn từ 300.000₫', 'type' => 'fixed', 'value' => 30000, 'min_order' => 300000, 'usage_limit' => 1000, 'used_count' => 890, 'expiry_date' => '2026-12-31'],
    ['id' => 'CPN-03', 'code' => 'SUMMER50K', 'description' => 'Giảm 50.000₫ cho đơn hàng mùa hè', 'type' => 'fixed', 'value' => 50000, 'min_order' => 500000, 'usage_limit' => 300, 'used_count' => 195, 'expiry_date' => '2026-11-30'],
    ['id' => 'CPN-04', 'code' => 'VIPMEMBER', 'description' => 'Thành viên VIP giảm 15% tổng hóa đơn', 'type' => 'percent', 'value' => 15, 'min_order' => 800000, 'usage_limit' => 200, 'used_count' => 112, 'expiry_date' => '2026-12-31'],
];

$settings = [
    'storeName' => 'VAST STUDIO',
    'tagline' => 'Modern Streetwear & Minimalist Aesthetics',
    'hotline' => '0988.123.456',
    'email' => 'contact@vaststudio.vn',
    'website' => 'https://vaststudio.vn',
    'addressHanoi' => '148 Phố Huế, Q. Hai Bà Trưng, Hà Nội (Flagship Store)',
    'addressHcm' => '72 Lê Thánh Tôn, P. Bến Nghé, Quận 1, TP.HCM',
    'freeShippingThreshold' => 500000,
    'defaultShippingFee' => 30000,
    'bankName' => 'Vietcombank - Chi nhánh Thăng Long, Hà Nội',
    'bankAccount' => '9988 7766 5500',
    'bankOwner' => 'CONG TY TNHH VAST STUDIO VIETNAM',
];

$db->beginTransaction();
$collectionInsert = $db->prepare(
    'INSERT IGNORE INTO collections (name, slug, description, status)
     VALUES (:name, :slug, :description, :status)'
);
$collectionFind = $db->prepare('SELECT id FROM collections WHERE slug = :slug');
$collectionIds = [];
foreach ($collections as $collection) {
    $collectionInsert->execute($collection + ['status' => 'active']);
    $collectionFind->execute(['slug' => $collection['slug']]);
    $collectionIds[$collection['slug']] = (int)$collectionFind->fetchColumn();
}

$customerInsert = $db->prepare(
    'INSERT IGNORE INTO customers (id, name, email, phone, address, tier, total_spent, joined_at)
     VALUES (:id, :name, :email, :phone, :address, :tier, :total_spent, :joined_at)'
);
foreach ($customers as $customer) {
    $customerInsert->execute($customer);
}

$productInsert = $db->prepare(
    'INSERT IGNORE INTO products
     (id, name, sku, category, collection_id, price, original_price, stock, sizes, colors,
      image, description, status, sales_count, created_at)
     VALUES
     (:id, :name, :sku, :category, :collection_id, :price, :original_price, :stock, :sizes, :colors,
      :image, :description, :status, :sales_count, :created_at)'
);
foreach ($products as $product) {
    $productInsert->execute([
        'id' => $product['id'],
        'name' => $product['name'],
        'sku' => $product['sku'],
        'category' => $product['category'],
        'collection_id' => $collectionIds[$product['collection']],
        'price' => $product['price'],
        'original_price' => $product['original_price'],
        'stock' => $product['stock'],
        'sizes' => json_encode($product['sizes'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
        'colors' => json_encode($product['colors'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
        'image' => $product['image'],
        'description' => $product['description'],
        'status' => 'active',
        'sales_count' => $product['sales_count'],
        'created_at' => $product['created_at'],
    ]);
}

$orderInsert = $db->prepare(
    'INSERT IGNORE INTO orders
     (id, customer_id, customer_name, customer_phone, customer_email, shipping_address, subtotal,
      shipping_fee, discount, coupon_code, total, payment_method, payment_status, order_status, note, created_at)
     VALUES
     (:id, :customer_id, :customer_name, :customer_phone, :customer_email, :shipping_address, :subtotal,
      :shipping_fee, :discount, :coupon_code, :total, :payment_method, :payment_status, :order_status, :note, :created_at)'
);
$itemExists = $db->prepare(
    'SELECT 1 FROM order_items
     WHERE order_id = :order_id AND product_id = :product_id AND size = :size AND color = :color'
);
$itemInsert = $db->prepare(
    'INSERT INTO order_items (order_id, product_id, product_name, size, color, price, quantity, image)
     VALUES (:order_id, :product_id, :product_name, :size, :color, :price, :quantity, :image)'
);
$productImage = $db->prepare('SELECT image FROM products WHERE id = :id');
foreach ($orders as $order) {
    $orderInsert->execute([
        'id' => $order['id'],
        'customer_id' => $order['customer_id'],
        'customer_name' => $order['customer_name'],
        'customer_phone' => $order['customer_phone'],
        'customer_email' => $order['customer_email'],
        'shipping_address' => $order['shipping_address'],
        'subtotal' => $order['subtotal'],
        'shipping_fee' => $order['shipping_fee'],
        'discount' => $order['discount'],
        'coupon_code' => $order['coupon_code'],
        'total' => $order['total'],
        'payment_method' => $order['payment_method'],
        'payment_status' => $order['payment_status'],
        'order_status' => $order['order_status'],
        'note' => $order['note'],
        'created_at' => $order['created_at'],
    ]);
    foreach ($order['items'] as $item) {
        $itemKey = [
            'order_id' => $order['id'],
            'product_id' => $item['product_id'],
            'size' => $item['size'],
            'color' => $item['color'],
        ];
        $itemExists->execute($itemKey);
        if ($itemExists->fetchColumn()) {
            continue;
        }
        $productImage->execute(['id' => $item['product_id']]);
        $itemInsert->execute($itemKey + [
            'product_name' => $item['name'],
            'price' => $item['price'],
            'quantity' => $item['quantity'],
            'image' => $productImage->fetchColumn(),
        ]);
    }
}

$couponInsert = $db->prepare(
    'INSERT IGNORE INTO coupons
     (id, code, description, type, value, min_order, usage_limit, used_count, expiry_date, status)
     VALUES
     (:id, :code, :description, :type, :value, :min_order, :usage_limit, :used_count, :expiry_date, :status)'
);
foreach ($coupons as $coupon) {
    $couponInsert->execute($coupon + ['status' => 'active']);
}

$settingsInsert = $db->prepare(
    'INSERT IGNORE INTO settings (setting_key, setting_value) VALUES (:setting_key, :setting_value)'
);
$settingsInsert->execute([
    'setting_key' => 'storefront',
    'setting_value' => json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
]);

$db->commit();

$counts = [];
foreach (['collections', 'customers', 'products', 'orders', 'order_items', 'coupons', 'settings'] as $table) {
    $counts[$table] = (int)$db->query('SELECT COUNT(*) FROM ' . $table)->fetchColumn();
}
foreach ($counts as $table => $count) {
    fwrite(STDOUT, $table . ': ' . $count . PHP_EOL);
}
