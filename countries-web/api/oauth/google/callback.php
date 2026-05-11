<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../helpers/auth.php';

$state = (string)($_GET['state'] ?? '');
$code = (string)($_GET['code'] ?? '');
$sessionState = (string)($_SESSION['oauth_state'] ?? '');
$cookieState = (string)($_COOKIE['oauth_state'] ?? '');
$isStateValid = ($sessionState !== '' && hash_equals($sessionState, $state))
    || ($cookieState !== '' && hash_equals($cookieState, $state));

if ($state === '' || $code === '' || !$isStateValid) {
    http_response_code(400);
    echo 'OAuth state invalid.';
    exit;
}
unset($_SESSION['oauth_state']);
setcookie('oauth_state', '', [
    'expires' => time() - 3600,
    'path' => '/',
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Lax',
]);

$clientId = env('GOOGLE_CLIENT_ID', '');
$clientSecret = env('GOOGLE_CLIENT_SECRET', '');
$redirectUri = oauthRedirectUri();

if ($clientId === '' || $clientSecret === '' || $redirectUri === '') {
    http_response_code(500);
    echo 'Missing Google OAuth settings in .env';
    exit;
}

$tokenPayload = http_build_query([
    'code' => $code,
    'client_id' => $clientId,
    'client_secret' => $clientSecret,
    'redirect_uri' => $redirectUri,
    'grant_type' => 'authorization_code',
]);

$tokenCh = curl_init('https://oauth2.googleapis.com/token');
curl_setopt_array($tokenCh, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $tokenPayload,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
]);
$tokenRaw = curl_exec($tokenCh);
$tokenErr = curl_error($tokenCh);
curl_close($tokenCh);

if (!is_string($tokenRaw) || $tokenRaw === '') {
    http_response_code(502);
    echo 'Failed to request OAuth token: ' . htmlspecialchars($tokenErr);
    exit;
}

$token = json_decode($tokenRaw, true);
$accessToken = (string)($token['access_token'] ?? '');
if ($accessToken === '') {
    http_response_code(502);
    echo 'OAuth token response invalid.';
    exit;
}

$userCh = curl_init('https://www.googleapis.com/oauth2/v2/userinfo');
curl_setopt_array($userCh, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $accessToken],
]);
$userRaw = curl_exec($userCh);
curl_close($userCh);

$profile = is_string($userRaw) ? json_decode($userRaw, true) : null;
$providerId = (string)($profile['id'] ?? '');
$email = strtolower(trim((string)($profile['email'] ?? '')));
$name = trim((string)($profile['name'] ?? 'Google User'));
$avatar = trim((string)($profile['picture'] ?? ''));

if ($providerId === '' || $email === '') {
    http_response_code(502);
    echo 'OAuth profile invalid.';
    exit;
}

$pdo = db();
$find = $pdo->prepare('SELECT id, email, name FROM users WHERE provider = :provider AND provider_id = :provider_id LIMIT 1');
$find->execute([
    'provider' => 'google',
    'provider_id' => $providerId,
]);
$user = $find->fetch();

if (!$user) {
    $findByEmail = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
    $findByEmail->execute(['email' => $email]);
    $existing = $findByEmail->fetch();

    if ($existing) {
        $update = $pdo->prepare('UPDATE users SET provider = :provider, provider_id = :provider_id, avatar_url = :avatar_url, name = :name WHERE id = :id');
        $update->execute([
            'provider' => 'google',
            'provider_id' => $providerId,
            'avatar_url' => $avatar !== '' ? $avatar : null,
            'name' => $name,
            'id' => (int)$existing['id'],
        ]);
        $userId = (int)$existing['id'];
    } else {
        $insert = $pdo->prepare('INSERT INTO users (email, name, provider, provider_id, avatar_url) VALUES (:email, :name, :provider, :provider_id, :avatar_url)');
        $insert->execute([
            'email' => $email,
            'name' => $name,
            'provider' => 'google',
            'provider_id' => $providerId,
            'avatar_url' => $avatar !== '' ? $avatar : null,
        ]);
        $userId = (int)$pdo->lastInsertId();
    }

    $reload = $pdo->prepare('SELECT id, email, name FROM users WHERE id = :id LIMIT 1');
    $reload->execute(['id' => $userId]);
    $user = $reload->fetch();
}

if (!$user) {
    http_response_code(500);
    echo 'Could not create OAuth user.';
    exit;
}

// Block banned users
try {
    $checkBan = $pdo->prepare('SELECT is_banned FROM users WHERE id = :id LIMIT 1');
    $checkBan->execute(['id' => (int)$user['id']]);
    $banRow = $checkBan->fetch();
    $banned = is_array($banRow) ? ($banRow['is_banned'] ?? 0) : 0;
    $isBanned = $banned === 1 || $banned === true || $banned === '1' || (is_numeric($banned) && (int)$banned === 1);
    if ($isBanned) {
        http_response_code(403);
        echo 'Account is banned.';
        exit;
    }
} catch (Throwable) {
    // ignore
}

setAuthSession($user);
redirect('../../../index.php');
