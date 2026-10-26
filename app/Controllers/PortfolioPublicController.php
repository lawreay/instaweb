<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\View;
use App\Models\Portfolio;
use App\Models\AdvancedPortfolio;
use App\Models\Website;

/**
 * Public, database-backed portfolio rendering.
 */
class PortfolioPublicController extends Controller
{
    public function show(): void
    {
        $slug = trim((string) $this->input('slug', ''));
        if ($slug === '' || !preg_match('/^[a-z0-9-]{1,80}$/i', $slug)) {
            http_response_code(404);
            echo 'Portfolio not found.';
            return;
        }

        $website = (new Website())->findPublishedPortfolioBySlug($slug);
        if (!$website) {
            http_response_code(404);
            echo 'Portfolio not found.';
            return;
        }

        $portfolioModel = new Portfolio();
        $portfolio = $portfolioModel->findPublishedForWebsite((int) $website['id']);
        if (!$portfolio) {
            http_response_code(404);
            echo 'Portfolio not found.';
            return;
        }

        $advancedModel = new AdvancedPortfolio();
        header('Content-Type: text/html; charset=UTF-8');
        echo View::render('portfolio/public', [
            'app' => $this->config,
            'website' => $website,
            'portfolio' => $portfolio,
            'projects' => $portfolioModel->projects((int) $portfolio['id']),
            'advancedSettings' => $advancedModel->settings((int) $website['id'], (int) $website['user_id']),
            'advancedItems' => $advancedModel->visibleItems((int) $website['id'], (int) $website['user_id']),
            'noirSectionImages' => $advancedModel->noirSectionImages((int) $website['id'], (int) $website['user_id']),
        ], '');
    }
}
