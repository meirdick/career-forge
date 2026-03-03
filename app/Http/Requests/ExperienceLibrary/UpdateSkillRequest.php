<?php

namespace App\Http\Requests\ExperienceLibrary;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSkillRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'category' => 'required|string|in:technical,domain,soft,tool,methodology',
            'proficiency' => 'nullable|string|in:beginner,intermediate,advanced,expert',
            'notes' => 'nullable|string',
        ];
    }
}
