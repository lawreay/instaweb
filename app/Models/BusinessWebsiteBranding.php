<?php
namespace App\Models;

use App\Core\Model;

class BusinessWebsiteBranding extends Model
{
    public function findForOwner(int $websiteId, int $userId): array
    {
        $statement = $this->query(
            "SELECT b.logo_path, b.favicon_path
             FROM business_website_branding b
             INNER JOIN websites w ON w.id = b.website_id
             WHERE b.website_id = :website_id AND b.user_id = :user_id
               AND w.user_id = :website_user_id AND w.template_key = 'business'
             LIMIT 1",
            ['website_id' => $websiteId, 'user_id' => $userId, 'website_user_id' => $userId]
        );
        $row = $statement->fetch();
        return is_array($row) ? $row : ['logo_path' => null, 'favicon_path' => null];
    }

    public function saveForOwner(int $websiteId, int $userId, array $branding): bool
    {
        $this->query(
            "INSERT INTO business_website_branding (website_id, user_id, logo_path, favicon_path)
             SELECT w.id, w.user_id, :logo_path, :favicon_path FROM websites w
             WHERE w.id = :website_id AND w.user_id = :user_id AND w.template_key = 'business'
             ON DUPLICATE KEY UPDATE logo_path = VALUES(logo_path),
                 favicon_path = VALUES(favicon_path), updated_at = CURRENT_TIMESTAMP",
            [
                'logo_path' => $branding['logo_path'] ?: null,
                'favicon_path' => $branding['favicon_path'] ?: null,
                'website_id' => $websiteId,
                'user_id' => $userId,
            ]
        );
        return true;
    }
}
