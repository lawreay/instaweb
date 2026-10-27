<?php

declare(strict_types=1);

use App\Core\Database;

require dirname(__DIR__) . '/vendor/autoload.php';

/**
 * Creates or refreshes the protected default portfolio owned by the first
 * super-admin/admin account. The script is intentionally idempotent so it can
 * be safely re-run after deploying the feature.
 *
 * Usage: php scripts/seed-default-admin-portfolio.php
 */

function ensureSystemTemplateColumn(PDO $database): void
{
    $statement = $database->query(
        "SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = 'websites'
           AND COLUMN_NAME = 'is_system_template'
         LIMIT 1"
    );

    if (!$statement->fetchColumn()) {
        $database->exec(
            'ALTER TABLE websites
             ADD COLUMN is_system_template TINYINT(1) NOT NULL DEFAULT 0 AFTER template_key'
        );
    }
}

function requireTable(PDO $database, string $table): void
{
    $statement = $database->prepare(
        'SELECT 1 FROM INFORMATION_SCHEMA.TABLES
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table_name
         LIMIT 1'
    );
    $statement->execute(['table_name' => $table]);

    if (!$statement->fetchColumn()) {
        throw new RuntimeException("Required table '{$table}' is missing. Apply the portfolio migrations first.");
    }
}

function ensurePlatformSettingsTable(PDO $database): void
{
    $database->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS platform_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(120) UNIQUE NOT NULL,
    setting_value TEXT NULL,
    setting_group VARCHAR(80) NOT NULL,
    is_secret BOOLEAN DEFAULT FALSE,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
}

function encodePayload(array $payload): string
{
    $json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        throw new RuntimeException('Could not encode default portfolio content.');
    }

    return $json;
}

