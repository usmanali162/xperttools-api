<?php

namespace App\Services;

abstract class BaseToolService
{
    protected function logUsage(string $tool, array $data = []): void
    {
        // Log tool usage for analytics
        // Will implement proper logging later
    }
    
    protected function checkRateLimit(string $tool, ?int $userId = null): bool
    {
        // Check if user has exceeded rate limits
        // For now, return true (no limits)
        return true;
    }
    
    protected function validateInput(array $data, array $rules): array
    {
        return validator($data, $rules)->validate();
    }
}