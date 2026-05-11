<?php
declare(strict_types=1);

require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/response.php';

requireAdmin();

try {
    $pdo = db();
    $stmt = $pdo->query('SELECT id, email, username, name, provider, is_admin, is_banned, created_at FROM users ORDER BY id DESC');
    $rows = $stmt->fetchAll();
    ok([
        'users' => is_array($rows) ? $rows : [],
    ]);
} catch (Throwable $e) {
    errorResponse($e->getMessage(), 500);
}

