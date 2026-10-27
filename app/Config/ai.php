<?php

declare(strict_types=1);

/**
 * Portfolio AI is deliberately configured outside the database. Provider keys
 * are deployment secrets and must never be exposed in an editor response.
 */
$defaultProvider = strtolower(trim((string) env('PORTFOLIO_AI_DEFAULT_PROVIDER', 'openai')));
if (!in_array($defaultProvider, ['openai', 'gemini'], true)) {
    $defaultProvider = 'openai';
}

return [
    'enabled' => filter_var(env('PORTFOLIO_AI_ENABLED', '1'), FILTER_VALIDATE_BOOLEAN),
    'default_provider' => $defaultProvider,
    'allow_request_key' => filter_var(env('PORTFOLIO_AI_ALLOW_REQUEST_KEY', '1'), FILTER_VALIDATE_BOOLEAN),
    'providers' => [
        'openai' => [
            'api_key' => trim((string) env('OPENAI_API_KEY', '')),
            'model' => trim((string) env('OPENAI_MODEL', 'gpt-5-mini')),
        ],
        'gemini' => [
            'api_key' => trim((string) env('GEMINI_API_KEY', '')),
            'model' => trim((string) env('GEMINI_MODEL', 'gemini-2.5-flash')),
        ],
    ],
];
