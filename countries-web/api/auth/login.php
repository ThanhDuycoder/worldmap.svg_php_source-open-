<?php
declare(strict_types=1);

require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/response.php';

requirePost();
// Ensure default admin (and schema upgrades) exist before attempting login.
ensureDefaultAdminAccount();

$login = strtolower(trim((string)($_POST['login'] ?? ($_POST['email'] ?? ''))));
$password = (string)($_POST['password'] ?? '');

if ($login === '' || $password === '') {
    errorResponse('Vui long nhap ten dang nhap/email va mat khau.', 422);
}

try {
    $stmt = db()->prepare('SELECT id, email, username, name, password_hash, is_banned FROM users WHERE email = :login OR username = :login LIMIT 1');
    $stmt->execute(['login' => $login]);
    $user = $stmt->fetch();
} catch (Throwable) {
    // Backward-compatible fallback for older schema (no username/is_banned yet).
    $stmt = db()->prepare('SELECT id, email, name, password_hash FROM users WHERE email = :login LIMIT 1');
    $stmt->execute(['login' => $login]);
    $user = $stmt->fetch();
    if (is_array($user)) {
        $user['is_banned'] = 0;
        $user['username'] = null;
    }
}

if (!$user || empty($user['password_hash']) || !password_verify($password, (string)$user['password_hash'])) {
    errorResponse('Sai thong tin dang nhap.', 401);
}

$banned = $user['is_banned'] ?? 0;
if ($banned === 1 || $banned === true || $banned === '1' || (is_numeric($banned) && (int)$banned === 1)) {
    errorResponse('Tài khoản đã bị chặn.', 403);
}

setAuthSession($user);
ok([
    'id' => (int)$user['id'],
    'email' => (string)$user['email'],
    'name' => (string)$user['name'],
]);
