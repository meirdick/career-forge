# Product Context

## Product Overview

- **Name:** CareerForge
- **One-liner:** AI-powered career toolkit that analyzes job postings, identifies skill gaps, generates tailored resumes, and manages applications
- **Stage:** Early
- **Usage Pattern:** Episodic
- **Value Prop:** Turn any job posting into a complete, tailored application package — gap analysis, resume, cover letter — powered by AI and grounded in your real experience

## User Personas

- **Active Job Seeker:** Applying to multiple roles, needs to tailor materials quickly and track applications
- **Passive Explorer:** Browsing opportunities, wants to understand their market fit without committing to a full application
- **Career Changer:** Needs help reframing existing experience for a new industry or role

## Core Action

- **Event:** `job_posting_viewed`
- **Description:** User views the AI-generated analysis of a job posting — the moment they recognize CareerForge's value
- **Central Model:** JobPosting

## Primary Success Metric

### Activation Rate

```
Formula: % of registered users who view their first job analysis
         COUNT(users with job_posting_viewed) / COUNT(users signed up)
         within 7 days of registration
```

### North Star: Pipeline Completion Rate

```
Formula: % of users who complete the full pipeline per job posting
         job_posting_created → gap_analysis_created → resume_generated → application_created
```

### Leading Indicators

- Signup → first `job_posting_created` (time & conversion rate)
- `job_posting_viewed` → `gap_analysis_created` conversion
- `gap_analysis_created` → `resume_generated` conversion

### Lagging Indicators

- Repeat job posting rate (users returning for another search)
- Pipeline depth (how far users progress before dropping off)
- Credits purchased per user

## User Journeys

| Domain | File | Events | Status |
|--------|------|--------|--------|
| Acquisition | journeys/acquisition.md | 3 | Not Tracked |
| Activation | journeys/activation.md | 4 | Not Tracked |
| Value Delivery — Pipeline | journeys/value-delivery-pipeline.md | 8 | Not Tracked |
| Value Delivery — Experience Library | journeys/value-delivery-experience-library.md | 7 | Not Tracked |
| Engagement | journeys/engagement.md | 4 | Not Tracked |
| Monetization | journeys/monetization.md | 4 | Not Tracked |
| Expansion | journeys/expansion.md | 4 | Not Tracked |
