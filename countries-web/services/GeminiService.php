<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/prompts.php';

final class GeminiService
{
    public function askCountriesAssistant(string $question): string
    {
        $question = trim($question);
        if ($question === '') {
            throw new InvalidArgumentException('Câu hỏi không được để trống.');
        }

        $apiKey = geminiApiKey();
        if ($apiKey === '') {
            throw new RuntimeException('Thiếu GEMINI_API_KEY trong file .env.');
        }

        $model = geminiModel();
        if ($model === '') {
            $model = GEMINI_MODEL;
        }

        $url = geminiBaseUrl() . '/models/' . rawurlencode($model) . ':generateContent?key=' . rawurlencode($apiKey);

        $instruction = geminiCountriesSystemPrompt();

        $payload = [
            'system_instruction' => [
                'parts' => [
                    ['text' => $instruction],
                ],
            ],
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => $question],
                    ],
                ],
            ],
            'generationConfig' => [
                'temperature' => 0.4,
                'maxOutputTokens' => 500,
            ],
        ];

        return $this->httpPostGemini($url, $payload);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function httpPostGemini(string $url, array $payload): string
    {
        $rawBody = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!is_string($rawBody)) {
            throw new RuntimeException('Không thể tạo payload gửi đến Gemini.');
        }

        $status = null;
        $raw = null;

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_CONNECTTIMEOUT => 8,
                CURLOPT_TIMEOUT => 25,
                CURLOPT_HTTPHEADER => [
                    'Accept: application/json',
                    'Content-Type: application/json',
                    'User-Agent: countries-web/1.0',
                ],
                CURLOPT_POSTFIELDS => $rawBody,
            ]);
            $response = curl_exec($ch);
            if (is_string($response)) {
                $raw = $response;
                $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $status = is_int($statusCode) && $statusCode > 0 ? $statusCode : null;
            }
            curl_close($ch);
        }

        if (!is_string($raw) || $raw === '') {
            $ctx = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'timeout' => 25,
                    'header' => "Accept: application/json\r\nContent-Type: application/json\r\nUser-Agent: countries-web/1.0\r\n",
                    'content' => $rawBody,
                ],
            ]);
            $raw = @file_get_contents($url, false, $ctx);
            if ($raw === false) {
                throw new RuntimeException('Không thể kết nối Gemini API.');
            }

            if (isset($http_response_header) && is_array($http_response_header) && isset($http_response_header[0])) {
                if (preg_match('/\s(\d{3})\s/', (string)$http_response_header[0], $m)) {
                    $status = (int)$m[1];
                }
            }
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('Gemini trả về dữ liệu không hợp lệ.');
        }

        if ($status !== null && $status >= 400) {
            $msg = (string)($decoded['error']['message'] ?? 'Gemini API bị lỗi.');
            throw new RuntimeException($msg);
        }

        $text = (string)($decoded['candidates'][0]['content']['parts'][0]['text'] ?? '');
        $text = trim($text);
        if ($text === '') {
            throw new RuntimeException('Gemini không trả về nội dung.');
        }
        return $text;
    }
}

