<?php

namespace App\Http\Requests\Mcp;

use App\Concerns\McpTokenValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class TokenStoreRequest extends FormRequest
{
    use McpTokenValidationRules;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->mcpTokenRules();
    }

    /**
     * Get the validation messages that apply to the request.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'scopes.required' => 'Pick at least one scope for the token.',
        ];
    }
}
