<?php

namespace App\Http\Requests\ExperienceLibrary;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAccomplishmentRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'experience_id' => [
                'nullable',
                Rule::exists('experiences', 'id')->where('user_id', $this->user()->id),
            ],
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'impact' => 'nullable|string',
            'sort_order' => 'integer|min:0',
        ];
    }
}
