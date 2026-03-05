<?php

namespace App\Ai\Tools;

use App\Models\IdealCandidateProfile;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class UpdateCandidateProfileField implements Tool
{
    private const ALLOWED_FIELDS = [
        'experience_profile',
        'cultural_fit',
        'language_guidance',
        'red_flags',
    ];

    public function __construct(
        private IdealCandidateProfile $profile,
        private ToolActionLog $actionLog,
    ) {}

    public function description(): Stringable|string
    {
        return 'Update a field on the ideal candidate profile. Allowed fields: experience_profile, cultural_fit, language_guidance, red_flags. The value should be an array of strings.';
    }

    public function handle(Request $request): Stringable|string
    {
        $field = $request['field'];
        $value = $request['value'];

        if (! in_array($field, self::ALLOWED_FIELDS)) {
            return 'Invalid field. Allowed fields: '.implode(', ', self::ALLOWED_FIELDS);
        }

        $this->profile->update([
            $field => $value,
            'is_user_edited' => true,
        ]);

        $this->actionLog->record("Updated candidate profile: {$field}");

        return "Successfully updated \"{$field}\" on the candidate profile.";
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'field' => $schema->string()->required(),
            'value' => $schema->array()->items($schema->string())->required(),
        ];
    }
}
