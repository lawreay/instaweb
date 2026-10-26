<?php

namespace App\Models;

use App\Core\Model;
use DateTimeImmutable;
use PDO;

/**
 * Handles website project records for authenticated users.
 */
class Website extends Model
{
    protected string $table = 'websites';

    /**
     * Return website projects owned by a user.
     */
    public function forUser(int $userId): array
    {
        $statement = $this->query(
            'SELECT id, user_id, website_name, slug, template_key, is_system_template, subdomain, custom_domain, storage_used, bandwidth_used, status, expires_at, created_at
             FROM websites
             WHERE user_id = :user_id
             ORDER BY created_at DESC',
            ['user_id' => $userId]
        );

        return $statement->fetchAll();
    }

    /**
     * Find one website project owned by a user.
     */
    public function findForUser(int $id, int $userId): ?array
    {
        $statement = $this->query(
            'SELECT w.*, u.username
             FROM websites w
             INNER JOIN users u ON u.id = w.user_id
             WHERE w.id = :id AND w.user_id = :user_id
             LIMIT 1',
            ['id' => $id, 'user_id' => $userId]
        );
        $website = $statement->fetch();

        return $website ?: null;
    }

    /**
     * Create a website project record.
     */
    public function createProject(int $userId, string $name, string $slug, bool $premium, int $freeHostingDays = 30, string $templateKey = 'blank'): int
    {
        $expiresAt = $premium ? null : (new DateTimeImmutable('+' . max(1, $freeHostingDays) . ' days'))->format('Y-m-d H:i:s');
        $statement = $this->database->prepare(
            'INSERT INTO websites (user_id, website_name, slug, template_key, status, expires_at)
             VALUES (:user_id, :website_name, :slug, :template_key, :status, :expires_at)'
        );
        $statement->execute([
            'user_id' => $userId,
            'website_name' => $name,
            'slug' => $slug,
            'template_key' => $templateKey,
            'status' => 'draft',
            'expires_at' => $expiresAt,
        ]);

        return (int) $this->database->lastInsertId();
    }

    /**
     * Update editable project metadata.
     */
    public function updateProject(int $id, int $userId, string $name): bool
    {
        $statement = $this->database->prepare(
            'UPDATE websites
             SET website_name = :website_name
             WHERE id = :id AND user_id = :user_id'
        );

        return $statement->execute([
            'website_name' => $name,
            'id' => $id,
            'user_id' => $userId,
        ]);
    }

    /**
     * Delete one website project owned by a user.
     */
    public function deleteForUser(int $id, int $userId): bool
    {
        // Verify ownership before touching any dependent records. The legacy
        // websites table uses MyISAM, so these deletes cannot share a transaction
        // with it; clean dependent rows first so a missing migration never leaves
        // a deleted website with an exception halfway through its cleanup.
        $ownerCheck = $this->database->prepare(
            'SELECT id, is_system_template FROM websites WHERE id = :id AND user_id = :user_id LIMIT 1'
        );
        $ownerCheck->execute(['id' => $id, 'user_id' => $userId]);
        $website = $ownerCheck->fetch();
        if (!$website || !empty($website['is_system_template'])) {
            return false;
        }

        // Capture the avatar path before deleting the portfolio row so its
        // generated public file can be removed after the database cleanup succeeds.
        $avatarStatement = $this->database->prepare(
            'SELECT avatar_path FROM portfolios WHERE website_id = :website_id AND user_id = :user_id LIMIT 1'
        );
        $avatarStatement->execute(['website_id' => $id, 'user_id' => $userId]);
        $avatarPath = $avatarStatement->fetchColumn();

        // Advanced tables deliberately have no foreign keys to legacy MyISAM
        // websites. All cleanup remains scoped to both website and owner.
        foreach (['portfolio_advanced_items', 'portfolio_advanced_settings', 'portfolio_advanced_uploads'] as $table) {
            $deleteAdvanced = $this->database->prepare(
                "DELETE FROM {$table} WHERE website_id = :website_id AND user_id = :user_id"
            );
            $deleteAdvanced->execute(['website_id' => $id, 'user_id' => $userId]);
        }

        // Delete portfolio rows before the parent website row. portfolio_projects
        // is removed by its InnoDB foreign-key cascade from portfolios.
        $deletePortfolios = $this->database->prepare(
            'DELETE FROM portfolios WHERE website_id = :website_id AND user_id = :user_id'
        );
        $deletePortfolios->execute(['website_id' => $id, 'user_id' => $userId]);

        $statement = $this->database->prepare(
            'DELETE FROM websites WHERE id = :id AND user_id = :user_id'
        );
        $statement->execute(['id' => $id, 'user_id' => $userId]);
        if ($statement->rowCount() !== 1) {
            return false;
        }

        // Avatar uploads live under a per-website directory. Only unlink a
        // stored path inside that exact directory; never trust a DB path as-is.
        if (is_string($avatarPath) && str_starts_with($avatarPath, 'uploads/portfolios/' . $id . '/')) {
            $publicRoot = realpath(dirname(__DIR__, 2) . '/public');
            $uploadDirectory = $publicRoot === false
                ? false
                : realpath($publicRoot . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'portfolios' . DIRECTORY_SEPARATOR . $id);
            $avatarAbsolutePath = $publicRoot === false
                ? false
                : realpath($publicRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $avatarPath));

            if ($uploadDirectory !== false && $avatarAbsolutePath !== false
                && str_starts_with($avatarAbsolutePath, rtrim($uploadDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR)
                && is_file($avatarAbsolutePath)) {
                @unlink($avatarAbsolutePath);
            }
        }

        return true;
    }

