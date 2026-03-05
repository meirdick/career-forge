<?php

namespace App\Http\Requests;

use App\Rules\SupportedScrapingUrl;
use Illuminate\Foundation\Http\FormRequest;

class StoreJobPostingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $urlRules = ['nullable', 'url', 'max:2048'];

        if (! $this->filled('raw_text')) {
            $urlRules[] = new SupportedScrapingUrl;
        }

        return [
            'url' => $urlRules,
            'raw_text' => 'required_without:url|nullable|string',
            'title' => 'nullable|string|max:255',
            'company' => 'nullable|string|max:255',
        ];
    }
}
