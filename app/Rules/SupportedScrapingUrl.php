<?php

namespace App\Rules;

use App\Services\WebScraperService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SupportedScrapingUrl implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_string($value) && WebScraperService::isUnsupportedUrl($value)) {
            $fail('LinkedIn URLs cannot be automatically fetched. Please use "New Posting" to paste the job description directly.');
        }
    }
}
