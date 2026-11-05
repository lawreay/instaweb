-- Theme preferences are stored separately from structured website content.
-- The legacy websites table is MyISAM; ownership is enforced by scoped model queries.
CREATE TABLE IF NOT EXISTS business_website_themes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    website_id INT NOT NULL,
    user_id INT NOT NULL,
    theme_key VARCHAR(40) NOT NULL DEFAULT 'evergreen',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_business_theme_website (website_id),
    KEY idx_business_theme_owner (user_id, website_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
