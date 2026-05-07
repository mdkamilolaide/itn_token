-- Test Data for ipolongo_test database
-- This file contains realistic test data for comprehensive application testing
-- Including: users, roles, geographic data, system lists, and sample operational records

USE ipolongo_test;

-- Clear existing seed data to avoid duplicates
SET FOREIGN_KEY_CHECKS = 0;
DELETE FROM hhm_distribution;
DELETE FROM hhm_mobilization;
DELETE FROM sys_device_login;
DELETE FROM sys_device_registry;
DELETE FROM usr_identity;
DELETE FROM usr_login;
DELETE FROM usr_role;
DELETE FROM sys_geo_codex;
DELETE FROM ms_geo_ward;
DELETE FROM ms_geo_lga;
DELETE FROM ms_geo_state;
DELETE FROM sys_list_privileges;
DELETE FROM sys_list_platform;
DELETE FROM sys_list_module;
SET FOREIGN_KEY_CHECKS = 1;

-- ==============================================
-- SYSTEM LOOKUP TABLES
-- ==============================================

-- System Modules
INSERT INTO sys_list_module (id, module_name) VALUES
(1, 'activity'),
(2, 'logistics'),
(3, 'mobilization'),
(4, 'distribution'),
(5, 'users'),
(6, 'enetcard'),
(7, 'beneficiary'),
(8, 'reporting'),
(9, 'system'),
(10, 'device'),
(11, 'monitoring'),
(12, 'smc'),
(13, 'training');

-- System Platforms
INSERT INTO sys_list_platform (id, platform_name) VALUES
(1, 'mobile'),
(2, 'pos'),
(3, 'web');

-- System Privileges
INSERT INTO sys_list_privileges (id, privilege_name) VALUES
(1, 'activity'),
(2, 'logistics'),
(3, 'mobilization'),
(4, 'distribution'),
(5, 'users'),
(6, 'dashboard'),
(7, 'enetcard'),
(8, 'beneficiary'),
(9, 'reporting'),
(10, 'system'),
(11, 'allocation'),
(12, 'monitoring'),
(13, 'smc');

-- ==============================================
-- USER ROLES
-- ==============================================

INSERT INTO usr_role (roleid, role_code, title, system_privilege, platform, module, priority) VALUES
-- Admin and System Roles
(1, 'ADMIN', 'System Administrator', 
 '["activity","logistics","mobilization","distribution","users","dashboard","enetcard","beneficiary","reporting","system","allocation","monitoring","smc"]',
 '["mobile","pos","web"]',
 '["activity","logistics","mobilization","distribution","users","enetcard","beneficiary","reporting","system","device","monitoring","smc","training"]',
 3),

(2, 'ICT4D', 'ICT4D Staff', 
 '["activity","logistics","mobilization","distribution","dashboard","enetcard","beneficiary","reporting","monitoring","smc"]',
 '["mobile","web"]',
 '["activity","logistics","mobilization","distribution","enetcard","beneficiary","reporting","monitoring","smc"]',
 2),

(3, 'CTAT', 'CTAT Admin', 
 '["activity","dashboard","reporting","monitoring","smc"]',
 '["web"]',
 '["activity","reporting","monitoring","smc"]',
 2),

-- Field Roles
(10, 'HHM', 'HH Mobilizer', 
 '["mobilization","dashboard"]',
 '["mobile"]',
 '["mobilization"]',
 1),

(11, 'DIST', 'Distributor', 
 '["distribution","dashboard"]',
 '["mobile","pos"]',
 '["distribution"]',
 1),

(12, 'SUPER', 'Supervisor', 
 '["activity","dashboard","reporting","monitoring"]',
 '["mobile","web"]',
 '["activity","reporting","monitoring"]',
 2),

(13, 'LGA', 'LGA Coordinator', 
 '["activity","logistics","dashboard","reporting","monitoring","smc"]',
 '["mobile","web"]',
 '["activity","logistics","reporting","monitoring","smc"]',
 2),

