<?php

namespace App\Models;

use App\Core\Model;

/**
 * Persists portfolio content separately from the generated website files.
 */
class Portfolio extends Model
{
    /**
     * Find a portfolio only when both its website and owner match.
     */
    public function findForWebsiteOwner(int $websiteId, int $userId): ?array
    {
        $statement = $this->query(
            'SELECT p.*, w.website_name, w.slug, w.status AS website_status
             FROM portfolios p
             INNER JOIN websites w ON w.id = p.website_id
             WHERE p.website_id = :website_id
               AND p.user_id = :user_id
               AND w.user_id = :website_user_id
             LIMIT 1',
            [
                'website_id' => $websiteId,
                'user_id' => $userId,
                'website_user_id' => $userId,
            ]
        );

        $portfolio = $statement->fetch();

        return $portfolio ?: null;
    }

    /**
     * Create a default portfolio for an already-created website project.
     */
    public function createForWebsite(
        int $websiteId,
        int $userId,
        string $displayName,
        string $templateKey = 'classic'
    ): int {
        $statement = $this->query(
            'INSERT INTO portfolios
                (website_id, user_id, template_key, display_name, headline, bio, social_links)
             SELECT w.id, w.user_id, :template_key, :display_name, :headline, :bio, :social_links
             FROM websites w
             WHERE w.id = :website_id AND w.user_id = :user_id',
            [
                'template_key' => $templateKey,
                'display_name' => $displayName,
                'headline' => 'Portfolio',
                'bio' => '',
                'social_links' => json_encode(new \stdClass()),
                'website_id' => $websiteId,
                'user_id' => $userId,
            ]
        );

        if ($statement->rowCount() !== 1) {
            throw new \RuntimeException('Website not found for the current user.');
        }

        return (int) $this->database->lastInsertId();
    }

    /**
     * Update editable profile fields after the controller validates ownership and CSRF.
     */
    public function updateProfile(int $portfolioId, int $websiteId, int $userId, array $data): bool
    {
        $statement = $this->query(
            'UPDATE portfolios p
             INNER JOIN websites w ON w.id = p.website_id
             SET p.display_name = :display_name,
                 p.headline = :headline,
                 p.bio = :bio,
                 p.email = :email,
                 p.location = :location,
                 p.template_key = :template_key,
                 p.updated_at = CURRENT_TIMESTAMP
             WHERE p.id = :portfolio_id
               AND p.website_id = :website_id
               AND p.user_id = :user_id
               AND w.user_id = :website_user_id',
            [
                'display_name' => $data['display_name'],
                'headline' => $data['headline'],
                'bio' => $data['bio'],
                'email' => $data['email'],
                'location' => $data['location'],
                'template_key' => $data['template_key'],
                'portfolio_id' => $portfolioId,
                'website_id' => $websiteId,
                'user_id' => $userId,
                'website_user_id' => $userId,
            ]
        );

        return $statement->rowCount() > 0;
    }

    /**
     * Return portfolio projects in deterministic display order.
     */
    public function projects(int $portfolioId): array
    {
        $statement = $this->query(
            'SELECT id, title, summary, project_url, image_path, sort_order
             FROM portfolio_projects
             WHERE portfolio_id = :portfolio_id
             ORDER BY sort_order ASC, id ASC',
            ['portfolio_id' => $portfolioId]
        );

        return $statement->fetchAll();
    }
}
