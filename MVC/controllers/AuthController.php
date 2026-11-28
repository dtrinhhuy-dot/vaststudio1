<?php

require_once __DIR__ . '/../models/CustomerAuthModel.php';
require_once __DIR__ . '/../models/OrderService.php';

class AuthController
{
    private CustomerAuthModel $model;
    private OrderService $orderService;

    public function __construct(CustomerAuthModel $model, ?OrderService $orderService = null)
    {
        $this->model = $model;
        $db = getDatabaseConnection();
        $this->orderService = $orderService ?? new OrderService($db);
    }

    public function handleRequest(): array
    {
        if (empty($_SESSION['customer_csrf'])) {
            $_SESSION['customer_csrf'] = bin2hex(random_bytes(32));
        }

        $error = '';
        $message = (string)($_SESSION['customer_flash_success'] ?? '');
        unset($_SESSION['customer_flash_success']);

        $activeTab = ($_GET['tab'] ?? 'login') === 'register' ? 'register' : 'login';
        $redirect = $this->sanitizeRedirect((string)($_GET['redirect'] ?? $_POST['redirect'] ?? ''));
        $oldInput = [
            'login_identifier' => '',
            'reg_name' => '',
            'reg_phone' => '',
            'reg_email' => '',
            'reg_city' => 'Hà Nội',
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = (string)($_POST['action'] ?? '');
            if ($action === 'customer_register') {
                $activeTab = 'register';
                $oldInput['reg_name'] = trim((string)($_POST['name'] ?? ''));
                $oldInput['reg_phone'] = trim((string)($_POST['phone'] ?? ''));
                $oldInput['reg_email'] = trim((string)($_POST['email'] ?? ''));
                $oldInput['reg_city'] = trim((string)($_POST['city'] ?? 'Hà Nội'));
            } elseif ($action === 'customer_login') {
                $activeTab = 'login';
                $oldInput['login_identifier'] = trim((string)($_POST['identifier'] ?? ''));
            }

            try {
                $this->handlePost($redirect);
            } catch (InvalidArgumentException $exception) {
                $error = $exception->getMessage();
            } catch (PDOException $exception) {
                if ($exception->getCode() === '23000') {
                    $error = 'Email hoặc số điện thoại này đã tồn tại trên hệ thống.';
                } else {
                    throw $exception;
                }
            }
        }

        $currentUser = null;
        $userOrders = [];
        $notifications = [];
        $sessionUserId = (int)($_SESSION['customer_user_id'] ?? 0);
        if ($sessionUserId > 0) {
            $currentUser = $this->model->getProfile($sessionUserId);
            if ($currentUser) {
                $this->storeSessionUser($currentUser);
                $userOrders = $this->orderService->getCustomerOrdersDetailed($sessionUserId);
                $notifications = $this->orderService->getNotifications('customer', $sessionUserId, 10);
            } else {
                $this->clearSessionUser();
            }
        }

        return [
            'dbError' => false,
            'csrf' => $_SESSION['customer_csrf'],
            'error' => $error,
            'message' => $message,
            'activeTab' => $activeTab,
            'redirect' => $redirect,
            'oldInput' => $oldInput,
            'currentUser' => $currentUser,
            'userOrders' => $userOrders,
            'notifications' => $notifications,
            'returnOrderCode' => trim((string)($_GET['return_order'] ?? '')),
            'detailOrderCode' => trim((string)($_GET['detail_order'] ?? '')),
        ];
    }

