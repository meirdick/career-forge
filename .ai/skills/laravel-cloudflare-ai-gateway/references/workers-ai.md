# Workers AI Reference

Workers AI runs open-source models on Cloudflare's global edge network. Free tier: 10,000 neurons/day. When routed through AI Gateway, it provides a unified endpoint for both free and paid models.

## Endpoint: `/compat` vs `/workers-ai/v1`

Workers AI has two endpoints through AI Gateway:

| Endpoint | Path | Format | Recommended? |
|----------|------|--------|-------------|
| Compat | `.../compat` | OpenAI `/chat/completions` format | **Yes** |
| Native | `.../workers-ai/v1` | Workers AI native format | No |

**Always use `/compat`.** The compat endpoint speaks the standard OpenAI `/chat/completions` format that Laravel AI SDK and Prism PHP expect. The native endpoint uses a different request/response format that isn't compatible with these frameworks.

The full URL pattern is:
```
https://gateway.ai.cloudflare.com/v1/{account-id}/{gateway-name}/compat
```

The SDK appends `/chat/completions` automatically, so the final request goes to `.../compat/chat/completions`.

**Important:** Do NOT add `/v1` after `/compat`. The URL `.../compat/v1/chat/completions` will return "No route for that URI".

## Driver: `workers-ai` (via `meirdick/prism-workers-ai`)

**Install:**
```bash
composer require meirdick/prism-workers-ai
```

This package provides a first-class `workers-ai` driver for Prism PHP that fixes three issues with the built-in `xai` driver:

1. **String content format** — The xAI driver wraps user content in `[{type: "text", text: "..."}]` (array format). Workers AI `/compat` expects a plain string for text-only messages. The `workers-ai` driver sends string content.
2. **Object content in structured output** — `/compat` may return `content` as a JSON object instead of a string, causing a TypeError crash. The `workers-ai` driver handles this gracefully.
3. **Embeddings support** — The xAI driver doesn't support embeddings. The `workers-ai` driver has a built-in Embeddings handler.

**Do NOT use the `openai` driver.** It uses `/responses` (as of Prism v4+ / laravel/ai v0.3+), which Workers AI doesn't support. This causes a silent 500 error.

```php
// Laravel AI SDK (config/ai.php)
'workers-ai' => [
    'driver' => 'workers-ai',
    'key' => env('CLOUDFLARE_AI_API_TOKEN'),
    'url' => env('WORKERS_AI_URL'),
],

// Prism PHP (config/prism.php)
'workers-ai' => [
    'api_key' => env('CLOUDFLARE_AI_API_TOKEN', ''),
    'url' => env('WORKERS_AI_URL'),
],
```

## Authentication

Workers AI requires a Cloudflare API token with `Workers AI: Read` permission.

**Creating the token:**
1. Go to dash.cloudflare.com/profile/api-tokens
2. Click "Create Token"
3. Use "Custom token" template
4. Add permission: `Account` > `Workers AI` > `Read`
5. Save the token as `CLOUDFLARE_AI_API_TOKEN` in your `.env`

The token is sent as a standard `Authorization: Bearer` header, which the gateway proxies to Workers AI.

## Recommended Models

All model names must be prefixed with `workers-ai/` when routing through the AI Gateway `/compat` endpoint, so the gateway knows which provider to route to.

| Model | Use Case | Notes |
|-------|----------|-------|
| `workers-ai/@cf/meta/llama-3.3-70b-instruct-fp8-fast` | General purpose | Best balance of quality and speed |
| `workers-ai/@cf/meta/llama-3.1-8b-instruct` | Cheap/fast tasks | Smallest, fastest, lowest cost |
| `workers-ai/@cf/qwen/qwq-32b` | Reasoning | Multi-step reasoning tasks |
| `workers-ai/@cf/qwen/qwen2.5-coder-32b-instruct` | Code generation | Optimized for code |
| `workers-ai/@cf/mistralai/mistral-small-3.1-24b-instruct` | Multilingual | Strong multilingual support |
| `workers-ai/@cf/deepseek-ai/deepseek-r1-distill-qwen-32b` | Reasoning (alt) | DeepSeek R1 distilled |
| `workers-ai/@cf/meta/llama-4-scout-17b-16e-instruct` | Multimodal | 131K context, MoE, function calling |
| `workers-ai/@cf/baai/bge-large-en-v1.5` | Embeddings | Text embedding model |

## JSON Mode

Workers AI supports JSON mode (`response_format: {type: 'json_schema', ...}`) only with specific models:

**Supported models:**
- `@cf/meta/llama-3.3-70b-instruct-fp8-fast`
- `@cf/meta/llama-3.1-70b-instruct`
- `@cf/meta/llama-3.1-8b-instruct` / `llama-3.1-8b-instruct-fast`
- `@cf/meta/llama-3.2-11b-vision-instruct`
- `@cf/meta/llama-3-8b-instruct`
- `@cf/deepseek-ai/deepseek-r1-distill-qwen-32b`
- `@hf/nousresearch/hermes-2-pro-mistral-7b`

Models not on this list (e.g., `@cf/qwen/qwq-32b`, `@cf/mistralai/mistral-small-3.1-24b-instruct`) return `"This model doesn't support JSON Schema"`.

**Tips for reliable JSON mode (via curl/direct HTTP only — see SDK limitations below):**
- Keep field names short and simple
- Avoid deeply nested objects
- Keep total fields under ~8
- Use short descriptions
- Avoid nullable schema fields — use empty strings instead

## SDK Compatibility

With the `meirdick/prism-workers-ai` package installed, all major Prism features work through Workers AI:

| Feature | Status | Notes |
|---------|--------|-------|
| Text generation | Works | String content format, no garbled responses |
| Structured output | Works | Handles object content gracefully |
| Tool calling | Works | Standard OpenAI function calling format |
| Streaming | Works | SSE streaming via `/chat/completions` |
| Embeddings | Works | Via `/embeddings` endpoint |
| json_schema mode | Works | On supported models (see JSON Mode section) |

### Remaining `/compat` notes

- **json_object mode** may not be enforced by Workers AI — use `json_schema` mode instead.
- **Multimodal** (image content) is untested through `/compat` — may work with models like `@cf/meta/llama-4-scout-17b-16e-instruct`.
- **Responses API** — Some models (`@cf/openai/gpt-oss-120b`) support `/responses`, but most Workers AI models only support `/chat/completions`.

### What always works

Gateway routing for **paid providers** (OpenAI, Anthropic, Gemini, Groq, etc.) works perfectly through the SDK — no issues. The compatibility notes above are specific to Workers AI's `/compat` endpoint.

## Limitations

### Llama 4 Scout multimodal
`@cf/meta/llama-4-scout-17b-16e-instruct` is natively multimodal and categorized as "Text Generation" (not Image-to-Text). Multimodal content through `/compat` is untested.

## Cost Tiering

With the `workers-ai` driver, Workers AI can be used through the full SDK — no need for direct HTTP fallbacks.

**Tier 1 — Workers AI (free/credits):** Chatbots, content suggestions, internal tools, embeddings, simple structured output — anything where open-source model quality is sufficient.

**Tier 2 — Paid providers via AI Gateway:** Complex agents, quality-critical output, vision/OCR, large document processing — where you need GPT-4/Claude-level quality.
