<?php
/**
 * VAST STUDIO — Database Connection Manager (PDO)
 * Supports MySQL (Default for Laragon / XAMPP) & SQLite Fallback
 */

class DB {
    private static $instance = null;
    private $pdo;

    // Database Configuration
    const DB_TYPE = 'mysql'; // 'mysql' or 'sqlite'
    const DB_HOST = 'localhost';
    const DB_PORT = '3306';
    const DB_NAME = 'vast_studio';
    const DB_USER = 'root';
    const DB_PASS = ''; // Default in Laragon is empty
    const DB_CHARSET = 'utf8mb4';

    private function __construct() {
        try {
            // Attempt to connect to MySQL first
            $dsn = "mysql:host=" . self::DB_HOST . ";port=" . self::DB_PORT . ";dbname=" . self::DB_NAME . ";charset=" . self::DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            $this->pdo = new PDO($dsn, self::DB_USER, self::DB_PASS, $options);
        } catch (PDOException $e) {
            // If MySQL is not running or vast_studio DB does not exist, check for SQLite fallback
            $sqlitePath = __DIR__ . '/../database/vast_studio.sqlite';
            if (file_exists($sqlitePath)) {
                try {
                    $this->pdo = new PDO("sqlite:" . $sqlitePath);
                    $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                    return;
                } catch (Exception $sqe) {}
            }
            
            // Re-throw or handle error
            throw new Exception("Không thể kết nối CSDL MySQL. Vui lòng bật MySQL trong Laragon hoặc chạy setup_db.php: " . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function getPDO() {
        return self::getInstance()->pdo;
    }

    // Helper: Execute a prepared statement
    public static function query($sql, $params = []) {
        $stmt = self::getPDO()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    // Helper: Fetch all rows
    public static function fetchAll($sql, $params = []) {
        return self::query($sql, $params)->fetchAll();
    }

    // Helper: Fetch a single row
    public static function fetchOne($sql, $params = []) {
        return self::query($sql, $params)->fetch();
    }

    // Helper: Insert record and return last insert ID
    public static function insert($table, $data) {
        $fields = array_keys($data);
        $placeholders = ':' . implode(', :', $fields);
        $sql = "INSERT INTO `{$table}` (`" . implode('`, `', $fields) . "`) VALUES ({$placeholders})";
        
        $params = [];
        foreach ($data as $key => $val) {
            $params[':' . $key] = $val;
        }
        
        self::query($sql, $params);
        return self::getPDO()->lastInsertId();
    }

    // Helper: Update record
    public static function update($table, $data, $where, $whereParams = []) {
        $setClauses = [];
        $params = [];
        foreach ($data as $key => $val) {
            $setClauses[] = "`{$key}` = :set_{$key}";
            $params[':set_' . $key] = $val;
        }
        $setStr = implode(', ', $setClauses);
        $sql = "UPDATE `{$table}` SET {$setStr} WHERE {$where}";
        
        $allParams = array_merge($params, $whereParams);
        $stmt = self::query($sql, $allParams);
        return $stmt->rowCount();
    }

    // Helper: Delete record
    public static function delete($table, $where, $whereParams = []) {
        $sql = "DELETE FROM `{$table}` WHERE {$where}";
        $stmt = self::query($sql, $whereParams);
        return $stmt->rowCount();
    }
}
