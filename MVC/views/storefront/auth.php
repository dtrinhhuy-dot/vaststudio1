<?php
require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../models/OrderWorkflow.php';
require_once __DIR__ . '/../../models/SepayService.php';

$e = static function ($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};

$currentUser = $currentUser ?? null;
$userOrders = $userOrders ?? [];
$notifications = $notifications ?? [];
$returnOrderCode = $returnOrderCode ?? '';
$detailOrderCode = $detailOrderCode ?? '';
$error = $error ?? '';
$message = $message ?? '';
$activeTab = ($activeTab ?? 'login') === 'register' ? 'register' : 'login';
$redirect = $redirect ?? '';
$csrf = $csrf ?? ($_SESSION['customer_csrf'] ?? '');
$oldInput = $oldInput ?? [
    'login_identifier' => '',
    'reg_name' => '',
    'reg_phone' => '',
    'reg_email' => '',
    'reg_city' => 'Hà Nội',
];
$dbError = (bool)($dbError ?? false);

if ($currentUser) {
    $pageTitle = 'Tài Khoản Thành Viên (' . $currentUser['name'] . ') — VAST STUDIO';
} elseif ($activeTab === 'register') {
    $pageTitle = 'Đăng Ký Tài Khoản — VAST STUDIO';
} else {
    $pageTitle = 'Đăng Nhập Thành Viên — VAST STUDIO';
}
$pageDesc = 'Đăng ký và đăng nhập tài khoản thành viên VAST STUDIO. Theo dõi đơn hàng, lưu địa chỉ giao hàng và nhận ưu đãi đặc quyền.';
$activeNav = '';

$badgeToAuthClass = [
    'badge-warning' => 'auth-status-pending',
    'badge-purple' => 'auth-status-processing',
    'badge-info' => 'auth-status-shipping',
    'badge-success' => 'auth-status-completed',
    'badge-danger' => 'auth-status-cancelled',
];
$statusMap = [];
foreach (OrderWorkflow::orderStatuses() as $code => $meta) {
    $statusMap[$code] = [$meta['label'], $badgeToAuthClass[$meta['badge']] ?? 'auth-status-pending'];
}
$paymentLabels = [
    'pending' => 'Chưa thanh toán',
    'paid' => 'Đã thanh toán',
    'refunded' => 'Đã hoàn tiền',
];
$returnReasons = OrderWorkflow::returnReasons();
$unreadNotifCount = count(array_filter($notifications, static fn (array $n): bool => empty($n['is_read'])));

$cities = [
    'Hà Nội', 'TP. Hồ Chí Minh', 'Đà Nẵng', 'Hải Phòng', 'Cần Thơ',
    'Bình Dương', 'Đồng Nai', 'Quảng Ninh', 'Bắc Ninh', 'Khánh Hòa',
    'Lâm Đồng', 'Thừa Thiên Huế', 'Nghệ An', 'Thanh Hóa',
];

$redirectQuery = $redirect !== '' ? '&amp;redirect=' . rawurlencode($redirect) : '';

