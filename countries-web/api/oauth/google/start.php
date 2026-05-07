<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../helpers/auth.php';

$clientId = env('GOOGLE_CLIENT_ID', '');
$redirectUri = oauthRedirectUri();

if ($clientId === '' || $redirectUri === '') {
    http_response_code(500);
    echo 'Missing GOOGLE_CLIENT_ID or GOOGLE_REDIRECT_URI in .env';
    exit;
}

$state = bin2hex(random_bytes(16));
$_SESSION['oauth_state'] = $state;
setcookie('oauth_state', $state, [
    'expires' => time() + 600,
    'path' => '/',
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Lax',
]);

$params = http_build_query([
    'client_id' => $clientId,
    'redirect_uri' => $redirectUri,
    'response_type' => 'code',
    'scope' => 'openid email profile',
    'state' => $state,
    'access_type' => 'online',
    'prompt' => 'select_account',
]);

redirect('https://accounts.google.com/o/oauth2/v2/auth?' . $params);
