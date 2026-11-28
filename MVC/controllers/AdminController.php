<?php

require_once __DIR__ . '/../models/AdminStoreModel.php';
require_once __DIR__ . '/../models/OrderService.php';
require_once __DIR__ . '/../models/SepayService.php';

class AdminController
{
    private AdminStoreModel $model;
    private OrderService $orderService;
    private SepayService $sepayService;

    public function __construct(AdminStoreModel $model, ?OrderService $orderService = null, ?SepayService $sepayService = null)
    {
        $db = getDatabaseConnection();
        $this->model = $model;
        $this->orderService = $orderService ?? new OrderService($db);
        $this->sepayService = $sepayService ?? new SepayService($db);
    }

    public function handleRequest(): array
    {
        if (empty($_SESSION['admin_csrf'])) {
            $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
        }

        $error = '';
        $message = $_SESSION['admin_message'] ?? '';
        unset($_SESSION['admin_message']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $this->handlePost();
            } catch (InvalidArgumentException $exception) {
                $error = $exception->getMessage();
            } catch (PDOException $exception) {
                if ($exception->getCode() !== '23000') {
                    throw $exception;
                }
                $error = 'Mã SKU hoặc mã khuyến mãi đã tồn tại. Hãy kiểm tra lại dữ liệu.';
            }
        }

        if (!$this->hasAdminAccess()) {
            return [
                'loginRequired' => true,
                'csrf' => $_SESSION['admin_csrf'],
                'error' => $error,
                'message' => $message,
            ];
        }

        $tab = $_GET['tab'] ?? 'dashboard';
        $tabs = ['dashboard', 'products', 'orders', 'returns', 'sepay', 'collections', 'customers', 'coupons', 'emails', 'settings'];
        if (!in_array($tab, $tabs, true)) {
            $tab = 'dashboard';
        }

        $productFilters = [
            'search' => trim((string)($_GET['product_search'] ?? '')),
            'category' => (string)($_GET['category'] ?? 'all'),
            'stock' => (string)($_GET['stock'] ?? 'all'),
            'sort' => (string)($_GET['sort'] ?? 'newest'),
        ];
        $orderFilters = [
            'search' => trim((string)($_GET['order_search'] ?? '')),
            'status' => (string)($_GET['status'] ?? 'all'),
        ];
        $customerFilters = [
            'search' => trim((string)($_GET['customer_search'] ?? '')),
            'tier' => (string)($_GET['tier'] ?? 'all'),
        ];
        $returnFilter = (string)($_GET['return_status'] ?? 'all');
        $emailFilter = (string)($_GET['email_status'] ?? 'all');
        $sepayFilter = (string)($_GET['sepay_status'] ?? 'all');
        $revenueRange = ($_GET['range'] ?? '7') === '30' ? 30 : 7;

        $mailer = new Mailer(getDatabaseConnection());

