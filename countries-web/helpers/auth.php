<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/**
 * Create/ensure a default admin account for local development.
 *
 * Controlled via .env:
 * - DEFAULT_ADMIN_ENABLED=1|0
 * - DEFAULT_ADMIN_EMAIL=admin@countries.local
 * - DEFAULT_ADMIN_PASSWORD=Admin@12345
 * - DEFAULT_ADMIN_NAME=Administrator
 */
function ensureDefaultAdminAccount(): void {
    static $ran = false;
    if ($ran) return;
    $ran = true;

    $enabled = trim((string)env('DEFAULT_ADMIN_ENABLED', '1'));
    if ($enabled === '0' || strcasecmp($enabled, 'false') === 0) return;

    $email = strtolower(trim((string)env('DEFAULT_ADMIN_EMAIL', 'admin@countries.local')));
    $password = (string)env('DEFAULT_ADMIN_PASSWORD', 'Admin@12345');
    $name = trim((string)env('DEFAULT_ADMIN_NAME', 'Administrator'));

    if ($email === '' || $password === '') return;

    try {
        $pdo = db();

        // Ensure required columns exist for backward-compatible upgrades.
        $check = $pdo->prepare(
            'SELECT 1 FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND COLUMN_NAME = :col LIMIT 1'
        );
        $ensureCol = function (string $col, string $ddl) use ($pdo, $check): void {
            $check->execute(['table' => 'users', 'col' => $col]);
            $has = (bool)$check->fetchColumn();
            if (!$has) {
                $pdo->exec($ddl);
            }
        };

        $ensureCol('is_admin', 'ALTER TABLE users ADD COLUMN is_admin TINYINT(1) NOT NULL DEFAULT 0');
        $ensureCol('is_banned', 'ALTER TABLE users ADD COLUMN is_banned TINYINT(1) NOT NULL DEFAULT 0');
        // Add column first, then unique index (some MySQL variants dislike UNIQUE in ADD COLUMN clause).
        $ensureCol('username', 'ALTER TABLE users ADD COLUMN username VARCHAR(60) NULL');
        try {
            $pdo->exec('CREATE UNIQUE INDEX uniq_users_username ON users (username)');
        } catch (Throwable) {
            // ignore if already exists or cannot be created
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        if (!is_string($hash) || $hash === '') return;

        $defaultUsername = strtolower(trim((string)env('DEFAULT_ADMIN_USERNAME', 'admin')));

        $stmt = $pdo->prepare(
            'INSERT INTO users (email, username, name, password_hash, provider, is_admin, is_banned)
             VALUES (:email, :username, :name, :password_hash, :provider, 1, 0)
             ON DUPLICATE KEY UPDATE
               username = COALESCE(NULLIF(username, \'\'), VALUES(username)),
               name = VALUES(name),
               password_hash = VALUES(password_hash),
               provider = VALUES(provider),
               provider_id = NULL,
               is_admin = 1,
               is_banned = 0'
        );
        $stmt->execute([
            'email' => $email,
            'username' => $defaultUsername !== '' ? $defaultUsername : null,
            'name' => $name !== '' ? $name : 'Administrator',
            'password_hash' => $hash,
            'provider' => 'local',
        ]);
    } catch (Throwable) {
        // Ignore: auth should still work even if seeding fails.
    }
}

function currentUser(): ?array {
    ensureDefaultAdminAccount();

    $id = $_SESSION['user_id'] ?? null;
    if (!is_int($id) && !ctype_digit((string)$id)) {
        return null;
    }

    try {
        $stmt = db()->prepare('SELECT id, email, username, name, provider, provider_id, avatar_url, is_admin, is_banned, created_at FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => (int)$id]);
        $user = $stmt->fetch();
    } catch (Throwable) {
        // Older DB schema fallback (before username/is_banned existed).
        $stmt = db()->prepare('SELECT id, email, name, provider, provider_id, avatar_url, is_admin, created_at FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => (int)$id]);
        $user = $stmt->fetch();
    }
    if (!is_array($user)) return null;
    $banned = $user['is_banned'] ?? 0;
    $isBanned = $banned === 1 || $banned === true || $banned === '1' || (is_numeric($banned) && (int)$banned === 1);
    if ($isBanned) {
        clearAuthSession();
        return null;
    }
    return $user;
}

function isAdmin(?array $user): bool {
    if (!$user) return false;
    $v = $user['is_admin'] ?? 0;
    return $v === 1 || $v === true || $v === '1' || (is_numeric($v) && (int)$v === 1);
}

function requireAdmin(): array {
    $user = currentUser();
    if (!isAdmin($user)) {
        http_response_code(403);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Forbidden';
        exit;
    }
    return $user;
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
