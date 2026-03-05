<?php

namespace App\Ai\Tools;

use App\Models\Resume;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class SelectResumeVariant implements Tool
{
    public function __construct(
        private Resume $resume,
        private ToolActionLog $actionLog,
    ) {}

    public function description(): Stringable|string
    {
        return 'Select a specific variant for a resume section. Each section may have multiple variants with different emphasis or phrasing. Use this to switch which variant is active.';
    }

    public function handle(Request $request): Stringable|string
    {
        $sectionTitle = $request['section_title'];
        $variantLabel = $request['variant_label'];

        $this->resume->load('sections.variants');

        $section = $this->resume->sections->first(
            fn ($s) => strcasecmp($s->title, $sectionTitle) === 0
        );

        if (! $section) {
            $available = $this->resume->sections->pluck('title')->join(', ');

            return "Could not find section \"{$sectionTitle}\". Available sections: {$available}";
        }

        $variant = $section->variants->first(
            fn ($v) => strcasecmp($v->label, $variantLabel) === 0
        );

        if (! $variant) {
            $available = $section->variants->pluck('label')->join(', ');

            return "Could not find variant \"{$variantLabel}\" in section \"{$sectionTitle}\". Available variants: {$available}";
        }

        $section->update(['selected_variant_id' => $variant->id]);

        $this->actionLog->record("Selected variant \"{$variantLabel}\" for section: {$sectionTitle}");

        return "Successfully selected the \"{$variantLabel}\" variant for the \"{$sectionTitle}\" section.";
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'section_title' => $schema->string()->required(),
            'variant_label' => $schema->string()->required(),
        ];
    }
}
