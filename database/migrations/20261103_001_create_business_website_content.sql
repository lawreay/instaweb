-- Structured content for managed Business starter websites.
-- The legacy websites table is MyISAM, so ownership is enforced in application queries.
CREATE TABLE IF NOT EXISTS business_website_content (
    id INT AUTO_INCREMENT PRIMARY KEY,
    website_id INT NOT NULL,
    user_id INT NOT NULL,
    content_json JSON NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_business_content_website (website_id),
    KEY idx_business_content_owner (user_id, website_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
