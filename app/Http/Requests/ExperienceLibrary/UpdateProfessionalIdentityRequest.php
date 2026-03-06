<?php

namespace App\Http\Requests\ExperienceLibrary;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfessionalIdentityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'values' => 'nullable|string',
            'philosophy' => 'nullable|string',
            'passions' => 'nullable|string',
            'leadership_style' => 'nullable|string',
            'collaboration_approach' => 'nullable|string',
            'communication_style' => 'nullable|string',
            'cultural_preferences' => 'nullable|string',
            'legal_name' => 'nullable|string|max:255',
            'resume_header_config' => 'nullable|array',
            'resume_header_config.name_preference' => 'sometimes|in:display_name,legal_name',
            'resume_header_config.show_email' => 'sometimes|boolean',
            'resume_header_config.show_phone' => 'sometimes|boolean',
            'resume_header_config.show_location' => 'sometimes|boolean',
            'resume_header_config.show_linkedin' => 'sometimes|boolean',
            'resume_header_config.show_portfolio' => 'sometimes|boolean',
        ];
    }
}
