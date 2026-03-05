# CareerForge

## Requirements

- PHP 8.5+
- Node.js 25+
- Composer
- Python 3 (for RenderCV PDF generation)

## RenderCV Setup

CareerForge uses [RenderCV](https://github.com/sinaatalay/rendercv) to generate LaTeX-quality PDF resumes. If RenderCV is not available, the system falls back to DomPDF automatically.

### Local Development

```bash
python3 -m venv vendor/rendercv-venv
vendor/rendercv-venv/bin/pip install "rendercv[full]"
```

Set the path in `.env`:

```
RENDERCV_PATH=/path/to/venv/bin/rendercv
```

### Laravel Cloud Deployment

Add a build step to install Python dependencies. In your `cloud.yaml` or build commands configuration:

```yaml
build:
  steps:
    - name: Install Python and RenderCV
      commands:
        - apt-get update && apt-get install -y python3 python3-venv
        - python3 -m venv /app/rendercv-venv
        - /app/rendercv-venv/bin/pip install "rendercv[full]"
```

Set the environment variable in Laravel Cloud:

```
RENDERCV_PATH=/app/rendercv-venv/bin/rendercv
```

If Python/RenderCV cannot be installed in your environment, the system will automatically fall back to DomPDF for PDF generation. No configuration changes are needed — the fallback is seamless.
