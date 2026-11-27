<?php
$e = static function ($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};
$money = static function ($value): string {
    return number_format((float)$value, 0, ',', '.') . ' ₫';
};
$maxRevenue = max(array_column($revenueHistory, 'value') ?: [0]);
$statusLabels = [
    'pending' => 'Chờ xác nhận',
    'processing' => 'Đang đóng gói',
    'shipping' => 'Đang giao',
    'completed' => 'Hoàn thành',
    'cancelled' => 'Đã hủy',
];
$paymentLabels = [
    'pending' => 'Chưa thanh toán',
    'paid' => 'Đã thanh toán',
    'refunded' => 'Đã hoàn tiền',
    'Đã thanh toán' => 'Đã thanh toán',
    'Chưa thanh toán' => 'Chưa thanh toán',
    'Đã hoàn tiền' => 'Đã hoàn tiền',
];
$navItems = [
    'dashboard' => 'Tổng quan',
    'products' => 'Sản phẩm',
    'orders' => 'Đơn hàng',
    'collections' => 'Bộ sưu tập',
    'customers' => 'Khách hàng',
    'coupons' => 'Khuyến mãi',
    'settings' => 'Cài đặt Shop',
];
$formAction = static function (string $action) use ($csrf): string {
    return '<input type="hidden" name="csrf" value="' . htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') . '">' .
        '<input type="hidden" name="action" value="' . htmlspecialchars($action, ENT_QUOTES, 'UTF-8') . '">';
};
?>
<!DOCTYPE html>
<html lang="vi" data-theme="<?= $e($theme) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $e($pageTitle) ?> — VAST STUDIO</title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        body{background:var(--bg-app);color:var(--text-primary)}
        .admin-layout{display:grid;grid-template-columns:250px minmax(0,1fr);min-height:100vh}
        .admin-sidebar{padding:24px 16px;background:var(--bg-sidebar);border-right:1px solid var(--border-color);color:var(--sidebar-text)}
        .admin-brand{display:block;color:var(--text-primary);font-weight:800;font-size:1.15rem;margin:0 10px 24px;text-decoration:none}
        .admin-nav{display:grid;gap:6px}
        .admin-nav a{padding:11px 12px;border-radius:8px;color:var(--sidebar-text);text-decoration:none}
        .admin-nav a.active,.admin-nav a:hover{background:var(--sidebar-active-bg);color:var(--sidebar-text)}
        .admin-main{padding:28px;min-width:0}
        .admin-topbar{display:flex;justify-content:space-between;align-items:center;gap:16px;margin-bottom:26px}
        .admin-topbar h1{font-size:1.5rem;margin:0}
        .admin-content{display:grid;gap:20px}
        .admin-card{background:var(--bg-surface);border:1px solid var(--border-color);border-radius:12px;padding:20px;min-width:0}
        .admin-card h2,.admin-card h3{margin:0 0 16px}
        .admin-stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px}
        .admin-stat{background:var(--bg-surface);border:1px solid var(--border-color);border-radius:12px;padding:18px}
        .admin-stat span{display:block;color:var(--text-muted);font-size:.82rem}
        .admin-stat strong{display:block;margin-top:8px;font-size:1.35rem}
        .admin-toolbar{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px}
        .admin-filters,.admin-form-grid{display:flex;align-items:end;flex-wrap:wrap;gap:12px}
        .admin-form-grid>*{flex:1 1 200px}
        .admin-field{display:grid;gap:6px}
        .admin-field label{font-size:.82rem;color:var(--text-secondary)}
        .admin-field input,.admin-field select,.admin-field textarea{width:100%;min-height:40px;padding:8px 10px}
        .admin-field textarea{min-height:90px}
        .admin-filters .admin-field input,.admin-filters .admin-field select{border:1px solid var(--border-color);border-radius:8px;background:var(--bg-subtle);color:var(--text-primary)}
        .admin-filters .admin-field input::placeholder{color:var(--text-secondary)}
        .admin-filters .admin-field select option{background:var(--bg-surface);color:var(--text-primary)}
        .admin-filters .admin-field input:focus,.admin-filters .admin-field select:focus{outline:none;border-color:var(--border-focus);box-shadow:0 0 0 3px color-mix(in srgb,var(--border-focus) 14%,transparent)}
        .admin-table-wrap{overflow:auto}
        .admin-table{width:100%;border-collapse:collapse;min-width:760px}
        .admin-table th,.admin-table td{padding:11px 9px;border-bottom:1px solid var(--border-color);text-align:left;vertical-align:top}
        .admin-table th{color:var(--text-muted);font-size:.75rem}
        .admin-table td{font-size:.82rem}
        .admin-inline{display:inline-flex;align-items:center;gap:6px}
        .admin-details{margin-top:16px}
        .admin-details>summary{cursor:pointer;color:var(--text-primary);font-weight:700;margin-bottom:14px}
        .product-editor{overflow:hidden;border:1px solid var(--border-color);border-radius:14px;background:var(--bg-surface);box-shadow:var(--shadow-sm)}
        .product-editor>summary{display:flex;align-items:center;justify-content:space-between;gap:16px;margin:0;padding:16px 20px;list-style:none;transition:background .2s ease}
        .product-editor>summary::-webkit-details-marker{display:none}
        .product-editor>summary:hover{background:var(--bg-surface-hover)}
        .product-editor[open]>summary{border-bottom:1px solid var(--border-color)}
        .product-editor-toggle-copy{display:grid;gap:3px}
        .product-editor-toggle-copy strong{font-size:.92rem}
        .product-editor-toggle-copy small,.product-editor-hint{color:var(--text-secondary);font-size:.76rem;font-weight:500}
        .product-editor-mark{display:grid;place-items:center;width:40px;height:40px;border:1px solid var(--border-color);border-radius:11px;background:var(--bg-subtle);font-size:1.15rem;font-weight:600}
        .product-editor-toggle:after{content:"+";display:grid;place-items:center;width:30px;height:30px;border:1px solid var(--border-color);border-radius:50%;color:var(--text-secondary);font-size:1.1rem}
        .product-editor[open] .product-editor-toggle:after{content:"−"}
        .product-editor-badge{padding:5px 9px;border-radius:999px;background:var(--status-info-bg);color:var(--status-info-text);font-size:.72rem;font-weight:700}
        .product-editor-body{padding:22px}
        .product-editor-heading{display:flex;align-items:flex-start;justify-content:space-between;gap:20px;margin-bottom:8px}
        .product-editor-eyebrow{margin-bottom:5px;color:var(--text-secondary);font-size:.72rem;font-weight:700;letter-spacing:.12em;text-transform:uppercase}
        .product-editor-heading h2{margin:0;color:var(--text-primary);font-size:1.25rem}
        .product-editor-heading p:last-child{margin-top:5px;color:var(--text-secondary);font-size:.82rem}
        .product-editor-sku{flex:none;padding:7px 10px;border:1px solid var(--border-color);border-radius:8px;background:var(--bg-subtle);color:var(--text-secondary);font-size:.76rem;font-weight:700}
        .product-editor-form{display:grid;gap:0}
        .product-editor-section{display:grid;grid-template-columns:minmax(160px,.7fr) minmax(0,2fr);gap:22px;padding:22px 0;border-top:1px solid var(--border-color)}
        .product-section-intro{display:flex;align-items:flex-start;gap:11px}
        .product-section-number{display:grid;place-items:center;flex:none;width:30px;height:30px;border-radius:9px;background:var(--bg-subtle);color:var(--text-secondary);font-size:.72rem;font-weight:700}
        .product-section-intro h3{margin:2px 0 4px;font-size:.88rem}
        .product-section-intro p{color:var(--text-secondary);font-size:.76rem;line-height:1.5}
        .product-fields{display:grid;gap:14px}
        .product-fields--two{grid-template-columns:repeat(2,minmax(0,1fr))}
        .product-fields--three{grid-template-columns:repeat(3,minmax(0,1fr))}
        .product-field{display:grid;align-content:start;gap:6px;min-width:0;color:var(--text-secondary);font-size:.76rem;font-weight:600}
        .product-field input,.product-field select,.product-field textarea{width:100%;min-height:42px;padding:10px 11px;border:1px solid var(--border-color);border-radius:9px;background:var(--bg-subtle);color:var(--text-primary);font-size:.84rem;font-weight:400;transition:border-color .18s ease,box-shadow .18s ease}
        .product-field textarea{min-height:104px;resize:vertical}
        .product-field input:focus,.product-field select:focus,.product-field textarea:focus{outline:none;border-color:var(--border-focus);box-shadow:0 0 0 3px color-mix(in srgb,var(--border-focus) 14%,transparent)}
        .product-field-hint{color:var(--text-secondary);font-size:.74rem;font-weight:500}
        .product-image-preview{width:68px;height:76px;object-fit:cover;border:1px solid var(--border-color);border-radius:9px;background:var(--bg-subtle)}
        .product-editor-footer{display:flex;align-items:center;justify-content:space-between;gap:16px;padding-top:18px;border-top:1px solid var(--border-color)}
        .product-editor-footer-actions{display:flex;align-items:center;gap:10px}
        .product-cancel-link{padding:9px 13px;border:1px solid var(--border-color);border-radius:9px;color:var(--text-secondary);font-size:.82rem;font-weight:600;text-decoration:none}
        .product-cancel-link:hover{background:var(--bg-surface-hover);color:var(--text-primary)}
        .product-editor-footer .btn-primary{min-height:40px}
        .admin-alert{padding:12px 14px;border:1px solid var(--border-color);border-radius:8px}
        .admin-error{color:#ef4444}.admin-success{color:#10b981}
        .admin-muted{color:var(--text-muted)}
        .admin-image{width:48px;height:56px;object-fit:cover;border-radius:6px}
        .admin-order-items{margin:8px 0;padding-left:18px}
        .admin-actions{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
        .admin-chart-wrap{overflow-x:auto}
        .admin-chart{display:grid;grid-auto-columns:minmax(34px,1fr);grid-auto-flow:column;align-items:end;gap:8px;min-width:500px;padding-top:20px}
        .admin-chart-column{text-align:center;font-size:.68rem}
        .admin-chart-track{height:130px;display:flex;align-items:end;border-bottom:1px solid var(--border-color)}
        .admin-chart-bar{display:block;width:100%;min-height:2px;background:#3b82f6;border-radius:4px 4px 0 0}
        .admin-chart-column small{display:block;margin-top:6px;color:var(--text-muted)}
        @media(max-width:850px){.admin-layout{grid-template-columns:1fr}.admin-sidebar{border-right:0;border-bottom:1px solid var(--border-color)}.admin-nav{grid-template-columns:repeat(2,minmax(0,1fr))}.admin-main{padding:18px}.admin-stats{grid-template-columns:repeat(2,minmax(0,1fr))}}
        @media(max-width:720px){.product-editor-section{grid-template-columns:1fr;gap:14px}.product-fields--three{grid-template-columns:repeat(2,minmax(0,1fr))}}
        @media(max-width:520px){.admin-topbar{align-items:flex-start;flex-direction:column}.admin-stats{grid-template-columns:1fr}.product-editor-body{padding:16px}.product-editor-heading{flex-direction:column}.product-fields--two,.product-fields--three{grid-template-columns:1fr}.product-editor-footer{align-items:stretch;flex-direction:column}.product-editor-footer-actions{justify-content:flex-end}}
        @media print{.admin-sidebar,.admin-topbar,.admin-toolbar,.admin-actions,form,button{display:none!important}.admin-main{padding:0}.admin-card{border:0}}
    </style>
</head>
<body>
<div class="admin-layout">
    <aside class="admin-sidebar">
        <a class="admin-brand" href="admin.php">VAST STUDIO<br><small>Admin Portal</small></a>
        <nav class="admin-nav" aria-label="Điều hướng quản trị">
            <?php foreach ($navItems as $key => $label): ?>
                <a class="<?= $tab === $key ? 'active' : '' ?>" href="admin.php?tab=<?= $e($key) ?>"><?= $e($label) ?></a>
            <?php endforeach; ?>
            <a href="index.php">← Xem cửa hàng</a>
        </nav>
    </aside>

    <main class="admin-main">
        <header class="admin-topbar">
            <div>
                <h1><?= $e($pageTitle) ?></h1>
                <p class="admin-muted">Hôm nay: <?= $e(date('d/m/Y')) ?></p>
            </div>
            <div class="admin-actions">
                <span><?= $e($adminName) ?></span>
                <form method="post" class="admin-inline">
                    <?= $formAction('theme') ?>
                    <input type="hidden" name="tab" value="<?= $e($tab) ?>">
                    <label for="admin-theme">Giao diện</label>
                    <select id="admin-theme" name="theme">
                        <option value="dark" <?= $theme === 'dark' ? 'selected' : '' ?>>Tối</option>
                        <option value="light" <?= $theme === 'light' ? 'selected' : '' ?>>Sáng</option>
                    </select>
                    <button class="btn-secondary" type="submit">Áp dụng</button>
                </form>
                <?php if (!$isLocalAccess): ?><form method="post"><?= $formAction('admin_logout') ?><button class="btn-secondary" type="submit">Đăng xuất</button></form><?php endif; ?>
            </div>
        </header>

        <div class="admin-content">
            <?php if ($error !== ''): ?>
                <div class="admin-alert admin-error" role="alert"><?= $e($error) ?></div>
            <?php endif; ?>
            <?php if ($message !== ''): ?>
                <div class="admin-alert admin-success" role="status"><?= $e($message) ?></div>
            <?php endif; ?>

            <?php if ($tab === 'dashboard'): ?>
                <section class="admin-stats">
                    <div class="admin-stat"><span>Doanh thu tháng này</span><strong><?= $money($stats['revenue']) ?></strong></div>
                    <div class="admin-stat"><span>Tổng đơn hàng</span><strong><?= $e($stats['orders']) ?></strong><small><?= $e($stats['pending']) ?> chờ xử lý</small></div>
                    <div class="admin-stat"><span>Sản phẩm trong kho</span><strong><?= $e($stats['inventory']) ?></strong><small><?= $e($stats['low_stock']) ?> mặt hàng sắp hết</small></div>
                    <div class="admin-stat"><span>Khách hàng</span><strong><?= $e($stats['customers']) ?></strong></div>
                </section>
                <section class="admin-card">
                    <div class="admin-toolbar"><div><h2>Doanh thu gần đây</h2><p class="admin-muted">Đơn hoàn thành và đang giao</p></div><div class="admin-actions"><a href="admin.php?tab=dashboard&amp;range=7" <?= $revenueRange === 7 ? 'aria-current="page"' : '' ?>>7 ngày</a><a href="admin.php?tab=dashboard&amp;range=30" <?= $revenueRange === 30 ? 'aria-current="page"' : '' ?>>30 ngày</a></div></div>
                    <div class="admin-chart-wrap" role="img" aria-label="Biểu đồ doanh thu <?= $e($revenueRange) ?> ngày gần nhất">
                        <div class="admin-chart">
                            <?php foreach ($revenueHistory as $point): $barHeight = $maxRevenue > 0 ? max(2, (int)round($point['value'] / $maxRevenue * 100)) : 0; ?>
                                <div class="admin-chart-column"><span><?= $point['value'] > 0 ? $money($point['value']) : '—' ?></span><div class="admin-chart-track"><i class="admin-chart-bar" style="height:<?= $barHeight ?>%"></i></div><small><?= $e($point['label']) ?></small></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>
                <section class="admin-card">
                    <div class="admin-toolbar"><h2>Sản phẩm bán chạy</h2><a href="admin.php?tab=products">Quản lý sản phẩm</a></div>
                    <?php if (!$topProducts): ?><p class="admin-muted">Chưa có dữ liệu sản phẩm.</p><?php else: ?>
                        <div class="admin-table-wrap"><table class="admin-table">
                            <thead><tr><th>Sản phẩm</th><th>Bộ sưu tập</th><th>Đã bán</th><th>Giá</th></tr></thead>
                            <tbody><?php foreach ($topProducts as $product): ?>
                                <tr><td><?= $e($product['name']) ?></td><td><?= $e($product['collection'] ?: '—') ?></td><td><?= $e($product['sales_count']) ?></td><td><?= $money($product['price']) ?></td></tr>
                            <?php endforeach; ?></tbody>
                        </table></div>
                    <?php endif; ?>
                </section>
                <section class="admin-card">
                    <div class="admin-toolbar"><h2>Đơn hàng gần đây</h2><a href="admin.php?tab=orders">Quản lý đơn hàng</a></div>
                    <?php if (!$recentOrders): ?><p class="admin-muted">Chưa có đơn hàng.</p><?php else: ?>
                        <div class="admin-table-wrap"><table class="admin-table">
                            <thead><tr><th>Mã đơn</th><th>Khách hàng</th><th>Tổng tiền</th><th>Trạng thái</th><th></th></tr></thead>
                            <tbody><?php foreach ($recentOrders as $order): ?>
                                <tr><td><?= $e($order['id']) ?></td><td><?= $e($order['customer_name']) ?><br><?= $e($order['customer_phone']) ?></td><td><?= $money($order['total']) ?></td><td><?= $e($statusLabels[$order['order_status']] ?? $order['order_status']) ?></td><td><a href="admin.php?tab=orders&amp;order=<?= rawurlencode($order['id']) ?>">Chi tiết</a></td></tr>
                            <?php endforeach; ?></tbody>
                        </table></div>
                    <?php endif; ?>
                </section>

            <?php elseif ($tab === 'products'): ?>
                <section class="admin-card">
                    <form method="get" class="admin-filters">
                        <input type="hidden" name="tab" value="products">
                        <label class="admin-field">Tìm kiếm<input name="product_search" value="<?= $e($productFilters['search']) ?>" placeholder="Tên hoặc SKU"></label>
                        <label class="admin-field">Danh mục<select name="category">
                            <?php foreach (['all' => 'Tất cả danh mục', 'Áo thun' => 'Áo thun', 'Áo sơ mi' => 'Áo sơ mi', 'Hoodie & Sweater' => 'Hoodie & Sweater', 'Quần' => 'Quần', 'Áo khoác' => 'Áo khoác', 'Phụ kiện' => 'Phụ kiện'] as $value => $label): ?>
                                <option value="<?= $e($value) ?>" <?= $productFilters['category'] === $value ? 'selected' : '' ?>><?= $e($label) ?></option>
                            <?php endforeach; ?>
                        </select></label>
                        <label class="admin-field">Tồn kho<select name="stock">
                            <?php foreach (['all' => 'Tất cả', 'in_stock' => 'Còn hàng (> 10)', 'low_stock' => 'Sắp hết (1–10)', 'out_of_stock' => 'Hết hàng'] as $value => $label): ?>
                                <option value="<?= $e($value) ?>" <?= $productFilters['stock'] === $value ? 'selected' : '' ?>><?= $e($label) ?></option>
                            <?php endforeach; ?>
                        </select></label>
                        <label class="admin-field">Sắp xếp<select name="sort">
                            <?php foreach (['newest' => 'Mới nhất', 'price_asc' => 'Giá tăng dần', 'price_desc' => 'Giá giảm dần', 'stock_desc' => 'Tồn kho nhiều nhất', 'sales_desc' => 'Bán chạy nhất'] as $value => $label): ?>
                                <option value="<?= $e($value) ?>" <?= $productFilters['sort'] === $value ? 'selected' : '' ?>><?= $e($label) ?></option>
                            <?php endforeach; ?>
                        </select></label>
                        <button class="btn-primary" type="submit">Lọc</button>
                    </form>
                    <details class="admin-details product-editor" <?= $editProduct ? 'open' : '' ?>>
                        <summary class="product-editor-toggle">
                            <span class="admin-inline">
                                <span class="product-editor-mark" aria-hidden="true"><?= $editProduct ? '✎' : '+' ?></span>
                                <span class="product-editor-toggle-copy">
                                    <strong><?= $editProduct ? 'Chỉnh sửa sản phẩm' : 'Thêm sản phẩm mới' ?></strong>
                                    <small><?= $editProduct ? 'Cập nhật thông tin và lưu thay đổi' : 'Mở biểu mẫu để tạo sản phẩm mới' ?></small>
                                </span>
                            </span>
                            <?php if ($editProduct): ?><span class="product-editor-badge">Đang chỉnh sửa</span><?php endif; ?>
                        </summary>
                        <div class="product-editor-body">
                            <header class="product-editor-heading">
                                <div>
                                    <p class="product-editor-eyebrow"><?= $editProduct ? 'THÔNG TIN SẢN PHẨM' : 'DANH MỤC SẢN PHẨM' ?></p>
                                    <h2><?= $editProduct ? $e($editProduct['name']) : 'Tạo sản phẩm mới' ?></h2>
                                    <p><?= $editProduct ? 'Thay đổi các trường bên dưới rồi lưu để cập nhật sản phẩm.' : 'Điền thông tin sản phẩm để đăng lên cửa hàng.' ?></p>
                                </div>
                                <?php if ($editProduct): ?><span class="product-editor-sku">SKU: <?= $e($editProduct['sku']) ?></span><?php endif; ?>
                            </header>
                            <form method="post" class="product-editor-form">
                                <?= $formAction('product_save') ?>
                                <input type="hidden" name="id" value="<?= $e($editProduct['id'] ?? '') ?>">
                                <section class="product-editor-section">
                                    <div class="product-section-intro">
                                        <span class="product-section-number">01</span>
                                        <div><h3>Thông tin cơ bản</h3><p>Tên gọi, mã SKU và bộ sưu tập của sản phẩm.</p></div>
                                    </div>
                                    <div class="product-fields product-fields--two">
                                        <label class="product-field">Tên sản phẩm *<input name="name" required value="<?= $e($editProduct['name'] ?? '') ?>" placeholder="Ví dụ: Áo thun VAST Boxy Tee"></label>
                                        <label class="product-field">Mã SKU *<input name="sku" required value="<?= $e($editProduct['sku'] ?? '') ?>" placeholder="VAST-TSH-01"></label>
                                        <label class="product-field">Danh mục *<input name="category" required value="<?= $e($editProduct['category'] ?? '') ?>" placeholder="Áo thun"></label>
                                        <label class="product-field">Bộ sưu tập
                                            <select name="collection">
                                                <option value="">Không thuộc bộ sưu tập</option>
                                                <?php foreach ($collections as $collection): ?>
                                                    <option value="<?= $e($collection['name']) ?>" <?= ($editProduct['collection'] ?? '') === $collection['name'] ? 'selected' : '' ?>><?= $e($collection['name']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </label>
                                    </div>
                                </section>
                                <section class="product-editor-section">
                                    <div class="product-section-intro">
                                        <span class="product-section-number">02</span>
                                        <div><h3>Giá &amp; kho hàng</h3><p>Thiết lập giá bán, tồn kho và trạng thái hiển thị.</p></div>
                                    </div>
                                    <div class="product-fields product-fields--three">
                                        <label class="product-field">Giá bán (₫) *<input type="number" min="0" step="1" name="price" required value="<?= $e($editProduct['price'] ?? '') ?>" placeholder="380000"></label>
                                        <label class="product-field">Giá gốc (₫)<input type="number" min="0" step="1" name="original_price" value="<?= $e($editProduct['original_price'] ?? '') ?>" placeholder="450000"></label>
                                        <label class="product-field">Số lượng tồn kho *<input type="number" min="0" name="stock" required value="<?= $e($editProduct['stock'] ?? '0') ?>" placeholder="0"></label>
                                        <label class="product-field">Trạng thái
                                            <select name="status">
                                                <option value="active" <?= ($editProduct['status'] ?? '') !== 'draft' ? 'selected' : '' ?>>Đang bán</option>
                                                <option value="draft" <?= ($editProduct['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Bản nháp</option>
                                            </select>
                                        </label>
                                    </div>
                                </section>
                                <section class="product-editor-section">
                                    <div class="product-section-intro">
                                        <span class="product-section-number">03</span>
                                        <div><h3>Biến thể &amp; hình ảnh</h3><p>Nhập kích cỡ, màu sắc và đường dẫn ảnh sản phẩm.</p></div>
                                    </div>
                                    <div class="product-fields product-fields--two">
                                        <label class="product-field">Kích cỡ *<input name="sizes" required value="<?= $e(implode(', ', $editProduct['sizes'] ?? [])) ?>" placeholder="S, M, L, XL"><small class="product-field-hint">Phân tách từng kích cỡ bằng dấu phẩy.</small></label>
                                        <label class="product-field">Màu sắc *<input name="colors" required value="<?= $e(implode(', ', $editProduct['colors'] ?? [])) ?>" placeholder="Đen, Trắng, Xám"><small class="product-field-hint">Phân tách từng màu sắc bằng dấu phẩy.</small></label>
                                        <label class="product-field">URL hình ảnh
                                            <input type="url" name="image" value="<?= $e($editProduct['image'] ?? '') ?>" placeholder="https://example.com/image.jpg">
                                            <small class="product-field-hint">Dùng đường dẫn ảnh trực tiếp.</small>
                                        </label>
                                        <?php if (!empty($editProduct['image'])): ?><img class="product-image-preview" src="<?= $e($editProduct['image']) ?>" alt="Ảnh hiện tại của <?= $e($editProduct['name']) ?>"><?php endif; ?>
                                    </div>
                                </section>
                                <section class="product-editor-section">
                                    <div class="product-section-intro">
                                        <span class="product-section-number">04</span>
                                        <div><h3>Mô tả</h3><p>Thông tin giúp khách hàng hiểu rõ hơn về sản phẩm.</p></div>
                                    </div>
                                    <div class="product-fields">
                                        <label class="product-field">Mô tả sản phẩm<textarea name="description" rows="4" placeholder="Mô tả chất liệu, kiểu dáng và đặc điểm nổi bật..."><?= $e($editProduct['description'] ?? '') ?></textarea></label>
                                    </div>
                                </section>
                                <footer class="product-editor-footer">
                                    <span class="product-editor-hint">Các trường có dấu * là bắt buộc.</span>
                                    <div class="product-editor-footer-actions">
                                        <?php if ($editProduct): ?><a class="product-cancel-link" href="admin.php?tab=products">Hủy sửa</a><?php endif; ?>
                                        <button class="btn-primary" type="submit"><?= $editProduct ? 'Lưu thay đổi' : 'Tạo sản phẩm' ?></button>
                                    </div>
                                </footer>
                            </form>
                        </div>
                    </details>
                </section>
                <section class="admin-card">
                    <?php if (!$products): ?><p class="admin-muted">Không tìm thấy sản phẩm. Nếu cơ sở dữ liệu mới, hãy nhập dữ liệu sản phẩm trước.</p><?php else: ?>
                        <div class="admin-table-wrap"><table class="admin-table">
                            <thead><tr><th>Sản phẩm</th><th>Danh mục</th><th>Bộ sưu tập</th><th>Giá</th><th>Size / màu</th><th>Tồn kho</th><th>Đã bán</th><th>Trạng thái</th><th>Thao tác</th></tr></thead>
                            <tbody><?php foreach ($products as $product): ?>
                                <tr>
                                    <td><div class="admin-inline"><?php if ($product['image']): ?><img class="admin-image" src="<?= $e($product['image']) ?>" alt=""><?php endif; ?><span><?= $e($product['name']) ?><br><small><?= $e($product['sku']) ?></small></span></div></td>
                                    <td><?= $e($product['category']) ?></td><td><?= $e($product['collection'] ?: '—') ?></td>
                                    <td><?= $money($product['price']) ?><?php if ($product['original_price']): ?><br><small><?= $money($product['original_price']) ?></small><?php endif; ?></td>
                                    <td><?= $e(implode(', ', $product['sizes'])) ?><br><small><?= $e(implode(', ', $product['colors'])) ?></small></td>
                                    <td><?= $e($product['stock']) ?></td><td><?= $e($product['sales_count']) ?></td><td><?= $product['status'] === 'active' ? 'Đang bán' : 'Bản nháp' ?></td>
                                    <td><div class="admin-actions"><a href="admin.php?tab=products&amp;edit_product=<?= rawurlencode($product['id']) ?>">Sửa</a><details><summary>Xóa</summary><form method="post"><?= $formAction('product_delete') ?><input type="hidden" name="id" value="<?= $e($product['id']) ?>"><button class="btn-danger" type="submit">Xác nhận xóa</button></form></details></div></td>
                                </tr>
                            <?php endforeach; ?></tbody>
                        </table></div>
                    <?php endif; ?>
                </section>

            <?php elseif ($tab === 'orders'): ?>
                <section class="admin-card">
                    <nav class="admin-actions" aria-label="Lọc trạng thái đơn hàng">
                        <?php foreach (['all' => 'Tất cả'] + $statusLabels as $key => $label): ?>
                            <a class="<?= $orderFilters['status'] === $key ? 'active' : '' ?>" href="admin.php?tab=orders&amp;status=<?= $e($key) ?>"><?= $e($label) ?> (<?= $e($orderCounts[$key] ?? 0) ?>)</a>
                        <?php endforeach; ?>
                    </nav>
                    <form method="get" class="admin-filters">
                        <input type="hidden" name="tab" value="orders"><input type="hidden" name="status" value="<?= $e($orderFilters['status']) ?>">
                        <label class="admin-field">Tìm mã đơn, khách hàng, số điện thoại<input name="order_search" value="<?= $e($orderFilters['search']) ?>"></label>
                        <button class="btn-primary" type="submit">Tìm kiếm</button>
                    </form>
                </section>
                <section class="admin-card">
                    <?php if (!$orders): ?><p class="admin-muted">Không có đơn hàng phù hợp.</p><?php else: ?>
                        <div class="admin-table-wrap"><table class="admin-table">
                            <thead><tr><th>Mã đơn / thời gian</th><th>Khách hàng</th><th>Tổng tiền</th><th>Thanh toán</th><th>Trạng thái</th><th>Chi tiết</th></tr></thead>
                            <tbody><?php foreach ($orders as $order): ?>
                                <tr>
                                    <td><?= $e($order['id']) ?><br><small><?= $e($order['created_at']) ?></small></td>
                                    <td><?= $e($order['customer_name']) ?><br><small><?= $e($order['customer_phone']) ?></small></td>
                                    <td><?= $money($order['total']) ?></td>
                                    <td><?= $e($order['payment_method'] ?? '—') ?><br><small><?= $e($paymentLabels[$order['payment_status']] ?? $order['payment_status']) ?></small></td>
                                    <td><form method="post" class="admin-inline"><?= $formAction('order_status') ?><input type="hidden" name="id" value="<?= $e($order['id']) ?>"><select name="status"><?php foreach ($statusLabels as $key => $label): ?><option value="<?= $e($key) ?>" <?= $order['order_status'] === $key ? 'selected' : '' ?>><?= $e($label) ?></option><?php endforeach; ?></select><button class="btn-secondary" type="submit">Lưu</button></form></td>
                                    <td><a href="admin.php?tab=orders&amp;order=<?= rawurlencode($order['id']) ?>">Xem</a></td>
                                </tr>
                            <?php endforeach; ?></tbody>
                        </table></div>
                    <?php endif; ?>
                </section>
                <?php if ($selectedOrder): ?>
                    <section class="admin-card">
                        <div class="admin-toolbar"><h2>Đơn hàng <?= $e($selectedOrder['id']) ?></h2><a href="admin.php?tab=orders">Đóng chi tiết</a></div>
                        <p><strong><?= $e($selectedOrder['customer_name']) ?></strong> · <?= $e($selectedOrder['customer_phone']) ?> · <?= $e($selectedOrder['customer_email']) ?></p>
                        <p>Địa chỉ: <?= $e($selectedOrder['shipping_address'] ?? '—') ?></p>
                        <?php if ($selectedOrder['note']): ?><p>Ghi chú: <?= $e($selectedOrder['note']) ?></p><?php endif; ?>
                        <ul class="admin-order-items"><?php foreach ($selectedOrder['items'] as $item): ?><li><?= $e($item['name']) ?> — <?= $e($item['size']) ?> / <?= $e($item['color']) ?> × <?= $e($item['quantity']) ?> — <?= $money($item['price'] * $item['quantity']) ?></li><?php endforeach; ?></ul>
                        <p>Tạm tính <?= $money($selectedOrder['subtotal']) ?> · Phí giao hàng <?= $money($selectedOrder['shipping_fee']) ?> · Giảm giá <?= $money($selectedOrder['discount']) ?> · <strong>Tổng <?= $money($selectedOrder['total']) ?></strong></p>
                    </section>
                <?php elseif (isset($_GET['order'])): ?>
                    <div class="admin-alert admin-error">Không tìm thấy đơn hàng này.</div>
                <?php endif; ?>

            <?php elseif ($tab === 'collections'): ?>
                <section class="admin-card">
                    <details>
                        <summary>Tạo bộ sưu tập mới</summary>
                        <form method="post" class="admin-form-grid" style="margin-top:16px">
                            <?= $formAction('collection_save') ?>
                            <label class="admin-field">Tên bộ sưu tập *<input name="name" required></label>
                            <label class="admin-field">URL ảnh banner<input type="url" name="banner"></label>
                            <label class="admin-field">Mô tả<textarea name="description"></textarea></label>
                            <button class="btn-primary" type="submit">Tạo bộ sưu tập</button>
                        </form>
                    </details>
                </section>
                <section class="admin-card">
                    <?php if (!$collections): ?><p class="admin-muted">Chưa có bộ sưu tập.</p><?php else: ?>
                        <div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Tên</th><th>Mô tả</th><th>Sản phẩm</th><th>Trạng thái</th></tr></thead><tbody>
                            <?php foreach ($collections as $collection): ?><tr><td><?= $e($collection['name']) ?></td><td><?= $e($collection['description']) ?></td><td><?= $e($collection['products_count']) ?></td><td><?= $e($collection['status']) ?></td></tr><?php endforeach; ?>
                        </tbody></table></div>
                    <?php endif; ?>
                </section>

            <?php elseif ($tab === 'customers'): ?>
                <section class="admin-card">
                    <form method="get" class="admin-filters">
                        <input type="hidden" name="tab" value="customers">
                        <label class="admin-field">Tìm kiếm khách hàng<input name="customer_search" value="<?= $e($customerFilters['search']) ?>" placeholder="Tên, email, số điện thoại"></label>
                        <label class="admin-field">Hạng thành viên<select name="tier"><option value="all">Tất cả</option><?php foreach (['VIP Black Card', 'Gold Member', 'Silver Member', 'Standard'] as $tier): ?><option value="<?= $e($tier) ?>" <?= $customerFilters['tier'] === $tier ? 'selected' : '' ?>><?= $e($tier) ?></option><?php endforeach; ?></select></label>
                        <button class="btn-primary" type="submit">Lọc</button>
                    </form>
                </section>
                <section class="admin-card">
                    <?php if (!$customers): ?><p class="admin-muted">Không tìm thấy khách hàng.</p><?php else: ?>
                        <div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Mã KH</th><th>Khách hàng</th><th>Liên hệ</th><th>Địa chỉ</th><th>Hạng</th><th>Số đơn</th><th>Tổng chi</th><th>Mua gần nhất</th></tr></thead><tbody>
                            <?php foreach ($customers as $customer): ?><tr><td><?= $e($customer['id']) ?></td><td><?= $e($customer['name']) ?></td><td><?= $e($customer['phone']) ?><br><?= $e($customer['email']) ?></td><td><?= $e($customer['city']) ?></td><td><?= $e($customer['tier']) ?></td><td><?= $e($customer['orders_count']) ?></td><td><?= $money($customer['total_spent']) ?></td><td><?= $e($customer['last_order'] ?: '—') ?></td></tr><?php endforeach; ?>
                        </tbody></table></div>
                    <?php endif; ?>
                </section>

            <?php elseif ($tab === 'coupons'): ?>
                <section class="admin-card">
                    <details>
                        <summary>Tạo mã khuyến mãi mới</summary>
                        <form method="post" class="admin-form-grid" style="margin-top:16px">
                            <?= $formAction('coupon_save') ?>
                            <label class="admin-field">Mã voucher *<input name="code" required></label>
                            <label class="admin-field">Mô tả *<input name="description" required></label>
                            <label class="admin-field">Loại giảm giá<select name="type"><option value="fixed">Số tiền cố định</option><option value="percent">Phần trăm</option></select></label>
                            <label class="admin-field">Giá trị *<input type="number" min="0" step="1" name="value" required></label>
                            <label class="admin-field">Đơn tối thiểu *<input type="number" min="0" step="1" name="min_order" required></label>
                            <label class="admin-field">Giới hạn lượt dùng *<input type="number" min="1" name="usage_limit" required></label>
                            <label class="admin-field">Ngày hết hạn *<input type="date" name="expiry_date" required></label>
                            <button class="btn-primary" type="submit">Tạo voucher</button>
                        </form>
                    </details>
                </section>
                <section class="admin-card">
                    <?php if (!$coupons): ?><p class="admin-muted">Chưa có mã khuyến mãi.</p><?php else: ?>
                        <div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Mã</th><th>Mô tả</th><th>Mức giảm</th><th>Đơn tối thiểu</th><th>Đã dùng / giới hạn</th><th>Hạn dùng</th><th>Thao tác</th></tr></thead><tbody>
                            <?php foreach ($coupons as $coupon): ?><tr><td><?= $e($coupon['code']) ?></td><td><?= $e($coupon['description']) ?></td><td><?= $coupon['type'] === 'percent' ? $e($coupon['value']) . '%' : $money($coupon['value']) ?></td><td><?= $money($coupon['min_order']) ?></td><td><?= $e($coupon['used_count']) ?> / <?= $e($coupon['usage_limit'] ?? '∞') ?></td><td><?= $e($coupon['expiry_date'] ?: '—') ?></td><td><details><summary>Xóa</summary><form method="post" class="admin-inline"><?= $formAction('coupon_delete') ?><input type="hidden" name="id" value="<?= $e($coupon['id']) ?>"><button class="btn-danger" type="submit">Xác nhận xóa</button></form></details></td></tr><?php endforeach; ?>
                        </tbody></table></div>
                    <?php endif; ?>
                </section>

            <?php elseif ($tab === 'settings'): ?>
                <section class="admin-card">
                    <form method="post" class="admin-form-grid">
                        <?= $formAction('settings_save') ?>
                        <label class="admin-field">Tên cửa hàng<input name="storeName" required value="<?= $e($settings['storeName'] ?? 'VAST STUDIO') ?>"></label>
                        <label class="admin-field">Slogan<input name="tagline" value="<?= $e($settings['tagline'] ?? '') ?>"></label>
                        <label class="admin-field">Hotline<input name="hotline" value="<?= $e($settings['hotline'] ?? '') ?>"></label>
                        <label class="admin-field">Email<input type="email" name="email" value="<?= $e($settings['email'] ?? '') ?>"></label>
                        <label class="admin-field">Địa chỉ Hà Nội<input name="addressHanoi" value="<?= $e($settings['addressHanoi'] ?? '') ?>"></label>
                        <label class="admin-field">Địa chỉ TP. Hồ Chí Minh<input name="addressHcm" value="<?= $e($settings['addressHcm'] ?? '') ?>"></label>
                        <label class="admin-field">Ngưỡng miễn phí vận chuyển<input type="number" min="0" name="freeShippingThreshold" value="<?= $e($settings['freeShippingThreshold'] ?? 500000) ?>"></label>
                        <label class="admin-field">Phí giao hàng tiêu chuẩn<input type="number" min="0" name="defaultShippingFee" value="<?= $e($settings['defaultShippingFee'] ?? 30000) ?>"></label>
                        <label class="admin-field">Ngân hàng<input name="bankName" value="<?= $e($settings['bankName'] ?? '') ?>"></label>
                        <label class="admin-field">Số tài khoản<input name="bankAccount" value="<?= $e($settings['bankAccount'] ?? '') ?>"></label>
                        <label class="admin-field">Chủ tài khoản<input name="bankOwner" value="<?= $e($settings['bankOwner'] ?? '') ?>"></label>
                        <button class="btn-primary" type="submit">Lưu cài đặt</button>
                    </form>
                </section>
            <?php endif; ?>
        </div>
    </main>
</div>
</body>
</html>
