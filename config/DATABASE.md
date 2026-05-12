# MTRTS — Database Schema Reference

All tables live in the `mtrts_sql` database. The single source of truth is `config/database.sql`, which is idempotent (drops and recreates everything) and can be used as a full reset.

---

## Module 0 — Users & Access Control

### `departments`
Institutional departments (e.g., IT Department, College of Engineering). Referenced by both `users` and `assets` to track who owns or manages a record.

### `roles`
Eight fixed system roles seeded at setup. Never managed in-app.

| role_id | role_name        | Can manage admin accounts |
|---------|-----------------|--------------------------|
| 1       | admin           | No                       |
| 2       | it_manager      | —                        |
| 3       | it_staff        | —                        |
| 4       | technician      | —                        |
| 5       | faculty         | —                        |
| 6       | department_staff| —                        |
| 7       | student         | —                        |
| 8       | super_admin     | Yes                      |

### `users`
All system accounts. Records are never deleted — set `is_active = 0` to deactivate. `role_id` and `department_id` are foreign keys to `roles` and `departments`.

### `user_sso`
Optional SSO link for users who authenticate via Google or Microsoft. One row per user. A user with no row here uses email + password login. Matched via `provider_uid` (the unique ID from the provider).

### `role_modules`
Access control map. Each row grants a `role_id` access to one `module_slug`. The application checks this table on every page load. Module slugs must match folder names under `/modules/`.

**Current module slugs:**

| Slug          | Description                  | Who has access                                  |
|---------------|------------------------------|-------------------------------------------------|
| dashboard     | Summary dashboard            | super_admin, admin, it_manager, it_staff        |
| assets        | Asset registry               | super_admin, admin, it_manager, it_staff        |
| tickets       | Repair request tickets       | All roles                                       |
| workorders    | Work order management        | super_admin, admin, it_manager                  |
| technician    | Technician field operations  | super_admin, admin, technician                  |
| reports       | SLA reports and analytics    | super_admin, admin, it_manager                  |
| users         | User management              | super_admin, admin                              |
| inventory     | Parts inventory              | super_admin, admin, it_manager, it_staff        |
| kb            | Knowledge base               | All roles                                       |
| profile       | User profile                 | All roles                                       |
| notifications | In-app notifications         | All roles                                       |

---

## Module 2 — Asset & Configuration Management

### `asset_categories`
Equipment types (Projector, Sound System, AV Switcher, etc.). The `has_bulb_hours` flag drives conditional form fields — only Projector category shows bulb hours. Referenced by `assets`, `tickets`, `wo_checklists`, `kb_articles`, and `sla_policies`.

### `locations`
Physical rooms in the institution. Combination of `(building, floor, room)` is unique. Referenced by `assets` and `tickets`.

### `assets`
The main equipment registry. Every piece of AV/media hardware has one row here.

**Key relationships:**
- `category_id` → `asset_categories`
- `location_id` → `locations`
- `parent_asset_id` → `assets` (self-reference for rack/component grouping, e.g., an AV Rack is the parent of its mixer and amplifier children)
- `department_id` → `departments` (financial ownership)
- `owner_id` → `users` (responsible person)
- `created_by` → `users`

**Business rules enforced at application layer:**
- `asset_tag` must be unique across all assets.
- `serial_number` must be unique per manufacturer.
- Cannot set `status = 'retired'` if open tickets exist.
- `location_id` must be set before `status` can be `'active'`.
- `install_date` cannot be a future date.

### `asset_warranty`
One warranty record per asset. Stores vendor, coverage type, and expiry date. The application sends automated in-app notifications at 60, 30, and 7 days before `warranty_end`.

### `asset_documents`
Manuals, wiring diagrams, config backups attached to an asset. Versioned — every upload creates a new row. When a new version is uploaded, `is_latest` on the previous row is set to `0`.

### `asset_audit_log`
Immutable, field-level change log for all asset records. INSERT only — never UPDATE or DELETE rows here. Every change to an asset (status, location, firmware, etc.) must produce a row with the old and new value.

