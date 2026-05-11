<?php
declare(strict_types=1);

require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/response.php';

requireAdmin();

try {
    $pdo = db();
    $total = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    $admins = (int)$pdo->query('SELECT COUNT(*) FROM users WHERE is_admin = 1')->fetchColumn();
    $banned = (int)$pdo->query('SELECT COUNT(*) FROM users WHERE is_banned = 1')->fetchColumn();

    ok([
        'users_total' => $total,
        'users_admin' => $admins,
        'users_banned' => $banned,
    ]);
} catch (Throwable $e) {
    errorResponse($e->getMessage(), 500);
}

