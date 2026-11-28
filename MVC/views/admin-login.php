<?php
$e = static function ($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};
$theme = $_SESSION['admin_theme'] ?? 'dark';
?>
<!DOCTYPE html>
<html lang="vi" data-theme="<?= $e($theme) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Đăng nhập quản trị — VAST STUDIO</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' rx='20' fill='%23000'/><text x='50' y='68' font-size='60' font-family='sans-serif' font-weight='900' fill='%23fff' text-anchor='middle'>V</text></svg>">
    <style>
        body{min-height:100vh;display:grid;place-items:center;padding:24px;background:var(--bg-app);color:var(--text-primary)}
        .login-shell{width:min(440px,100%);display:grid;gap:16px}
        .login-card{padding:32px;background:var(--bg-surface);border:1px solid var(--border-color);border-radius:var(--radius-lg);box-shadow:var(--shadow-lg)}
        .login-brand{display:flex;align-items:center;gap:12px;margin-bottom:20px}
        .login-brand .brand-symbol{box-shadow:var(--shadow-sm);border:1px solid var(--border-color)}
        .login-brand-text h1{font-family:var(--font-heading);font-size:1.25rem;font-weight:700;margin:0;letter-spacing:.06em}
        .login-brand-text p{font-size:.76rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.08em;font-weight:600}
        .login-subtitle{font-size:.88rem;color:var(--text-secondary);margin-bottom:22px}
        .login-error{margin-bottom:18px;padding:12px 14px;border-radius:var(--radius-md);background:var(--status-danger-bg);color:var(--status-danger-text);border:1px solid color-mix(in srgb,var(--status-danger-text) 28%,transparent);font-size:.85rem;font-weight:500}
        .login-form{display:grid;gap:16px}
        .login-submit{width:100%;min-height:44px;margin-top:4px}
        .login-back{text-align:center}
        .login-back a{font-size:.82rem;color:var(--text-muted);text-decoration:none}
        .login-back a:hover{color:var(--text-primary)}
    </style>
</head>
<body>
    <main class="login-shell">
        <section class="login-card">
            <div class="login-brand">
                <div class="brand-symbol">V</div>
                <div class="login-brand-text">
                    <h1>VAST STUDIO</h1>
                    <p>Admin Portal</p>
                </div>
            </div>
            <p class="login-subtitle">Đăng nhập bằng tài khoản có quyền quản trị để tiếp tục.</p>
            <?php if ($error !== ''): ?>
                <div class="login-error" role="alert"><?= $e($error) ?></div>
            <?php endif; ?>
            <form method="post" class="login-form">
                <input type="hidden" name="csrf" value="<?= $e($csrf) ?>">
                <input type="hidden" name="action" value="admin_login">
                <div class="form-group" style="margin-bottom:0">
                    <label class="form-label" for="admin-login-email">Email quản trị</label>
                    <input class="form-control" id="admin-login-email" type="email" name="email" autocomplete="username" placeholder="admin@vaststudio.vn" required>
                </div>
                <div class="form-group" style="margin-bottom:0">
                    <label class="form-label" for="admin-login-password">Mật khẩu</label>
                    <input class="form-control" id="admin-login-password" type="password" name="password" autocomplete="current-password" placeholder="Nhập mật khẩu..." required>
                </div>
                <button type="submit" class="btn-primary login-submit">Đăng nhập</button>
            </form>
        </section>
        <div class="login-back">
            <a href="index.php">← Quay lại cửa hàng VAST STUDIO</a>
        </div>
    </main>
</body>
</html>
