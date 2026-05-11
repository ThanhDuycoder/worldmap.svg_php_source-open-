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
$isAdmin = $json['is_admin'] ?? ($_POST['is_admin'] ?? null);
$isBanned = $json['is_banned'] ?? ($_POST['is_banned'] ?? null);

if ($id <= 0) {
    errorResponse('Thiếu id người dùng.', 422);
}
if ($isAdmin === null && $isBanned === null) {
    errorResponse('Thiếu dữ liệu cập nhật.', 422);
}

$newAdmin = $isAdmin === null ? null : (int)(($isAdmin === true || $isAdmin === 1 || $isAdmin === '1') ? 1 : 0);
$newBanned = $isBanned === null ? null : (int)(($isBanned === true || $isBanned === 1 || $isBanned === '1') ? 1 : 0);

// Prevent removing admin from self to avoid lock-out surprises
if ((int)$admin['id'] === $id && $newAdmin === 0) {
    errorResponse('Không thể tự gỡ quyền admin của chính bạn.', 409);
}
if ((int)$admin['id'] === $id && $newBanned === 1) {
    errorResponse('Không thể tự chặn chính bạn.', 409);
}

try {
    $pdo = db();
    $fields = [];
    $params = ['id' => $id];
    if ($newAdmin !== null) {
        $fields[] = 'is_admin = :is_admin';
        $params['is_admin'] = $newAdmin;
    }
    if ($newBanned !== null) {
        $fields[] = 'is_banned = :is_banned';
        $params['is_banned'] = $newBanned;
    }
    if (!$fields) {
        errorResponse('Không có thay đổi.', 422);
    }
    $sql = 'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = :id';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    ok(['id' => $id, 'is_admin' => $newAdmin, 'is_banned' => $newBanned]);
} catch (Throwable $e) {
    errorResponse($e->getMessage(), 500);
}