include __DIR__ . '/../partials/header.php';
?>

    <main class="auth-page-wrapper">
        <div class="store-container">
            <nav class="breadcrumbs" aria-label="Breadcrumb">
                <a href="index.php">Trang chủ</a>
                <span>/</span>
                <span style="color: var(--text-primary); font-weight: 600;" id="auth-breadcrumb-current">
                    <?= $currentUser ? $e('Tài khoản (' . $currentUser['name'] . ')') : ($activeTab === 'register' ? 'Đăng ký tài khoản' : 'Đăng nhập') ?>
                </span>
            </nav>

            <div id="auth-main-container" data-ssr="1">
                <?php if (!$currentUser): ?>
                    <div class="auth-box-wrapper">
                        <div class="auth-card">
                            <div class="auth-card-header">
                                <div class="auth-brand-monogram">V</div>
                                <h1 class="auth-main-title">VAST MEMBERSHIP</h1>
                                <p class="auth-sub-title">Đăng nhập hoặc tạo tài khoản thành viên VAST STUDIO</p>
                            </div>

                            <div class="auth-tabs-nav" role="tablist" aria-label="Chuyển giữa đăng nhập và đăng ký">
                                <a href="auth.php?tab=login<?= $redirectQuery ?>"
                                   class="auth-tab-btn <?= $activeTab === 'login' ? 'active' : '' ?>"
                                   id="tab-btn-login"
                                   style="text-decoration:none"
                                   onclick="if(window.Storefront){Storefront.switchAuthTab('login');return false;}">
                                    Đăng Nhập
                                </a>
                                <a href="auth.php?tab=register<?= $redirectQuery ?>"
                                   class="auth-tab-btn <?= $activeTab === 'register' ? 'active' : '' ?>"
                                   id="tab-btn-register"
                                   style="text-decoration:none"
                                   onclick="if(window.Storefront){Storefront.switchAuthTab('register');return false;}">
                                    Đăng Ký Tài Khoản
                                </a>
                            </div>

                            <div class="auth-form-body">
                                <?php if ($error !== ''): ?>
                                    <div class="auth-alert auth-alert--error" role="alert">
                                        <span data-icon="alertTriangle" style="flex-shrink:0"></span>
                                        <span><?= $e($error) ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if ($message !== ''): ?>
                                    <div class="auth-alert auth-alert--success" role="status">
                                        <span data-icon="checkCircle" style="flex-shrink:0"></span>
                                        <span><?= $e($message) ?></span>
                                    </div>
                                <?php endif; ?>

                                <!-- PANE 1: ĐĂNG NHẬP -->
                                <div class="auth-form-pane <?= $activeTab === 'login' ? 'active' : '' ?>" id="auth-pane-login">
                                    <form method="post" action="auth.php?tab=login<?= $redirectQuery ?>">
                                        <input type="hidden" name="csrf" value="<?= $e($csrf) ?>">
                                        <input type="hidden" name="action" value="customer_login">
                                        <input type="hidden" name="redirect" value="<?= $e($redirect) ?>">

                                        <div class="auth-input-group">
                                            <label class="auth-label" for="login-identifier">
                                                Email hoặc Số điện thoại <span style="color:var(--store-red)">*</span>
                                            </label>
                                            <div class="auth-input-wrap">
                                                <input type="text" id="login-identifier" name="identifier" class="auth-input"
                                                       value="<?= $e($oldInput['login_identifier']) ?>"
                                                       autocomplete="username"
                                                       placeholder="Nhập email hoặc số điện thoại..." required <?= $dbError ? 'disabled' : '' ?>>
                                            </div>
                                        </div>

                                        <div class="auth-input-group">
                                            <label class="auth-label" for="login-password">
                                                Mật khẩu <span style="color:var(--store-red)">*</span>
                                            </label>
                                            <div class="auth-input-wrap">
                                                <input type="password" id="login-password" name="password" class="auth-input auth-input--password"
                                                       autocomplete="current-password"
                                                       placeholder="Nhập mật khẩu..." required <?= $dbError ? 'disabled' : '' ?>>
                                                <button type="button" class="btn-toggle-eye" aria-label="Hiện hoặc ẩn mật khẩu" onclick="Storefront.togglePasswordVisibility('login-password', this)">
                                                    <span data-icon="eye"></span>
                                                </button>
                                            </div>
                                        </div>

                                        <div class="auth-extra-row" style="align-items:flex-start;flex-direction:column;gap:4px">
                                            <details class="auth-forgot-details" style="width:100%">
                                                <summary>Quên mật khẩu?</summary>
                                                <div class="auth-forgot-box">
                                                    Để khôi phục mật khẩu tài khoản, vui lòng liên hệ CSKH qua email
                                                    <strong><?= $e(APP_EMAIL) ?></strong> hoặc Hotline <strong><?= $e(APP_HOTLINE) ?></strong>
                                                    kèm email/SĐT đã đăng ký.
                                                </div>
                                            </details>
                                        </div>

                                        <button type="submit" class="btn-auth-submit" <?= $dbError ? 'disabled' : '' ?>>
                                            <span>Đăng Nhập Ngay</span>
                                            <span data-icon="arrowRight"></span>
                                        </button>
                                    </form>

                                    <div class="auth-switch-hint">
                                        Chưa có tài khoản VAST STUDIO?
                                        <a href="auth.php?tab=register<?= $redirectQuery ?>" onclick="if(window.Storefront){Storefront.switchAuthTab('register');return false;}">Đăng ký ngay</a>
                                    </div>
                                </div>

                                <!-- PANE 2: ĐĂNG KÝ -->
                                <div class="auth-form-pane <?= $activeTab === 'register' ? 'active' : '' ?>" id="auth-pane-register">
                                    <form method="post" action="auth.php?tab=register<?= $redirectQuery ?>">
                                        <input type="hidden" name="csrf" value="<?= $e($csrf) ?>">
                                        <input type="hidden" name="action" value="customer_register">
                                        <input type="hidden" name="redirect" value="<?= $e($redirect) ?>">

                                        <div class="membership-incentive-box">
                                            <span style="font-size:1.35rem;" aria-hidden="true">🎁</span>
                                            <div>
                                                <strong>Ưu đãi thành viên mới:</strong><br>
                                                Nhập mã <strong>VASTWELCOME</strong> giảm ngay 100.000₫ cho đơn đầu tiên từ 1.000.000₫!
                                            </div>
                                        </div>

                                        <div class="auth-input-group">
                                            <label class="auth-label" for="reg-name">
                                                Họ và tên <span style="color:var(--store-red)">*</span>
                                            </label>
                                            <div class="auth-input-wrap">
                                                <input type="text" id="reg-name" name="name" class="auth-input"
                                                       value="<?= $e($oldInput['reg_name']) ?>"
                                                       autocomplete="name"
                                                       placeholder="Ví dụ: Lê Hoàng Nam" required minlength="2" <?= $dbError ? 'disabled' : '' ?>>
                                            </div>
                                        </div>

                                        <div class="auth-form-row-2">
                                            <div class="auth-input-group">
                                                <label class="auth-label" for="reg-phone">
                                                    Số điện thoại <span style="color:var(--store-red)">*</span>
                                                </label>
                                                <div class="auth-input-wrap">
                                                    <input type="tel" id="reg-phone" name="phone" class="auth-input"
                                                           value="<?= $e($oldInput['reg_phone']) ?>"
                                                           autocomplete="tel"
                                                           placeholder="0912 345 678" required <?= $dbError ? 'disabled' : '' ?>>
                                                </div>
                                            </div>

                                            <div class="auth-input-group">
                                                <label class="auth-label" for="reg-city">Tỉnh / Thành phố</label>
                                                <div class="auth-input-wrap">
                                                    <select id="reg-city" name="city" class="auth-input" <?= $dbError ? 'disabled' : '' ?>>
                                                        <?php foreach ($cities as $city): ?>
                                                            <option value="<?= $e($city) ?>" <?= $oldInput['reg_city'] === $city ? 'selected' : '' ?>><?= $e($city) ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="auth-input-group">
                                            <label class="auth-label" for="reg-email">
                                                Địa chỉ Email <span style="color:var(--store-red)">*</span>
                                            </label>
                                            <div class="auth-input-wrap">
                                                <input type="email" id="reg-email" name="email" class="auth-input"
                                                       value="<?= $e($oldInput['reg_email']) ?>"
                                                       autocomplete="email"
                                                       placeholder="email@example.com" required <?= $dbError ? 'disabled' : '' ?>>
                                            </div>
                                        </div>

                                        <div class="auth-input-group">
                                            <label class="auth-label" for="reg-password">
                                                Mật khẩu (tối thiểu 6 ký tự) <span style="color:var(--store-red)">*</span>
                                            </label>
                                            <div class="auth-input-wrap">
                                                <input type="password" id="reg-password" name="password" class="auth-input auth-input--password"
                                                       autocomplete="new-password"
                                                       placeholder="Tạo mật khẩu tối thiểu 6 ký tự..." required minlength="6" <?= $dbError ? 'disabled' : '' ?>>
                                                <button type="button" class="btn-toggle-eye" aria-label="Hiện hoặc ẩn mật khẩu" onclick="Storefront.togglePasswordVisibility('reg-password', this)">
                                                    <span data-icon="eye"></span>
                                                </button>
                                            </div>
                                        </div>

                                        <div class="auth-input-group">
                                            <label class="auth-label" for="reg-confirm-password">
                                                Nhập lại mật khẩu <span style="color:var(--store-red)">*</span>
                                            </label>
                                            <div class="auth-input-wrap">
                                                <input type="password" id="reg-confirm-password" name="password_confirm" class="auth-input auth-input--password"
                                                       autocomplete="new-password"
                                                       placeholder="Xác nhận lại mật khẩu..." required minlength="6" <?= $dbError ? 'disabled' : '' ?>>
                                                <button type="button" class="btn-toggle-eye" aria-label="Hiện hoặc ẩn mật khẩu" onclick="Storefront.togglePasswordVisibility('reg-confirm-password', this)">
                                                    <span data-icon="eye"></span>
                                                </button>
                                            </div>
                                        </div>

                                        <div class="auth-input-group" style="margin-bottom:22px;">
                                            <label style="display:flex; align-items:flex-start; gap:8px; font-size:0.82rem; color:var(--text-secondary); cursor:pointer;">
                                                <input type="checkbox" name="terms" value="1" style="margin-top:3px; accent-color:var(--store-gold); cursor:pointer;" required <?= $dbError ? 'disabled' : '' ?>>
                                                <span>Tôi đồng ý với <a href="about.php" target="_blank" style="color:var(--store-gold); font-weight:600;">Điều khoản thành viên</a> &amp; <a href="about.php" target="_blank" style="color:var(--store-gold); font-weight:600;">Chính sách bảo mật</a> của VAST STUDIO.</span>
                                            </label>
                                        </div>

                                        <button type="submit" class="btn-auth-submit" <?= $dbError ? 'disabled' : '' ?>>
                                            <span>Tạo Tài Khoản Thành Viên</span>
                                            <span data-icon="arrowRight"></span>
                                        </button>
                                    </form>

                                    <div class="auth-switch-hint">
                                        Đã có tài khoản?
                                        <a href="auth.php?tab=login<?= $redirectQuery ?>" onclick="if(window.Storefront){Storefront.switchAuthTab('login');return false;}">Đăng nhập ngay</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php else:
                    $trimmedName = trim((string)$currentUser['name']);
                    $firstChar = function_exists('mb_substr') ? mb_substr($trimmedName, 0, 1) : substr($trimmedName, 0, 1);
                    $avatarInitial = function_exists('mb_strtoupper') ? mb_strtoupper($firstChar) : strtoupper($firstChar);
                ?>
                    <div class="profile-dashboard-layout">
                        <!-- LEFT SIDEBAR -->
                        <aside class="profile-card-base profile-user-card">
                            <div class="profile-avatar-circle"><?= $e($avatarInitial ?: 'V') ?></div>
                            <h1 class="profile-user-name"><?= $e($currentUser['name']) ?></h1>
                            <div class="profile-user-meta">
                                <div><?= $e($currentUser['email']) ?></div>
                                <div><?= $e($currentUser['phone'] ?: 'Chưa cập nhật SĐT') ?></div>
                                <div style="margin-top:4px;font-size:.76rem">Tham gia: <?= $e(date('d/m/Y', strtotime((string)$currentUser['joined_at']))) ?></div>
                            </div>
                            <div class="profile-tier-badge">
                                <span aria-hidden="true">★</span>
                                <span><?= $e($currentUser['tier']) ?></span>
                            </div>

                            <div class="profile-stats-grid">
                                <div class="profile-stat-item">
                                    <div class="profile-stat-val"><?= $e($currentUser['orders_count']) ?></div>
                                    <div class="profile-stat-lbl">Đơn hàng</div>
                                </div>
                                <div class="profile-stat-item">
                                    <div class="profile-stat-val" style="color:var(--store-gold);"><?= $e(formatVND($currentUser['total_spent'])) ?></div>
                                    <div class="profile-stat-lbl">Tổng chi tiêu</div>
                                </div>
                            </div>

                            <form method="post" action="auth.php">
                                <input type="hidden" name="csrf" value="<?= $e($csrf) ?>">
                                <input type="hidden" name="action" value="customer_logout">
                                <button type="submit" class="btn-profile-logout">
                                    <span data-icon="xCircle"></span>
                                    <span>Đăng Xuất Tài Khoản</span>
                                </button>
                            </form>
                        </aside>

                        <!-- RIGHT MAIN CONTENT -->
                        <div class="profile-content-area">
                            <?php if ($error !== ''): ?>
                                <div class="auth-alert auth-alert--error" style="margin-bottom:0" role="alert">
                                    <span data-icon="alertTriangle" style="flex-shrink:0"></span>
                                    <span><?= $e($error) ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if ($message !== ''): ?>
                                <div class="auth-alert auth-alert--success" style="margin-bottom:0" role="status">
                                    <span data-icon="checkCircle" style="flex-shrink:0"></span>
                                    <span><?= $e($message) ?></span>
                                </div>
                            <?php endif; ?>

                            <?php if ($notifications): ?>
                                <!-- THÔNG BÁO TÀI KHOẢN -->
                                <section class="profile-card-base profile-section-card">
                                    <div class="profile-section-title">
                                        <span>
                                            Thông Báo Đơn Hàng
                                            <?php if ($unreadNotifCount > 0): ?>
                                                <span class="profile-notif-badge"><?= $e($unreadNotifCount) ?> mới</span>
                                            <?php endif; ?>
                                        </span>
                                        <?php if ($unreadNotifCount > 0): ?>
                                            <form method="post" action="auth.php" style="margin:0;">
                                                <input type="hidden" name="csrf" value="<?= $e($csrf) ?>">
                                                <input type="hidden" name="action" value="customer_notifications_read">
                                                <button type="submit" class="coupon-pill-btn">Đánh dấu đã đọc</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                    <div class="profile-notif-list">
                                        <?php foreach (array_slice($notifications, 0, 6) as $notif): ?>
                                            <div class="profile-notif-item <?= empty($notif['is_read']) ? 'is-unread' : '' ?>">
                                                <div class="profile-notif-body">
                                                    <div class="profile-notif-title"><?= $e($notif['title']) ?></div>
                                                    <div class="profile-notif-msg"><?= $e($notif['message']) ?></div>
                                                    <div class="profile-notif-time"><?= $e(date('d/m/Y H:i', strtotime((string)$notif['created_at']))) ?></div>
                                                </div>
                                                <?php if (!empty($notif['link'])): ?>
                                                    <a href="<?= $e($notif['link']) ?>" class="coupon-pill-btn" style="text-decoration:none; white-space:nowrap;">Xem đơn</a>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </section>
                            <?php endif; ?>

                            <!-- SECTION 1: ĐƠN HÀNG -->
                            <section class="profile-card-base profile-section-card" id="orders-section">
                                <div class="profile-section-title">
                                    <span>Đơn Hàng Của Bạn (<?= $e(count($userOrders)) ?>)</span>
                                    <a href="order-tracking.php" style="font-size:0.82rem; color:var(--store-gold); text-decoration:none; font-weight:600;">
                                        Tra cứu đơn hàng →
                                    </a>
                                </div>

                                <?php if (!$userOrders): ?>
                                    <div style="text-align:center; padding:32px 16px; color:var(--text-muted);">
                                        <h3 style="margin:0 0 6px; color:var(--text-primary); font-family:var(--store-font-heading);">Chưa có đơn hàng nào</h3>
                                        <p style="font-size:0.86rem; margin:0 0 18px;">Khám phá các bộ sưu tập streetwear mới nhất và đặt hàng ngay.</p>
                                        <a href="products.php" class="btn-hero-primary" style="display:inline-flex; padding:10px 22px; font-size:0.85rem; text-decoration:none;">
                                            Mua sắm ngay
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <div class="profile-orders-list">
                                        <?php foreach ($userOrders as $order):
                                            $orderCode = (string)$order['id'];
                                            $orderStatus = (string)$order['order_status'];
                                            $st = $statusMap[$orderStatus] ?? [$orderStatus, 'auth-status-pending'];
                                            $payLabel = $paymentLabels[$order['payment_status'] ?? 'pending'] ?? ($order['payment_status'] ?? 'pending');
                                            $isDetailOpen = ($detailOrderCode === $orderCode || $returnOrderCode === $orderCode);
                                            $canDirectCancel = OrderWorkflow::canCustomerDirectCancel($orderStatus);
                                            $canRequestCancel = OrderWorkflow::canCustomerRequestCancel($orderStatus);
                                            $canConfirmReceipt = OrderWorkflow::canCustomerConfirmReceipt($orderStatus);
                                            $canRequestReturn = OrderWorkflow::canCustomerRequestReturn($orderStatus);
                                            $latestCancel = $order['cancel_requests'][0] ?? null;
                                            $latestReturn = $order['return_requests'][0] ?? null;
                                            $safeDomId = preg_replace('/[^A-Za-z0-9_-]/', '', $orderCode);
                                            $isQrPending = ($order['payment_status'] ?? 'pending') === 'pending'
                                                && $orderStatus !== 'cancelled'
                                                && (stripos((string)$order['payment_method'], 'qr') !== false || stripos((string)$order['payment_method'], 'sepay') !== false);
                                            $sepayQr = $isQrPending ? (new SepayService(getDatabaseConnection()))->buildQrData($order) : null;
                                        ?>
                                            <article class="profile-order-card" id="order-card-<?= $e($safeDomId) ?>">
                                                <div class="profile-order-top">
                                                    <div>
                                                        <span class="profile-order-id"><?= $e($orderCode) ?></span>
                                                        <span style="font-size:0.76rem; color:var(--text-muted); margin-left:8px;">
                                                            <?= $e(date('d/m/Y H:i', strtotime((string)$order['created_at']))) ?>
                                                        </span>
                                                        <span style="font-size:0.74rem; color:var(--text-secondary); margin-left:8px;">
                                                            · <?= $e($order['payment_method']) ?> (<?= $e($payLabel) ?>)
                                                        </span>
                                                    </div>
                                                    <span class="auth-status-badge <?= $e($st[1]) ?>"><?= $e($st[0]) ?></span>
                                                </div>

                                                <?php if ($canConfirmReceipt): ?>
                                                    <div class="profile-delivery-callout">
                                                        <strong>Đơn hàng đã được giao thành công!</strong>
                                                        <span>Vui lòng kiểm tra kỹ sản phẩm, sau đó xác nhận đã nhận hàng hoặc gửi yêu cầu đổi trả.</span>
                                                    </div>
                                                <?php endif; ?>

                                                <?php if ($latestCancel && $latestCancel['status'] === 'rejected' && !empty($latestCancel['admin_note'])): ?>
                                                    <div class="profile-inline-note profile-inline-note--warn">
                                                        <strong>Yêu cầu hủy đơn bị từ chối:</strong> <?= $e($latestCancel['admin_note']) ?>
                                                    </div>
                                                <?php endif; ?>

                                                <?php if (!empty($order['cancel_reason']) && $orderStatus === 'cancelled'): ?>
                                                    <div class="profile-inline-note profile-inline-note--danger">
                                                        <strong>Lý do hủy đơn:</strong> <?= $e($order['cancel_reason']) ?>
                                                        <?php if ((float)($order['refunded_amount'] ?? 0) > 0): ?>
                                                            · <strong>Hoàn tiền:</strong> <?= $e(formatVND($order['refunded_amount'])) ?>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>

                                                <?php if ($latestReturn): ?>
                                                    <div class="profile-inline-note <?= $latestReturn['status'] === 'rejected' ? 'profile-inline-note--danger' : 'profile-inline-note--info' ?>">
                                                        <strong>Đổi trả <?= $e($latestReturn['return_code']) ?> (<?= $e(OrderWorkflow::returnStatusLabel((string)$latestReturn['status'])) ?>):</strong>
                                                        <?= $e($latestReturn['reason_text']) ?>
                                                        <?php if (!empty($latestReturn['reject_reason'])): ?>
                                                            — <em>Lý do từ chối: <?= $e($latestReturn['reject_reason']) ?></em>
                                                        <?php elseif (!empty($latestReturn['admin_note'])): ?>
                                                            — <em><?= $e($latestReturn['admin_note']) ?></em>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>

                                                <div class="profile-order-items-grid">
                                                    <?php foreach ($order['items'] ?? [] as $item): ?>
                                                        <div class="profile-order-item-row">
                                                            <?php if (!empty($item['image'])): ?>
                                                                <img src="<?= $e($item['image']) ?>" alt="<?= $e($item['name']) ?>" class="profile-order-item-thumb">
                                                            <?php endif; ?>
                                                            <div class="profile-order-item-meta">
                                                                <div class="profile-order-item-name"><?= $e($item['name']) ?></div>
                                                                <div class="profile-order-item-sub">
                                                                    SKU: <?= $e($item['sku'] ?: '—') ?> ·
                                                                    Size <?= $e($item['size'] ?: 'Tiêu chuẩn') ?> ·
                                                                    Màu <?= $e($item['color'] ?: 'Tiêu chuẩn') ?> ·
                                                                    SL: <strong>×<?= $e($item['quantity']) ?></strong>
                                                                </div>
                                                            </div>
                                                            <div class="profile-order-item-price">
                                                                <strong><?= $e(formatVND($item['total_price'])) ?></strong>
                                                                <small><?= $e(formatVND($item['price'])) ?>/sp</small>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>

                                                <div class="profile-order-footer">
                                                    <div>
                                                        <span style="color:var(--text-muted);">Tổng thanh toán:</span>
                                                        <strong style="font-family:var(--store-font-heading); color:var(--store-gold); font-size:1.05rem; margin-left:4px;">
                                                            <?= $e(formatVND($order['total'])) ?>
                                                        </strong>
                                                    </div>
                                                    <div class="profile-order-actions">
                                                        <?php if ($isQrPending && $sepayQr): ?>
                                                            <button type="button" class="btn-order-confirm" style="background:var(--store-gold);color:#111" onclick="Storefront.toggleCustomerPanel('sepay-qr-panel-<?= $e($safeDomId) ?>')">
                                                                Thanh toán QR SePay
                                                            </button>
                                                        <?php endif; ?>

                                                        <?php if ($canConfirmReceipt): ?>
                                                            <form method="post" action="auth.php" style="margin:0;" onsubmit="return confirm('Xác nhận bạn đã nhận được đầy đủ hàng và hài lòng với đơn #<?= $e($orderCode) ?>? Trạng thái đơn sẽ chuyển sang Hoàn tất.');">
                                                                <input type="hidden" name="csrf" value="<?= $e($csrf) ?>">
                                                                <input type="hidden" name="action" value="customer_order_confirm_received">
                                                                <input type="hidden" name="order_code" value="<?= $e($orderCode) ?>">
                                                                <button type="submit" class="btn-order-confirm">
                                                                    Đã nhận được hàng
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>

                                                        <?php if ($canRequestReturn): ?>
                                                            <button type="button" class="btn-order-outline" onclick="Storefront.toggleCustomerPanel('return-panel-<?= $e($safeDomId) ?>')">
                                                                Yêu cầu đổi trả
                                                            </button>
                                                        <?php endif; ?>

                                                <?php if ($canDirectCancel): ?>
                                                            <button type="button" class="btn-order-danger-outline" onclick="Storefront.toggleCustomerPanel('cancel-panel-<?= $e($safeDomId) ?>')">
                                                                Hủy đơn
                                                            </button>
                                                        <?php elseif ($canRequestCancel): ?>
                                                            <button type="button" class="btn-order-danger-outline" onclick="Storefront.toggleCustomerPanel('cancel-req-panel-<?= $e($safeDomId) ?>')">
                                                                Yêu cầu hủy đơn
                                                            </button>
                                                        <?php elseif (in_array($orderStatus, ['processing', 'shipping'], true)): ?>
                                                            <span style="font-size:0.75rem; color:var(--text-muted);">Đang xử lý/vận chuyển — không thể hủy trực tiếp</span>
                                                        <?php endif; ?>

                                                        <a href="order-tracking.php?order=<?= rawurlencode($orderCode) ?>" class="btn-card-addcart" style="text-decoration:none; padding:6px 14px; font-size:0.8rem;">
                                                            Theo dõi đơn
                                                        </a>
                                                    </div>
                                                </div>

                                                <?php if ($isQrPending && $sepayQr): ?>
                                                    <div id="sepay-qr-panel-<?= $e($safeDomId) ?>" class="profile-action-drawer" <?= $detailOrderCode === $orderCode ? '' : 'hidden' ?>>
                                                        <div style="display:flex;gap:16px;align-items:center;flex-wrap:wrap">
                                                            <div style="background:#fff;padding:8px;border-radius:8px;width:150px;flex-shrink:0">
                                                                <img src="<?= $e($sepayQr['qrUrl']) ?>" onerror="this.onerror=null;this.src='<?= $e($sepayQr['fallbackQrUrl']) ?>';" alt="QR SePay <?= $e($orderCode) ?>" style="width:100%;height:auto;display:block">
                                                            </div>
                                                            <div style="flex:1;min-width:220px;display:grid;gap:6px;font-size:0.82rem">
                                                                <strong style="font-size:0.92rem;color:var(--text-primary)">Quét mã SePay VietQR để thanh toán tự động</strong>
                                                                <div>Ngân hàng: <strong><?= $e($sepayQr['bankName']) ?></strong></div>
                                                                <div>Số tài khoản: <strong style="font-family:monospace;color:var(--store-gold)"><?= $e($sepayQr['bankAccount']) ?></strong></div>
                                                                <div>Chủ tài khoản: <strong><?= $e($sepayQr['bankOwner']) ?></strong></div>
                                                                <div>Số tiền: <strong><?= $e(formatVND($order['total'])) ?></strong></div>
                                                                <div>Nội dung CK: <strong style="font-family:monospace;color:var(--store-gold)"><?= $e($sepayQr['transferContent']) ?></strong></div>
                                                                <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:4px">
                                                                    <button type="button" class="btn-order-confirm" onclick="Storefront.checkCustomerOrderSepay('<?= $e($orderCode) ?>', this)">Kiểm tra thanh toán</button>
                                                                    <button type="button" class="btn-order-outline" onclick="Storefront.copyToClipboard('<?= $e($sepayQr['transferContent']) ?>', 'Đã chép nội dung CK!')">Chép nội dung</button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>

                                                <?php if ($canDirectCancel): ?>
                                                    <div id="cancel-panel-<?= $e($safeDomId) ?>" class="profile-action-drawer" hidden>
                                                        <form method="post" action="auth.php" onsubmit="return confirm('Xác nhận hủy đơn hàng #<?= $e($orderCode) ?>? Hành động này không thể hoàn tác.');">
                                                            <input type="hidden" name="csrf" value="<?= $e($csrf) ?>">
                                                            <input type="hidden" name="action" value="customer_order_cancel">
                                                            <input type="hidden" name="order_code" value="<?= $e($orderCode) ?>">
                                                            <label class="checkout-label">Lý do hủy đơn <span class="required">*</span></label>
                                                            <textarea name="reason" class="checkout-input" rows="2" required minlength="5" placeholder="Nhập lý do hủy đơn (ví dụ: Đổi ý, đặt nhầm size/màu, đổi địa chỉ...)"></textarea>
                                                            <div class="profile-drawer-btns">
                                                                <button type="button" class="btn-order-outline" onclick="Storefront.toggleCustomerPanel('cancel-panel-<?= $e($safeDomId) ?>', false)">Đóng</button>
                                                                <button type="submit" class="btn-order-danger">Xác nhận hủy đơn</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                <?php endif; ?>

                                                <?php if ($canRequestCancel): ?>
                                                    <div id="cancel-req-panel-<?= $e($safeDomId) ?>" class="profile-action-drawer" hidden>
                                                        <form method="post" action="auth.php" onsubmit="return confirm('Đơn hàng đã được Admin xác nhận. Gửi yêu cầu hủy để Admin xem xét?');">
                                                            <input type="hidden" name="csrf" value="<?= $e($csrf) ?>">
                                                            <input type="hidden" name="action" value="customer_order_cancel_request">
                                                            <input type="hidden" name="order_code" value="<?= $e($orderCode) ?>">
                                                            <label class="checkout-label">Lý do yêu cầu hủy đơn <span class="required">*</span></label>
                                                            <textarea name="reason" class="checkout-input" rows="2" required minlength="5" placeholder="Nhập lý do để Admin kiểm tra và phê duyệt hủy đơn..."></textarea>
                                                            <div class="profile-drawer-btns">
                                                                <button type="button" class="btn-order-outline" onclick="Storefront.toggleCustomerPanel('cancel-req-panel-<?= $e($safeDomId) ?>', false)">Đóng</button>
                                                                <button type="submit" class="btn-order-danger">Gửi yêu cầu hủy</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                <?php endif; ?>

                                                <?php if ($canRequestReturn): ?>
                                                    <div id="return-panel-<?= $e($safeDomId) ?>" class="profile-action-drawer" <?= $returnOrderCode === $orderCode ? '' : 'hidden' ?>>
                                                        <h4 style="margin:0 0 10px; font-size:0.95rem;">Gửi Yêu Cầu Đổi Trả Hàng — #<?= $e($orderCode) ?></h4>
                                                        <form method="post" action="auth.php" enctype="multipart/form-data">
                                                            <input type="hidden" name="csrf" value="<?= $e($csrf) ?>">
                                                            <input type="hidden" name="action" value="customer_return_submit">
                                                            <input type="hidden" name="order_code" value="<?= $e($orderCode) ?>">

                                                            <div style="margin-bottom:12px;">
                                                                <label class="checkout-label">1. Chọn sản phẩm và số lượng muốn đổi trả <span class="required">*</span></label>
                                                                <div class="profile-return-items-pick">
                                                                    <?php
                                                                    $orderItemsCount = count($order['items'] ?? []);
                                                                    foreach ($order['items'] ?? [] as $idx => $item):
                                                                        $defaultReturnQty = ($orderItemsCount === 1 || $idx === 0) ? 1 : 0;
                                                                    ?>
                                                                        <div class="profile-return-pick-row">
                                                                            <div>
                                                                                <strong><?= $e($item['name']) ?></strong>
                                                                                <div style="font-size:0.75rem; color:var(--text-muted);">
                                                                                    SKU: <?= $e($item['sku'] ?: '—') ?> · Size <?= $e($item['size'] ?: '—') ?> · Màu <?= $e($item['color'] ?: '—') ?> (Đã mua: <?= $e($item['quantity']) ?>)
                                                                                </div>
                                                                            </div>
                                                                            <label style="display:flex; align-items:center; gap:6px; font-size:0.8rem;">
                                                                                <span>SL trả:</span>
                                                                                <input type="number" name="return_qty[<?= (int)$item['order_item_id'] ?>]" min="0" max="<?= (int)$item['quantity'] ?>" value="<?= $defaultReturnQty ?>" class="checkout-input" style="width:70px; padding:6px 8px; text-align:center;">
                                                                            </label>
                                                                        </div>
                                                                    <?php endforeach; ?>
                                                                </div>
                                                            </div>

                                                            <div class="checkout-form-grid">
                                                                <div>
                                                                    <label class="checkout-label">2. Lý do đổi trả <span class="required">*</span></label>
                                                                    <select name="reason_code" class="checkout-input" required>
                                                                        <option value="">-- Chọn lý do --</option>
                                                                        <?php foreach ($returnReasons as $rCode => $rLabel): ?>
                                                                            <option value="<?= $e($rCode) ?>"><?= $e($rLabel) ?></option>
                                                                        <?php endforeach; ?>
                                                                    </select>
                                                                </div>
                                                                <div>
                                                                    <label class="checkout-label">3. Hình ảnh thực tế (bắt buộc 1–5 ảnh JPG/PNG/WEBP) <span class="required">*</span></label>
                                                                    <input type="file" name="return_images[]" accept="image/jpeg,image/png,image/webp" multiple required class="checkout-input" style="padding:7px 10px;">
                                                                </div>
                                                                <div class="form-group-full">
                                                                    <label class="checkout-label">4. Mô tả chi tiết vấn đề <span class="required">*</span></label>
                                                                    <textarea name="description" class="checkout-input" rows="2" required minlength="8" placeholder="Mô tả chi tiết lỗi/hư hỏng hoặc sản phẩm muốn đổi sang size/màu khác..."></textarea>
                                                                </div>
                                                            </div>

                                                            <div class="profile-drawer-btns">
                                                                <button type="button" class="btn-order-outline" onclick="Storefront.toggleCustomerPanel('return-panel-<?= $e($safeDomId) ?>', false)">Đóng</button>
                                                                <button type="submit" class="btn-hero-primary" style="padding:9px 18px; font-size:0.82rem;">Gửi yêu cầu đổi trả</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                <?php endif; ?>

                                                <details class="profile-order-details" <?= $isDetailOpen ? 'open' : '' ?>>
                                                    <summary>Chi tiết đơn hàng, thanh toán &amp; lịch sử xử lý (<?= $e(count($order['history'] ?? [])) ?> mốc)</summary>
                                                    <div class="profile-details-grid">
                                                        <div>
                                                            <div class="profile-detail-block-title">Thông tin giao hàng &amp; thanh toán</div>
                                                            <div class="profile-detail-lines">
                                                                <div><strong>Người nhận:</strong> <?= $e($order['customer_name']) ?> — <?= $e($order['customer_phone']) ?></div>
                                                                <div><strong>Địa chỉ:</strong> <?= $e($order['shipping_address']) ?></div>
                                                                <div><strong>Vận chuyển:</strong> <?= $e($order['shipping_method']) ?> (<?= (float)$order['shipping_fee'] == 0.0 ? 'Miễn phí' : $e(formatVND($order['shipping_fee'])) ?>)</div>
                                                                <div><strong>Tạm tính:</strong> <?= $e(formatVND($order['subtotal'])) ?><?php if ((float)$order['discount'] > 0): ?> · <strong>Giảm giá:</strong> -<?= $e(formatVND($order['discount'])) ?><?= !empty($order['coupon_code']) ? ' (' . $e($order['coupon_code']) . ')' : '' ?><?php endif; ?></div>
                                                                <?php if (!empty($order['note'])): ?>
                                                                    <div><strong>Ghi chú:</strong> <?= $e($order['note']) ?></div>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                        <div>
                                                            <div class="profile-detail-block-title">Lịch sử xử lý đơn hàng</div>
                                                            <div class="profile-timeline">
                                                                <?php foreach ($order['history'] ?? [] as $h): ?>
                                                                    <div class="profile-timeline-row">
                                                                        <span class="profile-timeline-dot"></span>
                                                                        <div>
                                                                            <div style="font-weight:700; font-size:0.8rem; color:var(--text-primary);">
                                                                                <?= $e(OrderWorkflow::statusLabel((string)$h['status'])) ?>
                                                                                <span style="font-weight:400; color:var(--text-muted); margin-left:6px;"><?= $e(date('d/m/Y H:i', strtotime((string)$h['created_at']))) ?></span>
                                                                            </div>
                                                                            <div style="font-size:0.76rem; color:var(--text-secondary);">
                                                                                <?= $e($h['note'] ?: '') ?> · <em><?= $e($h['resolved_actor'] ?? 'Hệ thống') ?></em>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <?php foreach ($order['return_requests'] ?? [] as $ret): ?>
                                                        <div class="profile-return-detail-box">
                                                            <div style="display:flex; justify-content:space-between; align-items:center; gap:8px; margin-bottom:6px;">
                                                                <strong>Phiếu đổi trả #<?= $e($ret['return_code']) ?></strong>
                                                                <span class="auth-status-badge auth-status-processing"><?= $e(OrderWorkflow::returnStatusLabel((string)$ret['status'])) ?></span>
                                                            </div>
                                                            <div style="font-size:0.8rem; color:var(--text-secondary); margin-bottom:6px;">
                                                                <strong>Lý do:</strong> <?= $e($ret['reason_text']) ?> — <?= $e($ret['description']) ?>
                                                            </div>
                                                            <?php if (!empty($ret['items'])): ?>
                                                                <div style="font-size:0.78rem; color:var(--text-secondary); margin-bottom:8px;">
                                                                    <strong>Sản phẩm trả:</strong>
                                                                    <?= $e(implode(', ', array_map(static fn (array $ri): string => $ri['product_name'] . ' (' . ($ri['size'] ?: '-') . '/' . ($ri['color'] ?: '-') . ') ×' . $ri['quantity'], $ret['items']))) ?>
                                                                </div>
                                                            <?php endif; ?>
                                                            <?php if (!empty($ret['images'])): ?>
                                                                <div class="profile-return-thumbs">
                                                                    <?php foreach ($ret['images'] as $img): ?>
                                                                        <a href="<?= $e($img['file_path']) ?>" target="_blank" rel="noopener">
                                                                            <img src="<?= $e($img['file_path']) ?>" alt="Ảnh đổi trả">
                                                                        </a>
                                                                    <?php endforeach; ?>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </details>
                                            </article>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </section>

                            <!-- SECTION 2: VOUCHER THÀNH VIÊN -->
                            <section class="profile-card-base profile-section-card">
                                <div class="profile-section-title">
                                    <span>Kho Voucher Ưu Đãi</span>
                                    <span class="method-badge-rec">Thành Viên</span>
                                </div>
                                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:14px;">
                                    <div style="background:var(--bg-subtle); border:1px dashed var(--store-gold); border-radius:var(--radius-md); padding:14px; display:flex; justify-content:space-between; align-items:center; gap:10px;">
                                        <div>
                                            <strong style="font-family:monospace; color:var(--store-gold); font-size:1.02rem;">VASTWELCOME</strong>
                                            <div style="font-size:0.78rem; color:var(--text-secondary); margin-top:2px;">Giảm 100.000₫ cho đơn từ 1.000.000₫</div>
                                        </div>
                                        <button type="button" class="coupon-pill-btn" onclick="Storefront.copyToClipboard('VASTWELCOME', 'Đã sao chép mã giảm giá!')">
                                            Sao chép
                                        </button>
                                    </div>
                                    <div style="background:var(--bg-subtle); border:1px dashed var(--store-emerald); border-radius:var(--radius-md); padding:14px; display:flex; justify-content:space-between; align-items:center; gap:10px;">
                                        <div>
                                            <strong style="font-family:monospace; color:var(--store-emerald); font-size:1.02rem;">FREESHIP</strong>
                                            <div style="font-size:0.78rem; color:var(--text-secondary); margin-top:2px;">Hỗ trợ phí giao hàng toàn quốc</div>
                                        </div>
                                        <button type="button" class="coupon-pill-btn" onclick="Storefront.copyToClipboard('FREESHIP', 'Đã sao chép mã giảm giá!')">
                                            Sao chép
                                        </button>
                                    </div>
                                </div>
                            </section>

                            <!-- SECTION 3: CẬP NHẬT THÔNG TIN -->
                            <section class="profile-card-base profile-section-card">
                                <div class="profile-section-title">
                                    <span>Cập Nhật Thông Tin Cá Nhân</span>
                                </div>
                                <form method="post" action="auth.php">
                                    <input type="hidden" name="csrf" value="<?= $e($csrf) ?>">
                                    <input type="hidden" name="action" value="customer_profile_update">
                                    <div class="checkout-form-grid">
                                        <div>
                                            <label class="checkout-label" for="prof-name">Họ và tên <span class="required">*</span></label>
                                            <input type="text" id="prof-name" name="name" class="checkout-input" value="<?= $e($currentUser['name']) ?>" required minlength="2">
                                        </div>
                                        <div>
                                            <label class="checkout-label" for="prof-phone">Số điện thoại <span class="required">*</span></label>
                                            <input type="tel" id="prof-phone" name="phone" class="checkout-input" value="<?= $e($currentUser['phone']) ?>" required>
                                        </div>
                                        <div>
                                            <label class="checkout-label" for="prof-email">Email đăng nhập</label>
                                            <input type="email" id="prof-email" class="checkout-input auth-input" value="<?= $e($currentUser['email']) ?>" readonly>
                                        </div>
                                        <div>
                                            <label class="checkout-label" for="prof-city">Địa chỉ / Tỉnh thành</label>
                                            <input type="text" id="prof-city" name="city" class="checkout-input" value="<?= $e($currentUser['address'] !== '' ? $currentUser['address'] : $currentUser['city']) ?>" placeholder="VD: Hà Nội hoặc địa chỉ giao hàng mặc định">
                                        </div>
                                    </div>
                                    <div style="margin-top:18px; text-align:right;">
                                        <button type="submit" class="btn-hero-primary" style="padding:11px 22px; font-size:0.86rem;">
                                            Lưu Thay Đổi
                                        </button>
                                    </div>
                                </form>
                            </section>

                            <!-- SECTION 4: ĐỔI MẬT KHẨU -->
                            <section class="profile-card-base profile-section-card">
                                <div class="profile-section-title">
                                    <span>Bảo Mật &amp; Đổi Mật Khẩu</span>
                                </div>
                                <form method="post" action="auth.php">
                                    <input type="hidden" name="csrf" value="<?= $e($csrf) ?>">
                                    <input type="hidden" name="action" value="customer_password_change">
                                    <div class="checkout-form-grid">
                                        <div class="form-group-full">
                                            <label class="checkout-label" for="cur-password">Mật khẩu hiện tại <span class="required">*</span></label>
                                            <div class="auth-input-wrap">
                                                <input type="password" id="cur-password" name="current_password" class="auth-input auth-input--password" autocomplete="current-password" required>
                                                <button type="button" class="btn-toggle-eye" aria-label="Hiện hoặc ẩn mật khẩu" onclick="Storefront.togglePasswordVisibility('cur-password', this)">
                                                    <span data-icon="eye"></span>
                                                </button>
                                            </div>
                                        </div>
                                        <div>
                                            <label class="checkout-label" for="new-password">Mật khẩu mới (tối thiểu 6 ký tự) <span class="required">*</span></label>
                                            <div class="auth-input-wrap">
                                                <input type="password" id="new-password" name="new_password" class="auth-input auth-input--password" autocomplete="new-password" required minlength="6">
                                                <button type="button" class="btn-toggle-eye" aria-label="Hiện hoặc ẩn mật khẩu" onclick="Storefront.togglePasswordVisibility('new-password', this)">
                                                    <span data-icon="eye"></span>
                                                </button>
                                            </div>
                                        </div>
                                        <div>
                                            <label class="checkout-label" for="confirm-new-password">Nhập lại mật khẩu mới <span class="required">*</span></label>
                                            <div class="auth-input-wrap">
                                                <input type="password" id="confirm-new-password" name="new_password_confirm" class="auth-input auth-input--password" autocomplete="new-password" required minlength="6">
                                                <button type="button" class="btn-toggle-eye" aria-label="Hiện hoặc ẩn mật khẩu" onclick="Storefront.togglePasswordVisibility('confirm-new-password', this)">
                                                    <span data-icon="eye"></span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div style="margin-top:18px; text-align:right;">
                                        <button type="submit" class="btn-hero-primary" style="padding:11px 22px; font-size:0.86rem;">
                                            Đổi Mật Khẩu
                                        </button>
                                    </div>
                                </form>
                            </section>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

<?php
include __DIR__ . '/../partials/footer.php';
?>
