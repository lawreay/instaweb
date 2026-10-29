CREATE TABLE IF NOT EXISTS feedback_submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    submitter_name VARCHAR(150) NOT NULL,
    submitter_email VARCHAR(150) NULL,
    type ENUM('problem', 'feature') NOT NULL,
    subject VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,
    page_url VARCHAR(500) NULL,
    status ENUM('new', 'in_progress', 'resolved', 'closed') NOT NULL DEFAULT 'new',
    admin_note TEXT NULL,
    email_status ENUM('pending', 'sent', 'failed', 'not_configured') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_feedback_status_created (status, created_at),
    INDEX idx_feedback_type_created (type, created_at),
    CONSTRAINT fk_feedback_submissions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);
