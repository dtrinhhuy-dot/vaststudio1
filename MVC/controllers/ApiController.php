<?php

require_once __DIR__ . '/../models/OrderService.php';
require_once __DIR__ . '/../models/SepayService.php';

class ApiController
{
    public function handle(): void
    {
        require_once __DIR__ . '/../../includes/config.php';
        header('Content-Type: application/json; charset=UTF-8');

        $action = (string)($_GET['action'] ?? $_POST['action'] ?? '');

        try {
            $db = getDatabaseConnection();
            $service = new OrderService($db);
            $sepay = new SepayService($db);

            switch ($action) {
                case 'storefront_bootstrap':
                    echo json_encode(['ok' => true, 'data' => $service->getStorefrontCatalog()], JSON_UNESCAPED_UNICODE);
                    return;

                case 'product_quickview':
                    $id = trim((string)($_GET['id'] ?? ''));
                    if ($id === '') {
                        $this->jsonError('Thiếu mã sản phẩm.', 400);
                        return;
                    }
                    $product = $service->getProductQuickView($id);
                    if (!$product) {
                        $this->jsonError('Không tìm thấy sản phẩm.', 404);
                        return;
                    }
                    echo json_encode(['ok' => true, 'product' => $product], JSON_UNESCAPED_UNICODE);
                    return;

                case 'coupon_validate':
                    $code = (string)($_GET['code'] ?? '');
                    $subtotal = (float)($_GET['subtotal'] ?? 0);
                    $res = $service->validateCoupon($code, $subtotal);
                    echo json_encode(['ok' => true] + $res, JSON_UNESCAPED_UNICODE);
                    return;

                case 'order_track':
                    $query = trim((string)($_GET['q'] ?? ''));
                    if ($query === '') {
                        $this->jsonError('Vui lòng nhập mã đơn hàng hoặc số điện thoại.', 400);
                        return;
                    }
                    $order = $service->getTrackingOrder($query);
                    if (!$order) {
                        echo json_encode(['ok' => true, 'order' => null], JSON_UNESCAPED_UNICODE);
                        return;
                    }
                    echo json_encode(['ok' => true, 'order' => $this->formatPublicOrder($order, $sepay)], JSON_UNESCAPED_UNICODE);
                    return;

                case 'payment_status':
                    $orderCode = trim((string)($_GET['order'] ?? $_GET['q'] ?? ''));
                    if ($orderCode === '') {
                        $this->jsonError('Thiếu mã đơn hàng.', 400);
                        return;
                    }
                    $order = $service->getOrderFullDetail($orderCode);
                    if (!$order) {
                        $this->jsonError('Không tìm thấy đơn hàng.', 404);
                        return;
                    }
                    if (($order['payment_status'] ?? 'pending') === 'pending'
                        && ($order['order_status'] ?? '') !== OrderWorkflow::STATUS_CANCELLED
                        && $sepay->hasApiToken()
                    ) {
                        $sepay->syncRecentTransactionsFromApi(20);
                        $order = $service->getOrderFullDetail($orderCode) ?? $order;
                    }
                    $qrInfo = $sepay->buildQrData($order);
                    echo json_encode([
                        'ok' => true,
                        'orderId' => $order['id'],
                        'orderStatus' => $order['order_status'],
                        'statusLabel' => OrderWorkflow::statusLabel((string)$order['order_status']),
                        'paymentMethod' => $order['payment_method'],
                        'paymentStatus' => $order['payment_status'],
                        'transactionCode' => $order['payment_transaction_code'] ?? null,
                        'paidAt' => $order['payment_updated_at'] ?? null,
                        'total' => $order['total'],
                        'sepay' => $qrInfo,
                    ], JSON_UNESCAPED_UNICODE);
                    return;

                case 'sepay_test_pay':
                    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                        $this->jsonError('Phương thức không hợp lệ.', 405);
                        return;
                    }
                    $cfg = $sepay->getConfig();
                    $isDemoAcc = $cfg['bankAccountClean'] === '' || $cfg['bankAccountClean'] === '998877665500';
                    if (!$sepay->isLocalSandboxAllowed() || (!$isDemoAcc && ($cfg['apiToken'] !== '' || $cfg['apiKey'] !== ''))) {
                        $this->jsonError('Chức năng giả lập chỉ khả dụng ở môi trường thử nghiệm cục bộ khi chưa gắn tài khoản ngân hàng SePay thật.', 403);
                        return;
                    }
                    $raw = file_get_contents('php://input');
                    $payload = json_decode($raw ?: '{}', true);
                    if (!is_array($payload)) {
                        $payload = $_POST;
                    }
                    $orderCode = trim((string)($payload['order'] ?? $payload['orderCode'] ?? ''));
                    if ($orderCode === '') {
                        $this->jsonError('Thiếu mã đơn hàng.', 400);
                        return;
                    }
                    $res = $sepay->simulateWebhookForOrder($orderCode);
                    echo json_encode(['ok' => true] + $res, JSON_UNESCAPED_UNICODE);
                    return;

                case 'sepay_webhook':
                    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                        http_response_code(405);
                        echo json_encode(['success' => false, 'message' => 'Method not allowed'], JSON_UNESCAPED_UNICODE);
                        return;
                    }
                    $authHeader = $this->readHeader('Authorization');
                    $apiKeyHeader = $this->readHeader('X-Sepay-Apikey');
                    if (!$sepay->verifyAuthorizationHeader($authHeader, $apiKeyHeader)) {
                        http_response_code(401);
                        echo json_encode(['success' => false, 'message' => 'Unauthorized SePay webhook request'], JSON_UNESCAPED_UNICODE);
                        return;
                    }
                    $raw = file_get_contents('php://input') ?: '';
                    $payload = json_decode($raw, true);
                    if (!is_array($payload)) {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => 'Invalid JSON payload'], JSON_UNESCAPED_UNICODE);
                        return;
                    }
                    $result = $sepay->handleWebhook($payload, $raw, false);
                    http_response_code(200);
                    echo json_encode($result, JSON_UNESCAPED_UNICODE);
                    return;

                case 'checkout_create':
                    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                        $this->jsonError('Phương thức không hợp lệ.', 405);
                        return;
                    }
                    $raw = file_get_contents('php://input');
                    $payload = json_decode($raw ?: '{}', true);
                    if (!is_array($payload)) {
                        $payload = $_POST;
                    }
                    $sessionUserId = isset($_SESSION['customer_user_id']) ? (int)$_SESSION['customer_user_id'] : null;
                    $order = $service->createOrderFromCheckout($payload, $sessionUserId);
                    echo json_encode(['ok' => true, 'order' => $this->formatPublicOrder($order, $sepay)], JSON_UNESCAPED_UNICODE);
                    return;

                default:
                    $this->jsonError('Action API không hợp lệ.', 400);
            }
        } catch (InvalidArgumentException $exception) {
            if ($action === 'sepay_webhook') {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => $exception->getMessage()], JSON_UNESCAPED_UNICODE);
                return;
            }
            $this->jsonError($exception->getMessage(), 422);
        } catch (PDOException $exception) {
            error_log('API database error: ' . $exception->getMessage());
            if ($action === 'sepay_webhook') {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Database error'], JSON_UNESCAPED_UNICODE);
                return;
            }
            $this->jsonError('Lỗi kết nối cơ sở dữ liệu.', 503);
        } catch (Throwable $exception) {
            error_log('API error: ' . $exception->getMessage());
            if ($action === 'sepay_webhook') {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Server error'], JSON_UNESCAPED_UNICODE);
                return;
            }
            $this->jsonError('Không thể xử lý yêu cầu lúc này.', 500);
        }
    }

    private function formatPublicOrder(array $order, SepayService $sepay): array
    {
        return [
            'id' => $order['id'],
            'customerName' => $order['customer_name'],
            'customerPhone' => $order['customer_phone'],
            'customerEmail' => $order['customer_email'],
            'shippingAddress' => $order['shipping_address'],
            'shippingMethod' => $order['shipping_method'],
            'subtotal' => $order['subtotal'],
            'shippingFee' => $order['shipping_fee'],
            'discount' => $order['discount'],
            'couponCode' => $order['coupon_code'],
            'total' => $order['total'],
            'paymentMethod' => $order['payment_method'],
            'paymentStatus' => $order['payment_status'],
            'transactionCode' => $order['payment_transaction_code'] ?? null,
            'paidAt' => $order['payment_updated_at'] ?? null,
            'orderStatus' => $order['order_status'],
            'statusLabel' => OrderWorkflow::statusLabel($order['order_status']),
            'note' => $order['note'],
            'cancelReason' => $order['cancel_reason'],
            'createdAt' => $order['created_at'],
            'deliveredAt' => $order['delivered_at'],
            'customerConfirmedAt' => $order['customer_confirmed_at'],
            'sepay' => $sepay->buildQrData($order),
            'items' => array_map(static fn (array $i): array => [
                'name' => $i['name'],
                'sku' => $i['sku'],
                'image' => $i['image'],
                'size' => $i['size'],
                'color' => $i['color'],
                'price' => $i['price'],
                'quantity' => $i['quantity'],
            ], $order['items'] ?? []),
            'history' => array_map(static fn (array $h): array => [
                'status' => $h['status'],
                'statusLabel' => OrderWorkflow::statusLabel((string)$h['status']),
                'actor' => $h['resolved_actor'] ?? ($h['actor_name'] ?? 'Hệ thống'),
                'note' => $h['note'] ?? '',
                'createdAt' => $h['created_at'],
            ], $order['history'] ?? []),
        ];
    }

    private function readHeader(string $name): string
    {
        if (function_exists('getallheaders')) {
            foreach (getallheaders() as $k => $v) {
                if (strcasecmp((string)$k, $name) === 0) {
                    return (string)$v;
                }
            }
        }
        $serverKey = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        if (!empty($_SERVER[$serverKey])) {
            return (string)$_SERVER[$serverKey];
        }
        if (strcasecmp($name, 'Authorization') === 0 && !empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            return (string)$_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        }
        return '';
    }

    private function jsonError(string $message, int $code = 400): void
    {
        http_response_code($code);
        echo json_encode(['ok' => false, 'message' => $message], JSON_UNESCAPED_UNICODE);
    }
}
