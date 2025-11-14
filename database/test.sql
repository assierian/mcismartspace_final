-- =========================
-- Sample Data
-- =========================

INSERT INTO `registrar` (`regid`, `Reg_Email`, `Reg_Password`, `RoleID`) VALUES
(1, 'registrar@gmail.com', '1234', 1);

INSERT INTO `buildings` (`id`, `building_name`, `department`, `number_of_floors`, `created_at`) VALUES
(1, 'Accountancy Building', 'Accountancy', 4, '2025-05-22 12:05:20'),
(2, 'Business Administration Complex', 'Business Administration', 5, '2025-05-22 12:05:20'),
(3, 'Hospitality Management Building', 'Hospitality Management', 3, '2025-05-22 12:05:20'),
(4, 'Education and Arts Building', 'Education and Arts', 4, '2025-05-22 12:05:20'),
(5, 'Criminal Justice Building', 'Criminal Justice', 3, '2025-05-22 12:05:20'),
(6, 'Sports Complex', 'Common', 1, '2025-08-18 21:17:52');

INSERT INTO system_settings (setting_key, setting_value, updated_at) VALUES
('room_status_last_check', '2025-03-31 15:47:03', '2025-03-31 07:47:03');

INSERT INTO rooms (room_name, room_type, capacity, building_id) VALUES 
('ACC-101', 'Classroom', 40, 1),
('ACC-102', 'Classroom', 40, 1),
('ACC-103', 'Classroom', 40, 1),
('ACC-201', 'Classroom', 45, 1),
('ACC-202', 'Classroom', 40, 1),
('ACC-301', 'Classroom', 40, 1),
('ACC-302', 'Classroom', 40, 1),
('BA-101', 'Classroom', 50, 2),
('BA-102', 'Classroom', 40, 2),
('BA-103', 'Classroom', 45, 2),
('BA-201', 'Classroom', 50, 2),
('BA-202', 'Classroom', 40, 2),
('BA-301', 'Classroom', 40, 2),
('BA-302', 'Classroom', 40, 2),
('BA-401', 'Classroom', 40, 2),
('HM-101', 'Classroom', 40, 3),
('HM-102', 'Classroom', 40, 3),
('HM-103', 'Classroom', 40, 3),
('HM-201', 'Classroom', 40, 3),
('HM-202', 'Classroom', 40, 3),
('HM-301', 'Classroom', 40, 3),
('EA-101', 'Classroom', 45, 4),
('EA-102', 'Classroom', 40, 4),
('EA-103', 'Classroom', 40, 4),
('EA-201', 'Classroom', 40, 4),
('EA-202', 'Classroom', 50, 4),
('EA-301', 'Classroom', 40, 4),
('EA-302', 'Classroom', 40, 4),
('CJ-101', 'Classroom', 40, 5),
('CJ-102', 'Classroom', 40, 5),
('CJ-103', 'Classroom', 40, 5),
('CJ-201', 'Classroom', 45, 5),
('CJ-202', 'Classroom', 40, 5),
('CJ-301', 'Classroom', 40, 5),
('GYM-MAIN', 'Gymnasium', 200, 6);

INSERT INTO `equipment` (`id`, `name`, `description`, `category`, `created_at`) VALUES
(1, 'Smart TV', 'A smart television with internet capabilities', 'Electronics', '2025-05-22 12:05:20'),
(2, 'TV Remote', 'Remote control compatible with smart TVs', 'Accessories', '2025-05-22 12:05:20'),
(3, 'Projector', 'Digital projector for presentations', 'Electronics', '2025-05-22 12:05:20'),
(4, 'Electric Fan', 'Oscillating electric fan for ventilation', 'Appliances', '2025-05-22 12:05:20'),
(5, 'Aircon', 'Air conditioning unit for room cooling', 'Appliances', '2025-05-22 12:05:20'),
(6, 'Speaker', 'Audio speaker system for sound output', 'Audio Equipment', '2025-05-22 12:05:20'),
(7, 'Microphone', 'Handheld microphone for voice amplification', 'Audio Equipment', '2025-05-22 12:05:20'),
(8, 'Lapel', 'Clip-on lapel microphone for presentations', 'Audio Equipment', '2025-05-22 12:05:20'),
(9, 'HDMI Cable', 'High-Definition Multimedia Interface cable for audio/video connection', 'Accessories', '2025-05-22 12:05:20');


-- ============================
-- Equipment Units (Assignments)
-- ============================

INSERT INTO equipment_units (equipment_id, room_id, serial_number, status, purchased_at, notes) VALUES
-- Accountancy Building (Rooms ACC-101 to ACC-302)
(1, 1, 'TV-ACC101-001', 'working', '2024-05-01', 'Smart TV for ACC-101'),
(2, 1, 'REMOTE-ACC101-001', 'working', '2024-05-01', 'Remote for Smart TV in ACC-101'),
(9, 1, 'HDMI-ACC101-001', 'working', '2024-05-01', 'HDMI cable for Smart TV'),

(3, 2, 'PROJ-ACC102-001', 'working', '2024-06-01', 'Projector mounted in ACC-102'),
(9, 2, 'HDMI-ACC102-001', 'working', '2024-06-01', 'HDMI cable for projector'),
(4, 2, 'FAN-ACC102-001', 'working', '2023-09-10', 'Wall fan'),

