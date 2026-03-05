<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EnhanceContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'section' => 'required|string|in:experience,accomplishment,education,project',
            'item' => 'required|array',
        ];
    }
}
