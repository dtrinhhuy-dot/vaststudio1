<?php
require_once __DIR__ . '/../models/OrderWorkflow.php';

$e = static function ($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};
$money = static function ($value): string {
    return number_format((float)$value, 0, ',', '.') . ' ₫';
};
$shortMoney = static function ($value): string {
    $amount = (float)$value;
    if ($amount <= 0) {
        return '—';
    }
    if ($amount >= 1000000) {
        $formatted = rtrim(rtrim(number_format($amount / 1000000, 1, ',', '.'), '0'), ',');
        return $formatted . 'tr';
    }
    if ($amount >= 1000) {
        return round($amount / 1000) . 'k';
    }
    return (string)round($amount);
};
$formatDateTime = static function (?string $value): string {
    $raw = trim((string)$value);
    if ($raw === '') {
        return '—';
    }
    $ts = strtotime($raw);
    return $ts === false ? $raw : date('d/m/Y H:i', $ts);
};
$formatDateOnly = static function (?string $value): string {
    $raw = trim((string)$value);
    if ($raw === '') {
        return '—';
    }
    $ts = strtotime($raw);
    return $ts === false ? $raw : date('d/m/Y', $ts);
};
$icon = static function (string $name, int $size = 18): string {
    $paths = [
        'dashboard' => '<rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/>',
        'products' => '<path d="M20.38 3.46 16 2a4 4 0 0 1-8 0L3.62 3.46a2 2 0 0 0-1.34 2.23l.58 3.47a1 1 0 0 0 .99.84H6v10c0 1.1.9 2 2 2h8a2 2 0 0 0 2-2V10h2.15a1 1 0 0 0 .99-.84l.58-3.47a2 2 0 0 0-1.34-2.23z"/>',
        'orders' => '<circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/>',
        'collections' => '<path d="m12.83 2.18a2 2 0 0 0-1.66 0L2.6 6.08a1 1 0 0 0 0 1.83l8.58 3.91a2 2 0 0 0 1.66 0l8.58-3.9a1 1 0 0 0 0-1.83Z"/><path d="m22 17.65-9.17 4.16a2 2 0 0 1-1.66 0L2 17.65"/><path d="m22 12.65-9.17 4.16a2 2 0 0 1-1.66 0L2 12.65"/>',
        'customers' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
        'coupons' => '<path d="M2 9a3 3 0 0 1 0 6v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2a3 3 0 0 1 0-6V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2Z"/><path d="M13 5v2"/><path d="M13 17v2"/><path d="M13 11v2"/>',
        'settings' => '<path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/>',
        'store' => '<path d="m2 7 4.41-4.41A2 2 0 0 1 7.83 2h8.34a2 2 0 0 1 1.42.59L22 7"/><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><path d="M15 22v-4a2 2 0 0 0-2-2h-2a2 2 0 0 0-2 2v4"/><path d="M2 7h20"/>',
        'sun' => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/>',
        'moon' => '<path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/>',
        'dollar' => '<line x1="12" x2="12" y1="2" y2="22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>',
        'package' => '<path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/>',
        'search' => '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>',
        'plus' => '<path d="M5 12h14"/><path d="M12 5v14"/>',
        'edit' => '<path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/><path d="m15 5 4 4"/>',
        'trash' => '<path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/>',
        'eye' => '<path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/>',
        'check' => '<path d="M20 6 9 17l-5-5"/>',
        'checkCircle' => '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m9 11 3 3L22 4"/>',
        'alert' => '<path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" x2="12" y1="9" y2="13"/><line x1="12" x2="12.01" y1="17" y2="17"/>',
        'clock' => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
        'truck' => '<path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14"/><circle cx="17" cy="18" r="2"/><circle cx="7" cy="18" r="2"/>',
        'xCircle' => '<circle cx="12" cy="12" r="10"/><path d="m15 9-6 6"/><path d="m9 9 6 6"/>',
        'x' => '<path d="M18 6 6 18"/><path d="m6 6 12 12"/>',
        'image' => '<rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/>',
        'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" x2="9" y1="12" y2="12"/>',
        'arrowRight' => '<path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>',
        'rotateCcw' => '<path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/>',
        'mail' => '<rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>',
        'bell' => '<path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>',
    ];
    $body = $paths[$name] ?? $paths['products'];
    return '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $body . '</svg>';
};

$weekdays = [
    1 => 'Thứ Hai',
    2 => 'Thứ Ba',
    3 => 'Thứ Tư',
    4 => 'Thứ Năm',
    5 => 'Thứ Sáu',
    6 => 'Thứ Bảy',
    7 => 'Chủ Nhật',
];
$todayLabel = ($weekdays[(int)date('N')] ?? '') . ', ' . date('d/m/Y');

$maxRevenue = max(array_column($revenueHistory, 'value') ?: [0]);
$rangeRevenueTotal = array_sum(array_column($revenueHistory, 'value') ?: [0]);

$statusMeta = OrderWorkflow::orderStatuses();
$statusLabels = array_map(static fn (array $meta): string => $meta['label'], $statusMeta);
$returnStatusMeta = OrderWorkflow::returnStatuses();
$returnStatusLabels = array_map(static fn (array $meta): string => $meta['label'], $returnStatusMeta);

$paymentLabels = [
    'pending' => 'Chưa thanh toán',
    'paid' => 'Đã thanh toán',
    'refunded' => 'Đã hoàn tiền',
    'Đã thanh toán' => 'Đã thanh toán',
    'Chưa thanh toán' => 'Chưa thanh toán',
    'Đã hoàn tiền' => 'Đã hoàn tiền',
];
$paymentBadgeClass = static function (?string $status): string {
    return match ($status) {
        'paid', 'Đã thanh toán' => 'badge-success',
        'refunded', 'Đã hoàn tiền' => 'badge-danger',
        default => 'badge-warning',
    };
};
$tierBadgeClass = static function (?string $tier): string {
    $normalized = strtolower(trim((string)$tier));
    if (str_contains($normalized, 'vip')) {
        return 'badge-vip';
    }
    if (str_contains($normalized, 'gold')) {
        return 'badge-warning';
    }
    if (str_contains($normalized, 'silver')) {
        return 'badge-info';
    }
    return 'badge-neutral';
};

$navSections = [
    'Menu Chính' => [
        'dashboard' => ['label' => 'Tổng quan', 'icon' => 'dashboard', 'badge' => null],
        'products' => ['label' => 'Sản phẩm', 'icon' => 'products', 'badge' => null],
        'orders' => ['label' => 'Đơn hàng', 'icon' => 'orders', 'badge' => ($stats['pending'] ?? 0) > 0 ? (string)$stats['pending'] : null],
        'returns' => ['label' => 'Đổi trả & Hoàn tiền', 'icon' => 'rotateCcw', 'badge' => ($stats['pending_returns'] ?? 0) > 0 ? (string)$stats['pending_returns'] : null],
        'collections' => ['label' => 'Bộ sưu tập', 'icon' => 'collections', 'badge' => null],
    ],
    'Khách hàng & Bán hàng' => [
        'customers' => ['label' => 'Khách hàng', 'icon' => 'customers', 'badge' => null],
        'coupons' => ['label' => 'Khuyến mãi', 'icon' => 'coupons', 'badge' => null],
    ],
    'Hệ thống' => [
        'emails' => ['label' => 'Nhật ký Email', 'icon' => 'mail', 'badge' => ($stats['failed_emails'] ?? 0) > 0 ? (string)$stats['failed_emails'] : null],
        'settings' => ['label' => 'Cài đặt Shop', 'icon' => 'settings', 'badge' => null],
    ],
];

$formAction = static function (string $action) use ($csrf): string {
    return '<input type="hidden" name="csrf" value="' . htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') . '">' .
        '<input type="hidden" name="action" value="' . htmlspecialchars($action, ENT_QUOTES, 'UTF-8') . '">';
};

$avatarInitials = 'VS';
if (trim((string)$adminName) !== '' && $adminName !== 'Quản trị viên cục bộ') {
    $parts = preg_split('/\s+/u', trim((string)$adminName)) ?: [];
    $initials = '';
    foreach (array_slice($parts, 0, 2) as $part) {
        $initials .= function_exists('mb_substr') ? mb_strtoupper(mb_substr($part, 0, 1)) : strtoupper(substr($part, 0, 1));
    }
    if ($initials !== '') {
        $avatarInitials = $initials;
    }
}

$hasProductFilters = $productFilters['search'] !== ''
    || $productFilters['category'] !== 'all'
    || $productFilters['stock'] !== 'all'
    || $productFilters['sort'] !== 'newest';
