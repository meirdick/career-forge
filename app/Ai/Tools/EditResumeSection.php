<?php

namespace App\Ai\Tools;

use App\Models\Resume;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class EditResumeSection implements Tool
{
    public function __construct(
        private Resume $resume,
        private ToolActionLog $actionLog,
    ) {}

    public function description(): Stringable|string
    {
        return 'Edit the content of a resume section. Finds the section by title, updates the selected variant content, and marks it as user-edited. Use this to improve phrasing, add quantification, or strengthen action verbs in a resume section.';
    }

    public function handle(Request $request): Stringable|string
    {
        $sectionTitle = $request['section_title'];
        $newContent = $request['new_content'];

        $this->resume->load('sections.variants');

        $section = $this->resume->sections->first(
            fn ($s) => strcasecmp($s->title, $sectionTitle) === 0
        );

        if (! $section) {
            $available = $this->resume->sections->pluck('title')->join(', ');

            return "Could not find section \"{$sectionTitle}\". Available sections: {$available}";
        }

        $variant = $section->selectedVariant ?? $section->variants->first();

        if (! $variant) {
            return "Section \"{$sectionTitle}\" has no variants to edit.";
        }

        $variant->update([
            'content' => $newContent,
            'is_user_edited' => true,
        ]);

        $this->actionLog->record("Edited resume section: {$sectionTitle}");

        return "Successfully updated the \"{$sectionTitle}\" section of the resume.";
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'section_title' => $schema->string()->required(),
            'new_content' => $schema->string()->required(),
        ];
    }
}
