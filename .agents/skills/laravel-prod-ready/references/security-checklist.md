# Security Checklist (Laravel)

## 1. Hardcoded Secrets Detection — CRITICAL
**CWE-798: Use of Hard-coded Credentials**

Grep for leaked secrets in source code (exclude vendor, node_modules, .git, lock files):

```
Pattern: (sk_live|sk_test_|AKIA[0-9A-Z]{16}|ghp_[a-zA-Z0-9]{36}|xox[bapors]-|glpat-|sk-[a-zA-Z0-9]{20,}|-----BEGIN (RSA |EC )?PRIVATE KEY-----|eyJ[a-zA-Z0-9_-]*\.eyJ|AIza[0-9A-Za-z-_]{35}|SG\.[a-zA-Z0-9._-]{20,}|sq0atp-[0-9A-Za-z-_]{22,}|AC[a-z0-9]{32}|SK[a-z0-9]{32})
Glob: **/*.{php,env,yml,yaml,json,js,ts,sh,conf}
```

Also check for inline passwords/tokens in config or PHP files:
```
Pattern: (password|secret|token|api_key|apikey|api-key|auth_token|private_key|client_secret)\s*[:=]\s*['"][^'"]{8,}['"]
Glob: **/*.{php,yml,yaml,conf,env}
```

Check for hardcoded values in `config/*.php` that should come from `env()`:
```
Pattern: ['"](?:password|secret|key|token)['"].*=>\s*['"][^'"]+['"](?!.*env\()
Glob: **/config/*.php
```

Exclude `vendor/`, `node_modules/`, `.git/`, lock files, `.env.example`, and test fixtures from all findings.

**Why it matters:** Leaked secrets in source code get committed to git history and are nearly impossible to fully remove. They lead to account takeover and data breaches.

**Recommended tool:** Run `composer require --dev enlightn/security-checker` or use `gitleaks` for comprehensive secret scanning.

## 2. .env in .gitignore — CRITICAL
**CWE-200: Exposure of Sensitive Information**

