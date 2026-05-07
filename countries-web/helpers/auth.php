<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function currentUser(): ?array {
    $id = $_SESSION['user_id'] ?? null;
    if (!is_int($id) && !ctype_digit((string)$id)) {
        return null;
    }

    $stmt = db()->prepare('SELECT id, email, name, provider, provider_id, avatar_url, created_at FROM users WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => (int)$id]);
    $user = $stmt->fetch();
    return is_array($user) ? $user : null;
}

function setAuthSession(array $user): void {
    $_SESSION['user_id'] = (int)$user['id'];
}

function clearAuthSession(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool)$params['secure'], (bool)$params['httponly']);
    }
    session_destroy();
}

function requirePost(): void {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        http_response_code(405);
        header('Allow: POST');
        echo 'Method Not Allowed';
        exit;
    }
}

function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

function oauthRedirectUri(): string {
    return env('GOOGLE_REDIRECT_URI', 'http://localhost/countries-web/api/oauth/google/callback.php') ?? '';
}
