<?php
declare(strict_types=1);

require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../services/CountryService.php';

$name = trim((string)($_GET['name'] ?? ''));
if ($name === '') {
    errorResponse('Missing query param: name', 400);
}

try {
    $svc = new CountryService();
    $country = $svc->findOne($name);
    if ($country === null) {
        errorResponse('Country not found', 404, ['name' => $name]);
    }
    ok($country);
} catch (Throwable $e) {
    errorResponse($e->getMessage(), 502);
}

