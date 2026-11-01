<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

/** OpenAI-compatible chat completions adapter for OpenRouter. */
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

        $config = require dirname(__DIR__) . '/Config/ai.php';
        $baseUrl = rtrim((string) ($config['providers']['openrouter']['base_url'] ?? 'https://openrouter.ai/api/v1'), '/');
        if (!str_starts_with($baseUrl, 'https://')) {
            throw new RuntimeException('OpenRouter endpoint must use HTTPS.');
        }

        $response = $this->http->post($baseUrl . '/chat/completions', [
            'Authorization' => 'Bearer ' . $apiKey,
            'HTTP-Referer' => (string) env('APP_URL', ''),
            'X-Title' => 'Instaweb',
        ], [
            'model' => $this->model,
            'messages' => [
                ['role' => 'system', 'content' => $systemInstruction],
                ['role' => 'user', 'content' => $prompt],
            ],
            'response_format' => [
                'type' => 'json_schema',
                'json_schema' => [
                    'name' => 'portfolio_enhancement',
                    'strict' => true,
                    'schema' => $schema,
                ],
            ],
            'max_tokens' => 8192,
            'temperature' => 0.35,
        ]);

        $text = trim((string) ($response['choices'][0]['message']['content'] ?? ''));
        $proposal = json_decode($text, true);
        if (!is_array($proposal)) {
            throw new RuntimeException('OpenRouter returned an incomplete or invalid structured response. Check that the selected model supports JSON schema output.');
        }

        return $proposal;
    }
}
