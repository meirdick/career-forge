<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreChatSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'nullable|string|max:255',
            'job_posting_id' => 'nullable|exists:job_postings,id',
            'mode' => 'sometimes|in:general,job_specific',
        ];
    }
}
