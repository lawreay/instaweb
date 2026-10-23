<?php
/**
 * Application route definitions.
 */

use App\Controllers\HomeController;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\LegalController;
use App\Controllers\MarketingController;
use App\Controllers\AdminController;
use App\Controllers\WebsiteController;
use App\Controllers\PortfolioController;
use App\Controllers\FileManagerController;
use App\Controllers\EditorController;
use App\Controllers\PreviewController;
use App\Controllers\PublishController;
use App\Controllers\NotificationController;
use App\Controllers\PaymentController;
use App\Controllers\SitemapController;

return [
    'GET' => [
        '/' => [MarketingController::class, 'home'],
        '/features' => [MarketingController::class, 'features'],
        '/pricing' => [MarketingController::class, 'pricing'],
        '/about' => [MarketingController::class, 'about'],
        '/contact' => [MarketingController::class, 'contact'],
        '/faq' => [MarketingController::class, 'faq'],
        '/login' => [AuthController::class, 'showLogin'],
        '/register' => [AuthController::class, 'showRegister'],
        '/forgot-password' => [AuthController::class, 'showForgotPassword'],
        '/reset-password' => [AuthController::class, 'showResetPassword'],
        '/two-factor' => [AuthController::class, 'showTwoFactor'],
        '/verify-email' => [AuthController::class, 'verifyEmail'],
        '/dashboard' => [DashboardController::class, 'index'],
        '/dashboard/notifications' => [DashboardController::class, 'notifications'],
        '/dashboard/websites' => [WebsiteController::class, 'index'],
        '/dashboard/websites/create' => [WebsiteController::class, 'create'],
        '/dashboard/websites/show' => [WebsiteController::class, 'show'],
        '/dashboard/websites/edit' => [WebsiteController::class, 'edit'],
        '/dashboard/websites/portfolio' => [PortfolioController::class, 'edit'],
        '/dashboard/websites/files' => [FileManagerController::class, 'index'],
        '/dashboard/websites/files/download' => [FileManagerController::class, 'download'],
        '/dashboard/websites/files/export' => [FileManagerController::class, 'exportZip'],
        '/dashboard/websites/editor' => [EditorController::class, 'edit'],
        '/dashboard/websites/editor/file' => [EditorController::class, 'openFile'],
        '/dashboard/websites/preview' => [PreviewController::class, 'show'],
        '/dashboard/websites/preview/file' => [PreviewController::class, 'file'],
        '/profile' => [DashboardController::class, 'profile'],
        '/profile/confirm-email-change' => [DashboardController::class, 'confirmEmailChange'],
        '/profile/confirm-deactivation' => [DashboardController::class, 'confirmDeactivation'],
        '/payments/paychangu/callback' => [PaymentController::class, 'callback'],
        '/payments/paychangu/return' => [PaymentController::class, 'return'],
        '/admin' => [AdminController::class, 'dashboard'],
        '/admin/media' => [AdminController::class, 'media'],
        '/admin/users' => [AdminController::class, 'users'],
        '/admin/users/edit' => [AdminController::class, 'editUser'],
        '/admin/websites' => [AdminController::class, 'websites'],
        '/admin/payments' => [AdminController::class, 'payments'],
        '/admin/reports' => [AdminController::class, 'reports'],
        '/admin/notifications' => [AdminController::class, 'notifications'],
        '/admin/settings' => [AdminController::class, 'settings'],
        '/admin/settings/maintenance-preview' => [AdminController::class, 'previewMaintenance'],
        '/admin/legal' => [LegalController::class, 'adminIndex'],
        '/admin/legal/edit' => [LegalController::class, 'editPolicy'],
        '/admin/legal/publish' => [LegalController::class, 'publishVersion'],
        '/privacy' => [LegalController::class, 'privacy'],
        '/terms' => [LegalController::class, 'terms'],
        '/cookie-policy' => [LegalController::class, 'cookie'],
        '/acceptable-use' => [LegalController::class, 'acceptableUse'],
        '/copyright' => [LegalController::class, 'copyright'],
        '/sitemap.xml' => [SitemapController::class, 'show'],
        '/legal/accept' => [LegalController::class, 'showAcceptancePage'],
        '/api/notifications/poll' => [NotificationController::class, 'poll'],
         '/contact' => [MarketingController::class, 'contact'],
    ],
    'POST' => [
        '/login' => [AuthController::class, 'login'],
        '/register' => [AuthController::class, 'register'],
        '/forgot-password' => [AuthController::class, 'sendResetLink'],
        '/reset-password' => [AuthController::class, 'resetPassword'],
        '/two-factor' => [AuthController::class, 'verifyTwoFactor'],
        '/logout' => [AuthController::class, 'logout'],
        '/dashboard/websites' => [WebsiteController::class, 'store'],
        '/dashboard/websites/update' => [WebsiteController::class, 'update'],
        '/dashboard/websites/portfolio/save' => [PortfolioController::class, 'save'],
        '/dashboard/websites/portfolio/projects/add' => [PortfolioController::class, 'addProject'],
        '/dashboard/websites/portfolio/projects/delete' => [PortfolioController::class, 'deleteProject'],
        '/dashboard/websites/delete' => [WebsiteController::class, 'delete'],
        '/dashboard/websites/files/upload' => [FileManagerController::class, 'upload'],
        '/dashboard/websites/files/import' => [FileManagerController::class, 'importZip'],
        '/dashboard/websites/files/create-file' => [FileManagerController::class, 'createFile'],
        '/dashboard/websites/files/create-folder' => [FileManagerController::class, 'createFolder'],
        '/dashboard/websites/files/move' => [FileManagerController::class, 'move'],
        '/dashboard/websites/files/copy' => [FileManagerController::class, 'copy'],
        '/dashboard/websites/files/delete' => [FileManagerController::class, 'delete'],
        '/dashboard/websites/editor/save' => [EditorController::class, 'save'],
        '/dashboard/websites/publish' => [PublishController::class, 'publish'],
        '/dashboard/websites/unpublish' => [PublishController::class, 'unpublish'],
        '/admin/legal/save' => [LegalController::class, 'savePolicy'],
        '/legal/accept' => [LegalController::class, 'accept'],
        '/profile/update' => [DashboardController::class, 'updateProfile'],
        '/profile/change-password' => [DashboardController::class, 'changePassword'],
        '/profile/change-email' => [DashboardController::class, 'changeEmail'],
        '/profile/deactivate' => [DashboardController::class, 'requestDeactivation'],
        '/payments/paychangu/premium' => [PaymentController::class, 'startPremiumCheckout'],
        '/admin/settings' => [AdminController::class, 'saveSettings'],
        '/admin/settings/test-email' => [AdminController::class, 'sendTestEmail'],
        '/admin/media/upload' => [AdminController::class, 'uploadMedia'],
        '/admin/media/delete' => [AdminController::class, 'deleteMedia'],
        '/admin/media/rename' => [AdminController::class, 'renameMedia'],
        '/admin/users/update' => [AdminController::class, 'updateUser'],
        '/admin/users/change-plan' => [AdminController::class, 'changeUserPlan'],
        '/admin/users/delete' => [AdminController::class, 'deleteUser'],
        '/admin/users/reset-role' => [AdminController::class, 'resetRole'],
        '/admin/notifications/broadcast' => [AdminController::class, 'broadcastNotifications'],
        '/api/notifications/mark-read' => [NotificationController::class, 'markRead'],
        '/admin/websites/suspend' => [AdminController::class, 'suspendWebsite'],
        '/admin/websites/unsuspend' => [AdminController::class, 'unsuspendWebsite'],
        '/admin/websites/delete' => [AdminController::class, 'deleteWebsite'],
         '/contact' => [MarketingController::class, 'sendContact'],
    ],
];
