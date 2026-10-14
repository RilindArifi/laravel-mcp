<?php

namespace App\Concerns;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait McpTokenValidationRules
{
    /**
     * Get the validation rules used when issuing an MCP token.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function mcpTokenRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:60'],
            'abilities' => ['required', 'array', 'min:1'],
            'abilities.*' => [Rule::in($this->mcpAbilities())],
            'expires_in_days' => ['nullable', 'integer', Rule::in([7, 30, 90, 365])],
        ];
    }

    /**
     * Get the abilities a token may be granted.
     *
     * @return array<int, string>
     */
    protected function mcpAbilities(): array
    {
        return ['mcp:read', 'mcp:write'];
    }
}
