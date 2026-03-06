<?php

namespace App\Http\Requests;

use App\Concerns\NormalizesUrls;
use Illuminate\Foundation\Http\FormRequest;

class UpdateJobPostingRequest extends FormRequest
{
    use NormalizesUrls;

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
