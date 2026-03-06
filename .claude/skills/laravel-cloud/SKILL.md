# Laravel Cloud CLI

Manage the CareerForge production deployment on Laravel Cloud using the `cloud` CLI.

## Application Details

- **App name:** `career-forge`
- **Environment:** `master`
- **URL:** https://resume-forge.laravel.cloud
- **Push-to-deploy:** enabled (pushes to `master` auto-deploy)

## Common Operations

### Deploy

```bash
cloud deploy career-forge master --no-interaction
```

Push-to-deploy is enabled, so `git push` to master triggers a deploy automatically. Use `cloud deploy` only when you need to manually trigger a deploy without pushing.

### Monitor a deployment

```bash
cloud deploy:monitor --no-interaction
```

### Run artisan commands on production

```bash
cloud command:run master --cmd="php artisan migrate --force" --no-interaction
```

Any artisan command can be run this way:

```bash
cloud command:run master --cmd="php artisan <command>" --no-interaction
```

### View production logs

```bash
cloud environment:logs master --no-interaction
```

### Check environment status

```bash
cloud environment:get master --json --no-interaction
```

### List recent deployments

```bash
cloud deployment:list master --json --no-interaction
```

### Manage environment variables

```bash
# View current variables (included in environment:get output)
cloud environment:get master --json --no-interaction

# Replace all variables from a file
cloud environment:variables master --no-interaction
```

### Background processes (workers)

```bash
# List background processes
cloud background-process:list master --no-interaction

# Create a queue worker
cloud background-process:create master --no-interaction
```

## Safety Rules

- Always pass `--no-interaction` to all cloud CLI commands
- Never run destructive database commands (`migrate:fresh`, `db:wipe`) without explicit user approval
- Always confirm before modifying environment variables
- Use `--json` flag when you need to parse output programmatically
- The deploy command in Cloud is `php artisan migrate --force` — this runs automatically on every deploy
