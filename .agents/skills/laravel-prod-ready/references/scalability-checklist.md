# Scalability Checklist (Laravel)

## 1. Database Indexing — WARNING

Check migration files for index definitions:
```
Pattern: (->index\(|->unique\(|->primary\(|->foreign\(|->fullText\(|Schema::create.*->index|DB::statement.*CREATE INDEX)
Glob: **/database/migrations/*.php
```

Check for commonly un-indexed patterns — foreign keys and frequently queried columns:
```
Pattern: (->foreignId\(|->foreignIdFor\(|->references\(|->constrained\()
Glob: **/database/migrations/*.php
```

Read migration files and verify that columns used in `where()`, `orderBy()`, and `groupBy` queries have indexes. Foreign key columns created with `->foreignId()` get an index automatically, but other frequently filtered columns may not.

**Why it matters:** Missing indexes on frequently queried columns cause full table scans. A query that takes 5ms with 1,000 rows takes 5 seconds with 1,000,000 rows. Check your slowest queries with `DB::listen()` or Laravel Debugbar.

## 2. N+1 Queries — WARNING

Check for eager loading usage (good sign):
```
Pattern: (->with\(|->load\(|->loadMissing\(|->withCount\(|->loadCount\()
Glob: **/app/**/*.php
```

Check if lazy loading prevention is enabled (Laravel 9+):
```
Pattern: (Model::preventLazyLoading|preventLazyLoading\()
Glob: **/app/Providers/*.php, **/bootstrap/app.php
```

Look for relationship access patterns that suggest N+1 issues — accessing relationships in Blade loops without eager loading:
```
Pattern: (@foreach.*->(\w+)\b)
Glob: **/resources/views/**/*.blade.php
```

**Why it matters:** N+1 queries turn one database call into hundreds. Loading a page with 50 users that each display their role makes 51 queries instead of 2. Enable `Model::preventLazyLoading()` in `AppServiceProvider` to catch these during development. Use `->with('relationship')` to eager load.

**Recommended tool:** Install `barryvdh/laravel-debugbar` in development to see all queries per request.

## 3. Connection Pooling — WARNING

Check database config for connection settings:
```
Glob: **/config/database.php
```

Read `config/database.php` and check for:
- Connection pool settings (relevant for Octane or Swoole)
- Persistent connections (`'options' => [PDO::ATTR_PERSISTENT => true]`)

```
Pattern: (ATTR_PERSISTENT|pool|pgbouncer|DB_POOL_SIZE|octane)
Glob: **/config/database.php, **/.env*, **/config/octane.php
```

**Why it matters:** Standard Laravel creates a new database connection per request (fine for PHP-FPM). But with Laravel Octane, connections persist across requests and can exhaust the pool. If using PgBouncer or connection pooling, configure it in `config/database.php`.

## 4. Pagination on List Endpoints — WARNING

Check for pagination usage (good sign):
```
Pattern: (->paginate\(|->simplePaginate\(|->cursorPaginate\(|->fastPaginate\()
Glob: **/app/**/*.php
```

Check for unbounded queries that should be paginated:
```
Pattern: (->get\(\)|->all\(\)|::all\(\))
Glob: **/app/Http/Controllers/**/*.php, **/app/Livewire/**/*.php
```

**Why it matters:** Returning all records from a table works with 100 rows but crashes your server with 100,000 rows. Every list endpoint and Livewire table needs pagination. Use `->paginate()` for page-number pagination or `->cursorPaginate()` for infinite scroll.

## 5. Caching Setup — SUGGESTION

Check cache configuration:
```
Glob: **/config/cache.php
```

Check for cache usage in the application:
```
Pattern: (Cache::|cache\(\)|remember\(|rememberForever\(|Cache::store|@cache|cache\.store)
Glob: **/app/**/*.php, **/config/cache.php, **/.env*
```

Check the cache driver (should not be `file` or `array` in production):
```
Pattern: CACHE_STORE|CACHE_DRIVER
Glob: **/.env.example
```

**Why it matters:** Laravel defaults to the `file` cache driver, which is fine for development but doesn't scale. In production, use Redis or Memcached. Cache expensive queries, config (`php artisan config:cache`), routes (`php artisan route:cache`), and views (`php artisan view:cache`).

## 6. Asset Bundling — SUGGESTION

Check for Vite configuration (Laravel 9+):
```
Glob: **/vite.config.{js,ts,mjs}, **/resources/js/app.{js,ts}, **/resources/css/app.css
```

Check for the Vite directive in layouts:
```
Pattern: (@vite\(|@viteReactRefresh)
Glob: **/resources/views/**/*.blade.php
```

**Why it matters:** Laravel uses Vite for asset bundling by default. Ensure `npm run build` is part of your deployment process. Unminified, uncompressed assets slow page loads significantly.

## 7. Background Job Processing — WARNING

Check for queued jobs (good sign):
```
Pattern: (implements ShouldQueue|dispatch\(|Bus::dispatch|Queue::push|->onQueue\(|->delay\()
Glob: **/app/**/*.php
```

Check queue configuration:
```
Glob: **/config/queue.php
```

Check the queue driver (should not be `sync` in production):
```
Pattern: QUEUE_CONNECTION
Glob: **/.env.example, **/config/queue.php
```

Check for heavy operations in HTTP request handlers that should be queued:
```
Pattern: (Mail::send|Mail::to|Notification::send|->notify\(|Storage::put.*upload|Http::post|PDF|Excel|Import|Export)
Glob: **/app/Http/Controllers/**/*.php, **/routes/**/*.php
```

Check for queue worker / Horizon setup:
```
Pattern: (horizon|supervisor|queue:work|queue:listen)
Glob: **/.github/workflows/*.{yml,yaml}, **/Procfile, **/docker-compose*, **/supervisor*.conf, **/config/horizon.php
```

**Why it matters:** Sending emails, processing uploads, generating reports, or calling external APIs in a request handler blocks the response and can timeout. Use `implements ShouldQueue` on jobs, mailables, and notifications. Run `php artisan queue:work` or install Laravel Horizon for queue management.

## 8. Stateless Design — WARNING

Check the session driver:
```
Pattern: SESSION_DRIVER|SESSION_STORE
Glob: **/.env.example, **/config/session.php
```

Read `config/session.php` and check the driver. `file` sessions break with multiple servers:
```
Pattern: ('driver'.*=>.*'file')
Glob: **/config/session.php
```

Check for local filesystem state that won't persist across deploys:
```
Pattern: (Storage::disk\('local'\)|storage_path\()
Glob: **/app/**/*.php
```

**Why it matters:** If your app stores sessions on the local filesystem (`SESSION_DRIVER=file`), it breaks when you scale to multiple servers — users get logged out randomly. Use `redis`, `database`, or `cookie` session drivers in production. Similarly, user-uploaded files should go to S3 or similar, not `storage/app`.
