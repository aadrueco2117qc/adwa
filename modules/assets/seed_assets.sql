-- ============================================================
-- MTRTS — Media Technology Repair Tracker System
-- Seed Data: Sample Assets
-- ============================================================
-- Run AFTER asset_management.sql.
-- Assumes default seeded data:
--   category_id: 1=Projector, 2=Sound System, 3=AV Switcher,
--                4=Display, 5=Microphone, 6=AV Rack, 7=Camera, 8=Amplifier
--   location_id: 1=Room 101, 2=Room 102, 3=Room 201, 4=Media Lab A,
--                5=Auditorium, 6=Room 204, 7=Library
--   owner_id 1 = admin (System Administrator)
-- ============================================================


-- ------------------------------------------------------------
-- ASSETS
-- status: active | spare | retired
-- bulb_hours: only for category_id = 1 (Projector)
-- ------------------------------------------------------------
USE mtrts_sql;

-- department_id reference (from users.sql seed):
--   1 = IT Department  (owns most AV/tech assets)
--   2 = College of Nursing
--   3 = College of Engineering
--   4 = College of Education
--   5 = College of Business
--   6 = Registrar
--   7 = Library
--   8 = Administration

INSERT INTO assets (
  asset_tag, serial_number, manufacturer, model,
  category_id, status, location_id, parent_asset_id,
  install_date, firmware_version, network_info, bulb_hours,
  department_id, owner_id, created_by
) VALUES

-- ── Projectors (category 1, has bulb_hours) ─────────────────

('PRJ-1001-A', 'EPS-SN-00001', 'Epson',   'EB-2250U',
  1, 'active',  1, NULL, '2022-06-15', '1.04.00', '192.168.1.101', 1840,
  1, 1, 1),

('PRJ-1002-B', 'EPS-SN-00002', 'Epson',   'EB-2250U',
  1, 'active',  2, NULL, '2022-06-15', '1.04.00', '192.168.1.102', 2100,
  1, 1, 1),

('PRJ-1003-C', 'BNQ-SN-00101', 'BenQ',    'MH760',
  1, 'active',  3, NULL, '2021-08-01', '2.00.01', '192.168.1.103', 3450,
  4, 1, 1),

('PRJ-1004-D', 'BNQ-SN-00102', 'BenQ',    'MH760',
  1, 'active',  4, NULL, '2021-08-01', '2.00.01', '192.168.1.104', 3800,
  4, 1, 1),

('PRJ-1005-E', 'PAN-SN-00201', 'Panasonic','PT-VMZ60',
  1, 'active',  5, NULL, '2023-01-10', '3.10.00', '192.168.1.105',  520,
  8, 1, 1),

('PRJ-1006-F', 'PAN-SN-00202', 'Panasonic','PT-VMZ60',
  1, 'spare',   NULL, NULL, '2023-01-10', '3.10.00', NULL,           0,
  1, 1, 1),

('PRJ-1007-G', 'EPS-SN-00003', 'Epson',   'EB-1485Fi',
  1, 'retired', 6, NULL, '2019-03-20', '0.98.00', '192.168.1.106', 6800,
  1, 1, 1),

('PRJ-1008-H', 'EPS-SN-00004', 'Epson',   'EB-1485Fi',
  1, 'retired', 7, NULL, '2019-03-20', '0.98.00', NULL,            7200,
  7, 1, 1),

-- ── Sound Systems (category 2) ───────────────────────────────

('SND-2001-A', 'YMH-SN-00301', 'Yamaha',  'DXL1K',
  2, 'active',  5, NULL, '2021-05-12', NULL, NULL, NULL,
  8, 1, 1),

('SND-2002-B', 'YMH-SN-00302', 'Yamaha',  'DXL1K',
  2, 'active',  4, NULL, '2021-05-12', NULL, NULL, NULL,
  3, 1, 1),

