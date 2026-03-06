<?php

namespace App\Concerns;

trait NormalizesUrls
{
    /**
     * Fields to normalize as URLs.
     *
     * @return array<int, string>
     */
    protected function urlFields(): array
    {
        return ['url'];
    }

    protected function prepareForValidation(): void
    {
        foreach ($this->urlFields() as $field) {
            $value = $this->input($field);

            if (is_string($value) && $value !== '' && ! preg_match('#^https?://#i', $value)) {
                $this->merge([$field => 'https://'.$value]);
            }
        }
    }
}
