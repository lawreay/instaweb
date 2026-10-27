<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

/** Administrator-managed catalogue for portfolio layouts. */
class PortfolioTemplate
{
    public const BUILT_INS = [
        'classic' => 'Classic — clear and adaptable',
        'editorial' => 'Editorial — print-inspired showcase',
        '3d' => '3D Motion — immersive and technical',
        'minimal' => 'Minimal — calm, content-first',
        'noir' => 'Noir — bold dark portfolio',
        'aurora' => 'Aurora Motion — luminous and animated',
        'studio' => 'Studio Canvas — warm creative practice',
        'orbit' => 'Orbit 3D — mission-control interface',
        'prism' => 'Prism — colourful and expressive',
        'archive' => 'Archive — paper-led catalogue',
        'ledger' => 'Ledger — precise professional portfolio',
        'graduate' => 'Graduate Path — confident early-career portfolio',
    ];

    /** Built-in layouts available to Free accounts; all other layouts require Premium. */
    public const FREE_BUILT_INS = ['classic', 'minimal', 'graduate'];
    public const PREMIUM_BUILT_INS = ['editorial', '3d', 'noir', 'aurora', 'studio', 'orbit', 'prism', 'archive', 'ledger'];

    private PDO $database;

    public function __construct(?PDO $database = null)
    {
        $this->database = $database ?? Database::connection();
    }

    /** Return built-in and custom templates, including suspended records. */
    public function all(): array
    {
        $statuses = $this->builtInStatuses();
        $templates = [];
        foreach (self::BUILT_INS as $key => $label) {
            $templates[] = [
                'id' => null,
                'template_key' => $key,
                'display_name' => $label,
                'description' => 'Built-in portfolio layout',
                'render_engine' => 'built_in',
                'status' => $statuses[$key] ?? 'active',
                'is_builtin' => true,
                'html_code' => '',
                'css_code' => '',
                'react_code' => '',
            ];
        }

        $statement = $this->database->query(
            'SELECT id, template_key, display_name, description, render_engine, html_code, css_code, react_code, status, created_by, created_at, updated_at
             FROM portfolio_template_definitions
             ORDER BY created_at DESC, id DESC'
        );
        foreach ($statement->fetchAll() as $template) {
            $template['is_builtin'] = false;
            $templates[] = $template;
        }

        return $templates;
    }

    /** Return active templates as key => label for user-facing selectors. */
    public function selectable(): array
    {
        $templates = [];
        foreach ($this->all() as $template) {
            if (($template['status'] ?? '') === 'active') {
                $templates[(string) $template['template_key']] = (string) $template['display_name'];
            }
        }

        return $templates;
    }

    public function isSelectable(string $templateKey): bool
    {
        return array_key_exists($templateKey, $this->selectable());
    }

    /** Built-ins use the explicit tier lists; administrator-created templates are Premium by default. */
    public function isPremiumTemplate(string $templateKey): bool
    {
        if (array_key_exists($templateKey, self::BUILT_INS)) {
            return in_array($templateKey, self::PREMIUM_BUILT_INS, true);
        }

        return true;
    }

    /**
     * Return one custom template for rendering.
     *
     * A suspended template is hidden from the editor, but portfolios which
     * already use it must continue to render rather than unexpectedly falling
     * back to a different design.
     */
    public function customByKey(string $templateKey): ?array
    {
        $statement = $this->database->prepare(
            "SELECT id, template_key, display_name, description, render_engine, html_code, css_code, react_code
             FROM portfolio_template_definitions
             WHERE template_key = :template_key
             LIMIT 1"
        );
        $statement->execute(['template_key' => $templateKey]);
        $template = $statement->fetch();

        return $template ?: null;
    }

