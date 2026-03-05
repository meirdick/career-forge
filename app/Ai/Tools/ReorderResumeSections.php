<?php

namespace App\Ai\Tools;

use App\Models\Resume;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class ReorderResumeSections implements Tool
{
    public function __construct(
        private Resume $resume,
        private ToolActionLog $actionLog,
    ) {}

    public function description(): Stringable|string
    {
        return 'Reorder the sections of the resume. Provide the section titles in the desired order. Use this to optimize the resume layout for the target position.';
    }

    public function handle(Request $request): Stringable|string
    {
        $sectionOrder = $request['section_order'];

        $this->resume->load('sections');

        $sections = $this->resume->sections;
        $titleToId = $sections->pluck('id', 'title')->mapWithKeys(
            fn ($id, $title) => [strtolower($title) => $id]
        );

        foreach ($sectionOrder as $index => $title) {
            $sectionId = $titleToId[strtolower($title)] ?? null;

            if (! $sectionId) {
                $available = $sections->pluck('title')->join(', ');

                return "Could not find section \"{$title}\". Available sections: {$available}";
            }

            $this->resume->sections()->where('id', $sectionId)->update(['sort_order' => $index]);
        }

        $this->resume->update(['section_order' => $sectionOrder]);

        $this->actionLog->record('Reordered resume sections');

        return 'Successfully reordered resume sections to: '.implode(', ', $sectionOrder);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'section_order' => $schema->array()->items($schema->string())->required(),
        ];
    }
}
