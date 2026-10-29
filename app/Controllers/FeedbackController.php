<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Csrf;
use App\Models\FeedbackSubmission;
use App\Models\Setting;
use App\Services\AuthService;
use App\Services\MailerService;

class FeedbackController extends Controller
{
    public function submit(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        if (!AuthService::check()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Please sign in before sending feedback.']);
            return;
        }

        $payload = $this->request();
        $raw = json_decode((string) file_get_contents('php://input'), true);
        if (is_array($raw)) {
            $payload = array_merge($payload, $raw);
        }

        if (!Csrf::validate((string) ($payload['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''))) {
            http_response_code(419);
            echo json_encode(['success' => false, 'error' => 'Your session expired. Refresh the page and try again.']);
            return;
        }

        // Quiet bot trap. Do not disclose the field's purpose to the visitor.
        if (trim((string) ($payload['website'] ?? '')) !== '') {
            echo json_encode(['success' => true, 'message' => 'Thank you for your feedback.']);
            return;
        }

        $type = trim((string) ($payload['type'] ?? 'problem'));
        $subject = trim((string) ($payload['subject'] ?? ''));
        $message = trim((string) ($payload['message'] ?? ''));
        $email = strtolower(trim((string) ($payload['email'] ?? '')));
        $pageUrl = trim((string) ($payload['page_url'] ?? ''));

        if (!in_array($type, ['problem', 'feature'], true)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'error' => 'Choose a problem report or feature request.']);
            return;
        }
        if (mb_strlen($subject) < 4 || mb_strlen($subject) > 150) {
            http_response_code(422);
            echo json_encode(['success' => false, 'error' => 'Subject must be between 4 and 150 characters.']);
            return;
        }
        if (mb_strlen($message) < 10 || mb_strlen($message) > 5000) {
            http_response_code(422);
            echo json_encode(['success' => false, 'error' => 'Description must be between 10 and 5,000 characters.']);
            return;
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'error' => 'Enter a valid email address.']);
            return;
        }

        $pageUrl = mb_substr($pageUrl, 0, 500);
        $settings = (new Setting())->all();
        $userEmail = $email !== '' ? $email : (string) ($settings['unused'] ?? '');
        $name = trim((string) ($payload['name'] ?? ''));
        $userId = (int) AuthService::id();

        try {
            $id = (new FeedbackSubmission())->create([
                'user_id' => $userId > 0 ? $userId : null,
                'submitter_name' => mb_substr($name !== '' ? $name : 'InstaWeb user', 0, 150),
                'submitter_email' => $userEmail,
                'type' => $type,
                'subject' => $subject,
                'message' => $message,
                'page_url' => $pageUrl,
                'email_status' => 'pending',
            ]);
        } catch (\Throwable $exception) {
            error_log('Feedback submission save failed: ' . $exception->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'We could not save your feedback. Please try again.']);
            return;
        }

        $mailConfig = require APP_PATH . '/Config/mail.php';
        $recipient = trim((string) ($mailConfig['admin_login_notification_address'] ?? ''));
        if ($recipient === '') {
            $recipient = trim((string) ($settings['admin_login_notification_email'] ?? $settings['site_email'] ?? ''));
        }
        $emailSent = false;
        if (filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            $safe = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            $kind = $type === 'feature' ? 'Feature request' : 'Problem report';
            $html = '<h2>New InstaWeb ' . $safe($kind) . ' #' . $id . '</h2>'
                . '<p><strong>From:</strong> ' . $safe($name !== '' ? $name : 'InstaWeb user') . '</p>'
                . '<p><strong>Contact email:</strong> ' . $safe($userEmail !== '' ? $userEmail : 'Not provided') . '</p>'
                . '<p><strong>Subject:</strong> ' . $safe($subject) . '</p>'
                . '<p><strong>Description:</strong><br>' . nl2br($safe($message)) . '</p>'
                . '<p><strong>Page:</strong> ' . $safe($pageUrl !== '' ? $pageUrl : 'Not provided') . '</p>'
                . '<p>Review and manage this submission in the InstaWeb admin dashboard.</p>';
            try {
                $mailer = new MailerService($mailConfig, $this->config);
                $emailSent = $mailer->send($recipient, 'InstaWeb Admin', '[InstaWeb] ' . $kind . ': ' . $subject, $html);
            } catch (\Throwable $exception) {
                error_log('Feedback email notification failed: ' . $exception->getMessage());
            }
        }

        echo json_encode([
            'success' => true,
            'id' => $id,
            'emailSent' => $emailSent,
            'message' => $emailSent
                ? 'Submitted successfully. Your feedback was saved and emailed to the site administrator.'
                : 'Submitted successfully and saved to the admin dashboard. Email delivery is not configured or failed.',
        ]);
    }
}
