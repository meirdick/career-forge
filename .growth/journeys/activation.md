# Activation Journey

## Flow

Register Account → Verify Email → Upload/Paste Job Posting → View Job Analysis (Aha Moment)

## Events

| Step | Event Name | Type | Properties | Status |
|------|-----------|------|------------|--------|
| Register account | `user_signed_up` | server | `method`, `referral_code` | Not Tracked |
| Verify email | `email_verified` | server | | Not Tracked |
| Create job posting | `job_posting_created` | server | `source` (url/quick/bulk), `job_posting_id` | Not Tracked |
| View job analysis (aha moment) | `job_posting_viewed` | server | `job_posting_id`, `has_analysis`, `match_score` | Not Tracked |

## Conversion Funnel

`user_signed_up` → `email_verified` → `job_posting_created` → `job_posting_viewed` (aha)

## Notes

- The aha moment is viewing the AI-generated analysis of a job posting — this is when users recognize the value prop
- Users can also start with resume upload (`/resume-upload`) to populate their Experience Library first, but the activation signal is seeing job analysis
- `welcome/dismiss` fires when users close the onboarding banner — a secondary activation signal
- Track time from `user_signed_up` to `job_posting_viewed` as activation velocity
- Source breakdown (url vs quick vs bulk) reveals which entry point converts best
