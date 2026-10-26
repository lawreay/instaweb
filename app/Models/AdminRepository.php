<?php

namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * Reads admin dashboard data from the application database.
 */
class AdminRepository
{
    private PDO $database;

    public function __construct(?PDO $database = null)
    {
        $this->database = $database ?? Database::connection();
    }

    /**
     * Return dashboard counters for users, websites, payments, and reports.
     */
    public function stats(): array
    {
        return [
            'users' => $this->count('users'),
            'active_users' => $this->countWhere('users', "account_status = 'active'"),
            'websites' => $this->count('websites'),
            'published_websites' => $this->countWhere('websites', "status = 'published'"),
            'payments' => $this->count('payments'),
            'paid_revenue' => $this->sumPayments(),
            'reports' => $this->count('reports'),
            'pending_reports' => $this->countWhere('reports', "status = 'pending'"),
        ];
    }

    /**
     * Return recently created users.
     */
    public function recentUsers(int $limit = 8): array
    {
        $statement = $this->database->prepare(
            'SELECT id, fullname, username, email, role, account_status, email_verified, created_at
             FROM users
             ORDER BY created_at DESC
             LIMIT :limit'
        );
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    /**
     * Return all users for admin management.
     */
    public function users(int $limit = 100): array
    {
        $statement = $this->database->prepare(
            'SELECT id, fullname, username, email, phone, country, avatar, bio, role, account_status, email_verified, created_at, updated_at,
                    CASE
                        WHEN role IN (\'admin\', \'moderator\', \'premium\') THEN \'premium\'
                        ELSE COALESCE((SELECT plan FROM subscriptions WHERE user_id = u.id AND status = \'active\' ORDER BY id DESC LIMIT 1), \'free\')
                    END AS current_plan
             FROM users u
             ORDER BY created_at DESC
             LIMIT :limit'
        );
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    /**
     * Return recent websites with owner details.
     */
    public function recentWebsites(int $limit = 8): array
    {
        $statement = $this->database->prepare(
            'SELECT w.id, w.website_name, w.slug, w.subdomain, w.status, w.storage_used, w.created_at,
                    u.username, u.email
             FROM websites w
             INNER JOIN users u ON u.id = w.user_id
             ORDER BY w.created_at DESC
             LIMIT :limit'
        );
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    /**
     * Return all hosted websites with owner details.
     */
    public function websites(int $limit = 100): array
    {
        $statement = $this->database->prepare(
            'SELECT w.id, w.website_name, w.slug, w.subdomain, w.custom_domain, w.status, w.is_system_template,
                    w.storage_used, w.bandwidth_used, w.expires_at, w.created_at,
                    u.username, u.email
             FROM websites w
             INNER JOIN users u ON u.id = w.user_id
             ORDER BY w.created_at DESC
             LIMIT :limit'
        );
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    /**
     * Return one website record with owner details for admin actions.
     */
    public function website(int $id): ?array
    {
        $statement = $this->database->prepare(
            'SELECT w.id, w.user_id, w.website_name, w.slug, w.subdomain, w.custom_domain, w.status, w.is_system_template,
                    w.storage_used, w.bandwidth_used, w.expires_at, w.created_at,
                    u.username, u.email
             FROM websites w
             INNER JOIN users u ON u.id = w.user_id
             WHERE w.id = :id
             LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $website = $statement->fetch();

        return $website ?: null;
    }

    /**
     * Update website status from the admin console.
     */
    public function updateWebsiteStatus(int $id, string $status): void
    {
        $statement = $this->database->prepare('UPDATE websites SET status = :status WHERE id = :id');
        $statement->execute(['status' => $status, 'id' => $id]);
    }

    /**
     * Delete a website record from the admin console.
     */
    public function deleteWebsite(int $id): bool
    {
        $statement = $this->database->prepare('DELETE FROM websites WHERE id = :id AND is_system_template = 0');
        $statement->execute(['id' => $id]);

        return $statement->rowCount() === 1;
    }

    /** Keep the account owning a protected system site intact through the admin UI. */
    public function userOwnsSystemWebsite(int $userId): bool
    {
        $statement = $this->database->prepare(
            'SELECT 1 FROM websites WHERE user_id = :user_id AND is_system_template = 1 LIMIT 1'
        );
        $statement->execute(['user_id' => $userId]);

        return (bool) $statement->fetchColumn();
    }

    /**
     * Return all payment records with user details.
     */
    public function payments(int $limit = 100): array
    {
        $statement = $this->database->prepare(
            'SELECT p.id, p.amount, p.currency, p.payment_method, p.transaction_id,
                    p.tx_ref, p.payment_status, p.created_at, u.fullname, u.email
             FROM payments p
             INNER JOIN users u ON u.id = p.user_id
             ORDER BY p.created_at DESC
             LIMIT :limit'
        );
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    /**
     * Return website reports with related website and reporter details when available.
     */
    public function reports(int $limit = 100): array
    {
        $statement = $this->database->prepare(
            'SELECT r.id, r.reason, r.status, r.created_at,
                    w.website_name, w.slug,
                    u.fullname AS reporter_name, u.email AS reporter_email
             FROM reports r
             LEFT JOIN websites w ON w.id = r.website_id
             LEFT JOIN users u ON u.id = r.reported_by
             ORDER BY r.created_at DESC
             LIMIT :limit'
        );
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    /**
     * Return recent notifications.
     */
    public function notifications(int $limit = 100): array
    {
        $statement = $this->database->prepare(
            'SELECT n.id, n.title, n.message, n.is_read, n.created_at, u.fullname, u.email
             FROM notifications n
             LEFT JOIN users u ON u.id = n.user_id
             ORDER BY n.created_at DESC
             LIMIT :limit'
        );
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    /**
     * Return payment totals grouped by status.
     */
    public function paymentSummary(): array
    {
        $statement = $this->database->query(
            'SELECT payment_status, COUNT(*) AS total, COALESCE(SUM(amount), 0) AS amount
             FROM payments
             GROUP BY payment_status'
        );

        return $statement->fetchAll();
    }

    /**
     * Count all rows in a known table.
     */
    private function count(string $table): int
    {
        return (int) $this->database->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
    }

    /**
     * Count rows matching a safe hardcoded condition.
     */
    private function countWhere(string $table, string $condition): int
    {
        return (int) $this->database->query("SELECT COUNT(*) FROM {$table} WHERE {$condition}")->fetchColumn();
    }

    /**
     * Sum paid payment revenue.
     */
    private function sumPayments(): float
    {
        return (float) $this->database
            ->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payment_status = 'paid'")
            ->fetchColumn();
    }
}
