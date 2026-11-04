<?php
namespace App\Models;

use App\Core\Model;

class BusinessWebsiteTheme extends Model
{
    public function findForOwner(int $websiteId, int $userId): string
    {
        $statement = $this->query(
            "SELECT t.theme_key FROM business_website_themes t
             INNER JOIN websites w ON w.id = t.website_id
             WHERE t.website_id = :website_id AND t.user_id = :user_id
               AND w.user_id = :website_user_id AND w.template_key = 'business'
             LIMIT 1",
            ['website_id' => $websiteId, 'user_id' => $userId, 'website_user_id' => $userId]
        );
        $value = $statement->fetchColumn();
        return is_string($value) && $value !== '' ? $value : 'evergreen';
    }

    public function saveForOwner(int $websiteId, int $userId, string $themeKey): bool
    {
        $this->query(
            "INSERT INTO business_website_themes (website_id, user_id, theme_key)
             SELECT w.id, w.user_id, :theme_key FROM websites w
             WHERE w.id = :website_id AND w.user_id = :user_id AND w.template_key = 'business'
             ON DUPLICATE KEY UPDATE theme_key = VALUES(theme_key), updated_at = CURRENT_TIMESTAMP",
            ['theme_key' => $themeKey, 'website_id' => $websiteId, 'user_id' => $userId]
        );
        return true;
    }
}
