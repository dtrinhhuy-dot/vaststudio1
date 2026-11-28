<?php

require_once __DIR__ . '/../models/CustomerAuthModel.php';

class CustomerAuthController
{
    private CustomerAuthModel $model;

    public function __construct(CustomerAuthModel $model)
    {
        $this->model = $model;
    }

    public static function csrfToken(): string
    {
        if (empty($_SESSION['customer_csrf'])) {
            $_SESSION['customer_csrf'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['customer_csrf'];
    }

    public function handleRequest(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, private');

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            if (($_GET['action'] ?? '') !== 'session') {
                $this->respond(404, ['success' => false, 'message' => 'Không tìm thấy thao tác.']);
            }
            $this->respond(200, [
                'success' => true,
                'user' => $_SESSION['customer_user'] ?? null,
                'csrf' => self::csrfToken(),
            ]);
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Allow: GET, POST');
            $this->respond(405, ['success' => false, 'message' => 'Phương thức không được hỗ trợ.']);
        }

        $csrf = (string)($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        if (!hash_equals(self::csrfToken(), $csrf)) {
            $this->respond(419, ['success' => false, 'message' => 'Phiên làm việc đã hết hạn. Vui lòng tải lại trang.']);
        }

        $input = json_decode((string)file_get_contents('php://input'), true);
        if (!is_array($input)) {
            $this->respond(400, ['success' => false, 'message' => 'Dữ liệu gửi lên không hợp lệ.']);
        }

        try {
            $action = (string)($input['action'] ?? '');
            if ($action === 'register') {
                $user = $this->model->register($input);
                $this->startCustomerSession($user);
                $this->respond(201, [
                    'success' => true,
                    'user' => $user,
                    'csrf' => self::csrfToken(),
                ]);
            }
            if ($action === 'login') {
                if (
                    !isset($input['identifier'], $input['password']) ||
                    !is_string($input['identifier']) ||
                    !is_string($input['password'])
                ) {
                    $this->respond(422, ['success' => false, 'message' => 'Vui lòng nhập email/số điện thoại và mật khẩu.']);
                }
                $identifier = (string)($input['identifier'] ?? '');
                $password = (string)($input['password'] ?? '');
                $user = $this->model->authenticate($identifier, $password);
                if (!$user) {
                    $this->respond(401, ['success' => false, 'message' => 'Email/số điện thoại hoặc mật khẩu không chính xác.']);
                }
                $this->startCustomerSession($user);
                $this->respond(200, [
                    'success' => true,
                    'user' => $user,
                    'csrf' => self::csrfToken(),
                ]);
            }
            if ($action === 'logout') {
                unset($_SESSION['customer_user']);
                session_regenerate_id(true);
                $_SESSION['customer_csrf'] = bin2hex(random_bytes(32));
                $this->respond(200, [
                    'success' => true,
                    'user' => null,
                    'csrf' => self::csrfToken(),
                ]);
            }

            $this->respond(400, ['success' => false, 'message' => 'Thao tác không hợp lệ.']);
        } catch (InvalidArgumentException $exception) {
            $this->respond(422, ['success' => false, 'message' => $exception->getMessage()]);
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                $this->respond(409, ['success' => false, 'message' => 'Email hoặc số điện thoại đã được đăng ký.']);
            }
            error_log('Customer authentication database error: ' . $exception->getMessage());
            $this->respond(503, ['success' => false, 'message' => 'Dịch vụ tài khoản hiện không khả dụng. Vui lòng thử lại sau.']);
        }
    }

    private function startCustomerSession(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['customer_user'] = $user;
        $_SESSION['customer_csrf'] = bin2hex(random_bytes(32));
    }

    private function respond(int $status, array $payload): void
    {
        http_response_code($status);
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }
}
