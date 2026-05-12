-- ============================================================
-- MTRTS — Notifications System
-- Run AFTER users.sql and asset_management.sql
-- ============================================================

USE mtrts_sql;

-- ------------------------------------------------------------
-- TABLE: notifications
-- In-app notification inbox. One row per recipient per event.
-- notif_key prevents duplicate automated notifications.
-- ------------------------------------------------------------
CREATE TABLE notifications (
  notif_id    INT           PRIMARY KEY AUTO_INCREMENT,
  user_id     INT           NOT NULL,
  title       VARCHAR(255)  NOT NULL,
  body        TEXT          NULL,
  link        VARCHAR(500)  NULL,
    -- Relative URL to navigate to when clicked.
    -- e.g. 'modules/assets/view.php?id=12'
  is_read     TINYINT(1)    NOT NULL DEFAULT 0,
  notif_key   VARCHAR(255)  NULL,
    -- Unique key per automated notification to prevent re-sending.
    -- e.g. 'warranty_12_2026-05-01_30'
    -- NULL = ad-hoc / one-time notification (no dedup enforced).
  created_at  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,

  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  UNIQUE KEY uq_notif_key_user (notif_key, user_id)
    -- Prevents the same automated event from notifying the same user twice.
    -- MySQL allows multiple NULLs in a unique key — ad-hoc notifs are unaffected.
);


-- ------------------------------------------------------------
-- role_modules: profile (all roles — available to every user)
-- ------------------------------------------------------------
INSERT IGNORE INTO role_modules (role_id, module_slug) VALUES
  (1, 'profile'),
  (2, 'profile'),
  (3, 'profile'),
  (4, 'profile'),
  (5, 'profile'),
  (6, 'profile'),
  (7, 'profile'),
  (8, 'profile');


-- ------------------------------------------------------------
-- role_modules: notifications (all roles — available to every user)
-- ------------------------------------------------------------
INSERT IGNORE INTO role_modules (role_id, module_slug) VALUES
  (1, 'notifications'),
  (2, 'notifications'),
  (3, 'notifications'),
  (4, 'notifications'),
  (5, 'notifications'),
  (6, 'notifications'),
  (7, 'notifications'),
  (8, 'notifications');
