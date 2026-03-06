<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Services\UserApiKeyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ApiKeyController extends Controller
{
    public function __construct(
        protected UserApiKeyService $apiKeyService,
    ) {}

    public function show(Request $request): Response
    {
        return Inertia::render('settings/api-keys', [
            'apiKeys' => $request->user()->apiKeys()->get()->map(fn ($key) => [
                'id' => $key->id,
                'provider' => $key->provider,
                'is_active' => $key->is_active,
                'validated_at' => $key->validated_at?->toIso8601String(),
                'created_at' => $key->created_at->toIso8601String(),
            ]),
            'providers' => ['anthropic', 'openai', 'gemini', 'groq'],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'provider' => ['required', 'string', 'in:anthropic,openai,gemini,groq'],
            'api_key' => ['required', 'string', 'min:10'],
        ]);

        $isValid = $this->apiKeyService->validate($validated['provider'], $validated['api_key']);

        if (! $isValid) {
            return back()->withErrors(['api_key' => 'The API key could not be validated. Please check that it is correct.']);
        }

        $apiKey = $this->apiKeyService->store(
            $request->user(),
            $validated['provider'],
            $validated['api_key'],
        );

        $this->apiKeyService->activate($apiKey);

        return back()->with('success', 'API key saved and activated.');
    }

    public function destroy(Request $request, int $apiKeyId): RedirectResponse
    {
        $apiKey = $request->user()->apiKeys()->findOrFail($apiKeyId);
        $apiKey->delete();

        return back()->with('success', 'API key removed.');
    }
}
