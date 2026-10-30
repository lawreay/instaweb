<?php

declare(strict_types=1);

use App\\Models\\Setting;

/**
 * Runtime AI configuration. Admin-managed database values override deployment
 * defaults; API keys are never included in public status responses.
 */
$stored = (new Setting())->all();
$defaultProvider = strtolower(trim((string) ($stored['ai_default_provider'] ?? env('PORTFOLIO_AI_DEFAULT_PROVIDER', 'gemini'))));
if (!in_array($defaultProvider, ['openai', 'gemini', 'openrouter'], true)) $defaultProvider = 'gemini';

return [
    'enabled' => ($stored['ai_enabled'] ?? env('PORTFOLIO_AI_ENABLED', '1')) === '1',
    'default_provider' => $defaultProvider,
    'allow_request_key' => ($stored['ai_allow_request_key'] ?? env('PORTFOLIO_AI_ALLOW_REQUEST_KEY', '0')) === '1',
    'providers' => [
        'openai' => ['api_key' => trim((string) ($stored['ai_openai_api_key'] ?? env('OPENAI_API_KEY', ''))), 'model' => trim((string) ($stored['ai_openai_model'] ?? env('OPENAI_MODEL', 'gpt-5-mini')))],
        'gemini' => ['api_key' => trim((string) ($stored['ai_gemini_api_key'] ?? env('GEMINI_API_KEY', ''))), 'model' => trim((string) ($stored['ai_gemini_model'] ?? env('GEMINI_MODEL', 'gemini-2.5-flash')))],
        'openrouter' => ['api_key' => trim((string) ($stored['ai_openrouter_api_key'] ?? '')), 'model' => trim((string) ($stored['ai_openrouter_model'] ?? 'openrouter/auto')), 'base_url' => trim((string) ($stored['ai_openrouter_base_url'] ?? 'https://openrouter.ai/api/v1'))],
    ],
];
