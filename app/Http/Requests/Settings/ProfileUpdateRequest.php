<?php

namespace App\Http\Requests\Settings;

use App\Concerns\NormalizesUrls;
use App\Concerns\ProfileValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ProfileUpdateRequest extends FormRequest
{
    use NormalizesUrls;
    use ProfileValidationRules;

    protected function urlFields(): array
    {
        return ['linkedin_url', 'portfolio_url'];
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->profileRules($this->user()->id);
    }
}