    private function handlePost(string $redirect): void
    {
        $action = (string)($_POST['action'] ?? '');
        foreach ($_POST as $key => $value) {
            if ($key === 'return_qty' && is_array($value)) {
                continue;
            }
            if (!is_scalar($value)) {
                throw new InvalidArgumentException('Dữ liệu gửi lên không hợp lệ.');
            }
        }

        $token = (string)($_POST['csrf'] ?? '');
        if (!hash_equals((string)($_SESSION['customer_csrf'] ?? ''), $token)) {
            throw new InvalidArgumentException('Phiên làm việc đã hết hạn. Vui lòng tải lại trang và thử lại.');
        }

        switch ($action) {
            case 'customer_login':
                $identifier = trim((string)($_POST['identifier'] ?? ''));
                $password = (string)($_POST['password'] ?? '');
                if ($identifier === '' || $password === '') {
                    throw new InvalidArgumentException('Vui lòng nhập đầy đủ email/số điện thoại và mật khẩu.');
                }

                $user = $this->model->authenticate($identifier, $password);
                if (!$user) {
                    throw new InvalidArgumentException('Email/số điện thoại hoặc mật khẩu chưa chính xác.');
                }

                session_regenerate_id(true);
                $this->storeSessionUser($user);
                $_SESSION['customer_csrf'] = bin2hex(random_bytes(32));
                $_SESSION['customer_flash_success'] = 'Chào mừng ' . $user['name'] . ' quay trở lại VAST STUDIO!';
                $this->redirectTo($redirect !== '' ? $redirect : 'auth.php');
                break;

            case 'customer_register':
                if (empty($_POST['terms'])) {
                    throw new InvalidArgumentException('Vui lòng đồng ý với Điều khoản thành viên & Chính sách bảo mật.');
                }

                $user = $this->model->register($_POST);
                session_regenerate_id(true);
                $this->storeSessionUser($user);
                $_SESSION['customer_csrf'] = bin2hex(random_bytes(32));
                $_SESSION['customer_flash_success'] = 'Đăng ký thành công! Chào mừng ' . $user['name'] . ' gia nhập VAST STUDIO.';
                $this->redirectTo($redirect !== '' ? $redirect : 'auth.php');
                break;

            case 'customer_profile_update':
                $userId = $this->requireAuthenticatedCustomer();
                $updated = $this->model->updateProfile($userId, $_POST);
                $this->storeSessionUser($updated);
                $_SESSION['customer_flash_success'] = 'Đã cập nhật thông tin tài khoản thành công.';
                $this->redirectTo('auth.php');
                break;

            case 'customer_password_change':
                $userId = $this->requireAuthenticatedCustomer();
                $this->model->changePassword(
                    $userId,
                    (string)($_POST['current_password'] ?? ''),
                    (string)($_POST['new_password'] ?? ''),
                    (string)($_POST['new_password_confirm'] ?? '')
                );
                $_SESSION['customer_flash_success'] = 'Đã đổi mật khẩu thành công.';
                $this->redirectTo('auth.php');
                break;

            case 'customer_order_cancel':
                $userId = $this->requireAuthenticatedCustomer();
                $orderCode = trim((string)($_POST['order_code'] ?? ''));
                $reason = trim((string)($_POST['reason'] ?? ''));
                $this->orderService->customerCancelOrder($orderCode, $userId, $reason);
                $_SESSION['customer_flash_success'] = "Đã hủy đơn hàng #{$orderCode}. Email xác nhận đã được xử lý.";
                $this->redirectTo('auth.php?detail_order=' . rawurlencode($orderCode));
                break;

            case 'customer_order_cancel_request':
                $userId = $this->requireAuthenticatedCustomer();
                $orderCode = trim((string)($_POST['order_code'] ?? ''));
                $reason = trim((string)($_POST['reason'] ?? ''));
                $this->orderService->customerRequestCancel($orderCode, $userId, $reason);
                $_SESSION['customer_flash_success'] = "Đã gửi yêu cầu hủy đơn #{$orderCode}. Admin sẽ xem xét và phản hồi sớm.";
                $this->redirectTo('auth.php?detail_order=' . rawurlencode($orderCode));
                break;

            case 'customer_order_confirm_received':
                $userId = $this->requireAuthenticatedCustomer();
                $orderCode = trim((string)($_POST['order_code'] ?? ''));
                $this->orderService->customerConfirmReceived($orderCode, $userId);
                $_SESSION['customer_flash_success'] = "Cảm ơn bạn! Đơn hàng #{$orderCode} đã chuyển sang trạng thái Hoàn tất.";
                $this->redirectTo('auth.php?detail_order=' . rawurlencode($orderCode));
                break;

            case 'customer_return_submit':
                $userId = $this->requireAuthenticatedCustomer();
                $orderCode = trim((string)($_POST['order_code'] ?? ''));
                $quantities = is_array($_POST['return_qty'] ?? null) ? $_POST['return_qty'] : [];
                $reasonCode = trim((string)($_POST['reason_code'] ?? ''));
                $description = trim((string)($_POST['description'] ?? ''));
                $files = $_FILES['return_images'] ?? [];
                $this->orderService->customerSubmitReturn($orderCode, $userId, $quantities, $reasonCode, $description, $files);
                $_SESSION['customer_flash_success'] = "Đã gửi yêu cầu đổi trả cho đơn #{$orderCode}. Admin sẽ kiểm tra và phản hồi qua email.";
                $this->redirectTo('auth.php?detail_order=' . rawurlencode($orderCode));
                break;

            case 'customer_notifications_read':
                $userId = $this->requireAuthenticatedCustomer();
                $this->orderService->markNotificationsRead('customer', $userId);
                $this->redirectTo('auth.php');
                break;

            case 'customer_logout':
                $this->clearSessionUser();
                session_regenerate_id(true);
                $_SESSION['customer_csrf'] = bin2hex(random_bytes(32));
                $_SESSION['customer_flash_success'] = 'Bạn đã đăng xuất khỏi tài khoản.';
                $this->redirectTo('auth.php?tab=login');
                break;

            default:
                throw new InvalidArgumentException('Thao tác không hợp lệ.');
        }
    }

    private function requireAuthenticatedCustomer(): int
    {
        $userId = (int)($_SESSION['customer_user_id'] ?? 0);
        if ($userId <= 0) {
            throw new InvalidArgumentException('Vui lòng đăng nhập để thực hiện thao tác này.');
        }
        return $userId;
    }

    private function storeSessionUser(array $user): void
    {
        $_SESSION['customer_user_id'] = (int)$user['id'];
        $_SESSION['customer_user_profile'] = [
            'id' => (int)$user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'phone' => $user['phone'],
            'city' => $user['city'],
            'tier' => $user['tier'],
        ];
    }

    private function clearSessionUser(): void
    {
        unset($_SESSION['customer_user_id'], $_SESSION['customer_user_profile']);
    }

    private function sanitizeRedirect(string $target): string
    {
        $target = trim($target);
        if ($target === '' || preg_match('/[\r\n]/', $target) || str_starts_with($target, '//') || str_contains($target, '://')) {
            return '';
        }
        $path = parse_url($target, PHP_URL_PATH);
        $allowed = ['index.php', 'products.php', 'product-detail.php', 'order-tracking.php', 'about.php', 'contact.php', 'checkout.php', 'auth.php'];
        if (!is_string($path) || !in_array(basename($path), $allowed, true)) {
            return '';
        }
        return ltrim($target, '/');
    }

    private function redirectTo(string $location): void
    {
        header('Location: ' . $location, true, 303);
        exit;
    }
}
