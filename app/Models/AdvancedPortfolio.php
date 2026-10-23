<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * Owner-scoped persistence for advanced portfolio presentation settings.
 */
class AdvancedPortfolio extends Model
{
    public function settings(int $websiteId, int $userId): array
    {
        $statement = $this->query(
            'SELECT setting_key, setting_value, value_type
             FROM portfolio_advanced_settings
             WHERE website_id = :website_id AND user_id = :user_id
             ORDER BY setting_key',
            ['website_id' => $websiteId, 'user_id' => $userId]
        );

        $settings = [];
        foreach ($statement->fetchAll() as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }

        return $settings;
    }

    public function saveSettings(int $websiteId, int $userId, array $settings): void
    {
        $statement = $this->database->prepare(
            'INSERT INTO portfolio_advanced_settings
                (website_id, user_id, setting_key, setting_value, value_type)
             SELECT w.id, w.user_id, :setting_key, :setting_value, :value_type
             FROM websites w
             WHERE w.id = :website_id AND w.user_id = :user_id
             ON DUPLICATE KEY UPDATE
                setting_value = VALUES(setting_value),
                value_type = VALUES(value_type),
                user_id = VALUES(user_id),
                updated_at = CURRENT_TIMESTAMP'
        );

        foreach ($settings as $key => $value) {
            $statement->execute([
                'setting_key' => $key,
                'setting_value' => $value,
                'value_type' => 'text',
                'website_id' => $websiteId,
                'user_id' => $userId,
            ]);
        }
    }

    public function items(int $websiteId, int $userId): array
    {
        $statement = $this->query(
            'SELECT id, section_key, title, payload, sort_order, is_enabled
             FROM portfolio_advanced_items
             WHERE website_id = :website_id AND user_id = :user_id
             ORDER BY section_key, sort_order, id',
            ['website_id' => $websiteId, 'user_id' => $userId]
        );

        return $statement->fetchAll();
    }
}
