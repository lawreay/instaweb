<?php
namespace App\Models;
use App\Core\Model;
class BusinessWebsiteContent extends Model
{
    public function findForOwner(int $websiteId, int $userId): ?array
    {
        $statement = $this->query("SELECT c.content_json FROM business_website_content c INNER JOIN websites w ON w.id = c.website_id WHERE c.website_id = :website_id AND c.user_id = :user_id AND w.user_id = :website_user_id AND w.template_key = 'business' LIMIT 1", ['website_id' => $websiteId, 'user_id' => $userId, 'website_user_id' => $userId]);
        $row = $statement->fetch();
        if (!$row) return null;
        $content = json_decode((string) $row['content_json'], true);
        return is_array($content) ? $content : null;
    }
    public function saveForOwner(int $websiteId, int $userId, array $content): bool
    {
        $this->query("INSERT INTO business_website_content (website_id, user_id, content_json) SELECT w.id, w.user_id, :content_json FROM websites w WHERE w.id = :website_id AND w.user_id = :user_id AND w.template_key = 'business' ON DUPLICATE KEY UPDATE content_json = VALUES(content_json), updated_at = CURRENT_TIMESTAMP", ['content_json' => json_encode($content, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), 'website_id' => $websiteId, 'user_id' => $userId]);
        return true;
    }
}
