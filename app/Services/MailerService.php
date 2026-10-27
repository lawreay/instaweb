<?php

namespace App\Services;

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

/**
 * Sends application email through the configured SMTP provider.
 */
class MailerService
{
    public function __construct(
        private array $mailConfig,
        private array $appConfig
    ) {
    }

    /**
     * Send an HTML email with a plain text fallback.
     */
    public function send(
        string $toEmail,
        string $toName,
        string $subject,
        string $htmlBody,
        string $textBody = '',
        ?string $replyToEmail = null,
        ?string $replyToName = null
    ): bool
    {
        $mail = $this->buildMailer();
        $mail->addAddress($toEmail, $toName);
        if ($replyToEmail !== null && filter_var($replyToEmail, FILTER_VALIDATE_EMAIL)) {
            $mail->addReplyTo($replyToEmail, $replyToName ?? '');
        }
        $mail->Subject = $subject;
        $mail->isHTML(true);
        $mail->Body = $htmlBody;
        $mail->AltBody = $textBody !== '' ? $textBody : strip_tags($htmlBody);

        return $mail->send();
    }

    /**
     * Send an account email verification message.
     */
    public function sendVerificationEmail(string $toEmail, string $toName, string $token): bool
    {
        $verifyUrl = rtrim($this->appConfig['base_url'], '/') . '/verify-email?token=' . urlencode($token);
        $safeName = htmlspecialchars($toName, ENT_QUOTES, 'UTF-8');
        $safeUrl = htmlspecialchars($verifyUrl, ENT_QUOTES, 'UTF-8');
        $appName = htmlspecialchars($this->appConfig['name'] ?? 'Instaweb', ENT_QUOTES, 'UTF-8');

        $html = <<<HTML
<h2>Verify your {$appName} account</h2>
<p>Hello {$safeName},</p>
<p>Please confirm your email address to finish setting up your account.</p>
<p><a href="{$safeUrl}">Verify email address</a></p>
<p>If you did not create this account, you can ignore this message.</p>
HTML;

        $text = "Hello {$toName},\n\nVerify your email address using this link:\n{$verifyUrl}";

        return $this->send($toEmail, $toName, 'Verify your email address', $html, $text);
    }

    /**
     * Send a password reset message.
     */
    public function sendPasswordResetEmail(string $toEmail, string $toName, string $token): bool
    {
        $resetUrl = rtrim($this->appConfig['base_url'], '/') . '/reset-password?token=' . urlencode($token);
        $safeName = htmlspecialchars($toName, ENT_QUOTES, 'UTF-8');
        $safeUrl = htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8');
        $appName = htmlspecialchars($this->appConfig['name'] ?? 'Instaweb', ENT_QUOTES, 'UTF-8');

        $html = <<<HTML
<h2>Reset your {$appName} password</h2>
<p>Hello {$safeName},</p>
<p>Use the secure link below to choose a new password.</p>
<p><a href="{$safeUrl}">Reset password</a></p>
<p>If you did not request this, you can ignore this message.</p>
HTML;

        $text = "Hello {$toName},\n\nReset your password using this link:\n{$resetUrl}";

        return $this->send($toEmail, $toName, 'Reset your password', $html, $text);
    }

    /**
     * Send a two-factor authentication code to the user.
     */
    public function sendTwoFactorCodeEmail(string $toEmail, string $toName, string $code): bool
    {
        $safeName = htmlspecialchars($toName, ENT_QUOTES, 'UTF-8');
        $appName = htmlspecialchars($this->appConfig['name'] ?? 'Instaweb', ENT_QUOTES, 'UTF-8');

        $html = <<<HTML
<h2>{$appName} two-factor verification</h2>
<p>Hello {$safeName},</p>
<p>Your verification code is <strong>{$code}</strong>.</p>
<p>This code expires in 10 minutes.</p>
HTML;

        $text = "Hello {$toName},\n\nYour verification code is: {$code}\n\nThis code expires in 10 minutes.";
        $subject = "{$appName} two-factor verification";

        return $this->send($toEmail, $toName, $subject, $html, $text);
    }

    /**
     * Send a security alert when an administrator signs in.
     */
    public function sendAdminLoginAlert(string $adminName, string $ipAddress): bool
    {
        $recipient = $this->mailConfig['admin_login_notification_address'] ?? '';

        if ($recipient === '') {
            return false;
        }

        $message = $this->mailConfig['admin_login_notification_message'];
        $safeAdmin = htmlspecialchars($adminName, ENT_QUOTES, 'UTF-8');
        $safeIp = htmlspecialchars($ipAddress, ENT_QUOTES, 'UTF-8');
        $safeMessage = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));

        $html = <<<HTML
<h2>Administrator login alert</h2>
<p>{$safeMessage}</p>
<p><strong>Admin:</strong> {$safeAdmin}</p>
<p><strong>IP address:</strong> {$safeIp}</p>
HTML;

        return $this->send($recipient, 'Instaweb Admin', 'Administrator login alert', $html);
    }

    /**
     * Create a PHPMailer instance using application SMTP settings.
     */
    private function buildMailer(): PHPMailer
    {
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = $this->mailConfig['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $this->mailConfig['username'];
            $mail->Password = $this->mailConfig['password'];
            $mail->Port = (int) $this->mailConfig['port'];

            if (($this->mailConfig['encryption'] ?? '') !== '') {
                $mail->SMTPSecure = $this->mailConfig['encryption'];
            }

            $mail->CharSet = PHPMailer::CHARSET_UTF8;
            $mail->setFrom($this->mailConfig['from_address'], $this->mailConfig['from_name']);
        } catch (Exception $exception) {
            throw $exception;
        }

        return $mail;
    }
}
