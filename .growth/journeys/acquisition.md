# Acquisition Journey

## Flow

Land on Homepage / View Public Transparency Page → Click Register CTA

## Events

| Step | Event Name | Type | Properties | Status |
|------|-----------|------|------------|--------|
| View landing page | `$pageview` | auto | `$referrer`, `utm_source`, `utm_medium`, `utm_campaign` | Not Tracked |
| View public transparency page | `$pageview` | auto | `slug`, `$referrer`, `utm_source` | Not Tracked |
| Click register CTA | `cta_clicked` | client | `cta_location`, `page` | Not Tracked |

## Conversion Funnel

`$pageview (landing)` → `cta_clicked` → `user_signed_up` (handoff to Activation)

## Notes

- Acquisition events fire for anonymous visitors — no `distinctId` until registration
- Capture UTM parameters on first `$pageview`
- Public transparency pages (`/t/{slug}`) are a key organic acquisition channel — users share proof of their AI-assisted process, bringing in new visitors
- Referral codes (`referral_code` on User) should be captured on landing if present in URL
