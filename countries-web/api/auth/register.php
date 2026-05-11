<?php
declare(strict_types=1);

require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/response.php';

requirePost();

$name = trim((string)($_POST['name'] ?? ''));
$username = strtolower(trim((string)($_POST['username'] ?? '')));
$email = strtolower(trim((string)($_POST['email'] ?? '')));
$password = (string)($_POST['password'] ?? '');

if ($name === '' || $username === '' || $email === '' || $password === '') {
    errorResponse('Vui long nhap day du thong tin (bao gom ten dang nhap).', 422);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    errorResponse('Email khong hop le.', 422);
}
if (!preg_match('/^[a-z0-9_\.\-]{3,60}$/', $username)) {
    errorResponse('Ten dang nhap chi gom chu, so, dau . _ - va tu 3-60 ky tu.', 422);
}
if (strlen($password) < 6) {
    errorResponse('Mat khau toi thieu 6 ky tu.', 422);
}

$stmt = db()->prepare('SELECT id FROM users WHERE email = :email OR username = :username LIMIT 1');
$stmt->execute(['email' => $email, 'username' => $username]);
$existing = $stmt->fetch();
if ($existing) {
    errorResponse('Email hoac ten dang nhap da ton tai.', 409);
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$insert = db()->prepare('INSERT INTO users (email, username, name, password_hash, provider) VALUES (:email, :username, :name, :password_hash, :provider)');
$insert->execute([
    'email' => $email,
    'username' => $username,
    'name' => $name,
    'password_hash' => $hash,
    'provider' => 'local',
]);

$id = (int)db()->lastInsertId();
setAuthSession(['id' => $id]);
ok(['id' => $id, 'email' => $email, 'name' => $name]);
