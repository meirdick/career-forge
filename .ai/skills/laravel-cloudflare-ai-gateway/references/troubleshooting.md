# Troubleshooting

Common issues and fixes when using Cloudflare AI Gateway with Laravel.

## Workers AI 500 Errors

**Symptom:** `OpenAI Error [500]: Unknown error` when calling Workers AI.

**Cause:** The provider driver is set to `openai`. As of Prism v4+ / laravel/ai v0.3+, the OpenAI driver uses `/responses` instead of `/chat/completions`. The `/compat` endpoint only supports `/chat/completions`.

**Fix:** Change the driver to `xai`:
```php
'workers-ai' => [
    'driver' => 'xai',  // NOT 'openai'
    // ...
],
```

## "No Route for That URI" from AI Gateway

**Symptom:** AI Gateway returns "No route for that URI".

**Cause:** A stray `/v1` in the Workers AI URL. The SDK appends `/chat/completions` to the base URL, so `.../compat/v1/chat/completions` is invalid.

**Fix:** Use `.../compat` as the base URL (no trailing `/v1`):
```env
# Wrong
WORKERS_AI_URL=https://gateway.ai.cloudflare.com/v1/.../compat/v1
# Correct
WORKERS_AI_URL=https://gateway.ai.cloudflare.com/v1/.../compat
```

## Gemini Requests Failing

**Symptom:** Gemini requests through the gateway return errors or 404s.

**Cause:** The gateway URL is missing the `/v1beta/models` path suffix. Gemini expects this prefix in the base URL.

**Fix:** Include the full path:
```env
# Wrong
GEMINI_URL=https://gateway.ai.cloudflare.com/v1/.../google-ai-studio
# Correct
GEMINI_URL=https://gateway.ai.cloudflare.com/v1/.../google-ai-studio/v1beta/models
```

## Auth Errors Through Gateway

**Symptom:** Authentication errors when routing through the gateway.

**Cause:** The issue is with the provider API key, not the gateway. In pass-through mode, the gateway proxies auth headers transparently.

**Fix:** Verify the provider API key is correct in `.env`. Test directly against the provider URL to confirm.

## Workers AI Auth Errors

**Symptom:** 401 or 403 errors from Workers AI.

**Cause:** The Cloudflare API token is missing, invalid, or lacks the correct permission.

**Fix:**
1. Ensure `CLOUDFLARE_AI_API_TOKEN` is set in `.env`
2. Verify the token has `Account > Workers AI > Read` permission
3. Check the token hasn't expired

## Workers AI Model Not Found

**Symptom:** Model not found or invalid model name errors.

**Cause:** Missing `workers-ai/` prefix when routing through `/compat`. The gateway uses this prefix to identify the target provider.

**Fix:** Use the full prefixed model name:
```php
// Wrong
'model' => '@cf/meta/llama-3.3-70b-instruct-fp8-fast'
// Correct
'model' => 'workers-ai/@cf/meta/llama-3.3-70b-instruct-fp8-fast'
```

## Workers AI Returns Nonsense / "Your input is not sufficient"

**Symptom:** Workers AI responds with garbled output, "Your input is not sufficient", or completely unrelated answers when called through the Laravel AI SDK or Prism PHP.

**Cause:** The Prism xAI driver serializes all user messages as content arrays (`[{type: "text", text: "..."}]`), which is valid per the OpenAI spec. But Workers AI's `/compat` endpoint only accepts `content` as a plain string. The model receives the array structure as garbled input.

**This affects ALL requests through the SDK**, not just multimodal messages.

**Fix:** This is a `/compat` endpoint limitation. Use direct `Http::post()` calls with string content format, or route to a paid provider for SDK usage. See `references/workers-ai.md` "SDK Compatibility" section for full details and workarounds.

## Workers AI Structured Output Crashes (TypeError)

**Symptom:** `TypeError` or crash in `AssistantMessage` when using `HasStructuredOutput` agents or `Prism::structured()` with Workers AI.

**Cause:** Workers AI returns `content` as a parsed JSON object (`{"intent": "interested"}`) instead of a JSON string (`"{\"intent\": \"interested\"}"`) as the OpenAI spec requires. Prism's `Structured.php` expects a string and crashes when it gets an object.

**Fix:** Do not use structured output with Workers AI through the SDK. Use a paid provider (OpenAI, Anthropic) for structured output needs, or use direct HTTP calls and parse the response yourself.

## Multimodal Content Rejected

**Symptom:** `"Type mismatch of '/messages/0/content', 'array' not in 'string'"`.

**Cause:** The `/compat` endpoint only accepts string content in messages. This is the same root cause as the "nonsense response" issue above — the Prism xAI driver always sends array format, even for text-only messages.

**Fix:** Route vision/OCR requests to OpenAI or Anthropic through the gateway instead of Workers AI.

## JSON Mode Failures

**Symptom:** `"This model doesn't support JSON Schema"` or `"JSON Mode couldn't be met"`, or model returns prose with markdown-embedded JSON instead of clean JSON.

**Cause:** Either the model doesn't support JSON mode, the schema is too complex, or you're using `json_object` mode (which Workers AI doesn't enforce — the model ignores the constraint and returns prose).

**Fix:** For `json_schema`: use a supported model (see `references/workers-ai.md` for the list) and keep schemas simple (under ~8 fields, short names, no nullable fields). For `json_object`: this mode is unreliable on Workers AI — use `json_schema` with an explicit schema instead, or use a paid provider.

## Streaming Issues

**Symptom:** Streaming responses break or return all at once.

**Cause:** AI Gateway caching interferes with streaming. Cached responses are returned non-streaming.

**Fix:** Disable caching for endpoints that use streaming, or accept that cached responses won't stream.

## Dashboard Not Showing Requests

**Symptom:** No requests appear in the AI Gateway analytics dashboard.

**Cause:** The `*_URL` env vars aren't set, or the app is using cached config.

**Fix:**
1. Verify `*_URL` env vars are set in `.env` (not just `.env.example`)
2. Clear config cache: `php artisan config:clear`
3. Restart the app / queue workers
4. Check the dashboard at: Cloudflare Dashboard > AI > AI Gateway > [your-gateway] > Analytics
