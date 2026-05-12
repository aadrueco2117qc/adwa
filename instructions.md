# MTRTS — AI Coding Instructions
## Media Technology Repair Tracker System

---

## What This System Is

A web-based repair ticketing and asset management platform
for an educational institution (Our Lady of Fatima University).
It manages the full lifecycle of media/AV equipment repair —
from submitting a ticket, to fixing the device, to reporting.

---

## Tech Stack

| Concern       | Technology                        |
|---------------|-----------------------------------|
| Backend       | PHP 8+ (procedural, no framework) |
| Frontend      | HTML, Tailwind CSS, Vanilla JS    |
| Database      | MySQL 8+                          |
| Local server  | Apache via XAMPP                  |
| Auth (now)    | Email + password (bcrypt)         |
| Auth (future) | SSO via Google / Microsoft        |

Do not suggest frameworks like Laravel, Symfony, or React.
Do not suggest npm, composer, or any package manager.
Everything must run on a plain XAMPP setup.

---

## Folder Structure

```
mtrts/
│
├── index.php               ← Entry point — redirects to modules/dashboard/index.php
│
├── config/
│   ├── db.php              ← PDO connection + defines BASE_URL
│   ├── auth.php            ← Auth functions: attempt_login, can_access, etc.
│   ├── guard.php           ← Include at top of every page file (auth + layout)
│   ├── auth_only.php       ← Include at top of POST/AJAX handlers (auth, no layout)
│   └── *.sql               ← Database schema files
│
├── includes/               ← Shared UI components
│   ├── header.php          ← HTML head, defines $module_labels and $page_title
│   ├── navbar.php          ← Sidebar navigation + topbar
│   └── footer.php          ← Closes </main></div></body></html>
│
├── modules/                ← One folder per module
│   ├── dashboard/          ← Landing page after login (stats + recent activity)
│   ├── assets/             ← Module 2: Asset Management (fully implemented)
│   ├── tickets/            ← Module 1: Request Submission
│   ├── workorders/         ← Module 3: Work Order & Dispatch (fully implemented)
│   ├── technician/         ← Module 4: Technician Operations
│   ├── reports/            ← Module 5: SLA, Reporting & Audit
│   ├── users/              ← Module 6: User Access Control (fully implemented)
│   ├── profile/            ← Self-service profile page (all roles, accessed via avatar)
│   ├── notifications/      ← In-app notification inbox (all roles, accessed via bell)
│   │   ├── index.php       ← Full notification history (logic)
│   │   ├── index.view.php  ← Full notification history (HTML)
│   │   ├── functions.php   ← DB helpers + check_warranty_expiry()
│   │   ├── fetch.php       ← AJAX: returns {count, items} for the bell badge
│   │   └── mark_read.php   ← AJAX POST: marks one or all notifications as read
│   ├── login.php           ← Login page
│   └── denied.php          ← Shown when access is denied
│
└── public/
    ├── assets/css/
    ├── assets/images/      ← logo.png (OLFU crest)
    └── uploads/
        ├── avatars/        ← User profile pictures
        └── qrcodes/        ← Asset QR codes
```

---

## File Pattern Inside Each Module

Every module follows this exact structure.
Use `modules/assets/` as the reference implementation.

```
modules/{module}/
  index.php         ← List/landing page — LOGIC only
  index.view.php    ← List/landing page — HTML only
  view.php          ← Detail/read-only page — LOGIC only
  view.view.php     ← Detail page — HTML only
  add.php           ← Add form — LOGIC only
  edit.php          ← Edit form — LOGIC only
  _form.view.php    ← Shared form — HTML only (used by add + edit)
  save.php          ← POST handler — LOGIC only, no HTML output
  functions.php     ← All DB queries and helpers for this module
  _styles.php       ← Module-specific CSS (if needed)
```

Files prefixed with `_` (underscore) are **partials** — they are
never visited directly in a browser. They are only `require`d by
other files.

---

## How to Build a New Module

### Step 1 — Register the module

