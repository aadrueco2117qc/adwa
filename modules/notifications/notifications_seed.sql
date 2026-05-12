-- ============================================================
-- MTRTS — Warranty Notification Simulation Seed
-- Run AFTER notifications.sql.
--
-- Adds 3 test assets with warranties expiring at the three
-- alert thresholds (7, 30, 60 days from 2026-04-07).
-- These trigger the warranty notification system immediately.
-- ============================================================

USE mtrts_sql;

-- ------------------------------------------------------------
-- Test assets — one per alert tier
-- category_id 1 = Projector, 4 = Display, 2 = Sound System
-- location_id 1 = Room 101, 3 = Room 201, 5 = Auditorium
-- department_id 1 = IT Department
-- owner_id 1 = System Administrator
-- ------------------------------------------------------------
INSERT INTO assets (
  asset_tag, serial_number, manufacturer, model,
  category_id, status, location_id, install_date,
  department_id, owner_id, created_by
) VALUES
  -- Expiring in 7 days (2026-04-14)
  ('PRJ-TEST-7D',  'TEST-SN-07001', 'Epson',   'EB-PU2010W',
    1, 'active', 1, '2024-04-14', 1, 1, 1),

  -- Expiring in 30 days (2026-05-07)
  ('DSP-TEST-30D', 'TEST-SN-30001', 'Samsung', 'QM98T-B',
    4, 'active', 3, '2024-05-07', 1, 1, 1),

  -- Expiring in 60 days (2026-06-06)
  ('SND-TEST-60D', 'TEST-SN-60001', 'JBL',     'SRX906LA',
    2, 'active', 5, '2024-06-06', 1, 1, 1);


-- ------------------------------------------------------------
-- Warranties — dates relative to 2026-04-07 (today)
-- ------------------------------------------------------------
INSERT INTO asset_warranty (
  asset_id, warranty_start, warranty_end,
  coverage_type, vendor_name, contract_reference
)
SELECT asset_id, '2024-04-14', '2026-04-14',
       'parts_and_labor', 'Epson Philippines', 'TEST-EP-7D'
  FROM assets WHERE asset_tag = 'PRJ-TEST-7D'
UNION ALL
SELECT asset_id, '2024-05-07', '2026-05-07',
       'parts_and_labor', 'Samsung Philippines', 'TEST-SAM-30D'
  FROM assets WHERE asset_tag = 'DSP-TEST-30D'
UNION ALL
SELECT asset_id, '2024-06-06', '2026-06-06',
       'parts_and_labor', 'JBL Philippines', 'TEST-JBL-60D'
  FROM assets WHERE asset_tag = 'SND-TEST-60D';


-- ------------------------------------------------------------
-- Audit log entries for the test assets
-- ------------------------------------------------------------
INSERT INTO asset_audit_log (asset_id, field_name, old_value, new_value, changed_by)
SELECT asset_id, 'created', NULL, asset_tag, 1
FROM assets WHERE asset_tag IN ('PRJ-TEST-7D', 'DSP-TEST-30D', 'SND-TEST-60D');
