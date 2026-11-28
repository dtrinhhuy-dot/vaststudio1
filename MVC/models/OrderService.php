<?php

require_once __DIR__ . '/OrderWorkflow.php';
require_once __DIR__ . '/OrderSchemaMigrator.php';
require_once __DIR__ . '/../../includes/Mailer.php';

class OrderService
{
    private PDO $db;
    private Mailer $mailer;

    public function __construct(PDO $db, ?Mailer $mailer = null)
    {
        $this->db = $db;
        $this->mailer = $mailer ?? new Mailer($db);
    }

    public function getStorefrontCatalog(): array
    {
        $productsRows = $this->db->query(
            "SELECT p.*,
                    COALESCE(NULLIF(p.status, ''), IF(p.is_active = 1, 'active', 'draft')) AS computed_status
             FROM products p
             WHERE p.is_active = 1 AND COALESCE(NULLIF(p.status, ''), 'active') = 'active'
             ORDER BY p.sales_count DESC, p.id ASC"
        )->fetchAll();

        $variantsByProduct = $this->loadVariantsMap();

        $products = [];
        foreach ($productsRows as $row) {
            $pid = (int)$row['id'];
            $sizes = json_decode((string)($row['sizes_json'] ?? '[]'), true) ?: ['Freesize'];
            $colors = json_decode((string)($row['colors_json'] ?? '[]'), true) ?: ['Tiêu chuẩn'];
            $gallery = json_decode((string)($row['gallery_json'] ?? '[]'), true);
            $primaryImage = $row['image_url'] ?: ($row['fallback_image'] ?? '');
            if (!is_array($gallery) || !$gallery) {
                $gallery = array_values(array_filter([$primaryImage]));
            }

            $variants = $variantsByProduct[$pid] ?? [];
            $totalVariantStock = $variants
                ? array_sum(array_map(static fn (array $v): int => (int)($v['stock'] ?? $v['stock_quantity'] ?? 0), $variants))
                : (int)$row['stock_quantity'];

            $products[] = [
                'id' => $row['code'] ?: ('PRD-' . $pid),
                'dbId' => $pid,
                'sku' => (string)($row['sku'] ?? ''),
                'name' => (string)$row['name'],
                'category' => (string)($row['category'] ?? 'Thời trang'),
                'collection' => (string)($row['collection_name'] ?? ''),
                'price' => (float)$row['price'],
                'originalPrice' => $row['original_price'] !== null ? (float)$row['original_price'] : null,
                'stock' => max(0, (int)$row['stock_quantity']),
                'variantStockTotal' => $totalVariantStock,
                'sizes' => $sizes,
                'colors' => $colors,
                'variants' => $variants,
                'image' => $primaryImage,
                'fallbackImage' => (string)($row['fallback_image'] ?? ''),
                'gallery' => $gallery,
                'description' => (string)($row['description'] ?? ''),
                'status' => (string)$row['computed_status'],
                'salesCount' => (int)$row['sales_count'],
                'createdAt' => substr((string)($row['created_at'] ?? ''), 0, 10),
            ];
        }

        $collections = $this->db->query(
            "SELECT id, name, slug, description, banner, featured, status
             FROM collections
             WHERE status = 'active'
             ORDER BY featured DESC, created_at DESC"
        )->fetchAll();

        $coupons = $this->db->query(
            "SELECT id, code, description, type, value, min_order AS minOrder, usage_limit AS usageLimit,
                    usage_count AS usageCount, expiry_date AS expiryDate, status
             FROM coupons
             WHERE status = 'active'
             ORDER BY created_at DESC"
        )->fetchAll();
        foreach ($coupons as &$cp) {
            $cp['value'] = (float)$cp['value'];
            $cp['minOrder'] = (float)$cp['minOrder'];
            $cp['usageLimit'] = (int)$cp['usageLimit'];
            $cp['usageCount'] = (int)$cp['usageCount'];
        }
        unset($cp);

        $settings = $this->loadSettings();
        unset($settings['sepayWebhookToken'], $settings['sepayApiToken'], $settings['sepayRequireAuth']);
        require_once __DIR__ . '/SepayService.php';
        $sepayPublic = (new SepayService($this->db))->getPublicConfig();
        $settings['sepay'] = $sepayPublic;
        $settings['bankCode'] = $sepayPublic['bankCode'];
        $settings['bankName'] = $sepayPublic['bankName'];
        $settings['bankAccount'] = $sepayPublic['bankAccount'];
        $settings['bankOwner'] = $sepayPublic['bankOwner'];

        return [
            'products' => $products,
            'collections' => $collections,
            'coupons' => $coupons,
            'settings' => $settings,
        ];
    }

