<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

/** Minimal JSON client so provider SDKs and their secrets do not reach the UI. */
final class PortfolioAiHttpClient
{
    /**
     * @param array<string, string> $headers
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function post(string $url, array $headers, array $payload): array
    {
        if (!function_exists('curl_init')) {
            throw new RuntimeException('AI requests require the PHP cURL extension.');
        }

        $json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            throw new RuntimeException('Could not prepare the AI request.');
        }

        $curl = curl_init($url);
        if ($curl === false) {
            throw new RuntimeException('Could not start the AI request.');
        }

        $requestHeaders = ['Content-Type: application/json', 'Accept: application/json'];
        foreach ($headers as $name => $value) {
            $requestHeaders[] = $name . ': ' . $value;
        }

        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $json,
            CURLOPT_HTTPHEADER => $requestHeaders,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT => 38,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
            CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTPS,
            CURLOPT_FOLLOWLOCATION => false,
        ]);
        $body = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $curlError = curl_errno($curl);
        $curlFailed = $body === false;
        curl_close($curl);

        if ($curlFailed && in_array($curlError, [CURLE_OPERATION_TIMEDOUT, CURLE_COULDNT_CONNECT], true)) {
            throw new RuntimeException('The AI provider took too long to respond. Check your internet connection and provider availability, then try again.');
        }
        if ($curlFailed || $status < 200 || $status >= 300) {
            // Provider bodies may contain sensitive metadata; expose only safe status-based guidance.
            if (in_array($status, [401, 403], true)) {
                throw new RuntimeException('The AI provider rejected the API key or access to this model. Check the selected provider, key, and model permissions.');
            }
            if ($status === 429) {
                throw new RuntimeException('The AI provider free-tier quota or rate limit has been reached. Wait for the limit to reset, then try again.');
            }
            if (in_array($status, [400, 404, 422], true)) {
                throw new RuntimeException('The AI provider rejected this request. Check that the configured model exists and supports the requested response format.');
            }
            if ($status >= 500 && $status <= 599) {
                throw new RuntimeException('The AI provider is temporarily unavailable. Try again later.');
            }
            throw new RuntimeException('The AI provider could not complete this request. Check the provider configuration and usage limits.');
        }

        $decoded = json_decode((string) $body, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('The AI provider returned an unreadable response.');
        }

        return $decoded;
    }
}