$hasOrderFilters = $orderFilters['search'] !== '' || $orderFilters['status'] !== 'all';
$hasCustomerFilters = $customerFilters['search'] !== '' || $customerFilters['tier'] !== 'all';
?>
<!DOCTYPE html>
<html lang="vi" data-theme="<?= $e($theme) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $e($pageTitle) ?> — VAST STUDIO</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' rx='20' fill='%23000'/><text x='50' y='68' font-size='60' font-family='sans-serif' font-weight='900' fill='%23fff' text-anchor='middle'>V</text></svg>">
    <style>
        .nav-icon{display:inline-flex;align-items:center;justify-content:center;flex-shrink:0}
        .sidebar-footer-actions{display:flex;align-items:center;gap:8px}
        .sidebar-store-btn{display:flex;align-items:center;justify-content:space-between;gap:10px;margin:8px 14px 14px;padding:10px 14px;border-radius:var(--radius-md);border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.04);color:#e4e4e7;text-decoration:none;font-size:.82rem;font-weight:600;transition:var(--transition)}
        .sidebar-store-btn:hover{background:rgba(255,255,255,.12);color:#fff;border-color:rgba(255,255,255,.24)}
        .topbar{gap:16px}
        .admin-user-chip{display:inline-flex;align-items:center;gap:8px;padding:6px 12px;border-radius:var(--radius-full);background:var(--bg-subtle);border:1px solid var(--border-color);color:var(--text-secondary);font-size:.8rem;font-weight:600}
        .admin-user-dot{width:8px;height:8px;border-radius:50%;background:#10b981;flex-shrink:0}
        .btn-sm{padding:6px 12px;font-size:.78rem;border-radius:var(--radius-sm)}
        .btn-xs{padding:5px 10px;font-size:.75rem;border-radius:var(--radius-sm)}
        .admin-stack{display:grid;gap:20px}
        .admin-alert{display:flex;align-items:center;gap:12px;padding:14px 18px;border-radius:var(--radius-md);font-size:.88rem;font-weight:500;border:1px solid transparent}
        .admin-alert--error{background:var(--status-danger-bg);color:var(--status-danger-text);border-color:color-mix(in srgb,var(--status-danger-text) 28%,transparent)}
        .admin-alert--success{background:var(--status-success-bg);color:var(--status-success-text);border-color:color-mix(in srgb,var(--status-success-text) 28%,transparent)}
        .admin-alert-icon{display:inline-flex;flex-shrink:0}
        .admin-inline{display:inline-flex;align-items:center;gap:8px}
        .admin-actions{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
        .admin-muted{color:var(--text-muted)}
        .mono-id{display:inline-block;padding:3px 8px;border-radius:var(--radius-sm);background:var(--bg-subtle);border:1px solid var(--border-color);color:var(--text-primary);font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:.78rem;font-weight:700;letter-spacing:.02em;text-decoration:none}
        a.mono-id:hover{border-color:var(--border-focus)}
        .coupon-code-chip{display:inline-flex;align-items:center;padding:4px 10px;border-radius:var(--radius-sm);background:var(--status-info-bg);color:var(--status-info-text);border:1px dashed color-mix(in srgb,var(--status-info-text) 40%,transparent);font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:.84rem;font-weight:800;letter-spacing:.04em}
        .admin-filters{display:grid;grid-template-columns:minmax(220px,1.6fr) repeat(3,minmax(150px,1fr)) auto;gap:12px;align-items:end}
        .admin-filters--simple{grid-template-columns:minmax(240px,1.8fr) minmax(180px,1fr) auto}
        .admin-filters--search{grid-template-columns:minmax(260px,1fr) auto}
        .admin-field{display:grid;gap:6px;min-width:0}
        .admin-field>span{font-size:.76rem;font-weight:600;color:var(--text-secondary)}
        .admin-field input,.admin-field select,.admin-field textarea{width:100%;min-height:40px;padding:8px 12px;border:1px solid var(--border-color);border-radius:var(--radius-md);background:var(--bg-subtle);color:var(--text-primary);font-size:.85rem;transition:var(--transition)}
        .admin-field textarea{min-height:92px;resize:vertical}
        .admin-field input::placeholder,.admin-field textarea::placeholder{color:var(--text-muted)}
        .admin-field select option{background:var(--bg-surface);color:var(--text-primary)}
        .admin-field input:focus,.admin-field select:focus,.admin-field textarea:focus{outline:none;border-color:var(--border-focus);background:var(--bg-surface);box-shadow:0 0 0 3px color-mix(in srgb,var(--border-focus) 14%,transparent)}
        .admin-filter-actions{display:flex;align-items:center;gap:8px}
        .admin-select-sm{min-height:34px;padding:5px 10px;border-radius:var(--radius-sm);border:1px solid var(--border-color);background:var(--bg-subtle);color:var(--text-primary);font-size:.8rem;cursor:pointer;outline:none;transition:var(--transition)}
        .admin-select-sm option{background:var(--bg-surface);color:var(--text-primary)}
        .admin-select-sm:focus{border-color:var(--border-focus)}
        .admin-chart-summary{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:14px;padding-bottom:12px;border-bottom:1px solid var(--border-subtle);font-size:.82rem;color:var(--text-secondary)}
        .admin-chart-summary strong{color:var(--text-primary);font-family:var(--font-heading);font-size:.98rem}
        .admin-chart-empty{margin-bottom:14px;padding:12px 14px;border-radius:var(--radius-md);background:var(--bg-subtle);border:1px dashed var(--border-color);color:var(--text-secondary);font-size:.82rem}
        .admin-chart-wrap{overflow-x:auto;padding-bottom:4px}
        .admin-chart{position:relative;display:grid;grid-auto-flow:column;grid-auto-columns:minmax(44px,1fr);align-items:end;gap:10px;min-width:460px;padding-top:26px}
        .admin-chart--30{grid-auto-columns:minmax(38px,1fr);min-width:960px;gap:6px}
        .admin-chart-column{display:flex;flex-direction:column;align-items:center;gap:6px;min-width:0}
        .admin-chart-value{font-size:.7rem;font-weight:700;color:var(--text-secondary);white-space:nowrap}
        .admin-chart-track{width:100%;max-width:34px;height:150px;display:flex;align-items:flex-end;justify-content:center;border-bottom:1px solid var(--border-color);background-image:repeating-linear-gradient(to top,transparent,transparent 36px,var(--border-subtle) 36px,var(--border-subtle) 37px);border-radius:4px 4px 0 0}
        .admin-chart-bar{display:block;width:100%;border-radius:6px 6px 0 0;background:linear-gradient(180deg,#3b82f6 0%,rgba(37,99,235,.45) 100%);transition:opacity .2s ease}
        .admin-chart-bar:hover{opacity:.85}
        .admin-chart-bar--empty{height:2px;background:var(--border-color);opacity:.55;border-radius:2px}
        .admin-chart-label{font-size:.72rem;color:var(--text-muted);font-weight:500;white-space:nowrap}
        .thumb-placeholder{width:46px;height:46px;border-radius:var(--radius-sm);background:var(--bg-subtle);border:1px solid var(--border-color);color:var(--text-muted);display:inline-flex;align-items:center;justify-content:center;flex-shrink:0}
        .collection-banner-placeholder{height:180px;width:100%;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:6px;background:linear-gradient(135deg,#18181b 0%,#27272a 100%);color:#e4e4e7;text-align:center;padding:20px}
        .collection-banner-placeholder span{font-size:.7rem;letter-spacing:.14em;text-transform:uppercase;color:#a1a1aa;font-weight:700}
        .collection-banner-placeholder strong{font-family:var(--font-heading);font-size:1.1rem}
        .panel-disclosure{overflow:hidden;border:1px solid var(--border-color);border-radius:var(--radius-lg);background:var(--bg-surface);box-shadow:var(--shadow-sm)}
        .panel-disclosure>summary{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:16px 22px;cursor:pointer;list-style:none;transition:background .2s ease}
        .panel-disclosure>summary::-webkit-details-marker{display:none}
        .panel-disclosure>summary:hover{background:var(--bg-surface-hover)}
        .panel-disclosure[open]>summary{border-bottom:1px solid var(--border-color)}
        .panel-disclosure-left{display:flex;align-items:center;gap:14px}
        .panel-disclosure-mark{display:grid;place-items:center;width:40px;height:40px;border:1px solid var(--border-color);border-radius:11px;background:var(--bg-subtle);color:var(--text-primary);flex-shrink:0}
        .panel-disclosure-copy{display:grid;gap:2px}
        .panel-disclosure-copy strong{font-size:.94rem;color:var(--text-primary)}
        .panel-disclosure-copy small{font-size:.78rem;color:var(--text-muted)}
        .panel-disclosure-right{display:flex;align-items:center;gap:10px}
        .panel-disclosure-chevron{display:grid;place-items:center;width:30px;height:30px;border:1px solid var(--border-color);border-radius:50%;color:var(--text-secondary);font-size:1.05rem;font-weight:600}
        .panel-disclosure-chevron:after{content:"+"}
        .panel-disclosure[open] .panel-disclosure-chevron:after{content:"−"}
        .panel-disclosure-body{padding:22px}
        .product-editor-heading{display:flex;align-items:flex-start;justify-content:space-between;gap:18px;margin-bottom:8px}
        .product-editor-eyebrow{margin-bottom:4px;color:var(--text-muted);font-size:.72rem;font-weight:700;letter-spacing:.12em;text-transform:uppercase}
        .product-editor-heading h2{margin:0;color:var(--text-primary);font-size:1.2rem;font-family:var(--font-heading)}
        .product-editor-heading p:last-child{margin-top:4px;color:var(--text-secondary);font-size:.82rem}
        .product-editor-section{display:grid;grid-template-columns:minmax(180px,.68fr) minmax(0,2fr);gap:22px;padding:20px 0;border-top:1px solid var(--border-color)}
        .product-section-intro{display:flex;align-items:flex-start;gap:12px}
        .product-section-number{display:grid;place-items:center;flex:none;width:30px;height:30px;border-radius:9px;background:var(--bg-subtle);border:1px solid var(--border-color);color:var(--text-secondary);font-size:.74rem;font-weight:700}
        .product-section-intro h3{margin:2px 0 4px;font-size:.9rem;color:var(--text-primary)}
        .product-section-intro p{color:var(--text-muted);font-size:.76rem;line-height:1.5}
        .product-fields{display:grid;gap:14px}
        .product-fields--two{grid-template-columns:repeat(2,minmax(0,1fr))}
        .product-fields--three{grid-template-columns:repeat(3,minmax(0,1fr))}
        .product-field-hint{color:var(--text-muted);font-size:.74rem;font-weight:500}
        .product-image-preview-wrap{display:flex;align-items:center;gap:12px;padding:10px 12px;border:1px solid var(--border-color);border-radius:var(--radius-md);background:var(--bg-subtle)}
        .product-image-preview{width:56px;height:64px;object-fit:cover;border-radius:8px;border:1px solid var(--border-color);background:var(--bg-surface);flex-shrink:0}
        .editor-footer{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:14px;padding-top:18px;border-top:1px solid var(--border-color)}
        .editor-footer-hint{color:var(--text-muted);font-size:.78rem}
        .delete-confirm{display:inline-flex;align-items:center;gap:6px}
        .delete-confirm>summary{list-style:none}
        .delete-confirm>summary::-webkit-details-marker{display:none}
        .delete-confirm[open]{padding:3px 4px;border-radius:var(--radius-md);background:var(--status-danger-bg);border:1px solid color-mix(in srgb,var(--status-danger-text) 25%,transparent)}
        .delete-confirm[open]>summary{width:auto;padding:0 8px;font-size:.74rem;font-weight:600;border-color:transparent;color:var(--text-secondary)}
        .delete-confirm-label-open{display:none}
        .delete-confirm[open] .delete-confirm-label-closed{display:none}
        .delete-confirm[open] .delete-confirm-label-open{display:inline}
        .usage-meter{display:grid;gap:5px;min-width:110px}
        .usage-meter-track{height:6px;border-radius:999px;background:var(--bg-subtle);border:1px solid var(--border-color);overflow:hidden}
        .usage-meter-fill{height:100%;border-radius:999px;background:#3b82f6}
        .empty-state{padding:44px 24px;text-align:center;color:var(--text-muted);display:grid;justify-items:center;gap:10px}
        .empty-state p{font-size:.9rem;color:var(--text-secondary)}
        .order-detail-banner{border-color:color-mix(in srgb,var(--border-focus) 30%,var(--border-color))}
        .order-detail-kicker{font-size:.74rem;color:var(--text-muted);margin-top:2px}
        .order-detail-cell-label{display:block;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);margin-bottom:6px}
        .order-detail-note{margin-top:8px;padding:8px 10px;border-radius:var(--radius-sm);background:var(--status-warning-bg);color:var(--status-warning-text);font-size:.78rem;line-height:1.45}
        .admin-alert--warning{background:var(--status-warning-bg);color:var(--status-warning-text);border-color:color-mix(in srgb,var(--status-warning-text) 28%,transparent)}
        .admin-alert--info{background:var(--status-info-bg);color:var(--status-info-text);border-color:color-mix(in srgb,var(--status-info-text) 28%,transparent)}
        .order-workflow-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px;margin-bottom:20px}
        .order-workflow-box{padding:14px 16px;border:1px solid var(--border-color);border-radius:var(--radius-md);background:var(--bg-subtle);display:grid;gap:6px;align-content:start}
        .order-timeline{display:grid;gap:10px;padding-left:16px;border-left:2px solid var(--border-color);margin-top:10px}
        .order-timeline-item{position:relative;display:grid;gap:2px}
        .order-timeline-dot{position:absolute;left:-21px;top:5px;width:8px;height:8px;border-radius:50%;background:#3b82f6}
        .return-thumbs{display:flex;flex-wrap:wrap;gap:10px;margin-top:8px}
        .return-thumbs a{display:block;width:96px;height:96px;border-radius:8px;overflow:hidden;border:1px solid var(--border-color)}
        .return-thumbs img{width:100%;height:100%;object-fit:cover;display:block}
        .notif-strip{display:grid;gap:8px}
        .notif-row{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:10px 14px;border-radius:var(--radius-md);border:1px solid var(--border-color);background:var(--bg-subtle)}
        .notif-row.is-unread{border-color:color-mix(in srgb,var(--status-warning-text) 45%,transparent);background:color-mix(in srgb,var(--status-warning-bg) 45%,var(--bg-surface))}
        .btn-success-sm{padding:6px 12px;font-size:.78rem;border-radius:var(--radius-sm);border:none;background:#10b981;color:#fff;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:6px}
        .btn-danger-sm{padding:6px 12px;font-size:.78rem;border-radius:var(--radius-sm);border:none;background:#ef4444;color:#fff;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:6px}
        @media(max-width:960px){.order-workflow-grid{grid-template-columns:1fr}}
        .settings-submit-bar{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-top:20px;padding:18px 22px;background:var(--bg-surface);border:1px solid var(--border-color);border-radius:var(--radius-lg);box-shadow:var(--shadow-sm)}
        .form-grid-2{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}
        .form-grid-3{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px}
        .form-span-full{grid-column:1 / -1}
        @media(max-width:1120px){
            .admin-filters{grid-template-columns:repeat(2,minmax(0,1fr))}
            .admin-filters--simple{grid-template-columns:repeat(2,minmax(0,1fr))}
        }
        @media(max-width:960px){
            .app-container{flex-direction:column}
            .sidebar{position:static;width:100%;height:auto;transform:none!important;border-right:0;border-bottom:1px solid var(--sidebar-border)}
            .sidebar-header{padding:16px 20px}
            .store-status-pill,.nav-section-title{display:none}
            .sidebar-nav{flex-direction:row;flex-wrap:wrap;padding:10px 14px;gap:6px}
            .nav-item{padding:8px 12px;font-size:.82rem}
            .sidebar-store-btn{margin:0 14px 12px}
            .sidebar-footer{padding:12px 16px}
            .main-wrapper{margin-left:0}
        }
        @media(max-width:720px){
            .product-editor-section{grid-template-columns:1fr;gap:14px}
            .product-fields--three,.form-grid-3{grid-template-columns:repeat(2,minmax(0,1fr))}
            .admin-filters,.admin-filters--simple,.admin-filters--search,.form-grid-2{grid-template-columns:1fr}
        }
        @media(max-width:560px){
            .topbar{height:auto;padding:14px 16px;flex-wrap:wrap}
            .topbar-right{width:100%;justify-content:space-between;flex-wrap:wrap}
            .content-area{padding:16px}
            .product-fields--two,.product-fields--three,.form-grid-3{grid-template-columns:1fr}
        }
        @media print{
            .sidebar,.topbar,.table-toolbar,.panel-disclosure,.admin-alert,.actions-cell,form,button,.btn-primary,.btn-secondary{display:none!important}
            .main-wrapper{margin-left:0!important}
            .card{border:none!important;box-shadow:none!important}
        }
    </style>
</head>
<body>
<div class="app-container">
    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <a class="brand-badge" href="admin.php">
                <div class="brand-symbol">V</div>
                <div class="brand-details">
                    <span class="brand-name">VAST STUDIO</span>
                    <span class="brand-role">Admin Portal</span>
                </div>
            </a>
        </div>

        <div class="store-status-pill">
            <div class="status-dot-container">
                <span class="status-live-dot"></span>
                <span>Cửa hàng: Đang mở</span>
            </div>
            <span class="badge badge-success">Live</span>
        </div>

        <nav class="sidebar-nav" aria-label="Điều hướng quản trị">
            <?php foreach ($navSections as $sectionTitle => $items): ?>
                <div class="nav-section-title"><?= $e($sectionTitle) ?></div>
                <?php foreach ($items as $key => $item): ?>
                    <a class="nav-item <?= $tab === $key ? 'active' : '' ?>" href="admin.php?tab=<?= $e($key) ?>">
                        <div class="nav-item-left">
                            <span class="nav-icon"><?= $icon($item['icon'], 18) ?></span>
                            <span><?= $e($item['label']) ?></span>
                        </div>
                        <?php if ($item['badge'] !== null): ?>
                            <span class="nav-badge"><?= $e($item['badge']) ?></span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </nav>

        <div class="sidebar-footer">
            <div class="admin-profile">
                <div class="admin-avatar"><?= $e($avatarInitials) ?></div>
                <div class="admin-meta">
                    <span class="admin-name"><?= $e($adminName) ?></span>
                    <span class="admin-role-tag"><?= $isLocalAccess ? 'Truy cập cục bộ' : 'Quản trị viên' ?></span>
                </div>
            </div>
        </div>
    </aside>

    <!-- MAIN VIEWPORT -->
    <div class="main-wrapper">
        <header class="topbar">
            <div class="topbar-left">
                <div class="page-title-box">
                    <h1><?= $e($pageTitle) ?></h1>
                    <p>Hôm nay: <?= $e($todayLabel) ?></p>
                </div>
            </div>
            <div class="topbar-right">
                <form method="post" class="admin-inline">
                    <?= $formAction('theme') ?>
                    <input type="hidden" name="tab" value="<?= $e($tab) ?>">
                    <input type="hidden" name="theme" value="<?= $theme === 'dark' ? 'light' : 'dark' ?>">
                    <button class="btn-secondary btn-sm" type="submit" title="Đổi giao diện Sáng / Tối">
                        <?= $icon($theme === 'dark' ? 'sun' : 'moon', 16) ?>
                        <span><?= $theme === 'dark' ? 'Giao diện Sáng' : 'Giao diện Tối' ?></span>
                    </button>
                </form>

                <a class="btn-secondary btn-sm" href="index.php" title="Mở trang cửa hàng">
                    <?= $icon('store', 16) ?>
                    <span>Xem cửa hàng</span>
                </a>

                <?php if (!$isLocalAccess): ?>
                    <form method="post" class="admin-inline">
                        <?= $formAction('admin_logout') ?>
                        <button class="btn-secondary btn-sm" type="submit">
                            <?= $icon('logout', 16) ?>
                            <span>Đăng xuất</span>
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </header>

        <main class="content-area">
            <div class="admin-stack">
                <?php if ($error !== ''): ?>
                    <div class="admin-alert admin-alert--error" role="alert">
                        <span class="admin-alert-icon"><?= $icon('alert', 18) ?></span>
                        <span><?= $e($error) ?></span>
                    </div>
                <?php endif; ?>
                <?php if ($message !== ''): ?>
                    <div class="admin-alert admin-alert--success" role="status">
                        <span class="admin-alert-icon"><?= $icon('checkCircle', 18) ?></span>
                        <span><?= $e($message) ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($tab === 'dashboard'): ?>
                    <?php
                    $unreadAdminNotifs = array_filter($adminNotifications ?? [], static fn (array $n): bool => empty($n['is_read']));
                    if (($stats['pending_cancels'] ?? 0) > 0 || ($stats['pending_returns'] ?? 0) > 0 || ($stats['failed_emails'] ?? 0) > 0 || $unreadAdminNotifs): ?>
                        <section class="card">
                            <div class="card-header">
                                <div class="admin-inline">
                                    <span class="nav-icon"><?= $icon('bell', 18) ?></span>
                                    <div>
                                        <h2 class="card-title">Thông báo cần xử lý</h2>
                                        <p class="card-subtitle">Tổng hợp yêu cầu mới từ khách hàng và trạng thái hệ thống</p>
                                    </div>
                                </div>
                                <div class="admin-actions">
                                    <?php if (($stats['pending_cancels'] ?? 0) > 0): ?>
                                        <a class="badge badge-danger" style="text-decoration:none" href="admin.php?tab=orders&amp;status=cancel_requested"><?= $e($stats['pending_cancels']) ?> yêu cầu hủy</a>
                                    <?php endif; ?>
                                    <?php if (($stats['pending_returns'] ?? 0) > 0): ?>
                                        <a class="badge badge-warning" style="text-decoration:none" href="admin.php?tab=returns"><?= $e($stats['pending_returns']) ?> yêu cầu đổi trả</a>
                                    <?php endif; ?>
                                    <?php if (($stats['failed_emails'] ?? 0) > 0): ?>
                                        <a class="badge badge-danger" style="text-decoration:none" href="admin.php?tab=emails&amp;email_status=failed"><?= $e($stats['failed_emails']) ?> email lỗi</a>
                                    <?php endif; ?>
                                    <?php if ($unreadAdminNotifs): ?>
                                        <form method="post" style="margin:0">
                                            <?= $formAction('admin_notifications_read') ?>
                                            <input type="hidden" name="tab" value="dashboard">
                                            <button class="btn-secondary btn-xs" type="submit">Đánh dấu đã đọc</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php if (!empty($adminNotifications)): ?>
                                <div class="card-body">
                                    <div class="notif-strip">
                                        <?php foreach (array_slice($adminNotifications, 0, 5) as $notif): ?>
                                            <div class="notif-row <?= empty($notif['is_read']) ? 'is-unread' : '' ?>">
                                                <div>
                                                    <div style="font-weight:700;font-size:.84rem;color:var(--text-primary)"><?= $e($notif['title']) ?></div>
                                                    <div style="font-size:.78rem;color:var(--text-secondary)"><?= $e($notif['message']) ?> · <span class="admin-muted"><?= $e($formatDateTime($notif['created_at'])) ?></span></div>
                                                </div>
                                                <?php if (!empty($notif['link'])): ?>
                                                    <a class="btn-secondary btn-xs" href="<?= $e($notif['link']) ?>">Kiểm tra</a>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </section>
                    <?php endif; ?>

                    <!-- KPI CARDS -->
                    <section class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-header">
                                <span class="stat-title">Doanh thu thực tế tháng này</span>
                                <div class="stat-icon-wrap" style="color: var(--status-success-text); background: var(--status-success-bg);">
                                    <?= $icon('dollar', 18) ?>
                                </div>
                            </div>
                            <div class="stat-value"><?= $money($stats['revenue']) ?></div>
                            <div class="stat-footer">
                                <span class="admin-muted">Chỉ tính đơn Khách đã xác nhận Hoàn tất</span>
                            </div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-header">
                                <span class="stat-title">Tổng đơn hàng</span>
                                <div class="stat-icon-wrap" style="color: var(--status-info-text); background: var(--status-info-bg);">
                                    <?= $icon('orders', 18) ?>
                                </div>
                            </div>
                            <div class="stat-value"><?= $e($stats['orders']) ?> đơn</div>
                            <div class="stat-footer">
                                <span class="badge <?= $stats['pending'] > 0 ? 'badge-warning' : 'badge-success' ?>">
                                    <?= $e($stats['pending']) ?> chờ xử lý
                                </span>
                            </div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-header">
                                <span class="stat-title">Sản phẩm trong kho</span>
                                <div class="stat-icon-wrap" style="color: var(--status-warning-text); background: var(--status-warning-bg);">
                                    <?= $icon('products', 18) ?>
                                </div>
                            </div>
                            <div class="stat-value"><?= $e($stats['inventory']) ?> cái</div>
                            <div class="stat-footer">
                                <span class="badge <?= $stats['low_stock'] > 0 ? 'badge-warning' : 'badge-success' ?>">
                                    <?= $e($stats['low_stock']) ?> mặt hàng sắp hết
                                </span>
                            </div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-header">
                                <span class="stat-title">Khách hàng</span>
                                <div class="stat-icon-wrap" style="color: var(--status-purple-text); background: var(--status-purple-bg);">
                                    <?= $icon('customers', 18) ?>
                                </div>
                            </div>
                            <div class="stat-value"><?= $e($stats['customers']) ?> người</div>
                            <div class="stat-footer">
                                <span class="admin-muted">Tài khoản khách hàng</span>
                            </div>
                        </div>
                    </section>

                    <!-- CHART + TOP PRODUCTS -->
                    <div class="dashboard-grid-2" style="margin-bottom:0">
                        <section class="card">
                            <div class="card-header">
                                <div>
                                    <h2 class="card-title">Doanh thu gần đây</h2>
                                    <p class="card-subtitle">Chỉ ghi nhận từ đơn đã Hoàn tất (trừ tiền hoàn) trong <?= $e($revenueRange) ?> ngày gần nhất</p>
                                </div>
                                <div class="chart-controls">
                                    <a class="chart-btn <?= $revenueRange === 7 ? 'active' : '' ?>" style="text-decoration:none" href="admin.php?tab=dashboard&amp;range=7" <?= $revenueRange === 7 ? 'aria-current="page"' : '' ?>>7 ngày</a>
                                    <a class="chart-btn <?= $revenueRange === 30 ? 'active' : '' ?>" style="text-decoration:none" href="admin.php?tab=dashboard&amp;range=30" <?= $revenueRange === 30 ? 'aria-current="page"' : '' ?>>30 ngày</a>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="admin-chart-summary">
                                    <span>Tổng doanh thu <?= $e($revenueRange) ?> ngày:</span>
                                    <strong><?= $money($rangeRevenueTotal) ?></strong>
                                </div>
                                <?php if ($maxRevenue <= 0): ?>
                                    <div class="admin-chart-empty">
                                        Chưa có đơn hàng Hoàn tất trong <?= $e($revenueRange) ?> ngày qua.
                                    </div>
                                <?php endif; ?>
                                <div class="admin-chart-wrap" role="img" aria-label="Biểu đồ doanh thu <?= $e($revenueRange) ?> ngày gần nhất">
                                    <div class="admin-chart <?= $revenueRange === 30 ? 'admin-chart--30' : '' ?>">
                                        <?php foreach ($revenueHistory as $point):
                                            $hasVal = (float)$point['value'] > 0;
                                            $barHeight = $maxRevenue > 0 && $hasVal ? max(4, (int)round($point['value'] / $maxRevenue * 100)) : 0;
                                        ?>
                                            <div class="admin-chart-column" title="<?= $e($point['label']) ?>: <?= $e($money($point['value'])) ?>">
                                                <span class="admin-chart-value"><?= $e($shortMoney($point['value'])) ?></span>
                                                <div class="admin-chart-track">
                                                    <i class="admin-chart-bar <?= $hasVal ? '' : 'admin-chart-bar--empty' ?>" style="height:<?= $hasVal ? $barHeight . '%' : '2px' ?>"></i>
                                                </div>
                                                <small class="admin-chart-label"><?= $e($point['label']) ?></small>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section class="card">
                            <div class="card-header">
                                <div>
                                    <h2 class="card-title">Sản phẩm bán chạy</h2>
                                    <p class="card-subtitle">Các mẫu được đặt nhiều nhất</p>
                                </div>
                                <a class="btn-secondary btn-xs" href="admin.php?tab=products">Xem tất cả</a>
                            </div>
                            <div class="card-body">
                                <?php if (!$topProducts): ?>
                                    <div class="empty-state">
                                        <?= $icon('products', 28) ?>
                                        <p>Chưa có dữ liệu sản phẩm.</p>
                                    </div>
                                <?php else: ?>
                                    <div class="top-items-list">
                                        <?php foreach ($topProducts as $product):
                                            $thumb = $product['image'] ?: ($product['fallback_image'] ?? '');
                                        ?>
                                            <div class="top-item-row">
                                                <div class="top-item-info">
                                                    <?php if ($thumb): ?>
                                                        <img class="top-item-img" src="<?= $e($thumb) ?>" alt="<?= $e($product['name']) ?>">
                                                    <?php else: ?>
                                                        <span class="thumb-placeholder"><?= $icon('image', 18) ?></span>
                                                    <?php endif; ?>
                                                    <div>
                                                        <div class="top-item-name"><?= $e($product['name']) ?></div>
                                                        <div class="top-item-sub"><?= $e($product['category']) ?> · <?= $e($product['collection'] ?: 'Chưa phân loại') ?></div>
                                                    </div>
                                                </div>
                                                <div class="top-item-sales">
                                                    <div class="top-item-count"><?= $e($product['sales_count']) ?> đã bán</div>
                                                    <div class="top-item-revenue"><?= $money($product['price']) ?></div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </section>
                    </div>

                    <!-- RECENT ORDERS -->
                    <section class="card">
                        <div class="card-header">
                            <div>
                                <h2 class="card-title">Đơn hàng gần đây</h2>
                                <p class="card-subtitle">5 đơn hàng mới nhất trong hệ thống</p>
                            </div>
                            <a class="btn-primary btn-sm" href="admin.php?tab=orders">Quản lý đơn hàng</a>
                        </div>
                        <?php if (!$recentOrders): ?>
                            <div class="empty-state">
                                <?= $icon('orders', 28) ?>
                                <p>Chưa có đơn hàng nào.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="vast-table">
                                    <thead>
                                        <tr>
                                            <th>Mã đơn</th>
                                            <th>Khách hàng</th>
                                            <th>Tổng tiền</th>
                                            <th>Thanh toán</th>
                                            <th>Trạng thái</th>
                                            <th style="text-align:right">Thao tác</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentOrders as $order):
                                            $meta = $statusMeta[$order['order_status']] ?? ['label' => $order['order_status'], 'badge' => 'badge-neutral', 'icon' => 'clock'];
                                            $payLabel = $paymentLabels[$order['payment_status']] ?? $order['payment_status'];
                                        ?>
                                            <tr>
                                                <td><a class="mono-id" href="admin.php?tab=orders&amp;order=<?= rawurlencode($order['id']) ?>"><?= $e($order['id']) ?></a></td>
                                                <td>
                                                    <div class="product-meta-title"><?= $e($order['customer_name']) ?></div>
                                                    <div style="font-size:.76rem;color:var(--text-muted)"><?= $e($order['customer_phone'] ?: '—') ?></div>
                                                </td>
                                                <td><span class="price-display"><?= $money($order['total']) ?></span></td>
                                                <td>
                                                    <span class="badge <?= $paymentBadgeClass($order['payment_status']) ?>"><?= $e($payLabel) ?></span>
                                                </td>
                                                <td>
                                                    <span class="badge <?= $meta['badge'] ?>">
                                                        <?= $icon($meta['icon'], 13) ?>
                                                        <span><?= $e($meta['label']) ?></span>
                                                    </span>
                                                </td>
                                                <td style="text-align:right">
                                                    <a class="btn-secondary btn-xs" href="admin.php?tab=orders&amp;order=<?= rawurlencode($order['id']) ?>">
                                                        <?= $icon('eye', 14) ?>
                                                        <span>Chi tiết</span>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </section>

                <?php elseif ($tab === 'products'): ?>
                    <datalist id="vast-category-list">
                        <option value="Áo thun"></option>
                        <option value="Áo sơ mi"></option>
                        <option value="Hoodie &amp; Sweater"></option>
                        <option value="Quần"></option>
                        <option value="Áo khoác"></option>
                        <option value="Phụ kiện"></option>
                    </datalist>

                    <section class="card">
                        <div class="card-body">
                            <form method="get" class="admin-filters">
                                <input type="hidden" name="tab" value="products">
                                <label class="admin-field">
                                    <span>Tìm kiếm</span>
                                    <input name="product_search" value="<?= $e($productFilters['search']) ?>" placeholder="Tên sản phẩm hoặc mã SKU...">
                                </label>
                                <label class="admin-field">
                                    <span>Danh mục</span>
                                    <select name="category">
                                        <?php foreach (['all' => 'Tất cả danh mục', 'Áo thun' => 'Áo thun', 'Áo sơ mi' => 'Áo sơ mi', 'Hoodie & Sweater' => 'Hoodie & Sweater', 'Quần' => 'Quần', 'Áo khoác' => 'Áo khoác', 'Phụ kiện' => 'Phụ kiện'] as $value => $label): ?>
                                            <option value="<?= $e($value) ?>" <?= $productFilters['category'] === $value ? 'selected' : '' ?>><?= $e($label) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                                <label class="admin-field">
                                    <span>Tồn kho</span>
                                    <select name="stock">
                                        <?php foreach (['all' => 'Tất cả', 'in_stock' => 'Còn hàng (> 10)', 'low_stock' => 'Sắp hết (1–10)', 'out_of_stock' => 'Hết hàng'] as $value => $label): ?>
                                            <option value="<?= $e($value) ?>" <?= $productFilters['stock'] === $value ? 'selected' : '' ?>><?= $e($label) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                                <label class="admin-field">
                                    <span>Sắp xếp</span>
                                    <select name="sort">
                                        <?php foreach (['newest' => 'Mới nhất', 'price_asc' => 'Giá tăng dần', 'price_desc' => 'Giá giảm dần', 'stock_desc' => 'Tồn kho nhiều nhất', 'sales_desc' => 'Bán chạy nhất'] as $value => $label): ?>
                                            <option value="<?= $e($value) ?>" <?= $productFilters['sort'] === $value ? 'selected' : '' ?>><?= $e($label) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                                <div class="admin-filter-actions">
                                    <button class="btn-primary" type="submit">
                                        <?= $icon('search', 15) ?>
                                        <span>Lọc</span>
                                    </button>
                                    <?php if ($hasProductFilters): ?>
                                        <a class="btn-secondary" href="admin.php?tab=products" title="Đặt lại bộ lọc">Đặt lại</a>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>
                    </section>

                    <details class="panel-disclosure" <?= $editProduct ? 'open' : '' ?>>
                        <summary>
                            <span class="panel-disclosure-left">
                                <span class="panel-disclosure-mark"><?= $icon($editProduct ? 'edit' : 'plus', 18) ?></span>
                                <span class="panel-disclosure-copy">
                                    <strong><?= $editProduct ? 'Chỉnh sửa sản phẩm' : 'Thêm sản phẩm mới' ?></strong>
                                    <small><?= $editProduct ? 'Cập nhật thông tin và lưu thay đổi cho sản phẩm đang chọn' : 'Mở biểu mẫu để thêm sản phẩm mới vào cửa hàng' ?></small>
                                </span>
                            </span>
                            <span class="panel-disclosure-right">
                                <?php if ($editProduct): ?><span class="badge badge-info">Đang chỉnh sửa</span><?php endif; ?>
                                <span class="panel-disclosure-chevron" aria-hidden="true"></span>
                            </span>
                        </summary>
                        <div class="panel-disclosure-body">
                            <header class="product-editor-heading">
                                <div>
                                    <p class="product-editor-eyebrow"><?= $editProduct ? 'THÔNG TIN SẢN PHẨM' : 'DANH MỤC SẢN PHẨM' ?></p>
                                    <h2><?= $editProduct ? $e($editProduct['name']) : 'Tạo sản phẩm mới' ?></h2>
                                    <p><?= $editProduct ? 'Thay đổi các trường bên dưới rồi nhấn lưu để cập nhật.' : 'Điền đầy đủ thông tin để đăng bán sản phẩm mới.' ?></p>
                                </div>
                                <?php if ($editProduct): ?><span class="mono-id">SKU: <?= $e($editProduct['sku']) ?></span><?php endif; ?>
                            </header>
                            <form method="post">
                                <?= $formAction('product_save') ?>
                                <input type="hidden" name="id" value="<?= $e($editProduct['id'] ?? '') ?>">
                                <section class="product-editor-section">
                                    <div class="product-section-intro">
                                        <span class="product-section-number">01</span>
                                        <div><h3>Thông tin cơ bản</h3><p>Tên gọi, mã SKU, danh mục và bộ sưu tập.</p></div>
                                    </div>
                                    <div class="product-fields product-fields--two">
                                        <label class="admin-field"><span>Tên sản phẩm *</span><input name="name" required value="<?= $e($editProduct['name'] ?? '') ?>" placeholder="Ví dụ: Áo thun VAST Boxy Tee"></label>
                                        <label class="admin-field"><span>Mã SKU *</span><input name="sku" required value="<?= $e($editProduct['sku'] ?? '') ?>" placeholder="VAST-TSH-01"></label>
                                        <label class="admin-field"><span>Danh mục *</span><input name="category" list="vast-category-list" required value="<?= $e($editProduct['category'] ?? '') ?>" placeholder="Chọn hoặc nhập danh mục"></label>
                                        <label class="admin-field"><span>Bộ sưu tập</span>
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
                                        <div><h3>Giá &amp; kho hàng</h3><p>Thiết lập giá bán, giá niêm yết, tồn kho và trạng thái.</p></div>
                                    </div>
                                    <div class="product-fields product-fields--three">
                                        <label class="admin-field"><span>Giá bán (₫) *</span><input type="number" min="0" step="1" name="price" required value="<?= $e($editProduct['price'] ?? '') ?>" placeholder="380000"></label>
                                        <label class="admin-field"><span>Giá gốc (₫)</span><input type="number" min="0" step="1" name="original_price" value="<?= $e($editProduct['original_price'] ?? '') ?>" placeholder="450000"></label>
                                        <label class="admin-field"><span>Số lượng tồn kho *</span><input type="number" min="0" name="stock" required value="<?= $e($editProduct['stock'] ?? '0') ?>" placeholder="0"></label>
                                        <label class="admin-field"><span>Trạng thái</span>
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
                                        <div><h3>Biến thể &amp; hình ảnh</h3><p>Kích cỡ, màu sắc và đường dẫn ảnh sản phẩm.</p></div>
                                    </div>
                                    <div class="product-fields product-fields--two">
                                        <label class="admin-field"><span>Kích cỡ *</span><input name="sizes" required value="<?= $e(implode(', ', $editProduct['sizes'] ?? [])) ?>" placeholder="S, M, L, XL"><small class="product-field-hint">Phân tách từng kích cỡ bằng dấu phẩy.</small></label>
                                        <label class="admin-field"><span>Màu sắc *</span><input name="colors" required value="<?= $e(implode(', ', $editProduct['colors'] ?? [])) ?>" placeholder="Đen, Trắng, Xám"><small class="product-field-hint">Phân tách từng màu sắc bằng dấu phẩy.</small></label>
                                        <label class="admin-field <?= empty($editProduct['image']) ? 'form-span-full' : '' ?>"><span>URL hình ảnh</span>
                                            <input type="url" name="image" value="<?= $e($editProduct['image'] ?? '') ?>" placeholder="https://example.com/image.jpg">
                                            <small class="product-field-hint">Dùng đường dẫn ảnh trực tiếp (HTTPS).</small>
                                        </label>
                                        <?php if (!empty($editProduct['image'])): ?>
                                            <div class="product-image-preview-wrap">
                                                <img class="product-image-preview" src="<?= $e($editProduct['image']) ?>" alt="Ảnh hiện tại của <?= $e($editProduct['name']) ?>">
                                                <div>
                                                    <div style="font-size:.82rem;font-weight:600;color:var(--text-primary)">Ảnh hiện tại</div>
                                                    <div class="product-field-hint">Hiển thị trên storefront và danh sách</div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </section>
                                <section class="product-editor-section">
                                    <div class="product-section-intro">
                                        <span class="product-section-number">04</span>
                                        <div><h3>Mô tả</h3><p>Thông tin chất liệu và đặc điểm nổi bật.</p></div>
                                    </div>
                                    <div class="product-fields">
                                        <label class="admin-field"><span>Mô tả sản phẩm</span><textarea name="description" rows="4" placeholder="Mô tả chất liệu, kiểu dáng và đặc điểm nổi bật..."><?= $e($editProduct['description'] ?? '') ?></textarea></label>
                                    </div>
                                </section>
                                <footer class="editor-footer">
                                    <span class="editor-footer-hint">Các trường có dấu * là bắt buộc.</span>
                                    <div class="admin-actions">
                                        <?php if ($editProduct): ?><a class="btn-secondary" href="admin.php?tab=products">Hủy sửa</a><?php endif; ?>
                                        <button class="btn-primary" type="submit">
                                            <?= $icon('check', 16) ?>
                                            <span><?= $editProduct ? 'Lưu thay đổi' : 'Tạo sản phẩm' ?></span>
                                        </button>
                                    </div>
                                </footer>
                            </form>
                        </div>
                    </details>

                    <section class="card">
                        <div class="card-header">
                            <div>
                                <h2 class="card-title">Danh sách sản phẩm</h2>
                                <p class="card-subtitle">Hiển thị <?= $e(count($products)) ?> sản phẩm<?= $hasProductFilters ? ' theo bộ lọc' : '' ?></p>
                            </div>
                        </div>
                        <?php if (!$products): ?>
                            <div class="empty-state">
                                <?= $icon('products', 30) ?>
                                <p>Không tìm thấy sản phẩm nào phù hợp.</p>
                                <?php if ($hasProductFilters): ?>
                                    <a class="btn-secondary btn-sm" href="admin.php?tab=products">Đặt lại bộ lọc</a>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="vast-table">
                                    <thead>
                                        <tr>
                                            <th>Sản phẩm</th>
                                            <th>Danh mục</th>
                                            <th>Bộ sưu tập</th>
                                            <th>Giá bán</th>
                                            <th>Size / Màu</th>
                                            <th>Tồn kho</th>
                                            <th>Đã bán</th>
                                            <th>Trạng thái</th>
                                            <th style="text-align:right">Thao tác</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($products as $product):
                                            $thumb = $product['image'] ?: ($product['fallback_image'] ?? '');
                                            $stock = (int)$product['stock'];
                                            $stockBadge = $stock === 0
                                                ? ['Hết hàng', 'badge-danger']
                                                : ($stock <= 10 ? [$stock . ' (Sắp hết)', 'badge-warning'] : [$stock . ' cái', 'badge-success']);
                                        ?>
                                            <tr>
                                                <td>
                                                    <div class="product-cell">
                                                        <?php if ($thumb): ?>
                                                            <img class="product-thumb" src="<?= $e($thumb) ?>" alt="<?= $e($product['name']) ?>">
                                                        <?php else: ?>
                                                            <span class="thumb-placeholder"><?= $icon('image', 18) ?></span>
                                                        <?php endif; ?>
                                                        <div>
                                                            <div class="product-meta-title"><?= $e($product['name']) ?></div>
                                                            <div class="product-meta-sku"><?= $e($product['sku']) ?></div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><span class="badge badge-purple"><?= $e($product['category']) ?></span></td>
                                                <td><?= $e($product['collection'] ?: '—') ?></td>
                                                <td>
                                                    <span class="price-display"><?= $money($product['price']) ?></span>
                                                    <?php if ($product['original_price']): ?>
                                                        <br><span class="original-price" style="margin-left:0"><?= $money($product['original_price']) ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div style="font-size:.8rem;color:var(--text-primary)"><strong>Size:</strong> <?= $e(implode(', ', $product['sizes']) ?: '—') ?></div>
                                                    <div style="font-size:.74rem;color:var(--text-muted)"><?= $e(implode(', ', $product['colors']) ?: '—') ?></div>
                                                </td>
                                                <td><span class="badge <?= $stockBadge[1] ?>"><?= $e($stockBadge[0]) ?></span></td>
                                                <td><strong><?= $e($product['sales_count']) ?></strong></td>
                                                <td>
                                                    <span class="badge <?= $product['status'] === 'active' ? 'badge-success' : 'badge-neutral' ?>">
                                                        <?= $product['status'] === 'active' ? 'Đang bán' : 'Bản nháp' ?>
                                                    </span>
                                                </td>
                                                <td style="text-align:right">
                                                    <div class="actions-cell" style="justify-content:flex-end">
                                                        <a class="btn-icon-sm" title="Chỉnh sửa sản phẩm" href="admin.php?tab=products&amp;edit_product=<?= rawurlencode($product['id']) ?>">
                                                            <?= $icon('edit', 15) ?>
                                                        </a>
                                                        <details class="delete-confirm">
                                                            <summary class="btn-icon-sm delete-btn" title="Xóa sản phẩm">
                                                                <span class="delete-confirm-label-closed" style="display:inline-flex"><?= $icon('trash', 15) ?></span>
                                                                <span class="delete-confirm-label-open">Hủy</span>
                                                            </summary>
                                                            <form method="post" class="admin-inline">
                                                                <?= $formAction('product_delete') ?>
                                                                <input type="hidden" name="id" value="<?= $e($product['id']) ?>">
                                                                <button class="btn-danger btn-xs" type="submit">Xác nhận xóa</button>
                                                            </form>
                                                        </details>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </section>

                <?php elseif ($tab === 'orders'): ?>
                    <?php
                    $orderFilterTabs = ['all' => 'Tất cả'] + $statusLabels + ['returns_group' => 'Nhóm đổi trả'];
                    ?>
                    <section class="card">
                        <div class="card-body" style="display:grid;gap:16px">
                            <nav class="filter-pills" aria-label="Lọc trạng thái đơn hàng">
                                <?php foreach ($orderFilterTabs as $key => $label): ?>
                                    <a class="filter-pill <?= $orderFilters['status'] === $key ? 'active' : '' ?>" href="admin.php?tab=orders&amp;status=<?= $e($key) ?>">
                                        <span><?= $e($label) ?></span>
                                        <span class="badge badge-neutral" style="padding:1px 7px;font-size:.7rem"><?= $e($orderCounts[$key] ?? 0) ?></span>
                                    </a>
                                <?php endforeach; ?>
                            </nav>

                            <form method="get" class="admin-filters admin-filters--search">
                                <input type="hidden" name="tab" value="orders">
                                <input type="hidden" name="status" value="<?= $e($orderFilters['status']) ?>">
                                <label class="admin-field">
                                    <span>Tìm kiếm đơn hàng</span>
                                    <input name="order_search" value="<?= $e($orderFilters['search']) ?>" placeholder="Nhập mã đơn (VAST-...), tên khách hàng hoặc SĐT...">
                                </label>
                                <div class="admin-filter-actions">
                                    <button class="btn-primary" type="submit">
                                        <?= $icon('search', 15) ?>
                                        <span>Tìm kiếm</span>
                                    </button>
                                    <?php if ($hasOrderFilters): ?>
                                        <a class="btn-secondary" href="admin.php?tab=orders">Đặt lại</a>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>
                    </section>

                    <?php if ($selectedOrder):
                        $selStatus = (string)$selectedOrder['order_status'];
                        $selMeta = $statusMeta[$selStatus] ?? ['label' => $selStatus, 'badge' => 'badge-neutral', 'icon' => 'clock'];
                        $selPayLabel = $paymentLabels[$selectedOrder['payment_status']] ?? $selectedOrder['payment_status'];
                        $nextStep = OrderWorkflow::nextAdminFulfillmentStep($selStatus);
                        $canAdminCancel = OrderWorkflow::canAdminDirectCancel($selStatus);
                        $latestCancelReq = $selectedOrder['cancel_requests'][0] ?? null;
                    ?>
                        <section class="card order-detail-banner">
                            <div class="card-header">
                                <div>
                                    <div class="admin-inline">
                                        <h2 class="card-title">Chi tiết đơn hàng #<?= $e($selectedOrder['id']) ?></h2>
                                        <span class="badge <?= $selMeta['badge'] ?>">
                                            <?= $icon($selMeta['icon'], 13) ?>
                                            <span><?= $e($selMeta['label']) ?></span>
                                        </span>
                                    </div>
                                    <p class="order-detail-kicker">
                                        Đặt lúc: <?= $e($formatDateTime($selectedOrder['created_at'])) ?>
                                        <?php if (!empty($selectedOrder['confirmed_at'])): ?> · Xác nhận: <?= $e($formatDateTime($selectedOrder['confirmed_at'])) ?><?php endif; ?>
                                        <?php if (!empty($selectedOrder['delivered_at'])): ?> · Giao thành công: <?= $e($formatDateTime($selectedOrder['delivered_at'])) ?><?php endif; ?>
                                        <?php if (!empty($selectedOrder['customer_confirmed_at'])): ?> · Khách xác nhận Hoàn tất: <?= $e($formatDateTime($selectedOrder['customer_confirmed_at'])) ?><?php endif; ?>
                                    </p>
                                </div>
                                <a class="btn-secondary btn-sm" href="admin.php?tab=orders&amp;status=<?= $e($orderFilters['status']) ?>">
                                    <?= $icon('x', 15) ?>
                                    <span>Đóng chi tiết</span>
                                </a>
                            </div>
                            <div class="card-body">
                                <div class="order-workflow-grid">
                                    <div class="order-workflow-box">
                                        <span class="order-detail-cell-label">Thông tin đơn hàng &amp; thanh toán</span>
                                        <div style="font-size:.83rem"><strong>Mã đơn:</strong> <span class="mono-id"><?= $e($selectedOrder['id']) ?></span></div>
                                        <div style="font-size:.83rem"><strong>Phương thức:</strong> <?= $e($selectedOrder['payment_method'] ?: '—') ?></div>
                                        <div style="font-size:.83rem"><strong>TT thanh toán:</strong> <span class="badge <?= $paymentBadgeClass($selectedOrder['payment_status']) ?>"><?= $e($selPayLabel) ?></span></div>
                                        <div style="font-size:.83rem"><strong>Vận chuyển:</strong> <?= $e($selectedOrder['shipping_method'] ?: '—') ?></div>
                                        <div style="font-size:.83rem"><strong>Mã giảm giá:</strong> <?= $e($selectedOrder['coupon_code'] ?: 'Không dùng') ?></div>
                                        <?php if ((float)($selectedOrder['refunded_amount'] ?? 0) > 0): ?>
                                            <div style="font-size:.83rem;color:var(--status-danger-text)"><strong>Đã hoàn tiền:</strong> <?= $money($selectedOrder['refunded_amount']) ?></div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="order-workflow-box">
                                        <span class="order-detail-cell-label">Thông tin khách hàng</span>
                                        <strong style="font-size:.95rem;color:var(--text-primary)"><?= $e($selectedOrder['customer_name']) ?></strong>
                                        <div style="font-size:.82rem;color:var(--text-secondary)">SĐT: <?= $e($selectedOrder['customer_phone'] ?: '—') ?></div>
                                        <div style="font-size:.8rem;color:var(--text-muted)">Email: <?= $e($selectedOrder['customer_email'] ?: '—') ?></div>
                                        <div style="font-size:.82rem;color:var(--text-primary);line-height:1.45;margin-top:4px"><strong>Địa chỉ:</strong> <?= $e($selectedOrder['shipping_address'] ?: '—') ?></div>
                                        <?php if (!empty($selectedOrder['note'])): ?>
                                            <div class="order-detail-note"><strong>Ghi chú khách hàng:</strong> <?= $e($selectedOrder['note']) ?></div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="order-workflow-box">
                                        <span class="order-detail-cell-label">Điều khiển trạng thái (Từng bước)</span>
                                        <?php if ($nextStep !== null): ?>
                                            <form method="post" style="display:grid;gap:8px">
                                                <?= $formAction('order_advance') ?>
                                                <input type="hidden" name="id" value="<?= $e($selectedOrder['id']) ?>">
                                                <input type="hidden" name="target_status" value="<?= $e($nextStep) ?>">
                                                <label class="admin-field">
                                                    <span>Ghi chú cập nhật (tùy chọn)</span>
                                                    <input name="note" placeholder="VD: Đã bàn giao GHTK mã vận đơn...">
                                                </label>
                                                <button class="btn-primary btn-sm" type="submit">
                                                    <?= $icon('arrowRight', 15) ?>
                                                    <span>Chuyển sang: <?= $e(OrderWorkflow::statusLabel($nextStep)) ?></span>
                                                </button>
                                            </form>
                                        <?php elseif (in_array($selStatus, ['delivered', 'awaiting_confirmation'], true)): ?>
                                            <div class="admin-alert admin-alert--info" style="padding:10px 12px;font-size:.78rem">
                                                Admin chỉ cập nhật tối đa đến <strong>Giao hàng thành công</strong> / <strong>Chờ xác nhận nhận hàng</strong>. Khách hàng phải tự xác nhận đã nhận hàng hoặc yêu cầu đổi trả.
                                            </div>
                                        <?php else: ?>
                                            <div class="admin-muted" style="font-size:.8rem">Không có bước chuyển tiếp trực tiếp ở trạng thái hiện tại.</div>
                                        <?php endif; ?>

                                        <?php if ($canAdminCancel): ?>
                                            <details style="margin-top:6px">
                                                <summary style="cursor:pointer;font-size:.8rem;color:var(--status-danger-text);font-weight:600">Hủy đơn hàng (Admin)</summary>
                                                <form method="post" style="display:grid;gap:8px;margin-top:8px" onsubmit="return confirm('Xác nhận hủy đơn #<?= $e($selectedOrder['id']) ?>?')">
                                                    <?= $formAction('order_cancel') ?>
                                                    <input type="hidden" name="id" value="<?= $e($selectedOrder['id']) ?>">
                                                    <label class="admin-field">
                                                        <span>Lý do hủy bắt buộc *</span>
                                                        <textarea name="reason" rows="2" required minlength="5" placeholder="Nhập lý do hủy đơn..."></textarea>
                                                    </label>
                                                    <button class="btn-danger-sm" type="submit">Xác nhận hủy đơn</button>
                                                </form>
                                            </details>
                                        <?php endif; ?>

                                        <?php if (!empty($selectedOrder['cancel_reason']) && $selStatus === 'cancelled'): ?>
                                            <div class="order-detail-note" style="background:var(--status-danger-bg);color:var(--status-danger-text)">
                                                <strong>Đã hủy:</strong> <?= $e($selectedOrder['cancel_reason']) ?><br>
                                                <small>Người hủy: <?= $e($selectedOrder['cancelled_by'] ?: '—') ?> · <?= $e($formatDateTime($selectedOrder['cancelled_at'])) ?></small>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <?php if ($selStatus === 'cancel_requested'): ?>
                                    <div class="admin-alert admin-alert--warning" style="display:grid;gap:12px;margin-bottom:20px;align-items:stretch">
                                        <div>
                                            <strong>Khách hàng đã gửi Yêu cầu hủy đơn hàng đã xác nhận!</strong>
                                            <?php if ($latestCancelReq): ?>
                                                <div style="margin-top:4px;font-size:.84rem">
                                                    <strong>Lý do của khách:</strong> <?= $e($latestCancelReq['reason']) ?>
                                                    <span class="admin-muted">(lúc <?= $e($formatDateTime($latestCancelReq['created_at'])) ?>)</span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="form-grid-2">
                                            <form method="post" style="display:grid;gap:8px;background:var(--bg-surface);padding:12px;border-radius:8px;border:1px solid var(--border-color)" onsubmit="return confirm('Đồng ý hủy đơn #<?= $e($selectedOrder['id']) ?>, hoàn tồn kho và hoàn tiền nếu đã thanh toán?')">
                                                <?= $formAction('cancel_request_resolve') ?>
                                                <input type="hidden" name="id" value="<?= $e($selectedOrder['id']) ?>">
                                                <input type="hidden" name="decision" value="approve">
                                                <label class="admin-field">
                                                    <span>Đồng ý hủy — Ghi chú phản hồi</span>
                                                    <input name="note" placeholder="Chấp thuận hủy đơn và hoàn kho...">
                                                </label>
                                                <button class="btn-success-sm" type="submit"><?= $icon('check', 14) ?> Đồng ý hủy đơn</button>
                                            </form>

                                            <form method="post" style="display:grid;gap:8px;background:var(--bg-surface);padding:12px;border-radius:8px;border:1px solid var(--border-color)">
                                                <?= $formAction('cancel_request_resolve') ?>
                                                <input type="hidden" name="id" value="<?= $e($selectedOrder['id']) ?>">
                                                <input type="hidden" name="decision" value="reject">
                                                <label class="admin-field">
                                                    <span>Từ chối hủy — Lý do từ chối bắt buộc *</span>
                                                    <input name="note" required minlength="5" placeholder="VD: Đơn hàng đã đóng gói xong chuẩn bị bàn giao vận chuyển...">
                                                </label>
                                                <button class="btn-danger-sm" type="submit"><?= $icon('xCircle', 14) ?> Từ chối hủy &amp; khôi phục đơn</button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <h3 style="font-size:.92rem;font-weight:700;margin-bottom:12px;font-family:var(--font-heading)">
                                    Sản phẩm trong đơn (<?= $e(count($selectedOrder['items'])) ?> — lưu giữ theo thời điểm đặt)
                                </h3>
                                <div class="table-responsive" style="margin-bottom:16px">
                                    <table class="vast-table">
                                        <thead>
                                            <tr>
                                                <th>Sản phẩm</th>
                                                <th>SKU</th>
                                                <th>Biến thể</th>
                                                <th>SL</th>
                                                <th>Đơn giá</th>
                                                <th style="text-align:right">Thành tiền</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($selectedOrder['items'] as $item): ?>
                                                <tr>
                                                    <td>
                                                        <div style="display:flex;align-items:center;gap:10px">
                                                            <?php if (!empty($item['image'])): ?>
                                                                <img src="<?= $e($item['image']) ?>" alt="<?= $e($item['name']) ?>" style="width:42px;height:42px;border-radius:6px;object-fit:cover;border:1px solid var(--border-color)">
                                                            <?php else: ?>
                                                                <span class="thumb-placeholder" style="width:42px;height:42px"><?= $icon('package', 16) ?></span>
                                                            <?php endif; ?>
                                                            <strong style="font-size:.84rem"><?= $e($item['name']) ?></strong>
                                                        </div>
                                                    </td>
                                                    <td><span class="mono-id"><?= $e($item['sku'] ?: '—') ?></span></td>
                                                    <td>Size <?= $e($item['size'] ?: '—') ?> · Màu <?= $e($item['color'] ?: '—') ?></td>
                                                    <td><strong>×<?= $e($item['quantity']) ?></strong></td>
                                                    <td><?= $money($item['price']) ?></td>
                                                    <td style="text-align:right"><strong class="price-display"><?= $money($item['total_price'] ?? ($item['price'] * $item['quantity'])) ?></strong></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="order-summary-box" style="margin-bottom:20px">
                                    <div class="summary-line">
                                        <span>Tổng tiền hàng (Tạm tính)</span>
                                        <span><?= $money($selectedOrder['subtotal']) ?></span>
                                    </div>
                                    <div class="summary-line">
                                        <span>Phí vận chuyển</span>
                                        <span><?= (float)$selectedOrder['shipping_fee'] == 0.0 ? '<strong style="color:var(--status-success-text)">Miễn phí</strong>' : $money($selectedOrder['shipping_fee']) ?></span>
                                    </div>
                                    <?php if ((float)$selectedOrder['discount'] > 0): ?>
                                        <div class="summary-line" style="color:var(--status-danger-text)">
                                            <span>Giảm giá<?= !empty($selectedOrder['coupon_code']) ? ' (' . $e($selectedOrder['coupon_code']) . ')' : '' ?></span>
                                            <span>-<?= $money($selectedOrder['discount']) ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <div class="summary-line summary-total">
                                        <span>Tổng thanh toán</span>
                                        <span><?= $money($selectedOrder['total']) ?></span>
                                    </div>
                                </div>

                                <div class="form-grid-2">
                                    <div>
                                        <h3 style="font-size:.9rem;font-weight:700;margin-bottom:8px;font-family:var(--font-heading)">Lịch sử xử lý đơn hàng</h3>
                                        <div class="order-timeline">
                                            <?php foreach ($selectedOrder['history'] ?? [] as $h): ?>
                                                <div class="order-timeline-item">
                                                    <span class="order-timeline-dot"></span>
                                                    <div style="font-size:.82rem;font-weight:700;color:var(--text-primary)">
                                                        <?= $e(OrderWorkflow::statusLabel((string)$h['status'])) ?>
                                                        <span class="admin-muted" style="font-weight:500;margin-left:6px"><?= $e($formatDateTime($h['created_at'])) ?></span>
                                                    </div>
                                                    <div style="font-size:.78rem;color:var(--text-secondary)">
                                                        <?= $e($h['note'] ?: 'Cập nhật trạng thái') ?> · <em>Người thực hiện: <?= $e($h['resolved_actor'] ?? 'Hệ thống') ?></em>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>

                                    <div>
                                        <h3 style="font-size:.9rem;font-weight:700;margin-bottom:8px;font-family:var(--font-heading)">Nhật ký Email &amp; Đổi trả liên quan</h3>
                                        <?php if (!empty($selectedOrder['return_requests'])): ?>
                                            <div style="display:grid;gap:8px;margin-bottom:12px">
                                                <?php foreach ($selectedOrder['return_requests'] as $ret): ?>
                                                    <div class="notif-row">
                                                        <div>
                                                            <strong class="mono-id"><?= $e($ret['return_code']) ?></strong>
                                                            <span class="badge badge-warning" style="margin-left:6px"><?= $e(OrderWorkflow::returnStatusLabel((string)$ret['status'])) ?></span>
                                                            <div style="font-size:.76rem;color:var(--text-secondary);margin-top:4px"><?= $e($ret['reason_text']) ?></div>
                                                        </div>
                                                        <a class="btn-secondary btn-xs" href="admin.php?tab=returns&amp;return=<?= rawurlencode($ret['return_code']) ?>">Xử lý đổi trả</a>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>

                                        <?php if (empty($selectedOrder['email_logs'])): ?>
                                            <p class="admin-muted" style="font-size:.8rem">Chưa có bản ghi email nào cho đơn hàng này.</p>
                                        <?php else: ?>
                                            <div style="display:grid;gap:8px">
                                                <?php foreach ($selectedOrder['email_logs'] as $elog): ?>
                                                    <div class="notif-row">
                                                        <div style="min-width:0">
                                                            <div style="font-size:.78rem;font-weight:600;color:var(--text-primary);word-break:break-word"><?= $e($elog['subject']) ?></div>
                                                            <div style="font-size:.73rem;color:var(--text-muted)">
                                                                Tới: <?= $e($elog['recipient_email']) ?> ·
                                                                <span class="badge <?= $elog['status'] === 'sent' ? 'badge-success' : 'badge-danger' ?>"><?= $e($elog['status']) ?></span>
                                                            </div>
                                                            <?php if (!empty($elog['error_message'])): ?>
                                                                <div style="font-size:.72rem;color:var(--status-danger-text);margin-top:2px"><?= $e($elog['error_message']) ?></div>
                                                            <?php endif; ?>
                                                        </div>
                                                        <?php if ($elog['status'] !== 'sent'): ?>
                                                            <form method="post" style="margin:0">
                                                                <?= $formAction('email_retry') ?>
                                                                <input type="hidden" name="email_log_id" value="<?= $e($elog['id']) ?>">
                                                                <input type="hidden" name="return_tab" value="orders">
                                                                <input type="hidden" name="return_order" value="<?= $e($selectedOrder['id']) ?>">
                                                                <button class="btn-secondary btn-xs" type="submit">Gửi lại</button>
                                                            </form>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </section>
                    <?php elseif (isset($_GET['order'])): ?>
                        <div class="admin-alert admin-alert--error" role="alert">
                            <span class="admin-alert-icon"><?= $icon('alert', 18) ?></span>
                            <span>Không tìm thấy đơn hàng này.</span>
                        </div>
                    <?php endif; ?>

                    <section class="card">
                        <div class="card-header">
                            <div>
                                <h2 class="card-title">Danh sách đơn hàng</h2>
                                <p class="card-subtitle">Hiển thị <?= $e(count($orders)) ?> đơn hàng · Tuân thủ chuyển trạng thái từng bước</p>
                            </div>
                        </div>
                        <?php if (!$orders): ?>
                            <div class="empty-state">
                                <?= $icon('orders', 30) ?>
                                <p>Không có đơn hàng nào phù hợp.</p>
                                <?php if ($hasOrderFilters): ?>
                                    <a class="btn-secondary btn-sm" href="admin.php?tab=orders">Xem tất cả đơn hàng</a>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="vast-table">
                                    <thead>
                                        <tr>
                                            <th>Mã đơn &amp; Thời gian</th>
                                            <th>Khách hàng</th>
                                            <th>Tổng tiền</th>
                                            <th>Thanh toán</th>
                                            <th>Trạng thái &amp; Bước tiếp</th>
                                            <th style="text-align:right">Chi tiết</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($orders as $order):
                                            $payLabel = $paymentLabels[$order['payment_status']] ?? $order['payment_status'];
                                            $rowMeta = $statusMeta[$order['order_status']] ?? ['label' => $order['order_status'], 'badge' => 'badge-neutral', 'icon' => 'clock'];
                                            $rowNext = OrderWorkflow::nextAdminFulfillmentStep((string)$order['order_status']);
                                            $isSelected = $selectedOrder && $selectedOrder['id'] === $order['id'];
                                        ?>
                                            <tr <?= $isSelected ? 'style="background:var(--bg-surface-hover)"' : '' ?>>
                                                <td>
                                                    <a class="mono-id" href="admin.php?tab=orders&amp;status=<?= $e($orderFilters['status']) ?>&amp;order=<?= rawurlencode($order['id']) ?>"><?= $e($order['id']) ?></a>
                                                    <div style="font-size:.75rem;color:var(--text-muted);margin-top:4px"><?= $e($formatDateTime($order['created_at'])) ?></div>
                                                </td>
                                                <td>
                                                    <div class="product-meta-title"><?= $e($order['customer_name']) ?></div>
                                                    <div style="font-size:.76rem;color:var(--text-muted)"><?= $e($order['customer_phone'] ?: '—') ?></div>
                                                </td>
                                                <td><span class="price-display"><?= $money($order['total']) ?></span></td>
                                                <td>
                                                    <div style="font-size:.8rem;color:var(--text-primary);margin-bottom:4px"><?= $e($order['payment_method'] ?? '—') ?></div>
                                                    <span class="badge <?= $paymentBadgeClass($order['payment_status']) ?>"><?= $e($payLabel) ?></span>
                                                </td>
                                                <td>
                                                    <div class="admin-actions">
                                                        <span class="badge <?= $rowMeta['badge'] ?>">
                                                            <?= $icon($rowMeta['icon'], 13) ?>
                                                            <span><?= $e($rowMeta['label']) ?></span>
                                                        </span>
                                                        <?php if ($rowNext !== null): ?>
                                                            <form method="post" style="margin:0">
                                                                <?= $formAction('order_advance') ?>
                                                                <input type="hidden" name="id" value="<?= $e($order['id']) ?>">
                                                                <input type="hidden" name="target_status" value="<?= $e($rowNext) ?>">
                                                                <button class="btn-primary btn-xs" type="submit" title="Chuyển từng bước">→ <?= $e(OrderWorkflow::statusLabel($rowNext)) ?></button>
                                                            </form>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td style="text-align:right">
                                                    <a class="btn-secondary btn-xs" href="admin.php?tab=orders&amp;status=<?= $e($orderFilters['status']) ?>&amp;order=<?= rawurlencode($order['id']) ?>">
                                                        <?= $icon('eye', 14) ?>
                                                        <span>Chi tiết</span>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </section>

                <?php elseif ($tab === 'collections'): ?>
                    <details class="panel-disclosure">
                        <summary>
                            <span class="panel-disclosure-left">
                                <span class="panel-disclosure-mark"><?= $icon('plus', 18) ?></span>
                                <span class="panel-disclosure-copy">
                                    <strong>Tạo bộ sưu tập mới</strong>
                                    <small>Thêm chiến dịch hoặc lookbook mới cho VAST STUDIO</small>
                                </span>
                            </span>
                            <span class="panel-disclosure-right">
                                <span class="panel-disclosure-chevron" aria-hidden="true"></span>
                            </span>
                        </summary>
                        <div class="panel-disclosure-body">
                            <form method="post">
                                <?= $formAction('collection_save') ?>
                                <div class="form-grid-2">
                                    <label class="admin-field">
                                        <span>Tên bộ sưu tập *</span>
                                        <input name="name" required placeholder="VD: Summer Streetwear Drop 2026">
                                    </label>
                                    <label class="admin-field">
                                        <span>URL ảnh banner</span>
                                        <input type="url" name="banner" placeholder="https://images.unsplash.com/...">
                                    </label>
                                    <label class="admin-field form-span-full">
                                        <span>Mô tả bộ sưu tập</span>
                                        <textarea name="description" rows="3" placeholder="Ý tưởng thiết kế, chất liệu chủ đạo..."></textarea>
                                    </label>
                                </div>
                                <div class="editor-footer" style="margin-top:16px">
                                    <span class="editor-footer-hint">Bộ sưu tập mới sẽ ở trạng thái đang hoạt động.</span>
                                    <button class="btn-primary" type="submit">
                                        <?= $icon('check', 16) ?>
                                        <span>Tạo bộ sưu tập</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </details>

                    <?php if (!$collections): ?>
                        <section class="card">
                            <div class="empty-state">
                                <?= $icon('collections', 30) ?>
                                <p>Chưa có bộ sưu tập nào.</p>
                            </div>
                        </section>
                    <?php else: ?>
                        <section class="collections-grid">
                            <?php foreach ($collections as $collection): ?>
                                <article class="collection-card">
                                    <?php if (!empty($collection['banner'])): ?>
                                        <img class="collection-banner" src="<?= $e($collection['banner']) ?>" alt="<?= $e($collection['name']) ?>">
                                    <?php else: ?>
                                        <div class="collection-banner-placeholder">
                                            <span>VAST STUDIO COLLECTION</span>
                                            <strong><?= $e($collection['name']) ?></strong>
                                        </div>
                                    <?php endif; ?>
                                    <div class="collection-body">
                                        <div>
                                            <h2 class="collection-name"><?= $e($collection['name']) ?></h2>
                                            <p class="collection-desc"><?= $e($collection['description'] ?: 'Bộ sưu tập thời trang thiết kế bởi VAST STUDIO.') ?></p>
                                        </div>
                                        <div class="collection-meta">
                                            <span><strong><?= $e($collection['products_count']) ?></strong> sản phẩm</span>
                                            <span class="badge <?= ($collection['status'] ?? 'active') === 'active' ? 'badge-success' : 'badge-neutral' ?>">
                                                <?= ($collection['status'] ?? 'active') === 'active' ? 'Đang mở bán' : $e($collection['status']) ?>
                                            </span>
                                        </div>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </section>
                    <?php endif; ?>

                <?php elseif ($tab === 'customers'): ?>
                    <section class="card">
                        <div class="card-body">
                            <form method="get" class="admin-filters admin-filters--simple">
                                <input type="hidden" name="tab" value="customers">
                                <label class="admin-field">
                                    <span>Tìm kiếm khách hàng</span>
                                    <input name="customer_search" value="<?= $e($customerFilters['search']) ?>" placeholder="Tên, email hoặc số điện thoại...">
                                </label>
                                <label class="admin-field">
                                    <span>Hạng thành viên</span>
                                    <select name="tier">
                                        <option value="all">Tất cả hạng</option>
                                        <?php foreach (['VIP Black Card', 'Gold Member', 'Silver Member', 'Standard'] as $tier): ?>
                                            <option value="<?= $e($tier) ?>" <?= $customerFilters['tier'] === $tier ? 'selected' : '' ?>><?= $e($tier) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                                <div class="admin-filter-actions">
                                    <button class="btn-primary" type="submit">
                                        <?= $icon('search', 15) ?>
                                        <span>Lọc</span>
                                    </button>
                                    <?php if ($hasCustomerFilters): ?>
                                        <a class="btn-secondary" href="admin.php?tab=customers">Đặt lại</a>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>
                    </section>

                    <section class="card">
                        <div class="card-header">
                            <div>
                                <h2 class="card-title">Danh sách khách hàng</h2>
                                <p class="card-subtitle">Hiển thị <?= $e(count($customers)) ?> khách hàng</p>
                            </div>
                        </div>
                        <?php if (!$customers): ?>
                            <div class="empty-state">
                                <?= $icon('customers', 30) ?>
                                <p>Không tìm thấy khách hàng nào.</p>
                                <?php if ($hasCustomerFilters): ?>
                                    <a class="btn-secondary btn-sm" href="admin.php?tab=customers">Đặt lại bộ lọc</a>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="vast-table">
                                    <thead>
                                        <tr>
                                            <th>Mã KH</th>
                                            <th>Khách hàng</th>
                                            <th>Liên hệ</th>
                                            <th>Khu vực</th>
                                            <th>Hạng thành viên</th>
                                            <th>Số đơn</th>
                                            <th>Tổng chi tiêu</th>
                                            <th>Mua gần nhất</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($customers as $customer): ?>
                                            <tr>
                                                <td><span class="mono-id"><?= $e($customer['id']) ?></span></td>
                                                <td><div class="product-meta-title"><?= $e($customer['name']) ?></div></td>
                                                <td>
                                                    <div style="color:var(--text-primary);font-size:.82rem"><?= $e($customer['phone'] ?: '—') ?></div>
                                                    <div style="color:var(--text-muted);font-size:.75rem"><?= $e($customer['email'] ?: '—') ?></div>
                                                </td>
                                                <td><?= $e($customer['city'] ?: '—') ?></td>
                                                <td><span class="badge <?= $tierBadgeClass($customer['tier']) ?>"><?= $e($customer['tier']) ?></span></td>
                                                <td><strong><?= $e($customer['orders_count']) ?></strong></td>
                                                <td><span class="price-display"><?= $money($customer['total_spent']) ?></span></td>
                                                <td><span class="admin-muted" style="font-size:.78rem"><?= $e($formatDateTime($customer['last_order'] ?: '')) ?></span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </section>

                <?php elseif ($tab === 'coupons'): ?>
                    <details class="panel-disclosure">
                        <summary>
                            <span class="panel-disclosure-left">
                                <span class="panel-disclosure-mark"><?= $icon('plus', 18) ?></span>
                                <span class="panel-disclosure-copy">
                                    <strong>Tạo mã khuyến mãi mới</strong>
                                    <small>Thiết lập mã giảm giá theo số tiền hoặc phần trăm</small>
                                </span>
                            </span>
                            <span class="panel-disclosure-right">
                                <span class="panel-disclosure-chevron" aria-hidden="true"></span>
                            </span>
                        </summary>
                        <div class="panel-disclosure-body">
                            <form method="post">
                                <?= $formAction('coupon_save') ?>
                                <div class="form-grid-3">
                                    <label class="admin-field">
                                        <span>Mã voucher *</span>
                                        <input name="code" required placeholder="VD: AUTUMN100K">
                                    </label>
                                    <label class="admin-field" style="grid-column:span 2">
                                        <span>Mô tả ưu đãi *</span>
                                        <input name="description" required placeholder="VD: Giảm 100.000₫ cho đơn hàng từ 1.000.000₫">
                                    </label>
                                    <label class="admin-field">
                                        <span>Loại giảm giá</span>
                                        <select name="type">
                                            <option value="fixed">Số tiền cố định (₫)</option>
                                            <option value="percent">Phần trăm (%)</option>
                                        </select>
                                    </label>
                                    <label class="admin-field">
                                        <span>Giá trị giảm *</span>
                                        <input type="number" min="0" step="1" name="value" required placeholder="100000">
                                    </label>
                                    <label class="admin-field">
                                        <span>Đơn tối thiểu (₫) *</span>
                                        <input type="number" min="0" step="1" name="min_order" required placeholder="500000">
                                    </label>
                                    <label class="admin-field">
                                        <span>Giới hạn lượt dùng *</span>
                                        <input type="number" min="1" name="usage_limit" required placeholder="200">
                                    </label>
                                    <label class="admin-field">
                                        <span>Ngày hết hạn *</span>
                                        <input type="date" name="expiry_date" required>
                                    </label>
                                </div>
                                <div class="editor-footer" style="margin-top:16px">
                                    <span class="editor-footer-hint">Nếu chọn giảm theo phần trăm, nhập giá trị từ 1 đến 100.</span>
                                    <button class="btn-primary" type="submit">
                                        <?= $icon('check', 16) ?>
                                        <span>Tạo voucher</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </details>

                    <section class="card">
                        <div class="card-header">
                            <div>
                                <h2 class="card-title">Danh sách mã khuyến mãi</h2>
                                <p class="card-subtitle">Tổng cộng <?= $e(count($coupons)) ?> mã ưu đãi</p>
                            </div>
                        </div>
                        <?php if (!$coupons): ?>
                            <div class="empty-state">
                                <?= $icon('coupons', 30) ?>
                                <p>Chưa có mã khuyến mãi nào.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="vast-table">
                                    <thead>
                                        <tr>
                                            <th>Mã voucher</th>
                                            <th>Mô tả ưu đãi</th>
                                            <th>Mức giảm</th>
                                            <th>Đơn tối thiểu</th>
                                            <th>Lượt sử dụng</th>
                                            <th>Hạn dùng</th>
                                            <th style="text-align:right">Thao tác</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($coupons as $coupon):
                                            $used = (int)($coupon['used_count'] ?? 0);
                                            $limit = (int)($coupon['usage_limit'] ?? 0);
                                            $usagePct = $limit > 0 ? min(100, (int)round($used / $limit * 100)) : 0;
                                            $isExpired = !empty($coupon['expiry_date']) && $coupon['expiry_date'] < date('Y-m-d');
                                        ?>
                                            <tr>
                                                <td><span class="coupon-code-chip"><?= $e($coupon['code']) ?></span></td>
                                                <td style="color:var(--text-primary);max-width:300px"><?= $e($coupon['description']) ?></td>
                                                <td>
                                                    <span class="badge badge-success" style="font-size:.78rem">
                                                        Giảm <?= $coupon['type'] === 'percent' ? $e(rtrim(rtrim((string)$coupon['value'], '0'), '.')) . '%' : $money($coupon['value']) ?>
                                                    </span>
                                                </td>
                                                <td><?= $money($coupon['min_order']) ?></td>
                                                <td>
                                                    <div class="usage-meter">
                                                        <span style="font-size:.78rem"><strong><?= $e($used) ?></strong> / <?= $limit > 0 ? $e($limit) : '∞' ?></span>
                                                        <?php if ($limit > 0): ?>
                                                            <div class="usage-meter-track">
                                                                <div class="usage-meter-fill" style="width:<?= $usagePct ?>%"></div>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge <?= $isExpired ? 'badge-danger' : 'badge-neutral' ?>">
                                                        <?= $e($formatDateOnly($coupon['expiry_date'] ?: '')) ?>
                                                    </span>
                                                </td>
                                                <td style="text-align:right">
                                                    <div class="actions-cell" style="justify-content:flex-end">
                                                        <details class="delete-confirm">
                                                            <summary class="btn-icon-sm delete-btn" title="Xóa mã khuyến mãi">
                                                                <span class="delete-confirm-label-closed" style="display:inline-flex"><?= $icon('trash', 15) ?></span>
                                                                <span class="delete-confirm-label-open">Hủy</span>
                                                            </summary>
                                                            <form method="post" class="admin-inline">
                                                                <?= $formAction('coupon_delete') ?>
                                                                <input type="hidden" name="id" value="<?= $e($coupon['id']) ?>">
                                                                <button class="btn-danger btn-xs" type="submit">Xác nhận xóa</button>
                                                            </form>
                                                        </details>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </section>

                <?php elseif ($tab === 'settings'): ?>
                    <form method="post">
                        <?= $formAction('settings_save') ?>
                        <div class="settings-grid">
                            <section class="card">
                                <div class="card-header">
                                    <div>
                                        <h2 class="card-title">Thông tin thương hiệu &amp; Showroom</h2>
                                        <p class="card-subtitle">Thông tin liên hệ và địa chỉ hiển thị cho khách hàng</p>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="form-group">
                                        <label class="admin-field">
                                            <span>Tên cửa hàng *</span>
                                            <input name="storeName" required value="<?= $e($settings['storeName'] ?? 'VAST STUDIO') ?>">
                                        </label>
                                    </div>
                                    <div class="form-group">
                                        <label class="admin-field">
                                            <span>Slogan thương hiệu</span>
                                            <input name="tagline" value="<?= $e($settings['tagline'] ?? '') ?>">
                                        </label>
                                    </div>
                                    <div class="form-row-2 form-group">
                                        <label class="admin-field">
                                            <span>Hotline CSKH</span>
                                            <input name="hotline" value="<?= $e($settings['hotline'] ?? '') ?>">
                                        </label>
                                        <label class="admin-field">
                                            <span>Email hỗ trợ</span>
                                            <input type="email" name="email" value="<?= $e($settings['email'] ?? '') ?>">
                                        </label>
                                    </div>
                                    <div class="form-group">
                                        <label class="admin-field">
                                            <span>Địa chỉ Hà Nội</span>
                                            <input name="addressHanoi" value="<?= $e($settings['addressHanoi'] ?? '') ?>">
                                        </label>
                                    </div>
                                    <div class="form-group" style="margin-bottom:0">
                                        <label class="admin-field">
                                            <span>Địa chỉ TP. Hồ Chí Minh</span>
                                            <input name="addressHcm" value="<?= $e($settings['addressHcm'] ?? '') ?>">
                                        </label>
                                    </div>
                                </div>
                            </section>

                            <section class="card">
                                <div class="card-header">
                                    <div>
                                        <h2 class="card-title">Vận chuyển &amp; Thanh toán Ngân hàng</h2>
                                        <p class="card-subtitle">Cấu hình phí giao hàng và tài khoản nhận chuyển khoản</p>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="form-row-2 form-group">
                                        <label class="admin-field">
                                            <span>Ngưỡng miễn phí vận chuyển (₫)</span>
                                            <input type="number" min="0" name="freeShippingThreshold" value="<?= $e($settings['freeShippingThreshold'] ?? 500000) ?>">
                                        </label>
                                        <label class="admin-field">
                                            <span>Phí giao hàng tiêu chuẩn (₫)</span>
                                            <input type="number" min="0" name="defaultShippingFee" value="<?= $e($settings['defaultShippingFee'] ?? 30000) ?>">
                                        </label>
                                    </div>
                                    <div class="form-group">
                                        <label class="admin-field">
                                            <span>Tên ngân hàng</span>
                                            <input name="bankName" value="<?= $e($settings['bankName'] ?? '') ?>">
                                        </label>
                                    </div>
                                    <div class="form-row-2 form-group" style="margin-bottom:0">
                                        <label class="admin-field">
                                            <span>Số tài khoản</span>
                                            <input name="bankAccount" value="<?= $e($settings['bankAccount'] ?? '') ?>">
                                        </label>
                                        <label class="admin-field">
                                            <span>Chủ tài khoản</span>
                                            <input name="bankOwner" value="<?= $e($settings['bankOwner'] ?? '') ?>">
                                        </label>
                                    </div>
                                </div>
                            </section>
                        </div>

                        <div class="settings-submit-bar">
                            <span class="editor-footer-hint">Các thay đổi sẽ áp dụng cho cấu hình cửa hàng trong cơ sở dữ liệu.</span>
                            <button class="btn-primary" type="submit">
                                <?= $icon('check', 16) ?>
                                <span>Lưu cài đặt cửa hàng</span>
                            </button>
                        </div>
                    </form>

                <?php elseif ($tab === 'returns'): ?>
                    <section class="card">
                        <div class="card-body">
                            <nav class="filter-pills" aria-label="Lọc trạng thái đổi trả">
                                <?php foreach (['all' => 'Tất cả'] + $returnStatusLabels as $rKey => $rLabel): ?>
                                    <a class="filter-pill <?= ($returnFilter ?? 'all') === $rKey ? 'active' : '' ?>" href="admin.php?tab=returns&amp;return_status=<?= $e($rKey) ?>">
                                        <span><?= $e($rLabel) ?></span>
                                    </a>
                                <?php endforeach; ?>
                            </nav>
                        </div>
                    </section>

                    <?php if ($selectedReturn):
                        $retMeta = $returnStatusMeta[$selectedReturn['status']] ?? ['label' => $selectedReturn['status'], 'badge' => 'badge-neutral'];
                    ?>
                        <section class="card order-detail-banner">
                            <div class="card-header">
                                <div>
                                    <div class="admin-inline">
                                        <h2 class="card-title">Phiếu đổi trả #<?= $e($selectedReturn['return_code']) ?></h2>
                                        <span class="badge <?= $retMeta['badge'] ?>"><?= $e($retMeta['label']) ?></span>
                                    </div>
                                    <p class="order-detail-kicker">
                                        Đơn hàng: <a class="mono-id" href="admin.php?tab=orders&amp;order=<?= rawurlencode($selectedReturn['order_code']) ?>"><?= $e($selectedReturn['order_code']) ?></a>
                                        · Khách hàng: <strong><?= $e($selectedReturn['customer_name']) ?></strong> (<?= $e($selectedReturn['customer_phone']) ?>)
                                        · Gửi lúc: <?= $e($formatDateTime($selectedReturn['created_at'])) ?>
                                    </p>
                                </div>
                                <a class="btn-secondary btn-sm" href="admin.php?tab=returns&amp;return_status=<?= $e($returnFilter ?? 'all') ?>">Đóng</a>
                            </div>
                            <div class="card-body" style="display:grid;gap:18px">
                                <div class="form-grid-2">
                                    <div class="order-workflow-box">
                                        <span class="order-detail-cell-label">Lý do &amp; Mô tả từ khách hàng</span>
                                        <div><strong>Lý do:</strong> <?= $e($selectedReturn['reason_text']) ?></div>
                                        <div style="font-size:.84rem;color:var(--text-secondary);line-height:1.5"><?= $e($selectedReturn['description']) ?></div>
                                        <?php if (!empty($selectedReturn['images'])): ?>
                                            <div style="margin-top:8px">
                                                <strong style="font-size:.8rem">Hình ảnh khách gửi (<?= $e(count($selectedReturn['images'])) ?>):</strong>
                                                <div class="return-thumbs">
                                                    <?php foreach ($selectedReturn['images'] as $img): ?>
                                                        <a href="<?= $e($img['file_path']) ?>" target="_blank" rel="noopener" title="<?= $e($img['original_name']) ?>">
                                                            <img src="<?= $e($img['file_path']) ?>" alt="Ảnh đổi trả">
                                                        </a>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="order-workflow-box">
                                        <span class="order-detail-cell-label">Xử lý yêu cầu đổi trả</span>
                                        <?php if ($selectedReturn['status'] === 'pending_review'): ?>
                                            <form method="post" style="display:grid;gap:8px;padding-bottom:12px;border-bottom:1px dashed var(--border-color)">
                                                <?= $formAction('return_approve') ?>
                                                <input type="hidden" name="return_code" value="<?= $e($selectedReturn['return_code']) ?>">
                                                <label class="admin-field">
                                                    <span>Chấp thuận — Hướng dẫn khách gửi hàng về</span>
                                                    <textarea name="note" rows="2" placeholder="VD: Vui lòng đóng gói nguyên tem gửi về VAST STUDIO (24 Hàng Bông, Hoàn Kiếm, HN)..."></textarea>
                                                </label>
                                                <button class="btn-success-sm" type="submit"><?= $icon('check', 15) ?> Chấp thuận đổi trả</button>
                                            </form>

                                            <form method="post" style="display:grid;gap:8px">
                                                <?= $formAction('return_reject') ?>
                                                <input type="hidden" name="return_code" value="<?= $e($selectedReturn['return_code']) ?>">
                                                <label class="admin-field">
                                                    <span>Từ chối — Lý do từ chối bắt buộc *</span>
                                                    <textarea name="reject_reason" rows="2" required minlength="5" placeholder="Nhập lý do từ chối yêu cầu đổi trả..."></textarea>
                                                </label>
                                                <button class="btn-danger-sm" type="submit"><?= $icon('xCircle', 15) ?> Từ chối đổi trả</button>
                                            </form>
                                        <?php elseif (in_array($selectedReturn['status'], ['awaiting_shipment', 'approved'], true)): ?>
                                            <form method="post" style="display:grid;gap:10px">
                                                <?= $formAction('return_confirm_received') ?>
                                                <input type="hidden" name="return_code" value="<?= $e($selectedReturn['return_code']) ?>">
                                                <label class="admin-field">
                                                    <span>Tình trạng hàng khi shop nhận được</span>
                                                    <textarea name="condition_note" rows="2" placeholder="VD: Đã nhận kiện hàng đổi trả, sản phẩm còn nguyên tem mác..."></textarea>
                                                </label>
                                                <button class="btn-primary btn-sm" type="submit"><?= $icon('package', 15) ?> Xác nhận đã nhận hàng đổi trả</button>
                                            </form>
                                        <?php elseif (in_array($selectedReturn['status'], ['received', 'processing_exchange'], true)): ?>
                                            <form method="post" style="display:grid;gap:10px" onsubmit="return confirm('Xác nhận hoàn tất xử lý phiếu đổi trả <?= $e($selectedReturn['return_code']) ?>?')">
                                                <?= $formAction('return_complete') ?>
                                                <input type="hidden" name="return_code" value="<?= $e($selectedReturn['return_code']) ?>">
                                                <label class="admin-field">
                                                    <span>Hình thức hoàn tất *</span>
                                                    <select name="resolution_type">
                                                        <option value="exchange">Đổi sản phẩm mới (Gửi hàng đổi cho khách)</option>
                                                        <option value="refund">Hoàn tiền (Tính lại doanh thu đơn hàng)</option>
                                                    </select>
                                                </label>
                                                <label style="display:flex;align-items:center;gap:8px;font-size:.82rem;cursor:pointer">
                                                    <input type="checkbox" name="restock_approved" value="1">
                                                    <span>Nhập lại số lượng sản phẩm vào tồn kho sau khi kiểm tra đạt chuẩn</span>
                                                </label>
                                                <label class="admin-field">
                                                    <span>Ghi chú hoàn tất</span>
                                                    <textarea name="note" rows="2" placeholder="Ghi chú mã vận đơn mới hoặc thông tin hoàn tiền..."></textarea>
                                                </label>
                                                <button class="btn-success-sm" type="submit"><?= $icon('checkCircle', 15) ?> Hoàn tất xử lý đổi trả</button>
                                            </form>
                                        <?php else: ?>
                                            <div style="font-size:.83rem"><strong>Hướng xử lý:</strong> <?= $e($selectedReturn['resolution_type'] ?: '—') ?></div>
                                            <?php if ((float)($selectedReturn['refund_amount'] ?? 0) > 0): ?>
                                                <div style="font-size:.83rem"><strong>Số tiền hoàn:</strong> <?= $money($selectedReturn['refund_amount']) ?></div>
                                            <?php endif; ?>
                                            <?php if (!empty($selectedReturn['reject_reason'])): ?>
                                                <div class="order-detail-note" style="background:var(--status-danger-bg);color:var(--status-danger-text)"><strong>Lý do từ chối:</strong> <?= $e($selectedReturn['reject_reason']) ?></div>
                                            <?php elseif (!empty($selectedReturn['admin_note'])): ?>
                                                <div style="font-size:.83rem"><strong>Ghi chú Admin:</strong> <?= $e($selectedReturn['admin_note']) ?></div>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div>
                                    <h3 style="font-size:.9rem;font-weight:700;margin-bottom:8px;font-family:var(--font-heading)">Sản phẩm yêu cầu đổi trả</h3>
                                    <div class="table-responsive">
                                        <table class="vast-table">
                                            <thead>
                                                <tr>
                                                    <th>Sản phẩm</th>
                                                    <th>SKU</th>
                                                    <th>Biến thể</th>
                                                    <th>SL đổi trả</th>
                                                    <th style="text-align:right">Đơn giá</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($selectedReturn['items'] ?? [] as $ri): ?>
                                                    <tr>
                                                        <td><strong><?= $e($ri['product_name']) ?></strong></td>
                                                        <td><span class="mono-id"><?= $e($ri['product_sku'] ?: '—') ?></span></td>
                                                        <td>Size <?= $e($ri['size'] ?: '—') ?> · Màu <?= $e($ri['color'] ?: '—') ?></td>
                                                        <td><strong>×<?= $e($ri['quantity']) ?></strong></td>
                                                        <td style="text-align:right"><?= $money($ri['unit_price']) ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </section>
                    <?php endif; ?>

                    <section class="card">
                        <div class="card-header">
                            <div>
                                <h2 class="card-title">Danh sách yêu cầu đổi trả</h2>
                                <p class="card-subtitle">Tổng cộng <?= $e(count($returnRequests ?? [])) ?> phiếu đổi trả</p>
                            </div>
                        </div>
                        <?php if (empty($returnRequests)): ?>
                            <div class="empty-state">
                                <?= $icon('rotateCcw', 28) ?>
                                <p>Chưa có yêu cầu đổi trả nào.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="vast-table">
                                    <thead>
                                        <tr>
                                            <th>Mã đổi trả</th>
                                            <th>Đơn hàng</th>
                                            <th>Khách hàng</th>
                                            <th>Lý do</th>
                                            <th>Ảnh</th>
                                            <th>Trạng thái</th>
                                            <th style="text-align:right">Xử lý</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($returnRequests as $req):
                                            $rm = $returnStatusMeta[$req['status']] ?? ['label' => $req['status'], 'badge' => 'badge-neutral'];
                                        ?>
                                            <tr>
                                                <td>
                                                    <a class="mono-id" href="admin.php?tab=returns&amp;return=<?= rawurlencode($req['return_code']) ?>"><?= $e($req['return_code']) ?></a>
                                                    <div style="font-size:.73rem;color:var(--text-muted);margin-top:4px"><?= $e($formatDateTime($req['created_at'])) ?></div>
                                                </td>
                                                <td><a class="mono-id" href="admin.php?tab=orders&amp;order=<?= rawurlencode($req['order_code']) ?>"><?= $e($req['order_code']) ?></a></td>
                                                <td>
                                                    <div class="product-meta-title"><?= $e($req['customer_name']) ?></div>
                                                    <div style="font-size:.75rem;color:var(--text-muted)"><?= $e($req['customer_phone']) ?></div>
                                                </td>
                                                <td><?= $e($req['reason_text']) ?></td>
                                                <td><?= $e(count($req['images'] ?? [])) ?> ảnh</td>
                                                <td><span class="badge <?= $rm['badge'] ?>"><?= $e($rm['label']) ?></span></td>
                                                <td style="text-align:right">
                                                    <a class="btn-secondary btn-xs" href="admin.php?tab=returns&amp;return=<?= rawurlencode($req['return_code']) ?>">Chi tiết</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </section>

                <?php elseif ($tab === 'emails'): ?>
                    <section class="card">
                        <div class="card-body" style="display:grid;gap:14px">
                            <?php if (!empty($smtpConfigured)): ?>
                                <div class="admin-alert admin-alert--success">
                                    <span class="admin-alert-icon"><?= $icon('checkCircle', 18) ?></span>
                                    <span><strong>SMTP đã cấu hình:</strong> Hệ thống đang gửi email tự động qua máy chủ SMTP.</span>
                                </div>
                            <?php else: ?>
                                <div class="admin-alert admin-alert--warning">
                                    <span class="admin-alert-icon"><?= $icon('alert', 18) ?></span>
                                    <span>
                                        <strong>Chưa cấu hình SMTP thật:</strong> Vui lòng tạo file <code>includes/smtp.local.php</code> (từ mẫu <code>includes/smtp.example.php</code>) hoặc thiết lập biến môi trường <code>SMTP_HOST</code>, <code>SMTP_USERNAME</code>, <code>SMTP_PASSWORD</code> để gửi email thật. Mọi email vẫn được lưu tại đây để gửi lại sau khi cấu hình.
                                    </span>
                                </div>
                            <?php endif; ?>

                            <nav class="filter-pills" aria-label="Lọc nhật ký email">
                                <?php foreach (['all' => 'Tất cả', 'sent' => 'Đã gửi', 'failed' => 'Thất bại', 'pending' => 'Đang chờ'] as $ek => $el): ?>
                                    <a class="filter-pill <?= ($emailFilter ?? 'all') === $ek ? 'active' : '' ?>" href="admin.php?tab=emails&amp;email_status=<?= $e($ek) ?>">
                                        <span><?= $e($el) ?></span>
                                    </a>
                                <?php endforeach; ?>
                            </nav>
                        </div>
                    </section>

                    <section class="card">
                        <div class="card-header">
                            <div>
                                <h2 class="card-title">Nhật ký gửi Email SMTP</h2>
                                <p class="card-subtitle">Hiển thị tối đa <?= $e(count($emailLogs ?? [])) ?> bản ghi gần nhất (chống gửi trùng khi F5 và hỗ trợ gửi lại khi lỗi)</p>
                            </div>
                        </div>
                        <?php if (empty($emailLogs)): ?>
                            <div class="empty-state">
                                <?= $icon('mail', 28) ?>
                                <p>Chưa có bản ghi email nào.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="vast-table">
                                    <thead>
                                        <tr>
                                            <th>Đơn hàng</th>
                                            <th>Người nhận &amp; Tiêu đề</th>
                                            <th>Mẫu</th>
                                            <th>Trạng thái &amp; Lỗi</th>
                                            <th>Số lần</th>
                                            <th style="text-align:right">Thao tác</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($emailLogs as $elog): ?>
                                            <tr>
                                                <td>
                                                    <?php if (!empty($elog['order_code'])): ?>
                                                        <a class="mono-id" href="admin.php?tab=orders&amp;order=<?= rawurlencode($elog['order_code']) ?>"><?= $e($elog['order_code']) ?></a>
                                                    <?php else: ?>
                                                        <span class="admin-muted">—</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div style="font-weight:600;font-size:.84rem"><?= $e($elog['subject']) ?></div>
                                                    <div style="font-size:.75rem;color:var(--text-muted)"><?= $e($elog['recipient_email']) ?> · <?= $e($formatDateTime($elog['sent_at'] ?: $elog['last_attempt_at'])) ?></div>
                                                </td>
                                                <td><span class="mono-id"><?= $e($elog['template_code'] ?: 'email') ?></span></td>
                                                <td>
                                                    <span class="badge <?= $elog['status'] === 'sent' ? 'badge-success' : ($elog['status'] === 'failed' ? 'badge-danger' : 'badge-warning') ?>"><?= $e($elog['status']) ?></span>
                                                    <?php if (!empty($elog['error_message'])): ?>
                                                        <div style="font-size:.73rem;color:var(--status-danger-text);margin-top:4px;max-width:260px"><?= $e($elog['error_message']) ?></div>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= $e($elog['attempts'] ?? 0) ?></td>
                                                <td style="text-align:right">
                                                    <?php if ($elog['status'] !== 'sent'): ?>
                                                        <form method="post" style="margin:0">
                                                            <?= $formAction('email_retry') ?>
                                                            <input type="hidden" name="email_log_id" value="<?= $e($elog['id']) ?>">
                                                            <input type="hidden" name="return_tab" value="emails">
                                                            <button class="btn-primary btn-xs" type="submit">Gửi lại</button>
                                                        </form>
                                                    <?php else: ?>
                                                        <span class="admin-muted" style="font-size:.76rem">Đã gửi</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </section>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>
</body>
</html>
