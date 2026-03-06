# CareerForge

**Your career is more than one page.**

CareerForge is an open-source, AI-powered career management platform that helps you build a rich professional narrative — not just another resume. It maintains a living library of your skills, experiences, and accomplishments, then intelligently tailors resumes, cover letters, and application materials for each opportunity.

**Live at [resume-forge.laravel.cloud](https://resume-forge.laravel.cloud/)**

## Features

### Experience Library
Your career's single source of truth. Capture skills, experiences, accomplishments, projects, education, and evidence — organized with tags and searchable. Import from existing resumes or build from scratch. Supports voice interviews to extract experiences conversationally.

### Job Analysis Engine
Paste a job posting and CareerForge analyzes it using AI — extracting requirements, identifying key qualifications, and building an ideal candidate profile. Researches the company automatically to inform your application strategy.

### Gap Analysis
Compares your Experience Library against a job's requirements to identify gaps. Offers AI-powered reframing suggestions to help you articulate transferable skills and bridge perceived gaps.

### Resume Generation
Generates tailored resumes by selecting the most relevant experiences for each job. Supports multiple professional templates powered by [RenderCV](https://github.com/sinaatalay/rendercv) for LaTeX-quality PDFs, with DomPDF as an automatic fallback.

### Application Tracker
Manage your job applications through a visual pipeline with stages. Generate targeted cover letters and follow-up emails. Track notes and status for each opportunity.

### Career Chat
AI-powered coaching sessions for gap closure strategies, application support, and career guidance — all grounded in your actual experience data.

### Transparency Pages
A companion page you can share alongside your application, showing exactly how AI assisted in creating your materials. Radical transparency as a feature, not a liability.

## AI Access Modes

CareerForge supports two operating modes controlled by a single environment variable:

### Self-Hosted Mode (default)
```
AI_GATING_MODE=selfhosted
```
All AI features use your server-configured API keys with zero gating. No billing, no limits, no BYOK UI. Perfect for personal use or private deployments. This is the default — just set your API keys in `.env` and go.

### Gated Mode (public deployment)
```
AI_GATING_MODE=gated
```
Designed for multi-user public deployments. Users access AI features through three tiers:

- **Free Tier** — New users can analyze 1 job posting and upload 3 documents to try the platform
- **Bring Your Own Key (BYOK)** — Users add their own API key (Anthropic, OpenAI, Gemini, or Groq) in settings. All AI calls route through their key with no credit cost
- **Credits** — Users purchase credit packs ($5 = 500 credits) via [Polar](https://polar.sh). New signups receive a bonus to get started

#### Credit Costs

| Action | Credits |
|---|---|
| Resume Generation | 100 |
| Transparency Page | 100 |
| Resume Parsing | 50 |
| Job Analysis | 50 |
| Gap Analysis | 50 |
| Cover Letter | 25 |
| Experience Extract | 15 |
| Gap Reframe | 15 |
| Link Indexing | 15 |
| Chat Message | 2 |
| Content Enhance | 2 |

A full pipeline (parse + analyze + gap + resume + cover letter) costs 275 credits.

## Tech Stack

- **Backend:** Laravel 12, PHP 8.5+
- **Frontend:** React 19, Inertia.js v2, TypeScript, Tailwind CSS v4
- **AI:** Laravel AI SDK with Anthropic, OpenAI, Gemini, Groq, Cohere, and Jina providers
- **Auth:** Laravel Fortify (login, registration, 2FA, email verification)
- **Search:** Laravel Scout
- **PDF:** RenderCV (LaTeX) with DomPDF fallback
- **Payments:** Polar.sh (gated mode only)
- **Testing:** Pest v4

## Requirements

- PHP 8.5+
- Node.js 25+
- Composer
- Python 3 (optional — for RenderCV PDF generation)

## Installation

```bash
git clone https://github.com/meirdick/career-forge.git
cd career-forge

composer install
npm install

cp .env.example .env
php artisan key:generate
```

### Configure AI Providers

Add at least one AI provider key to `.env`:

```
ANTHROPIC_API_KEY=your-key
# Optional additional providers:
GEMINI_API_KEY=
OPENAI_API_KEY=
```

### Database Setup

```bash
php artisan migrate
```

SQLite works out of the box for development. Configure Postgres or MySQL via `DB_CONNECTION` in `.env` for production.

### RenderCV (Optional)

For LaTeX-quality PDF resumes:

```bash
python3 -m venv vendor/rendercv-venv
vendor/rendercv-venv/bin/pip install "rendercv[full]"
```

Set in `.env`:
```
RENDERCV_PATH=vendor/rendercv-venv/bin/rendercv
```

If RenderCV is not available, the system falls back to DomPDF automatically.

### Run

```bash
# Development
composer run dev

# Or manually:
php artisan serve
npm run dev
php artisan queue:work
```

## Testing

```bash
php artisan test --compact
```

## Gated Mode Setup

To run CareerForge as a public multi-user platform:

1. Set `AI_GATING_MODE=gated` in `.env`
2. Create a credit pack product on [Polar](https://polar.sh)
3. Configure Polar credentials:
   ```
   POLAR_API_KEY=your-key
   POLAR_WEBHOOK_SECRET=your-secret
   POLAR_CREDIT_PACK_PRODUCT_ID=your-product-id
   ```
4. Optionally configure signup bonus and promo codes:
   ```
   AI_SIGNUP_BONUS=250
   AI_PROMO_CODE=YOUR_CODE
   AI_PROMO_CODE_CREDITS=500
   ```

## License

CareerForge is source-available under a [Non-Commercial License](LICENSE). You are free to self-host, modify, and use it for personal purposes. Commercial use — including offering it as a paid service — is not permitted.
