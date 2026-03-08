# Reliability Checklist (Laravel)

## 1. Exception Handling — CRITICAL

**Laravel 11+ (slim skeleton):**
```
Pattern: (withExceptions|->render\(|->report\(|->renderable\(|->reportable\()
Glob: **/bootstrap/app.php
```

**Laravel 10 and below:**
```
Glob: **/app/Exceptions/Handler.php
```

Read the exception handler and verify:
- Custom rendering for production (not exposing stack traces)
- Reporting to an external service (Sentry, Flare, Bugsnag)
- Proper handling of `ModelNotFoundException`, `AuthenticationException`, `ValidationException`

**Why it matters:** Without proper exception handling, unhandled errors show Laravel's debug page (Ignition) to users in production, or return raw 500 errors with no useful context. Customize exception rendering so users see friendly error pages and your team gets detailed error reports.

## 2. Structured Logging — WARNING

Check logging configuration:
```
Glob: **/config/logging.php
```

Read `config/logging.php` and check:
- What channels are configured (stack, daily, slack, papertrail, stderr)
- Whether the production log channel writes structured output (not just single files that grow forever)

Check for raw debug logging that shouldn't be in production:
```
Pattern: (Log::debug|Log::info|logger\(\)|info\()
Glob: **/app/**/*.php
```

Check for security-relevant event logging (login attempts, permission failures, data access):
```
Pattern: (Log::.*(login|auth|permission|access|fail|denied|unauthorized))
Glob: **/app/**/*.php
```

**Why it matters:** Laravel's default `stack` channel writes to `storage/logs/laravel.log`. In production, this file grows until it fills the disk. Use `daily` (auto-rotates), `stderr` (for containers), or a service like Papertrail/CloudWatch. Also ensure failed login attempts and authorization failures are logged — you can't investigate what you don't record.

## 3. Health Check Endpoints — WARNING

**Laravel 11+** has a built-in `/up` health route. Check if it's present:
```
Pattern: (/up|health|healthCheck|DiagnosingHealth)
Glob: **/routes/*.php, **/bootstrap/app.php
```

For older versions, check for custom health endpoints:
```
Pattern: (health|ping|status|ready)
Glob: **/routes/web.php, **/routes/api.php
```

Check for comprehensive health checks (database, cache, queue):
```
Pattern: (spatie/laravel-health|HealthCheck|DB::select\(.*SELECT 1|Cache::has|Queue::size)
Glob: **/composer.json, **/app/**/*.php
```

**Why it matters:** Load balancers, container orchestrators, and monitoring systems need health check endpoints to know if your app is running. A basic `/up` is fine for liveness, but a full health check that tests database and cache connectivity catches more issues. Consider `spatie/laravel-health` for comprehensive checks.

## 4. Graceful Shutdown — WARNING

Check for queue worker graceful shutdown (Horizon handles this automatically):
```
Pattern: (horizon|queue:work.*--timeout|stopWhenEmpty|SIGTERM|pcntl_signal)
Glob: **/config/horizon.php, **/app/**/*.php, **/Procfile, **/docker-compose*
```

Check for long-running job timeout configuration:
```
Pattern: (public\s+\$timeout|->timeout\(|retry_after)
Glob: **/app/Jobs/**/*.php, **/config/queue.php
```

**Why it matters:** When you deploy a new version, queue workers need to finish their current job before shutting down. Without graceful shutdown, jobs get interrupted mid-execution, leaving data in inconsistent states. Laravel Horizon handles this automatically. If running `queue:work` directly, ensure your process manager sends `SIGTERM` and the worker respects it.

## 5. Retry Logic & Timeouts — WARNING

Check HTTP client timeout and retry configuration:
```
Pattern: (Http::timeout|Http::retry|Http::connectTimeout|->timeout\(|->retry\()
Glob: **/app/**/*.php
```

Check job retry configuration:
```
Pattern: (public\s+\$tries|public\s+\$backoff|public\s+\$maxExceptions|retryUntil\(\)|failed\(\))
Glob: **/app/Jobs/**/*.php
```

Check for failed job handling:
```
Pattern: (queue:failed|failed_jobs|FailedJobProviderInterface)
Glob: **/database/migrations/*.php, **/config/queue.php
```

**Why it matters:** External APIs go down. Without timeouts on `Http::get()`, your app hangs forever. Without retries on queued jobs, transient failures become permanent. Laravel's HTTP client supports `Http::retry(3, 100)` and jobs support `$tries`, `$backoff`, and `failed()` methods — use them.

## 6. Database Transactions — WARNING

Check for transaction usage:
```
Pattern: (DB::transaction|DB::beginTransaction|DB::commit|DB::rollBack)
Glob: **/app/**/*.php
```

Look for multi-step operations that should be transactional:
```
Pattern: (->save\(\).*->save\(\)|->create\(.*->create\(|->update\(.*->update\(|->delete\(.*->delete\()
Glob: **/app/**/*.php
```

**Why it matters:** If a multi-step operation fails halfway through (create order + deduct inventory + charge payment), you end up with inconsistent data. Wrap related writes in `DB::transaction(function () { ... })` to ensure all-or-nothing execution.

## 7. Database Migrations — WARNING

Check for migration files:
```
Glob: **/database/migrations/*.php
```

Check for raw SQL schema changes (should use migrations instead):
```
Pattern: (DB::statement.*ALTER|DB::statement.*DROP|DB::statement.*CREATE TABLE)
Glob: **/app/**/*.php, **/routes/**/*.php
```

Verify migrations are reversible (have `down()` methods):
```
Pattern: (function down\(\))
Glob: **/database/migrations/*.php
```

**Why it matters:** Without migrations, database schema changes require manual SQL on production servers — error-prone, unreproducible, and impossible to roll back. Laravel migrations are versioned and can be rolled back with `php artisan migrate:rollback`.

## 8. Scheduled Tasks — SUGGESTION

Check for scheduled tasks:
```
Glob: **/app/Console/Kernel.php, **/routes/console.php
```

```
Pattern: (->schedule\(|Schedule::command|->cron\(|->daily\(|->hourly\(|->everyMinute\(|schedule:run)
Glob: **/app/**/*.php, **/routes/console.php, **/bootstrap/app.php
```

If scheduled tasks exist, verify the scheduler is running in production:
```
Pattern: (schedule:run|schedule:work|cron)
Glob: **/Procfile, **/docker-compose*, **/.github/workflows/*.{yml,yaml}, **/supervisor*.conf
```

**Why it matters:** Laravel's scheduler only works if `php artisan schedule:run` is called every minute via cron or a process manager. If you define scheduled tasks but never run the scheduler in production, they silently never execute — no errors, no logs, just missing functionality.
