-- ============================================================
-- MTRTS — Media Technology Repair Tracker System
-- Master Database Schema & Seed Data
--
-- Idempotent: safe to run on a new or existing database.
-- Drops and recreates all tables — acts as a full reset.
-- Run this single file to set up or restore the entire schema.
-- ============================================================

CREATE DATABASE IF NOT EXISTS mtrts_sql
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE mtrts_sql;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS wo_time_logs;
DROP TABLE IF EXISTS wo_signoff;
DROP TABLE IF EXISTS wo_media;
DROP TABLE IF EXISTS wo_notes;
DROP TABLE IF EXISTS wo_parts_used;
DROP TABLE IF EXISTS wo_safety_completions;
DROP TABLE IF EXISTS wo_assignment_log;
DROP TABLE IF EXISTS wo_checklist_completions;
DROP TABLE IF EXISTS wo_checklist_items;
DROP TABLE IF EXISTS wo_checklists;
DROP TABLE IF EXISTS work_orders;
DROP TABLE IF EXISTS offline_sync_queue;
DROP TABLE IF EXISTS ticket_sla;
DROP TABLE IF EXISTS ticket_dynamic_fields;
DROP TABLE IF EXISTS ticket_comments;
DROP TABLE IF EXISTS ticket_attachments;
DROP TABLE IF EXISTS tickets;
DROP TABLE IF EXISTS sla_policies;
DROP TABLE IF EXISTS kb_articles;
DROP TABLE IF EXISTS parts_low_stock_alerts;
DROP TABLE IF EXISTS parts_inventory_audit;
DROP TABLE IF EXISTS parts_inventory;
DROP TABLE IF EXISTS asset_documents;
DROP TABLE IF EXISTS asset_audit_log;
DROP TABLE IF EXISTS asset_warranty;
DROP TABLE IF EXISTS assets;
DROP TABLE IF EXISTS locations;
DROP TABLE IF EXISTS asset_categories;
DROP TABLE IF EXISTS wo_safety_checks;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS user_sso;
DROP TABLE IF EXISTS role_modules;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS departments;
DROP TABLE IF EXISTS roles;
DROP TABLE IF EXISTS business_hours;
DROP TABLE IF EXISTS holidays;
DROP TABLE IF EXISTS audit_log;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- MODULE 0: BASE TABLES (NO FOREIGN KEYS)
-- ============================================================

CREATE TABLE roles (
  role_id   INT          PRIMARY KEY AUTO_INCREMENT,
  role_name VARCHAR(50)  NOT NULL UNIQUE
);

CREATE TABLE departments (
  department_id   INT           PRIMARY KEY AUTO_INCREMENT,
  department_name VARCHAR(100)  NOT NULL UNIQUE
);

