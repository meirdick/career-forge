# Report Template

Use this exact structure for the production readiness assessment output.

---

## Output Format

```markdown
# Production Readiness Assessment

**Project:** {project name from composer.json}
**Framework:** Laravel {version from composer.json}
**PHP:** {PHP version constraint}
**Key Packages:** {notable packages — Sanctum, Horizon, Livewire, Inertia, etc.}
**Date:** {current date}

---

## Overall Readiness: {READY | ALMOST | NOT READY}

**Score: {total}/60**

| Dimension | Score | Rating |
|---|---|---|
| Security | {0-10} | {emoji} |
| Scalability | {0-10} | {emoji} |
| Reliability | {0-10} | {emoji} |
| Hardening | {0-10} | {emoji} |
| Code Quality | {0-10} | {emoji} |
| Operational Readiness | {0-10} | {emoji} |

Rating emojis: 9-10 = EXCELLENT, 7-8 = GOOD, 5-6 = OK, 3-4 = CONCERNING, 0-2 = CRITICAL

---

## Critical Issues (Must Fix)

These issues pose serious risk and should be fixed before going to production.

### {issue number}. {issue title} — {dimension}
**Severity:** CRITICAL
**Reference:** {CWE-XXX or OWASP category, if applicable}
**File:** `{file path}:{line number}`
**Finding:** {what was found}
**Why it matters:** {plain language explanation}
**Fix:**
```{language}
{concrete Laravel-specific code fix or configuration change}
```

{repeat for each critical issue}

---

## Warnings (Should Fix)

These issues represent meaningful risk or technical debt.

### {issue number}. {issue title} — {dimension}
**Severity:** WARNING
**Reference:** {CWE-XXX or OWASP category, if applicable}
**File:** `{file path}:{line number}`
**Finding:** {what was found}
**Fix:** {how to fix it — include artisan commands, config changes, or code snippets}

{repeat for each warning}

---

## Suggestions (Nice to Have)

These improvements would strengthen the project but aren't blockers.

### {issue number}. {issue title} — {dimension}
**Severity:** SUGGESTION
**Finding:** {what was found}
**Recommendation:** {what to do}

{repeat for each suggestion}

---

## What You Did Well

{List 3-5 things the project does right. Look for:}
- Proper use of Eloquent (eager loading, scopes, relationships)
- Solid migration structure with indexes and foreign keys
- Sanctum/Passport configured correctly
- Form Request validation on all endpoints
- Queued jobs for heavy operations (implements ShouldQueue)
- Good test coverage with Pest or PHPUnit
- Proper .env.example with all variables documented
- Clean service/action class architecture
- Laravel Horizon for queue management
- Proper use of config caching and route caching

---

## Recommended Next Steps

Based on this assessment, here are the top 5 actions in priority order:

1. **{action}** — {why and estimated effort: quick/medium/significant}
2. **{action}** — {why and estimated effort}
3. **{action}** — {why and estimated effort}
4. **{action}** — {why and estimated effort}
5. **{action}** — {why and estimated effort}

---

## Recommended Tools

Based on the gaps found, consider these tools:
- {tool recommendation if applicable — e.g. "Run `composer audit` to check for known dependency vulnerabilities"}
- {tool recommendation — e.g. "Install `spatie/laravel-health` for comprehensive health checks"}
```

## Scoring Rubric

Use these guidelines when assigning dimension scores:

### Security (0-10)
- **10:** No secrets in code, CSRF active, mass assignment protected, validation on all inputs, rate limiting, CORS configured, auth on all sensitive routes
- **7-8:** Good fundamentals, minor gaps (missing rate limiting on some routes OR no CSP headers)
- **5-6:** Auth works but has gaps, some validation missing, no security headers
- **3-4:** Hardcoded secrets found OR `$guarded = []` on models OR raw SQL with user input
- **0-2:** Multiple critical issues (secrets in code AND unprotected routes AND injection vulnerabilities)

