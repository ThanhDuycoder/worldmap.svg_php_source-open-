<?php
declare(strict_types=1);

require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/response.php';

requirePost();

$name = trim((string)($_POST['name'] ?? ''));
$email = strtolower(trim((string)($_POST['email'] ?? '')));
$password = (string)($_POST['password'] ?? '');

if ($name === '' || $email === '' || $password === '') {
    errorResponse('Vui long nhap day du thong tin.', 422);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    errorResponse('Email khong hop le.', 422);
}
if (strlen($password) < 6) {
    errorResponse('Mat khau toi thieu 6 ky tu.', 422);
}

$stmt = db()->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
$stmt->execute(['email' => $email]);
if ($stmt->fetch()) {
    errorResponse('Email da ton tai.', 409);
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$insert = db()->prepare('INSERT INTO users (email, name, password_hash, provider) VALUES (:email, :name, :password_hash, :provider)');
$insert->execute([
    'email' => $email,
    'name' => $name,
    'password_hash' => $hash,
    'provider' => 'local',
]);

$id = (int)db()->lastInsertId();
setAuthSession(['id' => $id]);
ok(['id' => $id, 'email' => $email, 'name' => $name]);