CREATE TABLE asset_categories (
  category_id    INT           PRIMARY KEY AUTO_INCREMENT,
  category_name  VARCHAR(100)  NOT NULL UNIQUE,
  has_bulb_hours TINYINT(1)    NOT NULL DEFAULT 0,
  description    VARCHAR(255)  NULL,
  created_at     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE locations (
  location_id INT           PRIMARY KEY AUTO_INCREMENT,
  building    VARCHAR(100)  NOT NULL,
  floor       VARCHAR(50)   NOT NULL,
  room        VARCHAR(100)  NOT NULL,
  timezone    VARCHAR(50)   NOT NULL DEFAULT 'Asia/Manila',
  created_at  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,

  UNIQUE (building, floor, room)
);

CREATE TABLE business_hours (
  hour_id     INT        PRIMARY KEY AUTO_INCREMENT,
  day_of_week TINYINT    NOT NULL,
  start_time  TIME       NOT NULL,
  end_time    TIME       NOT NULL,
  is_working  TINYINT(1) NOT NULL DEFAULT 1,

  UNIQUE (day_of_week)
);

CREATE TABLE holidays (
  holiday_id   INT           PRIMARY KEY AUTO_INCREMENT,
  holiday_name VARCHAR(100)  NOT NULL,
  holiday_date DATE          NOT NULL UNIQUE,
  is_recurring TINYINT(1)    NOT NULL DEFAULT 0,
  created_at   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE wo_safety_checks (
  safety_id   INT           PRIMARY KEY AUTO_INCREMENT,
  check_text  VARCHAR(255)  NOT NULL,
  is_mandatory TINYINT(1)   NOT NULL DEFAULT 1,
  sort_order  INT           NOT NULL DEFAULT 0,
  is_active   TINYINT(1)    NOT NULL DEFAULT 1,
  created_at  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE sla_policies (
  policy_id          INT           PRIMARY KEY AUTO_INCREMENT,
  policy_name        VARCHAR(150)  NOT NULL,
  priority           ENUM('low','medium','high','critical') NULL,
  category_id        INT           NULL,
  location_id        INT           NULL,
  request_type       VARCHAR(50)   NULL,
  is_event_support   TINYINT(1)    NOT NULL DEFAULT 0,
  response_minutes   INT           NOT NULL,
  diagnosis_minutes  INT           NOT NULL,
  resolution_minutes INT           NOT NULL,
  uses_business_hours TINYINT(1)  NOT NULL DEFAULT 1,
  is_active          TINYINT(1)   NOT NULL DEFAULT 1,
  created_at         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

  FOREIGN KEY (category_id) REFERENCES asset_categories(category_id),
  FOREIGN KEY (location_id) REFERENCES locations(location_id)
);

CREATE TABLE parts_inventory (
  part_id          INT           PRIMARY KEY AUTO_INCREMENT,
  part_number      VARCHAR(100)  NOT NULL UNIQUE,
  part_name        VARCHAR(150)  NOT NULL,
  description      VARCHAR(255)  NULL,
  manufacturer     VARCHAR(100)  NULL,
  category         VARCHAR(100)  NULL,
  compatible_with  VARCHAR(500)  NULL,
  quantity_on_hand INT           NOT NULL DEFAULT 0,
  reorder_level    INT           NOT NULL DEFAULT 5,
  unit_cost        DECIMAL(10,2) NULL,
  unit_price       DECIMAL(10,2) NULL,
  storage_location VARCHAR(100)  NULL,
  is_active        TINYINT(1)    NOT NULL DEFAULT 1,
  created_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ============================================================
-- MODULE 0: USERS & ACCESS CONTROL
-- ============================================================

CREATE TABLE users (
  user_id         INT           PRIMARY KEY AUTO_INCREMENT,
  email           VARCHAR(150)  NOT NULL UNIQUE,
  password_hash   VARCHAR(255)  NULL,
  full_name       VARCHAR(150)  NOT NULL,
  id_number       VARCHAR(50)   NULL UNIQUE,
  contact_number  VARCHAR(20)   NULL,
  position        VARCHAR(100)  NULL,
  profile_picture VARCHAR(255)  NULL,
  department_id   INT           NULL,
  role_id         INT           NOT NULL,
  is_active       TINYINT(1)    NOT NULL DEFAULT 1,
  created_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  last_login      DATETIME      NULL,

  FOREIGN KEY (department_id) REFERENCES departments(department_id),
  FOREIGN KEY (role_id) REFERENCES roles(role_id)
);

CREATE TABLE user_sso (
  sso_id        INT           PRIMARY KEY AUTO_INCREMENT,
  user_id       INT           NOT NULL UNIQUE,
  provider      VARCHAR(20)   NOT NULL,
  provider_uid  VARCHAR(255)  NOT NULL UNIQUE,
  avatar_url    VARCHAR(500)  NULL,
  linked_at     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,

  FOREIGN KEY (user_id) REFERENCES users(user_id)
);

CREATE TABLE role_modules (
  id          INT          PRIMARY KEY AUTO_INCREMENT,
  role_id     INT          NOT NULL,
  module_slug VARCHAR(50)  NOT NULL,

  UNIQUE (role_id, module_slug),
  FOREIGN KEY (role_id) REFERENCES roles(role_id)
);

CREATE TABLE notifications (
  notif_id   INT           PRIMARY KEY AUTO_INCREMENT,
  user_id    INT           NOT NULL,
  title      VARCHAR(255)  NOT NULL,
  body       TEXT          NULL,
  link       VARCHAR(500)  NULL,
  is_read    TINYINT(1)    NOT NULL DEFAULT 0,
  notif_key  VARCHAR(255)  NULL,
  created_at DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,

  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  UNIQUE KEY uq_notif_key_user (notif_key, user_id)
);

CREATE TABLE audit_log (
  log_id      BIGINT        PRIMARY KEY AUTO_INCREMENT,
  user_id     INT           NULL,
  action      VARCHAR(50)   NOT NULL,
  object_type VARCHAR(50)   NOT NULL,
  object_id   INT           NULL,
  old_values  JSON          NULL,
  new_values  JSON          NULL,
  ip_address  VARCHAR(45)   NULL,
  user_agent  VARCHAR(500)  NULL,
  created_at  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,

  FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- ============================================================
-- MODULE 2: ASSET & CONFIGURATION MANAGEMENT
-- ============================================================

CREATE TABLE assets (
  asset_id         INT           PRIMARY KEY AUTO_INCREMENT,
  asset_tag        VARCHAR(50)   NOT NULL UNIQUE,
  serial_number    VARCHAR(100)  NULL,
  manufacturer     VARCHAR(100)  NOT NULL,
  model            VARCHAR(100)  NOT NULL,
  category_id      INT           NOT NULL,
  status           ENUM('active','spare','retired') NOT NULL DEFAULT 'active',
  location_id      INT           NULL,
  parent_asset_id  INT           NULL,
  install_date     DATE          NOT NULL,
  firmware_version VARCHAR(50)   NULL,
  network_info     VARCHAR(100)  NULL,
  bulb_hours       INT UNSIGNED  NULL,
  department_id    INT           NULL,
  owner_id         INT           NULL,
  qr_code_path     VARCHAR(255)  NULL,
  created_by       INT           NULL,
  created_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  FOREIGN KEY (category_id)     REFERENCES asset_categories(category_id),
  FOREIGN KEY (location_id)     REFERENCES locations(location_id),
  FOREIGN KEY (parent_asset_id) REFERENCES assets(asset_id),
  FOREIGN KEY (department_id)   REFERENCES departments(department_id),
  FOREIGN KEY (owner_id)        REFERENCES users(user_id),
  FOREIGN KEY (created_by)      REFERENCES users(user_id)
);

CREATE TABLE asset_warranty (
  warranty_id        INT           PRIMARY KEY AUTO_INCREMENT,
  asset_id           INT           NOT NULL UNIQUE,
  warranty_start     DATE          NOT NULL,
  warranty_end       DATE          NOT NULL,
  coverage_type      ENUM('parts','labor','parts_and_labor','onsite') NOT NULL,
  vendor_name        VARCHAR(150)  NULL,
  contract_reference VARCHAR(100)  NULL,
  created_at         DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at         DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  FOREIGN KEY (asset_id) REFERENCES assets(asset_id)
);

CREATE TABLE asset_documents (
  document_id   INT           PRIMARY KEY AUTO_INCREMENT,
  asset_id      INT           NOT NULL,
  document_name VARCHAR(255)  NOT NULL,
  file_path     VARCHAR(500)  NOT NULL,
  file_type     VARCHAR(10)   NOT NULL,
  file_size_kb  INT           NOT NULL,
  document_type VARCHAR(50)   NULL,
  version       INT           NOT NULL DEFAULT 1,
  is_latest     TINYINT(1)    NOT NULL DEFAULT 1,
  uploaded_by   INT           NULL,
  uploaded_at   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,

  FOREIGN KEY (asset_id)    REFERENCES assets(asset_id),
  FOREIGN KEY (uploaded_by) REFERENCES users(user_id)
);

CREATE TABLE asset_audit_log (
  log_id        INT           PRIMARY KEY AUTO_INCREMENT,
  asset_id      INT           NOT NULL,
  field_name    VARCHAR(100)  NOT NULL,
  old_value     TEXT          NULL,
  new_value     TEXT          NULL,
  changed_by    INT           NULL,
  changed_at    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  change_reason VARCHAR(255)  NULL,

  FOREIGN KEY (asset_id)   REFERENCES assets(asset_id),
  FOREIGN KEY (changed_by) REFERENCES users(user_id)
);

-- ============================================================
-- MODULE 1: REQUEST SUBMISSION & INTAKE (TICKETS)
-- ============================================================

CREATE TABLE tickets (
  ticket_id        INT           PRIMARY KEY AUTO_INCREMENT,
  ticket_number    VARCHAR(20)   NOT NULL UNIQUE,
  request_type     VARCHAR(50)   NULL,
  requester_id     INT           NOT NULL,
  asset_id         INT           NULL,
  category_id      INT           NULL,
  location_id      INT           NULL,
  model            VARCHAR(255)  NULL,
  asset_tag        VARCHAR(255)  NULL,
  warranty_status  VARCHAR(255)  NULL,
  title            VARCHAR(255)  NOT NULL,
  description      TEXT          NULL,
  impact           ENUM('low','medium','high','critical') NOT NULL DEFAULT 'medium',
  urgency          ENUM('low','medium','high','critical') NOT NULL DEFAULT 'medium',
  priority         ENUM('low','medium','high','critical') NOT NULL DEFAULT 'medium',
  channel          ENUM('web','email','qr_scan','walk_in') NOT NULL DEFAULT 'web',
  is_event_support TINYINT(1)    NOT NULL DEFAULT 0,
  preferred_window DATETIME      NULL,
  status           ENUM('new','assigned','scheduled','in_progress','on_hold','resolved','closed','cancelled') NOT NULL DEFAULT 'new',
  on_hold_reason   ENUM('waiting_parts','waiting_vendor','waiting_access','other') NULL,
  duplicate_of_id  INT           NULL,
  assigned_to      INT           NULL,
  assigned_team    VARCHAR(100)  NULL,
  approved_by      INT           NULL,
  approved_at      DATETIME      NULL,
  resolved_at      DATETIME      NULL,
  closed_at        DATETIME      NULL,
  created_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  FOREIGN KEY (requester_id)    REFERENCES users(user_id),
  FOREIGN KEY (asset_id)        REFERENCES assets(asset_id),
  FOREIGN KEY (category_id)     REFERENCES asset_categories(category_id),
  FOREIGN KEY (location_id)     REFERENCES locations(location_id),
  FOREIGN KEY (duplicate_of_id) REFERENCES tickets(ticket_id),
  FOREIGN KEY (assigned_to)     REFERENCES users(user_id),
  FOREIGN KEY (approved_by)     REFERENCES users(user_id)
);

CREATE TABLE ticket_attachments (
  attachment_id INT           PRIMARY KEY AUTO_INCREMENT,
  ticket_id     INT           NOT NULL,
  file_name     VARCHAR(255)  NOT NULL,
  file_path     VARCHAR(500)  NOT NULL,
  file_type     VARCHAR(10)   NOT NULL,
  file_size_kb  INT           NOT NULL,
  uploaded_by   INT           NULL,
  uploaded_at   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,

  FOREIGN KEY (ticket_id)   REFERENCES tickets(ticket_id),
  FOREIGN KEY (uploaded_by) REFERENCES users(user_id)
);

CREATE TABLE ticket_comments (
  comment_id   INT        PRIMARY KEY AUTO_INCREMENT,
  ticket_id    INT        NOT NULL,
  user_id      INT        NULL,
  comment_text TEXT       NOT NULL,
  is_internal  TINYINT(1) NOT NULL DEFAULT 0,
  created_at   DATETIME   NOT NULL DEFAULT CURRENT_TIMESTAMP,

  FOREIGN KEY (ticket_id) REFERENCES tickets(ticket_id),
  FOREIGN KEY (user_id)   REFERENCES users(user_id)
);

CREATE TABLE ticket_dynamic_fields (
  field_id    INT           PRIMARY KEY AUTO_INCREMENT,
  ticket_id   INT           NOT NULL,
  field_name  VARCHAR(100)  NOT NULL,
  field_value VARCHAR(500)  NULL,

  UNIQUE (ticket_id, field_name),
  FOREIGN KEY (ticket_id) REFERENCES tickets(ticket_id)
);

CREATE TABLE ticket_sla (
  sla_id                  INT        PRIMARY KEY AUTO_INCREMENT,
  ticket_id               INT        NOT NULL UNIQUE,
  policy_id               INT        NOT NULL,
  response_due            DATETIME   NULL,
  diagnosis_due           DATETIME   NULL,
  resolution_due          DATETIME   NULL,
  responded_at            DATETIME   NULL,
  diagnosed_at            DATETIME   NULL,
  resolved_at             DATETIME   NULL,
  is_response_breached    TINYINT(1) NOT NULL DEFAULT 0,
  is_diagnosis_breached   TINYINT(1) NOT NULL DEFAULT 0,
  is_resolution_breached  TINYINT(1) NOT NULL DEFAULT 0,
  paused_at               DATETIME   NULL,
  total_paused_minutes    INT        NOT NULL DEFAULT 0,
  pause_reason            VARCHAR(100) NULL,
  escalation_level        TINYINT    NOT NULL DEFAULT 0,

  FOREIGN KEY (ticket_id) REFERENCES tickets(ticket_id),
  FOREIGN KEY (policy_id) REFERENCES sla_policies(policy_id)
);

CREATE TABLE kb_articles (
  article_id  INT           PRIMARY KEY AUTO_INCREMENT,
  title       VARCHAR(255)  NOT NULL,
  content     TEXT          NOT NULL,
  category_id INT           NULL,
  tags        VARCHAR(500)  NULL,
  is_published TINYINT(1)  NOT NULL DEFAULT 1,
  views       INT           NOT NULL DEFAULT 0,
  created_by  INT           NULL,
  created_at  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  FOREIGN KEY (category_id) REFERENCES asset_categories(category_id),
  FOREIGN KEY (created_by)  REFERENCES users(user_id)
);

-- ============================================================
-- MODULE 3: WORK ORDER & DISPATCH MANAGEMENT
-- ============================================================

CREATE TABLE work_orders (
  wo_id            INT           PRIMARY KEY AUTO_INCREMENT,
  wo_number        VARCHAR(20)   NOT NULL UNIQUE,
  ticket_id        INT           NULL,
  wo_type          ENUM('diagnosis','repair','maintenance','follow_up') NOT NULL DEFAULT 'repair',
  assigned_role_id INT           NULL,
  assigned_to      INT           NULL,
  assigned_by      INT           NULL,
  claimed_at       DATETIME      NULL,
  claimed_by       INT           NULL,
  status           ENUM('new','assigned','scheduled','in_progress','on_hold','resolved','closed') NOT NULL DEFAULT 'new',
  on_hold_reason   ENUM('waiting_parts','waiting_vendor','waiting_access','other') NULL,
  is_rma           TINYINT(1)    NOT NULL DEFAULT 0,
  scheduled_start  DATETIME      NULL,
  scheduled_end    DATETIME      NULL,
  actual_start     DATETIME      NULL,
  actual_end       DATETIME      NULL,
  notes            TEXT          NULL,
  resolution_notes TEXT          NULL,
  findings         TEXT          NULL,
  actions_taken    TEXT          NULL,
  created_by       INT           NULL,
  created_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  last_synced_at   DATETIME      NULL COMMENT 'Timestamp of last successful sync from offline',
  sync_hash        VARCHAR(64)   NULL COMMENT 'SHA256 hash of synced state for conflict detection',

  FOREIGN KEY (ticket_id)   REFERENCES tickets(ticket_id),
  FOREIGN KEY (assigned_role_id) REFERENCES roles(role_id),
  FOREIGN KEY (assigned_to) REFERENCES users(user_id),
  FOREIGN KEY (assigned_by) REFERENCES users(user_id),
  FOREIGN KEY (claimed_by) REFERENCES users(user_id),
  FOREIGN KEY (created_by)  REFERENCES users(user_id),
  INDEX idx_work_orders_last_synced (last_synced_at)
);

CREATE TABLE wo_assignment_log (
  log_id        INT           PRIMARY KEY AUTO_INCREMENT,
  wo_id         INT           NOT NULL,
  assigned_from INT           NULL,
  assigned_to   INT           NULL,
  assigned_by   INT           NULL,
  reason        VARCHAR(255)  NULL,
  assigned_at   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,

  FOREIGN KEY (wo_id)         REFERENCES work_orders(wo_id),
  FOREIGN KEY (assigned_from) REFERENCES users(user_id),
  FOREIGN KEY (assigned_to)   REFERENCES users(user_id),
  FOREIGN KEY (assigned_by)   REFERENCES users(user_id)
);

CREATE TABLE wo_checklists (
  checklist_id   INT           PRIMARY KEY AUTO_INCREMENT,
  category_id    INT           NULL,
  checklist_name VARCHAR(150)  NOT NULL,
  description    VARCHAR(255)  NULL,
  is_active      TINYINT(1)    NOT NULL DEFAULT 1,
  created_at     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,

  FOREIGN KEY (category_id) REFERENCES asset_categories(category_id)
);

CREATE TABLE wo_checklist_items (
  item_id           INT           PRIMARY KEY AUTO_INCREMENT,
  checklist_id      INT           NOT NULL,
  item_text         VARCHAR(255)  NOT NULL,
  is_mandatory      TINYINT(1)    NOT NULL DEFAULT 0,
  requires_photo    TINYINT(1)    NOT NULL DEFAULT 0,
  is_verifiable     TINYINT(1)    NOT NULL DEFAULT 0,
  verification_type VARCHAR(50)   NULL,
  sort_order        INT           NOT NULL DEFAULT 0,

  FOREIGN KEY (checklist_id) REFERENCES wo_checklists(checklist_id)
);

CREATE TABLE wo_checklist_completions (
  completion_id INT           PRIMARY KEY AUTO_INCREMENT,
  wo_id         INT           NOT NULL,
  item_id       INT           NOT NULL,
  is_done       TINYINT(1)    NOT NULL DEFAULT 0,
  notes         VARCHAR(255)  NULL,
  completed_by  INT           NULL,
  completed_at  DATETIME      NULL,
  photo_path    VARCHAR(500)  NULL COMMENT 'Path to uploaded photo for auto-verification',
  auto_verified TINYINT(1)    NOT NULL DEFAULT 0 COMMENT 'Whether this item was auto-verified by photo upload',

  UNIQUE (wo_id, item_id),
  FOREIGN KEY (wo_id)         REFERENCES work_orders(wo_id),
  FOREIGN KEY (item_id)       REFERENCES wo_checklist_items(item_id),
  FOREIGN KEY (completed_by)  REFERENCES users(user_id)
);

CREATE TABLE wo_notes (
  note_id     INT           PRIMARY KEY AUTO_INCREMENT,
  wo_id       INT           NOT NULL,
  note_type   ENUM('diagnosis','repair','general','system','voice') NOT NULL DEFAULT 'general',
  note_text   TEXT          NOT NULL,
  is_private  TINYINT(1)    NOT NULL DEFAULT 0 COMMENT 'Private notes only visible to technicians',
  is_voice    TINYINT(1)    NOT NULL DEFAULT 0,
  voice_path  VARCHAR(500)  NULL,
  added_by    INT           NULL,
  added_at    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,

  FOREIGN KEY (wo_id)    REFERENCES work_orders(wo_id),
  FOREIGN KEY (added_by) REFERENCES users(user_id)
);

CREATE TABLE wo_media (
  media_id    INT           PRIMARY KEY AUTO_INCREMENT,
  wo_id       INT           NOT NULL,
  media_type  ENUM('before','after','evidence','other','photo_before','photo_after','image','config','video') NOT NULL DEFAULT 'evidence',
  file_path   VARCHAR(500)  NOT NULL,
  file_type   VARCHAR(50)   NOT NULL,
  file_size_kb INT          NOT NULL DEFAULT 0,
  caption     VARCHAR(255)  NULL,
  uploaded_by INT           NULL,
  uploaded_at DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,

  FOREIGN KEY (wo_id)       REFERENCES work_orders(wo_id),
  FOREIGN KEY (uploaded_by) REFERENCES users(user_id)
);

CREATE TABLE wo_time_logs (
  log_id        INT           PRIMARY KEY AUTO_INCREMENT,
  wo_id         INT           NOT NULL,
  technician_id INT           NOT NULL,
  action        ENUM('start','pause','resume','stop','draft','segment') NOT NULL,
  labor_type    ENUM('travel','diagnosis','repair','cleanup','maintenance','follow_up','other') NULL,
  notes         VARCHAR(255)  NULL,
  elapsed_ms    INT           NOT NULL DEFAULT 0,
  logged_at     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,

  FOREIGN KEY (wo_id)         REFERENCES work_orders(wo_id),
  FOREIGN KEY (technician_id) REFERENCES users(user_id)
);

CREATE TABLE wo_safety_completions (
  completion_id INT           PRIMARY KEY AUTO_INCREMENT,
  wo_id         INT           NOT NULL,
  safety_id     INT           NOT NULL,
  is_done       TINYINT(1)    NOT NULL DEFAULT 0,
  completed_by  INT           NULL,
  completed_at  DATETIME      NULL,
  notes         TEXT          NULL COMMENT 'Technician notes explaining why check passed/failed',
  notes_by      INT           NULL COMMENT 'User who added notes',

  UNIQUE (wo_id, safety_id),
  FOREIGN KEY (wo_id)         REFERENCES work_orders(wo_id),
  FOREIGN KEY (safety_id)     REFERENCES wo_safety_checks(safety_id),
  FOREIGN KEY (completed_by)  REFERENCES users(user_id),
  FOREIGN KEY (notes_by)      REFERENCES users(user_id)
);

CREATE TABLE wo_parts_used (
  usage_id        INT           PRIMARY KEY AUTO_INCREMENT,
  wo_id           INT           NOT NULL,
  part_id         INT           NOT NULL,
  quantity_used   INT           NOT NULL DEFAULT 1,
  serial_number   VARCHAR(100)  NULL,
  is_warranty     TINYINT(1)    NOT NULL DEFAULT 0,
  is_preallocated TINYINT(1)    NOT NULL DEFAULT 0,
  used_by         INT           NULL,
  used_at         DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,

  FOREIGN KEY (wo_id)   REFERENCES work_orders(wo_id),
  FOREIGN KEY (part_id) REFERENCES parts_inventory(part_id),
  FOREIGN KEY (used_by) REFERENCES users(user_id)
);

CREATE TABLE wo_signoff (
  signoff_id        INT           PRIMARY KEY AUTO_INCREMENT,
  wo_id             INT           NOT NULL UNIQUE,
  signed_by_user_id INT           NULL,
  signer_name       VARCHAR(150)  NOT NULL,
  signature_path    VARCHAR(500)  NOT NULL,
  satisfaction      TINYINT       NULL,
  feedback          TEXT          NULL,
  signed_at         DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,

  FOREIGN KEY (wo_id)             REFERENCES work_orders(wo_id),
  FOREIGN KEY (signed_by_user_id) REFERENCES users(user_id)
);

CREATE TABLE offline_sync_queue (
  sync_id       INT           PRIMARY KEY AUTO_INCREMENT,
  technician_id INT           NOT NULL,
  wo_id         INT           NULL,
  action_type   ENUM('wo_start','wo_pause','wo_resume','wo_stop','wo_complete','wo_note','wo_photo','wo_checklist','wo_safety','parts_used') NOT NULL,
  payload       JSON          NOT NULL,
  created_at    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  synced_at     DATETIME      NULL,
  sync_status   ENUM('pending','processing','completed','failed') NOT NULL DEFAULT 'pending',
  error_message VARCHAR(500)  NULL,
  retry_count   INT           NOT NULL DEFAULT 0,
  last_retry_at DATETIME      NULL,
  error_reason  VARCHAR(500)  NULL,

  FOREIGN KEY (technician_id) REFERENCES users(user_id),
  FOREIGN KEY (wo_id)         REFERENCES work_orders(wo_id)
);

CREATE TABLE parts_inventory_audit (
  audit_id        INT           PRIMARY KEY AUTO_INCREMENT,
  part_id         INT           NOT NULL,
  wo_id           INT           NULL COMMENT 'WO reference',
  change_type     ENUM('add','subtract','adjust','initial','usage') NOT NULL,
  action          VARCHAR(50)   NULL COMMENT 'Alias for change_type used in some queries',
  quantity_change INT           NOT NULL,
  new_quantity    INT           NOT NULL DEFAULT 0,
  reason          VARCHAR(255)  NULL,
  notes           VARCHAR(500)  NULL,
  reference_id    INT           NULL COMMENT 'WO ID or other reference',
  changed_by      INT           NULL,
  technician_id   INT           NULL,
  changed_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,

  FOREIGN KEY (part_id)    REFERENCES parts_inventory(part_id),
  FOREIGN KEY (changed_by) REFERENCES users(user_id)
);

CREATE TABLE parts_low_stock_alerts (
  alert_id      INT           PRIMARY KEY AUTO_INCREMENT,
  part_id       INT           NOT NULL,
  alert_type    ENUM('low_stock','out_of_stock') NOT NULL,
  threshold     INT           NOT NULL,
  current_stock INT           NOT NULL,
  is_acknowledged TINYINT(1)  NOT NULL DEFAULT 0,
  acknowledged_by INT         NULL,
  acknowledged_at DATETIME    NULL,
  created_at    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,

  FOREIGN KEY (part_id)         REFERENCES parts_inventory(part_id),
  FOREIGN KEY (acknowledged_by) REFERENCES users(user_id)
);

-- ============================================================
-- SEED DATA: ROLES
-- ============================================================

INSERT INTO roles (role_name) VALUES
  ('admin'),
  ('it_manager'),
  ('it_staff'),
  ('technician'),
  ('faculty'),
  ('department_staff'),
  ('student'),
  ('super_admin');

-- ============================================================
-- SEED DATA: DEPARTMENTS
-- ============================================================

INSERT INTO departments (department_name) VALUES
  ('IT Department'),
  ('College of Nursing'),
  ('College of Engineering'),
  ('College of Education'),
  ('College of Business'),
  ('Registrar'),
  ('Library'),
  ('Administration');

-- ============================================================
-- SEED DATA: ASSET CATEGORIES
-- ============================================================

INSERT INTO asset_categories (category_name, has_bulb_hours) VALUES
  ('Projector',    1),
  ('Sound System', 0),
  ('AV Switcher',  0),
  ('Display',      0),
  ('Microphone',   0),
  ('AV Rack',      0),
  ('Camera',       0),
  ('Amplifier',    0);

-- ============================================================
-- SEED DATA: LOCATIONS
-- ============================================================

INSERT INTO locations (building, floor, room) VALUES
  ('CAS BUILDING', '1st Floor', 'ROOM 101'),
  ('CAS BUILDING', '1st Floor', 'ROOM 102'),
  ('CAS BUILDING', '1st Floor', 'ROOM 103'),
  ('CAS BUILDING', '1st Floor', 'ROOM 104'),
  ('CAS BUILDING', '1st Floor', 'ROOM 105'),
  ('CAS BUILDING', '1st Floor', 'ROOM 106'),
  ('CAS BUILDING', '1st Floor', 'ROOM 107'),
  ('CAS BUILDING', '1st Floor', 'ROOM 108'),
  ('CAS BUILDING', '1st Floor', 'COMLAB'),
  ('CAS BUILDING', '1st Floor', 'MAC lab'),
  ('SJB BUILDING', 'Ground Floor', 'AUDITORIUM');

-- ============================================================
-- SEED DATA: BUSINESS HOURS
-- ============================================================

INSERT INTO business_hours (day_of_week, start_time, end_time, is_working) VALUES
  (0, '08:00:00', '17:00:00', 0),
  (1, '08:00:00', '17:00:00', 1),
  (2, '08:00:00', '17:00:00', 1),
  (3, '08:00:00', '17:00:00', 1),
  (4, '08:00:00', '17:00:00', 1),
  (5, '08:00:00', '17:00:00', 1),
  (6, '08:00:00', '17:00:00', 0);

-- ============================================================
-- SEED DATA: HOLIDAYS
-- ============================================================

INSERT INTO holidays (holiday_name, holiday_date, is_recurring) VALUES
  ('New Year''s Day',          '2026-01-01', 1),
  ('People Power Anniversary', '2026-02-25', 1),
  ('Maundy Thursday',          '2026-04-02', 0),
  ('Good Friday',              '2026-04-03', 0),
  ('Araw ng Kagitingan',       '2026-04-09', 1),
  ('Labor Day',                '2026-05-01', 1),
  ('Independence Day',         '2026-06-12', 1),
  ('Ninoy Aquino Day',         '2026-08-21', 1),
  ('National Heroes Day',      '2026-08-31', 0),
  ('All Saints'' Day',         '2026-11-01', 1),
  ('Bonifacio Day',            '2026-11-30', 1),
  ('Christmas Day',            '2026-12-25', 1),
  ('Rizal Day',                '2026-12-30', 1);

-- ============================================================
-- SEED DATA: USERS
-- ============================================================

INSERT INTO users (email, password_hash, full_name, role_id, is_active) VALUES
  ('admin@olfu.edu.ph', '$2y$10$placeholdchrerHashReplaceThisOnFirstLogin.........', 'System Administrator', 8, 1);

-- ============================================================
-- SEED DATA: ROLE MODULES
-- ============================================================

INSERT INTO role_modules (role_id, module_slug) VALUES
  (1,'dashboard'),(1,'assets'),(1,'tickets'),(1,'workorders'),
  (1,'technician'),(1,'reports'),(1,'users'),(1,'inventory'),
  (1,'kb'),(1,'profile'),(1,'notifications');
INSERT INTO role_modules (role_id, module_slug) VALUES
  (2,'dashboard'),(2,'assets'),(2,'tickets'),(2,'workorders'),
  (2,'reports'),(2,'inventory'),(2,'kb'),(2,'profile'),(2,'notifications');
INSERT INTO role_modules (role_id, module_slug) VALUES
  (3,'dashboard'),(3,'assets'),(3,'tickets'),(3,'inventory'),
  (3,'kb'),(3,'profile'),(3,'notifications');
INSERT INTO role_modules (role_id, module_slug) VALUES
  (4,'technician'),(4,'tickets'),(4,'kb'),(4,'profile'),(4,'notifications');
INSERT INTO role_modules (role_id, module_slug) VALUES
  (5,'tickets'),(5,'profile'),(5,'notifications');
INSERT INTO role_modules (role_id, module_slug) VALUES
  (6,'tickets'),(6,'profile'),(6,'notifications');
INSERT INTO role_modules (role_id, module_slug) VALUES
  (7,'tickets'),(7,'profile'),(7,'notifications');
INSERT INTO role_modules (role_id, module_slug) VALUES
  (8,'dashboard'),(8,'assets'),(8,'tickets'),(8,'workorders'),
  (8,'technician'),(8,'reports'),(8,'users'),(8,'inventory'),
  (8,'kb'),(8,'profile'),(8,'notifications');

-- ============================================================
-- SEED DATA: SLA POLICIES
-- ============================================================

INSERT INTO sla_policies (policy_name, priority, is_event_support, response_minutes, diagnosis_minutes, resolution_minutes, uses_business_hours) VALUES
  ('Critical Priority SLA',      'critical', 0,  30,  120,   240, 0),
  ('High Priority SLA',          'high',     0, 120,  480,  1440, 1),
  ('Medium Priority SLA',        'medium',   0, 240, 1440,  2880, 1),
  ('Low Priority SLA',           'low',      0, 480, 2880,  4320, 1),
  ('Event Support (Urgent) SLA', 'critical', 1,  15,   60,   120, 0);

-- ============================================================
-- SEED DATA: KNOWLEDGE BASE ARTICLES
-- ============================================================

INSERT INTO kb_articles (title, content, category_id, tags, created_by) VALUES
  (
    'Projector: No Image / No Signal',
    'Check that the source device is powered on and the correct input is selected on the projector. Verify HDMI/VGA cable connections at both ends. Try a different cable or input port. If still no image, reboot both devices. If the projector lamp indicator is flashing, the lamp may need replacement.',
    1, 'projector,no signal,hdmi,vga,lamp', 1
  ),
  (
    'Projector: How to Check and Reset Bulb Hours',
    'Access the projector menu → Information → Lamp Hours to view current usage. Replacement is typically recommended at 3000–4000 hours depending on the model. After replacing the lamp, reset the counter via Menu → Reset → Lamp Hours Reset.',
    1, 'projector,lamp,bulb hours,reset', 1
  ),
  (
    'Sound System: Feedback / High-Pitched Squeal',
    'Reduce microphone gain on the mixer. Move the microphone away from the speakers. Check that the EQ has no extreme high-frequency boosts. Lower the master volume incrementally until feedback stops, then find the source frequency using a graphic EQ.',
    2, 'sound system,feedback,microphone,mixer,squeal', 1
  ),
  (
    'AV Switcher: Input Not Displaying on Output',
    'Confirm the correct input is selected on the switcher. Check that the source device is outputting a signal at a supported resolution. Inspect all HDMI/HDBaseT cables for damage. Power-cycle the switcher. Check firmware version and update if a known fix is available.',
    3, 'av switcher,input,no signal,hdmi,firmware', 1
  ),
  (
    'Display: No Signal or Black Screen',
    'Ensure the display is set to the correct input source. Check that the source cable is fully seated. Test with a different cable and a different source device. If the display backlight is on but no image appears, the signal source or cable is likely the issue.',
    4, 'display,no signal,black screen,input,cable', 1
  ),
  (
    'How to Submit a Repair Request',
    'Log in to the MTRTS portal. Click "New Ticket" from the dashboard or scan the asset QR code to pre-fill details. Fill in the Category, Location, Impact, Urgency, and a clear Description. Attach a photo of the issue if possible. Submit — you will receive a ticket number and email confirmation.',
    NULL, 'ticket,submit,request,help desk,how to', 1
  );

-- ============================================================
-- SEED DATA: WO CHECKLISTS & ITEMS
-- ============================================================

INSERT INTO wo_checklists (category_id, checklist_name, description) VALUES
  (1, 'Projector Repair Checklist',    'Standard steps for diagnosing and repairing projectors'),
  (2, 'Sound System Repair Checklist', 'Standard steps for diagnosing and repairing sound systems'),
  (3, 'AV Switcher Repair Checklist',  'Standard steps for diagnosing and repairing AV switchers'),
  (4, 'Display Repair Checklist',      'Standard steps for diagnosing and repairing display units'),
  (NULL, 'General Repair Checklist',   'Generic checklist applicable to any equipment type');

INSERT INTO wo_checklist_items (checklist_id, item_text, is_mandatory, requires_photo, is_verifiable, verification_type, sort_order) VALUES
  (1, 'Record current bulb hours',              1, 0, 0, NULL,           1),
  (1, 'Inspect lamp/bulb for damage',           1, 1, 0, NULL,           2),
  (1, 'Clean or replace air filter',            1, 0, 0, NULL,           3),
  (1, 'Test all input sources (HDMI, VGA)',     1, 0, 0, NULL,           4),
  (1, 'Check and adjust focus, zoom, keystone', 0, 0, 0, NULL,           5),
  (1, 'Test remote control functionality',      0, 0, 0, NULL,           6),
  (1, 'Capture after-repair photo',             1, 1, 1, 'photo_after',  7);
INSERT INTO wo_checklist_items (checklist_id, item_text, is_mandatory, requires_photo, is_verifiable, verification_type, sort_order) VALUES
  (2, 'Capture before-repair photo',            1, 1, 1, 'photo_before', 1),
  (2, 'Inspect all cables and connectors',      1, 0, 0, NULL,           2),
  (2, 'Test speaker output (left and right)',   1, 0, 0, NULL,           3),
  (2, 'Check mixer and amplifier settings',     1, 0, 0, NULL,           4),
  (2, 'Test for audio feedback and noise',      1, 0, 0, NULL,           5),
  (2, 'Verify microphone inputs if present',    0, 0, 0, NULL,           6),
  (2, 'Capture after-repair photo',             1, 1, 1, 'photo_after',  7);
INSERT INTO wo_checklist_items (checklist_id, item_text, is_mandatory, requires_photo, is_verifiable, verification_type, sort_order) VALUES
  (3, 'Capture before-repair photo',            1, 1, 1, 'photo_before', 1),
  (3, 'Test all input ports',                   1, 0, 0, NULL,           2),
  (3, 'Test all output ports',                  1, 0, 0, NULL,           3),
  (3, 'Verify input-switching functionality',   1, 0, 0, NULL,           4),
  (3, 'Check and record firmware version',      0, 0, 0, NULL,           5),
  (3, 'Inspect network connection if applicable', 0, 0, 0, NULL,         6),
  (3, 'Capture after-repair photo',             1, 1, 1, 'photo_after',  7);
INSERT INTO wo_checklist_items (checklist_id, item_text, is_mandatory, requires_photo, is_verifiable, verification_type, sort_order) VALUES
  (4, 'Capture before-repair photo',                1, 1, 1, 'photo_before', 1),
  (4, 'Test display at all available inputs',       1, 0, 0, NULL,           2),
  (4, 'Inspect panel for dead pixels or damage',    1, 1, 0, NULL,           3),
  (4, 'Check cables and mounting hardware',         1, 0, 0, NULL,           4),
  (4, 'Verify network/smart features if present',   0, 0, 0, NULL,           5),
  (4, 'Capture after-repair photo',                 1, 1, 1, 'photo_after',  6);
INSERT INTO wo_checklist_items (checklist_id, item_text, is_mandatory, requires_photo, is_verifiable, verification_type, sort_order) VALUES
  (5, 'Capture before-repair photo',   1, 1, 1, 'photo_before', 1),
  (5, 'Perform visual inspection',     1, 0, 0, NULL,           2),
  (5, 'Perform power-on test',         1, 0, 0, NULL,           3),
  (5, 'Verify core functionality',     1, 0, 0, NULL,           4),
  (5, 'Document findings and actions', 1, 0, 0, NULL,           5),
  (5, 'Capture after-repair photo',    1, 1, 1, 'photo_after',  6);

-- ============================================================
-- SEED DATA: SAFETY CHECKS
-- ============================================================

INSERT INTO wo_safety_checks (check_text, is_mandatory, sort_order) VALUES
  ('Verify ESD protection (wrist strap or mat) is in place', 1, 1),
  ('Confirm equipment is powered off and unplugged', 1, 2),
  ('Ensure proper personal protective equipment (PPE) is worn', 1, 3),
  ('Clear workspace of clutter and hazards', 1, 4),
  ('Verify tools are in good working condition', 1, 5),
  ('Check for visible damage before starting work', 1, 6),
  ('Test bench voltage verified safe (multimeter checked)', 1, 7),
  ('Required parts and replacements inventoried', 1, 8),
  ('Static-safe mat and containers in use', 1, 9),
  ('Proper disposal container for old/hazardous parts ready', 1, 10),
  ('Safety glasses or face shield worn (when required)', 1, 11);

  CREATE TABLE IF NOT EXISTS `wo_safety_checks` (
  `safety_id` int(11) NOT NULL AUTO_INCREMENT,
  `check_text` varchar(255) NOT NULL,
  `is_mandatory` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`safety_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `wo_safety_completions` (
  `completion_id` int(11) NOT NULL AUTO_INCREMENT,
  `wo_id` int(11) NOT NULL,
  `safety_id` int(11) NOT NULL,
  `is_done` tinyint(1) NOT NULL DEFAULT 0,
  `notes` varchar(255) DEFAULT NULL,
  `completed_by` int(11) DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`completion_id`),
  UNIQUE KEY `idx_wo_safety` (`wo_id`,`safety_id`),
  KEY `safety_id` (`safety_id`),
  KEY `completed_by` (`completed_by`),
  CONSTRAINT `fk_safety_comp_wo` FOREIGN KEY (`wo_id`) REFERENCES `work_orders` (`wo_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_safety_comp_check` FOREIGN KEY (`safety_id`) REFERENCES `wo_safety_checks` (`safety_id`),
  CONSTRAINT `fk_safety_comp_user` FOREIGN KEY (`completed_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO `wo_safety_checks` (`safety_id`, `check_text`, `is_mandatory`, `sort_order`) VALUES
(1, 'Device Unplugged & Discharged', 1, 10),
(2, 'Anti-Static Wristband Applied', 1, 20),
(3, 'Data Backed Up', 0, 30),
(4, 'Work Area Clear', 1, 40);

-- ============================================================
-- SEED DATA: PARTS INVENTORY
-- Baked in so no separate seed script is needed.
-- Safe to re-run: uses INSERT ... ON DUPLICATE KEY UPDATE.
-- ============================================================

INSERT INTO parts_inventory
    (part_number, part_name, category, quantity_on_hand, reorder_level, unit_price, is_active)
VALUES
  -- Cables
  ('CABLE-HDMI-001',  'HDMI cable',              'cables',      50,  5,  12.50, 1),
  ('CABLE-VGA-001',   'VGA cable',               'cables',      35,  5,   8.00, 1),
  ('CABLE-DP-001',    'DisplayPort cable',       'cables',       3,  5,  15.00, 1),
  ('CABLE-AUX-001',   'AUX 3.5mm cable',         'cables',      45,  5,   5.00, 1),
  ('CABLE-XLR-001',   'XLR cable',               'cables',       2,  5,  18.00, 1),
  ('CABLE-ETH-001',   'Ethernet cable',          'cables',      60, 10,   6.00, 1),
  ('CABLE-USB-001',   'USB cable',               'cables',      55, 10,   7.50, 1),
  ('CABLE-COX-001',   'Coaxial cable',           'cables',       4,  5,   9.00, 1),

  -- Projector
  ('PROJ-LAMP-001',   'Projector lamp',          'projector',    1,  3, 120.00, 1),
  ('PROJ-AFIL-001',   'Air filter',              'projector',   12,  5,   8.50, 1),
  ('PROJ-LCD-001',    'LCD panel',               'projector',    0,  2, 350.00, 1),
  ('PROJ-BALL-001',   'Ballast (lamp driver)',   'projector',    5,  2,  85.00, 1),
  ('PROJ-LENS-001',   'Lens assembly',           'projector',    3,  2, 200.00, 1),
  ('PROJ-DLP-001',    'DLP chip',                'projector',    1,  1, 450.00, 1),
  ('PROJ-FAN-001',    'Cooling fan (projector)', 'projector',    8,  4,  25.00, 1),

  -- Audio
  ('AUD-SPK-001',     'Speaker driver',          'audio',       10,  4,  45.00, 1),
  ('AUD-JACK-001',    'Audio jack 3.5mm',        'audio',       40, 10,   3.50, 1),
  ('AUD-XLR-001',     'XLR connector',           'audio',       25,  8,  12.00, 1),
  ('AUD-VOL-001',     'Volume potentiometer',    'audio',        3,  6,   8.00, 1),
  ('AUD-AMP-001',     'Amplifier board',         'audio',        2,  3,  75.00, 1),
  ('AUD-TRF-001',     'Audio transformer',       'audio',        6,  3,  35.00, 1),

  -- Electrical
  ('ELEC-PWR-001',    'Power cable (AC)',        'electrical',  30,  8,  10.00, 1),
  ('ELEC-ADP-001',    'Power adapter',           'electrical',  18,  6,  22.00, 1),
  ('ELEC-F5A-001',    'Fuse 5A',                 'electrical',   5, 15,   2.50, 1),
  ('ELEC-F10A-001',   'Fuse 10A',                'electrical',   8, 15,   2.75, 1),
  ('ELEC-CBK-001',    'Circuit breaker',         'electrical',  10,  4,  18.00, 1),
  ('ELEC-PST-001',    'Power strip',             'electrical',  12,  5,  28.00, 1),
  ('ELEC-SPG-001',    'Surge protector',         'electrical',   4,  5,  35.00, 1),

  -- Electronic
  ('ELCN-C100-001',   'Capacitor 100uF',         'electronic',  80, 20,   1.50, 1),
  ('ELCN-C470-001',   'Capacitor 470uF',         'electronic',  15, 20,   2.00, 1),
  ('ELCN-R10K-001',   'Resistor 10kOhm',         'electronic', 100, 25,   0.50, 1),
  ('ELCN-DDE-001',    'Diode',                   'electronic',  90, 25,   0.75, 1),
  ('ELCN-TRN-001',    'Transistor',              'electronic',  18, 20,   1.25, 1),
  ('ELCN-RLY-001',    'IC relay',                'electronic',  35, 10,   5.50, 1),
  ('ELCN-MOS-001',    'MOSFET',                  'electronic',  50, 15,   3.00, 1),

  -- Cooling
  ('COOL-F80-001',    'Cooling fan 80mm',        'cooling',     14,  6,  12.00, 1),
  ('COOL-F120-001',   'Cooling fan 120mm',       'cooling',      5,  6,  15.00, 1),
  ('COOL-TPS-001',    'Thermal paste',           'cooling',     22,  8,   6.50, 1),
  ('COOL-HSK-001',    'Heat sink',               'cooling',      3,  5,  18.00, 1),
  ('COOL-DST-001',    'Dust filter',             'cooling',     28, 10,   4.00, 1),

  -- Mounting
  ('MNT-M3-001',      'M3 screw set',            'mounting',    40, 12,   5.00, 1),
  ('MNT-M4-001',      'M4 screw set',            'mounting',    38, 12,   5.50, 1),
  ('MNT-BKT-001',     'Bracket kit',             'mounting',     6,  8,  15.00, 1),
  ('MNT-TIE-001',     'Cable ties',              'mounting',    75, 20,   3.00, 1),
  ('MNT-WPL-001',     'Wall plate',              'mounting',     2,  6,   8.50, 1),
  ('MNT-RMR-001',     'Rack mount rails',        'mounting',     1,  4,  45.00, 1)

ON DUPLICATE KEY UPDATE
  part_name        = VALUES(part_name),
  category         = VALUES(category),
  quantity_on_hand = VALUES(quantity_on_hand),
  reorder_level    = VALUES(reorder_level),
  unit_price       = VALUES(unit_price),
  is_active        = 1,
  updated_at       = CURRENT_TIMESTAMP;
