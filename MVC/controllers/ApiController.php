<?php

require_once __DIR__ . '/../models/OrderService.php';

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
                    echo json_encode(['ok' => true, 'order' => $this->formatPublicOrder($order)], JSON_UNESCAPED_UNICODE);
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
                    echo json_encode(['ok' => true, 'order' => $this->formatPublicOrder($order)], JSON_UNESCAPED_UNICODE);
                    return;

                default:
                    $this->jsonError('Action API không hợp lệ.', 400);
            }
        } catch (InvalidArgumentException $exception) {
            $this->jsonError($exception->getMessage(), 422);
        } catch (PDOException $exception) {
            error_log('API database error: ' . $exception->getMessage());
            $this->jsonError('Lỗi kết nối cơ sở dữ liệu.', 503);
        } catch (Throwable $exception) {
            error_log('API error: ' . $exception->getMessage());
            $this->jsonError('Không thể xử lý yêu cầu lúc này.', 500);
        }
    }

    private function formatPublicOrder(array $order): array
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
            'orderStatus' => $order['order_status'],
            'statusLabel' => OrderWorkflow::statusLabel($order['order_status']),
            'note' => $order['note'],
            'cancelReason' => $order['cancel_reason'],
            'createdAt' => $order['created_at'],
            'deliveredAt' => $order['delivered_at'],
            'customerConfirmedAt' => $order['customer_confirmed_at'],
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

    private function jsonError(string $message, int $code = 400): void
    {
        http_response_code($code);
        echo json_encode(['ok' => false, 'message' => $message], JSON_UNESCAPED_UNICODE);
    }
}
