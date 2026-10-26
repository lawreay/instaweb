<?php

declare(strict_types=1);

/** Retains only short-lived, hashed-IP counters used to protect portfolio mail. */
return static function (PDO $pdo): void {
    $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS portfolio_contact_rate_limits (
    portfolio_id INT NOT NULL,
    ip_hash CHAR(64) NOT NULL,
    window_started_at INT UNSIGNED NOT NULL,
    attempts TINYINT UNSIGNED NOT NULL DEFAULT 1,
    PRIMARY KEY (portfolio_id, ip_hash),
    KEY idx_portfolio_contact_rate_limits_window (window_started_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
};
