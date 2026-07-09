# Home Page

## Purpose

Home is the premium discovery and entry point for LineWatt Library. It should feel like a professional engineering knowledge portal, not a software landing page.

## Actors

- Guest
- Subscriber / Member
- Partner users
- Platform staff

## Route Ideas

- `/`
- `/search`
- `/library`

## Dominant Action

Engineering Search.

## Screens

- Home discovery page.
- Search entry state.
- Featured Engineering Record preview.

## Components

- Premium hero section.
- EngineeringSearchBar above the fold.
- FeaturedManufacturersStrip.
- RecentlyAddedEngineeringRecords.
- FeaturedTechnologies.
- TechnologySpotlight.
- CompactBenefitsSection.
- SubscriptionCTA.
- Compact AdPromotionPlayer.

## Content Structure

1. Hero:
   - Headline: "LineWatt Library"
   - Supporting copy: create, manage, search, compare and distribute trusted Engineering Records.
   - Primary control: Engineering Search.
2. Featured manufacturers.
3. Recently added published Engineering Records.
4. Featured technologies.
5. Technology spotlight.
6. Compact benefits: validation, source traceability, engineering-ready exports.
7. Subscription CTA.
8. Small LineWatt promotional animation.

## Status Behavior

Home shows only central records with `status = published`. Discontinued records should appear only when explicitly included through Engineering Search.

## Permissions

Guests can search limited public metadata and view marketing/discovery pages. Guests cannot upload, download, compile or compare.

## Edge Cases

- If no published records exist, show curated technology and manufacturer discovery rather than empty tables.
- Ads must be visually secondary and must not imply engineering endorsement.
- Comparison examples can be shown, but the action requires authentication.
