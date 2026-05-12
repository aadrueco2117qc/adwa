-- ============================================================
-- MTRTS — Media Technology Repair Tracker System
-- Database Schema: Users & Access Control
-- ============================================================

-- ------------------------------------------------------------
-- TABLE 1: departments
-- Referenced by: users.department_id
-- ------------------------------------------------------------
CREATE TABLE departments (
  department_id   INT           PRIMARY KEY AUTO_INCREMENT,
  department_name VARCHAR(100)  NOT NULL UNIQUE
);

INSERT INTO departments (department_name) VALUES
  ('IT Department'),
  ('College of Nursing'),
  ('College of Engineering'),
  ('College of Education'),
  ('College of Business'),
  ('Registrar'),
  ('Library'),
  ('Administration');


-- ------------------------------------------------------------
-- TABLE 2: roles
-- Eight roles. Seeded on setup, rarely changed.
-- Referenced by: users.role_id, role_modules.role_id
-- ------------------------------------------------------------
CREATE TABLE roles (
  role_id   INT          PRIMARY KEY AUTO_INCREMENT,
  role_name VARCHAR(50)  NOT NULL UNIQUE
);

INSERT INTO roles (role_name) VALUES
  ('admin'),
  ('it_manager'),
  ('it_staff'),
  ('technician'),
  ('faculty'),
  ('department_staff'),
  ('student'),
  ('super_admin');
  -- super_admin: protected system account. Cannot be deactivated by anyone.
  -- Cannot be selected in create/edit user forms.
  -- Can manage (activate/deactivate) admin accounts; admins cannot.
  -- See: config/add_super_admin.sql for migration on existing databases.


-- ------------------------------------------------------------
-- TABLE 3: users
-- References: departments, roles
-- ------------------------------------------------------------
CREATE TABLE users (
  user_id         INT           PRIMARY KEY AUTO_INCREMENT,

  -- Login
  email           VARCHAR(150)  NOT NULL UNIQUE,
    -- Must be unique. Should enforce school email format (at application layer (e.g. must end in @olfu.edu.ph))
  password_hash   VARCHAR(255)  NULL,

  -- Identity
  full_name       VARCHAR(150)  NOT NULL,
  id_number       VARCHAR(50)   NULL UNIQUE,
    -- Student number or employee ID.
    -- NULL is allowed for admin accounts.

  -- Profile
  contact_number  VARCHAR(20)   NULL,
  position        VARCHAR(100)  NULL,
  profile_picture VARCHAR(255)  NULL,
    -- Relative web path: 'public/uploads/avatars/avatar_N_TIMESTAMP.ext'
    -- NULL = use initials avatar. See: modules/profile/upload_avatar.php

  department_id   INT           NULL,

  FOREIGN KEY (department_id)
    REFERENCES departments(department_id),

  -- Access Control
  role_id         INT           NOT NULL,
  FOREIGN KEY (role_id)
    REFERENCES roles(role_id),

  -- Status & Audit
  is_active       TINYINT(1)    NOT NULL DEFAULT 1,
    -- 1 = active, 0 = deactivated..
  created_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
                                ON UPDATE CURRENT_TIMESTAMP,
  last_login      DATETIME      NULL
);

-- Seed: default system administrator account (super_admin role — role_id 8)
-- Change password immediately after setup.
INSERT INTO users (
  email, password_hash, full_name, role_id, is_active
) VALUES (
  'admin@olfu.edu.ph',
  '$2y$10$placeholderHashReplaceThisOnFirstLogin.........',
  'System Administrator',
  8,
  1
);


