<?php
declare(strict_types=1);

require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../services/CountryService.php';

$q = trim((string)($_GET['q'] ?? ''));
$limit = (int)($_GET['limit'] ?? 30);
$limit = max(1, min(100, $limit));

if ($q === '') {
    ok([]);
}

try {
    $svc = new CountryService();
    ok($svc->search($q, $limit), ['q' => $q, 'limit' => $limit]);
} catch (Throwable $e) {
    errorResponse($e->getMessage(), 502);
}

