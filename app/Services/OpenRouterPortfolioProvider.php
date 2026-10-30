<?php

declare(strict_types=1);

namespace App\\Services;

use RuntimeException;

/** OpenRouter-compatible Chat Completions adapter for the free-model router and BYOK models. */
final class OpenRouterPortfolioProvider implements PortfolioAiProvider
{
    private string $model;
    private PortfolioAiHttpClient $http;

    public function __construct(string $model, ?PortfolioAiHttpClient $http = null)
    {
        $this->model = $model;
        $this->http = $http ?? new PortfolioAiHttpClient();
    }

    public function generate(string $systemInstruction, string $prompt, array $schema, string $apiKey): array
    {
        if ($this->model === '') {
            throw new RuntimeException('The OpenRouter model is not configured.');
        }

        $response = $this->http->post(
            'https://openrouter.ai/api/v1/chat/completions',
            [
                'Authorization' => 'Bearer ' . $apiKey,
                'HTTP-Referer' => (string) env('APP_URL', 'https://instaweb.mw'),
                'X-Title' => (string) env('APP_NAME', 'InstaWeb'),
            ],
            [
                'model' => $this->model,
                'messages' => [
                    ['role' => 'system', 'content' => $systemInstruction],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.35,
                'max_tokens' => 8192,
                'provider' => ['require_parameters' => true],
                'response_format' => [
                    'type' => 'json_schema',
                    'json_schema' => [
                        'name' => 'portfolio_enhancement',
                        'strict' => true,
                        'schema' => $schema,
                    ],
                ],
            ]
        );

        $choice = $response['choices'][0] ?? [];
        $message = is_array($choice) ? ($choice['message'] ?? []) : [];
        $content = is_array($message) ? ($message['content'] ?? '') : '';
        if (is_array($content)) {
            $content = implode('', array_map(
                static fn (mixed $part): string => is_array($part) && is_string($part['text'] ?? null) ? $part['text'] : '',
                $content
            ));
        }
        $proposal = is_string($content) ? json_decode(trim($content), true) : null;
        if (!is_array($proposal)) {
            $finishReason = (string) ($choice['finish_reason'] ?? '');
            if ($finishReason === 'length') {
                throw new RuntimeException('The AI response exceeded the output limit. Try enhancing fewer portfolio entries at once.');
            }
            throw new RuntimeException('The OpenRouter response was incomplete or not valid JSON. Try again, or enhance fewer portfolio entries at once.');
        }

        return $proposal;
    }
}
