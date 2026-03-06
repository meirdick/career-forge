<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\CreditService;
use Illuminate\Console\Command;

class GrantCreditsCommand extends Command
{
    protected $signature = 'credits:grant
        {email : The user email to grant credits to}
        {amount : Number of credits to grant}
        {--reason=Manual grant : Reason for the credit grant}';

    protected $description = 'Grant bonus credits to a user';

    public function handle(CreditService $creditService): int
    {
        $user = User::where('email', $this->argument('email'))->first();

        if (! $user) {
            $this->error("User not found: {$this->argument('email')}");

            return self::FAILURE;
        }

        $amount = (int) $this->argument('amount');

        if ($amount <= 0) {
            $this->error('Amount must be a positive integer.');

            return self::FAILURE;
        }

        $reason = $this->option('reason');

        $transaction = $creditService->addBonus($user, $amount, $reason);

        $this->info("Granted {$amount} credits to {$user->email}");
        $this->info("New balance: {$transaction->balance_after}");

        return self::SUCCESS;
    }
}