Read `.gitignore` and verify it includes `.env` (Laravel's default `.gitignore` includes this).

Check if any `.env` files are tracked:
```
Glob: **/.env, **/.env.local, **/.env.production, **/.env.staging
```

If `.env` files exist in the repo, read them and check if they contain real credentials (not the default Laravel placeholder values like `password` or `your-app-key`).

**Why it matters:** `.env` files contain `APP_KEY`, database credentials, mail credentials, API keys, and other secrets. If committed, they're in git history forever.

## 3. SQL Injection — CRITICAL
**CWE-89: SQL Injection**

Raw queries with string interpolation or concatenation:
```
Pattern: (DB::raw\(.*\$|DB::select\(.*\$|DB::statement\(.*\$|->whereRaw\(.*\$|->selectRaw\(.*\$|->orderByRaw\(.*\$|->groupByRaw\(.*\$|->havingRaw\(.*\$|->joinRaw\(.*\$)
Glob: **/*.php
```

Unsafe query methods — check that bindings are used when user input is involved:
```
Pattern: (DB::unprepared|DB::raw\()
Glob: **/app/**/*.php, **/routes/**/*.php
```

**Why it matters:** Eloquent's query builder uses parameterized queries by default, which is safe. But `DB::raw()`, `whereRaw()`, and friends bypass that protection when variables are interpolated directly. Always use the `?` binding syntax: `DB::raw('SELECT * FROM users WHERE id = ?', [$id])`.

## 4. XSS (Cross-Site Scripting) — CRITICAL
**CWE-79: Cross-site Scripting**

Unescaped Blade output:
```
Pattern: \{!!\s*.*\s*!!\}
Glob: **/resources/views/**/*.blade.php
```

Raw HTML rendering in Livewire or Inertia:
```
Pattern: (wire:model\.lazy|v-html|dangerouslySetInnerHTML|\$this->js\()
Glob: **/*.{blade.php,vue,jsx,tsx}
```

**Why it matters:** Blade's `{{ }}` automatically escapes output, but `{!! !!}` renders raw HTML. If user-controlled data flows into `{!! !!}`, attackers can inject scripts. Every `{!! !!}` should be reviewed to ensure only trusted, sanitized content is rendered.

## 5. Code Execution — CRITICAL
**CWE-94: Code Injection**

```
Pattern: (eval\(|assert\(.*\$|preg_replace\(.*\/e|unserialize\(|Process::fromShellCommandline\(.*\$|exec\(|shell_exec\(|system\(|passthru\(|proc_open\(|popen\()
Glob: **/app/**/*.php, **/routes/**/*.php
```

Check for unsafe deserialization:
```
Pattern: (unserialize\(|maybe_unserialize\()
Glob: **/*.php
```

**Why it matters:** `eval()`, `exec()`, and `unserialize()` with user-controlled input give attackers full server access. Laravel's `Process` facade (10+) is safer but still dangerous with unsanitized input. `unserialize()` with untrusted data can trigger arbitrary object instantiation (PHP Object Injection).

## 6. Authentication & Authorization — CRITICAL
**CWE-287: Improper Authentication / CWE-862: Missing Authorization**

Check for authentication setup:
```
Pattern: (Sanctum|Passport|Fortify|Breeze|Jetstream|auth:sanctum|auth:api|Auth::|auth\(\)->|middleware\(.*auth)
Glob: **/*.php
```

Check password hashing (Laravel uses bcrypt by default, but verify):
```
Pattern: (Hash::make|bcrypt\(|Hash::check|password_hash|Argon2)
Glob: **/app/**/*.php
```

Check for authorization — policies, gates, and middleware:
```
Pattern: (->authorize\(|Gate::|Policy|can\(|cannot\(|middleware\(.*can:|@can\b|@cannot\b|$this->authorize)
Glob: **/*.php
```

Check for routes without auth middleware — read `routes/web.php` and `routes/api.php` and look for sensitive endpoints (admin, user data, settings, payments) without `auth` or `auth:sanctum` middleware.

**Why it matters:** Laravel provides excellent auth scaffolding, but it only works if applied consistently. Unprotected routes and missing authorization checks are the most common Laravel security gaps.

## 7. CSRF Protection — WARNING
**CWE-352: Cross-Site Request Forgery**

Verify CSRF middleware is active:
```
Pattern: (VerifyCsrfToken|ValidateCsrfToken|\$except)
Glob: **/app/Http/Middleware/*.php, **/bootstrap/app.php
```

Check for CSRF exclusions that may be too broad:
```
Pattern: \$except\s*=\s*\[
Glob: **/app/Http/Middleware/VerifyCsrfToken.php
```

Check Blade forms include CSRF token:
```
Pattern: (@csrf|csrf_field\(\)|csrf_token\(\))
Glob: **/resources/views/**/*.blade.php
```

**Why it matters:** Laravel includes CSRF protection by default, but if routes are excluded from CSRF verification (via `$except`), those routes are vulnerable. SPA setups using Sanctum need the CSRF cookie flow configured correctly.

## 8. Mass Assignment — WARNING
**CWE-915: Improperly Controlled Modification of Dynamically-Determined Object Attributes**

Check that models define `$fillable` or `$guarded`:
```
Pattern: (protected\s+\$fillable|protected\s+\$guarded)
Glob: **/app/Models/*.php
```

Check for dangerous mass assignment patterns:
```
Pattern: (Model::unguard|->forceFill\(|->forceCreate\()
Glob: **/*.php
```

Check for `$guarded = []` (effectively disabling protection):
```
Pattern: \$guarded\s*=\s*\[\s*\]
Glob: **/app/Models/*.php
```

**Why it matters:** Without `$fillable`, a user can submit extra fields like `is_admin` or `role` in a request body and have them saved to the database. Laravel protects against this with `$fillable`/`$guarded`, but `$guarded = []` disables it entirely.

## 9. Input Validation — WARNING
**CWE-20: Improper Input Validation**

Check for validation usage:
```
Pattern: (\$request->validate\(|Validator::make|FormRequest|->validated\(\)|->rules\(\))
Glob: **/app/**/*.php, **/routes/**/*.php
```

Check for Form Request classes (best practice):
```
Glob: **/app/Http/Requests/*.php
```

Check for controllers that use `$request->all()` or `$request->input()` without validation:
```
Pattern: (\$request->all\(\)|\$request->input\()
Glob: **/app/Http/Controllers/**/*.php
```

**Why it matters:** `$request->validate()` and Form Requests ensure only expected, validated data reaches your application logic. Using `$request->all()` directly passes unvalidated data, enabling injection and logic bypass attacks.

## 10. File Upload Restrictions — WARNING
**CWE-434: Unrestricted Upload of File with Dangerous Type**

```
Pattern: (\$request->file\(|\$request->hasFile|Storage::put|Storage::disk|UploadedFile|storeAs\(|store\()
Glob: **/app/**/*.php
```

If found, verify:
- File type validation (`mimes:`, `mimetypes:` validation rules)
- File size limits (`max:` validation rule)
- Storage on non-public disk (not stored in `public/`)

**Why it matters:** Unrestricted file uploads can allow attackers to upload PHP files that execute on the server, or files that crash processing pipelines.

## 11. Dependency Vulnerabilities — WARNING
**CWE-1395: Dependency on Vulnerable Third-Party Component**

Check that lock files exist:
```
Glob: **/composer.lock, **/package-lock.json, **/yarn.lock, **/pnpm-lock.yaml
```

Check for audit scripts in CI:
```
Pattern: (composer audit|npm audit|yarn audit|snyk|dependabot)
Glob: **/.github/workflows/*.{yml,yaml}, **/composer.json
```

**Why it matters:** Known vulnerabilities in dependencies are one of the easiest attack vectors. Run `composer audit` regularly and configure Dependabot or similar for automatic alerts.

## 12. CORS Configuration — WARNING
**CWE-942: Permissive Cross-domain Policy**

Read the CORS config:
```
Glob: **/config/cors.php
```

Look for overly permissive settings:
```
Pattern: (allowed_origins.*\*|supports_credentials.*true)
Glob: **/config/cors.php
```

**Why it matters:** Laravel's `config/cors.php` controls cross-origin access. Setting `allowed_origins` to `['*']` while `supports_credentials` is `true` allows any website to make authenticated requests to your API.

## 13. Rate Limiting — WARNING
**CWE-770: Allocation of Resources Without Limits**

Check for rate limiter configuration:
```
Pattern: (RateLimiter::for|throttle:|ThrottleRequests|->middleware\(.*throttle)
Glob: **/app/Providers/*.php, **/routes/*.php, **/bootstrap/app.php
```

**Why it matters:** Without rate limiting, attackers can brute-force login pages, scrape data, or overwhelm your API. Laravel provides built-in throttle middleware — just apply it to sensitive routes.

## 14. Security Headers — SUGGESTION

```
Pattern: (Content-Security-Policy|X-Frame-Options|X-Content-Type-Options|Strict-Transport-Security|spatie/laravel-csp|SecureHeaders)
Glob: **/*.php, **/config/*.php
```

**Why it matters:** Security headers prevent clickjacking, XSS, MIME sniffing, and protocol downgrade attacks. Laravel doesn't set these by default — add them via middleware or use `spatie/laravel-csp`.

## 15. SSRF (Server-Side Request Forgery) — WARNING
**CWE-918: Server-Side Request Forgery**

Check for HTTP client calls with user-controlled URLs:
```
Pattern: (Http::(get|post|put|patch|delete|head)\(.*\$|file_get_contents\(.*\$|curl_exec)
Glob: **/app/**/*.php
```

**Why it matters:** If your Laravel app fetches a URL provided by a user (e.g. webhook URLs, avatar URLs, import URLs) without validation, attackers can target internal services like the cloud metadata API (`169.254.169.254`), Redis, or other internal infrastructure.

## 16. Secrets in Frontend Bundles — CRITICAL
**CWE-200: Exposure of Sensitive Information**

Check for secrets exposed via Vite's `VITE_` prefix:
```
Pattern: VITE_.*(SECRET|PASSWORD|PRIVATE|TOKEN|KEY|CREDENTIAL|AUTH)
Glob: **/.env*, **/*.{js,ts,vue}
```

Check that sensitive config isn't shared with Inertia or Blade:
```
Pattern: (Inertia::share|->with\(.*config\(|@json\(.*config\()
Glob: **/*.php, **/resources/views/**/*.blade.php
```

**Why it matters:** Anything passed to the frontend via Vite env vars, Inertia props, or Blade `@json` is visible to every user. Private API keys in client code mean anyone can use your accounts.