('SND-2003-C', 'BSE-SN-00401', 'Bose',    'L1 Pro8',
  2, 'spare',   NULL, NULL, '2022-11-03', NULL, NULL, NULL,
  1, 1, 1),

('SND-2004-D', 'BSE-SN-00402', 'Bose',    'L1 Pro8',
  2, 'retired', 5, NULL, '2018-09-01', NULL, NULL, NULL,
  8, 1, 1),

-- ── AV Switchers (category 3) ────────────────────────────────

('AVS-3001-A', 'EXT-SN-00501', 'Extron',  'SW4 HD 4K',
  3, 'active',  4, NULL, '2022-02-18', 'v60.14.01', '192.168.1.201', NULL,
  1, 1, 1),

('AVS-3002-B', 'EXT-SN-00502', 'Extron',  'SW4 HD 4K',
  3, 'active',  5, NULL, '2022-02-18', 'v60.14.01', '192.168.1.202', NULL,
  1, 1, 1),

('AVS-3003-C', 'KRN-SN-00601', 'Kramer',  'VS-42H2',
  3, 'spare',   NULL, NULL, '2023-07-01', 'v1.1',  NULL,             NULL,
  1, 1, 1),

-- ── Displays (category 4) ────────────────────────────────────

('DSP-4001-A', 'SAM-SN-00701', 'Samsung', 'QM75B',
  4, 'active',  3, NULL, '2023-03-15', '1220.2', '192.168.1.301', NULL,
  2, 1, 1),

('DSP-4002-B', 'SAM-SN-00702', 'Samsung', 'QM75B',
  4, 'active',  2, NULL, '2023-03-15', '1220.2', '192.168.1.302', NULL,
  3, 1, 1),

('DSP-4003-C', 'LGE-SN-00801', 'LG',      'OLED65C3',
  4, 'active',  1, NULL, '2022-08-20', 'v03.34.40', '192.168.1.303', NULL,
  4, 1, 1),

('DSP-4004-D', 'LGE-SN-00802', 'LG',      'OLED65C3',
  4, 'retired', 7, NULL, '2019-01-10', 'v01.10.00', NULL,             NULL,
  7, 1, 1),

-- ── Microphones (category 5) ─────────────────────────────────

('MIC-5001-A', 'SHR-SN-00901', 'Shure',   'SM58',
  5, 'active',  5, NULL, '2021-04-01', NULL, NULL, NULL,
  8, 1, 1),

('MIC-5002-B', 'SHR-SN-00902', 'Shure',   'SM58',
  5, 'active',  5, NULL, '2021-04-01', NULL, NULL, NULL,
  8, 1, 1),

('MIC-5003-C', 'SHR-SN-00903', 'Shure',   'SM58',
  5, 'spare',   NULL, NULL, '2021-04-01', NULL, NULL, NULL,
  1, 1, 1),

('MIC-5004-D', 'SNH-SN-01001', 'Sennheiser','EW 135P G4',
  5, 'active',  4, NULL, '2022-10-05', NULL, NULL, NULL,
  5, 1, 1),

('MIC-5005-E', 'SNH-SN-01002', 'Sennheiser','EW 135P G4',
  5, 'retired', 4, NULL, '2018-06-15', NULL, NULL, NULL,
  5, 1, 1),

-- ── AV Racks (category 6) ────────────────────────────────────

('RCK-6001-A', 'MWK-SN-01101', 'Middle Atlantic','WRK-4427',
  6, 'active',  4, NULL, '2021-01-20', NULL, NULL, NULL,
  1, 1, 1),

('RCK-6002-B', 'MWK-SN-01102', 'Middle Atlantic','WRK-4427',
  6, 'active',  5, NULL, '2021-01-20', NULL, NULL, NULL,
  1, 1, 1),

-- ── Cameras (category 7) ─────────────────────────────────────

('CAM-7001-A', 'SON-SN-01201', 'Sony',    'SRG-X400',
  7, 'active',  5, NULL, '2022-12-01', 'v1.10', '192.168.1.401', NULL,
  1, 1, 1),