    /** A protected system sample remains available as a portfolio template. */
    public function isSystemTemplate(array $website): bool
    {
        return !empty($website['is_system_template']);
    }

    /**
     * Count active project records for plan limit checks.
     */
    public function activeCountForUser(int $userId): int
    {
        $statement = $this->database->prepare(
            "SELECT COUNT(*) FROM websites WHERE user_id = :user_id AND status IN ('draft', 'published')"
        );
        $statement->execute(['user_id' => $userId]);

        return (int) $statement->fetchColumn();
    }

    /**
     * Check if a slug already exists.
     */
    public function slugExists(string $slug): bool
    {
        $statement = $this->database->prepare('SELECT COUNT(*) FROM websites WHERE slug = :slug');
        $statement->execute(['slug' => $slug]);

        return (int) $statement->fetchColumn() > 0;
    }

    /**
     * Return all published website projects for sitemap generation.
     */
    public function publishedSites(): array
    {
        $statement = $this->query(
            'SELECT id, slug, website_name, template_key, created_at
             FROM websites
             WHERE status = :status
             ORDER BY created_at DESC',
            ['status' => 'published']
        );

        return $statement->fetchAll();
    }

    /**
     * Find a published portfolio website by its public slug.
     */
    public function findPublishedPortfolioBySlug(string $slug): ?array
    {
        $statement = $this->query(
            "SELECT w.*
             FROM websites w
             INNER JOIN portfolios p ON p.website_id = w.id AND p.user_id = w.user_id
             WHERE w.slug = :slug
               AND w.template_key = 'portfolio'
               AND w.status = 'published'
             LIMIT 1",
            ['slug' => $slug]
        );
        $website = $statement->fetch();

        return $website ?: null;
    }

    /**
     * Return the user's current plan from role or latest subscription.
     */
    public function userPlan(int $userId): string
    {
        $statement = $this->database->prepare(
            'SELECT role FROM users WHERE id = :id LIMIT 1'
        );
        $statement->execute(['id' => $userId]);
        $role = (string) $statement->fetchColumn();

        if (in_array($role, ['premium', 'admin', 'moderator'], true)) {
            return 'premium';
        }

        $statement = $this->database->prepare(
            "SELECT plan FROM subscriptions
             WHERE user_id = :user_id AND status = 'active'
             ORDER BY id DESC
             LIMIT 1"
        );
        $statement->execute(['user_id' => $userId]);
        $plan = (string) $statement->fetchColumn();

        return $plan === 'premium' ? 'premium' : 'free';
    }

