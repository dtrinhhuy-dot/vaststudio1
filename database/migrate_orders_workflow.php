<?php
/**
 * Safe idempotent migration for VAST STUDIO order lifecycle, cancel, return & email workflow.
 * Preserves all existing tables and data.
 * CLI: php database/migrate_orders_workflow.php
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../MVC/models/OrderSchemaMigrator.php';

try {
    $db = getDatabaseConnection();
    $logs = OrderSchemaMigrator::migrate($db);
    foreach ($logs as $line) {
        fwrite(STDOUT, '[MIGRATE] ' . $line . PHP_EOL);
    }
    fwrite(STDOUT, "Migration completed successfully.\n");
} catch (Throwable $exception) {
    fwrite(STDERR, 'Migration failed: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
