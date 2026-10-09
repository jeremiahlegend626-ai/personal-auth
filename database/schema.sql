CREATE DATABASE IF NOT EXISTS personal_auth CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
USE personal_auth;
CREATE TABLE users (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 full_name VARCHAR(120) NOT NULL,
 username VARCHAR(32) NOT NULL,
 email VARCHAR(254) NOT NULL,
 password_hash VARCHAR(255) NOT NULL,
 role ENUM('USER','ADMIN') NOT NULL DEFAULT 'USER',
 totp_secret_enc TEXT NULL,
 totp_enabled TINYINT(1) NOT NULL DEFAULT 0,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 UNIQUE KEY uq_users_username (username), UNIQUE KEY uq_users_email (email), KEY idx_users_role (role)
) ENGINE=InnoDB;
CREATE TABLE login_attempts (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 subject_hash CHAR(64) NOT NULL,
 attempted_at DATETIME NOT NULL,
 succeeded TINYINT(1) NOT NULL DEFAULT 0,
 KEY idx_attempts_subject_time(subject_hash,attempted_at), KEY idx_attempts_time(attempted_at)
) ENGINE=InnoDB;
CREATE TABLE password_reset_tokens (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 user_id BIGINT UNSIGNED NOT NULL,
 token_hash CHAR(64) NOT NULL,
 expires_at DATETIME NOT NULL,
 used_at DATETIME NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uq_reset_token(token_hash), KEY idx_reset_user(user_id), KEY idx_reset_expiry(expires_at),
 CONSTRAINT fk_reset_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
