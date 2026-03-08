# Monetization Journey

## Flow

View Billing Page → Start Checkout → Complete Purchase / Redeem Promo Code

## Events

| Step | Event Name | Type | Properties | Status |
|------|-----------|------|------------|--------|
| View billing page | `billing_viewed` | server | `current_balance`, `lifetime_purchased` | Not Tracked |
| Start checkout | `checkout_started` | server | `product_id`, `price` | Not Tracked |
| Complete purchase | `credits_purchased` | server | `amount`, `credits`, `polar_order_id` | Not Tracked |
| Redeem promo code | `promo_redeemed` | server | `code`, `credits` | Not Tracked |

## Conversion Funnel

`billing_viewed` → `checkout_started` → `credits_purchased`

## Notes

- CareerForge uses a credit-based model via Polar — users purchase credit packs, not subscriptions
- BYOK (Bring Your Own Key) users bypass monetization entirely — they configure their own API key in Settings and use the product for free
- Credits are consumed by AI actions: job analysis, gap analysis, resume generation, cover letter generation, chat messages, content enhancement
- Track credit balance at time of `billing_viewed` to understand what triggers purchase intent
- Promo codes are a separate acquisition/activation lever — track separately from organic purchases
- Polar webhook (`/polar/webhook`) processes payment confirmations server-side
