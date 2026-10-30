<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Session;
use App\Models\ClientDashboardRepository;
use App\Models\Payment;
use App\Models\Setting;
use App\Services\AuthService;
use App\Services\NotificationManager;
use App\Services\PayChanguService;

/**
 * Starts and verifies Premium plan payments through PayChangu.
 */
class PaymentController extends Controller
{
    /**
     * Create a PayChangu checkout session for the signed-in user's Premium upgrade.
     */
    public function startPremiumCheckout(): void
    {
        if (!AuthService::check()) {
            Session::flash('warning', 'Please log in before upgrading to Premium.');
            $this->redirectTo('/login');
        }

        if (!Csrf::validate($this->input('_csrf'))) {
            Session::flash('error', 'Your session expired. Please try again.');
            $this->redirectTo('/dashboard');
        }

        $settings = (new Setting())->all();
        if (($settings['premium_plan_enabled'] ?? '1') !== '1') {
            Session::flash('warning', 'Premium upgrades are currently unavailable.');
            $this->redirectTo('/dashboard');
        }

        $secretKey = trim((string) ($settings['paychangu_secret_key'] ?? ''));
        if ($secretKey === '') {
            Session::flash('error', 'PayChangu is not configured yet. Contact support.');
            $this->redirectTo('/dashboard');
        }

        $checkoutMode = (string) ($settings['payment_checkout_mode'] ?? 'standard');
        if (!in_array($checkoutMode, ['standard', 'inline', 'dropin'], true)) {
            $checkoutMode = 'standard';
        }
        $publicKey = trim((string) ($settings['paychangu_public_key'] ?? ''));
        if ($checkoutMode !== 'standard' && $publicKey === '') {
            $this->jsonResponse(['ok' => false, 'message' => 'Inline and Drop-in checkout require the PayChangu public key in Admin Settings.'], 422);
            return;
        }

        $userId = (int) AuthService::id();
        $user = (new ClientDashboardRepository())->user($userId);
        if (!$user) {
            Session::flash('error', 'Unable to load your account.');
            $this->redirectTo('/dashboard');
        }

        $amount = $this->premiumAmount($settings);
        $currency = $this->premiumCurrency($settings);
        $txRef = 'THM-' . $userId . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(4));
        $baseUrl = rtrim((string) ($this->config['base_url'] ?? ''), '/');

        (new Payment())->createPending($userId, $amount, $currency, $txRef);

        $checkoutPayload = [
            'public_key' => $publicKey,
            'checkout_mode' => $checkoutMode,
            'tx_ref' => $txRef,
            'amount' => $amount,
            'currency' => $currency,
            'callback_url' => $baseUrl . '/payments/paychangu/callback',
            'return_url' => $baseUrl . '/payments/paychangu/return',
            'email' => (string) $user['email'],
            'first_name' => $this->firstName((string) $user['fullname']),
            'last_name' => $this->lastName((string) $user['fullname']),
            'customization' => ['title' => 'Instaweb Premium Plan', 'description' => 'One month Premium hosting upgrade'],
            'meta' => ['user_id' => $userId, 'plan' => 'premium'],
        ];

        if ($checkoutMode !== 'standard') {
            $this->jsonResponse(['ok' => true, 'checkout' => $checkoutPayload]);
            return;
        }

        try {
            $response = (new PayChanguService($secretKey))->initiatePayment([
                'amount' => (string) $amount,
                'currency' => $currency,
                'email' => (string) $user['email'],
                'first_name' => $this->firstName((string) $user['fullname']),
                'last_name' => $this->lastName((string) $user['fullname']),
                'callback_url' => $baseUrl . '/payments/paychangu/callback',
                'return_url' => $baseUrl . '/payments/paychangu/return',
                'tx_ref' => $txRef,
                'customization' => [
                    'title' => 'Instaweb Premium Plan',
                    'description' => 'One month Premium hosting upgrade',
                ],
                'meta' => [
                    'user_id' => $userId,
                    'plan' => 'premium',
                ],
            ]);
        } catch (\Throwable $exception) {
            Session::flash('error', 'Unable to start payment: ' . $exception->getMessage());
            $this->redirectTo('/dashboard');
        }

        $checkoutUrl = $response['data']['checkout_url'] ?? null;
        if (!is_string($checkoutUrl) || !filter_var($checkoutUrl, FILTER_VALIDATE_URL)) {
            Session::flash('error', 'PayChangu did not return a checkout link.');
            $this->redirectTo('/dashboard');
        }

