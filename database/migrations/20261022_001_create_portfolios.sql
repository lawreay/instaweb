-- InstaWeb portfolio integration, migration 001.
-- Apply once to the existing InstaWeb database after taking a backup.
-- This migration intentionally stores portfolio content separately from generated site files.

CREATE TABLE IF NOT EXISTS portfolios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    website_id INT NOT NULL,
    user_id INT NOT NULL,
    template_key VARCHAR(40) NOT NULL DEFAULT 'classic',
    display_name VARCHAR(120) NOT NULL,
    headline VARCHAR(180) NOT NULL DEFAULT '',
    bio TEXT NULL,
    email VARCHAR(190) NULL,
    location VARCHAR(120) NULL,
    avatar_path VARCHAR(255) NULL,
    social_links JSON NULL,
    published_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_portfolios_website (website_id),
    UNIQUE KEY uq_portfolios_public_name (user_id, display_name),
    KEY idx_portfolios_user (user_id),
    CONSTRAINT fk_portfolios_website
        FOREIGN KEY (website_id) REFERENCES websites(id) ON DELETE CASCADE,
    CONSTRAINT fk_portfolios_user
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS portfolio_projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    portfolio_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    summary TEXT NULL,
    project_url VARCHAR(500) NULL,
    image_path VARCHAR(255) NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_portfolio_projects_order (portfolio_id, sort_order, id),
    CONSTRAINT fk_portfolio_projects_portfolio
        FOREIGN KEY (portfolio_id) REFERENCES portfolios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
