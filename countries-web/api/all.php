<?php
declare(strict_types=1);

require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../services/CountryService.php';

try {
    $svc = new CountryService();
    ok($svc->listCountries(), ['source' => 'cache_or_api']);
} catch (Throwable $e) {
    errorResponse($e->getMessage(), 502);
}

