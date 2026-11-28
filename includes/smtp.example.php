<?php
/**
 * Mau cau hinh SMTP cho VAST STUDIO.
 * Copy file nay thanh includes/smtp.local.php va dien thong tin that,
 * hoac thiet lap cac bien moi truong SMTP_* tuong ung.
 * File includes/smtp.local.php duoc dua vao .gitignore de khong lo mat khau.
 */
return [
    'host' => getenv('SMTP_HOST') ?: '',          // Vi du: smtp.gmail.com
    'port' => (int)(getenv('SMTP_PORT') ?: 587),  // 587 (STARTTLS) hoac 465 (SSL/SMTPS)
    'encryption' => getenv('SMTP_ENCRYPTION') ?: 'tls', // 'tls', 'ssl', hoac ''
    'username' => getenv('SMTP_USERNAME') ?: '',  // Email SMTP (vi du: shop@gmail.com)
    'password' => getenv('SMTP_PASSWORD') ?: '',  // App Password / Mat khau SMTP
    'from_email' => getenv('SMTP_FROM_EMAIL') ?: '', // Email nguoi gui (mac dinh theo username)
    'from_name' => getenv('SMTP_FROM_NAME') ?: 'VAST STUDIO',
    'admin_email' => getenv('ADMIN_NOTIFY_EMAIL') ?: '', // Email Admin nhan thong bao don hang moi / huy / doi tra
];
