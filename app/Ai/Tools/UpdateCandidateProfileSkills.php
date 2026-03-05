<?php

namespace App\Ai\Tools;

use App\Models\IdealCandidateProfile;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class UpdateCandidateProfileSkills implements Tool
{
    public function __construct(
        private IdealCandidateProfile $profile,
        private ToolActionLog $actionLog,
    ) {}

    public function description(): Stringable|string
    {
        return 'Add or remove skills from the ideal candidate profile. Specify whether to add or remove, the skill type (required or preferred), and an array of skill strings.';
    }

    public function handle(Request $request): Stringable|string
    {
        $action = $request['action'];
        $skillType = $request['skill_type'];
        $skills = $request['skills'];

        if (! in_array($action, ['add', 'remove'])) {
            return 'Invalid action. Must be "add" or "remove".';
        }

        if (! in_array($skillType, ['required', 'preferred'])) {
            return 'Invalid skill_type. Must be "required" or "preferred".';
        }

        $column = $skillType === 'required' ? 'required_skills' : 'preferred_skills';
        $currentSkills = $this->profile->{$column} ?? [];

        if ($action === 'add') {
            $currentSkills = array_values(array_unique(array_merge($currentSkills, $skills)));
        } else {
            $currentSkills = array_values(array_filter(
                $currentSkills,
                fn ($s) => ! in_array(strtolower($s), array_map('strtolower', $skills))
            ));
        }

        $this->profile->update([
            $column => $currentSkills,
            'is_user_edited' => true,
        ]);

        $this->actionLog->record("Updated {$skillType} skills: {$action} ".implode(', ', $skills));

        $verb = $action === 'add' ? 'Added' : 'Removed';

        return "{$verb} {$skillType} skills: ".implode(', ', $skills)."\n\nCurrent {$skillType} skills: ".implode(', ', $currentSkills);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'action' => $schema->string()->required(),
            'skill_type' => $schema->string()->required(),
            'skills' => $schema->array()->items($schema->string())->required(),
        ];
    }
}
