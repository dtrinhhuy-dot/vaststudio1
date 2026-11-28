<?php

class OrderSchemaMigrator
{
    public static function migrate(PDO $db): array
    {
        $log = [];
        $legacyOrders = (bool)$db->query("SHOW COLUMNS FROM orders LIKE 'order_code'")->fetch();
        $legacyProducts = (bool)$db->query("SHOW COLUMNS FROM products LIKE 'stock_quantity'")->fetch();

        // 1. Extend products with gallery_json & product_variants table
        self::addColumnIfMissing($db, 'products', 'gallery_json', 'MEDIUMTEXT NULL', $log);

        $productIdType = $legacyProducts ? 'BIGINT UNSIGNED NOT NULL' : 'VARCHAR(30) NOT NULL';
        $db->exec(
            "CREATE TABLE IF NOT EXISTS product_variants (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                product_id {$productIdType},
                size VARCHAR(50) NOT NULL DEFAULT 'Freesize',
                color VARCHAR(100) NOT NULL DEFAULT 'Tiêu chuẩn',
                sku_variant VARCHAR(120) DEFAULT NULL,
                stock_quantity INT NOT NULL DEFAULT 0,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uq_pv_product_size_color (product_id, size, color),
                CONSTRAINT fk_pv_products FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
        $log[] = 'Ensured product_variants table';

        // 2. Extend orders with workflow timestamps, snapshot & cancel metadata
        if ($legacyOrders) {
            self::addColumnIfMissing($db, 'orders', 'customer_name', 'VARCHAR(150) NULL AFTER user_id', $log);
            self::addColumnIfMissing($db, 'orders', 'customer_phone', 'VARCHAR(30) NULL AFTER customer_name', $log);
            self::addColumnIfMissing($db, 'orders', 'customer_email', 'VARCHAR(191) NULL AFTER customer_phone', $log);
            self::addColumnIfMissing($db, 'orders', 'subtotal', 'DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER shipping_address', $log);
            self::addColumnIfMissing($db, 'orders', 'shipping_fee', 'DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER subtotal', $log);
            self::addColumnIfMissing($db, 'orders', 'discount_amount', 'DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER shipping_fee', $log);
            self::addColumnIfMissing($db, 'orders', 'coupon_code', 'VARCHAR(100) NULL AFTER discount_amount', $log);
            self::addColumnIfMissing($db, 'orders', 'shipping_method', 'VARCHAR(120) NULL AFTER coupon_code', $log);
            self::addColumnIfMissing($db, 'orders', 'note', 'TEXT NULL AFTER shipping_method', $log);

            // Backfill legacy customer info and financial subtotal safely where null
            $db->exec(
                "UPDATE orders o
                 LEFT JOIN users u ON u.id = o.user_id
                 SET o.customer_name = COALESCE(NULLIF(o.customer_name, ''), NULLIF(u.full_name, ''), u.email, 'Khách hàng'),
                     o.customer_phone = COALESCE(NULLIF(o.customer_phone, ''), u.phone),
                     o.customer_email = COALESCE(NULLIF(o.customer_email, ''), u.email),
                     o.subtotal = IF(o.subtotal = 0 AND o.total_amount > 0, o.total_amount, o.subtotal)"
            );
            $log[] = 'Backfilled legacy orders snapshot columns';
        } else {
            self::addColumnIfMissing($db, 'orders', 'shipping_method', 'VARCHAR(120) NULL', $log);
        }

        self::addColumnIfMissing($db, 'orders', 'stock_deducted', 'TINYINT(1) NOT NULL DEFAULT 0', $log);
        self::addColumnIfMissing($db, 'orders', 'pre_cancel_status', 'VARCHAR(50) NULL', $log);
        self::addColumnIfMissing($db, 'orders', 'cancel_reason', 'TEXT NULL', $log);
        self::addColumnIfMissing($db, 'orders', 'cancelled_by', 'VARCHAR(150) NULL', $log);
        self::addColumnIfMissing($db, 'orders', 'confirmed_at', 'DATETIME NULL', $log);
        self::addColumnIfMissing($db, 'orders', 'processing_at', 'DATETIME NULL', $log);
        self::addColumnIfMissing($db, 'orders', 'shipped_at', 'DATETIME NULL', $log);
        self::addColumnIfMissing($db, 'orders', 'delivered_at', 'DATETIME NULL', $log);
        self::addColumnIfMissing($db, 'orders', 'customer_confirmed_at', 'DATETIME NULL', $log);
        self::addColumnIfMissing($db, 'orders', 'cancelled_at', 'DATETIME NULL', $log);
        self::addColumnIfMissing($db, 'orders', 'refunded_amount', 'DECIMAL(12,2) NOT NULL DEFAULT 0.00', $log);

        // 3. Extend order_items with immutable product snapshot columns
        self::addColumnIfMissing($db, 'order_items', 'product_name', 'VARCHAR(255) NULL', $log);
        self::addColumnIfMissing($db, 'order_items', 'product_sku', 'VARCHAR(100) NULL', $log);
        self::addColumnIfMissing($db, 'order_items', 'product_image', 'TEXT NULL', $log);
        self::addColumnIfMissing($db, 'order_items', 'size', 'VARCHAR(50) NULL', $log);
        self::addColumnIfMissing($db, 'order_items', 'color', 'VARCHAR(100) NULL', $log);

        if ($legacyProducts) {
            $db->exec(
                "UPDATE order_items oi
                 JOIN products p ON p.id = oi.product_id
                 SET oi.product_name = COALESCE(NULLIF(oi.product_name, ''), p.name),
                     oi.product_sku = COALESCE(NULLIF(oi.product_sku, ''), p.sku),
                     oi.product_image = COALESCE(NULLIF(oi.product_image, ''), p.image_url, p.fallback_image)"
            );
            $log[] = 'Backfilled order_items snapshot columns from products';
        }

        // 4. Extend order_status_history
        self::addColumnIfMissing($db, 'order_status_history', 'from_status', 'VARCHAR(50) NULL AFTER admin_id', $log);
        self::addColumnIfMissing($db, 'order_status_history', 'actor_type', "VARCHAR(30) NOT NULL DEFAULT 'system' AFTER from_status", $log);
        self::addColumnIfMissing($db, 'order_status_history', 'actor_id', 'BIGINT UNSIGNED NULL AFTER actor_type', $log);
        self::addColumnIfMissing($db, 'order_status_history', 'actor_name', 'VARCHAR(150) NULL AFTER actor_id', $log);

        // Backfill initial order_status_history row for existing orders without any history
        $db->exec(
            "INSERT INTO order_status_history (order_id, from_status, actor_type, actor_name, status, note, created_at)
             SELECT o.id, NULL, 'system', 'Hệ thống', o.current_status, 'Trạng thái hiện tại được ghi nhận khi nâng cấp quy trình đơn hàng', o.created_at
             FROM orders o
             LEFT JOIN order_status_history h ON h.order_id = o.id
             WHERE h.id IS NULL"
        );

        // 5. Extend email_logs
        self::addColumnIfMissing($db, 'email_logs', 'body_html', 'MEDIUMTEXT NULL AFTER template_code', $log);
        self::addColumnIfMissing($db, 'email_logs', 'body_text', 'MEDIUMTEXT NULL AFTER body_html', $log);
        self::addColumnIfMissing($db, 'email_logs', 'idempotency_key', 'VARCHAR(191) NULL AFTER body_text', $log);
        self::addColumnIfMissing($db, 'email_logs', 'attempts', 'INT UNSIGNED NOT NULL DEFAULT 1 AFTER error_message', $log);
        self::addColumnIfMissing($db, 'email_logs', 'last_attempt_at', 'TIMESTAMP NULL AFTER attempts', $log);
        self::addUniqueIndexIfMissing($db, 'email_logs', 'uq_email_logs_idempotency', 'idempotency_key', $log);

        // 6. Create order_cancel_requests
        $orderIdType = $legacyOrders ? 'BIGINT UNSIGNED NOT NULL' : 'VARCHAR(30) NOT NULL';
        $db->exec(
            "CREATE TABLE IF NOT EXISTS order_cancel_requests (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                order_id {$orderIdType},
                user_id BIGINT UNSIGNED NOT NULL,
                previous_status VARCHAR(50) NOT NULL,
                reason TEXT NOT NULL,
                status VARCHAR(30) NOT NULL DEFAULT 'pending',
                admin_id BIGINT UNSIGNED DEFAULT NULL,
                admin_name VARCHAR(150) DEFAULT NULL,
                admin_note TEXT DEFAULT NULL,
                responded_at DATETIME DEFAULT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_ocr_order (order_id),
                KEY idx_ocr_status (status),
                CONSTRAINT fk_ocr_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
                CONSTRAINT fk_ocr_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
        $log[] = 'Ensured order_cancel_requests table';

        // 7. Create order_return_requests, order_return_items, order_return_images
        $db->exec(
            "CREATE TABLE IF NOT EXISTS order_return_requests (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                return_code VARCHAR(40) NOT NULL,
                order_id {$orderIdType},
                user_id BIGINT UNSIGNED NOT NULL,
                reason_code VARCHAR(60) NOT NULL,
                reason_text VARCHAR(150) NOT NULL,
                description TEXT NOT NULL,
                status VARCHAR(40) NOT NULL DEFAULT 'pending_review',
                resolution_type VARCHAR(30) NOT NULL DEFAULT 'none',
                refund_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                admin_id BIGINT UNSIGNED DEFAULT NULL,
                admin_name VARCHAR(150) DEFAULT NULL,
                reject_reason TEXT DEFAULT NULL,
                admin_note TEXT DEFAULT NULL,
                approved_at DATETIME DEFAULT NULL,
                received_at DATETIME DEFAULT NULL,
                completed_at DATETIME DEFAULT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uq_orr_code (return_code),
                KEY idx_orr_order (order_id),
                KEY idx_orr_status (status),
                CONSTRAINT fk_orr_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
                CONSTRAINT fk_orr_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $db->exec(
            "CREATE TABLE IF NOT EXISTS order_return_items (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                return_request_id BIGINT UNSIGNED NOT NULL,
                order_item_id BIGINT UNSIGNED NOT NULL,
                product_id {$productIdType},
                product_name VARCHAR(255) NOT NULL,
                product_sku VARCHAR(100) DEFAULT NULL,
                size VARCHAR(50) DEFAULT NULL,
                color VARCHAR(100) DEFAULT NULL,
                quantity INT UNSIGNED NOT NULL,
                unit_price DECIMAL(12,2) NOT NULL,
                condition_note TEXT DEFAULT NULL,
                restocked TINYINT(1) NOT NULL DEFAULT 0,
                PRIMARY KEY (id),
                KEY idx_ori_req (return_request_id),
                CONSTRAINT fk_ori_req FOREIGN KEY (return_request_id) REFERENCES order_return_requests(id) ON DELETE CASCADE,
                CONSTRAINT fk_ori_item FOREIGN KEY (order_item_id) REFERENCES order_items(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $db->exec(
            "CREATE TABLE IF NOT EXISTS order_return_images (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                return_request_id BIGINT UNSIGNED NOT NULL,
                file_path VARCHAR(500) NOT NULL,
                original_name VARCHAR(255) NOT NULL,
                mime_type VARCHAR(100) NOT NULL,
                file_size INT UNSIGNED NOT NULL DEFAULT 0,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_orimg_req (return_request_id),
                CONSTRAINT fk_orimg_req FOREIGN KEY (return_request_id) REFERENCES order_return_requests(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
        $log[] = 'Ensured order_return_* tables';

        // 8. Create notifications table
        $nullableOrderIdType = $legacyOrders ? 'BIGINT UNSIGNED DEFAULT NULL' : 'VARCHAR(30) DEFAULT NULL';
        $db->exec(
            "CREATE TABLE IF NOT EXISTS notifications (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id BIGINT UNSIGNED DEFAULT NULL,
                recipient_role VARCHAR(30) NOT NULL DEFAULT 'customer',
                order_id {$nullableOrderIdType},
                title VARCHAR(255) NOT NULL,
                message TEXT NOT NULL,
                link VARCHAR(255) DEFAULT NULL,
                is_read TINYINT(1) NOT NULL DEFAULT 0,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_notif_user_read (user_id, is_read),
                KEY idx_notif_role_read (recipient_role, is_read)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
        $log[] = 'Ensured notifications table';

        // 9. Seed product_variants from existing products if a product has no variants yet
        self::seedMissingVariants($db, $legacyProducts, $log);

        return $log;
    }

    private static function seedMissingVariants(PDO $db, bool $legacyProducts, array &$log): void
    {
        $stockCol = $legacyProducts ? 'stock_quantity' : 'stock';
        $sizesCol = $legacyProducts ? 'sizes_json' : 'sizes';
        $colorsCol = $legacyProducts ? 'colors_json' : 'colors';

        $products = $db->query(
            "SELECT p.id, p.sku, p.{$stockCol} AS stock, p.{$sizesCol} AS sizes, p.{$colorsCol} AS colors
             FROM products p
             WHERE NOT EXISTS (SELECT 1 FROM product_variants pv WHERE pv.product_id = p.id)"
        )->fetchAll();

        if (!$products) {
            return;
        }

        $insert = $db->prepare(
            "INSERT IGNORE INTO product_variants (product_id, size, color, sku_variant, stock_quantity)
             VALUES (:product_id, :size, :color, :sku_variant, :stock_quantity)"
        );

        foreach ($products as $product) {
            $sizes = json_decode((string)($product['sizes'] ?? '[]'), true);
            $colors = json_decode((string)($product['colors'] ?? '[]'), true);
            if (!is_array($sizes) || !$sizes) {
                $sizes = ['Freesize'];
            }
            if (!is_array($colors) || !$colors) {
                $colors = ['Tiêu chuẩn'];
            }

            $combos = [];
            foreach ($sizes as $size) {
                foreach ($colors as $color) {
                    $combos[] = [trim((string)$size), trim((string)$color)];
                }
            }

            $totalStock = max(0, (int)$product['stock']);
            $count = count($combos);
            $base = $count > 0 ? intdiv($totalStock, $count) : 0;
            $rem = $count > 0 ? ($totalStock % $count) : 0;

            foreach ($combos as $idx => [$size, $color]) {
                $qty = $base + ($idx < $rem ? 1 : 0);
                $insert->execute([
                    'product_id' => $product['id'],
                    'size' => $size !== '' ? $size : 'Freesize',
                    'color' => $color !== '' ? $color : 'Tiêu chuẩn',
                    'sku_variant' => strtoupper(trim((string)($product['sku'] ?? 'VAST'))) . '-' . strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', $size)) . '-' . ($idx + 1),
                    'stock_quantity' => $qty,
                ]);
            }
        }

        $log[] = 'Seeded initial product_variants for ' . count($products) . ' products';
    }

    private static function addColumnIfMissing(PDO $db, string $table, string $column, string $definition, array &$log): void
    {
        $stmt = $db->prepare(
            'SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND COLUMN_NAME = :col
             LIMIT 1'
        );
        $stmt->execute(['table' => $table, 'col' => $column]);
        if (!$stmt->fetchColumn()) {
            $db->exec("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
            $log[] = "Added {$table}.{$column}";
        }
    }

    private static function addUniqueIndexIfMissing(PDO $db, string $table, string $indexName, string $column, array &$log): void
    {
        $stmt = $db->prepare(
            'SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND INDEX_NAME = :idx
             LIMIT 1'
        );
        $stmt->execute(['table' => $table, 'idx' => $indexName]);
        if (!$stmt->fetchColumn()) {
            $db->exec("ALTER TABLE `{$table}` ADD UNIQUE KEY `{$indexName}` (`{$column}`)");
            $log[] = "Added unique index {$indexName} on {$table}";
        }
    }
}