('CAM-7002-B', 'SON-SN-01202', 'Sony',    'SRG-X400',
  7, 'active',  4, NULL, '2022-12-01', 'v1.10', '192.168.1.402', NULL,
  1, 1, 1),

('CAM-7003-C', 'PTZ-SN-01301', 'PTZOptics','PT20X-SDI',
  7, 'spare',   NULL, NULL, '2023-06-10', 'v6.2.19', NULL,           NULL,
  1, 1, 1),

-- ── Amplifiers (category 8) ──────────────────────────────────

('AMP-8001-A', 'CRN-SN-01401', 'Crown',   'XLi1500',
  8, 'active',  5, NULL, '2020-07-15', NULL, NULL, NULL,
  1, 1, 1),

('AMP-8002-B', 'CRN-SN-01402', 'Crown',   'XLi1500',
  8, 'active',  4, NULL, '2020-07-15', NULL, NULL, NULL,
  1, 1, 1),

('AMP-8003-C', 'QSC-SN-01501', 'QSC',     'GX5',
  8, 'retired', 7, NULL, '2017-03-10', NULL, NULL, NULL,
  1, 1, 1);


-- ------------------------------------------------------------
-- PARENT-CHILD RELATIONSHIPS
-- Link some assets as children of AV Racks
-- (must run AFTER the INSERT above so asset_ids exist)
-- Temporarily disable safe update mode for these statements.
-- ------------------------------------------------------------
SET SQL_SAFE_UPDATES = 0;

UPDATE assets SET parent_asset_id = (SELECT asset_id FROM (SELECT asset_id FROM assets WHERE asset_tag = 'RCK-6001-A') t)
  WHERE asset_tag IN ('AVS-3001-A', 'AMP-8002-B');

UPDATE assets SET parent_asset_id = (SELECT asset_id FROM (SELECT asset_id FROM assets WHERE asset_tag = 'RCK-6002-B') t)
  WHERE asset_tag IN ('AVS-3002-B', 'AMP-8001-A');

SET SQL_SAFE_UPDATES = 1;


