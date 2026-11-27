<?php

require_once __DIR__ . '/../models/AdminStoreModel.php';

class AdminController
{
    private AdminStoreModel $model;

    public function __construct(AdminStoreModel $model)
    {
        $this->model = $model;
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
        $tabs = ['dashboard', 'products', 'orders', 'collections', 'customers', 'coupons', 'settings'];
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
        $revenueRange = ($_GET['range'] ?? '7') === '30' ? 30 : 7;

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
            'stats' => $this->model->getDashboardStats(),
            'revenueRange' => $revenueRange,
            'revenueHistory' => $this->model->getRevenueHistory($revenueRange),
            'products' => $this->model->getProducts($productFilters),
            'editProduct' => isset($_GET['edit_product']) ? $this->model->getProduct((string)$_GET['edit_product']) : null,
            'orders' => $this->model->getOrders($orderFilters),
            'recentOrders' => $this->model->getRecentOrders(),
            'selectedOrder' => isset($_GET['order']) ? $this->model->getOrder((string)$_GET['order']) : null,
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

            case 'order_status':
                $id = trim((string)($_POST['id'] ?? ''));
                $status = (string)($_POST['status'] ?? '');
                if ($id === '' || !in_array($status, ['pending', 'processing', 'shipping', 'completed', 'cancelled'], true)) {
                    throw new InvalidArgumentException('Trạng thái đơn hàng không hợp lệ.');
                }
                if (!$this->model->updateOrderStatus($id, $status)) {
                    throw new InvalidArgumentException('Không tìm thấy đơn hàng cần cập nhật.');
                }
                $this->redirect('orders', 'Đã cập nhật trạng thái đơn hàng.');
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

            case 'settings_save':
                $settings = $this->model->getSettings();
                foreach ([
                    'storeName', 'tagline', 'hotline', 'email', 'addressHanoi', 'addressHcm',
                    'freeShippingThreshold', 'defaultShippingFee', 'bankName', 'bankAccount', 'bankOwner',
                ] as $key) {
                    $settings[$key] = trim((string)($_POST[$key] ?? ''));
                }
                $this->model->saveSettings($settings);
                $this->redirect('settings', 'Đã lưu cài đặt cửa hàng.');
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
            'collections' => 'Bộ sưu tập thời trang',
            'customers' => 'Danh sách Khách hàng',
            'coupons' => 'Mã Giảm Giá & Khuyến Mãi',
            'settings' => 'Cài đặt Cửa hàng',
        ];
        return $titles[$tab];
    }

    private function redirect(string $tab, string $message): void
    {
        $_SESSION['admin_message'] = $message;
        header('Location: admin.php?tab=' . rawurlencode($tab), true, 303);
        exit;
    }
}
