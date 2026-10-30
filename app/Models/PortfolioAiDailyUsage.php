<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

/** Atomically caps usage of the platform's shared OpenRouter free allowance. */
final class PortfolioAiDailyUsage
{
    public function consumeOpenRouter(int $dailyLimit): bool
    {
        $dailyLimit = max(1, min(1000, $dailyLimit));
        $pdo = Database::connection();
        $statement = $pdo->prepare(<<<'SQL'
INSERT INTO portfolio_ai_daily_usage (provider, usage_date, request_count)
VALUES ('openrouter', UTC_DATE(), 1)
ON DUPLICATE KEY UPDATE
    request_count = IF(request_count < :daily_limit, request_count + 1, request_count)
SQL);
        $statement->execute(['daily_limit' => $dailyLimit]);

        // MySQL reports 1 for insert, 2 for a changed duplicate row, and 0
        // when the daily cap prevented the update.
        return $statement->rowCount() > 0;
    }
}
