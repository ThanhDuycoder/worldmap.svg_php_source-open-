<?php
declare(strict_types=1);

function jsonResponse(array $payload, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Cache-Control: no-store');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function ok(mixed $data, array $meta = []): void {
    jsonResponse([
        'ok' => true,
        'data' => $data,
        'meta' => (object)$meta,
    ]);
}

function errorResponse(string $message, int $statusCode = 400, array $meta = []): void {
    jsonResponse([
        'ok' => false,
        'error' => [
            'message' => $message,
        ],
        'meta' => (object)$meta,
    ], $statusCode);
}

