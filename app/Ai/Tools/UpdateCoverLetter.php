<?php

namespace App\Ai\Tools;

use App\Models\Application;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class UpdateCoverLetter implements Tool
{
    public function __construct(
        private Application $application,
        private ToolActionLog $actionLog,
    ) {}

    public function description(): Stringable|string
    {
        return 'Update the cover letter text for the application. Use this to save an edited or improved cover letter.';
    }

    public function handle(Request $request): Stringable|string
    {
        $this->application->update(['cover_letter' => $request['cover_letter']]);

        $this->actionLog->record('Updated cover letter');

        return 'Successfully updated the cover letter.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'cover_letter' => $schema->string()->required(),
        ];
    }
}
