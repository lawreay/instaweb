<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Session;
use App\Models\AdvancedPortfolio;
use App\Models\Portfolio;
use App\Models\Website;
use App\Services\AuthService;

/**
 * Advanced presentation editor. Content remains website-scoped and database-backed.
 */
class AdvancedPortfolioController extends Controller
{
    private const SETTINGS = [
        'accent_color' => ['#f97316', 7],
        'background_color' => ['#ffffff', 7],
        'text_color' => ['#111827', 7],
        'font_family' => ['system', 20],
        'hero_style' => ['centered', 20],
        'show_projects' => ['1', 1],
        'show_contact' => ['1', 1],
    ];

    public function edit(): void
    {
        [$userId, $website, $portfolio] = $this->ownedPortfolio();
        $model = new AdvancedPortfolio();
        $settings = array_merge(
            array_map(static fn (array $definition): string => $definition[0], self::SETTINGS),
            $model->settings((int) $website['id'], $userId)
        );

        $this->view('portfolio/advanced', [
            'title' => 'Advanced Portfolio Editor',
            'website' => $website,
            'portfolio' => $portfolio,
            'settings' => $settings,
            'items' => $model->items((int) $website['id'], $userId),
            'active' => 'websites',
        ], 'layouts/dashboard');
    }

    public function save(): void
    {
        [$userId, $website] = $this->ownedPortfolio();

        if (!Csrf::validate($this->input('_csrf'))) {
            Session::flash('error', 'Your session expired. Please try again.');
            $this->redirectTo('/dashboard/websites/portfolio/advanced?id=' . (int) $website['id']);
        }

        $settings = [];
        foreach (self::SETTINGS as $key => [$default, $maxLength]) {
            $value = trim((string) $this->input($key, $default));
            if (strlen($value) > $maxLength) {
                Session::flash('error', 'One or more design settings are too long.');
                $this->redirectTo('/dashboard/websites/portfolio/advanced?id=' . (int) $website['id']);
            }
            $settings[$key] = $value;
        }

        foreach (['accent_color', 'background_color', 'text_color'] as $key) {
            if (!preg_match('/^#[0-9a-fA-F]{6}$/', $settings[$key])) {
                Session::flash('error', 'Choose valid six-digit hex colours.');
                $this->redirectTo('/dashboard/websites/portfolio/advanced?id=' . (int) $website['id']);
            }
        }

        if (!in_array($settings['font_family'], ['system', 'serif', 'sans-serif', 'mono'], true)
            || !in_array($settings['hero_style'], ['centered', 'split', 'minimal'], true)
            || !in_array($settings['show_projects'], ['0', '1'], true)
            || !in_array($settings['show_contact'], ['0', '1'], true)) {
            Session::flash('error', 'Choose one of the available design options.');
            $this->redirectTo('/dashboard/websites/portfolio/advanced?id=' . (int) $website['id']);
        }

        (new AdvancedPortfolio())->saveSettings((int) $website['id'], $userId, $settings);
        Session::flash('success', 'Advanced portfolio design settings saved.');
        $this->redirectTo('/dashboard/websites/portfolio/advanced?id=' . (int) $website['id']);
    }

    private function ownedPortfolio(): array
    {
        if (!AuthService::check()) {
            $this->redirectTo('/login');
        }

        $userId = (int) AuthService::id();
        $websiteId = (int) $this->input('id', $this->input('website_id', 0));
        $website = (new Website())->findForUser($websiteId, $userId);

        if (!$website || ($website['template_key'] ?? '') !== 'portfolio') {
            Session::flash('error', 'Portfolio website not found.');
            $this->redirectTo('/dashboard/websites');
        }

        $portfolio = (new Portfolio())->findForWebsiteOwner($websiteId, $userId);
        if (!$portfolio) {
            Session::flash('error', 'Portfolio data is not initialized for this website.');
            $this->redirectTo('/dashboard/websites/show?id=' . $websiteId);
        }

        return [$userId, $website, $portfolio];
    }
}
