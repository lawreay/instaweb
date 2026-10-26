<?php

declare(strict_types=1);

/**
 * Stores administrator-created portfolio templates. Built-in templates remain
 * code-owned; this table supplies safe, editable HTML/CSS custom layouts.
 */
return static function (PDO $pdo): void {
    $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS portfolio_template_definitions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    template_key VARCHAR(40) NOT NULL,
    display_name VARCHAR(120) NOT NULL,
    description VARCHAR(500) NOT NULL DEFAULT '',
    html_code MEDIUMTEXT NOT NULL,
    css_code MEDIUMTEXT NOT NULL,
    status ENUM('active', 'suspended') NOT NULL DEFAULT 'active',
    created_by INT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_portfolio_template_definitions_key (template_key),
    KEY idx_portfolio_template_definitions_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
};