-- ------------------------------------------------------------
-- WARRANTIES
-- Covers a spread: valid, expiring soon (~30 days), expired
-- ------------------------------------------------------------
INSERT INTO asset_warranty (
  asset_id, warranty_start, warranty_end, coverage_type, vendor_name, contract_reference
)
SELECT asset_id, '2022-06-15', '2025-06-14', 'parts_and_labor', 'Epson Philippines',  'EP-2022-0615' FROM assets WHERE asset_tag = 'PRJ-1001-A'
UNION ALL
SELECT asset_id, '2022-06-15', '2025-06-14', 'parts_and_labor', 'Epson Philippines',  'EP-2022-0615' FROM assets WHERE asset_tag = 'PRJ-1002-B'
UNION ALL
SELECT asset_id, '2021-08-01', '2024-07-31', 'parts',           'BenQ Philippines',   'BQ-2021-0801' FROM assets WHERE asset_tag = 'PRJ-1003-C'
UNION ALL
SELECT asset_id, '2021-08-01', '2024-07-31', 'parts',           'BenQ Philippines',   'BQ-2021-0801' FROM assets WHERE asset_tag = 'PRJ-1004-D'
UNION ALL
SELECT asset_id, '2023-01-10', '2026-01-09', 'parts_and_labor', 'Panasonic PH',       'PAN-2023-0110' FROM assets WHERE asset_tag = 'PRJ-1005-E'
UNION ALL
SELECT asset_id, '2023-01-10', '2026-01-09', 'parts_and_labor', 'Panasonic PH',       'PAN-2023-0110' FROM assets WHERE asset_tag = 'PRJ-1006-F'
UNION ALL
SELECT asset_id, '2021-05-12', '2024-05-11', 'labor',           'Yamaha Music PH',    'YMH-2021-0512' FROM assets WHERE asset_tag = 'SND-2001-A'
UNION ALL
SELECT asset_id, '2021-05-12', '2024-05-11', 'labor',           'Yamaha Music PH',    'YMH-2021-0512' FROM assets WHERE asset_tag = 'SND-2002-B'
UNION ALL
SELECT asset_id, '2022-11-03', '2025-11-02', 'parts_and_labor', 'Bose Philippines',   'BSE-2022-1103' FROM assets WHERE asset_tag = 'SND-2003-C'
UNION ALL
SELECT asset_id, '2022-02-18', '2025-02-17', 'parts_and_labor', 'Extron Electronics', 'EXT-2022-0218' FROM assets WHERE asset_tag = 'AVS-3001-A'
UNION ALL
SELECT asset_id, '2022-02-18', '2025-02-17', 'parts_and_labor', 'Extron Electronics', 'EXT-2022-0218' FROM assets WHERE asset_tag = 'AVS-3002-B'
UNION ALL
SELECT asset_id, '2023-07-01', '2026-06-30', 'parts',           'Kramer Philippines', 'KRN-2023-0701' FROM assets WHERE asset_tag = 'AVS-3003-C'
UNION ALL
SELECT asset_id, '2023-03-15', '2026-03-14', 'parts_and_labor', 'Samsung Philippines','SAM-2023-0315' FROM assets WHERE asset_tag = 'DSP-4001-A'
UNION ALL
SELECT asset_id, '2023-03-15', '2026-03-14', 'parts_and_labor', 'Samsung Philippines','SAM-2023-0315' FROM assets WHERE asset_tag = 'DSP-4002-B'
UNION ALL
SELECT asset_id, '2022-08-20', '2025-08-19', 'onsite',          'LG Philippines',     'LGE-2022-0820' FROM assets WHERE asset_tag = 'DSP-4003-C'
UNION ALL
SELECT asset_id, '2022-12-01', '2025-11-30', 'parts_and_labor', 'Sony Philippines',   'SON-2022-1201' FROM assets WHERE asset_tag = 'CAM-7001-A'
UNION ALL
SELECT asset_id, '2022-12-01', '2025-11-30', 'parts_and_labor', 'Sony Philippines',   'SON-2022-1201' FROM assets WHERE asset_tag = 'CAM-7002-B'
UNION ALL
SELECT asset_id, '2020-07-15', '2023-07-14', 'labor',           'Crown Audio PH',     'CRN-2020-0715' FROM assets WHERE asset_tag = 'AMP-8001-A'
UNION ALL
SELECT asset_id, '2020-07-15', '2023-07-14', 'labor',           'Crown Audio PH',     'CRN-2020-0715' FROM assets WHERE asset_tag = 'AMP-8002-B';


-- ------------------------------------------------------------
-- AUDIT LOG — creation entries for all seeded assets
-- ------------------------------------------------------------
INSERT INTO asset_audit_log (asset_id, field_name, old_value, new_value, changed_by)
SELECT asset_id, 'created', NULL, asset_tag, 1
FROM assets;


-- ============================================================
-- SUMMARY
-- ============================================================
--   Projectors   (category 1): 8  — 5 active, 1 spare, 2 retired
--   Sound Systems (category 2): 4  — 2 active, 1 spare, 1 retired
--   AV Switchers  (category 3): 3  — 2 active, 1 spare
--   Displays      (category 4): 4  — 3 active, 1 retired
--   Microphones   (category 5): 5  — 3 active, 1 spare, 1 retired
--   AV Racks      (category 6): 2  — 2 active
--   Cameras       (category 7): 3  — 2 active, 1 spare
--   Amplifiers    (category 8): 3  — 2 active, 1 retired
--   ─────────────────────────────────────────────────────────
--   Total: 32 assets — 21 active, 5 spare, 6 retired
--
--   Warranties: 19 records
--     - Active (expiry > today):     ~14
--     - Expired (expiry < today):    ~5
--     - Expiring soon (≤ 30 days):   varies by run date
-- ============================================================
