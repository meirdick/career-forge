# Value Delivery — Pipeline Journey

## Flow

Run Gap Analysis → Resolve Gaps → Generate Resume → Customize Resume → Create Application → Generate Cover Letter / Email

## Events

| Step | Event Name | Type | Properties | Status |
|------|-----------|------|------------|--------|
| Run gap analysis | `gap_analysis_created` | server | `job_posting_id`, `match_score` | Not Tracked |
| Resolve a gap | `gap_resolved` | server | `gap_area`, `resolution_method` (reframe/answer/acknowledge), `gap_analysis_id` | Not Tracked |
| Generate resume | `resume_generated` | server | `gap_analysis_id`, `job_posting_id` | Not Tracked |
| Edit resume section | `resume_section_edited` | server | `resume_id`, `section_type`, `action` (select_variant/toggle/edit) | Not Tracked |
| Create application | `application_created` | server | `job_posting_id`, `resume_id`, `status` | Not Tracked |
| Update application status | `application_status_changed` | server | `application_id`, `from_status`, `to_status` | Not Tracked |
| Generate cover letter | `cover_letter_generated` | server | `application_id` | Not Tracked |
| Generate email | `email_generated` | server | `application_id` | Not Tracked |

## Conversion Funnel

`gap_analysis_created` → `gap_resolved` → `resume_generated` → `application_created`

## Notes

- This is the core value loop — each step builds on the previous
- Pipeline completion rate (all 4 funnel steps for a single job posting) is the north star metric
- Gap resolution has 3 methods: reframe (AI suggests alternative framing), answer (user provides context), acknowledge (user accepts the gap)
- Resume customization depth (variant selection, section toggling, manual edits) indicates engagement quality
- Application status tracking (`applied` → `interviewing` → `offered` → `accepted`/`rejected`) provides outcome data
- Cover letter and email generation are high-value AI actions that consume credits
