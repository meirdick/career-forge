<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreJobPostingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'url' => 'nullable|url|max:2048',
            'raw_text' => 'required|string',
            'title' => 'nullable|string|max:255',
            'company' => 'nullable|string|max:255',
        ];
    }
}
