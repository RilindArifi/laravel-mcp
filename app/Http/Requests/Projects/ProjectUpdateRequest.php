<?php

namespace App\Http\Requests\Projects;

use App\Concerns\ProjectValidationRules;
use App\Models\Project;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ProjectUpdateRequest extends FormRequest
{
    use ProjectValidationRules;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $project = $this->route('project');

        return $this->projectRules(
            $this->user()->id,
            $project instanceof Project ? $project->id : null,
        );
    }
}
