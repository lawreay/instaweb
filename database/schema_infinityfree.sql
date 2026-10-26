-- ==========================================================
-- TurboHostMw Database
-- Developed by LawreayTech®
-- Database Name: if0_42267278_turohost_db
-- ==========================================================



-- ==========================================================
-- USERS
-- ==========================================================

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fullname VARCHAR(150) NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    phone VARCHAR(30),
    country VARCHAR(100),
    password VARCHAR(255) NOT NULL,
    avatar VARCHAR(255) DEFAULT 'default.png',
    bio VARCHAR(500) NULL,
    role ENUM('user', 'premium', 'moderator', 'admin', 'super_admin') DEFAULT 'user',
    account_status ENUM('active', 'pending', 'suspended', 'banned') DEFAULT 'pending',
    email_verified BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ==========================================================
-- EMAIL VERIFICATIONS
-- ==========================================================

CREATE TABLE email_verifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email_verifications_token_hash (token_hash),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ==========================================================
-- REMEMBER ME TOKENS
-- ==========================================================

CREATE TABLE remember_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    selector CHAR(24) NOT NULL UNIQUE,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_remember_tokens_selector (selector),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ==========================================================
-- PASSWORD RESETS
-- ==========================================================

CREATE TABLE password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_password_resets_token_hash (token_hash),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ==========================================================
-- TWO FACTOR AUTH CODES
-- ==========================================================

CREATE TABLE two_factor_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    code_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_two_factor_codes_user_id (user_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ==========================================================
-- WEBSITES
-- ==========================================================

CREATE TABLE websites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    website_name VARCHAR(120),
    slug VARCHAR(150) UNIQUE,
    template_key VARCHAR(40) NOT NULL DEFAULT 'blank',
    is_system_template BOOLEAN NOT NULL DEFAULT FALSE,
    subdomain VARCHAR(100),
    custom_domain VARCHAR(255),
    storage_used BIGINT DEFAULT 0,
    bandwidth_used BIGINT DEFAULT 0,
    status ENUM('draft', 'published', 'suspended', 'expired') DEFAULT 'draft',
    expires_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ==========================================================
-- FILES
-- ==========================================================

CREATE TABLE files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    website_id INT,
    filename VARCHAR(255),
    filepath TEXT,
    filetype VARCHAR(50),
    filesize BIGINT,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (website_id) REFERENCES websites(id) ON DELETE CASCADE
);

-- ==========================================================
-- SUBSCRIPTIONS
-- ==========================================================

CREATE TABLE subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    plan ENUM('free', 'premium') DEFAULT 'free',
    amount DECIMAL(10,2),
    start_date DATETIME,
    end_date DATETIME,
    status ENUM('active', 'expired', 'cancelled') DEFAULT 'active',
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ==========================================================
-- PAYMENTS
-- ==========================================================

CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    amount DECIMAL(10,2),
    currency VARCHAR(10) DEFAULT 'MWK',
    payment_method VARCHAR(50),
    transaction_id VARCHAR(255),
    tx_ref VARCHAR(120),
    provider_response JSON NULL,
    verified_at DATETIME NULL,
    payment_status ENUM('pending', 'paid', 'failed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE INDEX idx_payments_tx_ref (tx_ref),
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ==========================================================
-- VISITORS
-- ==========================================================

CREATE TABLE analytics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    website_id INT,
    ip_address VARCHAR(45),
    browser VARCHAR(100),
    device VARCHAR(100),
    country VARCHAR(100),
    visited_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(website_id) REFERENCES websites(id) ON DELETE CASCADE
);

-- ==========================================================
-- REPORTS
-- ==========================================================

CREATE TABLE reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    website_id INT,
    reported_by INT,
    reason TEXT,
    status ENUM('pending', 'reviewed', 'resolved') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ==========================================================
-- NOTIFICATIONS
-- ==========================================================

CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    category VARCHAR(80) DEFAULT 'general',
    icon VARCHAR(80) DEFAULT 'bell',
    title VARCHAR(255),
    message TEXT,
    target_url VARCHAR(255) DEFAULT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    email_sent BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ==========================================================
-- SETTINGS
-- ==========================================================

CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    site_name VARCHAR(150),
    site_email VARCHAR(150),
    maintenance_mode BOOLEAN DEFAULT FALSE,
    allow_registration BOOLEAN DEFAULT TRUE
);

