<?php

namespace App\Models;

use App\Core\Model;
use PDO;

/**
 * Handles app notification persistence.
 */
class Notification extends Model
{
    protected string $table = 'notifications';

    public function __construct(?PDO $database = null)
    {
        parent::__construct($database);
    }

    public function create(?int $userId, string $title, string $message, string $category = 'general', string $icon = 'bell', ?string $targetUrl = null, bool $isRead = false, bool $emailSent = false): int
    {
        $statement = $this->database->prepare(
            'INSERT INTO notifications (user_id, category, icon, title, message, target_url, is_read, email_sent)
             VALUES (:user_id, :category, :icon, :title, :message, :target_url, :is_read, :email_sent)'
        );

        $statement->execute([
            'user_id' => $userId,
            'category' => $category,
            'icon' => $icon,
            'title' => $title,
            'message' => $message,
            'target_url' => $targetUrl,
            'is_read' => $isRead ? 1 : 0,
            'email_sent' => $emailSent ? 1 : 0,
        ]);

        return (int) $this->database->lastInsertId();
    }

    public function latestForUser(int $userId, int $limit = 5): array
    {
        $statement = $this->database->prepare(
            'SELECT id, category, icon, title, message, target_url, is_read, email_sent, created_at
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

    public function latestForAdmins(int $limit = 100): array
    {
        $statement = $this->database->prepare(
            'SELECT n.id, n.category, n.icon, n.title, n.message, n.target_url, n.is_read, n.email_sent, n.created_at, u.fullname, u.email
             FROM notifications n
             LEFT JOIN users u ON u.id = n.user_id
             ORDER BY n.created_at DESC
             LIMIT :limit'
        );

        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function existsForUserAndTitle(int $userId, string $title): bool
    {
        $statement = $this->database->prepare(
            'SELECT COUNT(*) FROM notifications WHERE user_id = :user_id AND title = :title');
        $statement->execute(['user_id' => $userId, 'title' => $title]);

        return (int) $statement->fetchColumn() > 0;
    }

    public function unreadCountForUser(int $userId): int
    {
        $statement = $this->database->prepare(
            'SELECT COUNT(*) FROM notifications WHERE user_id = :user_id AND is_read = 0'
        );
        $statement->execute(['user_id' => $userId]);

        return (int) $statement->fetchColumn();
    }

    public function markAsRead(int $notificationId): void
    {
        $statement = $this->database->prepare('UPDATE notifications SET is_read = 1 WHERE id = :id');
        $statement->execute(['id' => $notificationId]);
    }

    /** Mark every unread notification owned by the user as read. */
    public function markAllAsReadForUser(int $userId): int
    {
        $statement = $this->database->prepare(
            'UPDATE notifications SET is_read = 1 WHERE user_id = :user_id AND is_read = 0'
        );
        $statement->execute(['user_id' => $userId]);

        return $statement->rowCount();
    }

    /** Delete all notifications owned by a specific user. */
    public function clearAllForUser(int $userId): int
    {
        $statement = $this->database->prepare('DELETE FROM notifications WHERE user_id = :user_id');
        $statement->execute(['user_id' => $userId]);

        return $statement->rowCount();
    }

    public function markAsReadByUser(int $notificationId, int $userId): bool
    {
        $statement = $this->database->prepare('UPDATE notifications SET is_read = 1 WHERE id = :id AND user_id = :user_id');
        $statement->execute(['id' => $notificationId, 'user_id' => $userId]);

        return $statement->rowCount() > 0;
    }
}