        if (str_contains((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json')) {
            $this->jsonResponse(['ok' => true, 'checkout' => ['checkout_mode' => 'standard', 'checkout_url' => $checkoutUrl]]);
            return;
        }
        $this->redirect($checkoutUrl);
    }

    private function jsonResponse(array $payload, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    }

    /**
     * Handle PayChangu's successful redirect and verify the payment server-side.
     */
    public function callback(): void
    {
        $this->verifyRedirect(true);
    }

    /**
     * Handle PayChangu cancellation or failed redirects.
     */
    public function return(): void
    {
        $this->verifyRedirect(false);
    }

    /**
     * Verify a PayChangu redirect before granting Premium access.
     */
    private function verifyRedirect(bool $expectedSuccess): void
    {
        $txRef = trim((string) $this->input('tx_ref', ''));
        if ($txRef === '') {
            Session::flash('error', 'Missing payment reference.');
            $this->redirectTo('/dashboard');
        }

        $paymentModel = new Payment();
        $payment = $paymentModel->findByReference($txRef);
        if (!$payment) {
            Session::flash('error', 'Payment record not found.');
            $this->redirectTo('/dashboard');
        }

        if (($payment['payment_status'] ?? '') === 'paid') {
            Session::flash('success', 'Your Premium plan is already active.');
            $this->redirectTo('/dashboard');
        }

        $secretKey = trim((string) ((new Setting())->all()['paychangu_secret_key'] ?? ''));
        try {
            $verification = (new PayChanguService($secretKey))->verifyPayment($txRef);
        } catch (\Throwable $exception) {
            Session::flash('warning', 'Payment verification is pending: ' . $exception->getMessage());
            $this->redirectTo('/dashboard');
        }

        $verified = $this->isVerifiedPayment($verification, $payment);
        if (!$verified) {
            $paymentModel->markFailed((int) $payment['id'], $verification);
            Session::flash($expectedSuccess ? 'error' : 'warning', 'Payment was not completed. No upgrade was applied.');
            $this->redirectTo('/dashboard');
        }

        $providerReference = (string) ($verification['data']['reference'] ?? $txRef);
        $paymentModel->markPaid((int) $payment['id'], $providerReference, $verification);
        $paymentModel->activatePremiumSubscription((int) $payment['user_id'], (float) $payment['amount']);
        Session::put('user_role', $this->roleAfterUpgrade(Session::get('user_role')));

        $message = 'Your PayChangu payment was verified and Premium hosting is now active.';
        (new NotificationManager($this->config))->sendUserNotificationWithEmail(
            (int) $payment['user_id'],
            'Premium plan activated',
            $message,
            'Your Instaweb Premium plan is active',
            'subscription-update',
            ['message' => $message],
            [
                'category' => 'subscription',
                'icon' => 'crown',
                'target_url' => '/dashboard',
            ]
        );

        Session::flash('success', 'Payment verified. Your Premium plan is active.');
        $this->redirectTo('/dashboard');
    }

    /**
     * Determine the effective role to keep after a successful upgrade.
     */
    private function roleAfterUpgrade(?string $currentRole): string
    {
        if (in_array($currentRole, ['admin', 'super_admin', 'moderator', 'premium'], true)) {
            return (string) $currentRole;
        }

        return 'premium';
    }

    /**
     * Validate PayChangu's verification payload against the local payment.
     */
    private function isVerifiedPayment(array $verification, array $payment): bool
    {
        $data = $verification['data'] ?? [];
        if (!is_array($data)) {
            return false;
        }

        return ($verification['status'] ?? '') === 'success'
            && ($data['status'] ?? '') === 'success'
            && hash_equals((string) $payment['tx_ref'], (string) ($data['tx_ref'] ?? ''))
            && strtoupper((string) $payment['currency']) === strtoupper((string) ($data['currency'] ?? ''))
            && (float) ($data['amount'] ?? 0) >= (float) $payment['amount'];
    }

    /**
     * Read the numeric Premium amount from admin settings.
     */
    private function premiumAmount(array $settings): float
    {
        $configured = (string) ($settings['premium_plan_amount'] ?? '');
        if ($configured === '') {
            $configured = preg_replace('/[^0-9.]/', '', (string) ($settings['premium_plan_price'] ?? '5000')) ?: '5000';
        }

        return max(1, (float) $configured);
    }

    /**
     * Read and normalize the Premium payment currency from settings.
     */
    private function premiumCurrency(array $settings): string
    {
        $currency = strtoupper(trim((string) ($settings['paychangu_currency'] ?? 'MWK')));

        return in_array($currency, ['MWK', 'USD'], true) ? $currency : 'MWK';
    }

    private function firstName(string $fullName): string
    {
        $parts = preg_split('/\s+/', trim($fullName)) ?: [];

        return $parts[0] ?? 'Customer';
    }

    private function lastName(string $fullName): string
    {
        $parts = preg_split('/\s+/', trim($fullName)) ?: [];

        return count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : 'Instaweb';
    }
}
