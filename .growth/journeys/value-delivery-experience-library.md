# Value Delivery — Experience Library Journey

## Flow

Upload Resume → Review & Commit → Add Experiences → Add Accomplishments → Add Skills → Edit Identity → Add Education / Evidence

## Events

| Step | Event Name | Type | Properties | Status |
|------|-----------|------|------------|--------|
| Upload resume | `resume_uploaded` | server | `document_id`, `mime_type` | Not Tracked |
| Commit parsed resume data | `resume_upload_committed` | server | `document_id`, `entries_imported` | Not Tracked |
| Create experience | `experience_created` | server | `has_company`, `has_dates`, `experience_id` | Not Tracked |
| Create accomplishment | `accomplishment_created` | server | `experience_id` | Not Tracked |
| Create skill | `skill_created` | server | `category`, `proficiency` | Not Tracked |
| Update professional identity | `identity_updated` | server | `fields_set` | Not Tracked |
| Create education entry | `education_created` | server | `type` | Not Tracked |

## Conversion Funnel

`resume_uploaded` → `resume_upload_committed` → `experience_created` → (library populated)

## Notes

- Resume upload is the fastest way to populate the library — AI parses the document and creates experiences, skills, and education entries
- Manual entry is the alternative path for users without an existing resume
- Experience Library depth (number of experiences, accomplishments, skills) directly affects gap analysis and resume quality
- AI enhancement (`experience-library/enhance`) lets users improve entries with AI — track as an engagement signal
- Evidence entries (links, documents, text) provide proof for claims — especially valuable for transparency pages