try {
    $database = Database::connection();
    ensureSystemTemplateColumn($database);
    ensurePlatformSettingsTable($database);
    foreach (['portfolios', 'portfolio_projects', 'portfolio_advanced_settings', 'portfolio_advanced_items', 'platform_settings'] as $table) {
        requireTable($database, $table);
    }

    $adminStatement = $database->query(
        "SELECT id FROM users
         WHERE role IN ('super_admin', 'admin')
         ORDER BY CASE role WHEN 'super_admin' THEN 0 ELSE 1 END, id ASC
         LIMIT 1"
    );
    $adminId = (int) $adminStatement->fetchColumn();
    if ($adminId < 1) {
        throw new RuntimeException('No admin account was found. Create an admin account before running this seed.');
    }

    $slug = 'john-doe-bba-accounting';
    $websiteStatement = $database->prepare('SELECT id FROM websites WHERE slug = :slug LIMIT 1');
    $websiteStatement->execute(['slug' => $slug]);
    $websiteId = (int) $websiteStatement->fetchColumn();

    if ($websiteId < 1) {
        $insertWebsite = $database->prepare(
            "INSERT INTO websites
                (user_id, website_name, slug, template_key, is_system_template, status, expires_at)
             VALUES
                (:user_id, :website_name, :slug, 'portfolio', 1, 'published', NULL)"
        );
        $insertWebsite->execute([
            'user_id' => $adminId,
            'website_name' => 'John Doe | BBA Accounting Graduate',
            'slug' => $slug,
        ]);
        $websiteId = (int) $database->lastInsertId();
    } else {
        $updateWebsite = $database->prepare(
            "UPDATE websites
             SET user_id = :user_id,
                 website_name = :website_name,
                 template_key = 'portfolio',
                 is_system_template = 1,
                 status = 'published',
                 expires_at = NULL
             WHERE id = :id"
        );
        $updateWebsite->execute([
            'id' => $websiteId,
            'user_id' => $adminId,
            'website_name' => 'John Doe | BBA Accounting Graduate',
        ]);
    }

    $avatarRelativePath = 'uploads/portfolios/' . $websiteId . '/john-doe-demo-portrait.jpg';
    $avatarDirectory = dirname(__DIR__) . '/public/uploads/portfolios/' . $websiteId;
    $portraitSource = dirname(__DIR__) . '/public/assets/portfolio-template/demo/john-doe/portrait.jpg';
    if (!is_file($portraitSource)) {
        throw new RuntimeException('The John Doe demo portrait is missing. Restore public/assets/portfolio-template/demo/john-doe/portrait.jpg.');
    }
    if (!is_dir($avatarDirectory) && !mkdir($avatarDirectory, 0755, true) && !is_dir($avatarDirectory)) {
        throw new RuntimeException('Could not create the portfolio image directory.');
    }
    if (!copy($portraitSource, $avatarDirectory . '/john-doe-demo-portrait.jpg')) {
        throw new RuntimeException('Could not prepare the John Doe profile image.');
    }

    $socialLinks = encodePayload([
        'linkedin' => 'https://example.com/john-doe-linkedin',
        'website' => 'https://example.com/john-doe',
    ]);
    $portfolioStatement = $database->prepare('SELECT id FROM portfolios WHERE website_id = :website_id LIMIT 1');
    $portfolioStatement->execute(['website_id' => $websiteId]);
    $portfolioId = (int) $portfolioStatement->fetchColumn();
    $profile = [
        'user_id' => $adminId,
        'template_key' => 'classic',
        'display_name' => 'John Doe',
        'headline' => 'BBA Accounting Graduate | Malawi Adventist University',
        'bio' => "John Doe is a detail-focused BBA Accounting graduate from Malawi Adventist University. This demonstration portfolio brings together graduate-level financial analysis, bookkeeping, budgeting, and internal-control work—presented as a practical starting point for finance and accounting opportunities.",
        'email' => 'john.doe@portfolio.example',
        'location' => 'Blantyre, Malawi',
        'avatar_path' => $avatarRelativePath,
        'social_links' => $socialLinks,
    ];

    if ($portfolioId < 1) {
        $insertPortfolio = $database->prepare(
            'INSERT INTO portfolios
                (website_id, user_id, template_key, display_name, headline, bio, email, location, avatar_path, social_links, published_at)
             VALUES
                (:website_id, :user_id, :template_key, :display_name, :headline, :bio, :email, :location, :avatar_path, :social_links, NOW())'
        );
        $insertPortfolio->execute(['website_id' => $websiteId] + $profile);
        $portfolioId = (int) $database->lastInsertId();
    } else {
        $updatePortfolio = $database->prepare(
            'UPDATE portfolios
             SET user_id = :user_id, template_key = :template_key, display_name = :display_name,
                 headline = :headline, bio = :bio, email = :email, location = :location,
                 avatar_path = :avatar_path, social_links = :social_links, published_at = NOW()
             WHERE id = :id AND website_id = :website_id'
        );
        $updatePortfolio->execute(['id' => $portfolioId, 'website_id' => $websiteId] + $profile);
    }

    $settings = [
        'accent_color' => '#0f766e',
        'background_color' => '#f8fafc',
        'text_color' => '#102a43',
        'font_family' => 'serif',
        'hero_style' => 'split',
        'show_projects' => '1',
        'show_contact' => '1',
    ];
    $saveSetting = $database->prepare(
        'INSERT INTO portfolio_advanced_settings
            (website_id, user_id, setting_key, setting_value, value_type)
         VALUES (:website_id, :user_id, :setting_key, :setting_value, :value_type)
         ON DUPLICATE KEY UPDATE
            user_id = VALUES(user_id), setting_value = VALUES(setting_value),
            value_type = VALUES(value_type), updated_at = CURRENT_TIMESTAMP'
    );
    foreach ($settings as $key => $value) {
        $saveSetting->execute([
            'website_id' => $websiteId,
            'user_id' => $adminId,
            'setting_key' => $key,
            'setting_value' => $value,
            'value_type' => 'text',
        ]);
    }

    $items = [
        ['goals', 'Build a strong accounting career', ['details' => 'Grow from graduate-level financial support into a trusted accounting professional who helps organisations make clear, well-supported decisions.'], 10],
        ['goals', 'Complete professional development milestones', ['details' => 'Develop practical capability in financial reporting, audit preparation, tax fundamentals, and accounting systems through structured training and supervised work.'], 20],
        ['interests', 'Financial reporting and business performance', ['details' => 'Interested in turning accounting records into useful management insight through clean reporting, ratio analysis, and clear communication.'], 10],
        ['interests', 'Small-business finance', ['details' => 'Motivated by practical bookkeeping, cash-flow planning, and simple internal controls that help growing businesses stay organised.'], 20],
        ['challenges', 'Turning incomplete records into a useful monthly view', ['details' => 'Structured source documents, reconciled key balances, and created an exception list for follow-up before reporting.', 'outcome' => 'A clearer month-end pack with issues visible early.'], 10],
        ['experience', 'Graduate Finance Assistant', ['organisation' => 'Sample commercial placement', 'details' => 'Supported invoice tracking, bank reconciliations, supplier statement checks, and monthly reporting schedules under review.', 'start_date' => '2025', 'end_date' => '2026', 'current' => '0', 'period' => '2025 – 2026'], 10],
        ['experience', 'Accounts Support Volunteer', ['organisation' => 'Sample community enterprise', 'details' => 'Organised receipt records, maintained a simple cashbook, and prepared a practical budget-versus-actual summary for the committee.', 'start_date' => '2024', 'end_date' => '2025', 'current' => '0', 'period' => '2024 – 2025'], 20],
        ['experience', 'Budget Lead — Student Project', ['organisation' => 'Malawi Adventist University · sample project', 'details' => 'Prepared an activity budget, monitored expenses against approved limits, and presented a short variance explanation to the project team.', 'start_date' => '2023', 'end_date' => '2024', 'current' => '0', 'period' => '2023 – 2024'], 30],
        ['education', 'Bachelor of Business Administration in Accounting', ['organisation' => 'Malawi Adventist University', 'field_of_study' => 'BBA Accounting', 'details' => 'Coursework focus: financial accounting, management accounting, auditing, taxation, business finance, and accounting information systems.', 'start_date' => '2021', 'end_date' => '2025', 'current' => '0', 'period' => '2021 – 2025'], 10],
        ['education', 'Malawi School Certificate of Education', ['organisation' => 'Malawi secondary education', 'field_of_study' => 'Commerce and quantitative studies', 'details' => 'Built a foundation in mathematics, business studies, communication, and disciplined independent learning.', 'start_date' => '2017', 'end_date' => '2020', 'current' => '0', 'period' => '2017 – 2020'], 20],
        ['skills', 'Bookkeeping and reconciliations', ['category' => 'Accounting operations', 'proficiency' => 'Working knowledge', 'details' => 'Cashbook maintenance, bank and supplier reconciliations, document organisation, and review-ready schedules.'], 10],
        ['skills', 'Microsoft Excel for finance', ['category' => 'Analysis and reporting', 'proficiency' => 'Working knowledge', 'details' => 'Formula-driven schedules, lookup functions, pivot-ready data, variance analysis, and clear reporting layouts.'], 20],
        ['skills', 'Financial statement analysis', ['category' => 'Financial analysis', 'proficiency' => 'Working knowledge', 'details' => 'Basic ratio analysis, trend review, and concise narrative explanations for non-finance stakeholders.'], 30],
        ['skills', 'Budgeting and cash-flow planning', ['category' => 'Planning', 'proficiency' => 'Working knowledge', 'details' => 'Simple forecasts, budget-versus-actual tracking, and practical cost-control recommendations.'], 40],
        ['skills', 'Professional communication', ['category' => 'Workplace effectiveness', 'proficiency' => 'Strong foundation', 'details' => 'Clear written updates, ethical handling of financial information, and collaborative follow-through.'], 50],
        ['news', 'Graduate portfolio published', ['details' => 'A complete graduate accounting portfolio is now available, including finance projects, skills, education, and practical work samples.', 'link' => '', 'published_date' => 'October 2026', 'period' => 'October 2026'], 10],
        ['news', 'Building practical accounting capability', ['details' => 'John is focusing on the habits that make early-career accountants dependable: clean records, timely reconciliations, and clear explanations.', 'link' => '', 'published_date' => 'September 2026', 'period' => 'September 2026'], 20],
        ['partnerships', 'Career mentorship programme', ['details' => 'Open to structured mentorship, internship, and graduate-trainee opportunities that build sound accounting judgement.', 'link' => '', 'start_date' => '2026', 'end_date' => '', 'period' => '2026 – Present'], 10],
        ['partnerships', 'Small-business support network', ['details' => 'Interested in collaborating with entrepreneurs and advisors who value organised records, cash-flow discipline, and sustainable growth.', 'link' => '', 'start_date' => '2026', 'end_date' => '', 'period' => '2026 – Present'], 20],
        ['cv', 'Graduate accounting profile', ['details' => 'Portfolio summary covering education, graduate-level accounting capability, selected work, and readiness for finance opportunities.', 'year' => '2026', 'period' => '2026'], 10],
    ];
    $database->prepare('DELETE FROM portfolio_advanced_items WHERE website_id = :website_id')->execute(['website_id' => $websiteId]);
    $saveItem = $database->prepare(
        'INSERT INTO portfolio_advanced_items
            (website_id, user_id, section_key, title, payload, sort_order, is_enabled)
         VALUES (:website_id, :user_id, :section_key, :title, :payload, :sort_order, 1)'
    );
    foreach ($items as [$section, $title, $payload, $sortOrder]) {
        $saveItem->execute([
            'website_id' => $websiteId,
            'user_id' => $adminId,
            'section_key' => $section,
            'title' => $title,
            'payload' => encodePayload($payload),
            'sort_order' => $sortOrder,
        ]);
    }

    $projects = [
        ['Budget and Variance Tracker', 'Built a clear monthly budget-versus-actual workbook that highlights material differences, asks the right follow-up questions, and supports concise management updates.', 'assets/portfolio-template/demo/john-doe/ledger.jpg'],
        ['Cash-flow Forecast Workbook', 'Prepared a simple rolling cash forecast using expected collections, operating costs, and planned commitments to make near-term liquidity visible.', 'assets/portfolio-template/demo/john-doe/analysis.jpg'],
        ['SME Bookkeeping Clean-up', 'Organised source documents, mapped recurring transactions, and prepared a reconciliation checklist for a sample small-business bookkeeping case.', 'assets/portfolio-template/demo/john-doe/ledger.jpg'],
        ['Financial Statement Ratio Review', 'Analysed liquidity, profitability, and efficiency ratios from sample financial statements, then translated the findings into plain-language recommendations.', 'assets/portfolio-template/demo/john-doe/analysis.jpg'],
        ['Inventory and Internal-control Review', 'Documented a simple stock-control flow, identified common risk points, and proposed practical approval and record-keeping improvements.', 'assets/portfolio-template/demo/john-doe/team.jpg'],
        ['Tax Readiness Calendar', 'Created a sample compliance calendar and document checklist to help a growing business prepare records before routine tax and reporting deadlines.', 'assets/portfolio-template/demo/john-doe/team.jpg'],
    ];
    $database->prepare('DELETE FROM portfolio_projects WHERE portfolio_id = :portfolio_id')->execute(['portfolio_id' => $portfolioId]);
    $saveProject = $database->prepare(
        'INSERT INTO portfolio_projects (portfolio_id, title, summary, project_url, image_path, sort_order)
         VALUES (:portfolio_id, :title, :summary, :project_url, :image_path, :sort_order)'
    );
    foreach ($projects as $index => [$title, $summary, $imagePath]) {
        $saveProject->execute([
            'portfolio_id' => $portfolioId,
            'title' => $title,
            'summary' => $summary,
            'project_url' => '',
            'image_path' => $imagePath,
            'sort_order' => ($index + 1) * 10,
        ]);
    }

    $defaultSetting = $database->prepare(
        "INSERT INTO platform_settings (setting_key, setting_value, setting_group)
         VALUES ('portfolio_template_default_classic', :portfolio_id, 'portfolio_templates')
         ON DUPLICATE KEY UPDATE
            setting_value = VALUES(setting_value), setting_group = VALUES(setting_group), updated_at = CURRENT_TIMESTAMP"
    );
    $defaultSetting->execute(['portfolio_id' => (string) $portfolioId]);

    echo "Default admin portfolio ready: website #{$websiteId}, portfolio #{$portfolioId}, owner #{$adminId}.\n";
} catch (Throwable $exception) {
    fwrite(STDERR, "[default-portfolio] " . $exception->getMessage() . PHP_EOL);
    exit(1);
}
