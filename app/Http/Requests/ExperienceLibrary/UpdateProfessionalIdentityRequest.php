<?php

namespace App\Http\Requests\ExperienceLibrary;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfessionalIdentityRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
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
        ];
    }
}
