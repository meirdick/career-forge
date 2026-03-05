<?php

namespace App\Ai\Tools;

class ToolActionLog
{
    /** @var string[] */
    private array $actions = [];

    public function record(string $action): void
    {
        $this->actions[] = $action;
    }

    /** @return string[] */
    public function actions(): array
    {
        return $this->actions;
    }

    public function hasActions(): bool
    {
        return count($this->actions) > 0;
    }
}
