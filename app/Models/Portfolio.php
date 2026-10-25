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
     * Load public portfolio content only when its parent website is published.
     */
    public function findPublishedForWebsite(int $websiteId): ?array
    {
        $statement = $this->query(
            "SELECT p.*, w.website_name, w.slug, w.status AS website_status
             FROM portfolios p
             INNER JOIN websites w ON w.id = p.website_id AND w.user_id = p.user_id
             WHERE p.website_id = :website_id
               AND w.template_key = 'portfolio'
               AND w.status = 'published'
             LIMIT 1",
            ['website_id' => $websiteId]
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
                 p.social_links = :social_links,
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
                'social_links' => json_encode($data['social_links'] ?? new \stdClass(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'portfolio_id' => $portfolioId,
                'website_id' => $websiteId,
                'user_id' => $userId,
                'website_user_id' => $userId,
            ]
        );

        return $statement->rowCount() > 0;
    }

    /**
     * Update the profile image only for the authenticated owner's portfolio.
     */
    public function updateAvatarForOwner(int $portfolioId, int $websiteId, int $userId, string $avatarPath): bool
    {
        $statement = $this->query(
            'UPDATE portfolios p
             INNER JOIN websites w ON w.id = p.website_id
             SET p.avatar_path = :avatar_path,
                 p.updated_at = CURRENT_TIMESTAMP
             WHERE p.id = :portfolio_id
               AND p.website_id = :website_id
               AND p.user_id = :user_id
               AND w.user_id = :website_user_id',
            [
                'avatar_path' => $avatarPath,
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

    /**
     * Add a project only when the portfolio and website belong to the current user.
     */
    public function addProjectForOwner(int $portfolioId, int $websiteId, int $userId, array $data): bool
    {
        $statement = $this->query(
            'INSERT INTO portfolio_projects (portfolio_id, title, summary, project_url, sort_order)
             SELECT p.id, :title, :summary, :project_url,
                    COALESCE((SELECT MAX(existing.sort_order) + 1
                              FROM portfolio_projects existing
                              WHERE existing.portfolio_id = p.id), 0)
             FROM portfolios p
             INNER JOIN websites w ON w.id = p.website_id
             WHERE p.id = :portfolio_id
               AND p.website_id = :website_id
               AND p.user_id = :user_id
               AND w.user_id = :website_user_id',
            [
                'title' => $data['title'],
                'summary' => $data['summary'],
                'project_url' => $data['project_url'],
                'portfolio_id' => $portfolioId,
                'website_id' => $websiteId,
                'user_id' => $userId,
                'website_user_id' => $userId,
            ]
        );

        return $statement->rowCount() === 1;
    }

    /**
     * Update a project only through the portfolio and website owned by the current user.
     */
    public function updateProjectForOwner(int $projectId, int $portfolioId, int $websiteId, int $userId, array $data): bool
    {
        $statement = $this->query(
            'UPDATE portfolio_projects pp
             INNER JOIN portfolios p ON p.id = pp.portfolio_id
             INNER JOIN websites w ON w.id = p.website_id
             SET pp.title = :title,
                 pp.summary = :summary,
                 pp.project_url = :project_url,
                 pp.sort_order = :sort_order
             WHERE pp.id = :project_id
               AND p.id = :portfolio_id
               AND p.website_id = :website_id
               AND p.user_id = :user_id
               AND w.user_id = :website_user_id',
            [
                'title' => $data['title'],
                'summary' => $data['summary'],
                'project_url' => $data['project_url'],
                'sort_order' => $data['sort_order'],
                'project_id' => $projectId,
                'portfolio_id' => $portfolioId,
                'website_id' => $websiteId,
                'user_id' => $userId,
                'website_user_id' => $userId,
            ]
        );

        return $statement->rowCount() > 0;
    }

    /**
     * Delete a project only through a portfolio owned by the current user.
     */
    public function deleteProjectForOwner(int $projectId, int $portfolioId, int $websiteId, int $userId): bool
    {
        $statement = $this->query(
            'DELETE pp
             FROM portfolio_projects pp
             INNER JOIN portfolios p ON p.id = pp.portfolio_id
             INNER JOIN websites w ON w.id = p.website_id
             WHERE pp.id = :project_id
               AND p.id = :portfolio_id
               AND p.website_id = :website_id
               AND p.user_id = :user_id
               AND w.user_id = :website_user_id',
            [
                'project_id' => $projectId,
                'portfolio_id' => $portfolioId,
                'website_id' => $websiteId,
                'user_id' => $userId,
                'website_user_id' => $userId,
            ]
        );

        return $statement->rowCount() === 1;
    }

}