(14, 'STATE', 'State Team', 
 '["activity","logistics","dashboard","reporting","monitoring","smc","allocation"]',
 '["web"]',
 '["activity","logistics","reporting","monitoring","smc"]',
 2);

-- ==============================================
-- GEOGRAPHIC DATA
-- ==============================================

-- States
INSERT INTO ms_geo_state (StateId, Fullname) VALUES
(7, 'Benue'),
(10, 'Kano'),
(15, 'Lagos');

-- LGAs
INSERT INTO ms_geo_lga (LgaId, LgaName, StateId) VALUES
-- Benue LGAs
(119, 'Ado', 7),
(120, 'Agatu', 7),
(121, 'Apa', 7),
-- Kano LGAs
(200, 'Ajingi', 10),
(201, 'Albasu', 10),
-- Lagos LGAs
(300, 'Agbado/Oke-Odo', 15),
(301, 'Ajeromi-Ifelodun', 15);

-- Wards
INSERT INTO ms_geo_ward (wardid, ward_name, lgaid) VALUES
-- Ado LGA Wards
(2000, 'Akpoge/Ogbilolo', 119),
(2001, 'Apa', 119),
(2002, 'Igumale I', 119),
(2003, 'Igumale II', 119),
-- Agatu LGA Wards
(2010, 'Ogbulu', 120),
(2011, 'Odugbeho', 120),
-- Ajingi LGA Wards (Kano)
(2100, 'Ajingi', 200),
(2101, 'Balare', 200),
-- Agbado LGA Wards (Lagos)
(2200, 'Ward 1', 300),
(2201, 'Ward 2', 300);

-- ==============================================
-- GEO CODEX (Critical for login/permissions)
-- ==============================================

-- State level
INSERT INTO sys_geo_codex (id, geo_level, geo_level_id, geo_value, title, geo_string) VALUES
(1, 'state', 7, 50, 'BENUE', 'Benue'),
(2, 'state', 10, 50, 'KANO', 'Kano'),
(3, 'state', 15, 50, 'LAGOS', 'Lagos'),

-- LGA level
(10, 'lga', 119, 40, 'ADO', 'BENUE > ADO'),
(11, 'lga', 120, 40, 'Agatu', 'BENUE > Agatu'),
(12, 'lga', 200, 40, 'Ajingi', 'KANO > Ajingi'),
(13, 'lga', 300, 40, 'Agbado', 'LAGOS > Agbado'),

-- Ward level
(100, 'ward', 2000, 30, 'Akpoge/Ogbilolo', 'BENUE > ADO > Akpoge/Ogbilolo'),
(101, 'ward', 2001, 30, 'Apa', 'BENUE > ADO > Apa'),
(102, 'ward', 2002, 30, 'Igumale I', 'BENUE > ADO > Igumale I'),
(110, 'ward', 2010, 30, 'Ogbulu', 'BENUE > Agatu > Ogbulu'),
(120, 'ward', 2100, 30, 'Ajingi', 'KANO > Ajingi > Ajingi'),
(130, 'ward', 2200, 30, 'Ward 1', 'LAGOS > Agbado > Ward 1');

-- ==============================================
-- USERS
-- ==============================================

-- Password: Admin@2026 (bcrypt hash)
INSERT INTO usr_login (userid, roleid, loginid, pwd, geo_level, geo_level_id, active) VALUES
(1001, 1, 'admin.user', '$2y$10$vN5YZJqFx7P8yQxLVpH1nO7EZmL8pGqKdRhWvMxNzTcUaQwErTyOu', 'state', 7, 1),
(1002, 2, 'ict4d.ado', '$2y$10$vN5YZJqFx7P8yQxLVpH1nO7EZmL8pGqKdRhWvMxNzTcUaQwErTyOu', 'lga', 119, 1),
(1003, 10, 'mobilizer.ward1', '$2y$10$vN5YZJqFx7P8yQxLVpH1nO7EZmL8pGqKdRhWvMxNzTcUaQwErTyOu', 'ward', 2000, 1),
(1004, 11, 'distributor.ward1', '$2y$10$vN5YZJqFx7P8yQxLVpH1nO7EZmL8pGqKdRhWvMxNzTcUaQwErTyOu', 'ward', 2000, 1),
(1005, 12, 'supervisor.ado', '$2y$10$vN5YZJqFx7P8yQxLVpH1nO7EZmL8pGqKdRhWvMxNzTcUaQwErTyOu', 'lga', 119, 1),
(1006, 13, 'lga.coordinator', '$2y$10$vN5YZJqFx7P8yQxLVpH1nO7EZmL8pGqKdRhWvMxNzTcUaQwErTyOu', 'lga', 119, 1),
(1007, 14, 'state.team', '$2y$10$vN5YZJqFx7P8yQxLVpH1nO7EZmL8pGqKdRhWvMxNzTcUaQwErTyOu', 'state', 7, 1);

