CREATE DATABASE IF NOT EXISTS countries_web CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE countries_web;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(190) NOT NULL UNIQUE,
    username VARCHAR(60) NULL UNIQUE,
    name VARCHAR(120) NOT NULL,
    password_hash VARCHAR(255) NULL,
    provider VARCHAR(30) NOT NULL DEFAULT 'local',
    provider_id VARCHAR(191) NULL,
    avatar_url VARCHAR(255) NULL,
    is_admin TINYINT(1) NOT NULL DEFAULT 0,
    is_banned TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_provider_user (provider, provider_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS settings (
    `key` VARCHAR(80) PRIMARY KEY,
    `value` TEXT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default admin for local/dev
-- Email/password can be changed via `.env` (see helpers/auth.php ensureDefaultAdminAccount()).
INSERT INTO users (email, name, password_hash, provider, is_admin)
VALUES ('admin@countries.local', 'Administrator', NULL, 'local', 1)
ON DUPLICATE KEY UPDATE is_admin = 1, provider = 'local';
