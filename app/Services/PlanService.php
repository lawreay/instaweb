<?php

namespace App\Services;

use App\Models\Setting;

/**
 * Resolves hosting plan names, limits, benefits, and display metadata from settings.
 */
class PlanService
{
    private array $settings;

    public function __construct(?array $settings = null)
    {
        $this->settings = $settings ?? (new Setting())->all();
    }

    /**
     * Return the configured display data for Free and Premium plans.
     */
    public function plans(): array
    {
        return [
            'free' => [
                'name' => $this->text('free_plan_name', 'Free Plan'),
                'price' => $this->text('free_plan_price', 'MWK 0'),
                'website_limit' => $this->positiveInt('free_plan_website_limit', 1),
                'storage_mb' => $this->positiveInt('free_storage_limit_mb', 100),
                'hosting_days' => $this->positiveInt('free_plan_hosting_days', 30),
                'benefits' => $this->benefits('free_plan_benefits', [
                    '1 website',
                    '100MB storage',
                    sprintf('%d days hosting', $this->freeHostingDays()),
                    'Instaweb subdomain',
                    'Create and preview a portfolio',
                    'Selected free portfolio templates',
                    'Published portfolios display Powered by InstaWeb',
                ]),
            ],
            'premium' => [
                'name' => $this->text('premium_plan_name', 'Premium Plan'),
                'price' => $this->text('premium_plan_price', 'MWK 5,000/mo'),
                'storage_mb' => $this->premiumStorageLimitMb(),
                'benefits' => $this->benefits('premium_plan_benefits', [
                    'Unlimited websites',
                    'Custom domains',
                    'No expiry',
                    'Analytics and priority support',
                    'Access to all premium portfolio templates',
                    'Remove the Powered by InstaWeb watermark',
                    'Export portfolios to PDF',
                ]),
            ],
        ];
    }

    /**
     * Return the Premium plan storage limit in megabytes.
     */
    public function premiumStorageLimitMb(): int
    {
        return $this->positiveInt('premium_storage_limit_mb', 0);
    }

    /**
     * Return the Premium plan storage limit in bytes.
     */
    public function premiumStorageBytes(): int
    {
        return $this->premiumStorageLimitMb() * BYTES_PER_MB;
    }

    /**
     * Return one plan definition.
     */
    public function plan(string $plan): array
    {
        $plans = $this->plans();

        return $plans[$plan] ?? $plans['free'];
    }

    /**
     * Return the Free plan storage limit in bytes.
     */
    public function freeStorageBytes(): int
    {
        return $this->positiveInt('free_storage_limit_mb', 100) * BYTES_PER_MB;
    }

    /**
     * Return the Free plan active website limit.
     */
    public function freeWebsiteLimit(): int
    {
        return $this->positiveInt('free_plan_website_limit', 1);
    }

    /**
     * Return the Free plan hosting duration in days.
     */
    public function freeHostingDays(): int
    {
        return $this->positiveInt('free_plan_hosting_days', 30);
    }

    /**
     * Return the CSS chip class used for plan badges.
     */
    public function badgeClass(string $plan): string
    {
        return $plan === 'premium' ? 'premium' : 'free';
    }

    private function text(string $key, string $default): string
    {
        $value = trim((string) ($this->settings[$key] ?? ''));

        return $value !== '' ? $value : $default;
    }

    private function positiveInt(string $key, int $default): int
    {
        $value = (int) ($this->settings[$key] ?? $default);

        return $value > 0 ? $value : $default;
    }

    private function benefits(string $key, array $defaults): array
    {
        $raw = trim((string) ($this->settings[$key] ?? ''));
        if ($raw === '') {
            return $defaults;
        }

        $items = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $raw) ?: [])));

        return $items !== [] ? $items : $defaults;
    }
}
