<?php
namespace App\Services;

use App\Models\BusinessWebsiteTheme;

/**
 * Small code-defined theme catalogue for managed Business websites.
 * Themes alter presentation only; structured content remains untouched.
 */
class BusinessWebsiteThemeService
{
    private const THEMES = [
        'evergreen' => [
            'name' => 'Evergreen',
            'description' => 'Warm, approachable and professional.',
            'class' => 'theme-evergreen',
            'css' => ':root{--ink:#17231f;--muted:#66736d;--green:#176b50;--dark:#10251f;--paper:#fbfaf7;--line:#e6e9e4}.hero{background:radial-gradient(ellipse at 85% 20%,#e2ecdf,transparent 38%)}.service{border-radius:12px}.about{background:#e8f1eb}',
        ],
        'midnight' => [
            'name' => 'Midnight',
            'description' => 'A bold, high-contrast look for modern brands.',
            'class' => 'theme-midnight',
            'css' => ':root{--ink:#f4f6fb;--muted:#b5bfd0;--green:#8b9cff;--dark:#090d19;--paper:#111827;--line:#303b50}body{background:var(--paper);color:var(--ink)}header{background:#111827ed;border-color:var(--line)}.links a{color:#c7d0e2}.hero{background:radial-gradient(ellipse at 85% 20%,#29365d,transparent 42%)}.hero p,.muted{color:var(--muted)}.service{background:#1b2638;border-color:var(--line);border-radius:4px}.service p{color:var(--muted)}.about{background:#1a2538}.contact{background:#070b15}.footer{border-color:var(--line);color:var(--muted)}.button{background:#6477e8;border-radius:4px}',
        ],
    ];

    public function all(): array
    {
        return self::THEMES;
    }

    public function normalize(string $themeKey): string
    {
        return array_key_exists($themeKey, self::THEMES) ? $themeKey : 'evergreen';
    }

    public function current(int $websiteId, int $userId): string
    {
        return $this->normalize((new BusinessWebsiteTheme())->findForOwner($websiteId, $userId));
    }

    public function save(int $websiteId, int $userId, string $themeKey): void
    {
        $themeKey = $this->normalize($themeKey);
        (new BusinessWebsiteTheme())->saveForOwner($websiteId, $userId, $themeKey);
    }

    public function render(array $content, string $themeKey, array $branding = []): string
    {
        $theme = self::THEMES[$this->normalize($themeKey)];
        $html = (new BusinessWebsiteService())->render($content, $branding);
        $style = '<style>' . $theme['css'] . '</style>';
        return str_replace('</head>', $style . '</head>', $html);
    }

    public function writeDraft(int $userId, string $slug, array $content, string $themeKey, array $branding = []): void
    {
        $path = (new ProjectStorageService())->ensureProjectDirectory($userId, $slug);
        if (file_put_contents($path . DIRECTORY_SEPARATOR . 'index.html', $this->render($content, $themeKey, $branding), LOCK_EX) === false) {
            throw new \RuntimeException('The Business website draft could not be updated.');
        }
    }
}
