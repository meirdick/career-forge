<?php

namespace App\Http\Requests\ExperienceLibrary;

use App\Concerns\NormalizesUrls;
use Illuminate\Foundation\Http\FormRequest;

class StoreEvidenceEntryRequest extends FormRequest
{
    use NormalizesUrls;

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
            'type' => 'required|string|in:portfolio,repository,article,review,testimonial,other',
            'title' => 'required|string|max:255',
            'url' => 'nullable|url|max:255',
            'description' => 'nullable|string',
            'content' => 'nullable|string',
        ];
    }
}
