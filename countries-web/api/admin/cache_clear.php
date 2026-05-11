<?php
declare(strict_types=1);

require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../config/constants.php';

requireAdmin();
requirePost();

$deleted = [];
foreach ([COUNTRIES_CACHE_FILE] as $path) {
    if (is_string($path) && $path !== '' && is_file($path)) {
        if (@unlink($path)) {
            $deleted[] = basename($path);
        }
    }
}

ok(['deleted' => $deleted]);

