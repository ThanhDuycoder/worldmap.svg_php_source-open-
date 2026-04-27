<?php
declare(strict_types=1);

require_once __DIR__ . '/constants.php';

function env(string $key, ?string $default = null): ?string {
    static $env = null;

    if ($env === null) {
        $env = [];
        $envPath = dirname(__DIR__) . '/.env';
        if (is_file($envPath)) {
            $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#')) continue;
                $pos = strpos($line, '=');
                if ($pos === false) continue;
                $k = trim(substr($line, 0, $pos));
                $v = trim(substr($line, $pos + 1));
                $v = trim($v, "\"'");
                if ($k !== '') $env[$k] = $v;
            }
        }
    }

    $value = $_ENV[$key] ?? getenv($key) ?: ($env[$key] ?? null);
    return $value !== null ? (string)$value : $default;
}

function restcountriesBaseUrl(): string {
    return rtrim(env('RESTCOUNTRIES_BASE_URL', RESTCOUNTRIES_BASE_URL) ?? RESTCOUNTRIES_BASE_URL, '/');
}

