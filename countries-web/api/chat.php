<?php
declare(strict_types=1);

require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../services/GeminiService.php';
require_once __DIR__ . '/../helpers/log.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    errorResponse('Phương thức không hợp lệ. Chỉ hỗ trợ POST.', 405);
}

$question = '';
$contentType = (string)($_SERVER['CONTENT_TYPE'] ?? '');
if (str_contains(strtolower($contentType), 'application/json')) {
    $raw = file_get_contents('php://input');
    $json = json_decode((string)$raw, true);
    if (is_array($json)) {
        $question = trim((string)($json['question'] ?? ''));
    }
} else {
    $question = trim((string)($_POST['question'] ?? ''));
}

if ($question === '') {
    errorResponse('Vui lòng nhập câu hỏi.', 422);
}

if (mb_strlen($question) > 600) {
    errorResponse('Câu hỏi quá dài (tối đa 600 ký tự).', 422);
}

try {
    $svc = new GeminiService();
    $answer = $svc->askCountriesAssistant($question);
    ok([
        'answer' => $answer,
    ]);
} catch (Throwable $e) {
    appLog('gemini.chat.error', ['message' => $e->getMessage()]);
    errorResponse($e->getMessage(), 502);
}

