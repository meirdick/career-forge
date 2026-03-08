# Engagement Journey

## Flow

View Dashboard → Start Chat Session → AI Enhancement on Entries → Gap Closure Chat

## Events

| Step | Event Name | Type | Properties | Status |
|------|-----------|------|------------|--------|
| View dashboard | `dashboard_viewed` | server | | Not Tracked |
| Start chat session | `chat_session_created` | server | `mode` (career_chat/pipeline) | Not Tracked |
| Request AI enhancement | `enhancement_requested` | server | `entry_type`, `entry_id` | Not Tracked |
| Send gap closure chat message | `gap_chat_message_sent` | server | `gap_analysis_id`, `message_count` | Not Tracked |

## Conversion Funnel

`dashboard_viewed` → `chat_session_created` or `enhancement_requested`

## Notes

- Dashboard is the re-engagement entry point — returning users land here
- Career Chat supports two modes: general career coaching and pipeline-specific guidance
- Chat sessions have extract and commit actions — users can pull structured data from conversations into their Experience Library
- AI enhancement (`experience-library/enhance`) improves existing entries — a retention signal showing users refining their profile
- Gap closure chat is contextual to a specific gap analysis — helps users work through identified gaps interactively
