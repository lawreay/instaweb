<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

/** Gemini generateContent adapter with a JSON response schema. */
final class GeminiPortfolioProvider implements PortfolioAiProvider
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
            throw new RuntimeException('The Gemini model is not configured.');
        }

        $response = $this->http->post(
            'https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode($this->model) . ':generateContent',
            ['x-goog-api-key' => $apiKey],
            [
                'systemInstruction' => ['parts' => [['text' => $systemInstruction]]],
                'contents' => [['role' => 'user', 'parts' => [['text' => $prompt]]]],
                'generationConfig' => [
                    'temperature' => 0.35,
                    'maxOutputTokens' => 1800,
                    'responseMimeType' => 'application/json',
                    'responseSchema' => $this->geminiSchema($schema),
                ],
            ]
        );

        $text = '';
        foreach (($response['candidates'][0]['content']['parts'] ?? []) as $part) {
            if (is_array($part) && is_string($part['text'] ?? null)) {
                $text .= $part['text'];
            }
        }
        $proposal = json_decode(trim($text), true);
        if (!is_array($proposal)) {
            throw new RuntimeException('The AI response was not in the expected format.');
        }

        return $proposal;
    }

    /**
     * Gemini accepts an OpenAPI-like schema, whose type values are uppercase.
     *
     * @param array<string, mixed> $schema
     * @return array<string, mixed>
     */
    private function geminiSchema(array $schema): array
    {
        $converted = [];
        foreach ($schema as $key => $value) {
            if ($key === 'additionalProperties') {
                continue;
            }
            if ($key === 'type' && is_string($value)) {
                $converted[$key] = strtoupper($value);
                continue;
            }
            if (is_array($value)) {
                if ($value === [] || array_keys($value) === range(0, count($value) - 1)) {
                    $converted[$key] = array_map(
                        fn (mixed $item): mixed => is_array($item) ? $this->geminiSchema($item) : $item,
                        $value
                    );
                } else {
                    $converted[$key] = $this->geminiSchema($value);
                }
                continue;
            }
            $converted[$key] = $value;
        }

        return $converted;
    }
}
