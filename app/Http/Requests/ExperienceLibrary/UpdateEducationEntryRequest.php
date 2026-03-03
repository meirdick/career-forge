<?php

namespace App\Http\Requests\ExperienceLibrary;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEducationEntryRequest extends FormRequest
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
            'type' => 'required|string|in:degree,certification,license,course,workshop,publication,patent,speaking_engagement',
            'institution' => 'required|string|max:255',
            'title' => 'required|string|max:255',
            'field' => 'nullable|string|max:255',
            'url' => 'nullable|url|max:255',
            'description' => 'nullable|string',
            'started_at' => 'nullable|date',
            'completed_at' => 'nullable|date|after_or_equal:started_at',
            'sort_order' => 'integer|min:0',
        ];
    }
}
