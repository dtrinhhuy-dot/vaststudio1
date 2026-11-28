<?php

require_once __DIR__ . '/AuthController.php';

class AuthPageController
{
    public function render(): void
    {
        require_once __DIR__ . '/../../includes/config.php';

        try {
            $controller = new AuthController(new CustomerAuthModel(getDatabaseConnection()));
            $viewData = $controller->handleRequest();
        } catch (PDOException $exception) {
            error_log('Customer auth database error: ' . $exception->getMessage());
            if (empty($_SESSION['customer_csrf'])) {
                $_SESSION['customer_csrf'] = bin2hex(random_bytes(32));
            }
            $viewData = [
                'dbError' => true,
                'csrf' => $_SESSION['customer_csrf'],
                'error' => 'Hệ thống đang bảo trì kết nối cơ sở dữ liệu. Vui lòng thử lại sau ít phút.',
                'message' => '',
                'activeTab' => 'login',
                'redirect' => '',
                'oldInput' => [
                    'login_identifier' => '',
                    'reg_name' => '',
                    'reg_phone' => '',
                    'reg_email' => '',
                    'reg_city' => 'Hà Nội',
                ],
                'currentUser' => null,
                'userOrders' => [],
            ];
        }

        extract($viewData, EXTR_SKIP);
        require __DIR__ . '/../views/storefront/auth.php';
    }
}
