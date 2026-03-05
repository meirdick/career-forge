<?php

namespace App\Ai\Tools;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class UpdateApplicationStatus implements Tool
{
    public function __construct(
        private Application $application,
        private ToolActionLog $actionLog,
    ) {}

    public function description(): Stringable|string
    {
        return 'Update the status of the application. Valid statuses: draft, applied, interviewing, offer, rejected, withdrawn. Optionally include notes about the status change.';
    }

    public function handle(Request $request): Stringable|string
    {
        $statusValue = $request['status'];
        $notes = $request['notes'] ?? null;

        $newStatus = ApplicationStatus::tryFrom($statusValue);

        if (! $newStatus) {
            $valid = collect(ApplicationStatus::cases())->map(fn ($s) => $s->value)->join(', ');

            return "Invalid status \"{$statusValue}\". Valid statuses: {$valid}";
        }

        $oldStatus = $this->application->status;

        $this->application->update([
            'status' => $newStatus,
            'applied_at' => $newStatus === ApplicationStatus::Applied && ! $this->application->applied_at ? now() : $this->application->applied_at,
        ]);

        $this->application->statusChanges()->create([
            'from_status' => $oldStatus,
            'to_status' => $newStatus,
            'notes' => $notes,
        ]);

        $this->actionLog->record("Updated application status to: {$newStatus->value}");

        return "Successfully updated the application status from \"{$oldStatus->value}\" to \"{$newStatus->value}\".";
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'status' => $schema->string()->required(),
            'notes' => $schema->string(),
        ];
    }
}
