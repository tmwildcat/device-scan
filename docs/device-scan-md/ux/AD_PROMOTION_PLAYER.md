# Advertisement / Promotion Player

## Purpose

The AdPromotionPlayer is a reusable, compact, premium promotional component. It begins with LineWatt promotional animation and later supports sponsored OEM partner promotions.

## Actors

- Guest
- Subscriber / Member
- Partner Admin
- Partner User
- Admin

## Route Ideas

Can appear on:

- `/`
- `/my-library`
- `/partner`
- selected non-review discovery pages

Must not appear on:

- `/central-engineering/review/*`
- engineering review pages
- validation decision pages

## Requirements

- small.
- subtle.
- compact.
- premium.
- silent autoplay.
- auto-loop.
- unobtrusive.
- LineWatt promotional animation initially.
- sponsored OEM promotions later.
- partner ads clearly labeled "Sponsored".
- no effect on validation.
- no effect on engineering recommendations.
- no effect on Engineering Search ranking.

## Components

- AdPromotionPlayer.
- SponsoredLabel.
- PromotionMediaSlot.
- PromotionClickTarget.
- CampaignTrackingHook.

## Status Behavior

Promotion status later can include draft, active, paused, expired and rejected. This milestone only defines the UX position.

## Permissions

Admin can manage partner promotions. Partner Admin can manage their own promotions. Partner User cannot manage promotions by default.

## Edge Cases

- If media fails to load, collapse the player without layout disruption.
- If a partner promotion is shown, label it Sponsored.
- If the page is an engineering review page, suppress the player entirely.
- Ads must never interrupt upload, review, comparison, export or publish workflows.
- Promotion metrics must not be confused with Engineering Record quality or validation metrics.