    public function getProductQuickView(string $idOrCode): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM products WHERE code = :code OR id = :num_id LIMIT 1"
        );
        $stmt->execute([
            'code' => $idOrCode,
            'num_id' => ctype_digit($idOrCode) ? (int)$idOrCode : 0,
        ]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }

        $pid = (int)$row['id'];
        $varStmt = $this->db->prepare(
            'SELECT id, size, color, sku_variant, stock_quantity FROM product_variants WHERE product_id = :pid ORDER BY id'
        );
        $varStmt->execute(['pid' => $pid]);
        $variants = $varStmt->fetchAll();

        $sizes = json_decode((string)($row['sizes_json'] ?? '[]'), true) ?: ['Freesize'];
        $colors = json_decode((string)($row['colors_json'] ?? '[]'), true) ?: ['Tiêu chuẩn'];
        $primaryImage = $row['image_url'] ?: ($row['fallback_image'] ?? '');
        $gallery = json_decode((string)($row['gallery_json'] ?? '[]'), true);
        if (!is_array($gallery) || !$gallery) {
            $gallery = array_values(array_unique(array_filter([
                $primaryImage,
                $row['fallback_image'] ?? '',
            ])));
        }

        return [
            'id' => $row['code'] ?: ('PRD-' . $pid),
            'dbId' => $pid,
            'sku' => (string)($row['sku'] ?? ''),
            'name' => (string)$row['name'],
            'category' => (string)($row['category'] ?? 'Thời trang'),
            'collection' => (string)($row['collection_name'] ?? ''),
            'price' => (float)$row['price'],
            'originalPrice' => $row['original_price'] !== null ? (float)$row['original_price'] : null,
            'stock' => max(0, (int)$row['stock_quantity']),
            'sizes' => $sizes,
            'colors' => $colors,
            'variants' => array_map(static fn (array $v): array => [
                'size' => (string)$v['size'],
                'color' => (string)$v['color'],
                'skuVariant' => (string)($v['sku_variant'] ?? ''),
                'stock' => max(0, (int)$v['stock_quantity']),
            ], $variants),
            'image' => $primaryImage,
            'fallbackImage' => (string)($row['fallback_image'] ?? ''),
            'gallery' => $gallery,
            'description' => (string)($row['description'] ?? ''),
            'salesCount' => (int)$row['sales_count'],
        ];
    }

    public function validateCoupon(string $code, float $subtotal): array
    {
        $cleanCode = strtoupper(trim($code));
        if ($cleanCode === '') {
            return ['valid' => false, 'message' => 'Vui lòng nhập mã giảm giá.'];
        }

        $stmt = $this->db->prepare("SELECT * FROM coupons WHERE UPPER(code) = :code AND status = 'active' LIMIT 1");
        $stmt->execute(['code' => $cleanCode]);
        $coupon = $stmt->fetch();
        if (!$coupon) {
            return ['valid' => false, 'message' => 'Mã giảm giá không tồn tại hoặc đã ngừng áp dụng.'];
        }

        if (!empty($coupon['expiry_date']) && $coupon['expiry_date'] < date('Y-m-d')) {
            return ['valid' => false, 'message' => 'Mã giảm giá này đã hết hạn sử dụng.'];
        }
        $limit = (int)($coupon['usage_limit'] ?? 0);
        $used = (int)($coupon['usage_count'] ?? 0);
        if ($limit > 0 && $used >= $limit) {
            return ['valid' => false, 'message' => 'Mã giảm giá này đã hết lượt sử dụng.'];
        }
        $minOrder = (float)($coupon['min_order'] ?? 0);
        if ($subtotal < $minOrder) {
            return [
                'valid' => false,
                'message' => 'Mã ' . $coupon['code'] . ' chỉ áp dụng cho đơn hàng từ ' . number_format($minOrder, 0, ',', '.') . ' ₫.',
            ];
        }

        $discount = $coupon['type'] === 'percent'
            ? round($subtotal * ((float)$coupon['value'] / 100))
            : (float)$coupon['value'];
        $discount = min($subtotal, max(0, $discount));

        return [
            'valid' => true,
            'coupon' => [
                'id' => $coupon['id'],
                'code' => $coupon['code'],
                'type' => $coupon['type'],
                'value' => (float)$coupon['value'],
                'minOrder' => $minOrder,
            ],
            'discount' => $discount,
        ];
    }

    public function createOrderFromCheckout(array $input, ?int $sessionUserId = null): array
    {
        $name = trim((string)($input['customerName'] ?? ''));
        $phone = preg_replace('/[^0-9]/', '', (string)($input['customerPhone'] ?? '')) ?? '';
        if (str_starts_with($phone, '84') && strlen($phone) >= 11) {
            $phone = '0' . substr($phone, 2);
        }
        $email = strtolower(trim((string)($input['customerEmail'] ?? '')));
        $shippingAddress = trim((string)($input['shippingAddress'] ?? ''));
        $shippingMethod = trim((string)($input['shippingMethod'] ?? 'Giao Tiêu Chuẩn (2-3 ngày)'));
        $shippingType = ($input['shippingType'] ?? 'standard') === 'express' ? 'express' : 'standard';
        $paymentCode = strtolower(trim((string)($input['paymentCode'] ?? 'cod')));
        $couponCode = strtoupper(trim((string)($input['couponCode'] ?? '')));
        $note = trim((string)($input['note'] ?? ''));
        $itemsInput = $input['items'] ?? [];

        if ($name === '' || mb_strlen($name) < 2) {
            throw new InvalidArgumentException('Vui lòng nhập họ tên người nhận hợp lệ.');
        }
        if (!preg_match('/^0[0-9]{9,10}$/', $phone)) {
            throw new InvalidArgumentException('Số điện thoại không hợp lệ (10–11 chữ số bắt đầu bằng 0).');
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Email không đúng định dạng.');
        }
        if ($shippingAddress === '') {
            throw new InvalidArgumentException('Vui lòng nhập đầy đủ địa chỉ nhận hàng.');
        }
        if (!is_array($itemsInput) || !$itemsInput) {
            throw new InvalidArgumentException('Giỏ hàng đang trống.');
        }

        $this->db->beginTransaction();
        try {
            $userId = $this->resolveOrderUserId($sessionUserId, $name, $phone, $email);
            $resolvedItems = [];
            $subtotal = 0.0;

            $prodLockStmt = $this->db->prepare(
                'SELECT * FROM products WHERE code = :code OR id = :num_id LIMIT 1 FOR UPDATE'
            );
            $variantLockStmt = $this->db->prepare(
                'SELECT * FROM product_variants WHERE product_id = :pid AND size = :size AND color = :color LIMIT 1 FOR UPDATE'
            );
            $variantDeductStmt = $this->db->prepare(
                'UPDATE product_variants SET stock_quantity = stock_quantity - :qty WHERE id = :vid'
            );
            $prodDeductStmt = $this->db->prepare(
                'UPDATE products SET stock_quantity = GREATEST(0, stock_quantity - :qty), sales_count = sales_count + :sales_qty WHERE id = :pid'
            );

            foreach ($itemsInput as $rawItem) {
                $rawId = (string)($rawItem['id'] ?? $rawItem['productId'] ?? '');
                $qty = max(0, (int)($rawItem['quantity'] ?? 0));
                $size = trim((string)($rawItem['size'] ?? 'Freesize'));
                $color = trim((string)($rawItem['color'] ?? 'Tiêu chuẩn'));
                if ($rawId === '' || $qty <= 0) {
                    throw new InvalidArgumentException('Thông tin sản phẩm trong giỏ hàng không hợp lệ.');
                }

                $prodLockStmt->execute([
                    'code' => $rawId,
                    'num_id' => ctype_digit($rawId) ? (int)$rawId : 0,
                ]);
                $product = $prodLockStmt->fetch();
                if (!$product || (int)$product['is_active'] !== 1) {
                    throw new InvalidArgumentException('Sản phẩm "' . ($rawItem['name'] ?? $rawId) . '" hiện không khả dụng.');
                }

                $pid = (int)$product['id'];
                $variantLockStmt->execute(['pid' => $pid, 'size' => $size, 'color' => $color]);
                $variant = $variantLockStmt->fetch();

                if ($variant) {
                    $avail = (int)$variant['stock_quantity'];
                    if ($avail < $qty) {
                        throw new InvalidArgumentException(
                            "Sản phẩm {$product['name']} ({$size} / {$color}) chỉ còn {$avail} sản phẩm trong kho."
                        );
                    }
                    $variantDeductStmt->execute(['qty' => $qty, 'vid' => (int)$variant['id']]);
                } else {
                    $avail = (int)$product['stock_quantity'];
                    if ($avail < $qty) {
                        throw new InvalidArgumentException(
                            "Sản phẩm {$product['name']} chỉ còn {$avail} sản phẩm trong kho."
                        );
                    }
                }

                $prodDeductStmt->execute(['qty' => $qty, 'sales_qty' => $qty, 'pid' => $pid]);

                $unitPrice = (float)$product['price'];
                $lineTotal = $unitPrice * $qty;
                $subtotal += $lineTotal;

                $resolvedItems[] = [
                    'product_id' => $pid,
                    'product_code' => $product['code'] ?: ('PRD-' . $pid),
                    'product_name' => (string)$product['name'],
                    'product_sku' => (string)($variant['sku_variant'] ?? $product['sku'] ?? ''),
                    'product_image' => (string)($product['image_url'] ?: ($product['fallback_image'] ?? '')),
                    'size' => $size,
                    'color' => $color,
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'total_price' => $lineTotal,
                ];
            }

            $settings = $this->loadSettings();
            $freeShipThreshold = (float)($settings['freeShippingThreshold'] ?? 500000);
            $defaultShipFee = (float)($settings['defaultShippingFee'] ?? 30000);
            $shippingFee = $shippingType === 'express'
                ? 50000.0
                : (($subtotal >= $freeShipThreshold || $subtotal <= 0) ? 0.0 : $defaultShipFee);

            $discount = 0.0;
            $appliedCouponCode = null;
            if ($couponCode !== '') {
                $cpRes = $this->validateCoupon($couponCode, $subtotal);
                if (!$cpRes['valid']) {
                    throw new InvalidArgumentException($cpRes['message']);
                }
                $discount = (float)$cpRes['discount'];
                $appliedCouponCode = (string)$cpRes['coupon']['code'];
                $this->db->prepare('UPDATE coupons SET usage_count = usage_count + 1 WHERE code = :code')
                    ->execute(['code' => $appliedCouponCode]);
            }

            $grandTotal = max(0.0, $subtotal - $discount + $shippingFee);
            $orderCode = $this->generateUniqueOrderCode();

            $insertOrder = $this->db->prepare(
                "INSERT INTO orders
                 (user_id, customer_name, customer_phone, customer_email, order_code, subtotal, shipping_fee,
                  discount_amount, coupon_code, total_amount, current_status, shipping_address, shipping_method,
                  note, stock_deducted)
                 VALUES
                 (:user_id, :customer_name, :customer_phone, :customer_email, :order_code, :subtotal, :shipping_fee,
                  :discount_amount, :coupon_code, :total_amount, :current_status, :shipping_address, :shipping_method,
                  :note, 1)"
            );
            $insertOrder->execute([
                'user_id' => $userId,
                'customer_name' => $name,
                'customer_phone' => $phone,
                'customer_email' => $email !== '' ? $email : null,
                'order_code' => $orderCode,
                'subtotal' => $subtotal,
                'shipping_fee' => $shippingFee,
                'discount_amount' => $discount,
                'coupon_code' => $appliedCouponCode,
                'total_amount' => $grandTotal,
                'current_status' => OrderWorkflow::STATUS_PENDING,
                'shipping_address' => $shippingAddress,
                'shipping_method' => $shippingMethod,
                'note' => $note !== '' ? $note : null,
            ]);
            $orderNumericId = (int)$this->db->lastInsertId();

            $insertItem = $this->db->prepare(
                'INSERT INTO order_items
                 (order_id, product_id, product_name, product_sku, product_image, size, color, quantity, unit_price, total_price)
                 VALUES
                 (:order_id, :product_id, :product_name, :product_sku, :product_image, :size, :color, :quantity, :unit_price, :total_price)'
            );
            foreach ($resolvedItems as $item) {
                $insertItem->execute([
                    'order_id' => $orderNumericId,
                    'product_id' => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'product_sku' => $item['product_sku'],
                    'product_image' => $item['product_image'],
                    'size' => $item['size'],
                    'color' => $item['color'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['total_price'],
                ]);
            }

            $paymentMethodId = $this->resolvePaymentMethodId($paymentCode);
            $this->db->prepare(
                "INSERT INTO payments (order_id, payment_method_id, amount, status)
                 VALUES (:order_id, :pm_id, :amount, 'pending')"
            )->execute([
                'order_id' => $orderNumericId,
                'pm_id' => $paymentMethodId,
                'amount' => $grandTotal,
            ]);

            $this->logStatusHistory(
                $orderNumericId,
                null,
                OrderWorkflow::STATUS_PENDING,
                'Khách hàng đặt đơn hàng mới',
                'customer',
                $userId,
                $name
            );

            $this->createNotification(
                null,
                'admin',
                $orderNumericId,
                "Đơn hàng mới #{$orderCode}",
                "{$name} ({$phone}) vừa đặt đơn trị giá " . $this->money($grandTotal),
                'admin.php?tab=orders&order=' . rawurlencode($orderCode)
            );

            $this->db->commit();
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }

        $detail = $this->getOrderFullDetail($orderCode);
        if ($detail) {
            $this->sendOrderCreatedEmails($detail);
        }

        return $detail ?? ['id' => $orderCode];
    }

    public function getOrderFullDetail(string $orderIdOrCode): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT o.*,
                    o.id AS numeric_id,
                    o.order_code AS id,
                    COALESCE(NULLIF(o.customer_name, ''), NULLIF(u.full_name, ''), u.email, 'Khách hàng') AS resolved_customer_name,
                    COALESCE(NULLIF(o.customer_phone, ''), u.phone, '') AS resolved_customer_phone,
                    COALESCE(NULLIF(o.customer_email, ''), u.email, '') AS resolved_customer_email,
                    (SELECT pm.name FROM payments p JOIN payment_methods pm ON pm.id = p.payment_method_id WHERE p.order_id = o.id ORDER BY p.id DESC LIMIT 1) AS payment_method,
                    COALESCE((SELECT p.status FROM payments p WHERE p.order_id = o.id ORDER BY p.id DESC LIMIT 1), 'pending') AS payment_status,
                    (SELECT p.transaction_code FROM payments p WHERE p.order_id = o.id ORDER BY p.id DESC LIMIT 1) AS payment_transaction_code,
                    (SELECT p.updated_at FROM payments p WHERE p.order_id = o.id ORDER BY p.id DESC LIMIT 1) AS payment_updated_at
             FROM orders o
             LEFT JOIN users u ON u.id = o.user_id
             WHERE o.order_code = :code OR o.id = :num_id
             LIMIT 1"
        );
        $stmt->execute([
            'code' => $orderIdOrCode,
            'num_id' => ctype_digit($orderIdOrCode) ? (int)$orderIdOrCode : 0,
        ]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }

        $numericId = (int)$row['numeric_id'];

        // Immutable item snapshots
        $itemStmt = $this->db->prepare(
            "SELECT oi.id AS order_item_id, oi.product_id, oi.quantity, oi.unit_price AS price, oi.total_price,
                    COALESCE(NULLIF(oi.product_name, ''), p.name, 'Sản phẩm VAST STUDIO') AS name,
                    COALESCE(NULLIF(oi.product_sku, ''), p.sku, '') AS sku,
                    COALESCE(NULLIF(oi.product_image, ''), p.image_url, p.fallback_image, '') AS image,
                    COALESCE(oi.size, '') AS size,
                    COALESCE(oi.color, '') AS color
             FROM order_items oi
             LEFT JOIN products p ON p.id = oi.product_id
             WHERE oi.order_id = :oid
             ORDER BY oi.id ASC"
        );
        $itemStmt->execute(['oid' => $numericId]);
        $items = array_map(static fn (array $item): array => [
            'order_item_id' => (int)$item['order_item_id'],
            'product_id' => (int)$item['product_id'],
            'name' => (string)$item['name'],
            'sku' => (string)$item['sku'],
            'image' => (string)$item['image'],
            'size' => (string)$item['size'],
            'color' => (string)$item['color'],
            'quantity' => (int)$item['quantity'],
            'price' => (float)$item['price'],
            'total_price' => (float)$item['total_price'],
        ], $itemStmt->fetchAll());

        // Status timeline history
        $histStmt = $this->db->prepare(
            "SELECT h.*, COALESCE(NULLIF(h.actor_name, ''), NULLIF(u.full_name, ''), u.email, 'Hệ thống') AS resolved_actor
             FROM order_status_history h
             LEFT JOIN users u ON u.id = COALESCE(h.actor_id, h.admin_id)
             WHERE h.order_id = :oid
             ORDER BY h.created_at ASC, h.id ASC"
        );
        $histStmt->execute(['oid' => $numericId]);
        $history = $histStmt->fetchAll();

        // Cancel requests
        $cancelStmt = $this->db->prepare(
            'SELECT * FROM order_cancel_requests WHERE order_id = :oid ORDER BY created_at DESC, id DESC'
        );
        $cancelStmt->execute(['oid' => $numericId]);
        $cancelRequests = $cancelStmt->fetchAll();

        // Return requests + items + images
        $retStmt = $this->db->prepare(
            'SELECT * FROM order_return_requests WHERE order_id = :oid ORDER BY created_at DESC, id DESC'
        );
        $retStmt->execute(['oid' => $numericId]);
        $returnRequests = $retStmt->fetchAll();
        foreach ($returnRequests as &$req) {
            $req = $this->hydrateReturnRequest($req);
        }
        unset($req);

        // Email logs for this order
        $emailStmt = $this->db->prepare(
            'SELECT id, recipient_email, subject, template_code, status, error_message, attempts, sent_at, last_attempt_at
             FROM email_logs WHERE order_id = :oid ORDER BY id DESC'
        );
        $emailStmt->execute(['oid' => $numericId]);
        $emailLogs = $emailStmt->fetchAll();

        $subtotal = (float)($row['subtotal'] ?? 0);
        if ($subtotal <= 0) {
            $subtotal = array_sum(array_map(static fn (array $i): float => $i['price'] * $i['quantity'], $items));
        }

        return [
            'numeric_id' => $numericId,
            'id' => (string)$row['order_code'],
            'user_id' => (int)$row['user_id'],
            'customer_name' => (string)$row['resolved_customer_name'],
            'customer_phone' => (string)$row['resolved_customer_phone'],
            'customer_email' => (string)$row['resolved_customer_email'],
            'shipping_address' => (string)$row['shipping_address'],
            'shipping_method' => (string)($row['shipping_method'] ?? 'Giao Tiêu Chuẩn (2-3 ngày)'),
            'subtotal' => $subtotal,
            'shipping_fee' => (float)($row['shipping_fee'] ?? 0),
            'discount' => (float)($row['discount_amount'] ?? 0),
            'coupon_code' => $row['coupon_code'] ?? null,
            'total' => (float)$row['total_amount'],
            'refunded_amount' => (float)($row['refunded_amount'] ?? 0),
            'payment_method' => (string)($row['payment_method'] ?? 'COD'),
            'payment_status' => (string)($row['payment_status'] ?? 'pending'),
            'payment_transaction_code' => $row['payment_transaction_code'] ?? null,
            'payment_updated_at' => $row['payment_updated_at'] ?? null,
            'order_status' => (string)$row['current_status'],
            'note' => (string)($row['note'] ?? ''),
            'stock_deducted' => (int)($row['stock_deducted'] ?? 0),
            'pre_cancel_status' => $row['pre_cancel_status'] ?? null,
            'cancel_reason' => $row['cancel_reason'] ?? null,
            'cancelled_by' => $row['cancelled_by'] ?? null,
            'created_at' => (string)$row['created_at'],
            'confirmed_at' => $row['confirmed_at'] ?? null,
            'processing_at' => $row['processing_at'] ?? null,
            'shipped_at' => $row['shipped_at'] ?? null,
            'delivered_at' => $row['delivered_at'] ?? null,
            'customer_confirmed_at' => $row['customer_confirmed_at'] ?? null,
            'cancelled_at' => $row['cancelled_at'] ?? null,
            'items' => $items,
            'history' => $history,
            'cancel_requests' => $cancelRequests,
            'return_requests' => $returnRequests,
            'email_logs' => $emailLogs,
        ];
    }

    public function getCustomerOrdersDetailed(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT order_code FROM orders WHERE user_id = :uid ORDER BY created_at DESC LIMIT 30'
        );
        $stmt->execute(['uid' => $userId]);
        $codes = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $list = [];
        foreach ($codes as $code) {
            $detail = $this->getOrderFullDetail((string)$code);
            if ($detail) {
                $list[] = $detail;
            }
        }
        return $list;
    }

    public function getTrackingOrder(string $query): ?array
    {
        $q = trim($query);
        if ($q === '') {
            return null;
        }
        $stmt = $this->db->prepare(
            "SELECT o.order_code
             FROM orders o
             LEFT JOIN users u ON u.id = o.user_id
             WHERE UPPER(o.order_code) = :code
                OR REPLACE(REPLACE(REPLACE(COALESCE(o.customer_phone, u.phone, ''), ' ', ''), '.', ''), '-', '') = :phone
             ORDER BY o.created_at DESC
             LIMIT 1"
        );
        $cleanPhone = preg_replace('/[^0-9]/', '', $q) ?? '';
        $stmt->execute([
            'code' => strtoupper($q),
            'phone' => $cleanPhone !== '' ? $cleanPhone : '__NONE__',
        ]);
        $orderCode = $stmt->fetchColumn();
        return $orderCode ? $this->getOrderFullDetail((string)$orderCode) : null;
    }

    public function adminAdvanceOrder(string $orderCode, string $targetStatus, ?int $adminId, string $adminName, string $note = ''): array
    {
        $this->db->beginTransaction();
        try {
            $order = $this->lockOrderByCode($orderCode);
            if (!$order) {
                throw new InvalidArgumentException('Không tìm thấy đơn hàng.');
            }
            $current = (string)$order['current_status'];
            if ($targetStatus === OrderWorkflow::STATUS_COMPLETED) {
                throw new InvalidArgumentException('Admin không được phép tự chuyển đơn sang Hoàn tất. Chỉ khách hàng mới có quyền xác nhận đã nhận hàng.');
            }
            if (!OrderWorkflow::canAdminAdvanceTo($current, $targetStatus)) {
                throw new InvalidArgumentException(
                    'Không thể chuyển từ "' . OrderWorkflow::statusLabel($current) . '" sang "' . OrderWorkflow::statusLabel($targetStatus) . '". Quy trình bắt buộc phải đi từng bước.'
                );
            }

            $tsColumn = match ($targetStatus) {
                OrderWorkflow::STATUS_CONFIRMED => 'confirmed_at',
                OrderWorkflow::STATUS_PROCESSING => 'processing_at',
                OrderWorkflow::STATUS_SHIPPING => 'shipped_at',
                OrderWorkflow::STATUS_DELIVERED => 'delivered_at',
                default => null,
            };

            $sql = 'UPDATE orders SET current_status = :status';
            if ($tsColumn !== null) {
                $sql .= ", {$tsColumn} = COALESCE({$tsColumn}, NOW())";
            }
            $sql .= ' WHERE id = :id';
            $this->db->prepare($sql)->execute([
                'status' => $targetStatus,
                'id' => (int)$order['id'],
            ]);

            $finalNote = trim($note) !== ''
                ? trim($note)
                : ('Admin cập nhật trạng thái: ' . OrderWorkflow::statusLabel($targetStatus));

            $historyId = $this->logStatusHistory(
                (int)$order['id'],
                $current,
                $targetStatus,
                $finalNote,
                'admin',
                $adminId,
                $adminName
            );

            if ($targetStatus === OrderWorkflow::STATUS_DELIVERED) {
                $this->createNotification(
                    (int)$order['user_id'],
                    'customer',
                    (int)$order['id'],
                    "Đơn hàng #{$order['order_code']} đã giao thành công",
                    'Kiện hàng đã được giao đến bạn. Vui lòng xác nhận đã nhận hàng hoặc gửi yêu cầu đổi trả nếu có vấn đề.',
                    'auth.php'
                );
            } else {
                $this->createNotification(
                    (int)$order['user_id'],
                    'customer',
                    (int)$order['id'],
                    "Cập nhật đơn hàng #{$order['order_code']}",
                    'Trạng thái đơn hàng đã chuyển sang: ' . OrderWorkflow::statusLabel($targetStatus),
                    'auth.php'
                );
            }

            $this->db->commit();
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }

        $detail = $this->getOrderFullDetail($orderCode);
        if ($detail) {
            $this->sendOrderStatusChangedEmail($detail, $current, $targetStatus, $historyId);
        }
        return $detail ?? [];
    }

    public function adminCancelOrder(string $orderCode, string $reason, ?int $adminId, string $adminName): array
    {
        $reason = trim($reason);
        if ($reason === '') {
            throw new InvalidArgumentException('Vui lòng nhập lý do hủy đơn.');
        }

        $this->db->beginTransaction();
        try {
            $order = $this->lockOrderByCode($orderCode);
            if (!$order) {
                throw new InvalidArgumentException('Không tìm thấy đơn hàng.');
            }
            $current = (string)$order['current_status'];
            if (!OrderWorkflow::canAdminDirectCancel($current)) {
                throw new InvalidArgumentException('Admin chỉ có thể hủy trực tiếp khi đơn ở trạng thái Chờ xác nhận.');
            }

            $this->performCancelEffects($order, $reason, $adminName, 'admin', $adminId, $current);
            $this->db->commit();
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }

        $detail = $this->getOrderFullDetail($orderCode);
        if ($detail) {
            $this->sendOrderCancelledEmail($detail, $reason, $adminName, 'admin_cancel_' . $orderCode);
        }
        return $detail ?? [];
    }

    public function customerCancelOrder(string $orderCode, int $userId, string $reason): array
    {
        $reason = trim($reason);
        if ($reason === '') {
            throw new InvalidArgumentException('Vui lòng nhập lý do hủy đơn.');
        }

        $this->db->beginTransaction();
        try {
            $order = $this->lockOrderByCode($orderCode);
            if (!$order || (int)$order['user_id'] !== $userId) {
                throw new InvalidArgumentException('Bạn không có quyền thao tác trên đơn hàng này.');
            }
            $current = (string)$order['current_status'];
            if (!OrderWorkflow::canCustomerDirectCancel($current)) {
                throw new InvalidArgumentException('Đơn hàng không còn ở trạng thái Chờ xác nhận để hủy trực tiếp.');
            }

            $actorName = (string)($order['customer_name'] ?: 'Khách hàng');
            $this->performCancelEffects($order, $reason, $actorName, 'customer', $userId, $current);
            $this->createNotification(
                null,
                'admin',
                (int)$order['id'],
                "Khách đã hủy đơn #{$orderCode}",
                "{$actorName} đã hủy đơn khi chưa xác nhận. Lý do: {$reason}",
                'admin.php?tab=orders&order=' . rawurlencode($orderCode)
            );
            $this->db->commit();
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }

        $detail = $this->getOrderFullDetail($orderCode);
        if ($detail) {
            $this->sendOrderCancelledEmail($detail, $reason, $detail['customer_name'], 'customer_cancel_' . $orderCode);
        }
        return $detail ?? [];
    }

    public function customerRequestCancel(string $orderCode, int $userId, string $reason): array
    {
        $reason = trim($reason);
        if ($reason === '') {
            throw new InvalidArgumentException('Vui lòng nhập lý do yêu cầu hủy đơn.');
        }

        $cancelReqId = 0;
        $this->db->beginTransaction();
        try {
            $order = $this->lockOrderByCode($orderCode);
            if (!$order || (int)$order['user_id'] !== $userId) {
                throw new InvalidArgumentException('Bạn không có quyền thao tác trên đơn hàng này.');
            }
            $current = (string)$order['current_status'];
            if (!OrderWorkflow::canCustomerRequestCancel($current)) {
                throw new InvalidArgumentException('Chỉ có thể gửi yêu cầu hủy khi đơn hàng ở trạng thái Đã xác nhận (chưa giao vận chuyển).');
            }

            $this->db->prepare(
                'UPDATE orders SET current_status = :status, pre_cancel_status = :prev WHERE id = :id'
            )->execute([
                'status' => OrderWorkflow::STATUS_CANCEL_REQUESTED,
                'prev' => $current,
                'id' => (int)$order['id'],
            ]);

            $this->db->prepare(
                "INSERT INTO order_cancel_requests (order_id, user_id, previous_status, reason, status)
                 VALUES (:oid, :uid, :prev, :reason, 'pending')"
            )->execute([
                'oid' => (int)$order['id'],
                'uid' => $userId,
                'prev' => $current,
                'reason' => $reason,
            ]);
            $cancelReqId = (int)$this->db->lastInsertId();

            $customerName = (string)($order['customer_name'] ?: 'Khách hàng');
            $this->logStatusHistory(
                (int)$order['id'],
                $current,
                OrderWorkflow::STATUS_CANCEL_REQUESTED,
                'Khách hàng gửi yêu cầu hủy: ' . $reason,
                'customer',
                $userId,
                $customerName
            );

            $this->createNotification(
                null,
                'admin',
                (int)$order['id'],
                "Yêu cầu hủy đơn #{$orderCode}",
                "{$customerName} yêu cầu hủy đơn đã xác nhận. Lý do: {$reason}",
                'admin.php?tab=orders&order=' . rawurlencode($orderCode)
            );

            $this->db->commit();
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }

        $detail = $this->getOrderFullDetail($orderCode);
        if ($detail) {
            $this->sendCancelRequestAdminEmail($detail, $reason, $cancelReqId);
        }
        return $detail ?? [];
    }

    public function adminResolveCancelRequest(string $orderCode, string $decision, ?int $adminId, string $adminName, string $note = ''): array
    {
        $decision = strtolower(trim($decision));
        $note = trim($note);
        if (!in_array($decision, ['approve', 'reject'], true)) {
            throw new InvalidArgumentException('Quyết định xử lý yêu cầu hủy không hợp lệ.');
        }
        if ($decision === 'reject' && $note === '') {
            throw new InvalidArgumentException('Bắt buộc nhập lý do khi từ chối yêu cầu hủy đơn.');
        }

        $reqId = 0;
        $cancelReason = '';
        $this->db->beginTransaction();
        try {
            $order = $this->lockOrderByCode($orderCode);
            if (!$order) {
                throw new InvalidArgumentException('Không tìm thấy đơn hàng.');
            }
            if ((string)$order['current_status'] !== OrderWorkflow::STATUS_CANCEL_REQUESTED) {
                throw new InvalidArgumentException('Đơn hàng không đang ở trạng thái Yêu cầu hủy.');
            }

            $reqStmt = $this->db->prepare(
                "SELECT * FROM order_cancel_requests WHERE order_id = :oid AND status = 'pending' ORDER BY id DESC LIMIT 1 FOR UPDATE"
            );
            $reqStmt->execute(['oid' => (int)$order['id']]);
            $req = $reqStmt->fetch();
            $reqId = $req ? (int)$req['id'] : 0;
            $cancelReason = $req ? (string)$req['reason'] : 'Khách hàng yêu cầu hủy';

            if ($decision === 'approve') {
                if ($req) {
                    $this->db->prepare(
                        "UPDATE order_cancel_requests SET status = 'approved', admin_id = :aid, admin_name = :aname, admin_note = :note, responded_at = NOW() WHERE id = :id"
                    )->execute([
                        'aid' => $adminId,
                        'aname' => $adminName,
                        'note' => $note !== '' ? $note : 'Admin phê duyệt yêu cầu hủy đơn',
                        'id' => $reqId,
                    ]);
                }
                $this->performCancelEffects(
                    $order,
                    $cancelReason,
                    $adminName,
                    'admin',
                    $adminId,
                    OrderWorkflow::STATUS_CANCEL_REQUESTED
                );
                $this->createNotification(
                    (int)$order['user_id'],
                    'customer',
                    (int)$order['id'],
                    "Yêu cầu hủy đơn #{$orderCode} được chấp thuận",
                    'Admin đã đồng ý hủy đơn hàng của bạn.' . ($note !== '' ? ' Ghi chú: ' . $note : ''),
                    'auth.php'
                );
            } else {
                $restoreStatus = (string)($order['pre_cancel_status'] ?: ($req['previous_status'] ?? OrderWorkflow::STATUS_CONFIRMED));
                if (!in_array($restoreStatus, [OrderWorkflow::STATUS_PENDING, OrderWorkflow::STATUS_CONFIRMED, OrderWorkflow::STATUS_PROCESSING], true)) {
                    $restoreStatus = OrderWorkflow::STATUS_CONFIRMED;
                }
                if ($req) {
                    $this->db->prepare(
                        "UPDATE order_cancel_requests SET status = 'rejected', admin_id = :aid, admin_name = :aname, admin_note = :note, responded_at = NOW() WHERE id = :id"
                    )->execute([
                        'aid' => $adminId,
                        'aname' => $adminName,
                        'note' => $note,
                        'id' => $reqId,
                    ]);
                }
                $this->db->prepare(
                    'UPDATE orders SET current_status = :status, pre_cancel_status = NULL WHERE id = :id'
                )->execute([
                    'status' => $restoreStatus,
                    'id' => (int)$order['id'],
                ]);

                $this->logStatusHistory(
                    (int)$order['id'],
                    OrderWorkflow::STATUS_CANCEL_REQUESTED,
                    $restoreStatus,
                    'Admin từ chối yêu cầu hủy: ' . $note,
                    'admin',
                    $adminId,
                    $adminName
                );
                $this->createNotification(
                    (int)$order['user_id'],
                    'customer',
                    (int)$order['id'],
                    "Yêu cầu hủy đơn #{$orderCode} bị từ chối",
                    "Lý do từ chối: {$note}. Đơn hàng tiếp tục ở trạng thái " . OrderWorkflow::statusLabel($restoreStatus) . '.',
                    'auth.php'
                );
            }

            $this->db->commit();
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }

        $detail = $this->getOrderFullDetail($orderCode);
        if ($detail) {
            $this->sendCancelDecisionEmail($detail, $decision === 'approve', $note, $reqId);
        }
        return $detail ?? [];
    }

    public function customerConfirmReceived(string $orderCode, int $userId): array
    {
        $this->db->beginTransaction();
        try {
            $order = $this->lockOrderByCode($orderCode);
            if (!$order || (int)$order['user_id'] !== $userId) {
                throw new InvalidArgumentException('Bạn không có quyền xác nhận đơn hàng này.');
            }
            $current = (string)$order['current_status'];
            if (!OrderWorkflow::canCustomerConfirmReceipt($current)) {
                throw new InvalidArgumentException('Chỉ có thể xác nhận nhận hàng khi đơn đã Giao hàng thành công.');
            }

            $this->db->prepare(
                'UPDATE orders SET current_status = :status, customer_confirmed_at = COALESCE(customer_confirmed_at, NOW()) WHERE id = :id'
            )->execute([
                'status' => OrderWorkflow::STATUS_COMPLETED,
                'id' => (int)$order['id'],
            ]);

            $this->db->prepare(
                "UPDATE payments SET status = 'paid' WHERE order_id = :oid AND status = 'pending'"
            )->execute(['oid' => (int)$order['id']]);

            $customerName = (string)($order['customer_name'] ?: 'Khách hàng');
            $this->logStatusHistory(
                (int)$order['id'],
                $current,
                OrderWorkflow::STATUS_COMPLETED,
                'Khách hàng xác nhận đã nhận đủ hàng',
                'customer',
                $userId,
                $customerName
            );

            $this->createNotification(
                null,
                'admin',
                (int)$order['id'],
                "Đơn #{$orderCode} đã hoàn tất",
                "{$customerName} đã xác nhận nhận hàng. Doanh thu " . $this->money((float)$order['total_amount']) . ' đã được ghi nhận.',
                'admin.php?tab=orders&order=' . rawurlencode($orderCode)
            );

            $this->db->commit();
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }

        $detail = $this->getOrderFullDetail($orderCode);
        if ($detail) {
            $this->sendOrderCompletedEmails($detail);
        }
        return $detail ?? [];
    }

    public function customerSubmitReturn(
        string $orderCode,
        int $userId,
        array $selectedQuantities,
        string $reasonCode,
        string $description,
        array $filesInput
    ): array {
        $reasons = OrderWorkflow::returnReasons();
        if (!isset($reasons[$reasonCode])) {
            throw new InvalidArgumentException('Vui lòng chọn lý do đổi trả hợp lệ.');
        }
        $description = trim($description);
        if ($description === '' || mb_strlen($description) < 10) {
            throw new InvalidArgumentException('Vui lòng nhập mô tả chi tiết tình trạng sản phẩm (ít nhất 10 ký tự).');
        }

        $uploadedFiles = $this->validateAndStageReturnImages($filesInput);
        if (!$uploadedFiles) {
            throw new InvalidArgumentException('Vui lòng đính kèm ít nhất 1 hình ảnh sản phẩm để Admin kiểm tra.');
        }

        $savedPaths = [];
        $returnCode = 'RET-' . strtoupper(bin2hex(random_bytes(3)));

        $this->db->beginTransaction();
        try {
            $order = $this->lockOrderByCode($orderCode);
            if (!$order || (int)$order['user_id'] !== $userId) {
                throw new InvalidArgumentException('Bạn không có quyền gửi yêu cầu đổi trả cho đơn hàng này.');
            }
            $current = (string)$order['current_status'];
            if (!OrderWorkflow::canCustomerRequestReturn($current)) {
                throw new InvalidArgumentException('Chỉ có thể yêu cầu đổi trả khi đơn ở trạng thái Giao hàng thành công hoặc Chờ xác nhận nhận hàng.');
            }

            $activeRet = $this->db->prepare(
                "SELECT id FROM order_return_requests WHERE order_id = :oid AND status IN ('pending_review','approved','awaiting_shipment','received','processing_exchange') LIMIT 1"
            );
            $activeRet->execute(['oid' => (int)$order['id']]);
            if ($activeRet->fetchColumn()) {
                throw new InvalidArgumentException('Đơn hàng này đang có yêu cầu đổi trả đang được xử lý.');
            }

            $itemRowsStmt = $this->db->prepare(
                "SELECT oi.*, COALESCE(NULLIF(oi.product_name, ''), p.name, 'Sản phẩm') AS snap_name,
                        COALESCE(NULLIF(oi.product_sku, ''), p.sku, '') AS snap_sku
                 FROM order_items oi
                 LEFT JOIN products p ON p.id = oi.product_id
                 WHERE oi.order_id = :oid"
            );
            $itemRowsStmt->execute(['oid' => (int)$order['id']]);
            $orderItemsById = [];
            foreach ($itemRowsStmt->fetchAll() as $ir) {
                $orderItemsById[(int)$ir['id']] = $ir;
            }

            $chosenItems = [];
            foreach ($selectedQuantities as $itemId => $qty) {
                $iid = (int)$itemId;
                $q = (int)$qty;
                if ($q <= 0) {
                    continue;
                }
                if (!isset($orderItemsById[$iid])) {
                    throw new InvalidArgumentException('Sản phẩm đổi trả không thuộc đơn hàng này.');
                }
                $orig = $orderItemsById[$iid];
                if ($q > (int)$orig['quantity']) {
                    throw new InvalidArgumentException('Số lượng đổi trả vượt quá số lượng đã mua cho "' . $orig['snap_name'] . '".');
                }
                $chosenItems[] = [
                    'order_item_id' => $iid,
                    'product_id' => (int)$orig['product_id'],
                    'product_name' => (string)$orig['snap_name'],
                    'product_sku' => (string)$orig['snap_sku'],
                    'size' => (string)($orig['size'] ?? ''),
                    'color' => (string)($orig['color'] ?? ''),
                    'quantity' => $q,
                    'unit_price' => (float)$orig['unit_price'],
                ];
            }

            if (!$chosenItems) {
                throw new InvalidArgumentException('Vui lòng chọn ít nhất 1 sản phẩm và số lượng cần đổi trả.');
            }

            $this->db->prepare(
                "INSERT INTO order_return_requests
                 (return_code, order_id, user_id, reason_code, reason_text, description, status)
                 VALUES
                 (:code, :oid, :uid, :rcode, :rtext, :desc, :status)"
            )->execute([
                'code' => $returnCode,
                'oid' => (int)$order['id'],
                'uid' => $userId,
                'rcode' => $reasonCode,
                'rtext' => $reasons[$reasonCode],
                'desc' => $description,
                'status' => OrderWorkflow::RETURN_PENDING_REVIEW,
            ]);
            $returnReqId = (int)$this->db->lastInsertId();

            $insItem = $this->db->prepare(
                'INSERT INTO order_return_items
                 (return_request_id, order_item_id, product_id, product_name, product_sku, size, color, quantity, unit_price)
                 VALUES
                 (:req_id, :oi_id, :pid, :pname, :psku, :size, :color, :qty, :price)'
            );
            foreach ($chosenItems as $ci) {
                $insItem->execute([
                    'req_id' => $returnReqId,
                    'oi_id' => $ci['order_item_id'],
                    'pid' => $ci['product_id'],
                    'pname' => $ci['product_name'],
                    'psku' => $ci['product_sku'],
                    'size' => $ci['size'],
                    'color' => $ci['color'],
                    'qty' => $ci['quantity'],
                    'price' => $ci['unit_price'],
                ]);
            }

            $savedPaths = $this->persistReturnImages($uploadedFiles, $returnCode);
            $insImg = $this->db->prepare(
                'INSERT INTO order_return_images (return_request_id, file_path, original_name, mime_type, file_size)
                 VALUES (:req_id, :path, :orig, :mime, :size)'
            );
            foreach ($savedPaths as $imgMeta) {
                $insImg->execute([
                    'req_id' => $returnReqId,
                    'path' => $imgMeta['relative_path'],
                    'orig' => $imgMeta['original_name'],
                    'mime' => $imgMeta['mime_type'],
                    'size' => $imgMeta['file_size'],
                ]);
            }

            $this->db->prepare(
                'UPDATE orders SET current_status = :status WHERE id = :id'
            )->execute([
                'status' => OrderWorkflow::STATUS_RETURN_REQUESTED,
                'id' => (int)$order['id'],
            ]);

            $customerName = (string)($order['customer_name'] ?: 'Khách hàng');
            $this->logStatusHistory(
                (int)$order['id'],
                $current,
                OrderWorkflow::STATUS_RETURN_REQUESTED,
                "Khách gửi yêu cầu đổi trả ({$returnCode}): {$reasons[$reasonCode]}",
                'customer',
                $userId,
                $customerName
            );

            $this->createNotification(
                null,
                'admin',
                (int)$order['id'],
                "Yêu cầu đổi trả mới {$returnCode}",
                "{$customerName} gửi yêu cầu đổi trả đơn #{$orderCode}: {$reasons[$reasonCode]}",
                'admin.php?tab=returns&return=' . rawurlencode($returnCode)
            );

            $this->db->commit();
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            foreach ($savedPaths as $sp) {
                @unlink($sp['abs_path']);
            }
            throw $exception;
        }

        $detail = $this->getOrderFullDetail($orderCode);
        if ($detail) {
            $ret = $this->getReturnRequestByCode($returnCode);
            if ($ret) {
                $this->sendReturnRequestedAdminEmail($detail, $ret);
            }
        }
        return $detail ?? [];
    }

    public function getReturnRequests(string $statusFilter = 'all'): array
    {
        $sql = "SELECT r.*, o.order_code,
                       COALESCE(NULLIF(o.customer_name, ''), NULLIF(u.full_name, ''), u.email) AS customer_name,
                       COALESCE(NULLIF(o.customer_phone, ''), u.phone) AS customer_phone,
                       COALESCE(NULLIF(o.customer_email, ''), u.email) AS customer_email,
                       o.total_amount AS order_total
                FROM order_return_requests r
                JOIN orders o ON o.id = r.order_id
                LEFT JOIN users u ON u.id = r.user_id
                WHERE 1 = 1";
        $params = [];
        if ($statusFilter !== 'all') {
            $sql .= ' AND r.status = :status';
            $params['status'] = $statusFilter;
        }
        $sql .= ' ORDER BY r.created_at DESC, r.id DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
        foreach ($rows as &$row) {
            $row = $this->hydrateReturnRequest($row);
        }
        unset($row);
        return $rows;
    }

    public function getReturnRequestByCode(string $returnCode): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT r.*, o.order_code,
                    COALESCE(NULLIF(o.customer_name, ''), NULLIF(u.full_name, ''), u.email) AS customer_name,
                    COALESCE(NULLIF(o.customer_phone, ''), u.phone) AS customer_phone,
                    COALESCE(NULLIF(o.customer_email, ''), u.email) AS customer_email,
                    o.total_amount AS order_total
             FROM order_return_requests r
             JOIN orders o ON o.id = r.order_id
             LEFT JOIN users u ON u.id = r.user_id
             WHERE r.return_code = :code
             LIMIT 1"
        );
        $stmt->execute(['code' => $returnCode]);
        $row = $stmt->fetch();
        return $row ? $this->hydrateReturnRequest($row) : null;
    }

    public function adminApproveReturn(string $returnCode, ?int $adminId, string $adminName, string $adminNote = ''): array
    {
        $this->db->beginTransaction();
        try {
            $req = $this->lockReturnByCode($returnCode);
            if (!$req) {
                throw new InvalidArgumentException('Không tìm thấy yêu cầu đổi trả.');
            }
            if ((string)$req['status'] !== OrderWorkflow::RETURN_PENDING_REVIEW) {
                throw new InvalidArgumentException('Yêu cầu đổi trả này không ở trạng thái Chờ duyệt.');
            }

            $this->db->prepare(
                "UPDATE order_return_requests
                 SET status = :status, admin_id = :aid, admin_name = :aname, admin_note = :note, approved_at = NOW()
                 WHERE id = :id"
            )->execute([
                'status' => OrderWorkflow::RETURN_AWAITING_SHIPMENT,
                'aid' => $adminId,
                'aname' => $adminName,
                'note' => trim($adminNote) !== '' ? trim($adminNote) : 'Đã chấp thuận đổi trả, chờ khách gửi hàng về.',
                'id' => (int)$req['id'],
            ]);

            $this->db->prepare(
                'UPDATE orders SET current_status = :status WHERE id = :oid'
            )->execute([
                'status' => OrderWorkflow::STATUS_RETURN_AWAITING_SHIPMENT,
                'oid' => (int)$req['order_id'],
            ]);

            $this->logStatusHistory(
                (int)$req['order_id'],
                OrderWorkflow::STATUS_RETURN_REQUESTED,
                OrderWorkflow::STATUS_RETURN_AWAITING_SHIPMENT,
                "Admin duyệt yêu cầu đổi trả {$returnCode}. Chờ khách gửi hàng về.",
                'admin',
                $adminId,
                $adminName
            );

            $this->createNotification(
                (int)$req['user_id'],
                'customer',
                (int)$req['order_id'],
                "Yêu cầu đổi trả {$returnCode} đã được chấp thuận",
                'Vui lòng gửi sản phẩm đổi trả về showroom VAST STUDIO theo hướng dẫn trong email.',
                'auth.php'
            );

            $this->db->commit();
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }

        $hydrated = $this->getReturnRequestByCode($returnCode);
        if ($hydrated) {
            $detail = $this->getOrderFullDetail((string)$hydrated['order_code']);
            if ($detail) {
                $this->sendReturnDecisionEmail($detail, $hydrated, 'approved');
            }
        }
        return $hydrated ?? [];
    }

    public function adminRejectReturn(string $returnCode, string $rejectReason, ?int $adminId, string $adminName): array
    {
        $rejectReason = trim($rejectReason);
        if ($rejectReason === '') {
            throw new InvalidArgumentException('Bắt buộc nhập lý do từ chối yêu cầu đổi trả.');
        }

        $this->db->beginTransaction();
        try {
            $req = $this->lockReturnByCode($returnCode);
            if (!$req) {
                throw new InvalidArgumentException('Không tìm thấy yêu cầu đổi trả.');
            }
            if ((string)$req['status'] !== OrderWorkflow::RETURN_PENDING_REVIEW) {
                throw new InvalidArgumentException('Yêu cầu đổi trả này không ở trạng thái Chờ duyệt.');
            }

            $this->db->prepare(
                "UPDATE order_return_requests
                 SET status = :status, reject_reason = :reason, admin_id = :aid, admin_name = :aname
                 WHERE id = :id"
            )->execute([
                'status' => OrderWorkflow::RETURN_REJECTED,
                'reason' => $rejectReason,
                'aid' => $adminId,
                'aname' => $adminName,
                'id' => (int)$req['id'],
            ]);

            $this->db->prepare(
                'UPDATE orders SET current_status = :status WHERE id = :oid'
            )->execute([
                'status' => OrderWorkflow::STATUS_AWAITING_CONFIRMATION,
                'oid' => (int)$req['order_id'],
            ]);

            $this->logStatusHistory(
                (int)$req['order_id'],
                OrderWorkflow::STATUS_RETURN_REQUESTED,
                OrderWorkflow::STATUS_AWAITING_CONFIRMATION,
                "Admin từ chối đổi trả {$returnCode}: {$rejectReason}",
                'admin',
                $adminId,
                $adminName
            );

            $this->createNotification(
                (int)$req['user_id'],
                'customer',
                (int)$req['order_id'],
                "Yêu cầu đổi trả {$returnCode} bị từ chối",
                "Lý do từ chối: {$rejectReason}",
                'auth.php'
            );

            $this->db->commit();
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }

        $hydrated = $this->getReturnRequestByCode($returnCode);
        if ($hydrated) {
            $detail = $this->getOrderFullDetail((string)$hydrated['order_code']);
            if ($detail) {
                $this->sendReturnDecisionEmail($detail, $hydrated, 'rejected');
            }
        }
        return $hydrated ?? [];
    }

    public function adminConfirmReturnReceived(string $returnCode, ?int $adminId, string $adminName, string $conditionNote = ''): array
    {
        $this->db->beginTransaction();
        try {
            $req = $this->lockReturnByCode($returnCode);
            if (!$req) {
                throw new InvalidArgumentException('Không tìm thấy yêu cầu đổi trả.');
            }
            if (!in_array((string)$req['status'], [OrderWorkflow::RETURN_APPROVED, OrderWorkflow::RETURN_AWAITING_SHIPMENT], true)) {
                throw new InvalidArgumentException('Yêu cầu đổi trả chưa ở trạng thái chờ nhận hàng.');
            }

            $note = trim($conditionNote) !== '' ? trim($conditionNote) : 'Đã nhận hàng đổi trả tại kho và đang kiểm tra tình trạng.';
            $this->db->prepare(
                "UPDATE order_return_requests
                 SET status = :status, received_at = NOW(), admin_id = :aid, admin_name = :aname, admin_note = :note
                 WHERE id = :id"
            )->execute([
                'status' => OrderWorkflow::RETURN_RECEIVED,
                'aid' => $adminId,
                'aname' => $adminName,
                'note' => $note,
                'id' => (int)$req['id'],
            ]);

            $this->db->prepare(
                'UPDATE orders SET current_status = :status WHERE id = :oid'
            )->execute([
                'status' => OrderWorkflow::STATUS_RETURN_RECEIVED,
                'oid' => (int)$req['order_id'],
            ]);

            $this->logStatusHistory(
                (int)$req['order_id'],
                OrderWorkflow::STATUS_RETURN_AWAITING_SHIPMENT,
                OrderWorkflow::STATUS_RETURN_RECEIVED,
                "Admin xác nhận đã nhận hàng đổi trả ({$returnCode}): {$note}",
                'admin',
                $adminId,
                $adminName
            );

            $this->createNotification(
                (int)$req['user_id'],
                'customer',
                (int)$req['order_id'],
                "Đã nhận hàng đổi trả {$returnCode}",
                'VAST STUDIO đã nhận được kiện hàng gửi về và đang kiểm tra tình trạng để đổi hàng/hoàn tiền.',
                'auth.php'
            );

            $this->db->commit();
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }

        $hydrated = $this->getReturnRequestByCode($returnCode);
        if ($hydrated) {
            $detail = $this->getOrderFullDetail((string)$hydrated['order_code']);
            if ($detail) {
                $this->sendReturnDecisionEmail($detail, $hydrated, 'received');
            }
        }
        return $hydrated ?? [];
    }

    public function adminCompleteReturn(
        string $returnCode,
        string $resolutionType,
        bool $restockApproved,
        ?int $adminId,
        string $adminName,
        string $note = ''
    ): array {
        if (!in_array($resolutionType, ['exchange', 'refund'], true)) {
            throw new InvalidArgumentException('Phương án xử lý đổi trả phải là Đổi hàng hoặc Hoàn tiền.');
        }

        $this->db->beginTransaction();
        try {
            $req = $this->lockReturnByCode($returnCode);
            if (!$req) {
                throw new InvalidArgumentException('Không tìm thấy yêu cầu đổi trả.');
            }
            if (!in_array((string)$req['status'], [OrderWorkflow::RETURN_RECEIVED, OrderWorkflow::RETURN_PROCESSING_EXCHANGE], true)) {
                throw new InvalidArgumentException('Chỉ hoàn tất đổi trả sau khi Admin đã xác nhận nhận hàng đổi trả.');
            }

            $itemsStmt = $this->db->prepare('SELECT * FROM order_return_items WHERE return_request_id = :rid');
            $itemsStmt->execute(['rid' => (int)$req['id']]);
            $retItems = $itemsStmt->fetchAll();

            $refundAmount = 0.0;
            if ($resolutionType === 'refund') {
                foreach ($retItems as $ri) {
                    $refundAmount += ((float)$ri['unit_price'] * (int)$ri['quantity']);
                }
            }

            if ($restockApproved) {
                $restockProd = $this->db->prepare('UPDATE products SET stock_quantity = stock_quantity + :qty WHERE id = :pid');
                $restockVar = $this->db->prepare(
                    'UPDATE product_variants SET stock_quantity = stock_quantity + :qty WHERE product_id = :pid AND size = :size AND color = :color'
                );
                $markItemRestocked = $this->db->prepare('UPDATE order_return_items SET restocked = 1 WHERE id = :id');
                foreach ($retItems as $ri) {
                    if ((int)$ri['restocked'] === 1) {
                        continue;
                    }
                    $qty = (int)$ri['quantity'];
                    $pid = (int)$ri['product_id'];
                    $restockProd->execute(['qty' => $qty, 'pid' => $pid]);
                    if (!empty($ri['size']) && !empty($ri['color'])) {
                        $restockVar->execute([
                            'qty' => $qty,
                            'pid' => $pid,
                            'size' => $ri['size'],
                            'color' => $ri['color'],
                        ]);
                    }
                    $markItemRestocked->execute(['id' => (int)$ri['id']]);
                }
            }

            $finalNote = trim($note) !== ''
                ? trim($note)
                : ($resolutionType === 'refund'
                    ? 'Hoàn tất đổi trả (Hoàn tiền ' . $this->money($refundAmount) . ')'
                    : 'Hoàn tất đổi trả (Đã gửi sản phẩm đổi mới)');

            $this->db->prepare(
                "UPDATE order_return_requests
                 SET status = :status, resolution_type = :res, refund_amount = :refund,
                     admin_id = :aid, admin_name = :aname, admin_note = :note, completed_at = NOW()
                 WHERE id = :id"
            )->execute([
                'status' => OrderWorkflow::RETURN_COMPLETED,
                'res' => $resolutionType,
                'refund' => $refundAmount,
                'aid' => $adminId,
                'aname' => $adminName,
                'note' => $finalNote,
                'id' => (int)$req['id'],
            ]);

            $this->db->prepare(
                'UPDATE orders SET current_status = :status, refunded_amount = refunded_amount + :refund WHERE id = :oid'
            )->execute([
                'status' => OrderWorkflow::STATUS_RETURN_COMPLETED,
                'refund' => $refundAmount,
                'oid' => (int)$req['order_id'],
            ]);

            if ($resolutionType === 'refund') {
                $this->db->prepare(
                    "UPDATE payments SET status = 'refunded' WHERE order_id = :oid AND status = 'paid'"
                )->execute(['oid' => (int)$req['order_id']]);
            }

            $this->logStatusHistory(
                (int)$req['order_id'],
                OrderWorkflow::STATUS_RETURN_RECEIVED,
                OrderWorkflow::STATUS_RETURN_COMPLETED,
                $finalNote . ($restockApproved ? ' (Đã kiểm tra & nhập lại tồn kho)' : ' (Không nhập lại kho)'),
                'admin',
                $adminId,
                $adminName
            );

            $this->createNotification(
                (int)$req['user_id'],
                'customer',
                (int)$req['order_id'],
                "Đổi trả {$returnCode} đã hoàn tất",
                $finalNote,
                'auth.php'
            );

            $this->db->commit();
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }

        $hydrated = $this->getReturnRequestByCode($returnCode);
        if ($hydrated) {
            $detail = $this->getOrderFullDetail((string)$hydrated['order_code']);
            if ($detail) {
                $this->sendReturnDecisionEmail($detail, $hydrated, 'completed');
            }
        }
        return $hydrated ?? [];
    }

    public function getNotifications(string $role, ?int $userId = null, int $limit = 15): array
    {
        if ($role === 'admin') {
            $stmt = $this->db->prepare(
                "SELECT * FROM notifications WHERE recipient_role = 'admin' ORDER BY is_read ASC, created_at DESC LIMIT :lim"
            );
            $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        }

        if (!$userId) {
            return [];
        }
        $stmt = $this->db->prepare(
            "SELECT * FROM notifications WHERE recipient_role = 'customer' AND user_id = :uid ORDER BY is_read ASC, created_at DESC LIMIT :lim"
        );
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function markNotificationsRead(string $role, ?int $userId = null): void
    {
        if ($role === 'admin') {
            $this->db->exec("UPDATE notifications SET is_read = 1 WHERE recipient_role = 'admin' AND is_read = 0");
            return;
        }
        if ($userId) {
            $this->db->prepare("UPDATE notifications SET is_read = 1 WHERE recipient_role = 'customer' AND user_id = :uid AND is_read = 0")
                ->execute(['uid' => $userId]);
        }
    }

    public function getEmailLogs(string $statusFilter = 'all', int $limit = 100): array
    {
        $sql = "SELECT e.*, o.order_code
                FROM email_logs e
                LEFT JOIN orders o ON o.id = e.order_id
                WHERE 1 = 1";
        $params = [];
        if ($statusFilter !== 'all') {
            $sql .= ' AND e.status = :status';
            $params['status'] = $statusFilter;
        }
        $sql .= ' ORDER BY e.id DESC LIMIT ' . max(1, $limit);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function retryEmailLog(int $logId): array
    {
        return $this->mailer->retry($logId);
    }

    private function performCancelEffects(
        array $order,
        string $reason,
        string $actorName,
        string $actorType,
        ?int $actorId,
        string $fromStatus
    ): void {
        $oid = (int)$order['id'];

        if ((int)($order['stock_deducted'] ?? 0) === 1) {
            $items = $this->db->prepare('SELECT product_id, size, color, quantity FROM order_items WHERE order_id = :oid');
            $items->execute(['oid' => $oid]);
            $prodRestock = $this->db->prepare(
                'UPDATE products SET stock_quantity = stock_quantity + :qty, sales_count = GREATEST(0, sales_count - :sqty) WHERE id = :pid'
            );
            $varRestock = $this->db->prepare(
                'UPDATE product_variants SET stock_quantity = stock_quantity + :qty WHERE product_id = :pid AND size = :size AND color = :color'
            );
            foreach ($items->fetchAll() as $it) {
                $qty = (int)$it['quantity'];
                $pid = (int)$it['product_id'];
                $prodRestock->execute(['qty' => $qty, 'sqty' => $qty, 'pid' => $pid]);
                if (!empty($it['size']) && !empty($it['color'])) {
                    $varRestock->execute([
                        'qty' => $qty,
                        'pid' => $pid,
                        'size' => $it['size'],
                        'color' => $it['color'],
                    ]);
                }
            }
        }

        $this->db->prepare(
            "UPDATE orders
             SET current_status = :status,
                 pre_cancel_status = NULL,
                 stock_deducted = 0,
                 cancel_reason = :reason,
                 cancelled_by = :by,
                 cancelled_at = COALESCE(cancelled_at, NOW())
             WHERE id = :id"
        )->execute([
            'status' => OrderWorkflow::STATUS_CANCELLED,
            'reason' => $reason,
            'by' => $actorName,
            'id' => $oid,
        ]);

        $refundStmt = $this->db->prepare(
            "UPDATE payments SET status = 'refunded' WHERE order_id = :oid AND status = 'paid'"
        );
        $refundStmt->execute(['oid' => $oid]);
        if ($refundStmt->rowCount() > 0) {
            $this->db->prepare(
                'UPDATE orders SET refunded_amount = GREATEST(refunded_amount, total_amount) WHERE id = :oid'
            )->execute(['oid' => $oid]);
        }

        $this->logStatusHistory(
            $oid,
            $fromStatus,
            OrderWorkflow::STATUS_CANCELLED,
            "Đã hủy đơn ({$actorName}): {$reason}",
            $actorType,
            $actorId,
            $actorName
        );
    }

    private function validateAndStageReturnImages(array $filesInput): array
    {
        if (empty($filesInput) || !isset($filesInput['name'])) {
            return [];
        }

        $names = is_array($filesInput['name']) ? $filesInput['name'] : [$filesInput['name']];
        $tmpNames = is_array($filesInput['tmp_name']) ? $filesInput['tmp_name'] : [$filesInput['tmp_name']];
        $errors = is_array($filesInput['error']) ? $filesInput['error'] : [$filesInput['error']];
        $sizes = is_array($filesInput['size']) ? $filesInput['size'] : [$filesInput['size']];

        $allowedMimes = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];
        $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : null;
        $staged = [];

        foreach ($names as $idx => $origName) {
            $err = (int)($errors[$idx] ?? UPLOAD_ERR_NO_FILE);
            if ($err === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            if ($err !== UPLOAD_ERR_OK) {
                throw new InvalidArgumentException('Tải ảnh lên thất bại (mã lỗi ' . $err . ').');
            }
            $tmp = (string)($tmpNames[$idx] ?? '');
            $size = (int)($sizes[$idx] ?? 0);
            if ($tmp === '' || !is_file($tmp)) {
                throw new InvalidArgumentException('Không tìm thấy tệp ảnh tạm thời.');
            }
            if ($size <= 0 || $size > 5 * 1024 * 1024) {
                throw new InvalidArgumentException('Mỗi ảnh đổi trả phải có dung lượng tối đa 5MB.');
            }

            $mime = $finfo ? (string)finfo_file($finfo, $tmp) : (string)(@mime_content_type($tmp) ?: '');
            if (!isset($allowedMimes[$mime])) {
                throw new InvalidArgumentException('Chỉ chấp nhận ảnh định dạng JPG, PNG hoặc WEBP.');
            }
            $imgInfo = @getimagesize($tmp);
            if ($imgInfo === false) {
                throw new InvalidArgumentException('Tệp "' . $origName . '" không phải là hình ảnh hợp lệ.');
            }

            $staged[] = [
                'tmp_path' => $tmp,
                'original_name' => basename((string)$origName),
                'mime_type' => $mime,
                'ext' => $allowedMimes[$mime],
                'file_size' => $size,
            ];
            if (count($staged) >= 5) {
                break;
            }
        }

        if ($finfo) {
            finfo_close($finfo);
        }
        return $staged;
    }

    private function persistReturnImages(array $stagedFiles, string $returnCode): array
    {
        $uploadRoot = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'returns';
        if (!is_dir($uploadRoot) && !@mkdir($uploadRoot, 0775, true) && !is_dir($uploadRoot)) {
            throw new RuntimeException('Không thể tạo thư mục lưu ảnh đổi trả.');
        }

        $saved = [];
        foreach ($stagedFiles as $idx => $file) {
            $filename = strtolower($returnCode) . '-' . ($idx + 1) . '-' . bin2hex(random_bytes(4)) . '.' . $file['ext'];
            $dest = $uploadRoot . DIRECTORY_SEPARATOR . $filename;
            $moved = is_uploaded_file($file['tmp_path'])
                ? @move_uploaded_file($file['tmp_path'], $dest)
                : @copy($file['tmp_path'], $dest);

            if (!$moved) {
                throw new RuntimeException('Không thể lưu ảnh đổi trả lên máy chủ.');
            }
            $saved[] = [
                'abs_path' => $dest,
                'relative_path' => 'uploads/returns/' . $filename,
                'original_name' => $file['original_name'],
                'mime_type' => $file['mime_type'],
                'file_size' => $file['file_size'],
            ];
        }
        return $saved;
    }

    private function hydrateReturnRequest(array $row): array
    {
        $rid = (int)$row['id'];
        $items = $this->db->prepare('SELECT * FROM order_return_items WHERE return_request_id = :rid ORDER BY id');
        $items->execute(['rid' => $rid]);
        $row['items'] = $items->fetchAll();

        $imgs = $this->db->prepare('SELECT * FROM order_return_images WHERE return_request_id = :rid ORDER BY id');
        $imgs->execute(['rid' => $rid]);
        $row['images'] = $imgs->fetchAll();
        return $row;
    }

    private function lockOrderByCode(string $orderCode): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT o.*, COALESCE(NULLIF(o.customer_name, ''), u.full_name, u.email) AS customer_name
             FROM orders o
             LEFT JOIN users u ON u.id = o.user_id
             WHERE o.order_code = :code
             LIMIT 1 FOR UPDATE"
        );
        $stmt->execute(['code' => $orderCode]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    private function lockReturnByCode(string $returnCode): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM order_return_requests WHERE return_code = :code LIMIT 1 FOR UPDATE'
        );
        $stmt->execute(['code' => $returnCode]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    private function resolveExistingUserId(?int $userId): ?int
    {
        if ($userId === null || $userId <= 0) {
            return null;
        }
        $stmt = $this->db->prepare('SELECT id FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $userId]);
        $found = $stmt->fetchColumn();
        return $found !== false ? (int)$found : null;
    }

    private function logStatusHistory(
        int $orderId,
        ?string $fromStatus,
        string $toStatus,
        string $note,
        string $actorType,
        ?int $actorId,
        string $actorName
    ): int {
        $resolvedActorId = $this->resolveExistingUserId($actorId);
        $stmt = $this->db->prepare(
            'INSERT INTO order_status_history (order_id, admin_id, from_status, actor_type, actor_id, actor_name, status, note)
             VALUES (:oid, :admin_id, :from_status, :actor_type, :actor_id, :actor_name, :status, :note)'
        );
        $stmt->execute([
            'oid' => $orderId,
            'admin_id' => $actorType === 'admin' ? $resolvedActorId : null,
            'from_status' => $fromStatus,
            'actor_type' => $actorType,
            'actor_id' => $resolvedActorId,
            'actor_name' => $actorName,
            'status' => $toStatus,
            'note' => $note,
        ]);
        return (int)$this->db->lastInsertId();
    }

    private function createNotification(
        ?int $userId,
        string $role,
        ?int $orderId,
        string $title,
        string $message,
        string $link
    ): void {
        $this->db->prepare(
            'INSERT INTO notifications (user_id, recipient_role, order_id, title, message, link)
             VALUES (:uid, :role, :oid, :title, :msg, :link)'
        )->execute([
            'uid' => $userId,
            'role' => $role,
            'oid' => $orderId,
            'title' => $title,
            'msg' => $message,
            'link' => $link,
        ]);
    }

    private function resolveOrderUserId(?int $sessionUserId, string $name, string $phone, string $email): int
    {
        if ($sessionUserId && $sessionUserId > 0) {
            $check = $this->db->prepare('SELECT id FROM users WHERE id = :id LIMIT 1');
            $check->execute(['id' => $sessionUserId]);
            if ($check->fetchColumn()) {
                return $sessionUserId;
            }
        }

        if ($email !== '') {
            $stmt = $this->db->prepare('SELECT id FROM users WHERE LOWER(email) = :email LIMIT 1');
            $stmt->execute(['email' => $email]);
            $found = $stmt->fetchColumn();
            if ($found) {
                return (int)$found;
            }
        }

        $guestEmail = $email !== '' ? $email : ('guest.' . $phone . '.' . bin2hex(random_bytes(3)) . '@guest.vaststudio.local');
        $stmt = $this->db->prepare(
            "INSERT INTO users (email, password_hash, full_name, phone, role, is_active)
             VALUES (:email, :hash, :name, :phone, 'customer', 1)"
        );
        $stmt->execute([
            'email' => $guestEmail,
            'hash' => password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT),
            'name' => $name,
            'phone' => $phone,
        ]);
        return (int)$this->db->lastInsertId();
    }

    private function resolvePaymentMethodId(string $paymentCode): int
    {
        $codeMap = [
            'qr' => ['qr', 'Chuyển khoản QR (Vietcombank)'],
            'cod' => ['cod', 'COD (Thanh toán khi nhận hàng)'],
            'momo' => ['momo', 'Ví điện tử MoMo'],
            'card' => ['card', 'Thẻ tín dụng Quốc tế (Visa/Mastercard)'],
        ];
        [$normCode, $defaultName] = $codeMap[$paymentCode] ?? $codeMap['cod'];

        $stmt = $this->db->prepare('SELECT id FROM payment_methods WHERE LOWER(code) = :code LIMIT 1');
        $stmt->execute(['code' => $normCode]);
        $id = $stmt->fetchColumn();
        if ($id) {
            return (int)$id;
        }

        $this->db->prepare('INSERT INTO payment_methods (code, name, is_active) VALUES (:code, :name, 1)')
            ->execute(['code' => $normCode, 'name' => $defaultName]);
        return (int)$this->db->lastInsertId();
    }

    private function generateUniqueOrderCode(): string
    {
        for ($i = 0; $i < 10; $i++) {
            $code = 'VAST-' . random_int(10000, 99999);
            $stmt = $this->db->prepare('SELECT 1 FROM orders WHERE order_code = :c LIMIT 1');
            $stmt->execute(['c' => $code]);
            if (!$stmt->fetchColumn()) {
                return $code;
            }
        }
        return 'VAST-' . strtoupper(bin2hex(random_bytes(3)));
    }

    private function loadVariantsMap(): array
    {
        $rows = $this->db->query('SELECT * FROM product_variants ORDER BY id')->fetchAll();
        $map = [];
        foreach ($rows as $row) {
            $map[(int)$row['product_id']][] = [
                'id' => (int)$row['id'],
                'size' => (string)$row['size'],
                'color' => (string)$row['color'],
                'skuVariant' => (string)($row['sku_variant'] ?? ''),
                'stock' => max(0, (int)$row['stock_quantity']),
            ];
        }
        return $map;
    }

    public static function readMergedSettings(PDO $db): array
    {
        $settings = [];
        try {
            $hasLegacy = (bool)$db->query("SHOW TABLES LIKE 'store_settings'")->fetchColumn();
            if ($hasLegacy) {
                foreach ($db->query('SELECT setting_key, setting_value FROM store_settings') as $row) {
                    $decoded = json_decode((string)$row['setting_value'], true);
                    $settings[$row['setting_key']] = json_last_error() === JSON_ERROR_NONE ? $decoded : $row['setting_value'];
                }
            }
        } catch (Throwable $e) {
        }

        try {
            $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = 'storefront' LIMIT 1");
            $stmt->execute();
            $val = $stmt->fetchColumn();
            if ($val !== false) {
                $decoded = json_decode((string)$val, true);
                if (is_array($decoded)) {
                    $settings = array_merge($settings, $decoded);
                }
            }
        } catch (Throwable $e) {
        }

        return $settings;
    }

    private function loadSettings(): array
    {
        return self::readMergedSettings($this->db);
    }

    // =========================================================================
    // EMAIL DISPATCH HELPERS (V.1 – V.8)
    // =========================================================================

    private function renderItemsHtml(array $items): string
    {
        $rows = '';
        foreach ($items as $item) {
            $name = htmlspecialchars((string)$item['name'], ENT_QUOTES, 'UTF-8');
            $variant = htmlspecialchars(trim(($item['size'] ?? '') . ' / ' . ($item['color'] ?? ''), ' /'), ENT_QUOTES, 'UTF-8');
            $qty = (int)$item['quantity'];
            $total = $this->money((float)$item['price'] * $qty);
            $rows .= "<tr>
                <td style='padding:8px 0;border-bottom:1px solid #e2e8f0;'><strong>{$name}</strong><br><small style='color:#64748b'>{$variant}</small></td>
                <td style='padding:8px;border-bottom:1px solid #e2e8f0;text-align:center;'>×{$qty}</td>
                <td style='padding:8px 0;border-bottom:1px solid #e2e8f0;text-align:right;font-weight:700;'>{$total}</td>
            </tr>";
        }
        return "<table style='width:100%;border-collapse:collapse;margin:12px 0;'>{$rows}</table>";
    }

    private function sendOrderCreatedEmails(array $order): void
    {
        $baseUrl = $this->mailer->getBaseUrl();
        $orderCode = $order['id'];
        $itemsHtml = $this->renderItemsHtml($order['items']);

        // 1. Admin email
        $adminEmail = $this->mailer->getAdminNotificationEmail();
        $adminContent = "<p>Hệ thống vừa ghi nhận đơn hàng mới <strong>#{$orderCode}</strong>:</p>
            <ul>
                <li><strong>Khách hàng:</strong> " . htmlspecialchars($order['customer_name'], ENT_QUOTES, 'UTF-8') . "</li>
                <li><strong>Số điện thoại:</strong> " . htmlspecialchars($order['customer_phone'], ENT_QUOTES, 'UTF-8') . "</li>
                <li><strong>Tổng thanh toán:</strong> <strong>" . $this->money($order['total']) . "</strong></li>
                <li><strong>Thanh toán:</strong> " . htmlspecialchars($order['payment_method'], ENT_QUOTES, 'UTF-8') . "</li>
                <li><strong>Thời gian đặt:</strong> " . htmlspecialchars($order['created_at'], ENT_QUOTES, 'UTF-8') . "</li>
            </ul>
            {$itemsHtml}";
        $this->mailer->sendTemplated([
            'order_id' => $order['numeric_id'],
            'recipient_email' => $adminEmail,
            'subject' => "[VAST STUDIO] Đơn hàng mới #{$orderCode} — " . $this->money($order['total']),
            'template_code' => 'order_created_admin',
            'idempotency_key' => 'order_created_admin_' . $orderCode,
            'body_html' => Mailer::wrapHtml("Đơn hàng mới #{$orderCode}", $adminContent, "{$baseUrl}/admin.php?tab=orders&order=" . rawurlencode($orderCode), 'Xem đơn trong Admin'),
        ]);

        // 2. Customer email
        if (!empty($order['customer_email'])) {
            $custContent = "<p>Xin chào <strong>" . htmlspecialchars($order['customer_name'], ENT_QUOTES, 'UTF-8') . "</strong>,</p>
                <p>Cảm ơn bạn đã đặt hàng tại <strong>VAST STUDIO</strong>. Đơn hàng <strong>#{$orderCode}</strong> đã được tiếp nhận lúc {$order['created_at']}.</p>
                <p><strong>Địa chỉ nhận hàng:</strong> " . htmlspecialchars($order['shipping_address'], ENT_QUOTES, 'UTF-8') . "<br>
                <strong>Phương thức thanh toán:</strong> " . htmlspecialchars($order['payment_method'], ENT_QUOTES, 'UTF-8') . "</p>
                {$itemsHtml}
                <p style='font-size:16px;'><strong>Tổng cộng: " . $this->money($order['total']) . "</strong></p>";
            $this->mailer->sendTemplated([
                'user_id' => $order['user_id'],
                'order_id' => $order['numeric_id'],
                'recipient_email' => $order['customer_email'],
                'subject' => "[VAST STUDIO] Xác nhận đặt hàng #{$orderCode}",
                'template_code' => 'order_created_customer',
                'idempotency_key' => 'order_created_customer_' . $orderCode,
                'body_html' => Mailer::wrapHtml("Xác nhận đơn hàng #{$orderCode}", $custContent, "{$baseUrl}/order-tracking.php?order=" . rawurlencode($orderCode), 'Theo dõi đơn hàng'),
            ]);
        }
    }

    private function sendOrderStatusChangedEmail(array $order, string $oldStatus, string $newStatus, int $historyId): void
    {
        if (empty($order['customer_email'])) {
            return;
        }
        $baseUrl = $this->mailer->getBaseUrl();
        $orderCode = $order['id'];
        $oldLabel = OrderWorkflow::statusLabel($oldStatus);
        $newLabel = OrderWorkflow::statusLabel($newStatus);
        $updatedAt = date('d/m/Y H:i');

        $extra = '';
        if ($newStatus === OrderWorkflow::STATUS_DELIVERED) {
            $extra = "<p style='padding:12px;background:#ecfdf5;border-radius:8px;color:#047857;'><strong>Đơn hàng đã được giao thành công!</strong> Vui lòng đăng nhập tài khoản khách hàng để bấm <strong>Xác nhận đã nhận hàng</strong> hoặc gửi <strong>Yêu cầu đổi trả</strong> nếu sản phẩm có vấn đề.</p>";
        }

        $content = "<p>Xin chào <strong>" . htmlspecialchars($order['customer_name'], ENT_QUOTES, 'UTF-8') . "</strong>,</p>
            <p>Trạng thái đơn hàng <strong>#{$orderCode}</strong> vừa được cập nhật:</p>
            <ul>
                <li><strong>Trạng thái cũ:</strong> {$oldLabel}</li>
                <li><strong>Trạng thái mới:</strong> <strong>{$newLabel}</strong></li>
                <li><strong>Thời gian cập nhật:</strong> {$updatedAt}</li>
                <li><strong>Tổng giá trị:</strong> " . $this->money($order['total']) . "</li>
            </ul>
            {$extra}
            " . $this->renderItemsHtml($order['items']);

        $this->mailer->sendTemplated([
            'user_id' => $order['user_id'],
            'order_id' => $order['numeric_id'],
            'recipient_email' => $order['customer_email'],
            'subject' => "[VAST STUDIO] Đơn #{$orderCode}: {$newLabel}",
            'template_code' => 'order_status_changed',
            'idempotency_key' => 'order_status_' . $orderCode . '_' . $historyId,
            'body_html' => Mailer::wrapHtml("Cập nhật đơn hàng #{$orderCode}", $content, "{$baseUrl}/auth.php", 'Xem chi tiết đơn hàng'),
        ]);
    }

    private function sendOrderCancelledEmail(array $order, string $reason, string $cancelledBy, string $idemPrefix): void
    {
        $baseUrl = $this->mailer->getBaseUrl();
        $orderCode = $order['id'];
        $refundNote = $order['payment_status'] === 'refunded'
            ? 'Đơn hàng đã thanh toán trước sẽ được hoàn tiền về tài khoản của bạn trong 1–3 ngày làm việc.'
            : 'Không phát sinh khoản thanh toán cần hoàn lại.';

        if (!empty($order['customer_email'])) {
            $content = "<p>Xin chào <strong>" . htmlspecialchars($order['customer_name'], ENT_QUOTES, 'UTF-8') . "</strong>,</p>
                <p>Đơn hàng <strong>#{$orderCode}</strong> đã được chuyển sang trạng thái <strong>Đã hủy</strong>.</p>
                <ul>
                    <li><strong>Lý do hủy:</strong> " . htmlspecialchars($reason, ENT_QUOTES, 'UTF-8') . "</li>
                    <li><strong>Người thực hiện:</strong> " . htmlspecialchars($cancelledBy, ENT_QUOTES, 'UTF-8') . "</li>
                    <li><strong>Thời gian hủy:</strong> " . date('d/m/Y H:i') . "</li>
                    <li><strong>Thông tin hoàn tiền:</strong> {$refundNote}</li>
                </ul>
                " . $this->renderItemsHtml($order['items']);

            $this->mailer->sendTemplated([
                'user_id' => $order['user_id'],
                'order_id' => $order['numeric_id'],
                'recipient_email' => $order['customer_email'],
                'subject' => "[VAST STUDIO] Thông báo hủy đơn hàng #{$orderCode}",
                'template_code' => 'order_cancelled',
                'idempotency_key' => $idemPrefix . '_cust',
                'body_html' => Mailer::wrapHtml("Đơn hàng #{$orderCode} đã hủy", $content, "{$baseUrl}/auth.php", 'Xem tài khoản'),
            ]);
        }
    }

    private function sendCancelRequestAdminEmail(array $order, string $reason, int $reqId): void
    {
        $baseUrl = $this->mailer->getBaseUrl();
        $orderCode = $order['id'];
        $content = "<p>Khách hàng <strong>" . htmlspecialchars($order['customer_name'], ENT_QUOTES, 'UTF-8') . "</strong> vừa gửi yêu cầu hủy đơn hàng đã xác nhận <strong>#{$orderCode}</strong>.</p>
            <ul>
                <li><strong>Lý do yêu cầu hủy:</strong> " . htmlspecialchars($reason, ENT_QUOTES, 'UTF-8') . "</li>
                <li><strong>Số điện thoại:</strong> " . htmlspecialchars($order['customer_phone'], ENT_QUOTES, 'UTF-8') . "</li>
                <li><strong>Giá trị đơn:</strong> " . $this->money($order['total']) . "</li>
            </ul>";

        $this->mailer->sendTemplated([
            'order_id' => $order['numeric_id'],
            'recipient_email' => $this->mailer->getAdminNotificationEmail(),
            'subject' => "[VAST STUDIO] Yêu cầu hủy đơn #{$orderCode}",
            'template_code' => 'cancel_requested_admin',
            'idempotency_key' => 'cancel_req_admin_' . $reqId,
            'body_html' => Mailer::wrapHtml("Yêu cầu hủy đơn #{$orderCode}", $content, "{$baseUrl}/admin.php?tab=orders&order=" . rawurlencode($orderCode), 'Xử lý yêu cầu hủy'),
        ]);
    }

    private function sendCancelDecisionEmail(array $order, bool $approved, string $note, int $reqId): void
    {
        if (empty($order['customer_email'])) {
            return;
        }
        $baseUrl = $this->mailer->getBaseUrl();
        $orderCode = $order['id'];
        $resultText = $approved ? 'ĐỒNG Ý HỦY ĐƠN' : 'TỪ CHỐI YÊU CẦU HỦY';
        $refundLine = $approved && $order['payment_status'] === 'refunded'
            ? '<li><strong>Hoàn tiền:</strong> Khoản thanh toán sẽ được hoàn trả trong 1–3 ngày làm việc.</li>'
            : '';

        $content = "<p>Xin chào <strong>" . htmlspecialchars($order['customer_name'], ENT_QUOTES, 'UTF-8') . "</strong>,</p>
            <p>Admin đã phản hồi yêu cầu hủy đơn hàng <strong>#{$orderCode}</strong>:</p>
            <ul>
                <li><strong>Kết quả:</strong> <strong>{$resultText}</strong></li>
                <li><strong>Trạng thái hiện tại:</strong> " . OrderWorkflow::statusLabel($order['order_status']) . "</li>
                " . ($note !== '' ? '<li><strong>Lý do / Ghi chú:</strong> ' . htmlspecialchars($note, ENT_QUOTES, 'UTF-8') . '</li>' : '') . "
                {$refundLine}
            </ul>";

        $this->mailer->sendTemplated([
            'user_id' => $order['user_id'],
            'order_id' => $order['numeric_id'],
            'recipient_email' => $order['customer_email'],
            'subject' => "[VAST STUDIO] Kết quả yêu cầu hủy đơn #{$orderCode}",
            'template_code' => 'cancel_request_responded',
            'idempotency_key' => 'cancel_decision_' . $reqId . '_' . ($approved ? 'app' : 'rej'),
            'body_html' => Mailer::wrapHtml("Phản hồi yêu cầu hủy #{$orderCode}", $content, "{$baseUrl}/auth.php", 'Xem đơn hàng'),
        ]);
    }

    private function sendReturnRequestedAdminEmail(array $order, array $ret): void
    {
        $baseUrl = $this->mailer->getBaseUrl();
        $orderCode = $order['id'];
        $returnCode = $ret['return_code'];

        $itemLines = '';
        foreach ($ret['items'] as $ri) {
            $itemLines .= '<li>' . htmlspecialchars($ri['product_name'], ENT_QUOTES, 'UTF-8') . ' (' . htmlspecialchars($ri['size'] . ' / ' . $ri['color'], ENT_QUOTES, 'UTF-8') . ') × ' . (int)$ri['quantity'] . '</li>';
        }
        $imgLines = '';
        foreach ($ret['images'] as $im) {
            $url = $baseUrl . '/' . ltrim($im['file_path'], '/');
            $imgLines .= '<li><a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($im['original_name'], ENT_QUOTES, 'UTF-8') . '</a></li>';
        }

        $content = "<p>Khách hàng <strong>" . htmlspecialchars($order['customer_name'], ENT_QUOTES, 'UTF-8') . "</strong> gửi yêu cầu đổi trả <strong>{$returnCode}</strong> cho đơn <strong>#{$orderCode}</strong>:</p>
            <ul>
                <li><strong>Lý do:</strong> " . htmlspecialchars($ret['reason_text'], ENT_QUOTES, 'UTF-8') . "</li>
                <li><strong>Mô tả:</strong> " . htmlspecialchars($ret['description'], ENT_QUOTES, 'UTF-8') . "</li>
            </ul>
            <p><strong>Sản phẩm yêu cầu đổi trả:</strong></p><ul>{$itemLines}</ul>
            <p><strong>Hình ảnh đính kèm:</strong></p><ul>{$imgLines}</ul>";

        $this->mailer->sendTemplated([
            'order_id' => $order['numeric_id'],
            'recipient_email' => $this->mailer->getAdminNotificationEmail(),
            'subject' => "[VAST STUDIO] Yêu cầu đổi trả mới {$returnCode} (Đơn #{$orderCode})",
            'template_code' => 'return_requested_admin',
            'idempotency_key' => 'return_req_admin_' . $returnCode,
            'body_html' => Mailer::wrapHtml("Yêu cầu đổi trả {$returnCode}", $content, "{$baseUrl}/admin.php?tab=returns&return=" . rawurlencode($returnCode), 'Kiểm tra yêu cầu đổi trả'),
        ]);
    }

    private function sendReturnDecisionEmail(array $order, array $ret, string $stage): void
    {
        if (empty($order['customer_email'])) {
            return;
        }
        $baseUrl = $this->mailer->getBaseUrl();
        $orderCode = $order['id'];
        $returnCode = $ret['return_code'];

        $hanoi = $this->mailer->getStoreSetting('addressHanoi', '148 Phố Huế, Q. Hai Bà Trưng, Hà Nội');
        $hotline = $this->mailer->getStoreSetting('hotline', '0988.123.456');

        if ($stage === 'approved') {
            $body = "<p>Yêu cầu đổi trả <strong>{$returnCode}</strong> cho đơn <strong>#{$orderCode}</strong> đã được <strong>CHẤP THUẬN</strong>.</p>
                <p><strong>Hướng dẫn gửi hàng về VAST STUDIO:</strong></p>
                <ol>
                    <li>Đóng gói nguyên vẹn sản phẩm kèm tag mác và ghi rõ mã <strong>{$returnCode}</strong> ngoài kiện hàng.</li>
                    <li>Gửi về địa chỉ: <strong>" . htmlspecialchars($hanoi, ENT_QUOTES, 'UTF-8') . "</strong> (Người nhận: Bộ phận Đổi trả VAST STUDIO — SĐT: {$hotline}).</li>
                    <li>Sau khi nhận được kiện hàng, chúng tôi sẽ kiểm tra và gửi sản phẩm đổi hoặc hoàn tiền ngay cho bạn.</li>
                </ol>";
            $subject = "[VAST STUDIO] Yêu cầu đổi trả {$returnCode} đã được chấp thuận";
        } elseif ($stage === 'rejected') {
            $body = "<p>Rất tiếc, yêu cầu đổi trả <strong>{$returnCode}</strong> cho đơn <strong>#{$orderCode}</strong> đã <strong>BỊ TỪ CHỐI</strong>.</p>
                <p><strong>Lý do từ chối:</strong> " . htmlspecialchars((string)$ret['reject_reason'], ENT_QUOTES, 'UTF-8') . "</p>
                <p>Nếu cần hỗ trợ thêm, vui lòng liên hệ Hotline {$hotline}.</p>";
            $subject = "[VAST STUDIO] Yêu cầu đổi trả {$returnCode} không được chấp thuận";
        } elseif ($stage === 'received') {
            $body = "<p>VAST STUDIO đã <strong>NHẬN ĐƯỢC HÀNG ĐỔI TRẢ</strong> cho yêu cầu <strong>{$returnCode}</strong> (Đơn #{$orderCode}).</p>
                <p>Chúng tôi đang kiểm tra tình trạng thực tế và sẽ xử lý đổi hàng/hoàn tiền sớm nhất.</p>";
            $subject = "[VAST STUDIO] Đã nhận hàng đổi trả {$returnCode}";
        } else {
            $resText = $ret['resolution_type'] === 'refund'
                ? 'Đã xử lý hoàn tiền ' . $this->money((float)$ret['refund_amount'])
                : 'Đã xử lý đổi sản phẩm mới';
            $body = "<p>Yêu cầu đổi trả <strong>{$returnCode}</strong> cho đơn <strong>#{$orderCode}</strong> đã <strong>HOÀN TẤT</strong>.</p>
                <p><strong>Kết quả:</strong> {$resText}</p>";
            $subject = "[VAST STUDIO] Đổi trả {$returnCode} hoàn tất";
        }

        $this->mailer->sendTemplated([
            'user_id' => $order['user_id'],
            'order_id' => $order['numeric_id'],
            'recipient_email' => $order['customer_email'],
            'subject' => $subject,
            'template_code' => 'return_' . $stage,
            'idempotency_key' => 'return_' . $stage . '_' . $returnCode,
            'body_html' => Mailer::wrapHtml("Cập nhật đổi trả {$returnCode}", $body, "{$baseUrl}/auth.php", 'Xem chi tiết trong tài khoản'),
        ]);
    }

    private function sendOrderCompletedEmails(array $order): void
    {
        $baseUrl = $this->mailer->getBaseUrl();
        $orderCode = $order['id'];

        if (!empty($order['customer_email'])) {
            $custContent = "<p>Xin chào <strong>" . htmlspecialchars($order['customer_name'], ENT_QUOTES, 'UTF-8') . "</strong>,</p>
                <p>Cảm ơn bạn đã xác nhận nhận hàng cho đơn <strong>#{$orderCode}</strong>. Đơn hàng hiện đã <strong>Hoàn tất</strong>.</p>
                <p>Chúc bạn có trải nghiệm tuyệt vời cùng các thiết kế từ VAST STUDIO!</p>";
            $this->mailer->sendTemplated([
                'user_id' => $order['user_id'],
                'order_id' => $order['numeric_id'],
                'recipient_email' => $order['customer_email'],
                'subject' => "[VAST STUDIO] Đơn hàng #{$orderCode} đã hoàn tất",
                'template_code' => 'order_completed_customer',
                'idempotency_key' => 'order_completed_cust_' . $orderCode,
                'body_html' => Mailer::wrapHtml("Đơn hàng #{$orderCode} hoàn tất", $custContent, "{$baseUrl}/products.php", 'Tiếp tục mua sắm'),
            ]);
        }

        $adminContent = "<p>Khách hàng <strong>" . htmlspecialchars($order['customer_name'], ENT_QUOTES, 'UTF-8') . "</strong> đã xác nhận nhận đủ hàng cho đơn <strong>#{$orderCode}</strong>.</p>
            <p>Doanh thu <strong>" . $this->money($order['total']) . "</strong> đã được ghi nhận vào thống kê hoàn tất.</p>";
        $this->mailer->sendTemplated([
            'order_id' => $order['numeric_id'],
            'recipient_email' => $this->mailer->getAdminNotificationEmail(),
            'subject' => "[VAST STUDIO] Khách xác nhận hoàn tất đơn #{$orderCode}",
            'template_code' => 'order_completed_admin',
            'idempotency_key' => 'order_completed_admin_' . $orderCode,
            'body_html' => Mailer::wrapHtml("Đơn #{$orderCode} hoàn tất", $adminContent, "{$baseUrl}/admin.php?tab=orders&order=" . rawurlencode($orderCode), 'Xem chi tiết đơn'),
        ]);
    }

    private function money(float $amount): string
    {
        return number_format($amount, 0, ',', '.') . ' ₫';
    }
}
