<?php
declare(strict_types=1);

/**
 * Very small file logger (for local/dev admin viewing).
 */
function appLogPath(): string {
    $dir = dirname(__DIR__) . '/logs';
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
    return $dir . '/app.log';
}

function appLog(string $event, array $ctx = []): void {
    $ts = date('c');
    $line = [
        'ts' => $ts,
        'event' => $event,
        'ctx' => $ctx,
    ];
    $json = json_encode($line, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if (!is_string($json)) return;
    @file_put_contents(appLogPath(), $json . PHP_EOL, FILE_APPEND);
}

/**
 * Read last N lines from log (best-effort).
 * @return array<int, string>
 */
function appLogTail(int $lines = 200): array {
    $lines = max(1, min(2000, $lines));
    $path = appLogPath();
    if (!is_file($path)) return [];

    $fp = @fopen($path, 'rb');
    if (!$fp) return [];

    $buffer = '';
    $chunkSize = 4096;
    fseek($fp, 0, SEEK_END);
    $pos = ftell($fp);
    $lineCount = 0;

    while ($pos > 0 && $lineCount <= $lines) {
        $read = min($chunkSize, $pos);
        $pos -= $read;
        fseek($fp, $pos);
        $chunk = fread($fp, $read);
        if (!is_string($chunk)) break;
        $buffer = $chunk . $buffer;
        $lineCount = substr_count($buffer, "\n");
    }
    fclose($fp);

    $arr = preg_split("/\r?\n/", trim($buffer));
    if (!is_array($arr)) return [];
    return array_slice($arr, -$lines);
}

