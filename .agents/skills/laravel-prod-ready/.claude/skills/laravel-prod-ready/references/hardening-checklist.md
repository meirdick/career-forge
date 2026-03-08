# Hardening Checklist (Laravel)

## 1. Debug Mode & Environment — CRITICAL

Check for debug mode enabled:
```
Pattern: (APP_DEBUG\s*=\s*true|'debug'\s*=>\s*true)
Glob: **/.env*, **/config/app.php
```

Check the application environment:
```
Pattern: (APP_ENV\s*=\s*(local|development|testing))
Glob: **/.env*
```

Exclude `.env.example` and `.env.testing` from findings — these are expected to have debug enabled.

Also check that `APP_KEY` is set (not empty or the default placeholder):
```
Pattern: APP_KEY\s*=\s*$|APP_KEY\s*=\s*SomeRandomString
Glob: **/.env*
```

**Why it matters:** `APP_DEBUG=true` in production exposes Laravel's Ignition error page, which shows full stack traces, environment variables (including database passwords and API keys), and source code to every visitor. This is the single most common Laravel security misconfiguration.

## 2. Telescope & Debug Tools in Production — CRITICAL

Check if Telescope is installed and whether it's properly gated:
```
Pattern: (TelescopeServiceProvider|Telescope::auth|telescope)
Glob: **/config/telescope.php, **/app/Providers/*.php, **/config/app.php, **/composer.json
```

Check if Debugbar is restricted to non-production:
```
Pattern: (barryvdh/laravel-debugbar|DEBUGBAR_ENABLED)
Glob: **/composer.json, **/.env*, **/config/debugbar.php
```

Check if Horizon dashboard has auth:
```
Pattern: (Horizon::auth|HorizonServiceProvider)
Glob: **/app/Providers/HorizonServiceProvider.php, **/config/horizon.php
```

Check if Pulse dashboard has auth:
```
Pattern: (Pulse::auth|PulseServiceProvider)
Glob: **/app/Providers/*.php, **/config/pulse.php
```

**Why it matters:** Telescope records every request, query, job, and exception — including full request payloads with passwords and tokens. Without an auth gate, anyone can browse this data. Debugbar in production leaks queries and internal state. Horizon and Pulse dashboards expose queue and performance data.

## 3. Source Maps in Production — WARNING

Check Vite config for source map generation:
```
Pattern: (sourcemap|devtool|GENERATE_SOURCEMAP)
Glob: **/vite.config.{js,ts,mjs}
```

Check for source map files that shouldn't be deployed:
```
Glob: **/public/build/**/*.map, **/public/dist/**/*.map
```

**Why it matters:** Source maps expose your original JavaScript/CSS source code, making it easy for attackers to understand client-side logic, find hidden API endpoints, and discover vulnerabilities.

## 4. Session & Cookie Security — WARNING

Read the session configuration:
```
Glob: **/config/session.php
```

Check for secure cookie settings:
```
Pattern: ('secure'|'http_only'|'same_site'|SESSION_SECURE_COOKIE)
Glob: **/config/session.php, **/.env*
```

Verify:
- `secure` is `true` (or tied to HTTPS detection) — cookies only sent over HTTPS
- `http_only` is `true` — prevents JavaScript access to session cookie
- `same_site` is `lax` or `strict` — prevents CSRF via cookies
- `encrypt` is `true` — encrypts cookie contents

**Why it matters:** Laravel sets `http_only` and `same_site` to secure defaults, but `secure` defaults to `env('SESSION_SECURE_COOKIE')` which may be `null` (not secure). In production with HTTPS, explicitly set `SESSION_SECURE_COOKIE=true` or configure `secure => true`.

## 5. Docker Security — WARNING

If Dockerfiles exist, check for:

Non-root user:
```
Pattern: (USER\s+(?!root)|--chown|gosu|su-exec)
Glob: **/Dockerfile*
```

Minimal base image:
```
Pattern: (FROM\s+(php:\d+.*alpine|php:\d+.*fpm|serversideup/php|dunglas/frankenphp))
Glob: **/Dockerfile*
```

Secrets in Docker build:
```
Pattern: (ARG.*SECRET|ARG.*PASSWORD|ARG.*TOKEN|ARG.*KEY|ENV.*SECRET|ENV.*PASSWORD|COPY.*\.env\b)
Glob: **/Dockerfile*
```

.dockerignore exists:
```
Glob: **/.dockerignore
```

**Why it matters:** Running containers as root means a container escape gives attackers root on the host. Copying `.env` into a Docker image bakes secrets into every layer. Use multi-stage builds, non-root users, and mount secrets at runtime.

## 6. Environment Variable Validation — SUGGESTION

Check for startup validation of required env vars:
```
Pattern: (env\(.*\?\?|env\(.*throw|abort_unless.*env|required_env|Config::get\(.*\?\?)
Glob: **/app/Providers/AppServiceProvider.php, **/bootstrap/app.php
```

Check if the app validates critical env vars on boot:
```
Pattern: (throw_unless|abort_if|abort_unless|MissingAppKeyException)
Glob: **/app/Providers/*.php
```

**Why it matters:** Without validation, a missing `APP_KEY` or `DB_HOST` causes cryptic errors deep in your app. Add checks in `AppServiceProvider::boot()` to fail fast with clear messages. Consider packages like `spatie/laravel-settings` for structured configuration validation.

## 7. Dependency Pinning — WARNING

Check that `composer.lock` is committed (not in .gitignore):
```
Glob: **/.gitignore
```

Read `.gitignore` and verify it does NOT include `composer.lock`.

Check for wide version constraints in `composer.json`:
```
Pattern: ("require"[\s\S]*?"\*"|"dev-main"|"dev-master")
Glob: **/composer.json
```

**Why it matters:** Without `composer.lock`, `composer install` on Monday might give you different code than on Friday. A broken upstream update can break your production deploy with no code change on your end. Always commit `composer.lock` and run `composer install --no-dev` in production.

## 8. Trusted Proxies & Hosts — WARNING

Check for trusted proxy configuration:
```
Pattern: (TrustProxies|trustedproxies|TRUSTED_PROXIES|TrustHosts)
Glob: **/app/Http/Middleware/*.php, **/bootstrap/app.php, **/config/*.php
```

If behind a load balancer (AWS ALB, Cloudflare, Nginx reverse proxy), verify `TrustProxies` middleware is configured with the correct proxy IPs — not just `*`.

**Why it matters:** Without proper proxy configuration, `$request->ip()` returns the load balancer's IP instead of the user's real IP. Rate limiting, logging, and geo-blocking all break. Setting `$proxies = '*'` trusts all IPs, which lets attackers spoof their IP via headers.
