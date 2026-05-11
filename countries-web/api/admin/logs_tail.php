<?php
declare(strict_types=1);

require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/log.php';

requireAdmin();

$lines = 200;
if (isset($_GET['lines']) && is_numeric($_GET['lines'])) {
    $lines = (int)$_GET['lines'];
}

ok([
    'lines' => appLogTail($lines),
]);

