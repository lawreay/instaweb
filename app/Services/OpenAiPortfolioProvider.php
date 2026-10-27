<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

/** OpenAI Responses API adapter using strict JSON schema output. */
final class OpenAiPortfolioProvider implements PortfolioAiProvider
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
            throw new RuntimeException('The OpenAI model is not configured.');
        }

        $response = $this->http->post('https://api.openai.com/v1/responses', [
            'Authorization' => 'Bearer ' . $apiKey,
        ], [
            'model' => $this->model,
            'input' => [
                ['role' => 'system', 'content' => [['type' => 'input_text', 'text' => $systemInstruction]]],
                ['role' => 'user', 'content' => [['type' => 'input_text', 'text' => $prompt]]],
            ],
            'text' => [
                'format' => [
                    'type' => 'json_schema',
                    'name' => 'portfolio_enhancement',
                    'strict' => true,
                    'schema' => $schema,
                ],
            ],
            'max_output_tokens' => 1800,
        ]);

        $text = trim((string) ($response['output_text'] ?? ''));
        if ($text === '') {
            foreach (($response['output'] ?? []) as $output) {
                if (!is_array($output)) {
                    continue;
                }
                foreach (($output['content'] ?? []) as $content) {
                    if (is_array($content) && is_string($content['text'] ?? null)) {
                        $text .= $content['text'];
                    }
                }
            }
        }

        return $this->decodeProposal($text);
    }

    /** @return array<string, mixed> */
    private function decodeProposal(string $text): array
    {
        $proposal = json_decode($text, true);
        if (!is_array($proposal)) {
            throw new RuntimeException('The AI response was not in the expected format.');
        }

        return $proposal;
    }
}
