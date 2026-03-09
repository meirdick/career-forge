<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserApiKey;
use Illuminate\Support\Facades\Http;

class UserApiKeyService
{
    public function store(User $user, string $provider, string $key): UserApiKey
    {
        return UserApiKey::updateOrCreate(
            ['user_id' => $user->id, 'provider' => $provider],
            [
                'encrypted_key' => $key,
                'is_active' => false,
                'validated_at' => null,
            ]
        );
    }

    public function validate(string $provider, string $key): bool
    {
        return match ($provider) {
            'anthropic' => $this->validateAnthropic($key),
            'openai' => $this->validateOpenAi($key),
            'gemini' => $this->validateGemini($key),
            'groq' => $this->validateGroq($key),
            default => false,
        };
    }

    public function activate(UserApiKey $apiKey): void
    {
        // Deactivate all other keys for this user first
        UserApiKey::where('user_id', $apiKey->user_id)
            ->where('id', '!=', $apiKey->id)
            ->update(['is_active' => false]);

        $apiKey->update([
            'is_active' => true,
            'validated_at' => now(),
        ]);
    }

    public function deactivate(User $user): void
    {
        $user->apiKeys()->update(['is_active' => false]);
    }

    protected function validateAnthropic(string $key): bool
    {
        $response = Http::timeout(60)->withHeaders([
            'x-api-key' => $key,
            'anthropic-version' => '2023-06-01',
        ])->post('https://api.anthropic.com/v1/messages', [
            'model' => 'claude-haiku-4-5-20251001',
            'max_tokens' => 1,
            'messages' => [['role' => 'user', 'content' => 'hi']],
        ]);

        return $response->successful();
    }

    protected function validateOpenAi(string $key): bool
    {
        $response = Http::timeout(60)->withToken($key)
            ->get('https://api.openai.com/v1/models');

        return $response->successful();
    }

    protected function validateGemini(string $key): bool
    {
        $response = Http::timeout(60)
            ->get("https://generativelanguage.googleapis.com/v1beta/models?key={$key}");

        return $response->successful();
    }

    protected function validateGroq(string $key): bool
    {
        $response = Http::timeout(60)->withToken($key)
            ->get('https://api.groq.com/openai/v1/models');

        return $response->successful();
    }
}
