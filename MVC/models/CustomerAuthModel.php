<?php

class CustomerAuthModel
{
    private PDO $db;
    private bool $legacySchema;
    private bool $hasCustomersTable;

    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->legacySchema = (bool)$this->db->query("SHOW COLUMNS FROM users LIKE 'full_name'")->fetch();
        $this->hasCustomersTable = (bool)$this->db->query("SHOW TABLES LIKE 'customers'")->fetchColumn();
    }

    public function authenticate(string $identifier, string $password): ?array
    {
        $identifier = trim($identifier);
        if ($identifier === '' || $password === '') {
            return null;
        }

        $cleanEmail = strtolower($identifier);
        $cleanPhone = $this->normalizePhone($identifier);

        if ($this->legacySchema) {
            $sql = "SELECT id, email, password_hash, COALESCE(NULLIF(full_name, ''), email) AS name,
                           COALESCE(phone, '') AS phone
                    FROM users
                    WHERE role = 'customer' AND is_active = 1
                      AND (LOWER(email) = :email";
            $params = ['email' => $cleanEmail];
            if ($cleanPhone !== '') {
                $sql .= " OR REPLACE(REPLACE(REPLACE(COALESCE(phone, ''), ' ', ''), '.', ''), '-', '') = :phone";
                $params['phone'] = $cleanPhone;
            }
            $sql .= ') LIMIT 1';
            $statement = $this->db->prepare($sql);
            $statement->execute($params);
            $user = $statement->fetch();
        } else {
            $sql = "SELECT u.id, u.email, u.password_hash, u.name,
                           COALESCE(c.phone, '') AS phone
                    FROM users u
                    LEFT JOIN customers c ON LOWER(c.email) = LOWER(u.email)
                    WHERE u.role = 'customer'
                      AND (LOWER(u.email) = :email";
            $params = ['email' => $cleanEmail];
            if ($cleanPhone !== '') {
                $sql .= " OR REPLACE(REPLACE(REPLACE(COALESCE(c.phone, ''), ' ', ''), '.', ''), '-', '') = :phone";
                $params['phone'] = $cleanPhone;
            }
            $sql .= ') LIMIT 1';
            $statement = $this->db->prepare($sql);
            $statement->execute($params);
            $user = $statement->fetch();
        }

        if (!$user || !password_verify($password, (string)$user['password_hash'])) {
            return null;
        }

        return $this->getProfile((int)$user['id']);
    }

    public function register(array $input): array
    {
        $name = trim((string)($input['name'] ?? ''));
        $email = strtolower(trim((string)($input['email'] ?? '')));
        $rawPhone = trim((string)($input['phone'] ?? ''));
        $phone = $this->normalizePhone($rawPhone);
        $password = (string)($input['password'] ?? '');
        $confirmPassword = (string)($input['password_confirm'] ?? '');
        $city = trim((string)($input['city'] ?? ''));

        if ($name === '' || mb_strlen($name) < 2) {
            throw new InvalidArgumentException('Vui lòng nhập họ và tên hợp lệ (ít nhất 2 ký tự).');
        }
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Địa chỉ email không hợp lệ.');
        }
        if (!$this->isValidPhone($phone)) {
            throw new InvalidArgumentException('Số điện thoại không hợp lệ (cần 10–11 chữ số, bắt đầu bằng 0).');
        }
        if (strlen($password) < 6) {
            throw new InvalidArgumentException('Mật khẩu phải có ít nhất 6 ký tự.');
        }
        if ($password !== $confirmPassword) {
            throw new InvalidArgumentException('Mật khẩu xác nhận không khớp. Vui lòng kiểm tra lại.');
        }

        $emailCheck = $this->db->prepare('SELECT id FROM users WHERE LOWER(email) = :email LIMIT 1');
        $emailCheck->execute(['email' => $email]);
        if ($emailCheck->fetchColumn()) {
            throw new InvalidArgumentException('Email này đã được đăng ký. Vui lòng chọn đăng nhập!');
        }

        if ($this->legacySchema) {
            $phoneCheck = $this->db->prepare(
                "SELECT id FROM users
                 WHERE REPLACE(REPLACE(REPLACE(COALESCE(phone, ''), ' ', ''), '.', ''), '-', '') = :phone
                 LIMIT 1"
            );
            $phoneCheck->execute(['phone' => $phone]);
            if ($phoneCheck->fetchColumn()) {
                throw new InvalidArgumentException('Số điện thoại này đã được đăng ký tài khoản!');
            }
        } elseif ($this->hasCustomersTable) {
            $phoneCheck = $this->db->prepare(
                "SELECT id FROM customers
                 WHERE REPLACE(REPLACE(REPLACE(COALESCE(phone, ''), ' ', ''), '.', ''), '-', '') = :phone
                 LIMIT 1"
            );
            $phoneCheck->execute(['phone' => $phone]);
            if ($phoneCheck->fetchColumn()) {
                throw new InvalidArgumentException('Số điện thoại này đã được đăng ký tài khoản!');
            }
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $this->db->beginTransaction();

        try {
            if ($this->legacySchema) {
                $insertUser = $this->db->prepare(
                    "INSERT INTO users (email, password_hash, full_name, phone, role, is_active)
                     VALUES (:email, :password_hash, :full_name, :phone, 'customer', 1)"
                );
                $insertUser->execute([
                    'email' => $email,
                    'password_hash' => $hash,
                    'full_name' => $name,
                    'phone' => $phone,
                ]);
            } else {
                $insertUser = $this->db->prepare(
                    "INSERT INTO users (name, email, password_hash, role)
                     VALUES (:name, :email, :password_hash, 'customer')"
                );
                $insertUser->execute([
                    'name' => $name,
                    'email' => $email,
                    'password_hash' => $hash,
                ]);
            }

            $userId = (int)$this->db->lastInsertId();

            if ($this->hasCustomersTable) {
                $this->upsertCustomerRecord($userId, $name, $email, $phone, $city);
            }

            $this->db->commit();
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }

        $profile = $this->getProfile($userId);
        if (!$profile) {
            throw new RuntimeException('Không thể khởi tạo hồ sơ tài khoản sau khi đăng ký.');
        }
        return $profile;
    }

    public function getProfile(int $userId): ?array
    {
        if ($userId <= 0) {
            return null;
        }

        if ($this->legacySchema) {
            $statement = $this->db->prepare(
                "SELECT id, email, COALESCE(NULLIF(full_name, ''), email) AS name,
                        COALESCE(phone, '') AS phone, DATE(created_at) AS joined_at
                 FROM users
                 WHERE id = :id AND role = 'customer' AND is_active = 1
                 LIMIT 1"
            );
            $statement->execute(['id' => $userId]);
            $user = $statement->fetch();
            if (!$user) {
                return null;
            }

            $address = '';
            $tier = 'Standard';
            if ($this->hasCustomersTable) {
                $custStmt = $this->db->prepare(
                    'SELECT address, tier FROM customers WHERE LOWER(email) = :email LIMIT 1'
                );
                $custStmt->execute(['email' => strtolower($user['email'])]);
                $cust = $custStmt->fetch();
                if ($cust) {
                    $address = trim((string)($cust['address'] ?? ''));
                    $tier = trim((string)($cust['tier'] ?? '')) ?: 'Standard';
                }
            }

            $statsStmt = $this->db->prepare(
                "SELECT COUNT(*) AS orders_count,
                        COALESCE(SUM(CASE WHEN current_status <> 'cancelled' THEN total_amount ELSE 0 END), 0) AS total_spent
                 FROM orders
                 WHERE user_id = :user_id"
            );
            $statsStmt->execute(['user_id' => $userId]);
            $stats = $statsStmt->fetch() ?: ['orders_count' => 0, 'total_spent' => 0];

            return [
                'id' => (int)$user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'phone' => $user['phone'],
                'city' => $address !== '' ? $address : 'Hà Nội',
                'address' => $address,
                'tier' => $this->formatTier($tier, (float)$stats['total_spent']),
                'orders_count' => (int)$stats['orders_count'],
                'total_spent' => (float)$stats['total_spent'],
                'joined_at' => $user['joined_at'] ?? date('Y-m-d'),
            ];
        }

        $statement = $this->db->prepare(
            "SELECT u.id, u.email, u.name, DATE(u.created_at) AS joined_at,
                    c.id AS customer_id, COALESCE(c.phone, '') AS phone,
                    COALESCE(c.address, '') AS address, COALESCE(c.tier, 'Standard') AS tier
             FROM users u
             LEFT JOIN customers c ON LOWER(c.email) = LOWER(u.email)
             WHERE u.id = :id AND u.role = 'customer'
             LIMIT 1"
        );
        $statement->execute(['id' => $userId]);
        $user = $statement->fetch();
        if (!$user) {
            return null;
        }

        $statsStmt = $this->db->prepare(
            "SELECT COUNT(*) AS orders_count,
                    COALESCE(SUM(CASE WHEN order_status <> 'cancelled' THEN total ELSE 0 END), 0) AS total_spent
             FROM orders
             WHERE LOWER(customer_email) = :email OR (:customer_id <> '' AND customer_id = :customer_id_match)"
        );
        $customerId = (string)($user['customer_id'] ?? '');
        $statsStmt->execute([
            'email' => strtolower($user['email']),
            'customer_id' => $customerId,
            'customer_id_match' => $customerId,
        ]);
        $stats = $statsStmt->fetch() ?: ['orders_count' => 0, 'total_spent' => 0];

        $address = trim((string)($user['address'] ?? ''));
        return [
            'id' => (int)$user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'phone' => $user['phone'],
            'city' => $address !== '' ? $address : 'Hà Nội',
            'address' => $address,
            'tier' => $this->formatTier((string)$user['tier'], (float)$stats['total_spent']),
            'orders_count' => (int)$stats['orders_count'],
            'total_spent' => (float)$stats['total_spent'],
            'joined_at' => $user['joined_at'] ?? date('Y-m-d'),
        ];
    }

    public function getUserOrders(int $userId, string $email): array
    {
        if ($this->legacySchema) {
            $statement = $this->db->prepare(
                "SELECT o.id AS numeric_id, o.order_code AS id, o.total_amount AS total,
                        o.current_status AS order_status, o.shipping_address, o.created_at
                 FROM orders o
                 WHERE o.user_id = :user_id
                 ORDER BY o.created_at DESC
                 LIMIT 20"
            );
            $statement->execute(['user_id' => $userId]);
            $orders = $statement->fetchAll();

            $itemStmt = $this->db->prepare(
                "SELECT COALESCE(p.name, 'Sản phẩm VAST STUDIO') AS name, oi.quantity, oi.unit_price AS price
                 FROM order_items oi
                 LEFT JOIN products p ON p.id = oi.product_id
                 WHERE oi.order_id = :order_id
                 ORDER BY oi.id"
            );
            foreach ($orders as &$order) {
                $itemStmt->execute(['order_id' => $order['numeric_id']]);
                $order['items'] = $itemStmt->fetchAll();
                $order['total'] = (float)$order['total'];
            }
            unset($order);
            return $orders;
        }

        $statement = $this->db->prepare(
            "SELECT o.id, o.total, o.order_status, o.shipping_address, o.created_at
             FROM orders o
             LEFT JOIN customers c ON c.id = o.customer_id
             WHERE LOWER(o.customer_email) = :email OR LOWER(c.email) = :cust_email
             ORDER BY o.created_at DESC
             LIMIT 20"
        );
        $cleanEmail = strtolower(trim($email));
        $statement->execute(['email' => $cleanEmail, 'cust_email' => $cleanEmail]);
        $orders = $statement->fetchAll();

        $itemStmt = $this->db->prepare(
            "SELECT product_name AS name, quantity, price
             FROM order_items
             WHERE order_id = :order_id
             ORDER BY id"
        );
        foreach ($orders as &$order) {
            $itemStmt->execute(['order_id' => $order['id']]);
            $order['items'] = $itemStmt->fetchAll();
            $order['total'] = (float)$order['total'];
        }
        unset($order);
        return $orders;
    }

    public function updateProfile(int $userId, array $input): array
    {
        $existing = $this->getProfile($userId);
        if (!$existing) {
            throw new InvalidArgumentException('Không tìm thấy tài khoản.');
        }

        $name = trim((string)($input['name'] ?? ''));
        $phone = $this->normalizePhone((string)($input['phone'] ?? ''));
        $city = trim((string)($input['city'] ?? ''));

        if ($name === '' || mb_strlen($name) < 2) {
            throw new InvalidArgumentException('Vui lòng nhập họ và tên hợp lệ.');
        }
        if (!$this->isValidPhone($phone)) {
            throw new InvalidArgumentException('Số điện thoại không hợp lệ (cần 10–11 chữ số, bắt đầu bằng 0).');
        }

        if ($this->legacySchema) {
            $phoneCheck = $this->db->prepare(
                "SELECT id FROM users
                 WHERE id <> :id
                   AND REPLACE(REPLACE(REPLACE(COALESCE(phone, ''), ' ', ''), '.', ''), '-', '') = :phone
                 LIMIT 1"
            );
            $phoneCheck->execute(['id' => $userId, 'phone' => $phone]);
            if ($phoneCheck->fetchColumn()) {
                throw new InvalidArgumentException('Số điện thoại này đang được sử dụng bởi tài khoản khác.');
            }

            $stmt = $this->db->prepare('UPDATE users SET full_name = :name, phone = :phone WHERE id = :id');
            $stmt->execute(['name' => $name, 'phone' => $phone, 'id' => $userId]);
        } else {
            if ($this->hasCustomersTable) {
                $phoneCheck = $this->db->prepare(
                    "SELECT id FROM customers
                     WHERE LOWER(email) <> :email
                       AND REPLACE(REPLACE(REPLACE(COALESCE(phone, ''), ' ', ''), '.', ''), '-', '') = :phone
                     LIMIT 1"
                );
                $phoneCheck->execute(['email' => strtolower($existing['email']), 'phone' => $phone]);
                if ($phoneCheck->fetchColumn()) {
                    throw new InvalidArgumentException('Số điện thoại này đang được sử dụng bởi tài khoản khác.');
                }
            }
            $stmt = $this->db->prepare('UPDATE users SET name = :name WHERE id = :id');
            $stmt->execute(['name' => $name, 'id' => $userId]);
        }

        if ($this->hasCustomersTable) {
            $this->upsertCustomerRecord($userId, $name, $existing['email'], $phone, $city);
        }

        return $this->getProfile($userId) ?? $existing;
    }

    public function changePassword(int $userId, string $currentPassword, string $newPassword, string $confirmPassword): void
    {
        if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
            throw new InvalidArgumentException('Vui lòng nhập đầy đủ mật khẩu hiện tại và mật khẩu mới.');
        }
        if (strlen($newPassword) < 6) {
            throw new InvalidArgumentException('Mật khẩu mới phải có ít nhất 6 ký tự.');
        }
        if ($newPassword !== $confirmPassword) {
            throw new InvalidArgumentException('Mật khẩu xác nhận không khớp.');
        }

        $stmt = $this->db->prepare("SELECT password_hash FROM users WHERE id = :id AND role = 'customer' LIMIT 1");
        $stmt->execute(['id' => $userId]);
        $hash = $stmt->fetchColumn();
        if (!$hash || !password_verify($currentPassword, (string)$hash)) {
            throw new InvalidArgumentException('Mật khẩu hiện tại không chính xác.');
        }

        $update = $this->db->prepare('UPDATE users SET password_hash = :hash WHERE id = :id');
        $update->execute([
            'hash' => password_hash($newPassword, PASSWORD_DEFAULT),
            'id' => $userId,
        ]);
    }

    private function upsertCustomerRecord(int $userId, string $name, string $email, string $phone, string $address): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO customers (id, name, email, phone, address, tier, total_spent, joined_at)
             VALUES (:id, :name, :email, :phone, :address, 'Standard', 0, :joined_at)
             ON DUPLICATE KEY UPDATE
                name = VALUES(name),
                phone = VALUES(phone),
                address = VALUES(address)"
        );
        $stmt->execute([
            'id' => 'CUS-' . $userId,
            'name' => $name,
            'email' => strtolower($email),
            'phone' => $phone,
            'address' => $address,
            'joined_at' => date('Y-m-d'),
        ]);
    }

    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/[^0-9]/', '', $phone) ?? '';
        return $digits;
    }

    private function isValidPhone(string $digits): bool
    {
        return (bool)preg_match('/^0[0-9]{9,10}$/', $digits);
    }

    private function formatTier(string $storedTier, float $totalSpent): string
    {
        if (strtolower(trim($storedTier)) !== '' && strtolower(trim($storedTier)) !== 'standard') {
            return ucfirst(str_replace('_', ' ', $storedTier));
        }
        if ($totalSpent >= 8000000) {
            return 'VIP Black Card';
        }
        if ($totalSpent >= 4000000) {
            return 'Gold Member';
        }
        if ($totalSpent >= 1500000) {
            return 'Silver Member';
        }
        return 'Standard';
    }
}
