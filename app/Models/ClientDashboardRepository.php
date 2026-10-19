<?php

namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * Reads client dashboard data for the authenticated user.
 */
class ClientDashboardRepository
{
    private PDO $database;

    public function __construct(?PDO $database = null)
    {
        $this->database = $database ?? Database::connection();
    }

    /**
     * Return the user profile used by the dashboard header.
     */
    public function user(int $userId): ?array
    {
        $statement = $this->database->prepare(
            'SELECT id, fullname, username, email, phone, country, avatar, bio, role, account_status, email_verified, created_at
             FROM users
             WHERE id = :id
             LIMIT 1'
        );
        $statement->execute(['id' => $userId]);
        $user = $statement->fetch();

        return $user ?: null;
    }

    /**
     * Return dashboard statistics for the user's hosting account.
     */
    public function stats(int $userId): array
    {
        $statement = $this->database->prepare(
            'SELECT COUNT(*) AS websites,
                    COALESCE(SUM(storage_used), 0) AS storage_used,
                    COALESCE(SUM(bandwidth_used), 0) AS bandwidth_used,
                    SUM(status = "published") AS published_websites,
                    SUM(status = "draft") AS draft_websites
             FROM websites
             WHERE user_id = :user_id'
        );
        $statement->execute(['user_id' => $userId]);
        $stats = $statement->fetch() ?: [];

        return [
            'websites' => (int) ($stats['websites'] ?? 0),
            'published_websites' => (int) ($stats['published_websites'] ?? 0),
            'draft_websites' => (int) ($stats['draft_websites'] ?? 0),
            'storage_used' => (int) ($stats['storage_used'] ?? 0),
            'bandwidth_used' => (int) ($stats['bandwidth_used'] ?? 0),
        ];
    }

    /**
     * Return the active subscription or a free fallback.
     */
    public function subscription(int $userId): array
    {
        $statement = $this->database->prepare(
            'SELECT plan, amount, start_date, end_date, status
             FROM subscriptions
             WHERE user_id = :user_id AND status = "active"
             ORDER BY id DESC
             LIMIT 1'
        );
        $statement->execute(['user_id' => $userId]);
        $subscription = $statement->fetch();

        if ($subscription) {
            return $subscription;
        }

        $statement = $this->database->prepare(
            'SELECT plan, amount, start_date, end_date, status
             FROM subscriptions
             WHERE user_id = :user_id
             ORDER BY id DESC
             LIMIT 1'
        );
        $statement->execute(['user_id' => $userId]);
        $subscription = $statement->fetch();

        return $subscription ?: [
            'plan' => 'free',
            'amount' => 0,
            'start_date' => null,
            'end_date' => null,
            'status' => 'active',
        ];
    }

    /**
     * Return the user's recent websites.
     */
    public function websites(int $userId, int $limit = 6): array
    {
        $statement = $this->database->prepare(
            'SELECT id, website_name, slug, subdomain, custom_domain, storage_used, bandwidth_used, status, expires_at, created_at
             FROM websites
             WHERE user_id = :user_id
             ORDER BY created_at DESC
             LIMIT :limit'
        );
        $statement->bindValue('user_id', $userId, PDO::PARAM_INT);
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    /**
     * Return recent files across the user's websites.
     */
    public function recentFiles(int $userId, int $limit = 5): array
    {
        $statement = $this->database->prepare(
            'SELECT f.filename, f.filetype, f.filesize, f.uploaded_at, w.website_name
             FROM files f
             INNER JOIN websites w ON w.id = f.website_id
             WHERE w.user_id = :user_id
             ORDER BY f.uploaded_at DESC
             LIMIT :limit'
        );
        $statement->bindValue('user_id', $userId, PDO::PARAM_INT);
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    /**
     * Return recent notifications for the user.
     */
    public function notifications(int $userId, int $limit = 5): array
    {
        $statement = $this->database->prepare(
            'SELECT title, message, is_read, created_at
             FROM notifications
             WHERE user_id = :user_id
             ORDER BY created_at DESC
             LIMIT :limit'
        );
        $statement->bindValue('user_id', $userId, PDO::PARAM_INT);
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }
}
