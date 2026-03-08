---
name: growth-product-context
description: "Discovers user journeys by scanning routes, controllers, and models, then proposes a product context with event names. Activates when mapping journeys, defining product context, creating PRODUCT.md, discovering what to track, or when the user mentions product context, journey mapping, or route discovery."
license: MIT
compatibility: Requires a Laravel application and Laravel Boost v2+ (provides list-routes, database-schema, and tinker tools).
compatible_agents:
  - Claude Code
  - Cursor
  - Windsurf
  - Amp
  - Goose
  - Gemini CLI
  - OpenCode
  - Roo Code
tags:
  - laravel
  - php
  - posthog
  - analytics
  - growth
  - product-analytics
metadata:
  author: growth-engineering
  version: "1.0"
---

# Product Context — Journey Discovery Engine

> Free skill from [Growth Engineering](https://growengine.dev). For auto-instrumentation, auditing, and MCP tools, install the full package.

## Context

This skill scans a Laravel application's routes, controllers, and Eloquent models to discover user journeys and propose a product context with event names following the AARRR (Pirate Metrics) framework.

**Scope:** Laravel applications using Eloquent, Blade, Inertia, or Livewire. Outputs `.growth/PRODUCT.md` and per-domain journey files.

**Activate when:**

- The user asks to "map journeys", "define product context", or "what should I track"
- `.growth/PRODUCT.md` exists but still has placeholder values (e.g., `[Your Product Name]`)
- The user wants to discover or define their product's user journeys
- The user wants to create a `.growth/` directory with product context files

**Prerequisites:**

1. A Laravel application with routes, controllers, and models
2. Laravel Boost installed (provides the `list-routes`, `database-schema`, and `tinker` tools used below)

If `.growth/PRODUCT.md` doesn't exist yet, create it during Step 10.

---

## Rules

### Key Principles

1. **Propose, don't ask** — Always present a concrete proposal based on what you discover in the codebase. Never show blank templates or ask the user to fill in fields from scratch.
2. **Confirm per domain** — Present each journey domain (Activation, Value Delivery, etc.) separately and get confirmation before moving on.
3. **Incremental refinement** — Start with what the code reveals, then let the user correct and refine.

### Step 1: Discover Routes

Read the application's route files to understand what endpoints exist:

1. Read `routes/web.php` and `routes/api.php`
2. Check for Folio page-based routing in `resources/views/pages/`
3. Use the `list-routes` tool from Laravel Boost to get the full route table
4. Present a summary table to the user (see Examples below)

### Step 2: Map Controllers

For each route group, read the associated controllers to understand what actions exist:

1. Identify the controller for each route
2. Note which models are referenced (created, updated, deleted)
3. Note any events fired, jobs dispatched, or notifications sent
4. Note form request classes used (these reveal important user inputs)

### Step 3: Identify Domain Entities

Read the application's Eloquent models to understand the domain:

1. List all models in `app/Models/`
2. Read each model's relationships, casts, and scopes
3. Use the `database-schema` tool to inspect table structures
4. Identify the **central model** — the model with the most relationships and the one users interact with most frequently

Ask the user to confirm or correct the central model.

### Step 4: Detect Frontend Stack

Check which frontend technologies are in use:

1. Check `composer.json` for `inertiajs/inertia-laravel` or `livewire/livewire`
2. Check `package.json` for `@inertiajs/react`, `@inertiajs/vue3`, or `@inertiajs/svelte`
3. Check for Blade views in `resources/views/`
4. Report the detected stack

### Step 5: Classify Routes into Journey Domains

Domains follow the **AARRR (Pirate Metrics)** framework: Acquisition → Activation → Retention → Revenue → Referral.

Use the following rule table to classify routes into journey domains:

| Route Pattern | Journey Domain | AARRR Stage |
|--------------|----------------|-------------|
| `/`, `landing`, `pricing`, `about`, `features`, `blog*`, public marketing pages | Acquisition | Acquisition |
| `register`, `signup`, `verify-email` | Activation | Activation |
| `onboard*`, `welcome`, `getting-started`, first core action | Activation | Activation |
| `login`, `logout`, `password/*`, `two-factor*` | Authentication (supports Activation & Retention) | — |
| Core resource CRUD (the central model's routes) | Value Delivery | Retention |
| `dashboard`, `home`, `feed`, `overview` | Value Delivery (entry point) | Retention |
| Secondary resource CRUD | Engagement | Retention |
| `settings`, `profile`, `account`, `preferences`, `notifications` | Engagement | Retention |
| `billing`, `subscribe`, `checkout`, `payment`, `plan*`, `pricing` | Monetization | Revenue |
| `invite*`, `share*`, `team*`, `referral*`, `export*` | Expansion | Referral |
| `admin/*`, `health`, `up`, `status`, `horizon`, `telescope` | Internal (skip) | — |
| `api/*` | Mirror of web domain (classify by resource) | — |

**Key distinction between Acquisition and Activation:**
- **Acquisition** = the user arrives (anonymous visitor). Track: landing page views, blog views, traffic source, UTM parameters, referral. These events fire *before* any authentication.
- **Activation** = the user signs up and reaches the "aha moment". Track: registration, email verification, onboarding steps, first core action. The boundary between acquisition and activation is the registration event.

Present the classified routes grouped by domain. Ask the user to confirm or reassign any misclassified routes.

### Step 6: Propose Journeys

For each confirmed domain, propose a journey as an ASCII flow diagram with specific event names derived from the controllers (see Examples below).

Present each journey and ask:
- "Does this flow match how users actually use the product?"
- "Are there any steps I'm missing?"
- "Should any events be renamed?"

### Step 7: Identify Core Action

Based on the central model and the value delivery journey, propose the core action. Ask the user to confirm or provide an alternative.

### Step 8: Detect Usage Pattern

Examine the codebase for signals that reveal how frequently users interact with the product. This determines which metrics are meaningful.

| Signal in Codebase | Usage Pattern | Why |
|---------------------|--------------|-----|
| Notification/reminder models, streak counters, daily metric tables, `last_active_at` on users | **Daily Habitual** | Product expects users back every day |
| Date-range models (`start_date`/`end_date`), trip/project/event with lifecycle states, milestone tracking | **Episodic** | Users have intense windows then go dormant |
| Order/transaction/payment models, cart/checkout, booking system, invoice tables | **Transactional** | Each interaction is an independent value exchange |
| Season/period fields, tax-year, annual-cycle, holiday/calendar models | **Seasonal** | Usage follows predictable calendar patterns |
| Content library, playback/progress tracking, watch/read history, recommendation models | **Consumption** | Users consume content on an ongoing basis |

Present the detected pattern with evidence. Ask the user to confirm or correct.

### Step 9: Define Success Metric

Based on the core action AND the detected usage pattern, propose a success metric. **Do not default to Weekly Active Users** — choose the metric that fits the pattern.

**Daily Habitual:** DAU with DAU/MAU stickiness ratio (target 0.3+)
**Episodic:** Active Window Engagement Rate + Repeat {central_model} Rate
**Transactional:** Repeat Transaction Rate + Transactions per Active User
**Seasonal:** Season-over-Season Retention + Seasonal Completion Rate
**Consumption:** Consumption Hours per Active User + DAU/MAU stickiness

Present the pattern-appropriate metric and ask the user to confirm.

### Step 10: Write Results

After the user confirms all domains, journeys, core action, and success metric:

1. **Create `.growth/` directory** if it doesn't exist:
   - Create subdirectories: `journeys/`, `experiments/`, `research/`, `decisions/`, `channels/`, `segments/`, `digests/`
2. **Write `.growth/PRODUCT.md`** — Fill in the product context with confirmed values (see Examples below for format)
3. **Create journey files** in `.growth/journeys/` — One file per domain (see Examples below for format)

---

## Examples

### Route discovery summary

```
Found 42 routes across web and API:

| Domain     | Routes | Examples                          |
|------------|--------|-----------------------------------|
| Auth       | 8      | /login, /register, /password/reset |
| Dashboard  | 3      | /dashboard, /dashboard/stats       |
| Resources  | 18     | /projects/*, /tasks/*              |
| Billing    | 5      | /billing, /subscribe, /checkout    |
| Settings   | 6      | /settings/*, /profile              |
| API        | 12     | /api/v1/projects, /api/v1/tasks    |
```

### Controller mapping summary

```
Key actions found:

- ProjectController: create, update, archive, share (uses Project model)
- TaskController: create, complete, assign, reorder (uses Task model)
- BillingController: subscribe, cancel, updatePlan (uses Subscription model)
- OnboardingController: step1, step2, complete (uses User model)
```

### Domain entity summary

```
Domain entities:

- User (central: has projects, tasks, subscriptions, teams)
- Project (belongs to user, has many tasks)
- Task (belongs to project, has statuses)
- Subscription (belongs to user, has plan)
- Team (belongs to user, has members)

Proposed central model: Project
  Rationale: Most relationships, core CRUD operations, referenced in most controllers
```

### Journey domain classification

```
Proposed journey domains:

ACQUISITION (2 routes)
  / (landing page), /pricing

ACTIVATION (6 routes)
  /register, /verify-email, /onboarding/step-1, /onboarding/step-2,
  /onboarding/step-3, /onboarding/complete

VALUE DELIVERY (12 routes)
  /dashboard, /projects, /projects/create, /projects/{id},
  /projects/{id}/edit, /tasks/create, /tasks/{id}/complete, ...

MONETIZATION (4 routes)
  /billing, /subscribe/{plan}, /checkout, /billing/portal

ENGAGEMENT (6 routes)
  /settings, /profile, /notifications, /preferences, ...

EXPANSION (3 routes)
  /teams/invite, /projects/{id}/share, /referral

INTERNAL (skipped: 4 routes)
  /admin/*, /health, /up

Does this grouping look right? Should any routes move to a different domain?
```

### Journey flow diagrams

```
ACQUISITION JOURNEY

  Land on         Read Blog       View            Click
  Homepage        Post            Pricing         Sign Up CTA
     |              |              |                |
     v              v              v                v
  $pageview      $pageview      $pageview       cta_clicked
     |              |              |                |
     props:         props:         props:           props:
     - $referrer    - $referrer    - $referrer      - cta_location
     - utm_source   - utm_source   - utm_source     - page
     - utm_medium   - blog_slug    - utm_medium
     - utm_campaign


ACTIVATION JOURNEY

  Register       Verify Email    Complete         First Core
  Account                       Onboarding        Action
     |              |              |               |
     v              v              v               v
  user_signed_    email_       onboarding_     project_created
     up           verified      completed
     |                              |
     props:                         props:
     - method                       - steps_completed
     - referral_source              - time_to_complete


VALUE DELIVERY JOURNEY

  Open           Create         Add            Complete        View
  Dashboard      Project        Tasks          Task           Progress
     |              |              |              |               |
     v              v              v              v               v
  dashboard_    project_       task_          task_           dashboard_
  viewed        created        created        completed       viewed
                   |              |              |
                   props:         props:         props:
                   - template     - project_id   - project_id
                                  - position     - time_to_complete
```

### Core action proposal

```
Proposed core action: project_created

Rationale:
- Project is the central model with the most relationships
- Creating a project is the primary action in the value delivery journey
- It represents the moment the user gets value from the product

Is this the right core action, or is there a different action that
better represents the "aha moment" for your users?
```

### Usage pattern detection

```
Detected usage pattern: Episodic

Evidence:
- Trip model has `departure_date` and `return_date` (lifecycle dates)
- Checklist items are tied to a trip with a finite completion state
- No daily streak or notification loop features
- Users plan intensely before a trip, then go dormant until next trip

Does this match how your users interact with the product?
```

### Success metric (Episodic example)

```
Primary: Active Window Engagement Rate
Formula: Of users with an active {central_model} (end_date > today),
         % who performed {core_action} in the last 7 days

North Star: Repeat {central_model} Rate
Formula: % of users who create a 2nd {central_model} after their
         first one completes

Leading: Activation rate (signup → first {core_action})
         Completion rate at end-of-window
Lagging: Repeat {central_model} rate, time to 2nd {central_model}
```

### PRODUCT.md output format

```markdown
# Product Context

## Product Overview

- **Name:** [Confirmed product name]
- **One-liner:** [What the product does in one sentence]
- **Stage:** [Pre-launch / Early / Growth / Mature]
- **Usage Pattern:** [Daily Habitual / Episodic / Transactional / Seasonal / Consumption]
- **Value Prop:** [Core value proposition]

## User Personas

[Discovered from models and routes]

## Core Action

- **Event:** [core_action_event_name]
- **Description:** [What the user does]
- **Central Model:** [The model at the center of the product]

## Primary Success Metric

[Pattern-appropriate metric from Step 9]

## User Journeys

| Domain | File | Events | Status |
|--------|------|--------|--------|
| Acquisition | journeys/acquisition.md | X | Not Tracked |
| Activation | journeys/activation.md | X | Not Tracked |
| Value Delivery | journeys/value-delivery.md | X | Not Tracked |
| Monetization | journeys/monetization.md | X | Not Tracked |
| Engagement | journeys/engagement.md | X | Not Tracked |
| Expansion | journeys/expansion.md | X | Not Tracked |
```

### Journey file output format

Each journey file follows this structure (example: `.growth/journeys/acquisition.md`):

```markdown
# Acquisition Journey

## Flow

Land on Homepage / Blog → View Pricing → Click Sign Up CTA

## Events

| Step | Event Name | Type | Properties | Status |
|------|-----------|------|------------|--------|
| View landing page | `$pageview` | auto | `$referrer`, `utm_source`, `utm_medium`, `utm_campaign` | Not Tracked |
| Read blog post | `$pageview` | auto | `$referrer`, `utm_source`, `blog_slug` | Not Tracked |
| View pricing page | `$pageview` | auto | `$referrer`, `utm_source` | Not Tracked |
| Click sign up CTA | `cta_clicked` | client | `cta_location`, `page` | Not Tracked |

## Conversion Funnel

$pageview (landing) → $pageview (pricing) → cta_clicked → user_signed_up (handoff to Activation)

## Notes

- Acquisition events fire for anonymous visitors — no `distinctId` until registration
- Capture UTM parameters on first `$pageview`
- Blog posts are key entry points — track `blog_slug` to identify high-converting content
```

---

## Anti-patterns

- **Don't show blank templates.** Never present an empty PRODUCT.md and ask the user to fill it in. Always propose concrete values based on what the codebase reveals.
- **Don't ask per-field questions.** Instead of asking "What is your product name?", "What is your core action?", etc., scan the code and propose answers. Let the user correct, not create.
- **Don't classify without reading controllers.** Route URLs alone are insufficient. Read the controller code to understand what each endpoint actually does before classifying into journey domains.
- **Don't default to WAU.** Weekly Active Users is a lazy default. Match the success metric to the detected usage pattern (Daily Habitual → DAU, Episodic → Repeat Rate, etc.).
- **Don't skip confirmation.** Present each domain's journey separately and get explicit confirmation before moving on. Don't dump all 6 domains at once.
- **Don't invent events.** Every proposed event name should map to a real controller action or route discovered in the codebase. Don't propose events for functionality that doesn't exist.

---

## References

- [AARRR (Pirate Metrics) Framework](https://www.productplan.com/glossary/aarrr-framework/) — The growth model used for journey classification
- [Growth Engineering](https://growengine.dev) — Full package with auto-instrumentation, tracking audits, and PostHog MCP tools
- [Laravel Boost](https://boost.laravel.com) — Required for `list-routes`, `database-schema`, and `tinker` tools
- [PostHog](https://posthog.com/docs) — Event naming conventions and property standards
