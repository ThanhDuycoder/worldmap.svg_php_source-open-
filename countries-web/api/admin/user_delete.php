<?php
declare(strict_types=1);

require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/response.php';

$admin = requireAdmin();
requirePost();

$contentType = (string)($_SERVER['CONTENT_TYPE'] ?? '');
$raw = null;
$json = null;
if (str_contains(strtolower($contentType), 'application/json')) {
    $raw = file_get_contents('php://input');
    $json = json_decode((string)$raw, true);
}

$id = (int)($json['id'] ?? ($_POST['id'] ?? 0));
if ($id <= 0) {
    errorResponse('Thiếu id người dùng.', 422);
}

if ((int)$admin['id'] === $id) {
    errorResponse('Không thể tự xoá chính bạn.', 409);
}

try {
    $pdo = db();
    $stmt = $pdo->prepare('DELETE FROM users WHERE id = :id');
    $stmt->execute(['id' => $id]);
    ok(['id' => $id]);
} catch (Throwable $e) {
    errorResponse($e->getMessage(), 500);
}