**`includes/header.php`** — add the label:
```php
$module_labels = [
    'tickets' => 'Request Submission',  // ← add your module here
];
```

**Database** — grant role access:
```sql
INSERT INTO role_modules (role_id, module_slug) VALUES (1, 'tickets');
```

### Step 2 — Every page file (index, view, add, edit)

Start with guard, load functions, fetch data, then require the view:

```php
<?php
$module = 'tickets';
require_once __DIR__ . '/../../config/guard.php';
require_once __DIR__ . '/functions.php';

// fetch data here using $pdo...

require __DIR__ . '/index.view.php';
require_once __DIR__ . '/../../includes/footer.php';
```

`guard.php` handles everything automatically:
- Starts the session
- Connects to the database (`$pdo` is available after this)
- Redirects to login if not logged in
- Blocks access if the user's role cannot access `$module`
- Runs `check_warranty_expiry()` once per hour (throttled via `$_SESSION['last_warranty_check']`)
- Outputs the sidebar + topbar and opens `<main>`

> **Special case:** Pages open to all logged-in users (e.g. dashboard, profile, notifications)
> use their module slug normally — those slugs are registered in `role_modules` for ALL roles
> via `config/notifications.sql`. Do NOT set `$module = ''` for them.

### Step 3 — Every POST handler (save.php) and AJAX endpoint

Use `auth_only.php` instead — same auth checks but no HTML output:

```php
<?php
$module = 'tickets';
require_once __DIR__ . '/../../config/auth_only.php';
require_once __DIR__ . '/functions.php';
```

### Step 4 — View files (*.view.php)

Pure HTML + PHP output only. No `require`, no DB calls, no redirects.
All variables they need are set by the logic file before `require`.

### Step 5 — Links between pages

Use relative paths within the same module:

```php
<a href="index.php">Back to list</a>
<a href="view.php?id=<?= $id ?>">View</a>
<a href="edit.php?id=<?= $id ?>">Edit</a>
<a href="add.php">Add New</a>
```

For redirects in save.php, use BASE_URL:

```php
header('Location: ' . BASE_URL . 'modules/tickets/view.php?id=' . $id);
```

---

## Session Variables Available Everywhere

After `guard.php` or `auth_only.php` runs, these are always set:

```php
$_SESSION['user_id']         // int
$_SESSION['role_id']         // int
$_SESSION['full_name']       // string
$_SESSION['email']           // string
$_SESSION['profile_picture'] // string — relative web path, or '' if not set
```

---

## Database Access

`$pdo` is available automatically after `guard.php` or `auth_only.php`.
Never create a new database connection inside a module.

Always use prepared statements:

```php
$stmt = $pdo->prepare('SELECT * FROM tickets WHERE ticket_id = ?');
$stmt->execute([$id]);
$row = $stmt->fetch();
```

---

## Roles and Module Access

| Module        | admin | it_manager | it_staff | technician | faculty | dept_staff | student |
|---------------|:-----:|:----------:|:--------:|:----------:|:-------:|:----------:|:-------:|
| dashboard     |  ✅   |     ✅     |    ✅    |     ✅     |   ✅    |     ✅     |   ✅    |
| assets        |  ✅   |     ✅     |    ✅    |     ❌     |   ❌    |     ❌     |   ❌    |
| tickets       |  ✅   |     ✅     |    ✅    |     ✅     |   ✅    |     ✅     |   ✅    |
| workorders    |  ✅   |     ✅     |    ❌    |     ❌     |   ❌    |     ❌     |   ❌    |
| technician    |  ✅   |     ✅     |    ❌    |     ✅     |   ❌    |     ❌     |   ❌    |
| reports       |  ✅   |     ✅     |    ❌    |     ❌     |   ❌    |     ❌     |   ❌    |
| users         |  ✅   |     ❌     |    ❌    |     ❌     |   ❌    |     ❌     |   ❌    |
| profile       |  ✅   |     ✅     |    ✅    |     ✅     |   ✅    |     ✅     |   ✅    |
| notifications |  ✅   |     ✅     |    ✅    |     ✅     |   ✅    |     ✅     |   ✅    |