---

## Module — Notifications

### `notifications`
In-app notification inbox. One row per recipient per event. `notif_key` is a unique string for automated alerts (e.g., `warranty_12_2026-05-01_30`) that prevents the same alert from being delivered twice. `NULL` notif_key = one-time ad-hoc notification.

---

## Module 1 — Request Submission & Intake (Tickets)

### `tickets`
Core repair request records. Created by any user role via web form, email-to-ticket, or QR scan. `ticket_number` is a human-readable ID (e.g., `TKT-2026-00001`) generated at the application layer.

**Key relationships:**
- `requester_id` → `users`
- `asset_id` → `assets` (the equipment being reported)
- `category_id` → `asset_categories`
- `location_id` → `locations`
- `duplicate_of_id` → `tickets` (self-reference if this ticket is flagged as a duplicate)
- `assigned_to` → `users` (technician or staff assigned)
- `approved_by` → `users`

**Status flow:**
```
new → assigned → scheduled → in_progress → on_hold → resolved → closed
                                                   ↘ cancelled
```

**`on_hold_reason`** tracks why a ticket is paused: `waiting_parts`, `waiting_vendor`, `waiting_access`, or `other`.

**`is_event_support = 1`** triggers an escalated SLA (event support policy).

### `ticket_attachments`
Photos, videos, or files uploaded at ticket submission or added during updates. Linked to a ticket. Allowed types and size limits enforced at application layer.

### `ticket_comments`
Threaded comments on a ticket. `is_internal = 1` means the note is visible to staff only (not the requester).

### `ticket_dynamic_fields`
Category-specific extra fields that don't belong in the main ticket schema. Examples: `bulb_hours` (Projectors), `input_source` (AV Switchers). Each row is a `field_name`/`field_value` pair unique per ticket.

---

## Module 3 — Work Order & Dispatch Management

### `work_orders`
A work order is a task dispatched to a technician from a ticket. One ticket can have multiple WOs (e.g., one for diagnosis, one for follow-up repair). `wo_number` is a human-readable ID (e.g., `WO-2026-00001`).

**Key relationships:**
- `ticket_id` → `tickets`
- `assigned_to` → `users` (executing technician)
- `assigned_by` → `users` (dispatcher/manager)
- `created_by` → `users`

**`is_rma = 1`** flags that the asset is under warranty and should be sent back to the vendor rather than repaired in-house.

### `wo_assignment_log`
Every technician reassignment on a work order is recorded here with a reason. Useful for audit and performance analysis.

### `wo_checklists`
Reusable checklist templates. Can be scoped to a specific `category_id` (e.g., "Projector Repair Checklist") or global (`category_id = NULL`). When a WO is created, the matching checklist items are copied into `wo_checklist_completions`.

### `wo_checklist_items`
Individual steps within a checklist. `is_mandatory = 1` means the WO cannot be set to Resolved until this item is marked done. `requires_photo = 1` means evidence must be uploaded before the item can be completed.

### `wo_checklist_completions`
The per-WO instance of checklist item completion. One row per `(wo_id, item_id)` pair. `is_done`, `completed_by`, and `completed_at` are updated as the technician works through the list.

---

## Module 4 — Technician Operations

### `wo_time_logs`
Time tracking events for a work order. Each row is one event: `start`, `pause`, `resume`, or `stop`. Total labor time is derived by calculating intervals between events at the application layer.

### `wo_media`
Before/after photos and evidence files captured during WO execution. `media_type` distinguishes between `before`, `after`, `evidence`, and `other`.

### `wo_signoff`
Digital sign-off captured from the requester when a WO is completed. One row per WO (UNIQUE constraint). Stores the signer's name, a path to the signature image, and an optional 1–5 satisfaction rating.

---

## Module — Parts Inventory

### `parts_inventory`
Stock of spare parts and consumables. `quantity_on_hand` is decremented by the application when a part is used on a WO. `reorder_level` triggers a low-stock alert in the dashboard.

