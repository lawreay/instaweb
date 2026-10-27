<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Session;
use App\Models\Portfolio;
use App\Models\Setting;
use App\Models\Website;
use App\Services\MailerService;

/** Sends a visitor's message only to the contact address of a published portfolio. */
class PortfolioContactController extends Controller
{
    private const WINDOW_SECONDS = 600;
    private const MAX_MESSAGES_PER_WINDOW = 3;

    public function send(): void
    {
        if (!Csrf::validate((string) $this->input('_csrf', ''))) {
            $this->respond(419, false, 'Your session expired. Refresh the portfolio and try again.');
            return;
        }

        // A bot that fills this field receives a harmless success response but
        // never reaches the mailer.
        if (trim((string) $this->input('website', '')) !== '') {
            $this->respond(200, true, 'Thanks — your message has been sent.');
            return;
        }

        $slug = trim((string) $this->input('portfolio_slug', ''));
        $name = trim((string) $this->input('name', ''));
        $email = trim((string) $this->input('email', ''));
        $phone = trim((string) $this->input('phone', ''));
        $message = trim((string) $this->input('message', ''));

        if (!preg_match('/^[a-z0-9-]{1,80}$/i', $slug)
            || mb_strlen($name) < 2 || mb_strlen($name) > 150 || preg_match('/[\x00-\x1F\x7F]/', $name)
            || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 254
            || !preg_match('/^[0-9+().\s-]{7,30}$/', $phone)
            || mb_strlen($message) < 2 || mb_strlen($message) > 5000) {
            $this->respond(422, false, 'Please enter a name, valid email address, phone number, and message.');
            return;
        }

        $website = (new Website())->findPublishedPortfolioBySlug($slug);
        $portfolio = $website ? (new Portfolio())->findPublishedForWebsite((int) $website['id']) : null;
        $recipient = trim((string) ($portfolio['email'] ?? ''));
        if (!$portfolio || !filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            $this->respond(404, false, 'This portfolio is not accepting messages right now.');
            return;
        }

        $settings = (new Setting())->all();
        if (($settings['smtp_enabled'] ?? '0') !== '1') {
            $this->respond(503, false, 'Messages are temporarily unavailable. Please try again later.');
            return;
        }

        if (!$this->withinRateLimit((int) $portfolio['id'])) {
            $this->respond(429, false, 'Please wait a few minutes before sending another message.');
            return;
        }

        $portfolioName = trim((string) ($portfolio['display_name'] ?? 'this portfolio'));
        $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        $safeEmail = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
        $safePhone = htmlspecialchars($phone, ENT_QUOTES, 'UTF-8');
        $safeMessage = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));
        $subject = 'New portfolio message from ' . $name;
        $html = '<h2>New portfolio enquiry</h2>'
            . '<p><strong>Portfolio:</strong> ' . htmlspecialchars($portfolioName, ENT_QUOTES, 'UTF-8') . '</p>'
            . '<p><strong>Name:</strong> ' . $safeName . '</p>'
            . '<p><strong>Email:</strong> ' . $safeEmail . '</p>'
            . '<p><strong>Phone:</strong> ' . $safePhone . '</p>'
            . '<p><strong>Message:</strong><br>' . $safeMessage . '</p>';
        $text = "Portfolio: {$portfolioName}\nName: {$name}\nEmail: {$email}\nPhone: {$phone}\n\nMessage:\n{$message}";

        $mailer = new MailerService([
            'host' => (string) ($settings['mail_host'] ?? ''),
            'port' => (int) ($settings['mail_port'] ?? 587),
            'username' => (string) ($settings['mail_username'] ?? ''),
            'password' => (string) ($settings['mail_password'] ?? ''),
            'encryption' => (string) ($settings['mail_encryption'] ?? ''),
            'from_address' => (string) ($settings['mail_from_address'] ?? ''),
            'from_name' => (string) ($settings['mail_from_name'] ?? ''),
        ], $this->config);

        try {
            $sent = $mailer->send($recipient, $portfolioName, $subject, $html, $text, $email, $name);
        } catch (\Throwable) {
            $sent = false;
        }

        $this->respond(
            $sent ? 200 : 503,
            $sent,
            $sent ? 'Thanks — your message has been sent.' : 'We could not send your message right now. Please try again later.'
        );
    }

    /** Limit mail delivery attempts per portfolio and anonymised visitor IP. */
    private function withinRateLimit(int $portfolioId): bool
    {
        $now = time();
        $cutoff = $now - self::WINDOW_SECONDS;
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        $secret = (string) ($this->config['app_key'] ?? $this->config['base_url'] ?? 'instaweb');
        $ipHash = hash('sha256', $secret . '|' . $ip);

        try {
            $database = Database::connection();
            $database->beginTransaction();
            $database->prepare('DELETE FROM portfolio_contact_rate_limits WHERE window_started_at < :cutoff')
                ->execute(['cutoff' => $cutoff]);
            $statement = $database->prepare(
                'INSERT INTO portfolio_contact_rate_limits (portfolio_id, ip_hash, window_started_at, attempts)
                 VALUES (:portfolio_id, :ip_hash, :now, 1)
                 ON DUPLICATE KEY UPDATE
                    attempts = IF(window_started_at < :reset_cutoff, 1, LEAST(attempts + 1, 4)),
                    window_started_at = IF(window_started_at < :window_cutoff, VALUES(window_started_at), window_started_at)'
            );
            $statement->execute([
                'portfolio_id' => $portfolioId,
                'ip_hash' => $ipHash,
                'now' => $now,
                'reset_cutoff' => $cutoff,
                'window_cutoff' => $cutoff,
            ]);
            $count = $database->prepare(
                'SELECT attempts FROM portfolio_contact_rate_limits
                 WHERE portfolio_id = :portfolio_id AND ip_hash = :ip_hash LIMIT 1'
            );
            $count->execute(['portfolio_id' => $portfolioId, 'ip_hash' => $ipHash]);
            $allowed = (int) $count->fetchColumn() <= self::MAX_MESSAGES_PER_WINDOW;
            $database->commit();

            return $allowed;
        } catch (\Throwable) {
            if (isset($database) && $database->inTransaction()) {
                $database->rollBack();
            }

            // A short per-session fallback keeps email protected until the
            // versioned migration is applied or a database operation recovers.
            $key = 'portfolio_contact_rate_' . $portfolioId;
            $attempts = Session::get($key, []);
            $attempts = is_array($attempts)
                ? array_values(array_filter($attempts, static fn ($timestamp): bool => is_int($timestamp) && $timestamp >= $cutoff))
                : [];
            $attempts[] = $now;
            Session::put($key, $attempts);

            return count($attempts) <= self::MAX_MESSAGES_PER_WINDOW;
        }
    }

    private function respond(int $status, bool $ok, string $message): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=UTF-8');
        header('Cache-Control: no-store');
        echo json_encode(['ok' => $ok, 'message' => $message], JSON_UNESCAPED_SLASHES);
    }
}
