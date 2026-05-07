<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/constants.php';

function cacheEnsureDir(): void {
    if (!is_dir(CACHE_DIR)) {
        mkdir(CACHE_DIR, 0777, true);
    }
}

function cacheReadJson(string $path, int $ttlSeconds): ?array {
    if (!is_file($path)) return null;
    $mtime = @filemtime($path);
    if ($mtime === false) return null;
    if (time() - $mtime > $ttlSeconds) return null;

    $raw = @file_get_contents($path);
    if ($raw === false || trim($raw) === '') return null;

    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : null;
}

function cacheReadJsonAnyAge(string $path): ?array {
    if (!is_file($path)) return null;
    $raw = @file_get_contents($path);
    if ($raw === false || trim($raw) === '') return null;
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : null;
}

function cacheWriteJson(string $path, array $data): void {
    cacheEnsureDir();
    $tmp = $path . '.tmp';
    file_put_contents($tmp, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    @rename($tmp, $path);
}

