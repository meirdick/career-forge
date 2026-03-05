<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CommitExtractionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'experiences' => 'array',
            'experiences.*.company' => 'required|string',
            'experiences.*.title' => 'required|string',
            'experiences.*.started_at' => 'required|date',
            'accomplishments' => 'array',
            'accomplishments.*.title' => 'required|string',
            'accomplishments.*.description' => 'required|string',
            'skills' => 'array',
            'skills.*.name' => 'required|string',
            'skills.*.category' => 'required|string',
            'education' => 'array',
            'education.*.type' => 'required|string',
            'education.*.institution' => 'required|string',
            'education.*.title' => 'required|string',
            'projects' => 'array',
            'projects.*.name' => 'required|string',
            'projects.*.description' => 'required|string',
        ];
    }
}
