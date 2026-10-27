<?php

declare(strict_types=1);

namespace App\Services;

/** Provider adapter for a structured portfolio-writing response. */
interface PortfolioAiProvider
{
    /**
     * @param array<string, mixed> $schema
     * @return array<string, mixed>
     */
    public function generate(string $systemInstruction, string $prompt, array $schema, string $apiKey): array;
}
