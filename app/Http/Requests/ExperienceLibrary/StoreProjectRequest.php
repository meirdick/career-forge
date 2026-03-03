<?php

namespace App\Http\Requests\ExperienceLibrary;

use Illuminate\Foundation\Http\FormRequest;

class StoreProjectRequest extends FormRequest
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
            'experience_id' => 'nullable|exists:experiences,id',
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'role' => 'nullable|string|max:255',
            'url' => 'nullable|url|max:255',
            'scale' => 'nullable|string|max:255',
            'outcome' => 'nullable|string',
            'started_at' => 'nullable|date',
            'ended_at' => 'nullable|date|after_or_equal:started_at',
            'sort_order' => 'integer|min:0',
        ];
    }
}
