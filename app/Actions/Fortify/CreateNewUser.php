<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use App\Services\CreditService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
            'referral_code' => ['nullable', 'string', 'max:20'],
        ])->validate();

        $referrer = null;

        if (! empty($input['referral_code'])) {
            $referrer = User::where('referral_code', $input['referral_code'])->first();
        }

        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $input['password'],
            'referral_code' => Str::random(8),
            'referred_by' => $referrer?->id,
        ]);

        $creditService = app(CreditService::class);
        $creditService->grantSignupBonus($user);

        if ($referrer) {
            $creditService->grantReferralBonus($referrer, $user);
        }

        return $user;
    }
}
