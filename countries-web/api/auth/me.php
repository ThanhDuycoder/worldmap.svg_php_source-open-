<?php
declare(strict_types=1);

require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/response.php';

$user = currentUser();
if (!$user) {
    errorResponse('Unauthorized', 401);
}

ok($user);
