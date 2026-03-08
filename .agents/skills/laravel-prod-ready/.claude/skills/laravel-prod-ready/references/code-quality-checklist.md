# Code Quality Checklist (Laravel)

## 1. Test Coverage Exists — WARNING

Check for test directories and files:
```
Glob: **/tests/**/*.php, **/tests/Feature/**/*.php, **/tests/Unit/**/*.php
```

Check for test runner config:
```
Glob: **/phpunit.xml, **/phpunit.xml.dist, **/pest.php
```

Check for test scripts in composer.json:
```
Pattern: ("test"|"pest"|"phpunit"|"php artisan test")
Glob: **/composer.json
```

Check which testing framework is used:
```
Pattern: (pestphp/pest|phpunit/phpunit)
Glob: **/composer.json
```

**Why it matters:** No tests means every deploy is a leap of faith. You can't refactor safely, you can't catch regressions, and you can't verify bug fixes. Even minimal feature tests for critical paths (auth, payments, core business logic) dramatically reduce risk.

## 2. Debug Helpers in Production Code — WARNING

Count occurrences of debug helpers:
```
Pattern: (\bdd\(|\bdump\(|\bray\(|var_dump\(|print_r\(|error_log\(|echo\s+\$)
Glob: **/app/**/*.php, **/routes/**/*.php, **/resources/views/**/*.blade.php
```

Check for debug logging that may be excessive:
```
Pattern: (Log::debug\(|logger\(\)->debug\(|info\()
Glob: **/app/**/*.php
```

Exclude test files from this count. A few `Log::debug()` calls are fine; `dd()` and `dump()` should never be in production code.

**Why it matters:** `dd()` halts execution and dumps data to the browser — if it reaches production, it breaks the page for users and leaks internal data. `dump()` outputs debug data into the response. `ray()` calls should be removed or guarded. These are development tools, not production code.

## 3. TODO/FIXME/HACK Comments — SUGGESTION

```
Pattern: (TODO|FIXME|HACK|XXX|TEMP|TEMPORARY|WORKAROUND)
Glob: **/app/**/*.php, **/routes/**/*.php, **/config/**/*.php, **/database/**/*.php, **/resources/views/**/*.blade.php
```

Count total occurrences and note any in critical paths (auth, payments, data handling).

**Why it matters:** These comments mark known technical debt. A few TODOs are normal; many in critical code paths suggest unfinished work being shipped.

## 4. Dead Code — SUGGESTION

Commented-out code blocks:
```
Pattern: (^\s*//\s*(public |protected |private |function |class |Route::|use |return |if \(|foreach))
Glob: **/app/**/*.php, **/routes/**/*.php
```

Unused imports:
```
Pattern: (^use\s+)
Glob: **/app/**/*.php
```

Unused route definitions (routes to controllers/actions that don't exist):
```
Pattern: (Route::(get|post|put|patch|delete)\()
Glob: **/routes/*.php
```

**Why it matters:** Dead code adds noise, confuses developers, and can introduce subtle bugs when it accidentally gets uncommented. Run `composer require --dev laravel/pint` and consider static analysis to detect unused code.

## 5. Type Safety & Static Analysis — SUGGESTION

Check for PHP strict types declaration:
```
Pattern: (declare\(strict_types\s*=\s*1\))
Glob: **/app/**/*.php
```

Check for static analysis tools:
```
Pattern: (larastan|phpstan|psalm|nunomaduro/larastan|phpstan/phpstan)
Glob: **/composer.json
```

Check for static analysis config:
```
Glob: **/phpstan.neon, **/phpstan.neon.dist, **/psalm.xml
```

Check for return type declarations on controller methods:
```
Pattern: (public function \w+\(.*\)(?!.*:))
Glob: **/app/Http/Controllers/**/*.php
```

**Why it matters:** PHP is dynamically typed, which means type errors surface at runtime — in production. `declare(strict_types=1)` catches type coercion bugs. Larastan (PHPStan for Laravel) catches null reference errors, incorrect method calls, and type mismatches before deployment.

## 6. Error Response Exposure — WARNING

Check if `APP_DEBUG` is not hardcoded to true in config:
```
Pattern: ('debug'\s*=>\s*true)
Glob: **/config/app.php
```

Check for custom error pages:
```
Glob: **/resources/views/errors/404.blade.php, **/resources/views/errors/500.blade.php, **/resources/views/errors/503.blade.php
```

Check for stack trace exposure in API responses:
```
Pattern: (->trace\(|->getTraceAsString|exception.*getMessage|err.*message.*stack)
Glob: **/app/**/*.php
```

**Why it matters:** Stack traces in error responses reveal file paths, library versions, and internal architecture. In production, users should see friendly error pages (or clean JSON error responses for APIs), not raw exception output. Create custom error views in `resources/views/errors/`.

## 7. Structural Anti-Patterns — SUGGESTION

**God controllers** — controllers with too many methods or too much logic:
```
Pattern: (public function \w+)
Glob: **/app/Http/Controllers/**/*.php
```
Read large controllers (500+ lines) and check if business logic should be extracted into services, actions, or jobs.

**Business logic in routes** — complex logic directly in route closures:
```
Pattern: (Route::(get|post|put|patch|delete)\(.*function\s*\()
Glob: **/routes/*.php
```

**Missing Form Requests** — validation in controllers instead of dedicated request classes:
```
Pattern: (\$request->validate\()
Glob: **/app/Http/Controllers/**/*.php
```
If there are many inline `$request->validate()` calls, suggest extracting to Form Request classes for reusability and cleaner controllers.

**Raw queries when Eloquent suffices:**
```
Pattern: (DB::select\(|DB::insert\(|DB::update\(|DB::delete\()
Glob: **/app/**/*.php
```

**Why it matters:** Laravel provides excellent patterns for organizing code — Form Requests, service classes, Eloquent scopes, jobs, events. When these patterns are ignored, code becomes hard to test, hard to maintain, and prone to bugs. A 1000-line controller is a sign that logic should be distributed.

## 8. Code Style & Linting — SUGGESTION

Check for a code style tool:
```
Pattern: (laravel/pint|php-cs-fixer|squizlabs/php_codesniffer)
Glob: **/composer.json
```

Check for style config:
```
Glob: **/pint.json, **/.php-cs-fixer.php, **/.php-cs-fixer.dist.php, **/phpcs.xml
```

**Why it matters:** Consistent code style reduces cognitive load during code review and makes the codebase more approachable. Laravel Pint is zero-config and follows Laravel's conventions by default. Run it in CI to enforce standards automatically.
