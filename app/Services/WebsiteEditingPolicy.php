<?php

namespace App\Services;

/**
 * Central policy for raw project-file access.
 *
 * Only projects explicitly created from the Blank starter may expose the
 * generic file manager and code editor. All other template types are managed
 * through their own editing workflow.
 */
final class WebsiteEditingPolicy
{
    public static function isBlankProject(array $website): bool
    {
        return strtolower(trim((string) ($website['template_key'] ?? ''))) === 'blank';
    }

    public static function canUseCodeEditor(array $website): bool
    {
        return self::isBlankProject($website);
    }

    public static function canUseFileManager(array $website): bool
    {
        return self::isBlankProject($website);
    }

    public static function isTemplateManaged(array $website): bool
    {
        return !self::isBlankProject($website);
    }
}
