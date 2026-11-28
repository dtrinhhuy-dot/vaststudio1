<?php

class AdminStoreModel
{
    private PDO $db;
    private bool $legacySchema;

    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->legacySchema = (bool)$this->db->query("SHOW COLUMNS FROM products LIKE 'stock_quantity'")->fetch();
    }

    public function getProducts(array $filters = []): array
    {
        $sql = $this->legacySchema
            ? "SELECT p.*, p.stock_quantity AS stock, p.sizes_json AS sizes,
                      p.colors_json AS colors, COALESCE(p.image_url, p.fallback_image) AS image,
                      COALESCE(NULLIF(p.status, ''), IF(p.is_active = 1, 'active', 'draft')) AS status,
                      p.collection_name AS collection_name
               FROM products p WHERE 1 = 1"
            : 'SELECT p.*, c.name AS collection_name
               FROM products p
               LEFT JOIN collections c ON c.id = p.collection_id
               WHERE 1 = 1';
        $params = [];

        if ($filters['search'] !== '') {
            $sql .= ' AND (p.name LIKE :product_name_search OR p.sku LIKE :product_sku_search)';
            $params['product_name_search'] = '%' . $filters['search'] . '%';
            $params['product_sku_search'] = '%' . $filters['search'] . '%';
        }
        if ($filters['category'] !== 'all') {
            $sql .= ' AND p.category = :category';
            $params['category'] = $filters['category'];
        }
        if ($filters['stock'] === 'in_stock') {
            $sql .= $this->legacySchema ? ' AND p.stock_quantity > 10' : ' AND p.stock > 10';
        } elseif ($filters['stock'] === 'low_stock') {
            $sql .= $this->legacySchema ? ' AND p.stock_quantity BETWEEN 1 AND 10' : ' AND p.stock BETWEEN 1 AND 10';
        } elseif ($filters['stock'] === 'out_of_stock') {
            $sql .= $this->legacySchema ? ' AND p.stock_quantity = 0' : ' AND p.stock = 0';
        }

        $sorts = [
            'price_asc' => 'p.price ASC',
            'price_desc' => 'p.price DESC',
            'stock_desc' => $this->legacySchema ? 'p.stock_quantity DESC' : 'p.stock DESC',
            'sales_desc' => 'p.sales_count DESC',
            'newest' => 'p.created_at DESC, p.id DESC',
        ];
        $sql .= ' ORDER BY ' . ($sorts[$filters['sort']] ?? $sorts['newest']);

        $statement = $this->db->prepare($sql);
        $statement->execute($params);
        return array_map([$this, 'mapProduct'], $statement->fetchAll());
    }

    public function authenticateAdmin(string $email, string $password): ?array
    {
        $nameColumn = $this->legacySchema
            ? "COALESCE(NULLIF(full_name, ''), email)"
            : 'name';
        $activeCondition = $this->legacySchema ? ' AND is_active = 1' : '';
        $statement = $this->db->prepare(
            "SELECT id, {$nameColumn} AS name, email, password_hash
             FROM users
             WHERE email = :email AND role = 'admin'{$activeCondition}
             LIMIT 1"
        );
        $statement->execute(['email' => strtolower(trim($email))]);
        $user = $statement->fetch();
        if (!$user || !password_verify($password, $user['password_hash'])) {
            return null;
        }
        unset($user['password_hash']);
        return $user;
    }

    public function getProduct(string $id): ?array
    {
        $sql = $this->legacySchema
            ? "SELECT p.*, p.stock_quantity AS stock, p.sizes_json AS sizes,
                      p.colors_json AS colors, COALESCE(p.image_url, p.fallback_image) AS image,
                      COALESCE(NULLIF(p.status, ''), IF(p.is_active = 1, 'active', 'draft')) AS status,
                      p.collection_name AS collection_name
               FROM products p WHERE p.id = :id"
            : 'SELECT p.*, c.name AS collection_name
               FROM products p
               LEFT JOIN collections c ON c.id = p.collection_id
               WHERE p.id = :id';
        $statement = $this->db->prepare($sql);
        $statement->execute(['id' => $id]);
        $product = $statement->fetch();
        return $product ? $this->mapProduct($product) : null;
    }

    public function saveProduct(array $input): void
    {
        if ($this->legacySchema) {
            $this->saveLegacyProduct($input);
            return;
        }

        $collectionId = $this->findCollectionId(trim($input['collection'] ?? ''));
        $values = [
            'name' => trim($input['name']),
            'sku' => strtoupper(trim($input['sku'])),
            'category' => trim($input['category']),
            'collection_id' => $collectionId,
            'price' => max(0, (float)$input['price']),
            'original_price' => ($input['original_price'] ?? '') === '' ? null : max(0, (float)$input['original_price']),
            'stock' => max(0, (int)$input['stock']),
            'sizes' => json_encode($this->splitList($input['sizes']), JSON_UNESCAPED_UNICODE),
            'colors' => json_encode($this->splitList($input['colors']), JSON_UNESCAPED_UNICODE),
            'image' => trim($input['image'] ?? '') ?: null,
            'description' => trim($input['description'] ?? ''),
            'status' => ($input['status'] ?? '') === 'draft' ? 'draft' : 'active',
        ];

        if (($input['id'] ?? '') !== '') {
            $values['id'] = $input['id'];
            $statement = $this->db->prepare(
                'UPDATE products SET name = :name, sku = :sku, category = :category,
                 collection_id = :collection_id, price = :price, original_price = :original_price,
                 stock = :stock, sizes = :sizes, colors = :colors, image = :image,
                 description = :description, status = :status WHERE id = :id'
            );
            $statement->execute($values);
            return;
        }

        $values['id'] = 'PRD-' . strtoupper(bin2hex(random_bytes(4)));
        $values['sales_count'] = 0;
        $values['created_at'] = date('Y-m-d');
        $statement = $this->db->prepare(
            'INSERT INTO products
             (id, name, sku, category, collection_id, price, original_price, stock,
              sizes, colors, image, description, status, sales_count, created_at)
             VALUES
             (:id, :name, :sku, :category, :collection_id, :price, :original_price, :stock,
              :sizes, :colors, :image, :description, :status, :sales_count, :created_at)'
        );
        $statement->execute($values);
    }

    public function deleteProduct(string $id): void
    {
        $statement = $this->db->prepare('DELETE FROM products WHERE id = :id');
        $statement->execute(['id' => $id]);
    }

    public function getOrders(array $filters = []): array
    {
        if ($this->legacySchema) {
            $sql = $this->legacyOrderSelect() . ' WHERE 1 = 1';
            $params = [];
            if ($filters['status'] === 'returns_group') {
                $sql .= " AND o.current_status IN ('return_requested','return_awaiting_shipment','return_received','return_completed')";
            } elseif ($filters['status'] !== 'all') {
                $sql .= ' AND o.current_status = :status';
                $params['status'] = $filters['status'];
            }
            if ($filters['search'] !== '') {
                $sql .= " AND (o.order_code LIKE :order_code_search
                               OR COALESCE(NULLIF(o.customer_name, ''), u.full_name) LIKE :customer_name_search
                               OR COALESCE(NULLIF(o.customer_phone, ''), u.phone) LIKE :customer_phone_search)";
                $term = '%' . $filters['search'] . '%';
                $params['order_code_search'] = $term;
                $params['customer_name_search'] = $term;
                $params['customer_phone_search'] = $term;
            }
            $sql .= ' ORDER BY o.created_at DESC';
            $statement = $this->db->prepare($sql);
            $statement->execute($params);
            return array_map([$this, 'mapOrder'], $statement->fetchAll());
        }

        $sql = 'SELECT o.* FROM orders o WHERE 1 = 1';
        $params = [];
        if ($filters['status'] === 'returns_group') {
            $sql .= " AND o.order_status IN ('return_requested','return_awaiting_shipment','return_received','return_completed')";
        } elseif ($filters['status'] !== 'all') {
            $sql .= ' AND o.order_status = :status';
            $params['status'] = $filters['status'];
        }
        if ($filters['search'] !== '') {
            $sql .= ' AND (o.id LIKE :order_id_search OR o.customer_name LIKE :customer_name_search OR o.customer_phone LIKE :customer_phone_search)';
            $term = '%' . $filters['search'] . '%';
            $params['order_id_search'] = $term;
            $params['customer_name_search'] = $term;
            $params['customer_phone_search'] = $term;
        }
        $sql .= ' ORDER BY o.created_at DESC';
        $statement = $this->db->prepare($sql);
        $statement->execute($params);
        return array_map([$this, 'mapOrder'], $statement->fetchAll());
    }

    public function getOrderCounts(): array
    {
        require_once __DIR__ . '/OrderWorkflow.php';
        $counts = ['all' => 0, 'returns_group' => 0];
        foreach (array_keys(OrderWorkflow::orderStatuses()) as $st) {
            $counts[$st] = 0;
        }

        $statusColumn = $this->legacySchema ? 'current_status' : 'order_status';
        $rows = $this->db->query(
            'SELECT ' . $statusColumn . ' AS order_status, COUNT(*) AS total
             FROM orders GROUP BY ' . $statusColumn
        )->fetchAll();
        foreach ($rows as $row) {
            $status = (string)$row['order_status'];
            $total = (int)$row['total'];
            if (!isset($counts[$status])) {
                $counts[$status] = 0;
            }
            $counts[$status] += $total;
            $counts['all'] += $total;
            if (str_starts_with($status, 'return_')) {
                $counts['returns_group'] += $total;
            }
        }
        return $counts;
    }

    public function getOrder(string $id): ?array
    {
        if ($this->legacySchema) {
            require_once __DIR__ . '/OrderService.php';
            return (new OrderService($this->db))->getOrderFullDetail($id);
        }

        $statement = $this->db->prepare('SELECT * FROM orders WHERE id = :id');
        $statement->execute(['id' => $id]);
        $order = $statement->fetch();
        if (!$order) {
            return null;
        }
        $order = $this->mapOrder($order);
        $items = $this->db->prepare('SELECT * FROM order_items WHERE order_id = :id ORDER BY id');
        $items->execute(['id' => $id]);
        $order['items'] = array_map(static function (array $item): array {
            return [
                'name' => $item['product_name'],
                'sku' => $item['product_sku'] ?? '',
                'size' => $item['size'] ?? '',
                'color' => $item['color'] ?? '',
                'price' => (float)$item['price'],
                'quantity' => (int)$item['quantity'],
                'image' => $item['image'] ?? '',
            ];
        }, $items->fetchAll());
        $order['history'] = [];
        $order['cancel_requests'] = [];
        $order['return_requests'] = [];
        $order['email_logs'] = [];
        return $order;
    }

    public function updateOrderStatus(string $id, string $status): bool
    {
        throw new InvalidArgumentException('Thao tác cập nhật trạng thái tự do đã bị khóa. Vui lòng sử dụng quy trình xử lý từng bước.');
    }

    public function getCollections(): array
    {
        if ($this->legacySchema) {
            return $this->db->query(
                'SELECT c.*, COUNT(p.id) AS products_count
                 FROM collections c
                 LEFT JOIN products p ON p.collection_name = c.name
                 GROUP BY c.id ORDER BY c.created_at DESC'
            )->fetchAll();
        }

        return $this->db->query(
            'SELECT c.*, COUNT(p.id) AS products_count
             FROM collections c LEFT JOIN products p ON p.collection_id = c.id
             GROUP BY c.id ORDER BY c.created_at DESC'
        )->fetchAll();
    }

    public function saveCollection(array $input): void
    {
        $name = trim($input['name']);
        $transliterated = function_exists('iconv') ? iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name) : false;
        $slugSource = $transliterated === false ? $name : $transliterated;
        $slug = strtolower(trim((string)preg_replace('/[^a-z0-9]+/i', '-', $slugSource), '-'));
        if ($slug === '') {
            $slug = 'collection';
        }
        if ($this->legacySchema) {
            $statement = $this->db->prepare(
                'INSERT INTO collections (id, name, slug, description, banner)
                 VALUES (:id, :name, :slug, :description, :banner)'
            );
            $statement->execute([
                'id' => 'COL-' . strtoupper(bin2hex(random_bytes(6))),
                'name' => $name,
                'slug' => $slug . '-' . substr(bin2hex(random_bytes(3)), 0, 6),
                'description' => trim($input['description'] ?? ''),
                'banner' => trim($input['banner'] ?? '') ?: null,
            ]);
            return;
        }

        $statement = $this->db->prepare(
            'INSERT INTO collections (name, slug, description, banner)
             VALUES (:name, :slug, :description, :banner)'
        );
        $statement->execute([
            'name' => $name,
            'slug' => $slug . '-' . substr(bin2hex(random_bytes(3)), 0, 6),
            'description' => trim($input['description'] ?? ''),
            'banner' => trim($input['banner'] ?? '') ?: null,
        ]);
    }

    public function getCustomers(array $filters = []): array
    {
        if ($this->legacySchema) {
            $sql = "SELECT CONCAT('CUS-', u.id) AS id,
                           COALESCE(NULLIF(u.full_name, ''), u.email) AS name,
                           u.email, u.phone, '' AS address, 'standard' AS tier,
                           COALESCE(SUM(o.total_amount), 0) AS total_spent,
                           DATE(u.created_at) AS joined_at, u.created_at,
                           COUNT(o.id) AS orders_count, MAX(o.created_at) AS last_order
                    FROM users u LEFT JOIN orders o ON o.user_id = u.id
                    WHERE u.role = 'customer'";
            $params = [];
            if ($filters['search'] !== '') {
                $sql .= ' AND (u.full_name LIKE :customer_name_search
                               OR u.email LIKE :customer_email_search
                               OR u.phone LIKE :customer_phone_search)';
                $term = '%' . $filters['search'] . '%';
                $params['customer_name_search'] = $term;
                $params['customer_email_search'] = $term;
                $params['customer_phone_search'] = $term;
            }
            if ($filters['tier'] !== 'all' && strtolower($filters['tier']) !== 'standard') {
                return [];
            }
            $sql .= ' GROUP BY u.id ORDER BY u.created_at DESC';
            $statement = $this->db->prepare($sql);
            $statement->execute($params);
            return array_map(static function (array $customer): array {
                $customer['city'] = '—';
                $customer['tier'] = 'Standard';
                $customer['total_spent'] = (float)$customer['total_spent'];
                return $customer;
            }, $statement->fetchAll());
        }

        $sql = 'SELECT c.*,
                       COUNT(o.id) AS orders_count,
                       MAX(o.created_at) AS last_order
                FROM customers c LEFT JOIN orders o ON o.customer_id = c.id
                WHERE 1 = 1';
        $params = [];
        if ($filters['search'] !== '') {
            $sql .= ' AND (c.name LIKE :customer_name_search OR c.email LIKE :customer_email_search OR c.phone LIKE :customer_phone_search)';
            $term = '%' . $filters['search'] . '%';
            $params['customer_name_search'] = $term;
            $params['customer_email_search'] = $term;
            $params['customer_phone_search'] = $term;
        }
        if ($filters['tier'] !== 'all') {
            $sql .= ' AND LOWER(c.tier) = LOWER(:tier)';
            $params['tier'] = $filters['tier'];
        }
        $sql .= ' GROUP BY c.id ORDER BY c.created_at DESC';
        $statement = $this->db->prepare($sql);
        $statement->execute($params);
        return array_map(static function (array $customer): array {
            $customer['city'] = trim((string)($customer['address'] ?? '')) ?: '—';
            $customer['tier'] = ucfirst(str_replace('_', ' ', $customer['tier']));
            return $customer;
        }, $statement->fetchAll());
    }

    public function getCoupons(): array
    {
        $sql = $this->legacySchema
            ? 'SELECT c.*, c.usage_count AS used_count FROM coupons c ORDER BY c.created_at DESC'
            : 'SELECT * FROM coupons ORDER BY created_at DESC';
        return $this->db->query($sql)->fetchAll();
    }

    public function saveCoupon(array $input): void
    {
        $statement = $this->db->prepare(
            'INSERT INTO coupons
             (id, code, description, type, value, min_order, usage_limit, expiry_date, status)
             VALUES (:id, :code, :description, :type, :value, :min_order, :usage_limit, :expiry_date, "active")'
        );
        $statement->execute([
            'id' => 'CPN-' . strtoupper(bin2hex(random_bytes(4))),
            'code' => strtoupper(trim($input['code'])),
            'description' => trim($input['description']),
            'type' => $input['type'] === 'percent' ? 'percent' : 'fixed',
            'value' => max(0, (float)$input['value']),
            'min_order' => max(0, (float)$input['min_order']),
            'usage_limit' => max(1, (int)$input['usage_limit']),
            'expiry_date' => $input['expiry_date'],
        ]);
    }

    public function deleteCoupon(string $id): void
    {
        $statement = $this->db->prepare('DELETE FROM coupons WHERE id = :id');
        $statement->execute(['id' => $id]);
    }

    public function getSettings(): array
    {
        require_once __DIR__ . '/OrderService.php';
        return OrderService::readMergedSettings($this->db);
    }

    public function saveSettings(array $settings): void
    {
        $statement = $this->db->prepare(
            'INSERT INTO settings (setting_key, setting_value) VALUES ("storefront", :value)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
        );
        $statement->execute([
            'value' => json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
        ]);
    }

    public function getDashboardStats(): array
    {
        if ($this->legacySchema) {
            return [
                'revenue' => (float)$this->db->query(
                    "SELECT COALESCE(SUM(GREATEST(0, total_amount - COALESCE(refunded_amount, 0))), 0)
                     FROM orders
                     WHERE current_status = 'completed'
                       AND YEAR(COALESCE(customer_confirmed_at, created_at)) = YEAR(CURRENT_DATE())
                       AND MONTH(COALESCE(customer_confirmed_at, created_at)) = MONTH(CURRENT_DATE())"
                )->fetchColumn(),
                'orders' => (int)$this->db->query('SELECT COUNT(*) FROM orders')->fetchColumn(),
                'pending' => (int)$this->db->query(
                    "SELECT COUNT(*) FROM orders WHERE current_status IN ('pending', 'confirmed', 'processing', 'cancel_requested')"
                )->fetchColumn(),
                'pending_cancels' => (int)$this->db->query(
                    "SELECT COUNT(*) FROM order_cancel_requests WHERE status = 'pending'"
                )->fetchColumn(),
                'pending_returns' => (int)$this->db->query(
                    "SELECT COUNT(*) FROM order_return_requests WHERE status IN ('pending_review','approved','awaiting_shipment','received','processing_exchange')"
                )->fetchColumn(),
                'failed_emails' => (int)$this->db->query(
                    "SELECT COUNT(*) FROM email_logs WHERE status = 'failed'"
                )->fetchColumn(),
                'inventory' => (int)$this->db->query(
                    'SELECT COALESCE(SUM(stock_quantity), 0) FROM products'
                )->fetchColumn(),
                'low_stock' => (int)$this->db->query(
                    'SELECT COUNT(*) FROM products WHERE stock_quantity <= 10'
                )->fetchColumn(),
                'customers' => (int)$this->db->query(
                    "SELECT COUNT(*) FROM users WHERE role = 'customer'"
                )->fetchColumn(),
            ];
        }

        $stats = [
            'revenue' => (float)$this->db->query(
                "SELECT COALESCE(SUM(GREATEST(0, total - COALESCE(refunded_amount, 0))), 0) FROM orders
                 WHERE order_status = 'completed'
                   AND YEAR(COALESCE(customer_confirmed_at, created_at)) = YEAR(CURRENT_DATE())
                   AND MONTH(COALESCE(customer_confirmed_at, created_at)) = MONTH(CURRENT_DATE())"
            )->fetchColumn(),
            'orders' => (int)$this->db->query('SELECT COUNT(*) FROM orders')->fetchColumn(),
            'pending' => (int)$this->db->query("SELECT COUNT(*) FROM orders WHERE order_status IN ('pending', 'confirmed', 'processing', 'cancel_requested')")->fetchColumn(),
            'pending_cancels' => 0,
            'pending_returns' => 0,
            'failed_emails' => 0,
            'inventory' => (int)$this->db->query('SELECT COALESCE(SUM(stock), 0) FROM products')->fetchColumn(),
            'low_stock' => (int)$this->db->query('SELECT COUNT(*) FROM products WHERE stock <= 10')->fetchColumn(),
            'customers' => (int)$this->db->query('SELECT COUNT(*) FROM customers')->fetchColumn(),
        ];
        return $stats;
    }

    public function getRevenueHistory(int $days): array
    {
        $days = $days === 30 ? 30 : 7;
        $start = (new DateTimeImmutable('today'))->modify('-' . ($days - 1) . ' days');
        $statusColumn = $this->legacySchema ? 'current_status' : 'order_status';
        $totalColumn = $this->legacySchema ? 'total_amount' : 'total';
        $statement = $this->db->prepare(
            "SELECT DATE(COALESCE(customer_confirmed_at, created_at)) AS sale_date,
                    SUM(GREATEST(0, {$totalColumn} - COALESCE(refunded_amount, 0))) AS revenue
             FROM orders
             WHERE COALESCE(customer_confirmed_at, created_at) >= :start
               AND {$statusColumn} = 'completed'
             GROUP BY DATE(COALESCE(customer_confirmed_at, created_at))
             ORDER BY sale_date"
        );
        $statement->execute(['start' => $start->format('Y-m-d 00:00:00')]);
        $rows = [];
        foreach ($statement->fetchAll() as $row) {
            $rows[$row['sale_date']] = (float)$row['revenue'];
        }

        $history = [];
        for ($offset = 0; $offset < $days; $offset++) {
            $date = $start->modify('+' . $offset . ' days');
            $key = $date->format('Y-m-d');
            $history[] = [
                'label' => $date->format('d/m'),
                'value' => $rows[$key] ?? 0,
            ];
        }
        return $history;
    }

    public function getRecentOrders(): array
    {
        $statement = $this->db->query(
            $this->legacySchema
                ? $this->legacyOrderSelect() . ' ORDER BY o.created_at DESC LIMIT 5'
                : 'SELECT * FROM orders ORDER BY created_at DESC LIMIT 5'
        );
        return array_map([$this, 'mapOrder'], $statement->fetchAll());
    }

    public function getTopProducts(): array
    {
        $sql = $this->legacySchema
            ? "SELECT p.*, p.stock_quantity AS stock, p.sizes_json AS sizes,
                      p.colors_json AS colors, COALESCE(p.image_url, p.fallback_image) AS image,
                      COALESCE(NULLIF(p.status, ''), IF(p.is_active = 1, 'active', 'draft')) AS status,
                      p.collection_name AS collection_name
               FROM products p ORDER BY p.sales_count DESC LIMIT 4"
            : 'SELECT p.*, c.name AS collection_name
               FROM products p LEFT JOIN collections c ON c.id = p.collection_id
               ORDER BY p.sales_count DESC LIMIT 4';
        $statement = $this->db->query($sql);
        return array_map([$this, 'mapProduct'], $statement->fetchAll());
    }

    private function saveLegacyProduct(array $input): void
    {
        $collection = trim($input['collection'] ?? '');
        if ($collection !== '') {
            $collectionCheck = $this->db->prepare('SELECT 1 FROM collections WHERE name = :name LIMIT 1');
            $collectionCheck->execute(['name' => $collection]);
            if (!$collectionCheck->fetchColumn()) {
                throw new InvalidArgumentException('Bộ sưu tập đã chọn không tồn tại.');
            }
        }

        $values = [
            'name' => trim($input['name']),
            'sku' => strtoupper(trim($input['sku'])),
            'category' => trim($input['category']),
            'collection_name' => $collection !== '' ? $collection : null,
            'price' => max(0, (float)$input['price']),
            'original_price' => ($input['original_price'] ?? '') === ''
                ? null
                : max(0, (float)$input['original_price']),
            'stock_quantity' => max(0, (int)$input['stock']),
            'sizes_json' => json_encode($this->splitList($input['sizes']), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'colors_json' => json_encode($this->splitList($input['colors']), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'image_url' => trim($input['image'] ?? '') ?: null,
            'description' => trim($input['description'] ?? ''),
            'status' => ($input['status'] ?? '') === 'draft' ? 'draft' : 'active',
        ];
        $values['is_active'] = $values['status'] === 'active' ? 1 : 0;

        if (($input['id'] ?? '') !== '') {
            $values['id'] = $input['id'];
            $statement = $this->db->prepare(
                'UPDATE products
                 SET name = :name, sku = :sku, category = :category,
                     collection_name = :collection_name, price = :price,
                     original_price = :original_price, stock_quantity = :stock_quantity,
                     sizes_json = :sizes_json, colors_json = :colors_json,
                     image_url = :image_url, description = :description,
                     status = :status, is_active = :is_active
                 WHERE id = :id'
            );
            $statement->execute($values);
            $this->syncProductVariants((int)$values['id'], $values['sku'], $this->splitList($input['sizes']), $this->splitList($input['colors']), $values['stock_quantity']);
            return;
        }

        $values['sales_count'] = 0;
        $statement = $this->db->prepare(
            'INSERT INTO products
             (name, sku, category, collection_name, price, original_price, stock_quantity,
              sizes_json, colors_json, image_url, description, status, is_active, sales_count)
             VALUES
             (:name, :sku, :category, :collection_name, :price, :original_price, :stock_quantity,
              :sizes_json, :colors_json, :image_url, :description, :status, :is_active, :sales_count)'
        );
        $statement->execute($values);
        $newId = (int)$this->db->lastInsertId();
        $this->syncProductVariants($newId, $values['sku'], $this->splitList($input['sizes']), $this->splitList($input['colors']), $values['stock_quantity']);
    }

    private function syncProductVariants(int $productId, string $sku, array $sizes, array $colors, int $totalStock): void
    {
        if ($productId <= 0) {
            return;
        }
        $sizes = $sizes ?: ['Freesize'];
        $colors = $colors ?: ['Tiêu chuẩn'];
        $existsStmt = $this->db->prepare('SELECT COUNT(*) FROM product_variants WHERE product_id = :pid');
        $existsStmt->execute(['pid' => $productId]);
        $hasExisting = (int)$existsStmt->fetchColumn() > 0;

        $insert = $this->db->prepare(
            'INSERT IGNORE INTO product_variants (product_id, size, color, sku_variant, stock_quantity)
             VALUES (:pid, :size, :color, :sku_var, :stock)'
        );
        $combos = [];
        foreach ($sizes as $s) {
            foreach ($colors as $c) {
                $combos[] = [$s, $c];
            }
        }
        $count = count($combos);
        $base = $count > 0 ? intdiv($totalStock, $count) : 0;
        $rem = $count > 0 ? ($totalStock % $count) : 0;
        foreach ($combos as $idx => [$s, $c]) {
            $insert->execute([
                'pid' => $productId,
                'size' => $s,
                'color' => $c,
                'sku_var' => $sku . '-' . strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', $s)) . '-' . ($idx + 1),
                'stock' => $hasExisting ? max(1, $base) : ($base + ($idx < $rem ? 1 : 0)),
            ]);
        }
    }

    private function legacyOrderSelect(): string
    {
        return <<<'SQL'
            SELECT o.order_code AS id,
                   COALESCE(NULLIF(o.customer_name, ''), NULLIF(u.full_name, ''), u.email, 'Khách hàng') AS customer_name,
                   COALESCE(NULLIF(o.customer_phone, ''), u.phone) AS customer_phone,
                   COALESCE(NULLIF(o.customer_email, ''), u.email) AS customer_email,
                   o.shipping_address,
                   COALESCE(NULLIF(o.subtotal, 0), o.total_amount) AS subtotal,
                   COALESCE(o.shipping_fee, 0) AS shipping_fee,
                   COALESCE(o.discount_amount, 0) AS discount,
                   o.coupon_code,
                   o.total_amount AS total,
                   (SELECT pm.name
                    FROM payments p
                    JOIN payment_methods pm ON pm.id = p.payment_method_id
                    WHERE p.order_id = o.id ORDER BY p.id DESC LIMIT 1) AS payment_method,
                   COALESCE((SELECT p.status FROM payments p
                             WHERE p.order_id = o.id ORDER BY p.id DESC LIMIT 1), 'pending') AS payment_status,
                   o.current_status AS order_status,
                   o.note,
                   o.cancel_reason,
                   o.created_at
            FROM orders o
            LEFT JOIN users u ON u.id = o.user_id
            SQL;
    }

    private function findCollectionId(string $name): ?int
    {
        if ($name === '') {
            return null;
        }
        $statement = $this->db->prepare('SELECT id FROM collections WHERE name = :name LIMIT 1');
        $statement->execute(['name' => $name]);
        $id = $statement->fetchColumn();
        if ($id === false) {
            throw new InvalidArgumentException('Bộ sưu tập đã chọn không tồn tại.');
        }
        return (int)$id;
    }

    private function splitList(string $value): array
    {
        return array_values(array_filter(array_map('trim', explode(',', $value)), static function (string $item): bool {
            return $item !== '';
        }));
    }

    private function mapProduct(array $product): array
    {
        $product['sizes'] = json_decode($product['sizes'] ?? '[]', true) ?: [];
        $product['colors'] = json_decode($product['colors'] ?? '[]', true) ?: [];
        $product['price'] = (float)$product['price'];
        $product['original_price'] = $product['original_price'] === null ? null : (float)$product['original_price'];
        $product['stock'] = (int)$product['stock'];
        $product['sales_count'] = (int)$product['sales_count'];
        $product['collection'] = $product['collection_name'] ?? '';
        return $product;
    }

    private function mapOrder(array $order): array
    {
        $order['total'] = (float)$order['total'];
        $order['subtotal'] = (float)$order['subtotal'];
        $order['shipping_fee'] = (float)$order['shipping_fee'];
        $order['discount'] = (float)$order['discount'];
        return $order;
    }
}
