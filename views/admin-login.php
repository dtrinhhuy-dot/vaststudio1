<?php
$e = static function ($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Đăng nhập quản trị — VAST STUDIO</title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        body{min-height:100vh;display:grid;place-items:center;background:var(--bg-app);color:var(--text-primary)}
        .login-card{width:min(420px,calc(100% - 32px));padding:28px;background:var(--bg-surface);border:1px solid var(--border-color);border-radius:14px}
        .login-card h1{margin-top:0}.login-card form{display:grid;gap:14px}
        .login-card label{display:grid;gap:6px}.login-card input{padding:10px}
        .login-error{color:var(--status-danger-text)}
    </style>
</head>
<body>
    <main class="login-card">
        <h1>VAST STUDIO</h1>
        <p>Đăng nhập bằng tài khoản có quyền quản trị.</p>
        <?php if ($error !== ''): ?><p class="login-error" role="alert"><?= $e($error) ?></p><?php endif; ?>
        <form method="post">
            <input type="hidden" name="csrf" value="<?= $e($csrf) ?>">
            <input type="hidden" name="action" value="admin_login">
            <label>Email<input type="email" name="email" autocomplete="username" required></label>
            <label>Mật khẩu<input type="password" name="password" autocomplete="current-password" required></label>
            <button type="submit" class="btn-primary">Đăng nhập</button>
        </form>
    </main>
</body>
</html>
