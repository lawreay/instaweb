<?php

declare(strict_types=1);

/**
 * Schema for the namespaced advanced portfolio editor data.
 *
 * Run this migration after the base InstaWeb schema. It intentionally does
 * not reuse the upstream Custom-portfolios table names.
 */
return static function (PDO $pdo): void {
    $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS portfolio_advanced_settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    website_id INT NOT NULL,
    user_id INT NOT NULL,
    setting_key VARCHAR(100) NOT NULL,
    setting_value LONGTEXT NULL,
    value_type VARCHAR(20) NOT NULL DEFAULT 'text',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_portfolio_advanced_setting (website_id, setting_key),
    KEY idx_portfolio_advanced_settings_owner (user_id, website_id),
    CONSTRAINT fk_portfolio_advanced_settings_website FOREIGN KEY (website_id) REFERENCES websites(id) ON DELETE CASCADE,
    CONSTRAINT fk_portfolio_advanced_settings_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);

    $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS portfolio_advanced_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    website_id INT NOT NULL,
    user_id INT NOT NULL,
    section_key VARCHAR(60) NOT NULL,
    title VARCHAR(190) NOT NULL DEFAULT '',
    payload JSON NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_enabled TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_portfolio_advanced_items_order (website_id, section_key, sort_order, id),
    KEY idx_portfolio_advanced_items_owner (user_id, website_id, section_key),
    CONSTRAINT fk_portfolio_advanced_items_website FOREIGN KEY (website_id) REFERENCES websites(id) ON DELETE CASCADE,
    CONSTRAINT fk_portfolio_advanced_items_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);

    $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS portfolio_advanced_uploads (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    website_id INT NOT NULL,
    user_id INT NOT NULL,
    storage_key VARCHAR(500) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    mime_type VARCHAR(120) NOT NULL,
    file_size BIGINT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_portfolio_advanced_upload_key (website_id, storage_key),
    KEY idx_portfolio_advanced_uploads_owner (user_id, website_id),
    CONSTRAINT fk_portfolio_advanced_uploads_website FOREIGN KEY (website_id) REFERENCES websites(id) ON DELETE CASCADE,
    CONSTRAINT fk_portfolio_advanced_uploads_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
};
