# Expansion Journey

## Flow

Create Transparency Page → Publish Transparency Page → Share Public Link → Referral Signup

## Events

| Step | Event Name | Type | Properties | Status |
|------|-----------|------|------------|--------|
| Create transparency page | `transparency_created` | server | `application_id` | Not Tracked |
| Publish transparency page | `transparency_published` | server | `application_id`, `slug` | Not Tracked |
| View public transparency page | `transparency_link_viewed` | auto | `slug`, `$referrer` | Not Tracked |
| Referral signup | `referral_signup` | server | `referral_code`, `referred_by` | Not Tracked |

## Conversion Funnel

`transparency_published` → `transparency_link_viewed` → `user_signed_up` (with referral_code) → `referral_signup`

## Notes

- Transparency pages are CareerForge's primary viral loop — users publish proof of their AI-assisted application process
- Each transparency page includes authorship statement, research summary, ideal profile summary, section decisions, and tool description
- Public pages at `/t/{slug}` are viewable without authentication — they serve as both portfolio pieces and acquisition channels
- Referral system uses `referral_code` on User — track which users drive signups
- `transparency_page_views` table already tracks views with IP, user agent, and referer — can feed analytics
