<?php

declare(strict_types=1);

/** Tracks the shared OpenRouter daily allowance across all InstaWeb users. */
return static function (PDO $pdo): void {
    $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS portfolio_ai_daily_usage (
    provider VARCHAR(40) NOT NULL,
    usage_date DATE NOT NULL,
    request_count INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (provider, usage_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
};
