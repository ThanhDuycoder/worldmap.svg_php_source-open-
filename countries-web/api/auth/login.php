<?php
declare(strict_types=1);

require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/response.php';

requirePost();

$email = strtolower(trim((string)($_POST['email'] ?? '')));
$password = (string)($_POST['password'] ?? '');

if ($email === '' || $password === '') {
    errorResponse('Vui long nhap email va mat khau.', 422);
}

$stmt = db()->prepare('SELECT id, email, name, password_hash FROM users WHERE email = :email LIMIT 1');
$stmt->execute(['email' => $email]);
$user = $stmt->fetch();

if (!$user || empty($user['password_hash']) || !password_verify($password, (string)$user['password_hash'])) {
    errorResponse('Sai thong tin dang nhap.', 401);
}

setAuthSession($user);
ok([
    'id' => (int)$user['id'],
    'email' => (string)$user['email'],
    'name' => (string)$user['name'],
]);
