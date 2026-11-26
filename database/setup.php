<?php
/**
 * Import the MySQL schema.
 * CLI: php database/setup.php
 */

$schemaFile = __DIR__ . '/schema.sql';

if (!is_file($schemaFile)) {
    fwrite(STDERR, "Missing schema file: {$schemaFile}\n");
    exit(1);
}

$host = getenv('DB_HOST') ?: '127.0.0.1';
$port = getenv('DB_PORT') ?: '3306';
$database = getenv('DB_DATABASE') ?: 'vaststudio';
$username = getenv('DB_USERNAME') ?: 'root';
$password = getenv('DB_PASSWORD') ?: '';

if (!preg_match('/\A[A-Za-z0-9_]{1,64}\z/', $database)) {
    fwrite(STDERR, "Invalid DB_DATABASE value. Use 1-64 letters, numbers, or underscores.\n");
    exit(1);
}

try {
    $pdo = new PDO(
        "mysql:host={$host};port={$port};charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_MULTI_STATEMENTS => true,
        ]
    );

    $quotedDatabase = '`' . $database . '`';
    $pdo->exec(
        'CREATE DATABASE IF NOT EXISTS ' . $quotedDatabase .
        ' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
    );
    $pdo->exec('USE ' . $quotedDatabase);

    $productsTable = $pdo->query("SHOW TABLES LIKE 'products'")->fetchColumn();
    if ($productsTable !== false) {
        $legacyProducts = $pdo->query("SHOW COLUMNS FROM products LIKE 'stock_quantity'")->fetch();
        if ($legacyProducts) {
            $pdo->exec(
                'CREATE TABLE IF NOT EXISTS settings (
                    setting_key VARCHAR(100) NOT NULL,
                    setting_value JSON DEFAULT NULL,
                    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (setting_key)
                ) ENGINE=InnoDB'
            );
            fwrite(STDOUT, "Connected to {$database}; existing tables and records were preserved.\n");
            fwrite(STDOUT, "Detected the existing store schema; the PHP admin will use its current products and orders.\n");
            exit(0);
        }
    }

    $schema = file_get_contents($schemaFile);
    if ($schema === false) {
        throw new RuntimeException("Unable to read schema file: {$schemaFile}");
    }
    $pdo->exec($schema);

    echo "Database {$database} and tables created successfully.\n";
} catch (PDOException $exception) {
    fwrite(STDERR, "Database setup failed: " . $exception->getMessage() . "\n");
    exit(1);
} catch (RuntimeException $exception) {
    fwrite(STDERR, "Database setup failed: " . $exception->getMessage() . "\n");
    exit(1);
}
