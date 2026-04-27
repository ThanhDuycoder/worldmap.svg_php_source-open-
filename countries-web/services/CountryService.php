<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../helpers/cache.php';
require_once __DIR__ . '/../config/constants.php';

final class CountryService
{
    /**
     * Public list (no internal normalized fields).
     *
     * @return array<int, array<string, mixed>>
     */
    public function listCountries(): array
    {
        $all = $this->getAllCountries();
        return array_map(fn($c) => $this->stripInternal($c), $all);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getAllCountries(): array
    {
        $cached = cacheReadJson(COUNTRIES_CACHE_FILE, COUNTRIES_CACHE_TTL_SECONDS);
        // Treat empty cache as a miss (an earlier failed fetch may have written []).
        if (is_array($cached) && count($cached) > 0) {
            return $cached;
        }

        $url = restcountriesBaseUrl() . '/all?fields=' . rawurlencode(RESTCOUNTRIES_ALL_FIELDS);
        $data = $this->httpGetJson($url);
        $countries = $this->normalizeCountries($data);
        if (count($countries) === 0) {
            throw new RuntimeException('RestCountries returned no usable country data.');
        }
        cacheWriteJson(COUNTRIES_CACHE_FILE, $countries);
        return $countries;
    }

    /**
     * Fetch full details by ISO 3166-1 alpha-2 code (cca2).
     *
     * @return array<string, mixed>|null
     */
    public function getDetailsByCode(string $code): ?array
    {
        $code = strtoupper(trim($code));
        if (!preg_match('/^[A-Z]{2}$/', $code)) return null;

        $url = restcountriesBaseUrl() . '/alpha/' . rawurlencode($code) . '?fields=' . rawurlencode(RESTCOUNTRIES_ALPHA_FIELDS);
        $data = $this->httpGetJson($url);

        // alpha/{code} usually returns an array with one country
        if (array_is_list($data)) {
            $item = $data[0] ?? null;
            return is_array($item) ? $this->normalizeCountryDetail($item) : null;
        }

        // Some proxies may return a direct object
        return $this->normalizeCountryDetail($data);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findOne(string $query): ?array
    {
        $queryNorm = $this->norm($query);
        if ($queryNorm === '') return null;

        $all = $this->getAllCountries();

        // 1) exact match on common name
        foreach ($all as $c) {
            if (($c['_n_common'] ?? '') === $queryNorm) return $this->stripInternal($c);
        }

        // 2) exact match on official name
        foreach ($all as $c) {
            if (($c['_n_official'] ?? '') === $queryNorm) return $this->stripInternal($c);
        }

        // 3) substring match (pick shortest common name)
        $best = null;
        foreach ($all as $c) {
            $common = (string)($c['name']['common'] ?? '');
            if ($common === '') continue;
            $n = (string)($c['_n_common'] ?? '');
            if ($n !== '' && str_contains($n, $queryNorm)) {
                if ($best === null || strlen($common) < strlen((string)($best['name']['common'] ?? ''))) {
                    $best = $c;
                }
            }
        }
        return $best ? $this->stripInternal($best) : null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function search(string $q, int $limit = 30): array
    {
        $qNorm = $this->norm($q);
        if ($qNorm === '') return [];

        $all = $this->getAllCountries();
        $out = [];

        foreach ($all as $c) {
            $common = (string)($c['name']['common'] ?? '');
            if ($common === '') continue;
            $nCommon = (string)($c['_n_common'] ?? '');
            $nOfficial = (string)($c['_n_official'] ?? '');

            if (($nCommon !== '' && str_contains($nCommon, $qNorm)) || ($nOfficial !== '' && str_contains($nOfficial, $qNorm))) {
                $out[] = $this->stripInternal($c);
                if (count($out) >= $limit) break;
            }
        }

        return $out;
    }

    /**
     * @return array<int, mixed>
     */
    private function httpGetJson(string $url): array
    {
        $ctx = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 12,
                'header' => "Accept: application/json\r\nUser-Agent: countries-web/1.0\r\n",
            ],
        ]);

        $raw = @file_get_contents($url, false, $ctx);
        if ($raw === false) {
            throw new RuntimeException('Cannot reach RestCountries API.');
        }

        // If server responded with a non-2xx status, avoid caching bad payloads.
        $status = null;
        if (isset($http_response_header) && is_array($http_response_header) && isset($http_response_header[0])) {
            if (preg_match('/\s(\d{3})\s/', (string)$http_response_header[0], $m)) {
                $status = (int)$m[1];
            }
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            $code = $status ?? 502;
            throw new RuntimeException("Invalid JSON from RestCountries API (HTTP $code).");
        }

        if ($status !== null && $status >= 400) {
            $msg = (string)($decoded['message'] ?? $decoded['error'] ?? 'Upstream request failed.');
            throw new RuntimeException("RestCountries error (HTTP $status): $msg");
        }

        return $decoded;
    }

