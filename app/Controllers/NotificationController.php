<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Csrf;
use App\Services\AuthService;
use App\Services\NotificationManager;
use App\Models\Notification as NotificationModel;

/**
 * Handles lightweight AJAX polling and notification status endpoints.
 */
class NotificationController extends Controller
{
    public function poll(): void
    {
        if (!AuthService::check()) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        $userId = AuthService::id();
        $manager = new NotificationManager($this->config);
        $latestNotifications = $manager->latestForUser($userId, 10);
        $unreadCount = $manager->unreadCount($userId);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'latest' => $latestNotifications,
            'unreadCount' => $unreadCount,
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Mark a notification as read for the current user.
     */
    public function markRead(): void
    {
        if (!AuthService::check()) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        $userId = AuthService::id();

        // Accept JSON payload or form POST
        $payload = $_POST;
        if (empty($payload)) {
            $raw = file_get_contents('php://input');
            $data = json_decode($raw, true);
            if (is_array($data)) {
                $payload = $data;
            }
        }

        $csrfToken = (string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? $payload['_csrf'] ?? '');
        if (!Csrf::validate($csrfToken)) {
            http_response_code(419);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'Invalid CSRF token']);
            return;
        }

        $id = isset($payload['id']) ? (int) $payload['id'] : 0;
        if ($id <= 0) {
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'Invalid notification id']);
            return;
        }

        $model = new NotificationModel();
        // Mark as read only for the owning user
        $model->markAsReadByUser($id, $userId);

        $unreadCount = $model->unreadCountForUser($userId);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => true, 'unreadCount' => $unreadCount]);
    }

    /** Permanently clear notifications belonging to the signed-in user. */
    public function clearAll(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        if (!AuthService::check()) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        $payload = $_POST;
        if (empty($payload)) {
            $decoded = json_decode((string) file_get_contents('php://input'), true);
            $payload = is_array($decoded) ? $decoded : [];
        }

        $csrfToken = (string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? $payload['_csrf'] ?? '');
        if (!Csrf::validate($csrfToken)) {
            http_response_code(419);
            echo json_encode(['error' => 'Invalid CSRF token']);
            return;
        }

        $model = new NotificationModel();
        $deleted = $model->clearAllForUser(AuthService::id());
        echo json_encode(['success' => true, 'deleted' => $deleted, 'unreadCount' => 0]);
    }

    /** Mark all notifications belonging to the signed-in user as read. */
    public function markAllRead(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        if (!AuthService::check()) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        $payload = $_POST;
        if (empty($payload)) {
            $decoded = json_decode((string) file_get_contents('php://input'), true);
            $payload = is_array($decoded) ? $decoded : [];
        }

        $csrfToken = (string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? $payload['_csrf'] ?? '');
        if (!Csrf::validate($csrfToken)) {
            http_response_code(419);
            echo json_encode(['error' => 'Invalid CSRF token']);
            return;
        }

        $model = new NotificationModel();
        $model->markAllAsReadForUser(AuthService::id());
        echo json_encode([
            'success' => true,
            'unreadCount' => $model->unreadCountForUser(AuthService::id()),
        ]);
    }
}
