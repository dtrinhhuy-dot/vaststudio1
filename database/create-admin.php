<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../includes/database.php';

if ($argc !== 3) {
    fwrite(STDERR, "Usage: php database/create-admin.php \"Admin Name\" admin@example.com\n");
    exit(1);
}

$name = trim($argv[1]);
$email = filter_var(strtolower(trim($argv[2])), FILTER_VALIDATE_EMAIL);
$password = getenv('ADMIN_INITIAL_PASSWORD');

if ($name === '' || $email === false) {
    fwrite(STDERR, "Provide a non-empty name and a valid email address.\n");
    exit(1);
}
if ($password === false || strlen($password) < 12) {
    fwrite(STDERR, "Set ADMIN_INITIAL_PASSWORD to a password with at least 12 characters.\n");
    exit(1);
}

$db = getDatabaseConnection();
$nameColumn = $db->query("SHOW COLUMNS FROM users LIKE 'full_name'")->fetch()
    ? 'full_name'
    : 'name';
$statement = $db->prepare(
    "INSERT INTO users ({$nameColumn}, email, password_hash, role)
     VALUES (:name, :email, :password_hash, 'admin')"
);
$statement->execute([
    'name' => $name,
    'email' => $email,
    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
]);

fwrite(STDOUT, "Administrator account created for {$email}.\n");
