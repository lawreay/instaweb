<?php

namespace App\Core;

use App\Core\Session;
use App\Models\Setting;
use App\Services\AuthService;

/**
 * Minimal HTTP router for dispatching configured controller actions.
 */
class Router
{
    /**
     * Resolve the current URI and run the matching controller action.
     */
    public static function dispatch(string $uri, string $method = 'GET', array $routes = [], array $config = []): void
    {
        $path = '/' . trim((string) parse_url($uri, PHP_URL_PATH), '/');
        $path = $path === '//' ? '/' : $path;
        $method = strtoupper($method);
        $basePath = '/' . trim((string) parse_url($config['base_url'] ?? '', PHP_URL_PATH), '/');

        if ($basePath !== '/' && str_starts_with($path, $basePath)) {
            $path = substr($path, strlen($basePath)) ?: '/';
        }

        $settings = (new Setting())->all();
        $maintenanceEnabled = ($settings['maintenance_mode'] ?? '0') === '1';
        $isAdminArea = str_starts_with($path, '/admin');
        $isMaintenancePreview = $path === '/admin/settings/maintenance-preview';
        $isMaintenancePage = $path === '/maintenance';
        $allowMaintenanceBypass = false;

        if ($maintenanceEnabled && !$isAdminArea && !$isMaintenancePreview) {
            $bypassToken = trim((string) ($_GET['token'] ?? ''));
            $storedToken = trim((string) ($settings['maintenance_bypass_token'] ?? ''));

            if ($bypassToken !== '' && $storedToken !== '' && hash_equals($storedToken, $bypassToken)) {
                Session::put('maintenance_bypass', '1');
                $allowMaintenanceBypass = true;
            }

            if (Session::get('maintenance_bypass') === '1') {
                $allowMaintenanceBypass = true;
            }

            if (!$allowMaintenanceBypass) {
                echo View::render('maintenance', array_merge([
                    'title' => $settings['maintenance_page_title'] ?? 'We’ll be back soon',
                    'maintenance_page_title' => $settings['maintenance_page_title'] ?? 'We’ll be back soon',
                    'maintenance_status_label' => $settings['maintenance_status_label'] ?? 'Maintenance in progress',
                    'maintenance_message' => $settings['maintenance_message'] ?? 'Our website is currently undergoing scheduled maintenance. We appreciate your patience and expect to be back online shortly.',
                    'maintenance_return_at' => $settings['maintenance_return_at'] ?? '',
                    'maintenance_preview' => false,
                ], ['app' => $config]), 'layouts/maintenance');
                return;
            }
        }

        if (isset($routes[$method][$path])) {
            [$controllerClass, $action] = $routes[$method][$path];
            $controller = new $controllerClass($config, $_REQUEST);
            $controller->{$action}();
            return;
        }

        if ($method === 'GET' && preg_match('#^/p/([a-z0-9-]{1,80})/?$#i', $path, $matches)) {
            $controller = new \App\Controllers\PortfolioPublicController(
                $config,
                array_merge($_REQUEST, ['slug' => $matches[1]])
            );
            $controller->show();
            return;
        }

        http_response_code(404);
        echo 'Route not found: ' . htmlspecialchars($path, ENT_QUOTES, 'UTF-8');
    }
}
