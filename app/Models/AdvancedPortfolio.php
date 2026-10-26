<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * Owner-scoped persistence for advanced portfolio presentation and content.
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

    public function visibleItems(int $websiteId, int $userId): array
    {
        $statement = $this->query(
            'SELECT id, section_key, title, payload, sort_order, is_enabled
             FROM portfolio_advanced_items
             WHERE website_id = :website_id
               AND user_id = :user_id
               AND is_enabled = 1
             ORDER BY section_key, sort_order, id',
            ['website_id' => $websiteId, 'user_id' => $userId]
        );

        return $statement->fetchAll();
    }

    public function saveItem(int $websiteId, int $userId, string $sectionKey, string $title, array $payload, int $sortOrder, ?int $itemId = null): void
    {
        $json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            throw new \RuntimeException('Could not encode portfolio item content.');
        }

        if ($itemId !== null && $itemId > 0) {
            $statement = $this->database->prepare(
                'UPDATE portfolio_advanced_items
                 SET section_key = :section_key, title = :title, payload = :payload, sort_order = :sort_order
                 WHERE id = :id AND website_id = :website_id AND user_id = :user_id'
            );
            $statement->execute([
                'section_key' => $sectionKey,
                'title' => $title,
                'payload' => $json,
                'sort_order' => $sortOrder,
                'id' => $itemId,
                'website_id' => $websiteId,
                'user_id' => $userId,
            ]);
            return;
        }

        $statement = $this->database->prepare(
            'INSERT INTO portfolio_advanced_items
                (website_id, user_id, section_key, title, payload, sort_order, is_enabled)
             SELECT w.id, w.user_id, :section_key, :title, :payload, :sort_order, 1
             FROM websites w
             WHERE w.id = :website_id AND w.user_id = :user_id'
        );
        $statement->execute([
            'section_key' => $sectionKey,
            'title' => $title,
            'payload' => $json,
            'sort_order' => $sortOrder,
            'website_id' => $websiteId,
            'user_id' => $userId,
        ]);
    }

    public function setItemEnabled(int $websiteId, int $userId, int $itemId, bool $enabled): void
    {
        $statement = $this->database->prepare(
            'UPDATE portfolio_advanced_items SET is_enabled = :enabled
             WHERE id = :id AND website_id = :website_id AND user_id = :user_id'
        );
        $statement->execute([
            'enabled' => $enabled ? 1 : 0,
            'id' => $itemId,
            'website_id' => $websiteId,
            'user_id' => $userId,
        ]);
    }

    public function deleteItem(int $websiteId, int $userId, int $itemId): void
    {
        $statement = $this->database->prepare(
            'DELETE FROM portfolio_advanced_items
             WHERE id = :id AND website_id = :website_id AND user_id = :user_id'
        );
        $statement->execute([
            'id' => $itemId,
            'website_id' => $websiteId,
            'user_id' => $userId,
        ]);
    }

    /** Return Noir section-image overrides belonging to one portfolio owner. */
    public function noirSectionImages(int $websiteId, int $userId): array
    {
        $statement = $this->query(
            "SELECT storage_key, original_name, mime_type, file_size
             FROM portfolio_advanced_uploads
             WHERE website_id = :website_id AND user_id = :user_id
               AND storage_key LIKE :prefix",
            ['website_id' => $websiteId, 'user_id' => $userId, 'prefix' => 'uploads/portfolios/' . $websiteId . '/noir/section-%']
        );

        return $statement->fetchAll();
    }

    public function noirSectionImage(int $websiteId, int $userId, string $sectionKey): ?array
    {
        $statement = $this->query(
            'SELECT storage_key, original_name, mime_type, file_size
             FROM portfolio_advanced_uploads
             WHERE website_id = :website_id AND user_id = :user_id AND storage_key LIKE :prefix
             LIMIT 1',
            ['website_id' => $websiteId, 'user_id' => $userId, 'prefix' => 'uploads/portfolios/' . $websiteId . '/noir/section-' . $sectionKey . '-%']
        );
        $upload = $statement->fetch();

        return $upload ?: null;
    }

    public function saveNoirSectionImage(int $websiteId, int $userId, string $sectionKey, string $path, string $originalName, string $mimeType, int $fileSize): void
    {
        $statement = $this->database->prepare(
            'INSERT INTO portfolio_advanced_uploads
                (website_id, user_id, storage_key, original_name, mime_type, file_size)
             SELECT w.id, w.user_id, :storage_key, :original_name, :mime_type, :file_size
             FROM websites w WHERE w.id = :website_id AND w.user_id = :user_id
             ON DUPLICATE KEY UPDATE
                user_id = VALUES(user_id), original_name = VALUES(original_name),
                mime_type = VALUES(mime_type), file_size = VALUES(file_size)'
        );
        $statement->execute([
            'storage_key' => $path,
            'original_name' => $originalName,
            'mime_type' => $mimeType,
            'file_size' => $fileSize,
            'website_id' => $websiteId,
            'user_id' => $userId,
        ]);
    }

    public function deleteNoirSectionImage(int $websiteId, int $userId, string $sectionKey): ?array
    {
        $upload = $this->noirSectionImage($websiteId, $userId, $sectionKey);
        if ($upload === null) {
            return null;
        }
        $statement = $this->database->prepare(
            'DELETE FROM portfolio_advanced_uploads
             WHERE website_id = :website_id AND user_id = :user_id AND storage_key = :storage_key'
        );
        $statement->execute(['website_id' => $websiteId, 'user_id' => $userId, 'storage_key' => $upload['storage_key']]);

        return $statement->rowCount() === 1 ? $upload : null;
    }

    public function deleteUploadPath(int $websiteId, int $userId, string $path): void
    {
        $statement = $this->database->prepare(
            'DELETE FROM portfolio_advanced_uploads
             WHERE website_id = :website_id AND user_id = :user_id AND storage_key = :storage_key'
        );
        $statement->execute(['website_id' => $websiteId, 'user_id' => $userId, 'storage_key' => $path]);
    }
}