`profile` and `notifications` are **not shown in the sidebar** (`$nav_exclude` in `navbar.php`).
They are accessed via the avatar link and the bell icon respectively.

---

## Login Flow

```
User visits any page
  → index.php checks $_SESSION['user_id']
  → Not set? Redirect to modules/login.php
  → Set? Redirect to modules/dashboard/index.php

modules/login.php
  → User submits email + password
  → Query users table by email
  → password_verify() against stored hash
  → If valid:
      session_regenerate_id(true)
      Set $_SESSION['user_id'], ['role_id'], ['full_name'], ['email'], ['profile_picture']
      Update users.last_login = NOW()
      Redirect to ../index.php
  → If invalid: show error message
```

---

## Security Rules

These are non-negotiable. Follow all of them.

1. Always use prepared statements with PDO. Never raw queries.
2. Always set `$module` and include `guard.php` at the top of every page.
3. Never trust `$_GET` or `$_POST` without validation.
4. Always hash passwords with `password_hash()` (bcrypt).
5. Always verify passwords with `password_verify()`.
6. Regenerate session ID after login: `session_regenerate_id(true)`.
7. Validate file uploads server-side (type and size).
8. Never expose raw PHP errors to the user.
9. Never store plain text passwords. Ever.
10. PII fields (email, phone) must be masked in exported reports
    unless the user has explicit permission.

---

## Existing Database Tables

Before creating any new table, check this list to avoid duplicates.
All schema files are in `config/`.

### `config/users.sql` — Users & Access Control

| Table | Primary Key | Purpose |
|---|---|---|
| `departments` | `department_id` | List of university departments |
| `roles` | `role_id` | User roles (admin, it_manager, it_staff, technician, faculty, department_staff, student) |
| `users` | `user_id` | All system users — never DELETE, use `is_active = 0` |
| `user_sso` | `sso_id` | SSO credentials (Google/Microsoft) linked to a user |
| `role_modules` | `id` | Which modules each role can access (`role_id` + `module_slug`) |

**Notable columns on `users`:** `profile_picture VARCHAR(255)` — relative web path to uploaded avatar (nullable).

### `config/notifications.sql` — Notification System

| Table | Primary Key | Purpose |
|---|---|---|
| `notifications` | `notif_id` | In-app notification inbox. One row per recipient per event. |

**Notable columns on `notifications`:**
- `user_id` — recipient (FK → users)
- `title`, `body` — notification content
- `link` — relative URL to navigate to on click (nullable)
- `is_read` — `0` = unread, `1` = read
- `notif_key VARCHAR(255)` — deduplication key for automated alerts.
  Format: `warranty_{asset_id}_{warranty_end}_{threshold}`.
  `UNIQUE KEY (notif_key, user_id)` + `INSERT IGNORE` ensures each automated
  threshold fires exactly once per asset per user, even across many page loads.
  NULL = ad-hoc notification (no dedup enforced).
- `created_at` — timestamp

This file also grants `notifications` and `profile` module access to **all roles** via `INSERT IGNORE INTO role_modules`.

### `config/asset_management.sql` — Asset Management (Module 2)

| Table | Primary Key | Purpose |
|---|---|---|
| `asset_categories` | `category_id` | Equipment types (Projector, Sound System, etc.) |
| `locations` | `location_id` | Rooms in the institution (building + floor + room) |
| `assets` | `asset_id` | Main asset registry — every piece of equipment |
| `asset_warranty` | `warranty_id` | Warranty & contract info per asset (one row per asset) |
| `asset_documents` | `document_id` | Files attached to an asset (full version history) |
| `asset_audit_log` | `log_id` | Immutable log of every field change on an asset |

### Tables expected from other modules (not yet created)

| Table | Owner Module | Notes |
|---|---|---|
| `tickets` | Module 1 (tickets) | Referenced by assets to check open tickets before retiring |

