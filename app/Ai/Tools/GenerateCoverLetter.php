<?php

namespace App\Ai\Tools;

use App\Ai\Agents\CoverLetterWriter;
use App\Models\Application;
use App\Models\User;
use App\Services\CoverLetterContextBuilder;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GenerateCoverLetter implements Tool
{
    public function __construct(
        private User $user,
        private Application $application,
        private ToolActionLog $actionLog,
    ) {}

    public function description(): Stringable|string
    {
        return 'Generate a new cover letter for the application using AI. Optionally accepts extra instructions to guide the generation. The generated cover letter is saved to the application automatically.';
    }

    public function handle(Request $request): Stringable|string
    {
        $context = app(CoverLetterContextBuilder::class)->build($this->user, $this->application);
        $instructions = $request['instructions'] ?? '';

        $prompt = 'Write a cover letter for this position.';

        if ($instructions !== '') {
            $prompt .= " Additional guidance: {$instructions}";
        }

        $response = (new CoverLetterWriter(context: $context))
            ->prompt($prompt);

        $this->application->update(['cover_letter' => $response->text]);

        $this->actionLog->record('Generated cover letter');

        return "Cover letter generated and saved:\n\n{$response->text}";
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'instructions' => $schema->string(),
        ];
    }
}
