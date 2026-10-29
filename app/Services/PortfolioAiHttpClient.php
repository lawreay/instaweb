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
            // Do not forward provider bodies or secrets to the browser.
            throw new RuntimeException('The AI provider rejected or could not complete this request. Check the selected provider, API key, model, and usage limits.');
        }

        $decoded = json_decode((string) $body, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('The AI provider returned an unreadable response.');
        }

        return $decoded;
    }
}