-- ==========================================================
-- INSERT DEFAULT SETTINGS
-- ==========================================================

INSERT INTO settings(site_name, site_email)
VALUES ('TurboHostMw', 'admin@turbohostmw.com');

-- ==========================================================
-- PLATFORM SETTINGS
-- ==========================================================

CREATE TABLE platform_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(120) UNIQUE NOT NULL,
    setting_value TEXT NULL,
    setting_group VARCHAR(80) NOT NULL,
    is_secret BOOLEAN DEFAULT FALSE,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT INTO platform_settings(setting_key, setting_value, setting_group)
VALUES
('site_name', 'TurboHostMw', 'general'),
('site_email', 'admin@turbohostmw.com', 'general'),
('browser_title', 'TurboHostMw - Website Hosting Made Simple', 'branding'),
('meta_description', 'Create, upload, and publish static websites with TurboHostMw.', 'branding'),
('homepage_hero_title', 'Create websites directly in your browser.', 'homepage'),
('homepage_hero_subtitle', 'Upload files, manage projects, and publish online without complicated hosting setup.', 'homepage'),
('homepage_cta_text', 'Start free', 'homepage'),
('primary_color', '#0D6EFD', 'appearance'),
('secondary_color', '#00B4D8', 'appearance'),
('free_plan_price', 'MWK 0', 'pricing'),
('premium_plan_price', 'MWK 5,000/mo', 'pricing'),
('premium_plan_amount', '5000', 'pricing'),
('paychangu_currency', 'MWK', 'pricing'),
('paychangu_public_key', '', 'pricing'),
('paychangu_secret_key', '', 'pricing'),
('mail_host', 'smtp.gmail.com', 'smtp'),
('mail_port', '587', 'smtp'),
('mail_username', '', 'smtp'),
('mail_from_address', 'no-reply@turbohostmw.com', 'smtp'),
('mail_from_name', 'TurboHostMw', 'smtp'),
('admin_login_notification_email', '', 'notifications'),
('admin_login_alerts_enabled', '0', 'notifications'),
('email_notifications_enabled', '1', 'notifications'),
('password_min_length', '8', 'security'),
('login_attempt_limit', '5', 'security'),
('session_lifetime_minutes', '120', 'security'),
('free_storage_limit_mb', '100', 'storage'),
('premium_storage_limit_mb', '0', 'storage'),
('max_upload_size_mb', '10', 'uploads'),
('allowed_file_extensions', 'html,css,js,png,jpg,jpeg,gif,svg,webp,pdf,txt,zip', 'uploads'),
('backup_frequency', 'daily', 'backups'),
('api_rate_limit', '60', 'api');

-- ==========================================================
-- LEGAL POLICY MANAGEMENT
-- ==========================================================

CREATE TABLE IF NOT EXISTS legal_policies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    policy_type VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE INDEX idx_legal_policies_type (policy_type)
);

CREATE TABLE IF NOT EXISTS legal_policy_versions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    policy_id INT NOT NULL,
    version_label VARCHAR(100) NOT NULL,
    content LONGTEXT NOT NULL,
    published BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (policy_id) REFERENCES legal_policies(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS user_policy_acceptances (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    policy_id INT NOT NULL,
    version_id INT NOT NULL,
    accepted_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (policy_id) REFERENCES legal_policies(id) ON DELETE CASCADE,
    FOREIGN KEY (version_id) REFERENCES legal_policy_versions(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS policy_audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_type VARCHAR(50) NOT NULL,
    policy_id INT NOT NULL,
    version_id INT NOT NULL,
    user_id INT NOT NULL,
    note TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (policy_id) REFERENCES legal_policies(id) ON DELETE CASCADE,
    FOREIGN KEY (version_id) REFERENCES legal_policy_versions(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ==========================================================
-- DEFAULT SUPER ADMIN
-- Temporary password: ChangeMeNow123!
-- Password should be changed immediately after installation.
-- ==========================================================

INSERT INTO users(
    fullname,
    username,
    email,
    password,
    role,
    account_status,
    email_verified
)
VALUES (
    'System Administrator',
    'admin',
    'admin@turbohostmw.com',
    '$2y$10$4QND0muNv4rwnlQvoaRhrOZ1oBkXGr/ENdomFlwTtcNs7linlH4Lu',
    'super_admin',
    'active',
    TRUE
);