### Key foreign key relationships at a glance

```
departments ──── users ──────────────────────────────┐
roles       ──── users                               │
roles       ──── role_modules                        │
                                                     │
asset_categories ──── assets ◄── asset_warranty      │
locations        ──── assets ◄── asset_documents ────┤
assets (self) ───┘ ◄── asset_audit_log               │
                                                     │
users ◄── assets.owner_id                           ◄┘
users ◄── assets.created_by
users ◄── asset_documents.uploaded_by
users ◄── asset_audit_log.changed_by
users ◄── notifications.user_id (ON DELETE CASCADE)
```

---

## Notification System

### Overview

In-app only. No email. Notifications are stored in the `notifications` table
and surfaced via a bell icon in the topbar. The badge shows the unread count,
updated every 30 seconds via `fetch()`. Clicking the bell opens a dropdown
showing the 10 most recent items.

### How to send a notification

```php
require_once BASE_PATH . '/modules/notifications/functions.php';

// One recipient
notify_user($pdo, $user_id, 'Title', 'Body text', BASE_URL . 'modules/tickets/view.php?id=5');

// Broadcast to a role group (query users first, then loop)
$recipients = $pdo->query("SELECT user_id FROM users WHERE role_id IN (1,2,3) AND is_active = 1")
                  ->fetchAll(PDO::FETCH_COLUMN);
foreach ($recipients as $uid) {
    notify_user($pdo, (int)$uid, 'Title', 'Body', $link);
}
```

For automated alerts that must never be sent twice, pass a `$notif_key`:
```php
notify_user($pdo, $uid, $title, $body, $link, 'warranty_12_2026-05-01_30');
```

`notify_user` uses `INSERT IGNORE` — duplicate keys are silently skipped.

### Automated warranty alerts

`check_warranty_expiry(PDO $pdo)` is called automatically by `guard.php` on
every page load, throttled to once per hour via `$_SESSION['last_warranty_check']`.

- Scans `asset_warranty` for assets expiring within 60 days
- Fires three alert tiers: **7-day**, **30-day**, **60-day**
- Each asset fires only the **most specific (smallest)** applicable tier
- Recipients: `role_id IN (1, 2, 3, 8)` — admin, it_manager, it_staff, super_admin
- `notif_key` format: `warranty_{asset_id}_{warranty_end}_{threshold}`

To add alerts for other events (ticket status changes, work order assignments),
call `notify_user()` inside the relevant `save.php` at the point of the state change.

### AJAX endpoints

| File | Method | Body | Returns |
|------|--------|------|---------|
| `modules/notifications/fetch.php` | GET | — | `{count: int, items: [...]}` |
| `modules/notifications/mark_read.php` | POST | `id=N` | `{ok: true}` |
| `modules/notifications/mark_read.php` | POST | `all=1` | `{ok: true}` |

Both endpoints require an active session (`$_SESSION['user_id']` must be set).
They return `401` if the user is not logged in.

### SQL setup order

```
1. config/users.sql
2. config/asset_management.sql
3. config/notifications.sql         ← creates notifications table + role_modules
4. config/seed_assets.sql           ← optional: sample assets
5. config/notifications_seed.sql    ← optional: 3 test assets with near-expiry warranties
```

---

## Things to Never Do
- Never create a database connection inside a module.
- Never use `mysqli_*` functions. Use PDO only.
- Never use `$_GET` or `$_POST` directly in a SQL query.
- Never delete a user record. Use `is_active = 0`.
- Never edit another team's module folder.
- Never put HTML output in a logic file (index.php, view.php, add.php, edit.php, save.php).
- Never put DB queries or redirects in a view file (*.view.php).
- Never hardcode a user ID, role ID, or module name in a module file.
- Never skip the `$module` + `guard.php` include in a page file.
- Never store passwords in plain text.
- Never edit `includes/header.php`, `navbar.php`, or `footer.php`
  without informing the whole team.