### `wo_parts_used`
Parts consumed during a work order. Each row links a `part_id` to a `wo_id` with quantity and optional per-unit serial number. `is_warranty = 1` flags parts used under an RMA/warranty claim.

---

## Module — Knowledge Base

### `kb_articles`
Help articles and triage guides surfaced to users when submitting tickets. The application matches articles to tickets by `category_id` and keyword `tags`. `views` is incremented each time an article is opened.

---

## Module 5 — SLA, Reporting & Audit

### `business_hours`
Working schedule used by the SLA engine to calculate deadlines. One row per day of week (`0 = Sunday` through `6 = Saturday`). `is_working = 0` for weekends or non-working days.

### `holidays`
Non-working dates excluded from SLA clock calculations. `is_recurring = 1` means the same calendar date is a holiday every year (e.g., New Year's Day). `is_recurring = 0` for dates that change year to year (e.g., Good Friday, National Heroes Day).

### `sla_policies`
Service level agreement definitions. Matched to a ticket at creation based on `priority` and/or `category_id`. Defines three time targets (in minutes): `response_minutes`, `diagnosis_minutes`, and `resolution_minutes`. `uses_business_hours = 0` means the clock runs 24/7 (used for Critical and Event Support policies).

**Seeded policies:**

| Policy                    | Priority | Response | Diagnosis | Resolution | 24/7 |
|---------------------------|----------|----------|-----------|------------|------|
| Critical Priority SLA     | critical | 30 min   | 2 hr      | 4 hr       | Yes  |
| High Priority SLA         | high     | 2 hr     | 8 hr      | 24 hr      | No   |
| Medium Priority SLA       | medium   | 4 hr     | 24 hr     | 48 hr      | No   |
| Low Priority SLA          | low      | 8 hr     | 48 hr     | 72 hr      | No   |
| Event Support (Urgent) SLA| critical | 15 min   | 1 hr      | 2 hr       | Yes  |

### `ticket_sla`
Per-ticket SLA tracking. Created when a ticket is assigned an SLA policy. Stores computed deadline timestamps and actual timestamps. Breach flags (`is_*_breached`) are set to `1` by the application when a deadline is missed.

`paused_at` and `total_paused_minutes` support clock-pause rules (e.g., when a ticket is on hold waiting for parts, the SLA clock stops). `pause_reason` maps to the ticket's `on_hold_reason`.

### `audit_log`
System-wide immutable event log. Every significant action (login, ticket create/update, asset change, WO status change, approval) writes a row here. `old_values` and `new_values` are JSON snapshots. INSERT only — never UPDATE or DELETE. Supports e-discovery queries by date, user, and object type.

---

## Entity Relationship Summary

```
departments ──────────────────────────────────────────────┐
     │                                                     │
     ├── users ──── user_sso                               │
     │      │                                              │
     │      ├── role_modules ──── roles                    │
     │      │                                              │
     │      ├── notifications                              │
     │      │                                              │
     │      └── audit_log                                  │
     │                                                     │
     └── assets ◄──────────────────────────────────────────┘
            │  (self-ref: parent_asset_id)
            ├── asset_categories
            ├── locations
            ├── asset_warranty
            ├── asset_documents
            └── asset_audit_log

tickets ──────── assets
    │   ──────── locations
    │   ──────── asset_categories
    │   ──────── users (requester, assigned_to, approved_by)
    │   ──────── tickets (self-ref: duplicate_of_id)
    │
    ├── ticket_attachments
    ├── ticket_comments
    ├── ticket_dynamic_fields
    ├── ticket_sla ──── sla_policies
    │
    └── work_orders ──── users (assigned_to, assigned_by)
            │
            ├── wo_assignment_log
            ├── wo_checklist_completions ──── wo_checklist_items ──── wo_checklists ──── asset_categories
            ├── wo_time_logs
            ├── wo_media
            ├── wo_signoff
            └── wo_parts_used ──── parts_inventory

sla_policies ──── asset_categories
business_hours (standalone)
holidays (standalone)
kb_articles ──── asset_categories
```