(1, 3, 'TV-ACC103-001', 'working', '2023-08-20', 'Smart TV for lectures'),
(5, 3, 'AIRCON-ACC103-001', 'working', '2023-07-15', 'Aircon unit'),

(3, 4, 'PROJ-ACC201-001', 'working', '2023-10-05', 'Ceiling projector'),
(9, 4, 'HDMI-ACC201-001', 'working', '2023-10-05', 'HDMI cable'),

(1, 5, 'TV-ACC202-001', 'working', '2023-09-01', 'Smart TV'),
(2, 5, 'REMOTE-ACC202-001', 'working', '2023-09-01', 'Remote for Smart TV'),
(5, 5, 'AIRCON-ACC202-001', 'working', '2023-09-01', 'Split-type Aircon'),

(4, 6, 'FAN-ACC301-001', 'working', '2023-09-20', 'Wall fan'),
(3, 7, 'PROJ-ACC302-001', 'working', '2024-01-10', 'Mounted projector'),

-- Business Admin Complex (BA Rooms)
(1, 8, 'TV-BA101-001', 'working', '2024-01-01', 'Smart TV'),
(2, 8, 'REMOTE-BA101-001', 'working', '2024-01-01', 'Remote'),

(3, 9, 'PROJ-BA102-001', 'working', '2024-02-01', 'Projector'),
(9, 9, 'HDMI-BA102-001', 'working', '2024-02-01', 'HDMI Cable'),

(1, 10, 'TV-BA103-001', 'working', '2023-05-10', 'Smart TV'),

(5, 11, 'AIRCON-BA201-001', 'working', '2023-09-01', 'Aircon for large room'),

(1, 12, 'TV-BA202-001', 'working', '2024-02-15', 'Smart TV'),

(3, 13, 'PROJ-BA301-001', 'working', '2024-02-20', 'Mounted Projector'),

(1, 14, 'TV-BA302-001', 'working', '2024-03-01', 'Smart TV'),

(4, 15, 'FAN-BA401-001', 'working', '2024-03-05', 'Wall fan'),

-- Hospitality Management (HM Rooms)
(3, 16, 'PROJ-HM101-001', 'working', '2023-11-10', 'Projector'),
(1, 17, 'TV-HM102-001', 'working', '2023-11-11', 'Smart TV'),
(2, 17, 'REMOTE-HM102-001', 'working', '2023-11-11', 'TV Remote'),

(5, 18, 'AIRCON-HM103-001', 'working', '2023-11-15', 'Aircon'),

(3, 19, 'PROJ-HM201-001', 'working', '2023-12-01', 'Mounted projector'),

(1, 20, 'TV-HM202-001', 'working', '2024-01-15', 'Smart TV'),

(4, 21, 'FAN-HM301-001', 'working', '2024-01-20', 'Wall fan'),

-- Education and Arts (EA Rooms)
(3, 22, 'PROJ-EA101-001', 'working', '2024-02-01', 'Projector for lecture hall'),
(5, 22, 'AIRCON-EA101-001', 'working', '2024-02-01', 'Split-type Aircon'),

(1, 23, 'TV-EA102-001', 'working', '2024-02-02', 'Smart TV'),
(2, 23, 'REMOTE-EA102-001', 'working', '2024-02-02', 'TV Remote'),

(3, 24, 'PROJ-EA103-001', 'working', '2024-02-05', 'Ceiling projector'),

(1, 25, 'TV-EA201-001', 'working', '2024-02-06', 'Smart TV'),
(5, 25, 'AIRCON-EA201-001', 'working', '2024-02-06', 'Aircon'),

(3, 26, 'PROJ-EA202-001', 'working', '2024-02-10', 'Projector'),
(9, 26, 'HDMI-EA202-001', 'working', '2024-02-10', 'HDMI Cable'),

(1, 27, 'TV-EA301-001', 'working', '2024-02-15', 'Smart TV'),
(4, 28, 'FAN-EA302-001', 'working', '2024-02-20', 'Wall fan'),

-- Criminal Justice (CJ Rooms)
(1, 29, 'TV-CJ101-001', 'working', '2023-12-01', 'Smart TV'),
(2, 29, 'REMOTE-CJ101-001', 'working', '2023-12-01', 'Remote'),

(3, 30, 'PROJ-CJ102-001', 'working', '2023-12-02', 'Projector'),

(1, 31, 'TV-CJ103-001', 'working', '2023-12-03', 'Smart TV'),

(5, 32, 'AIRCON-CJ201-001', 'working', '2023-12-04', 'Aircon'),

(3, 33, 'PROJ-CJ202-001', 'working', '2023-12-05', 'Projector'),

(4, 34, 'FAN-CJ301-001', 'working', '2023-12-06', 'Wall fan'),

-- Gymnasium
(6, 35, 'SPK-GYM-001', 'working', '2025-01-05', 'Main speaker system'),
(7, 35, 'MIC-GYM-001', 'working', '2025-01-05', 'Wireless microphone'),
(8, 35, 'LAPEL-GYM-001', 'working', '2025-01-05', 'Lapel mic for announcements');

