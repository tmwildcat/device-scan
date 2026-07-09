# LineWatt Platform
# Activity & Notification Framework

**Status:** Design Approved

---

# Purpose

The Activity & Notification Framework provides a lightweight, reusable platform service for the entire LineWatt ecosystem.

It is **not** a DeviceScan-specific feature.

The framework will eventually be used by:

- LineWatt Library
- LineWatt Studio
- LineWatt EMS
- GridFinex
- Future products

---

# Philosophy

Everything important generates an Activity.

Activities may create Notifications.

Notifications may generate Deliveries.

The framework separates:

```
Business Event

↓

Activity

↓

Notification

↓

Delivery
```

This separation allows:

- Activity feeds
- Notification center
- Email
- Push notifications
- Audit history
- Future Teams/Slack/Webhooks

without changing business logic.

---

# Architecture

```
User Action

↓

Laravel Event

↓

Activity Logger

↓

Activity

↓

Notification Manager

↓

Notification

↓

Delivery Channel

↓

In-App

Email

Future:
Push
Slack
Teams
Webhook
```

---

# Core Concepts

## Activity

Represents something that happened.

Examples:

- Datasheet Uploaded
- Engineering Record Compiled
- Engineering Record Published
- Partner Submission Created
- Validation Failed
- Subscription Renewed

Activities are immutable.

---

## Notification

Represents something a user should see.

Notifications are derived from Activities.

Notifications can be:

- informational
- warnings
- action required

Notifications can be dismissed or marked as read.

---

## Delivery

Represents how a notification reaches the user.

Initially supported:

- In-App
- Email

Future:

- Push
- Slack
- Teams
- Webhook

---

# Activity Model

Suggested fields

```
id

uuid

tenant_id

partner_id

workspace

actor_type

actor_id

activity_type

entity_type

entity_id

title

summary

metadata

created_at
```

Activities should never be deleted.

---

# Notification Model

Suggested fields

```
id

uuid

activity_id

recipient_type

recipient_id

notification_type

priority

title

message

status

read_at

dismissed_at

metadata
```

---

# Notification Delivery

Suggested fields

```
id

notification_id

channel

status

attempts

sent_at

error_message
```

---

# Notification Types

Supported

```
Info

Success

Warning

Action Required

Error
```

---

# Priority

```
Low

Normal

High

Critical
```

---

# Email Policy

## Email only when action is required.

Examples

✔ Engineering Record requires review

✔ Partner submission awaiting review

✔ Storage quota exceeded

✔ Subscription renewal

✔ Validation failed

✔ Compiler failure

---

## Do NOT send email for

✖ Successful upload

✖ Successful compile

✖ Successful export

✖ Search completed

✖ Record viewed

These should appear only inside the Notification Center.

---

# Activity Feed

A reusable Activity Feed component should exist.

Eventually used in:

- Home
- My Library
- Central Engineering Workspace
- Partner Portal

Example

```
Today

Uploaded Jinko Datasheet

Engineering Record Published

Validation Warning

Partner Submission

Yesterday

Storage Warning
```

---

# Notification Center

A reusable Notification Center should provide:

- unread count
- mark read
- dismiss
- filter
- link to related entity

Displayed through a notification bell.

---

# Laravel Events

The framework should build around Laravel Events.

Examples

```
DatasheetUploaded

EngineeringRecordCompiled

EngineeringRecordPublished

EngineeringRecordRejected

EngineeringRecordDiscontinued

PartnerSubmissionCreated

PartnerSubmissionApproved

PromotionPublished

StorageQuotaExceeded

SubscriptionRenewed

CompilerFailed
```

Business logic should fire Events.

The framework converts Events into Activities.

Activities become Notifications.

Notifications become Deliveries.

---

# Services

Recommended services

```
ActivityLogger

NotificationManager

NotificationDispatcher

EmailNotificationChannel

InAppNotificationChannel
```

Future

```
PushNotificationChannel

SlackNotificationChannel

TeamsNotificationChannel

WebhookNotificationChannel
```

---

# Configuration

Create

```
config/notifications.php
```

Suggested settings

```
enabled_channels

email_enabled

retention_days

default_priority

queue

throttle

digest_options
```

---

# UI Components

Reusable components

```
NotificationBell

NotificationDropdown

NotificationList

NotificationCard

ActivityFeed

UnreadBadge
```

---

# Product Scope

The framework should remain platform-level.

Business modules should never send emails directly.

Instead:

```
Business Event

↓

Activity

↓

Notification

↓

Delivery
```

---

# Future

The same framework will power:

- Mobile applications
- Desktop notifications
- Slack
- Teams
- Webhooks
- AI Assistant activity streams

without changing product business logic.