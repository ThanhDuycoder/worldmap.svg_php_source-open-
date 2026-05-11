<?php
declare(strict_types=1);

require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/settings.php';

requireAdmin();
requirePost();

$contentType = (string)($_SERVER['CONTENT_TYPE'] ?? '');
$json = null;
if (str_contains(strtolower($contentType), 'application/json')) {
    $raw = file_get_contents('php://input');
    $json = json_decode((string)$raw, true);
}

$model = trim((string)($json['gemini_model'] ?? ($_POST['gemini_model'] ?? '')));
$temp = trim((string)($json['gemini_temperature'] ?? ($_POST['gemini_temperature'] ?? '')));
$maxTok = trim((string)($json['gemini_max_output_tokens'] ?? ($_POST['gemini_max_output_tokens'] ?? '')));

if ($model === '') errorResponse('Thiếu gemini_model.', 422);
if ($temp === '' || !is_numeric($temp)) errorResponse('gemini_temperature không hợp lệ.', 422);
if ($maxTok === '' || !ctype_digit($maxTok)) errorResponse('gemini_max_output_tokens không hợp lệ.', 422);

$t = (float)$temp;
if ($t < 0 || $t > 2) errorResponse('gemini_temperature phải trong khoảng 0..2', 422);
$m = (int)$maxTok;
if ($m < 128 || $m > 8192) errorResponse('gemini_max_output_tokens phải trong khoảng 128..8192', 422);

try {
    settingSet('gemini_model', $model);
    settingSet('gemini_temperature', (string)$t);
    settingSet('gemini_max_output_tokens', (string)$m);
    ok(['saved' => true]);
} catch (Throwable $e) {
    errorResponse($e->getMessage(), 500);
}

