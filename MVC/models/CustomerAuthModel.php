<?php

class CustomerAuthModel
{
    private PDO $db;
    private bool $legacySchema;
    private bool $legacyActiveColumn;

    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->legacySchema = (bool)$this->db->query("SHOW COLUMNS FROM products LIKE 'stock_quantity'")->fetch();
        $this->legacyActiveColumn = $this->legacySchema
            && (bool)$this->db->query("SHOW COLUMNS FROM users LIKE 'is_active'")->fetch();
    }

    public function register(array $input): array
    {
        foreach (['name', 'email', 'phone', 'password'] as $field) {
            if (!isset($input[$field]) || !is_string($input[$field])) {
                throw new InvalidArgumentException('Dữ liệu đăng ký không hợp lệ.');
            }
        }

        $name = trim((string)($input['name'] ?? ''));
        $email = strtolower(trim((string)($input['email'] ?? '')));
        $phone = preg_replace('/[\s().-]/', '', (string)($input['phone'] ?? ''));
        $password = (string)($input['password'] ?? '');

        if ($name === '' || preg_match('/^.{1,100}$/u', $name) !== 1) {
            throw new InvalidArgumentException('Họ tên không hợp lệ.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 191) {
            throw new InvalidArgumentException('Email không hợp lệ.');
        }
        if (!preg_match('/^0[35789][0-9]{8}$/D', $phone)) {
            throw new InvalidArgumentException('Số điện thoại phải có 10 chữ số hợp lệ.');
        }
        if (strlen($password) < 8 || strlen($password) > 72) {
            throw new InvalidArgumentException('Mật khẩu phải có từ 8 đến 72 ký tự.');
        }

        if ($this->legacySchema) {
            $existing = $this->db->prepare(
                "SELECT 1 FROM users
                 WHERE LOWER(email) = :email
                    OR REPLACE(REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '.', ''), '+', '') = :phone
                 LIMIT 1"
            );
            $existing->execute(['email' => $email, 'phone' => $phone]);
        } else {
            $existing = $this->db->prepare(
                "SELECT 1 FROM users WHERE LOWER(email) = :user_email
                 UNION ALL
                 SELECT 1 FROM customers
                 WHERE LOWER(email) = :customer_email
                    OR REPLACE(REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '.', ''), '+', '') = :phone
                 LIMIT 1"
            );
            $existing->execute([
                'user_email' => $email,
                'customer_email' => $email,
                'phone' => $phone,
            ]);
        }
        if ($existing->fetchColumn()) {
            throw new InvalidArgumentException('Email hoặc số điện thoại đã được đăng ký.');
        }

        $customerId = 'CUS-' . strtoupper(bin2hex(random_bytes(6)));
        $this->db->beginTransaction();
        try {
            if ($this->legacySchema) {
                $activeColumn = $this->legacyActiveColumn ? ', is_active' : '';
                $activeValue = $this->legacyActiveColumn ? ', 1' : '';
                $userStatement = $this->db->prepare(
                    "INSERT INTO users (full_name, email, phone, password_hash, role{$activeColumn})
                     VALUES (:name, :email, :phone, :password_hash, 'customer'{$activeValue})"
                );
                $userStatement->execute([
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone,
                    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                ]);
            } else {
                $userStatement = $this->db->prepare(
                    "INSERT INTO users (name, email, password_hash, role)
                     VALUES (:name, :email, :password_hash, 'customer')"
                );
                $userStatement->execute([
                    'name' => $name,
                    'email' => $email,
                    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                ]);
            }
            $userId = (int)$this->db->lastInsertId();

            if (!$this->legacySchema) {
                $customerStatement = $this->db->prepare(
                    'INSERT INTO customers (id, name, email, phone, tier, joined_at)
                     VALUES (:id, :name, :email, :phone, :tier, :joined_at)'
                );
                $customerStatement->execute([
                    'id' => $customerId,
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone,
                    'tier' => 'Standard',
                    'joined_at' => date('Y-m-d'),
                ]);
            }
            $this->db->commit();
        } catch (PDOException $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }

        return $this->getCustomerUser($userId);
    }

    public function authenticate(string $identifier, string $password): ?array
    {
        $identifier = trim($identifier);
        $email = strtolower($identifier);
        $phone = preg_replace('/[\s().-]/', '', $identifier);
        if ($this->legacySchema) {
            $activeCondition = $this->legacyActiveColumn ? ' AND u.is_active = 1' : '';
            $statement = $this->db->prepare(
                "SELECT u.id AS user_id, u.password_hash,
                        CONCAT('CUS-', u.id) AS customer_id,
                        COALESCE(NULLIF(u.full_name, ''), u.email) AS name,
                        u.email, u.phone, 'Standard' AS tier,
                        COALESCE(SUM(o.total_amount), 0) AS total_spent,
                        DATE(u.created_at) AS joined_at, COUNT(o.id) AS orders_count
                 FROM users u
                 LEFT JOIN orders o ON o.user_id = u.id
                 WHERE u.role = 'customer'{$activeCondition}
                   AND (LOWER(u.email) = :email
                        OR REPLACE(REPLACE(REPLACE(REPLACE(u.phone, ' ', ''), '-', ''), '.', ''), '+', '') = :phone)
                 GROUP BY u.id
                 LIMIT 1"
            );
            $statement->execute(['email' => $email, 'phone' => $phone]);
            $user = $statement->fetch();
            if (!$user || !password_verify($password, $user['password_hash'])) {
                return null;
            }
            return $this->mapCustomerUser($user);
        }

        $statement = $this->db->prepare(
            "SELECT u.id AS user_id, u.password_hash,
                    c.id AS customer_id, c.name, c.email, c.phone, c.tier,
                    c.total_spent, c.joined_at,
                    (SELECT COUNT(*) FROM orders o WHERE o.customer_id = c.id) AS orders_count
             FROM users u
             INNER JOIN customers c ON c.email = u.email
             WHERE u.role = 'customer'
               AND (LOWER(u.email) = :email
                    OR REPLACE(REPLACE(REPLACE(REPLACE(c.phone, ' ', ''), '-', ''), '.', ''), '+', '') = :phone)
             LIMIT 1"
        );
        $statement->execute([
            'email' => $email,
            'phone' => $phone,
        ]);
        $user = $statement->fetch();
        if (!$user || !password_verify($password, $user['password_hash'])) {
            return null;
        }

        return $this->mapCustomerUser($user);
    }

    private function getCustomerUser(int $userId): array
    {
        if ($this->legacySchema) {
            $activeCondition = $this->legacyActiveColumn ? ' AND u.is_active = 1' : '';
            $statement = $this->db->prepare(
                "SELECT u.id AS user_id, u.password_hash,
                        CONCAT('CUS-', u.id) AS customer_id,
                        COALESCE(NULLIF(u.full_name, ''), u.email) AS name,
                        u.email, u.phone, 'Standard' AS tier,
                        COALESCE(SUM(o.total_amount), 0) AS total_spent,
                        DATE(u.created_at) AS joined_at, COUNT(o.id) AS orders_count
                 FROM users u
                 LEFT JOIN orders o ON o.user_id = u.id
                 WHERE u.id = :user_id AND u.role = 'customer'{$activeCondition}
                 GROUP BY u.id
                 LIMIT 1"
            );
            $statement->execute(['user_id' => $userId]);
            $user = $statement->fetch();
            if (!$user) {
                throw new RuntimeException('Không thể tải thông tin tài khoản vừa tạo.');
            }
            return $this->mapCustomerUser($user);
        }

        $statement = $this->db->prepare(
            "SELECT u.id AS user_id, c.id AS customer_id, c.name, c.email, c.phone, c.tier,
                    c.total_spent, c.joined_at,
                    (SELECT COUNT(*) FROM orders o WHERE o.customer_id = c.id) AS orders_count
             FROM users u
             INNER JOIN customers c ON c.email = u.email
             WHERE u.id = :user_id AND u.role = 'customer'
             LIMIT 1"
        );
        $statement->execute(['user_id' => $userId]);
        $user = $statement->fetch();
        if (!$user) {
            throw new RuntimeException('Không thể tải thông tin tài khoản vừa tạo.');
        }

        return $this->mapCustomerUser($user);
    }

    private function mapCustomerUser(array $user): array
    {
        return [
            'id' => $user['customer_id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'phone' => $user['phone'],
            'tier' => $user['tier'],
            'totalSpent' => (float)$user['total_spent'],
            'ordersCount' => (int)$user['orders_count'],
            'registeredAt' => $user['joined_at'],
        ];
    }
}
