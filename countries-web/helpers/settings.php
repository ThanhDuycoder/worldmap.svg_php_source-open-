<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

/**
 * Read/write simple app settings stored in DB.
 */
function settingGet(string $key, ?string $default = null): ?string {
    $key = trim($key);
    if ($key === '') return $default;

    try {
        $stmt = db()->prepare('SELECT value FROM settings WHERE `key` = :k LIMIT 1');
        $stmt->execute(['k' => $key]);
        $row = $stmt->fetch();
        if (is_array($row) && array_key_exists('value', $row)) {
            $v = $row['value'];
            return $v === null ? null : (string)$v;
        }
    } catch (Throwable) {
        // ignore
    }
    return $default;
}

function settingSet(string $key, ?string $value): void {
    $key = trim($key);
    if ($key === '') {
        throw new InvalidArgumentException('Setting key is empty.');
    }
    $stmt = db()->prepare('INSERT INTO settings (`key`, `value`) VALUES (:k, :v) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)');
    $stmt->execute(['k' => $key, 'v' => $value]);
}

