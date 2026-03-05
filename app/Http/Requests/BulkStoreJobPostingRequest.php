<?php

namespace App\Http\Requests;

use App\Rules\SupportedScrapingUrl;
use Illuminate\Foundation\Http\FormRequest;

class BulkStoreJobPostingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'urls' => 'required|array|min:1|max:20',
            'urls.*' => ['required', 'url', 'max:2048', 'distinct', new SupportedScrapingUrl],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'urls.required' => 'Please provide at least one URL.',
            'urls.max' => 'You can add up to 20 URLs at a time.',
            'urls.*.url' => 'Each line must be a valid URL.',
            'urls.*.distinct' => 'Duplicate URLs are not allowed.',
        ];
    }
}
