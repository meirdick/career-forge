# AI Gateway Modes

Cloudflare AI Gateway supports four authentication/routing modes. This determines how your app authenticates with providers and whether the gateway itself requires authentication.

## Mode Comparison

| Mode | Provider Key? | Gateway Auth? | Laravel Native? | Recommended? |
|------|--------------|---------------|-----------------|--------------|
| Pass-through (open) | Yes, in headers | No | Yes | **Default** |
| Pass-through (authenticated) | Yes + `cf-aig-authorization` | Yes | No | Advanced |
| BYOK (stored keys) | No (keys in CF dashboard) | `cf-aig-authorization` | No | Advanced |
| Unified billing | No (CF manages billing) | `cf-aig-authorization` | No | Advanced |

## Mode 1: Pass-through (open)

**How it works:** Your app sends requests to the gateway URL instead of directly to the provider. Provider API keys pass through transparently in standard auth headers (`Authorization: Bearer ...` for OpenAI, `x-api-key` for Anthropic, etc.). The gateway logs, caches, and rate-limits requests without needing its own authentication.

**Setup:**
1. Create an AI Gateway in the Cloudflare dashboard (AI > AI Gateway)
2. Set provider URLs to `https://gateway.ai.cloudflare.com/v1/{account-id}/{gateway-name}/{provider-path}`
3. Keep your existing provider API keys in `.env`

**Pros:**
- Zero-friction setup — just change base URLs
- Works natively with Laravel AI SDK and Prism PHP
- No custom HTTP middleware needed
- Easy rollback — remove URL env vars to go direct

**Cons:**
- Anyone with the gateway URL can proxy through it (though they still need valid provider keys)
- No centralized key management

**This is the recommended mode for Laravel projects.** All configuration changes are env-var based, requiring no custom code.

## Mode 2: Pass-through (authenticated)

**How it works:** Same as Mode 1, but the gateway itself requires authentication via the `cf-aig-authorization` header. Your app must send both the provider API key (in standard headers) and a gateway token (in `cf-aig-authorization`).

**Setup:**
1. Enable authentication on the gateway in Cloudflare dashboard
2. Create a gateway API token
3. Add `cf-aig-authorization: Bearer {gateway-token}` header to all requests

**Laravel limitation:** Neither Laravel AI SDK nor Prism PHP natively support adding custom headers to AI provider requests. You would need to write custom HTTP middleware (e.g., using Guzzle middleware or Laravel's HTTP client events) to inject the `cf-aig-authorization` header.

**Pros:**
- Gateway URL alone is not enough — requires a token
- Still uses your own provider keys

**Cons:**
- Requires custom HTTP middleware in Laravel
- More complex setup and maintenance

## Mode 3: BYOK (Bring Your Own Key, stored in Cloudflare)

**How it works:** Provider API keys are stored in the Cloudflare dashboard instead of your app. Your app authenticates to the gateway with `cf-aig-authorization`, and the gateway injects the provider key before forwarding the request.

**Setup:**
1. Add provider API keys in the AI Gateway settings in Cloudflare dashboard
2. Create a gateway API token
3. Your app sends only the `cf-aig-authorization` header — no provider keys needed in requests

**Laravel limitation:** Same as Mode 2 — requires custom HTTP middleware for the `cf-aig-authorization` header. Additionally, you'd need to remove/skip the standard provider key headers that Laravel AI SDK and Prism PHP automatically include.

**Pros:**
- API keys never leave Cloudflare — not in your `.env` or app config
- Single auth token for all providers

**Cons:**
- Requires custom HTTP middleware in Laravel
- Key management moves to Cloudflare dashboard
- Harder to debug auth issues

## Mode 4: Unified Billing

**How it works:** Cloudflare manages both the provider keys and billing. Your app authenticates to the gateway with `cf-aig-authorization`, and Cloudflare handles everything else.

**Setup:**
1. Set up billing through Cloudflare for supported providers
2. Create a gateway API token
3. Your app sends only the `cf-aig-authorization` header

**Laravel limitation:** Same as Modes 2 and 3.

**Pros:**
- Simplest from an app perspective — one token, no provider keys
- Consolidated billing through Cloudflare

**Cons:**
- Requires custom HTTP middleware in Laravel
- Limited to providers Cloudflare supports for billing
- Less control over provider accounts

## Recommendation

**Use Mode 1 (pass-through, open) for Laravel projects.** It's the only mode that works natively with Laravel AI SDK and Prism PHP without custom HTTP middleware. The security trade-off is minimal — the gateway URL is functionally equivalent to a proxy URL, and attackers would still need valid provider API keys to make requests.

If you need gateway authentication (Modes 2-4), you'll need to implement custom HTTP middleware to inject the `cf-aig-authorization` header. This is possible but adds complexity that may not be worth it for most Laravel applications.
