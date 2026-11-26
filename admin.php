<?php

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/AdminController.php';

try {
    $controller = new AdminController(new AdminStoreModel(getDatabaseConnection()));
    $viewData = $controller->handleRequest();
    extract($viewData, EXTR_SKIP);
    require __DIR__ . ($loginRequired ? '/views/admin-login.php' : '/views/admin.php');
} catch (PDOException $exception) {
    error_log('Admin database error: ' . $exception->getMessage());
    http_response_code(503);
    ?>
    <!DOCTYPE html>
    <html lang="vi">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Không thể mở trang quản trị — VAST STUDIO</title>
        <link rel="stylesheet" href="css/styles.css">
    </head>
    <body>
        <main class="content-area">
            <section class="card">
                <h1>Không thể kết nối cơ sở dữ liệu</h1>
                <p>Hãy kiểm tra cấu hình MySQL trong biến môi trường DB_* và chạy <code>php database/setup.php</code> để tạo schema.</p>
            </section>
        </main>
    </body>
    </html>
    <?php
}