    /**
     * @param array<int, mixed> $data
     * @return array<int, array<string, mixed>>
     */
    private function normalizeCountries(array $data): array
    {
        // The /all endpoint must return a JSON array (list). If it returns an object, it's usually an error payload.
        if (!array_is_list($data)) {
            $maybeMsg = (string)($data['message'] ?? $data['error'] ?? '');
            $suffix = $maybeMsg !== '' ? " ($maybeMsg)" : '';
            throw new RuntimeException("Unexpected RestCountries payload shape$suffix.");
        }

        $out = [];

        foreach ($data as $item) {
            if (!is_array($item)) continue;

            $name = is_array($item['name'] ?? null) ? $item['name'] : [];
            $common = (string)($name['common'] ?? '');
            $official = (string)($name['official'] ?? '');
            if ($common === '') continue;

            $cca2 = isset($item['cca2']) ? strtoupper((string)$item['cca2']) : null;
            if (!is_string($cca2) || !preg_match('/^[A-Z]{2}$/', $cca2)) {
                $cca2 = null;
            }

            $capital = $item['capital'] ?? [];
            $capital0 = is_array($capital) && isset($capital[0]) ? (string)$capital[0] : null;

            $region = isset($item['region']) ? (string)$item['region'] : null;
            $subregion = isset($item['subregion']) ? (string)$item['subregion'] : null;

            $out[] = [
                'name' => [
                    'common' => $common,
                    'official' => $official,
                ],
                'cca2' => $cca2,
                'capital' => $capital0,
                'region' => $region !== '' ? $region : null,
                'subregion' => $subregion !== '' ? $subregion : null,
                '_n_common' => $this->norm($common),
                '_n_official' => $this->norm($official),
            ];
        }

        usort($out, fn($a, $b) => strcasecmp((string)($a['name']['common'] ?? ''), (string)($b['name']['common'] ?? '')));
        return $out;
    }

    /**
     * @param array<string, mixed> $item
     * @return array<string, mixed>
     */
    private function normalizeCountryDetail(array $item): array
    {
        $name = is_array($item['name'] ?? null) ? $item['name'] : [];
        $common = (string)($name['common'] ?? '');
        $official = (string)($name['official'] ?? '');

        $cca2 = isset($item['cca2']) ? strtoupper((string)$item['cca2']) : null;
        if (!is_string($cca2) || !preg_match('/^[A-Z]{2}$/', $cca2)) $cca2 = null;

        $capital = $item['capital'] ?? [];
        $capital0 = is_array($capital) && isset($capital[0]) ? (string)$capital[0] : null;

        $latlng = $item['latlng'] ?? [];
        $lat = (is_array($latlng) && isset($latlng[0]) && is_numeric($latlng[0])) ? (float)$latlng[0] : null;
        $lng = (is_array($latlng) && isset($latlng[1]) && is_numeric($latlng[1])) ? (float)$latlng[1] : null;

        $population = isset($item['population']) && is_numeric($item['population']) ? (int)$item['population'] : null;

        $currencies = is_array($item['currencies'] ?? null) ? $item['currencies'] : [];
        $currList = [];
        foreach ($currencies as $code => $cur) {
            if (!is_string($code) || !is_array($cur)) continue;
            $currList[] = [
                'code' => $code,
                'name' => (string)($cur['name'] ?? ''),
                'symbol' => isset($cur['symbol']) ? (string)$cur['symbol'] : null,
            ];
        }

        $languages = is_array($item['languages'] ?? null) ? $item['languages'] : [];
        $langList = [];
        foreach ($languages as $lang) {
            if (!is_string($lang) || trim($lang) === '') continue;
            $langList[] = $lang;
        }
        sort($langList, SORT_NATURAL | SORT_FLAG_CASE);

        $flags = is_array($item['flags'] ?? null) ? $item['flags'] : [];
        $flagsOut = [
            'png' => isset($flags['png']) ? (string)$flags['png'] : null,
            'svg' => isset($flags['svg']) ? (string)$flags['svg'] : null,
            'alt' => isset($flags['alt']) ? (string)$flags['alt'] : null,
        ];

        $maps = is_array($item['maps'] ?? null) ? $item['maps'] : [];
        $mapsOut = [
            'googleMaps' => isset($maps['googleMaps']) ? (string)$maps['googleMaps'] : null,
            'openStreetMaps' => isset($maps['openStreetMaps']) ? (string)$maps['openStreetMaps'] : null,
        ];

        $timezones = $item['timezones'] ?? [];
        $tzList = [];
        if (is_array($timezones)) {
            foreach ($timezones as $tz) {
                if (!is_string($tz) || trim($tz) === '') continue;
                $tzList[] = $tz;
            }
        }

        $region = isset($item['region']) ? (string)$item['region'] : null;
        $subregion = isset($item['subregion']) ? (string)$item['subregion'] : null;

        return [
            'name' => [
                'common' => $common !== '' ? $common : null,
                'official' => $official !== '' ? $official : null,
            ],
            'cca2' => $cca2,
            'capital' => $capital0,
            'region' => $region !== '' ? $region : null,
            'subregion' => $subregion !== '' ? $subregion : null,
            'latlng' => ($lat !== null && $lng !== null) ? [$lat, $lng] : null,
            'population' => $population,
            'currencies' => $currList,
            'languages' => $langList,
            'flags' => $flagsOut,
            'maps' => $mapsOut,
            'timezones' => $tzList,
        ];
    }

    private function norm(string $s): string
    {
        $s = trim(strtolower($s));
        $s = preg_replace('/[\s\p{Pd}]+/u', ' ', $s) ?? $s;
        $s = preg_replace('/[^\p{L}\p{N}\s\.\'&()]/u', '', $s) ?? $s;
        return trim($s);
    }

    /**
     * @param array<string, mixed> $country
     * @return array<string, mixed>
     */
    private function stripInternal(array $country): array
    {
        unset($country['_n_common'], $country['_n_official']);
        return $country;
    }
}

