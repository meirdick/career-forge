<?php

namespace App\Http\Requests\Settings;

use App\Concerns\NormalizesUrls;
use Illuminate\Foundation\Http\FormRequest;

class UserLinkRequest extends FormRequest
{
    use NormalizesUrls;

    protected function urlFields(): array
    {
        return ['url'];
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'url' => ['required', 'url', 'max:2048'],
            'label' => ['nullable', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:portfolio,github,website,other'],
        ];
    }
}