        return [
            'loginRequired' => false,
            'tab' => $tab,
            'pageTitle' => $this->pageTitle($tab),
            'adminName' => $_SESSION['admin_user_name'] ?? ($this->isLocalRequest() ? 'Quản trị viên cục bộ' : ''),
            'isLocalAccess' => $this->isLocalRequest(),
            'csrf' => $_SESSION['admin_csrf'],
            'error' => $error,
            'message' => $message,
            'theme' => $_SESSION['admin_theme'] ?? 'dark',
            'productFilters' => $productFilters,
            'orderFilters' => $orderFilters,
            'customerFilters' => $customerFilters,
            'returnFilter' => $returnFilter,
            'emailFilter' => $emailFilter,
            'sepayFilter' => $sepayFilter,
            'stats' => $this->model->getDashboardStats(),
            'revenueRange' => $revenueRange,
            'revenueHistory' => $this->model->getRevenueHistory($revenueRange),
            'products' => $this->model->getProducts($productFilters),
            'editProduct' => isset($_GET['edit_product']) ? $this->model->getProduct((string)$_GET['edit_product']) : null,
            'orders' => $this->model->getOrders($orderFilters),
            'recentOrders' => $this->model->getRecentOrders(),
            'selectedOrder' => isset($_GET['order']) ? $this->model->getOrder((string)$_GET['order']) : null,
            'returnRequests' => $this->orderService->getReturnRequests($returnFilter),
            'selectedReturn' => isset($_GET['return']) ? $this->orderService->getReturnRequestByCode((string)$_GET['return']) : null,
            'sepayTransactions' => $this->sepayService->getTransactions($sepayFilter, 80),
            'sepayConfig' => $this->sepayService->getConfig(),
            'sepayBanks' => SepayService::supportedBanks(),
            'sepayApiAccounts' => in_array($tab, ['sepay', 'settings'], true) ? $this->sepayService->getBankAccountsFromApi() : null,
            'emailLogs' => $this->orderService->getEmailLogs($emailFilter, 80),
            'smtpConfigured' => $mailer->isConfigured(),
            'adminNotifications' => $this->orderService->getNotifications('admin', null, 12),
            'collections' => $this->model->getCollections(),
            'customers' => $this->model->getCustomers($customerFilters),
            'coupons' => $this->model->getCoupons(),
            'topProducts' => $this->model->getTopProducts(),
            'settings' => $this->model->getSettings(),
            'orderCounts' => $this->model->getOrderCounts(),
        ];
    }

    private function handlePost(): void
    {
        foreach ($_POST as $value) {
            if (!is_scalar($value)) {
                throw new InvalidArgumentException('Dữ liệu gửi lên không hợp lệ.');
            }
        }
        $token = (string)($_POST['csrf'] ?? '');
        if (!hash_equals((string)($_SESSION['admin_csrf'] ?? ''), $token)) {
            throw new InvalidArgumentException('Phiên làm việc đã hết hạn. Vui lòng tải lại trang và thử lại.');
        }

        $action = (string)($_POST['action'] ?? '');
        if ($action === 'admin_login') {
            $this->requireFields(['email', 'password']);
            $user = $this->model->authenticateAdmin((string)$_POST['email'], (string)$_POST['password']);
            if (!$user) {
                throw new InvalidArgumentException('Thông tin đăng nhập không hợp lệ.');
            }
            session_regenerate_id(true);
            $_SESSION['admin_user_id'] = $user['id'];
            $_SESSION['admin_user_name'] = $user['name'];
            $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
            header('Location: admin.php', true, 303);
            exit;
        }
        if ($action === 'admin_logout') {
            unset($_SESSION['admin_user_id'], $_SESSION['admin_user_name'], $_SESSION['admin_theme']);
            session_regenerate_id(true);
            $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
            header('Location: admin.php', true, 303);
            exit;
        }
        if (!$this->hasAdminAccess()) {
            throw new InvalidArgumentException('Vui lòng đăng nhập tài khoản quản trị.');
        }

        $adminId = isset($_SESSION['admin_user_id']) ? (int)$_SESSION['admin_user_id'] : null;
        $adminName = (string)($_SESSION['admin_user_name'] ?? 'Quản trị viên cục bộ');

        switch ($action) {
            case 'product_save':
                $this->requireFields(['name', 'sku', 'category', 'price', 'stock', 'sizes', 'colors']);
                if (!is_numeric($_POST['price']) || !is_numeric($_POST['stock'])) {
                    throw new InvalidArgumentException('Giá bán và số lượng tồn kho phải là số hợp lệ.');
                }
                $this->model->saveProduct($_POST);
                $this->redirect('products', 'Đã lưu sản phẩm.');
                break;

            case 'product_delete':
                $id = trim((string)($_POST['id'] ?? ''));
                if ($id === '') {
                    throw new InvalidArgumentException('Không xác định được sản phẩm cần xóa.');
                }
                $this->model->deleteProduct($id);
                $this->redirect('products', 'Đã xóa sản phẩm.');
                break;

            case 'order_advance':
            case 'order_status':
                $id = trim((string)($_POST['id'] ?? ''));
                $targetStatus = trim((string)($_POST['status'] ?? $_POST['target_status'] ?? ''));
                $note = trim((string)($_POST['note'] ?? ''));
                if ($id === '' || $targetStatus === '') {
                    throw new InvalidArgumentException('Thiếu thông tin đơn hàng hoặc trạng thái cần cập nhật.');
                }
                $this->orderService->adminAdvanceOrder($id, $targetStatus, $adminId, $adminName, $note);
                $this->redirectUrl('admin.php?tab=orders&order=' . rawurlencode($id), 'Đã cập nhật trạng thái đơn #' . $id . ' thành "' . OrderWorkflow::statusLabel($targetStatus) . '".');
                break;

            case 'order_cancel':
                $id = trim((string)($_POST['id'] ?? ''));
                $reason = trim((string)($_POST['reason'] ?? ''));
                $this->orderService->adminCancelOrder($id, $reason, $adminId, $adminName);
                $this->redirectUrl('admin.php?tab=orders&order=' . rawurlencode($id), 'Đã hủy đơn hàng #' . $id . '.');
                break;

            case 'cancel_request_resolve':
                $id = trim((string)($_POST['id'] ?? ''));
                $decision = trim((string)($_POST['decision'] ?? ''));
                $note = trim((string)($_POST['note'] ?? ''));
                $this->orderService->adminResolveCancelRequest($id, $decision, $adminId, $adminName, $note);
                $msg = $decision === 'approve'
                    ? "Đã đồng ý hủy đơn #{$id}, hoàn kho và cập nhật thông tin hoàn tiền."
                    : "Đã từ chối yêu cầu hủy đơn #{$id} và gửi lý do cho khách hàng.";
                $this->redirectUrl('admin.php?tab=orders&order=' . rawurlencode($id), $msg);
                break;

            case 'return_approve':
                $returnCode = trim((string)($_POST['return_code'] ?? ''));
                $note = trim((string)($_POST['note'] ?? ''));
                $this->orderService->adminApproveReturn($returnCode, $adminId, $adminName, $note);
                $this->redirectUrl('admin.php?tab=returns&return=' . rawurlencode($returnCode), "Đã chấp thuận yêu cầu đổi trả {$returnCode} và gửi hướng dẫn gửi hàng cho khách.");
                break;

            case 'return_reject':
                $returnCode = trim((string)($_POST['return_code'] ?? ''));
                $reason = trim((string)($_POST['reject_reason'] ?? ''));
                $this->orderService->adminRejectReturn($returnCode, $reason, $adminId, $adminName);
                $this->redirectUrl('admin.php?tab=returns&return=' . rawurlencode($returnCode), "Đã từ chối yêu cầu đổi trả {$returnCode}.");
                break;

            case 'return_confirm_received':
                $returnCode = trim((string)($_POST['return_code'] ?? ''));
                $note = trim((string)($_POST['condition_note'] ?? ''));
                $this->orderService->adminConfirmReturnReceived($returnCode, $adminId, $adminName, $note);
                $this->redirectUrl('admin.php?tab=returns&return=' . rawurlencode($returnCode), "Đã xác nhận nhận hàng đổi trả cho {$returnCode}.");
                break;

            case 'return_complete':
                $returnCode = trim((string)($_POST['return_code'] ?? ''));
                $resolutionType = trim((string)($_POST['resolution_type'] ?? 'exchange'));
                $restock = !empty($_POST['restock_approved']);
                $note = trim((string)($_POST['note'] ?? ''));
                $this->orderService->adminCompleteReturn($returnCode, $resolutionType, $restock, $adminId, $adminName, $note);
                $this->redirectUrl('admin.php?tab=returns&return=' . rawurlencode($returnCode), "Đã hoàn tất xử lý đổi trả {$returnCode}.");
                break;

            case 'email_retry':
                $logId = (int)($_POST['email_log_id'] ?? 0);
                if ($logId <= 0) {
                    throw new InvalidArgumentException('Không xác định được bản ghi email.');
                }
                $res = $this->orderService->retryEmailLog($logId);
                $returnTab = (string)($_POST['return_tab'] ?? 'emails');
                $returnOrder = trim((string)($_POST['return_order'] ?? ''));
                $dest = 'admin.php?tab=' . rawurlencode($returnTab) . ($returnOrder !== '' ? '&order=' . rawurlencode($returnOrder) : '');
                if ($res['sent']) {
                    $this->redirectUrl($dest, 'Đã gửi lại email thành công!');
                }
                throw new InvalidArgumentException('Gửi lại email thất bại: ' . ($res['error'] ?? 'Lỗi không xác định'));

            case 'admin_notifications_read':
                $this->orderService->markNotificationsRead('admin', null);
                $this->redirect((string)($_POST['tab'] ?? 'dashboard'), 'Đã đánh dấu tất cả thông báo là đã đọc.');
                break;

            case 'collection_save':
                $this->requireFields(['name']);
                $this->model->saveCollection($_POST);
                $this->redirect('collections', 'Đã tạo bộ sưu tập.');
                break;

            case 'coupon_save':
                $this->requireFields(['code', 'description', 'value', 'min_order', 'usage_limit', 'expiry_date']);
                if (!is_numeric($_POST['value']) || !is_numeric($_POST['min_order']) || !is_numeric($_POST['usage_limit'])) {
                    throw new InvalidArgumentException('Giá trị ưu đãi, đơn tối thiểu và giới hạn lượt dùng phải là số.');
                }
                if (($_POST['type'] ?? '') === 'percent' && ((float)$_POST['value'] <= 0 || (float)$_POST['value'] > 100)) {
                    throw new InvalidArgumentException('Mức giảm theo phần trăm phải lớn hơn 0 và không vượt quá 100%.');
                }
                $date = DateTime::createFromFormat('Y-m-d', (string)$_POST['expiry_date']);
                if (!$date || $date->format('Y-m-d') !== $_POST['expiry_date']) {
                    throw new InvalidArgumentException('Ngày hết hạn không hợp lệ.');
                }
                $this->model->saveCoupon($_POST);
                $this->redirect('coupons', 'Đã tạo mã khuyến mãi.');
                break;

            case 'coupon_delete':
                $id = trim((string)($_POST['id'] ?? ''));
                if ($id === '') {
                    throw new InvalidArgumentException('Không xác định được mã giảm giá cần xóa.');
                }
                $this->model->deleteCoupon($id);
                $this->redirect('coupons', 'Đã xóa mã giảm giá.');
                break;

            case 'sepay_simulate':
                $orderCode = trim((string)($_POST['order_code'] ?? ''));
                $customAmt = trim((string)($_POST['amount'] ?? ''));
                $amountOverride = ($customAmt !== '' && is_numeric($customAmt)) ? (float)$customAmt : null;
                $res = $this->sepayService->simulateWebhookForOrder($orderCode, $amountOverride);
                $returnTo = (string)($_POST['return_tab'] ?? 'sepay');
                $dest = $returnTo === 'orders'
                    ? 'admin.php?tab=orders&order=' . rawurlencode($orderCode)
                    : 'admin.php?tab=sepay';
                $this->redirectUrl($dest, 'SePay giả lập: ' . ($res['message'] ?? 'Đã xử lý webhook.'));
                break;

            case 'sepay_sync_now':
                $accRes = $this->sepayService->getBankAccountsFromApi(true);
                $txRes = $this->sepayService->syncRecentTransactionsFromApi(30, true);
                $autoFilledMsg = '';
                if (!empty($accRes['ok']) && !empty($accRes['accounts'][0])) {
                    $firstAcc = $accRes['accounts'][0];
                    $curSettings = $this->model->getSettings();
                    $curClean = preg_replace('/\s+/', '', (string)($curSettings['bankAccount'] ?? ''));
                    if (($curClean === '' || $curClean === '998877665500') && !empty($firstAcc['accountNumber'])) {
                        $curSettings['bankAccount'] = $firstAcc['accountNumber'];
                        if (!empty($firstAcc['bankCode'])) {
                            $curSettings['sepayBankCode'] = $firstAcc['bankCode'];
                        }
                        if (!empty($firstAcc['bankFullName'])) {
                            $curSettings['bankName'] = $firstAcc['bankFullName'];
                        }
                        if (!empty($firstAcc['accountHolder'])) {
                            $curSettings['bankOwner'] = $firstAcc['accountHolder'];
                        }
                        $this->model->saveSettings($curSettings);
                        $autoFilledMsg = ' Tự động cập nhật STK ' . $firstAcc['accountNumber'] . ' (' . ($firstAcc['bankCode'] ?: 'Bank') . ').';
                    }
                }
                if (empty($accRes['ok'])) {
                    $this->redirectUrl('admin.php?tab=sepay', 'Lỗi kết nối SePay API: ' . ($accRes['error'] ?? 'Không xác định'));
                }
                $accCount = count($accRes['accounts'] ?? []);
                $txChecked = (int)($txRes['checked'] ?? 0);
                $txMatched = count($txRes['matched'] ?? []);
                $this->redirect('sepay', "Đã đồng bộ SePay API (HTTP 200): {$accCount} tài khoản ngân hàng liên kết, kiểm tra {$txChecked} giao dịch mới ({$txMatched} khớp đơn).{$autoFilledMsg}");
                break;

            case 'settings_save':
                $settings = $this->model->getSettings();
                foreach ([
                    'storeName', 'tagline', 'hotline', 'email', 'addressHanoi', 'addressHcm',
                    'freeShippingThreshold', 'defaultShippingFee', 'bankName', 'bankAccount', 'bankOwner',
                    'sepayBankCode', 'sepayPrefix', 'sepayCompanyUrl',
                ] as $key) {
                    $settings[$key] = trim((string)($_POST[$key] ?? ''));
                }
                $settings['sepayEnabled'] = !empty($_POST['sepayEnabled']);
                $settings['sepayRequireAuth'] = !empty($_POST['sepayRequireAuth']);
                $newToken = trim((string)($_POST['sepayWebhookToken'] ?? ''));
                if ($newToken !== '') {
                    $settings['sepayWebhookToken'] = $newToken;
                } elseif (!empty($_POST['clearSepayWebhookToken'])) {
                    $settings['sepayWebhookToken'] = '';
                }
                $newApiToken = trim((string)($_POST['sepayApiToken'] ?? ''));
                if ($newApiToken !== '') {
                    $settings['sepayApiToken'] = $newApiToken;
                } elseif (!empty($_POST['clearSepayApiToken'])) {
                    $settings['sepayApiToken'] = '';
                }
                $this->model->saveSettings($settings);
                $this->redirect('settings', 'Đã lưu cài đặt cửa hàng & cấu hình SePay.');
                break;

            case 'theme':
                $_SESSION['admin_theme'] = ($_POST['theme'] ?? '') === 'light' ? 'light' : 'dark';
                $this->redirect((string)($_POST['tab'] ?? 'dashboard'), 'Đã cập nhật giao diện.');
                break;

            default:
                throw new InvalidArgumentException('Thao tác không hợp lệ.');
        }
    }

    private function requireFields(array $fields): void
    {
        foreach ($fields as $field) {
            if (!isset($_POST[$field]) || trim((string)$_POST[$field]) === '') {
                throw new InvalidArgumentException('Vui lòng nhập đầy đủ các trường bắt buộc.');
            }
        }
    }

    private function hasAdminAccess(): bool
    {
        return $this->isLocalRequest() || !empty($_SESSION['admin_user_id']);
    }

    private function isLocalRequest(): bool
    {
        $remoteAddress = $_SERVER['REMOTE_ADDR'] ?? '';
        $host = strtolower((string)parse_url('http://' . ($_SERVER['HTTP_HOST'] ?? ''), PHP_URL_HOST));

        return in_array($remoteAddress, ['127.0.0.1', '::1'], true)
            && in_array($host, ['localhost', '127.0.0.1', '[::1]'], true);
    }

    private function pageTitle(string $tab): string
    {
        $titles = [
            'dashboard' => 'Tổng quan kinh doanh',
            'products' => 'Quản lý Sản phẩm',
            'orders' => 'Quản lý Đơn hàng',
            'returns' => 'Quản lý Đổi Trả & Hoàn Tiền',
            'sepay' => 'Giao dịch & Webhook SePay',
            'collections' => 'Bộ sưu tập thời trang',
            'customers' => 'Danh sách Khách hàng',
            'coupons' => 'Mã Giảm Giá & Khuyến Mãi',
            'emails' => 'Nhật ký Gửi Email (SMTP)',
            'settings' => 'Cài đặt Cửa hàng',
        ];
        return $titles[$tab] ?? 'Tổng quan kinh doanh';
    }

    private function redirect(string $tab, string $message): void
    {
        $this->redirectUrl('admin.php?tab=' . rawurlencode($tab), $message);
    }

    private function redirectUrl(string $url, string $message): void
    {
        $_SESSION['admin_message'] = $message;
        header('Location: ' . $url, true, 303);
        exit;
    }
}