    public function findCustom(int $id): ?array
    {
        $statement = $this->database->prepare(
            'SELECT id, template_key, display_name, description, render_engine, html_code, css_code, react_code, status, created_by, created_at, updated_at
             FROM portfolio_template_definitions WHERE id = :id LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $template = $statement->fetch();

        return $template ?: null;
    }

    public function create(array $data, int $adminId): int
    {
        $statement = $this->database->prepare(
            'INSERT INTO portfolio_template_definitions
                (template_key, display_name, description, render_engine, html_code, css_code, react_code, status, created_by)
             VALUES (:template_key, :display_name, :description, :render_engine, :html_code, :css_code, :react_code, :status, :created_by)'
        );
        $statement->execute([
            'template_key' => (string) $data['template_key'],
            'display_name' => (string) $data['display_name'],
            'description' => (string) $data['description'],
            'render_engine' => (string) ($data['render_engine'] ?? 'html'),
            'html_code' => (string) $data['html_code'],
            'css_code' => (string) $data['css_code'],
            'react_code' => (string) ($data['react_code'] ?? ''),
            'status' => (string) ($data['status'] ?? 'active'),
            'created_by' => $adminId,
        ]);

        return (int) $this->database->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $statement = $this->database->prepare(
            'UPDATE portfolio_template_definitions
             SET display_name = :display_name, description = :description,
                 render_engine = :render_engine, html_code = :html_code,
                 css_code = :css_code, react_code = :react_code, updated_at = CURRENT_TIMESTAMP
             WHERE id = :id'
        );
        $statement->execute([
            'display_name' => (string) $data['display_name'],
            'description' => (string) $data['description'],
            'render_engine' => (string) ($data['render_engine'] ?? 'html'),
            'html_code' => (string) $data['html_code'],
            'css_code' => (string) $data['css_code'],
            'react_code' => (string) ($data['react_code'] ?? ''),
            'id' => $id,
        ]);

        return $statement->rowCount() === 1;
    }

    public function setCustomStatus(int $id, string $status): bool
    {
        $statement = $this->database->prepare(
            'UPDATE portfolio_template_definitions SET status = :status, updated_at = CURRENT_TIMESTAMP WHERE id = :id'
        );
        $statement->execute(['id' => $id, 'status' => $status]);

        return $statement->rowCount() === 1;
    }

    public function deleteCustom(int $id): bool
    {
        $statement = $this->database->prepare('DELETE FROM portfolio_template_definitions WHERE id = :id');
        $statement->execute(['id' => $id]);

        return $statement->rowCount() === 1;
    }

    public function isInUse(string $templateKey): bool
    {
        $statement = $this->database->prepare('SELECT 1 FROM portfolios WHERE template_key = :template_key LIMIT 1');
        $statement->execute(['template_key' => $templateKey]);

        return (bool) $statement->fetchColumn();
    }

    public function setBuiltInStatus(string $templateKey, string $status): void
    {
        if (!array_key_exists($templateKey, self::BUILT_INS)) {
            throw new \InvalidArgumentException('Unknown built-in template.');
        }

        $statement = $this->database->prepare(
            'INSERT INTO platform_settings (setting_key, setting_value, setting_group)
             VALUES (:setting_key, :setting_value, :setting_group)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), setting_group = VALUES(setting_group), updated_at = CURRENT_TIMESTAMP'
        );
        $statement->execute([
            'setting_key' => 'portfolio_template_status_' . $templateKey,
            'setting_value' => $status,
            'setting_group' => 'portfolio_templates',
        ]);
    }

    public function isBuiltIn(string $templateKey): bool
    {
        return array_key_exists($templateKey, self::BUILT_INS);
    }

    private function builtInStatuses(): array
    {
        $statement = $this->database->query(
            "SELECT setting_key, setting_value FROM platform_settings
             WHERE setting_key LIKE 'portfolio_template_status_%'"
        );
        $statuses = [];
        foreach ($statement->fetchAll() as $setting) {
            $key = substr((string) $setting['setting_key'], strlen('portfolio_template_status_'));
            if (array_key_exists($key, self::BUILT_INS) && in_array($setting['setting_value'], ['active', 'suspended'], true)) {
                $statuses[$key] = $setting['setting_value'];
            }
        }

        return $statuses;
    }
}
