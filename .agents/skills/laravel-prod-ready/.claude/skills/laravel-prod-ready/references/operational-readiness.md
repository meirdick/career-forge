# Operational Readiness Checklist (Laravel)

## 1. CI/CD Pipeline — WARNING

Check for CI/CD configuration:
```
Glob: **/.github/workflows/*.{yml,yaml}, **/.gitlab-ci.yml, **/Jenkinsfile, **/.circleci/config.yml, **/bitbucket-pipelines.yml
```

Check for Laravel-specific deployment tools:
```
Pattern: (forge|envoyer|vapor|deployer|deploy\.php|Envoy\.blade\.php)
Glob: **/composer.json, **/deploy.php, **/Envoy.blade.php, **/serverless.yml
```

**Why it matters:** Without CI/CD, deployments are manual and error-prone. You'll forget to run tests, skip `composer install --no-dev`, or deploy without running migrations. Automated pipelines catch problems before they reach production.

## 2. Tests Run in CI — WARNING

If CI config exists, read it and verify test execution:
```
Pattern: (php artisan test|vendor/bin/pest|vendor/bin/phpunit|phpunit|pest)
Glob: **/.github/workflows/*.{yml,yaml}, **/.gitlab-ci.yml, **/Jenkinsfile, **/.circleci/config.yml
```

Also check that CI runs static analysis:
```
Pattern: (phpstan|larastan|pint|php-cs-fixer)
Glob: **/.github/workflows/*.{yml,yaml}, **/.gitlab-ci.yml
```

**Why it matters:** Having CI but not running tests is worse than no CI — it gives false confidence. Every push should run `php artisan test` and ideally `phpstan analyse` automatically.

## 3. Deployment Configuration — WARNING

Check for deployment infrastructure:
```
Glob: **/Dockerfile, **/docker-compose*.{yml,yaml}, **/Procfile, **/fly.toml, **/render.yaml, **/railway.json, **/serverless.yml, **/deploy.php, **/Envoy.blade.php
```

Check for production optimization commands in deployment:
```
Pattern: (config:cache|route:cache|view:cache|event:cache|optimize|composer install.*--no-dev)
Glob: **/.github/workflows/*.{yml,yaml}, **/deploy.php, **/Envoy.blade.php, **/Dockerfile, **/Procfile
```

Check for migration execution in deployment:
```
Pattern: (migrate\s+--force|migrate.*--force|artisan migrate)
Glob: **/.github/workflows/*.{yml,yaml}, **/deploy.php, **/Envoy.blade.php, **/Dockerfile
```

**Why it matters:** A production Laravel deploy should run: `composer install --no-dev`, `php artisan migrate --force`, `php artisan config:cache`, `php artisan route:cache`, `php artisan view:cache`. Missing any of these means either security risk (dev dependencies deployed), missing schema changes, or degraded performance.

## 4. README with Setup Instructions — SUGGESTION

```
Glob: **/README.md, **/README.rst, **/README.txt
```

If README exists, read it and check for:
- Project description
- Setup/installation instructions (`composer install`, `.env` setup, `php artisan key:generate`)
- How to run locally (`php artisan serve` or Docker setup)
- How to run tests (`php artisan test`)
- How to run queue workers and schedulers

**Why it matters:** Without a README, onboarding takes hours instead of minutes. It's also the first thing you'll need when you come back to this project after 6 months.

## 5. Environment Variables Documented — WARNING

Check for env documentation:
```
Glob: **/.env.example
```

If `.env.example` exists, read it and verify it documents all required variables with placeholder values (not real credentials).

Compare `.env.example` against actual env usage:
```
Pattern: (env\(\s*['"])
Glob: **/config/**/*.php
```

Check if any `env()` calls in config files reference variables not present in `.env.example`.

**Why it matters:** `.env.example` is the contract for what environment variables your app needs. If it's incomplete, new developers or deployment environments have to guess. Missing a critical env var usually means a production crash.

**Important:** `env()` should ONLY be called inside `config/` files, never in application code. After `php artisan config:cache`, `env()` returns `null` outside of config files.

## 6. Error Tracking Integration — SUGGESTION

```
Pattern: (sentry|bugsnag|rollbar|flare|ignition|spatie/laravel-ignition|sentry/sentry-laravel|SENTRY_LARAVEL_DSN|FLARE_KEY)
Glob: **/composer.json, **/config/**/*.php, **/.env.example
```

**Why it matters:** Without error tracking, you only know about errors when users complain. Sentry, Flare (by Spatie — built for Laravel), or Bugsnag give you real-time alerts with full context (request data, user, stack trace) to fix issues before most users notice.

## 7. Rollback Capability — SUGGESTION

Check for versioned or rollback-capable deployments:
```
Pattern: (rollback|previous.*version|blue.?green|canary|rolling.*update|zero-downtime|envoyer|--strategy)
Glob: **/.github/workflows/*.{yml,yaml}, **/deploy.php, **/Envoy.blade.php
```

Check for reversible migrations:
```
Pattern: (function down\(\))
Glob: **/database/migrations/*.php
```

**Why it matters:** When a deploy breaks production, you need to roll back in minutes. Laravel Forge supports instant rollback. Envoyer keeps previous releases for instant switching. Vapor has automatic rollback. If using custom deployment, ensure you have a rollback strategy. Also ensure migrations have `down()` methods so `php artisan migrate:rollback` works.

## 8. Monitoring & Observability — SUGGESTION

Check for monitoring tools:
```
Pattern: (telescope|pulse|horizon|prometheus|grafana|datadog|newrelic|cloudwatch|opentelemetry|TELESCOPE_ENABLED|PULSE_ENABLED)
Glob: **/composer.json, **/config/**/*.php, **/.env.example
```

Check for Laravel Horizon (queue monitoring):
```
Pattern: (laravel/horizon)
Glob: **/composer.json
```

Check for Laravel Pulse (application monitoring, Laravel 10+):
```
Pattern: (laravel/pulse)
Glob: **/composer.json
```

**Why it matters:** Without monitoring, you're flying blind. Laravel Pulse gives you real-time performance metrics (slow queries, slow routes, slow jobs). Horizon gives you queue visibility. External tools like Datadog or New Relic give infrastructure-level monitoring. At minimum, know your response times and error rates.
