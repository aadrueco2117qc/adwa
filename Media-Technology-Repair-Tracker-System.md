# MTRTS — Media Technology Repair Tracker System
## Functional Requirements

---

## Module 1: Request Submission & Intake

**Purpose:** Provide an easy, structured way for end users to submit repair requests and for the system to capture, validate, and route them correctly.

**Primary Users:** Faculty, Department Staff, Students, IT Help Desk

### Core Features / Functional Requirements

#### Multi-Channel Intake
- Web portal (desktop/mobile responsive)
- Email-to-ticket conversion
- QR/barcode scan from asset tag to prefill asset details

#### Smart Form
Fields:
- Category (e.g., "Projector", "Sound System", "AV Switcher")
- Asset ID
- Location/Room
- Impact
- Urgency
- Description
- Attachments (photo/video)
- Preferred window/time
- Event support flag (urgent pre-class request)
- Dynamic fields based on category (e.g., "Bulb hours" for projectors, "Input source" for switchers)

#### Autofill & Validation
- Prefill user profile, contact, department from directory
- Prefill asset details (model, warranty, vendor) after scan/lookup
- Validate required fields and file type/size for attachments
- Duplicate detection (same asset, same issue within N days)

#### Triage Assist
- Auto-categorization and suggested KB articles based on description
- Detect safety/urgent tags (e.g., room-wide outage) to fast-track priority

#### Routing
- Auto-assign to queue/team by location, category, or building
- Optional approval workflow (e.g., for out-of-warranty or costly repairs)

#### Notifications
- Acknowledgement to requester with ticket ID and ETA window
- Status change alerts (via email, push, or SMS where enabled)

### Workflows
1. Submit → Validate → Route → (Approve) → Open Work Order
2. Event-mode submission (short lead time) → Escalated SLA → Scheduled slot

### Integrations
- SSO/Directory (Azure AD/Entra ID, Google Workspace) for user profile
- Email gateway for email-to-ticket
- Asset DB/CMDB for lookup

### Acceptance Criteria
- As a requester, I can scan an asset tag to prefill details and submit in <60 seconds
- Submitting via email creates a ticket with subject/body parsed, and attachments preserved
- The system suggests at least one relevant KB article for 70%+ of tickets with free-text descriptions

---

## Module 2: Asset and Configuration Management

**Purpose:** Maintain authoritative records for media/AV assets, including warranty, location, and history.

**Primary Users:** IT Helpdesk

### Core Features / Functional Requirements

#### Asset Registry
- Create, import (CSV), and bulk update assets
- Fields: manufacturer, model, serial, asset tag, room/location, install date, status (active/spare/retired), firmware, bulb hours (for projectors), network info where applicable

#### Warranty & Contracts
- Warranty terms, expiry, coverage type (parts/labor/onsite)
- Auto alerts 60/30/7 days before expiry
- Link SLA terms per vendor or contract

#### Relationship Mapping
- Parent-child (e.g., AV rack → mixer, amp, switcher)
- Room → assets mapping

#### History & Documentation
- Attach manuals, wiring diagrams, configuration backups
- Full repair & maintenance history linked to tickets/WOs

#### Tagging & Identification
- Generate/print QR/barcodes
- Track ownership/cost center

### Workflows
1. New asset onboarding → tag printing → room assignment → baseline photos/config
2. Warranty nearing expiry → batch check for outstanding issues → notification to manager

### Validation & Business Rules
- Serial numbers must be unique for same manufacturer
- Cannot retire an asset with open tickets
- Warranty end date > start date and ≥ install date

---

## Module 3: Work Order & Dispatch Management

**Purpose:** Ensure every ticket is correctly classified, scheduled, assigned, and executed as a work order.

**Primary Users:** IT Helpdesk Manager

### Core Features / Functional Requirements

#### Queues & Assignment
- Skill-based and location-based auto-assignment rules
- Manual reassignment with notes

#### Work Order (WO) / Service Request Generation
- One or more WOs per ticket (diagnosis, follow-up)
- Include tasks/checklists per category (e.g., "Projector: lamp, filter, input test")

#### Scheduling
- Calendar view by team/resource/room; avoid double-booking rooms and technicians
- Time windows, buffer times, travel time estimates

#### Parts & Pre-check
- Pre-allocate parts from stock
- RMA flag if under warranty

#### Status Management
`New → Assigned → Scheduled → In Progress → On Hold (waiting parts/vendor/access) → Resolved → Closed`

> Status can vary.

#### Knowledge Aids
- Playbooks/triage scripts
- Known issues by model/location

### Validation & Business Rules
- Mandatory checklist items must be completed before WO can be set to Resolved
- SLA timers respect business hours and holidays
- Can't schedule in a room that's booked for a class/event (if integrated)

---

## Module 4: Technician Operations (Mobile & Field)

**Purpose:** Equip technicians with a fast, offline-capable mobile experience to execute work orders effectively.

**Primary Users:** IT Support

### Core Features / Functional Requirements

#### Mobile App
- Secure sign-in (SSO, biometric if available)
- Offline mode with later sync & conflict resolution
- My Jobs list with priority indicators and travel time (optional)

#### WO Execution
- Step-by-step checklist with required evidence (photos, videos, measurements)
- Time tracking (start/pause/stop) and labor categorization
- Parts usage: scan parts, decrement stock, capture serials
- Customer communication: call/message requester; arrival ETA
- Sign-off capture: requester digital signature and satisfaction rating
- Safety & pre-flight checks (ESD, power off, ladder safety, etc.)

#### Documentation
- Before/after media
- Quick notes dictation (voice-to-text)
- Attach configuration backups/logs

### Validation & Business Rules
- Can't close WO without mandatory fields, evidence, or signatures (configurable)
- Offline entries must keep local audit hashes for forensics until synced

---

## Module 5: SLA, Reporting, Analytics & Audit

**Purpose:** Define service levels, ensure compliance, and provide insight for continuous improvement.

**Primary Users:** IT Managers, Service Owners, Auditors, Executives

### Core Features / Functional Requirements

#### SLA Engine
- SLA policies by priority, location, category, request type (e.g., event support)
- Working calendars, holidays, time-zone aware
- Clock pause rules (awaiting parts/vendor/access)
- Multi-stage targets: Response, Diagnosis, Resolution
- Escalations: time-based, breach warnings, hierarchical on-call

#### Reporting
- Dashboards: backlog, aging, MTTR, FTFR, SLA compliance, cost per ticket/asset, asset failure hotspots
- Technician scorecards and heatmaps by building/room/time
- Warranty exposure and upcoming expirations

#### Data Access
- Export (CSV/Excel), scheduled report emails
- BI connectors (e.g., OData/REST) with row-level security

#### Audit & Compliance
- Immutable audit log for tickets, WOs, inventory, approvals
- Data retention policies; PII minimization and masking
- E-discovery support (searchable logs by date/user/object)

### Workflows
1. SLA breach approaching → notify assignee & manager → auto-reprioritize or escalate
2. Monthly operations review: scheduled dashboard PDF to stakeholders

### Validation & Business Rules
- SLAs cannot be changed retroactively without audit justification
- Row-level security ensures users only see data they're permitted to