### Scalability (0-10)
- **10:** Eager loading everywhere, pagination on all lists, Redis caching, queued jobs, database indexes, stateless sessions
- **7-8:** Good patterns with minor gaps (one unbounded `->get()` OR missing caching)
- **5-6:** Basic pagination, some eager loading, but sync queue driver or file sessions
- **3-4:** No pagination OR N+1 queries detected OR `QUEUE_CONNECTION=sync` with heavy operations
- **0-2:** Multiple fundamental issues (no pagination AND no eager loading AND file sessions AND sync queue)

### Reliability (0-10)
- **10:** Custom exception handler, structured logging (daily/stderr), health checks, graceful shutdown, retries on HTTP and jobs, transactions, reversible migrations
- **7-8:** Good error handling with minor gaps (missing health check OR no job retry config)
- **5-6:** Basic exception handling exists, logging configured, but no health checks or retry logic
- **3-4:** Default exception handler only OR single-file logging OR no migrations
- **0-2:** Multiple fundamental issues (no error handling AND no logging AND no migrations)

### Hardening (0-10)
- **10:** `APP_DEBUG=false`, secure cookies, Telescope gated, no source maps, env validated, deps pinned, trusted proxies configured
- **7-8:** Good hardening with minor gaps (missing env validation OR no custom error pages)
- **5-6:** Debug disabled but cookies not fully secured OR Telescope auth not configured
- **3-4:** `APP_DEBUG=true` in non-example env OR Telescope exposed OR source maps deployed
- **0-2:** `APP_DEBUG=true` in production AND Telescope/Debugbar exposed AND insecure cookies

### Code Quality (0-10)
- **10:** Comprehensive Pest/PHPUnit tests, Larastan running, no `dd()`/`dump()`, strict types, Form Requests, clean architecture
- **7-8:** Tests exist with decent coverage, linting configured, few debug helpers
- **5-6:** Some tests exist, no static analysis, moderate debug helpers
- **3-4:** No tests OR `dd()`/`dump()` in production code OR god controllers with no structure
- **0-2:** No tests AND lots of dead code AND no linting AND debug helpers everywhere

### Operational Readiness (0-10)
- **10:** CI/CD with tests + static analysis, deployment config with caching commands, `.env.example` complete, error tracking, monitoring (Pulse/Horizon), rollback capability
- **7-8:** CI/CD works with minor gaps (missing monitoring OR incomplete `.env.example`)
- **5-6:** Basic CI exists, deployment works, `.env.example` exists but incomplete
- **3-4:** No CI/CD OR no deployment config OR no `.env.example`
- **0-2:** No CI/CD AND no deployment config AND no documentation

---

## Output Tone Guidelines

These guidelines apply when writing any section of the report:

**Be calibrated, not catastrophizing.** Reserve strong language ("this is a critical security
vulnerability") for actual critical issues like `APP_DEBUG=true` with real credentials in
`.env`, exposed Telescope dashboards, or SQL injection via `DB::raw()` with user input. Use
measured language for common gaps like missing rate limiting or no health check endpoint.

**Lead with the verdict.** Developers scanning this report want to know immediately: can I
deploy this? Give them the overall rating prominently, not buried at the end.

**Quantify when you can.** "14 `dd()` calls found across 8 controllers" is more useful than
"many debug helpers found." File counts, line numbers, and pattern match counts help developers
scope the work.

**The "What You Did Well" section matters.** It's not fluff — it signals to the developer which
patterns are worth keeping, and makes the critical findings feel actionable rather than crushing.
If a developer did nothing right, they'd have a score of 0. Find the genuine positives.

**Fix examples should be copy-pasteable.** Don't show conceptual pseudo-code. Show the actual
Laravel fix. For rate limiting, show the `RateLimiter::for()` call and the middleware
application. For mass assignment, show the `$fillable` array. For session security, show the
exact `config/session.php` values to change.

**Reference CWE/OWASP when relevant.** Including CWE IDs (e.g. CWE-798 for hardcoded
credentials, CWE-89 for SQL injection) helps developers look up the full context and
demonstrates that the finding maps to a known vulnerability class — not just an opinion.
