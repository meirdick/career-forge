<?php

namespace App\Http\Requests\ExperienceLibrary;

use Illuminate\Foundation\Http\FormRequest;

class StoreExperienceRequest extends FormRequest
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
            'company' => 'required|string|max:255',
            'title' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
            'started_at' => 'required|date',
            'ended_at' => 'nullable|date|after_or_equal:started_at',
            'is_current' => 'boolean',
            'description' => 'nullable|string',
            'reporting_to' => 'nullable|string|max:255',
            'team_size' => 'nullable|integer|min:1|max:10000',
            'reason_for_leaving' => 'nullable|string|max:255',
            'sort_order' => 'integer|min:0',
        ];
    }
}