-- ------------------------------------------------------------
-- TABLE 4: user_sso
-- Stores SSO credentials separately from the main users table.
-- Optional — only exists for users who authenticate via SSO.
-- A user with no row here uses email + password instead.
-- References: users
-- ------------------------------------------------------------
CREATE TABLE user_sso (
  sso_id        INT           PRIMARY KEY AUTO_INCREMENT,
  user_id       INT           NOT NULL UNIQUE,
    -- UNIQUE: one SSO link per user.
    -- Remove UNIQUE constraint later if multi-provider
    -- support is needed (e.g. link both Google + Microsoft).
  provider      VARCHAR(20)   NOT NULL,
    -- 'google' or 'microsoft'
  provider_uid  VARCHAR(255)  NOT NULL UNIQUE,
    -- The unique ID issued by the SSO provider.
    -- Google: numeric string. Microsoft: GUID.
    -- This is the primary lookup key during SSO login.
  avatar_url    VARCHAR(500)  NULL,
    -- Profile photo URL from the SSO provider.
    -- Optional. Can be used for display purposes.
  linked_at     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    -- When the SSO account was linked to this user.

  FOREIGN KEY (user_id)
    REFERENCES users(user_id)
);


-- ------------------------------------------------------------
-- TABLE 5: role_modules
-- Defines which modules each role can access.
-- This is the access control map for the hub-and-module
-- architecture. index.php checks this on every page load.
-- References: roles
-- ------------------------------------------------------------
CREATE TABLE role_modules (
  id            INT          PRIMARY KEY AUTO_INCREMENT,
  role_id       INT          NOT NULL,
  module_slug   VARCHAR(50)  NOT NULL,
    -- Must match the folder name under /modules/
    -- e.g. 'assets', 'tickets', 'workorders',
    --      'technician', 'reports', 'users'
  UNIQUE (role_id, module_slug),
    -- Prevents duplicate assignments
  FOREIGN KEY (role_id)
    REFERENCES roles(role_id)
);

-- Seed: module access per role
-- role_id 1 = admin (all modules)
INSERT INTO role_modules (role_id, module_slug) VALUES
  (1, 'assets'),
  (1, 'tickets'),
  (1, 'workorders'),
  (1, 'technician'),
  (1, 'reports'),
  (1, 'users');

-- role_id 2 = it_manager
INSERT INTO role_modules (role_id, module_slug) VALUES
  (2, 'assets'),
  (2, 'tickets'),
  (2, 'workorders'),
  (2, 'reports');

-- role_id 3 = it_staff
INSERT INTO role_modules (role_id, module_slug) VALUES
  (3, 'assets'),
  (3, 'tickets');

-- role_id 4 = technician
INSERT INTO role_modules (role_id, module_slug) VALUES
  (4, 'technician'),
  (4, 'tickets');

-- role_id 5 = faculty
INSERT INTO role_modules (role_id, module_slug) VALUES
  (5, 'tickets');

-- role_id 6 = department_staff
INSERT INTO role_modules (role_id, module_slug) VALUES
  (6, 'tickets');

-- role_id 7 = student
INSERT INTO role_modules (role_id, module_slug) VALUES
  (7, 'tickets');

-- role_id 8 = super_admin (all modules, same as admin)
INSERT INTO role_modules (role_id, module_slug) VALUES
  (8, 'assets'),
  (8, 'tickets'),
  (8, 'workorders'),
  (8, 'technician'),
  (8, 'reports'),
  (8, 'users');


-- ============================================================
-- VALIDATION RULES (enforced at application layer)
-- Documented here for reference. See config/auth.php.
-- ============================================================
-- 1. email must be unique and valid format.
-- 2. password_hash must never store plain text (use password_hash()).
-- 3. role_name must be unique in roles table.
-- 4. module_slug must be unique per role in role_modules.
-- 5. A user must have at least one role before they can log in.
-- 6. When a user is deactivated (is_active = 0), invalidate
--    all active sessions immediately at application layer.
-- 7. SSO login: match by provider_uid first, then fall back
--    to email match, then link SSO record to existing user.
-- 8. A user row is NEVER deleted. Set is_active = 0 instead.
-- ============================================================


-- ============================================================
-- QUICK REFERENCE: Table relationships
-- ============================================================
--
--  departments          roles
--       |                 |
--       |                 |--- role_modules
--       |                 |
--       └──── users ───────┘
--               |
--           user_sso (optional, only for SSO users)
--
-- ============================================================