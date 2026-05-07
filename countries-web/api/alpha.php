<?php
declare(strict_types=1);

require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../services/CountryService.php';

$code = trim((string)($_GET['code'] ?? ''));
if ($code === '') {
    errorResponse('Missing query param: code', 400);
}

try {
    $svc = new CountryService();
    $country = $svc->getDetailsByCode($code);
    if ($country === null) {
        errorResponse('Country not found', 404, ['code' => $code]);
    }
    ok($country);
} catch (Throwable $e) {
    errorResponse($e->getMessage(), 502);
}