    /**
     * Return a user's public username for paths and URLs.
     */
    public function usernameForUser(int $userId): ?string
    {
        $statement = $this->database->prepare('SELECT username FROM users WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $userId]);
        $username = $statement->fetchColumn();

        return is_string($username) && $username !== '' ? $username : null;
    }

    /**
     * Record a starter file for dashboard recent-file display.
     */
    public function addFileRecord(int $websiteId, string $filename, string $relativePath, string $type, int $size): void
    {
        $statement = $this->database->prepare(
            'INSERT INTO files (website_id, filename, filepath, filetype, filesize)
             VALUES (:website_id, :filename, :filepath, :filetype, :filesize)'
        );
        $statement->execute([
            'website_id' => $websiteId,
            'filename' => $filename,
            'filepath' => $relativePath,
            'filetype' => $type,
            'filesize' => $size,
        ]);
    }

    /**
     * Replace a file record by project-relative path.
     */
    public function replaceFileRecord(int $websiteId, string $filename, string $relativePath, string $type, int $size): void
    {
        $delete = $this->database->prepare('DELETE FROM files WHERE website_id = :website_id AND filepath = :filepath');
        $delete->execute(['website_id' => $websiteId, 'filepath' => $relativePath]);

        $this->addFileRecord($websiteId, $filename, $relativePath, $type, $size);
    }

    /**
     * Delete one or more file records under a path.
     */
    public function deleteFileRecordsUnderPath(int $websiteId, string $relativePath): void
    {
        $statement = $this->database->prepare(
            'DELETE FROM files
             WHERE website_id = :website_id
             AND (filepath = :filepath OR filepath LIKE :child_path)'
        );
        $statement->execute([
            'website_id' => $websiteId,
            'filepath' => $relativePath,
            'child_path' => rtrim($relativePath, '/') . '/%',
        ]);
    }

    /**
     * Update tracked file paths after a file or folder move.
     */
    public function moveFileRecords(int $websiteId, string $fromPath, string $toPath, string $type, int $size): void
    {
        if ($type === 'file') {
            $statement = $this->database->prepare(
                'UPDATE files
                 SET filename = :filename, filepath = :to_path, filetype = :filetype, filesize = :filesize
                 WHERE website_id = :website_id AND filepath = :from_path'
            );
            $statement->execute([
                'filename' => basename($toPath),
                'to_path' => $toPath,
                'filetype' => strtolower(pathinfo($toPath, PATHINFO_EXTENSION)),
                'filesize' => $size,
                'website_id' => $websiteId,
                'from_path' => $fromPath,
            ]);
            return;
        }

        $statement = $this->database->prepare(
            'SELECT id, filepath FROM files
             WHERE website_id = :website_id
             AND (filepath = :from_path OR filepath LIKE :child_path)'
        );
        $statement->execute([
            'website_id' => $websiteId,
            'from_path' => $fromPath,
            'child_path' => rtrim($fromPath, '/') . '/%',
        ]);

        foreach ($statement->fetchAll() as $file) {
            $newPath = $toPath . substr($file['filepath'], strlen($fromPath));
            $update = $this->database->prepare(
                'UPDATE files SET filename = :filename, filepath = :filepath WHERE id = :id'
            );
            $update->execute([
                'filename' => basename($newPath),
                'filepath' => $newPath,
                'id' => (int) $file['id'],
            ]);
        }
    }

    /**
     * Return total storage used across a user's projects.
     */
    public function totalStorageForUser(int $userId): int
    {
        $statement = $this->database->prepare('SELECT COALESCE(SUM(storage_used), 0) FROM websites WHERE user_id = :user_id');
        $statement->execute(['user_id' => $userId]);

        return (int) $statement->fetchColumn();
    }

    /**
     * Replace file records from a storage listing.
     */
    public function syncFileRecords(int $websiteId, array $items): void
    {
        $this->database->beginTransaction();

        try {
            $delete = $this->database->prepare('DELETE FROM files WHERE website_id = :website_id');
            $delete->execute(['website_id' => $websiteId]);

            foreach ($items as $item) {
                if (($item['type'] ?? '') !== 'file') {
                    continue;
                }

                $this->addFileRecord(
                    $websiteId,
                    basename((string) $item['path']),
                    (string) $item['path'],
                    strtolower(pathinfo((string) $item['path'], PATHINFO_EXTENSION)),
                    (int) $item['size']
                );
            }

            $this->database->commit();
        } catch (\Throwable $exception) {
            $this->database->rollBack();
            throw $exception;
        }
    }

    /**
     * Update cached storage usage for a website.
     */
    public function updateStorageUsed(int $websiteId, int $bytes): void
    {
        $statement = $this->database->prepare('UPDATE websites SET storage_used = :storage_used WHERE id = :id');
        $statement->bindValue('storage_used', $bytes, PDO::PARAM_INT);
        $statement->bindValue('id', $websiteId, PDO::PARAM_INT);
        $statement->execute();
    }

    /**
     * Update a website status when the owner performs a lifecycle action.
     */
    public function updateStatus(int $websiteId, int $userId, string $status): void
    {
        $statement = $this->database->prepare(
            'UPDATE websites SET status = :status WHERE id = :id AND user_id = :user_id'
        );
        $statement->execute([
            'status' => $status,
            'id' => $websiteId,
            'user_id' => $userId,
        ]);
    }
}
