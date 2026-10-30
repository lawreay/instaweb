<?php

declare(strict_types=1);

namespace App\\Models;

use App\\Core\\Database;
use App\\Services\\CredentialCipher;
use PDO;

/**
 * Stores and reads platform settings from the database.
 * Sensitive integration credentials are transparently encrypted at rest.
 */
class Setting
{
    private PDO $database;

    /** @var string[] */
    private const SECRET_KEYS = [
        'paychangu_secret_key',
        'ai_openai_api_key',
        'ai_gemini_api_key',
        'ai_openrouter_api_key',
        'mail_password',
        'smtp_password',
    ];

    public function __construct(?PDO $database = null)
    {
        $this->database = $database ?? Database::connection();
    }

    /**
     * Return all saved settings as key/value pairs. Legacy plaintext secrets
     * are transparently upgraded in place when APP_ENCRYPTION_KEY is configured.
     */
    public function all(): array
    {
        $statement = $this->database->query('SELECT setting_key, setting_value FROM platform_settings');
        $settings = [];

        foreach ($statement->fetchAll() as $row) {
            $key = (string) $row['setting_key'];
            $value = (string) $row['setting_value'];

            if (in_array($key, self::SECRET_KEYS, true)) {
                if (CredentialCipher::isEncrypted($value)) {
                    $value = CredentialCipher::decrypt($value);
                } elseif ($value !== '' && CredentialCipher::keyIsConfigured()) {
                    $encrypted = CredentialCipher::encrypt($value);
                    $upgrade = $this->database->prepare(
                        'UPDATE platform_settings SET setting_value = :value, updated_at = CURRENT_TIMESTAMP WHERE setting_key = :key'
                    );
                    $upgrade->execute(['value' => $encrypted, 'key' => $key]);
                }
            }

            $settings[$key] = $value;
        }

        return $settings;
    }

    /** Save multiple settings using an upsert. */
    public function saveMany(array $settings): void
    {
        $statement = $this->database->prepare(
            'INSERT INTO platform_settings (setting_key, setting_value, setting_group)
             VALUES (:setting_key, :setting_value, :setting_group)
             ON DUPLICATE KEY UPDATE
                setting_value = VALUES(setting_value),
                setting_group = VALUES(setting_group),
                updated_at = CURRENT_TIMESTAMP'
        );

        foreach ($settings as $key => $meta) {
            $value = (string) ($meta['value'] ?? '');
            if (in_array((string) $key, self::SECRET_KEYS, true) && $value !== '') {
                $value = CredentialCipher::encrypt($value);
            }

            $statement->execute([
                'setting_key' => $key,
                'setting_value' => $value,
                'setting_group' => $meta['group'] ?? 'general',
            ]);
        }
    }
}
