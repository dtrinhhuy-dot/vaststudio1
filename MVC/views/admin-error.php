<?php
$theme = $_SESSION['admin_theme'] ?? 'dark';
?>
<!DOCTYPE html>
<html lang="vi" data-theme="<?= htmlspecialchars((string)$theme, ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Không thể mở trang quản trị — VAST STUDIO</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' rx='20' fill='%23000'/><text x='50' y='68' font-size='60' font-family='sans-serif' font-weight='900' fill='%23fff' text-anchor='middle'>V</text></svg>">
    <style>
        body{min-height:100vh;display:grid;place-items:center;padding:24px;background:var(--bg-app);color:var(--text-primary)}
        .error-card{width:min(540px,100%);padding:32px;background:var(--bg-surface);border:1px solid var(--border-color);border-radius:var(--radius-lg);box-shadow:var(--shadow-lg);display:grid;gap:16px}
        .error-badge{display:inline-flex;align-items:center;gap:8px;width:fit-content;padding:5px 12px;border-radius:var(--radius-full);background:var(--status-danger-bg);color:var(--status-danger-text);font-size:.76rem;font-weight:700}
        .error-card h1{font-family:var(--font-heading);font-size:1.35rem;margin:0}
        .error-card p{font-size:.9rem;color:var(--text-secondary);line-height:1.6}
        .error-card code{padding:2px 7px;border-radius:6px;background:var(--bg-subtle);border:1px solid var(--border-color);color:var(--text-primary);font-size:.84em}
        .error-actions{display:flex;align-items:center;gap:10px;flex-wrap:wrap;padding-top:4px}
    </style>
</head>
<body>
    <main class="error-card" role="alert">
        <span class="error-badge">Lỗi kết nối dữ liệu</span>
        <h1>Không thể kết nối cơ sở dữ liệu</h1>
        <p>Hệ thống quản trị chưa kết nối được tới MySQL. Hãy kiểm tra dịch vụ MySQL và cấu hình biến môi trường <code>DB_*</code>, sau đó chạy <code>php database/setup.php</code> để khởi tạo schema.</p>
        <div class="error-actions">
            <a class="btn-primary" href="admin.php">Thử kết nối lại</a>
            <a class="btn-secondary" href="index.php">Về trang cửa hàng</a>
        </div>
    </main>
</body>
</html>