-- User identities
INSERT INTO usr_identity (id, userid, first, last, middle, phone, email, gender) VALUES
(1001, 1001, 'System', 'Administrator', NULL, '08012345678', 'admin@ipolongo.test', 'Male'),
(1002, 1002, 'John', 'Okeke', 'Chukwu', '08023456789', 'john.okeke@ipolongo.test', 'Male'),
(1003, 1003, 'Mary', 'Adeola', 'Grace', '08034567890', 'mary.adeola@ipolongo.test', 'Female'),
(1004, 1004, 'Ibrahim', 'Musa', NULL, '08045678901', 'ibrahim.musa@ipolongo.test', 'Male'),
(1005, 1005, 'Grace', 'Audu', 'Blessing', '08056789012', 'grace.audu@ipolongo.test', 'Female'),
(1006, 1006, 'Samuel', 'Oche', 'Peter', '08067890123', 'samuel.oche@ipolongo.test', 'Male'),
(1007, 1007, 'Elizabeth', 'Akume', 'Joy', '08078901234', 'elizabeth.akume@ipolongo.test', 'Female');

-- ==============================================
-- DEVICES
-- ==============================================

INSERT INTO sys_device_registry (id, serial_no, device_code, device_name, connected, connected_loginid, created) VALUES
(1, 'DEVICE001', 'DEV-001', 'Test Mobile Device 1', NOW(), 'mobilizer.ward1', NOW()),
(2, 'DEVICE002', 'DEV-002', 'Test POS Device 1', NOW(), 'distributor.ward1', NOW()),
(3, 'DEVICE003', 'DEV-003', 'Test Mobile Device 2', NOW(), 'supervisor.ado', NOW());

INSERT INTO sys_device_login (id, device_serial, loginid, created) VALUES
(1, 'DEVICE001', 'mobilizer.ward1', NOW()),
(2, 'DEVICE002', 'distributor.ward1', NOW()),
(3, 'DEVICE003', 'supervisor.ado', NOW());

-- ==============================================
-- SAMPLE OPERATIONAL DATA
-- ==============================================

-- Sample mobilization records (simplified based on actual schema)
INSERT INTO hhm_mobilization (hhid, mobilization_date, created, dp_id) VALUES
(1, '2026-01-10 09:00:00', NOW(), 100),
(2, '2026-01-10 10:30:00', NOW(), 100),
(3, '2026-01-11 08:45:00', NOW(), 101);

-- Sample distribution records (simplified based on actual schema)
INSERT INTO hhm_distribution (dis_id, dp_id, hhid, distributor_id, collected_nets, is_gs_net, collected_date, created) VALUES
(1, 100, 1, 1004, 2, 0, '2026-01-15 10:30:00', NOW()),
(2, 100, 2, 1004, 3, 0, '2026-01-15 11:00:00', NOW()),
(3, 101, 3, 1004, 1, 0, '2026-01-16 09:00:00', NOW());

-- ==============================================
-- SUMMARY
-- ==============================================
-- This test dataset includes:
-- - 7 user accounts across different roles (admin, ICT, mobilizer, distributor, supervisor, LGA coordinator, state team)
-- - All passwords: Admin@2026
-- - 3 states, 7 LGAs, 10 wards with proper geographic hierarchy
-- - 3 test devices registered
-- - 3 mobilization records, 3 distribution records
-- ==============================================
