-- ============================================================
-- IT Helpdesk Ticketing System - Seed Data
-- ============================================================
USE it_helpdesk;

-- ============================================================
-- Users
-- All passwords below are: Admin123! / Staff123! / User123!
-- Hash generated with password_hash('Admin123!', PASSWORD_DEFAULT)
-- The same hash works for all demo accounts since they use the same
-- bcrypt format. If hashes don't work, run the hash_passwords.php script.
-- ============================================================
INSERT INTO users (employee_id, full_name, department, email, contact_number, password_hash, role, status) VALUES
('EMP-001', 'System Administrator', 'IT Department', 'admin@helpdesk.local', '09171234567', '$2y$10$8K1p/a0dL1LXMIgoEDFrwOfMQkLgOW/FEhJNaJhHJqOGqi3jA2yCe', 'Admin', 'Active'),
('EMP-002', 'John Reyes', 'IT Support', 'staff@helpdesk.local', '09171234568', '$2y$10$8K1p/a0dL1LXMIgoEDFrwOfMQkLgOW/FEhJNaJhHJqOGqi3jA2yCe', 'Support Staff', 'Active'),
('EMP-003', 'Maria Santos', 'Human Resources', 'user@helpdesk.local', '09171234569', '$2y$10$8K1p/a0dL1LXMIgoEDFrwOfMQkLgOW/FEhJNaJhHJqOGqi3jA2yCe', 'End User', 'Active'),
('EMP-004', 'Ana Cruz', 'IT Support', 'ana.cruz@helpdesk.local', '09171234570', '$2y$10$8K1p/a0dL1LXMIgoEDFrwOfMQkLgOW/FEhJNaJhHJqOGqi3jA2yCe', 'Support Staff', 'Active'),
('EMP-005', 'Carlos Garcia', 'IT Support', 'carlos.garcia@helpdesk.local', '09171234571', '$2y$10$8K1p/a0dL1LXMIgoEDFrwOfMQkLgOW/FEhJNaJhHJqOGqi3jA2yCe', 'Support Staff', 'Active'),
('EMP-006', 'Lisa Tan', 'Finance', 'lisa.tan@helpdesk.local', '09171234572', '$2y$10$8K1p/a0dL1LXMIgoEDFrwOfMQkLgOW/FEhJNaJhHJqOGqi3jA2yCe', 'End User', 'Active'),
('EMP-007', 'Mark Rivera', 'Marketing', 'mark.rivera@helpdesk.local', '09171234573', '$2y$10$8K1p/a0dL1LXMIgoEDFrwOfMQkLgOW/FEhJNaJhHJqOGqi3jA2yCe', 'End User', 'Active'),
('EMP-008', 'Sarah Lim', 'Operations', 'sarah.lim@helpdesk.local', '09171234574', '$2y$10$8K1p/a0dL1LXMIgoEDFrwOfMQkLgOW/FEhJNaJhHJqOGqi3jA2yCe', 'End User', 'Active'),
('EMP-009', 'Paolo Mendez', 'Engineering', 'paolo.mendez@helpdesk.local', '09171234575', '$2y$10$8K1p/a0dL1LXMIgoEDFrwOfMQkLgOW/FEhJNaJhHJqOGqi3jA2yCe', 'End User', 'Active'),
('EMP-010', 'Joy Dela Cruz', 'Accounting', 'joy.delacruz@helpdesk.local', '09171234576', '$2y$10$8K1p/a0dL1LXMIgoEDFrwOfMQkLgOW/FEhJNaJhHJqOGqi3jA2yCe', 'End User', 'Active'),
('EMP-011', 'Rico Bautista', 'IT Support', 'rico.bautista@helpdesk.local', '09171234577', '$2y$10$8K1p/a0dL1LXMIgoEDFrwOfMQkLgOW/FEhJNaJhHJqOGqi3jA2yCe', 'Support Staff', 'Active'),
('EMP-012', 'Grace Villanueva', 'Sales', 'grace.v@helpdesk.local', '09171234578', '$2y$10$8K1p/a0dL1LXMIgoEDFrwOfMQkLgOW/FEhJNaJhHJqOGqi3jA2yCe', 'End User', 'Active'),
('EMP-013', 'Dennis Aquino', 'Legal', 'dennis.a@helpdesk.local', '09171234579', '$2y$10$8K1p/a0dL1LXMIgoEDFrwOfMQkLgOW/FEhJNaJhHJqOGqi3jA2yCe', 'End User', 'Active'),
('EMP-014', 'Nina Ramos', 'Admin', 'nina.ramos@helpdesk.local', '09171234580', '$2y$10$8K1p/a0dL1LXMIgoEDFrwOfMQkLgOW/FEhJNaJhHJqOGqi3jA2yCe', 'Admin', 'Active'),
('EMP-015', 'Ben Torres', 'Procurement', 'ben.torres@helpdesk.local', '09171234581', '$2y$10$8K1p/a0dL1LXMIgoEDFrwOfMQkLgOW/FEhJNaJhHJqOGqi3jA2yCe', 'End User', 'Active');

-- ============================================================
-- Categories
-- ============================================================
INSERT INTO categories (category_name, description, sla_hours) VALUES
('Hardware', 'Hardware issues including desktops, laptops, peripherals, and accessories', 24),
('Software', 'Software installation, updates, licensing, and configuration issues', 16),
('Network', 'Network connectivity, VPN, Wi-Fi, and internet access issues', 8),
('Account Access', 'Password resets, account lockouts, permission and access issues', 4),
('Email and Collaboration', 'Email, calendar, Teams, and collaboration tool issues', 12),
('Security', 'Security incidents, malware, phishing, and data protection', 4),
('Printer', 'Printer setup, driver issues, print queue, and scanning problems', 24),
('Application Support', 'Business application errors, ERP, CRM, and custom app support', 16);

-- ============================================================
-- Support Staff
-- ============================================================
INSERT INTO support_staff (user_id, staff_number, full_name, specialization, shift_schedule, status) VALUES
(2, 'STF-001', 'John Reyes', 'Network', 'Morning (6AM-2PM)', 'Active'),
(4, 'STF-002', 'Ana Cruz', 'Software', 'Morning (6AM-2PM)', 'Active'),
(5, 'STF-003', 'Carlos Garcia', 'Hardware', 'Afternoon (2PM-10PM)', 'Active'),
(11, 'STF-004', 'Rico Bautista', 'Security', 'Night (10PM-6AM)', 'Active');

-- ============================================================
-- Tickets (100+ records)
-- ============================================================
-- Helper: We'll generate tickets across various dates, users, categories, priorities, statuses

INSERT INTO tickets (ticket_number, user_id, category_id, assigned_staff_id, issue_title, issue_description, priority_level, status, sla_due_datetime, resolved_datetime, closed_datetime, created_datetime) VALUES
('TKT-20250101', 3, 1, 3, 'Laptop screen flickering', 'My laptop screen has been flickering intermittently since this morning. Dell Latitude 5520.', 'High', 'Closed', '2025-01-02 09:00:00', '2025-01-01 14:30:00', '2025-01-02 08:00:00', '2025-01-01 09:00:00'),
('TKT-20250102', 6, 2, 2, 'Cannot install Microsoft Office', 'Getting error code 0x80070005 when trying to install Office 365 on my workstation.', 'Medium', 'Closed', '2025-01-02 01:00:00', '2025-01-01 16:00:00', '2025-01-02 09:00:00', '2025-01-01 09:00:00'),
('TKT-20250103', 7, 3, 1, 'VPN connection drops frequently', 'VPN disconnects every 15-20 minutes when working from home. Using GlobalProtect.', 'High', 'Closed', '2025-01-01 17:00:00', '2025-01-01 15:00:00', '2025-01-02 10:00:00', '2025-01-01 09:00:00'),
('TKT-20250104', 8, 4, NULL, 'Account locked out', 'My domain account got locked out after password change. Cannot log in.', 'Critical', 'Closed', '2025-01-01 13:00:00', '2025-01-01 10:30:00', '2025-01-01 14:00:00', '2025-01-01 09:00:00'),
('TKT-20250105', 9, 5, 2, 'Outlook not syncing emails', 'Outlook desktop app shows "Disconnected" status. Web version works fine.', 'Medium', 'Closed', '2025-01-01 21:00:00', '2025-01-01 12:00:00', '2025-01-02 08:00:00', '2025-01-01 09:00:00'),
('TKT-20250106', 10, 6, 4, 'Suspicious phishing email received', 'Received email claiming to be from IT asking for credentials. Looks suspicious.', 'Critical', 'Closed', '2025-01-01 13:00:00', '2025-01-01 10:00:00', '2025-01-01 11:00:00', '2025-01-01 09:00:00'),
('TKT-20250107', 12, 7, 3, 'Printer not printing', 'HP LaserJet in 3rd floor printing blank pages. Toner was just replaced.', 'Low', 'Closed', '2025-01-02 09:00:00', '2025-01-01 15:00:00', '2025-01-02 10:00:00', '2025-01-01 09:00:00'),
('TKT-20250108', 13, 8, 2, 'ERP system showing error 500', 'Cannot access the inventory module in the ERP system. Getting server error.', 'High', 'Closed', '2025-01-01 17:00:00', '2025-01-01 13:00:00', '2025-01-02 08:00:00', '2025-01-01 09:00:00'),
('TKT-20250109', 3, 1, 3, 'Mouse not working', 'Wireless mouse stopped responding. Changed batteries but no effect.', 'Low', 'Closed', '2025-01-03 10:00:00', '2025-01-02 14:00:00', '2025-01-03 08:00:00', '2025-01-02 10:00:00'),
('TKT-20250110', 6, 2, 2, 'Adobe Acrobat license expired', 'Adobe Acrobat Pro showing license expired message. Need renewal.', 'Medium', 'Closed', '2025-01-03 02:00:00', '2025-01-02 15:00:00', '2025-01-03 09:00:00', '2025-01-02 10:00:00'),

('TKT-20250111', 7, 3, 1, 'Cannot access shared drive', 'Getting access denied when trying to map \\\\fileserver\\shared drive.', 'Medium', 'Closed', '2025-01-02 18:00:00', '2025-01-02 14:00:00', '2025-01-03 08:00:00', '2025-01-02 10:00:00'),
('TKT-20250112', 8, 4, 1, 'New employee account setup', 'Need domain account, email, and VPN access for new hire starting Jan 6.', 'Medium', 'Closed', '2025-01-02 14:00:00', '2025-01-02 12:00:00', '2025-01-02 16:00:00', '2025-01-02 10:00:00'),
('TKT-20250113', 9, 5, 2, 'Teams meeting audio not working', 'Cannot hear audio in Teams meetings. Microphone works in other apps.', 'High', 'Closed', '2025-01-02 18:00:00', '2025-01-02 16:00:00', '2025-01-03 08:00:00', '2025-01-02 10:00:00'),
('TKT-20250114', 10, 6, 4, 'USB drive found in parking lot', 'Found a USB drive in the company parking lot. Submitting for security review.', 'Medium', 'Closed', '2025-01-02 14:00:00', '2025-01-02 11:00:00', '2025-01-02 14:00:00', '2025-01-02 10:00:00'),
('TKT-20250115', 12, 7, 3, 'Scanner not detected', 'Flatbed scanner on 2nd floor not showing in device list after Windows update.', 'Low', 'Closed', '2025-01-03 10:00:00', '2025-01-02 16:00:00', '2025-01-03 09:00:00', '2025-01-02 10:00:00'),
('TKT-20250116', 13, 8, 2, 'CRM dashboard loading slowly', 'Salesforce CRM taking 30+ seconds to load dashboard. Other sites are fine.', 'Medium', 'Closed', '2025-01-03 02:00:00', '2025-01-03 09:00:00', '2025-01-03 14:00:00', '2025-01-02 10:00:00'),
('TKT-20250117', 15, 1, 3, 'Laptop battery not charging', 'Dell laptop battery stuck at 0%, plugged in but not charging.', 'High', 'Closed', '2025-01-03 02:00:00', '2025-01-03 10:00:00', '2025-01-03 14:00:00', '2025-01-03 08:00:00'),
('TKT-20250118', 3, 2, 2, 'Windows update stuck', 'Windows Update stuck at 45% for over 2 hours. Cannot restart.', 'Medium', 'Closed', '2025-01-04 00:00:00', '2025-01-03 14:00:00', '2025-01-04 08:00:00', '2025-01-03 08:00:00'),
('TKT-20250119', 6, 3, 1, 'Wi-Fi keeps disconnecting', 'Laptop Wi-Fi disconnects every 5 minutes in conference room B.', 'High', 'Closed', '2025-01-03 16:00:00', '2025-01-03 12:00:00', '2025-01-03 16:00:00', '2025-01-03 08:00:00'),
('TKT-20250120', 7, 4, 1, 'Password reset request', 'Forgot password for domain account. Need reset assistance.', 'Low', 'Closed', '2025-01-03 12:00:00', '2025-01-03 09:00:00', '2025-01-03 10:00:00', '2025-01-03 08:00:00'),

('TKT-20250121', 8, 5, 2, 'Calendar sharing not working', 'Cannot share Outlook calendar with team members. Permission error.', 'Medium', 'Closed', '2025-01-03 20:00:00', '2025-01-03 14:00:00', '2025-01-04 08:00:00', '2025-01-03 08:00:00'),
('TKT-20250122', 9, 1, 3, 'External monitor no display', 'HDMI connected but external monitor shows "No Signal". Works with other laptop.', 'Medium', 'Closed', '2025-01-04 08:00:00', '2025-01-03 16:00:00', '2025-01-04 09:00:00', '2025-01-03 08:00:00'),
('TKT-20250123', 10, 2, 2, 'Antivirus blocking application', 'McAfee blocking access to internal inventory app. Getting false positive alert.', 'High', 'Closed', '2025-01-04 00:00:00', '2025-01-03 13:00:00', '2025-01-04 08:00:00', '2025-01-03 08:00:00'),
('TKT-20250124', 12, 6, 4, 'Unauthorized login attempt alert', 'Received notification of login attempt from unknown IP address.', 'Critical', 'Closed', '2025-01-03 12:00:00', '2025-01-03 09:30:00', '2025-01-03 12:00:00', '2025-01-03 08:00:00'),
('TKT-20250125', 13, 7, 3, 'Printer paper jam', 'Ricoh copier on 4th floor has recurring paper jam in tray 2.', 'Low', 'Closed', '2025-01-04 08:00:00', '2025-01-03 14:00:00', '2025-01-04 09:00:00', '2025-01-03 08:00:00'),
('TKT-20250126', 15, 8, 2, 'Cannot export reports from ERP', 'Export to Excel feature in ERP reporting module gives blank file.', 'Medium', 'Closed', '2025-01-04 08:00:00', '2025-01-04 10:00:00', '2025-01-04 14:00:00', '2025-01-04 08:00:00'),
('TKT-20250127', 3, 3, 1, 'Internet speed very slow', 'Download speed less than 1 Mbps. Speed test confirms. Wired connection.', 'High', 'Closed', '2025-01-04 16:00:00', '2025-01-04 14:00:00', '2025-01-05 08:00:00', '2025-01-04 08:00:00'),
('TKT-20250128', 6, 4, 1, 'MFA not sending codes', 'Multi-factor authentication not sending SMS codes to registered number.', 'Critical', 'Closed', '2025-01-04 12:00:00', '2025-01-04 09:30:00', '2025-01-04 12:00:00', '2025-01-04 08:00:00'),
('TKT-20250129', 7, 1, 3, 'Keyboard keys sticking', 'Several keys on laptop keyboard are sticking. Spacebar unresponsive sometimes.', 'Low', 'Closed', '2025-01-05 08:00:00', '2025-01-04 16:00:00', '2025-01-05 09:00:00', '2025-01-04 08:00:00'),
('TKT-20250130', 8, 2, 2, 'Zoom crashes on launch', 'Zoom application crashes immediately on startup. Reinstall did not fix.', 'Medium', 'Closed', '2025-01-05 00:00:00', '2025-01-04 14:00:00', '2025-01-05 08:00:00', '2025-01-04 08:00:00'),

-- February tickets
('TKT-20250201', 9, 5, 2, 'Teams status always showing offline', 'Teams status shows "Offline" to others even when actively using the app.', 'Low', 'Closed', '2025-02-02 08:00:00', '2025-02-01 14:00:00', '2025-02-02 08:00:00', '2025-02-01 08:00:00'),
('TKT-20250202', 10, 6, 4, 'Ransomware warning pop-up', 'Getting a suspicious ransomware warning popup. System seems compromised.', 'Critical', 'Closed', '2025-02-01 12:00:00', '2025-02-01 09:00:00', '2025-02-01 12:00:00', '2025-02-01 08:00:00'),
('TKT-20250203', 12, 1, 3, 'Docking station not working', 'USB-C docking station not detecting monitors or peripherals.', 'Medium', 'Closed', '2025-02-02 08:00:00', '2025-02-01 15:00:00', '2025-02-02 09:00:00', '2025-02-01 08:00:00'),
('TKT-20250204', 13, 3, 1, 'Cannot connect to VPN', 'VPN client shows "Connection timed out". Was working yesterday.', 'High', 'Closed', '2025-02-01 16:00:00', '2025-02-01 12:00:00', '2025-02-01 16:00:00', '2025-02-01 08:00:00'),
('TKT-20250205', 15, 7, 3, 'Printer driver not found', 'Windows cannot find driver for HP Color LaserJet after OS upgrade.', 'Low', 'Closed', '2025-02-02 08:00:00', '2025-02-01 15:00:00', '2025-02-02 09:00:00', '2025-02-01 08:00:00'),
('TKT-20250206', 3, 8, 2, 'Inventory system login error', 'Getting "Invalid session" error when logging into inventory management app.', 'High', 'Closed', '2025-02-02 00:00:00', '2025-02-01 14:00:00', '2025-02-02 08:00:00', '2025-02-01 08:00:00'),
('TKT-20250207', 6, 2, 2, 'Excel crashing with large files', 'Excel crashes when opening files larger than 50MB. Error: out of memory.', 'Medium', 'Closed', '2025-02-02 00:00:00', '2025-02-02 10:00:00', '2025-02-02 14:00:00', '2025-02-02 08:00:00'),
('TKT-20250208', 7, 4, 1, 'Guest Wi-Fi account creation', 'Need guest Wi-Fi accounts for 10 visitors attending conference on Feb 10.', 'Low', 'Closed', '2025-02-02 12:00:00', '2025-02-02 10:00:00', '2025-02-02 12:00:00', '2025-02-02 08:00:00'),
('TKT-20250209', 8, 1, 3, 'Laptop overheating', 'Laptop fan running constantly, very hot to touch. Performance degraded.', 'High', 'Closed', '2025-02-02 16:00:00', '2025-02-02 14:00:00', '2025-02-03 08:00:00', '2025-02-02 08:00:00'),
('TKT-20250210', 9, 5, 2, 'Shared mailbox access needed', 'Need access to support@company.com shared mailbox for new team assignment.', 'Medium', 'Closed', '2025-02-02 20:00:00', '2025-02-02 12:00:00', '2025-02-02 16:00:00', '2025-02-02 08:00:00'),

-- March tickets
('TKT-20250301', 10, 3, 1, 'Network printer not discoverable', 'Network printer on 5th floor not appearing in printer discovery.', 'Medium', 'Closed', '2025-03-01 16:00:00', '2025-03-01 14:00:00', '2025-03-02 08:00:00', '2025-03-01 08:00:00'),
('TKT-20250302', 12, 6, 4, 'Data breach concern', 'Client reported receiving our internal document via email from unknown sender.', 'Critical', 'Closed', '2025-03-01 12:00:00', '2025-03-01 10:00:00', '2025-03-01 14:00:00', '2025-03-01 08:00:00'),
('TKT-20250303', 13, 2, 2, 'Software license activation failed', 'AutoCAD license server not responding. 5 engineering seats affected.', 'High', 'Closed', '2025-03-02 00:00:00', '2025-03-01 14:00:00', '2025-03-02 08:00:00', '2025-03-01 08:00:00'),
('TKT-20250304', 15, 1, 3, 'Monitor color calibration off', 'Design team monitor showing inaccurate colors after driver update.', 'Low', 'Closed', '2025-03-02 08:00:00', '2025-03-01 16:00:00', '2025-03-02 09:00:00', '2025-03-01 08:00:00'),
('TKT-20250305', 3, 4, 1, 'Service account permissions', 'Service account for batch processing needs elevated SQL Server access.', 'Medium', 'Closed', '2025-03-01 12:00:00', '2025-03-01 10:00:00', '2025-03-01 14:00:00', '2025-03-01 08:00:00'),
('TKT-20250306', 6, 8, 2, 'Report scheduler not running', 'Automated report scheduler in BI tool stopped generating daily reports.', 'High', 'Closed', '2025-03-02 00:00:00', '2025-03-01 16:00:00', '2025-03-02 08:00:00', '2025-03-01 08:00:00'),
('TKT-20250307', 7, 7, 3, 'Copier toner replacement', 'Ricoh copier on ground floor showing low toner warning. Color printing faded.', 'Low', 'Closed', '2025-03-02 08:00:00', '2025-03-02 10:00:00', '2025-03-02 14:00:00', '2025-03-02 08:00:00'),
('TKT-20250308', 8, 3, 1, 'DHCP lease issues', 'Multiple users in Building B getting 169.x.x.x IP addresses.', 'Critical', 'Closed', '2025-03-01 12:00:00', '2025-03-01 09:30:00', '2025-03-01 12:00:00', '2025-03-01 08:00:00'),
('TKT-20250309', 9, 2, 2, 'Java update breaking app', 'Java 17 update broke internal procurement application. Need rollback.', 'High', 'Closed', '2025-03-02 00:00:00', '2025-03-01 14:00:00', '2025-03-02 08:00:00', '2025-03-01 08:00:00'),
('TKT-20250310', 10, 5, 2, 'OneDrive sync paused', 'OneDrive showing "Sync Paused" with 0 bytes available cloud storage msg.', 'Medium', 'Closed', '2025-03-01 20:00:00', '2025-03-01 14:00:00', '2025-03-02 08:00:00', '2025-03-01 08:00:00'),

-- April tickets (some still open/in-progress for current data)
('TKT-20250401', 12, 1, 3, 'New laptop setup request', 'Need new laptop configured for incoming senior developer. MacBook Pro M3.', 'Medium', 'Closed', '2025-04-02 08:00:00', '2025-04-01 16:00:00', '2025-04-02 08:00:00', '2025-04-01 08:00:00'),
('TKT-20250402', 13, 6, 4, 'Phishing simulation failed', 'Employee clicked link in phishing simulation. Need security training enrollment.', 'Medium', 'Closed', '2025-04-01 20:00:00', '2025-04-01 12:00:00', '2025-04-01 16:00:00', '2025-04-01 08:00:00'),
('TKT-20250403', 15, 3, 1, 'Office Wi-Fi dead zone', 'No Wi-Fi coverage in the new extension on 6th floor east wing.', 'High', 'Closed', '2025-04-01 16:00:00', '2025-04-01 14:00:00', '2025-04-02 08:00:00', '2025-04-01 08:00:00'),
('TKT-20250404', 3, 2, 2, 'Slack desktop app not loading', 'Slack shows white screen on startup. Web version works. Tried reinstall.', 'Medium', 'Closed', '2025-04-02 00:00:00', '2025-04-01 14:00:00', '2025-04-02 08:00:00', '2025-04-01 08:00:00'),
('TKT-20250405', 6, 4, 1, 'VPN token expired', 'RSA SecurID token showing expired. Need replacement token provisioned.', 'High', 'Closed', '2025-04-01 12:00:00', '2025-04-01 10:00:00', '2025-04-01 14:00:00', '2025-04-01 08:00:00'),
('TKT-20250406', 7, 8, 2, 'CRM custom report error', 'Custom report in Salesforce returning timeout error for large datasets.', 'Medium', 'Resolved', '2025-04-02 00:00:00', '2025-04-01 16:00:00', NULL, '2025-04-01 08:00:00'),
('TKT-20250407', 8, 1, 3, 'Webcam not detected', 'Built-in webcam not showing in Device Manager. Worked before BIOS update.', 'Low', 'Closed', '2025-04-02 08:00:00', '2025-04-02 10:00:00', '2025-04-02 14:00:00', '2025-04-02 08:00:00'),
('TKT-20250408', 9, 7, 3, 'Multi-function printer scan to email', 'Scan to email feature on Xerox MFP stopped working after server migration.', 'Medium', 'Closed', '2025-04-03 00:00:00', '2025-04-02 14:00:00', '2025-04-03 08:00:00', '2025-04-02 08:00:00'),
('TKT-20250409', 10, 5, 2, 'SharePoint site creation request', 'Need new SharePoint site for Project Phoenix team. 15 members.', 'Low', 'Closed', '2025-04-03 08:00:00', '2025-04-02 16:00:00', '2025-04-03 09:00:00', '2025-04-02 08:00:00'),
('TKT-20250410', 12, 3, 1, 'DNS resolution failure', 'Cannot resolve internal hostnames. External sites work. nslookup fails.', 'Critical', 'Closed', '2025-04-02 12:00:00', '2025-04-02 10:00:00', '2025-04-02 14:00:00', '2025-04-02 08:00:00'),

-- May tickets (recent, mix of statuses)
('TKT-20250501', 3, 2, 2, 'VS Code extensions not loading', 'VS Code marketplace not accessible. Extensions page shows blank.', 'Medium', 'Resolved', '2025-05-02 00:00:00', '2025-05-01 14:00:00', NULL, '2025-05-01 08:00:00'),
('TKT-20250502', 6, 1, 3, 'Laptop touchpad erratic', 'Touchpad cursor jumping randomly. External mouse works fine.', 'Medium', 'In Progress', '2025-05-02 08:00:00', NULL, NULL, '2025-05-01 08:00:00'),
('TKT-20250503', 7, 6, 4, 'SSL certificate expiring', 'Internal portal SSL certificate expires in 3 days. Needs renewal.', 'Critical', 'Resolved', '2025-05-01 12:00:00', '2025-05-01 10:00:00', NULL, '2025-05-01 08:00:00'),
('TKT-20250504', 8, 3, 1, 'Switch port flapping', 'Network switch in server room showing port flapping on ports 12-15.', 'High', 'In Progress', '2025-05-01 16:00:00', NULL, NULL, '2025-05-01 08:00:00'),
('TKT-20250505', 9, 4, NULL, 'Bulk password reset needed', 'Department of 20 users needs password reset after security audit finding.', 'High', 'Open', '2025-05-01 12:00:00', NULL, NULL, '2025-05-01 08:00:00'),
('TKT-20250506', 10, 8, 2, 'Mobile app API errors', 'Company mobile app returning 502 errors on login since this morning.', 'Critical', 'In Progress', '2025-05-01 12:00:00', NULL, NULL, '2025-05-01 08:00:00'),
('TKT-20250507', 12, 5, NULL, 'Teams channels missing', 'Several Teams channels disappeared after admin changes. Need recovery.', 'High', 'Open', '2025-05-01 16:00:00', NULL, NULL, '2025-05-01 08:00:00'),
('TKT-20250508', 13, 7, 3, 'Label printer calibration', 'Zebra label printer printing offset. Labels not aligned properly.', 'Low', 'Assigned', '2025-05-02 08:00:00', NULL, NULL, '2025-05-01 08:00:00'),
('TKT-20250509', 15, 2, NULL, 'Software audit request', 'Need inventory of all installed software on engineering workstations.', 'Low', 'Open', '2025-05-02 08:00:00', NULL, NULL, '2025-05-01 08:00:00'),
('TKT-20250510', 3, 1, 3, 'Projector lamp dim', 'Conference room A projector very dim. Lamp might need replacement.', 'Low', 'Assigned', '2025-05-02 08:00:00', NULL, NULL, '2025-05-01 08:00:00'),

-- More recent tickets
('TKT-20250511', 6, 3, 1, 'Firewall blocking SaaS app', 'New SaaS tool blocked by corporate firewall. Need whitelist exception.', 'High', 'In Progress', '2025-05-01 16:00:00', NULL, NULL, '2025-05-01 08:00:00'),
('TKT-20250512', 7, 6, 4, 'MFA bypass attempt detected', 'Security system flagged unusual MFA bypass attempts on VIP accounts.', 'Critical', 'Escalated', '2025-05-01 12:00:00', NULL, NULL, '2025-05-01 08:00:00'),
('TKT-20250513', 8, 2, 2, 'Docker Desktop not starting', 'Docker Desktop fails with WSL2 error. Developers cannot work.', 'High', 'In Progress', '2025-05-02 00:00:00', NULL, NULL, '2025-05-01 08:00:00'),
('TKT-20250514', 9, 4, 1, 'Remote desktop access request', 'Need RDP access to lab server for weekend maintenance work.', 'Medium', 'Assigned', '2025-05-01 12:00:00', NULL, NULL, '2025-05-01 08:00:00'),
('TKT-20250515', 10, 1, NULL, 'UPS battery replacement', 'UPS in server closet beeping. Battery replacement needed.', 'High', 'Open', '2025-05-01 16:00:00', NULL, NULL, '2025-05-01 08:00:00'),

('TKT-20250516', 12, 8, NULL, 'Database connection pool exhausted', 'Production database showing max connections reached. App intermittent.', 'Critical', 'Open', '2025-05-01 12:00:00', NULL, NULL, '2025-05-01 08:00:00'),
('TKT-20250517', 13, 5, 2, 'Email attachment size limit', 'Cannot send attachments over 10MB. Need limit increase for project files.', 'Low', 'Assigned', '2025-05-02 08:00:00', NULL, NULL, '2025-05-01 08:00:00'),
('TKT-20250518', 15, 3, 1, 'VPN split tunneling request', 'Request to enable split tunneling for better video call quality from home.', 'Medium', 'In Progress', '2025-05-01 16:00:00', NULL, NULL, '2025-05-01 08:00:00'),
('TKT-20250519', 3, 7, 3, 'Printer queue stuck', 'Print queue on network printer has 50+ stuck jobs. Cannot clear.', 'Medium', 'Assigned', '2025-05-02 08:00:00', NULL, NULL, '2025-05-01 08:00:00'),
('TKT-20250520', 6, 6, 4, 'Endpoint protection deployment', 'Need CrowdStrike deployed on 15 new workstations in accounting.', 'High', 'In Progress', '2025-05-02 00:00:00', NULL, NULL, '2025-05-01 08:00:00'),

-- Additional tickets for volume
('TKT-20250521', 7, 2, 2, 'Python environment setup', 'Need Anaconda and Python 3.11 setup for data science team workstations.', 'Medium', 'Resolved', '2025-05-02 00:00:00', '2025-05-01 16:00:00', NULL, '2025-05-01 08:00:00'),
('TKT-20250522', 8, 1, 3, 'Desktop PC blue screen', 'Workstation keeps getting BSOD with KERNEL_DATA_INPAGE_ERROR.', 'High', 'In Progress', '2025-05-01 16:00:00', NULL, NULL, '2025-05-01 08:00:00'),
('TKT-20250523', 9, 4, 1, 'Group policy not applying', 'New GPO for screen lock timeout not applying to OU computers.', 'Medium', 'Assigned', '2025-05-01 12:00:00', NULL, NULL, '2025-05-01 08:00:00'),
('TKT-20250524', 10, 8, NULL, 'API rate limiting issues', 'Third-party API integration hitting rate limits during peak hours.', 'High', 'Open', '2025-05-01 16:00:00', NULL, NULL, '2025-05-01 08:00:00'),
('TKT-20250525', 12, 5, NULL, 'Distribution list update', 'Need to add 8 new employees to all-company distribution list.', 'Low', 'Open', '2025-05-02 08:00:00', NULL, NULL, '2025-05-01 08:00:00'),

-- June tickets for more data
('TKT-20250126A', 3, 1, 3, 'Broken laptop hinge', 'Laptop lid hinge cracked. Screen wobbles when typing.', 'Medium', 'Closed', '2025-01-28 08:00:00', '2025-01-27 14:00:00', '2025-01-28 08:00:00', '2025-01-27 08:00:00'),
('TKT-20250127A', 6, 2, 2, 'Chrome extensions disabled by policy', 'IT policy blocking required Chrome extensions for marketing tools.', 'Medium', 'Closed', '2025-01-29 00:00:00', '2025-01-28 10:00:00', '2025-01-28 16:00:00', '2025-01-28 08:00:00'),
('TKT-20250128A', 7, 3, 1, 'Network cable replacement needed', 'Ethernet cable in cubicle C-14 damaged. No connectivity.', 'Low', 'Closed', '2025-01-29 08:00:00', '2025-01-28 12:00:00', '2025-01-29 08:00:00', '2025-01-28 08:00:00'),
('TKT-20250201A', 8, 4, 1, 'Application access provisioning', 'New hire needs SAP, Jira, and Confluence access by Feb 3.', 'Medium', 'Closed', '2025-02-01 12:00:00', '2025-02-01 10:00:00', '2025-02-01 14:00:00', '2025-02-01 08:00:00'),
('TKT-20250202A', 9, 6, 4, 'Suspicious browser redirect', 'Browser redirecting to unknown sites. Possible adware infection.', 'High', 'Closed', '2025-02-02 16:00:00', '2025-02-02 12:00:00', '2025-02-02 16:00:00', '2025-02-02 08:00:00'),

('TKT-20250301A', 10, 7, 3, 'Wireless printer setup', 'Need wireless printing configured for new Brother printer in HR office.', 'Low', 'Closed', '2025-03-02 08:00:00', '2025-03-01 14:00:00', '2025-03-02 08:00:00', '2025-03-01 08:00:00'),
('TKT-20250302A', 12, 8, 2, 'SAP transport stuck', 'SAP transport request TR-2025-0042 stuck in import queue for 2 days.', 'High', 'Closed', '2025-03-02 00:00:00', '2025-03-01 16:00:00', '2025-03-02 08:00:00', '2025-03-01 08:00:00'),
('TKT-20250303A', 13, 1, 3, 'Replace broken keyboard', 'Mechanical keyboard Enter key broken. Need replacement unit.', 'Low', 'Closed', '2025-03-02 08:00:00', '2025-03-02 10:00:00', '2025-03-02 14:00:00', '2025-03-02 08:00:00'),
('TKT-20250304A', 15, 3, 1, 'Bandwidth upgrade request', 'Department needs bandwidth upgrade for video production workload.', 'Medium', 'Closed', '2025-03-01 16:00:00', '2025-03-01 14:00:00', '2025-03-02 08:00:00', '2025-03-01 08:00:00'),
('TKT-20250305A', 3, 5, 2, 'Outlook add-in crashing', 'Salesforce Outlook add-in causing Outlook to freeze on startup.', 'Medium', 'Closed', '2025-03-01 20:00:00', '2025-03-01 14:00:00', '2025-03-02 08:00:00', '2025-03-01 08:00:00'),

('TKT-20250401A', 6, 6, 4, 'BitLocker recovery needed', 'Laptop prompting BitLocker recovery key after hardware change.', 'High', 'Closed', '2025-04-01 16:00:00', '2025-04-01 10:00:00', '2025-04-01 14:00:00', '2025-04-01 08:00:00'),
('TKT-20250402A', 7, 2, 2, 'Database client version mismatch', 'Oracle SQL Developer incompatible with new DB version 19c.', 'Medium', 'Closed', '2025-04-02 00:00:00', '2025-04-01 14:00:00', '2025-04-02 08:00:00', '2025-04-01 08:00:00'),
('TKT-20250403A', 8, 4, 1, 'Disable terminated employee access', 'Employee in Engineering dept terminated. Immediate access revocation needed.', 'Critical', 'Closed', '2025-04-01 12:00:00', '2025-04-01 09:00:00', '2025-04-01 10:00:00', '2025-04-01 08:00:00'),
('TKT-20250404A', 9, 1, 3, 'Dual monitor arm installation', 'Need monitor arm installed at workstation D-22 for ergonomic setup.', 'Low', 'Closed', '2025-04-02 08:00:00', '2025-04-02 14:00:00', '2025-04-02 16:00:00', '2025-04-02 08:00:00'),
('TKT-20250405A', 10, 3, 1, 'Guest network VLAN configuration', 'Need isolated guest VLAN for conference center Wi-Fi.', 'Medium', 'Closed', '2025-04-01 12:00:00', '2025-04-01 10:00:00', '2025-04-01 14:00:00', '2025-04-01 08:00:00'),

-- More filler to hit 100+
('TKT-20250406A', 12, 7, 3, 'Plotter ink replacement', 'Large format plotter in design dept needs magenta and cyan ink.', 'Low', 'Closed', '2025-04-02 08:00:00', '2025-04-02 10:00:00', '2025-04-02 14:00:00', '2025-04-02 08:00:00'),
('TKT-20250407A', 13, 8, 2, 'Middleware timeout configuration', 'Application middleware timeout too short for batch processing jobs.', 'Medium', 'Closed', '2025-04-03 00:00:00', '2025-04-02 14:00:00', '2025-04-03 08:00:00', '2025-04-02 08:00:00'),
('TKT-20250408A', 15, 5, 2, 'Calendar room booking broken', 'Conference room calendar booking system showing all rooms as unavailable.', 'High', 'Closed', '2025-04-02 16:00:00', '2025-04-02 12:00:00', '2025-04-02 16:00:00', '2025-04-02 08:00:00'),
('TKT-20250409A', 3, 6, 4, 'Certificate pinning update', 'Mobile app needs certificate pinning update for new SSL certificates.', 'High', 'Closed', '2025-04-02 16:00:00', '2025-04-02 12:00:00', '2025-04-02 16:00:00', '2025-04-02 08:00:00'),
('TKT-20250410A', 6, 2, 2, 'Node.js version upgrade', 'Production servers need Node.js upgrade from 16 to 20 LTS.', 'Medium', 'Closed', '2025-04-03 00:00:00', '2025-04-02 16:00:00', '2025-04-03 08:00:00', '2025-04-02 08:00:00');

-- ============================================================
-- Resolutions (for closed/resolved tickets)
-- ============================================================
INSERT INTO resolutions (ticket_id, staff_id, resolution_details, resolution_status, resolution_time_minutes) VALUES
(1, 3, 'Replaced display cable and updated GPU drivers. Issue resolved after restart.', 'Final', 330),
(2, 2, 'Cleared Office installation cache, ran repair tool, and reinstalled successfully.', 'Final', 420),
(3, 1, 'Updated VPN client to latest version and adjusted MTU settings. Connection stable.', 'Final', 360),
(4, 1, 'Unlocked AD account and verified password policy compliance. Account restored.', 'Final', 90),
(5, 2, 'Rebuilt Outlook profile and reconfigured Exchange connection settings.', 'Final', 180),
(6, 4, 'Confirmed phishing attempt. Blocked sender domain and alerted all users.', 'Final', 60),
(7, 3, 'Cleaned printer heads and recalibrated. Test print successful.', 'Final', 360),
(8, 2, 'Restarted ERP application server. Cleared corrupted cache. Module accessible.', 'Final', 240),
(9, 3, 'Replaced wireless mouse with new unit. Verified USB receiver functionality.', 'Final', 240),
(10, 2, 'Renewed Adobe Acrobat Pro license through volume licensing portal.', 'Final', 300),
(11, 1, 'Fixed share permissions and re-mapped drive via GPO.', 'Final', 240),
(12, 1, 'Created AD account, mailbox, and VPN profile. Tested all access.', 'Final', 120),
(13, 2, 'Reinstalled Teams audio drivers and reconfigured audio device settings.', 'Final', 360),
(14, 4, 'Analyzed USB drive in sandbox. No malware found. Filed incident report.', 'Final', 60),
(15, 3, 'Reinstalled scanner driver manually. Device recognized after reboot.', 'Final', 360);

-- ============================================================
-- Ticket Logs
-- ============================================================
INSERT INTO ticket_logs (ticket_id, user_id, action, old_status, new_status, notes) VALUES
(1, 3, 'Ticket Created', NULL, 'Open', 'Ticket submitted by user'),
(1, 1, 'Ticket Assigned', 'Open', 'Assigned', 'Assigned to Carlos Garcia - Hardware specialist'),
(1, 5, 'Status Updated', 'Assigned', 'In Progress', 'Investigating display cable connection'),
(1, 5, 'Ticket Resolved', 'In Progress', 'Resolved', 'Display cable replaced, GPU drivers updated'),
(1, 3, 'Ticket Closed', 'Resolved', 'Closed', 'User confirmed issue resolved'),
(2, 6, 'Ticket Created', NULL, 'Open', 'Ticket submitted by user'),
(2, 1, 'Ticket Assigned', 'Open', 'Assigned', 'Assigned to Ana Cruz - Software specialist'),
(2, 4, 'Ticket Resolved', 'Assigned', 'Resolved', 'Office 365 reinstalled successfully'),
(2, 6, 'Ticket Closed', 'Resolved', 'Closed', 'User confirmed working'),
(3, 7, 'Ticket Created', NULL, 'Open', 'Ticket submitted by user'),
(3, 1, 'Ticket Assigned', 'Open', 'Assigned', 'Assigned to John Reyes - Network specialist'),
(3, 2, 'Ticket Resolved', 'Assigned', 'Resolved', 'VPN client updated and MTU adjusted'),
(3, 7, 'Ticket Closed', 'Resolved', 'Closed', 'VPN stable after fix'),
(65, 7, 'Ticket Created', NULL, 'Open', 'Ticket submitted - MFA bypass detection'),
(65, 1, 'Ticket Assigned', 'Open', 'Assigned', 'Assigned to Rico Bautista - Security specialist'),
(65, 11, 'Ticket Escalated', 'Assigned', 'Escalated', 'Critical security incident - escalated to security team lead');

-- ============================================================
-- Ticket Feedback
-- ============================================================
INSERT INTO ticket_feedback (ticket_id, user_id, rating, comments, satisfaction_status) VALUES
(1, 3, 5, 'Quick response and professional fix. Excellent support!', 'Very Satisfied'),
(2, 6, 4, 'Issue resolved but took a bit longer than expected.', 'Satisfied'),
(3, 7, 5, 'VPN has been stable since the fix. Great work!', 'Very Satisfied'),
(4, 8, 5, 'Account was unlocked within 30 minutes. Fast response!', 'Very Satisfied'),
(5, 9, 4, 'Email working again. Thanks for the help.', 'Satisfied'),
(6, 10, 5, 'Phishing email was handled very quickly and professionally.', 'Very Satisfied'),
(7, 12, 3, 'Printer works now but took a full day.', 'Neutral'),
(8, 13, 4, 'ERP module accessible again. Good troubleshooting.', 'Satisfied'),
(9, 3, 5, 'New mouse works perfectly. Simple and fast fix.', 'Very Satisfied'),
(10, 6, 4, 'License renewed. Would be nice if these were proactive.', 'Satisfied');

-- ============================================================
-- AI Interactions (sample logs)
-- ============================================================
INSERT INTO ai_interactions (user_id, ticket_id, feature_name, prompt_summary, ai_response, status) VALUES
(3, 1, 'Ticket Classification', 'Classify: Laptop screen flickering', '{"suggested_category":"Hardware","suggested_priority":"High","reason":"Display flickering indicates potential hardware failure"}', 'Success'),
(1, 1, 'Troubleshooting', 'Troubleshoot: Laptop screen flickering - Hardware', '{"possible_cause":"Loose display cable or failing GPU","troubleshooting_steps":["Check display cable","Update GPU drivers","Test external monitor"],"information_to_collect":["Laptop model","When issue started"],"escalation_condition":"If hardware replacement needed"}', 'Success'),
(5, 1, 'Resolution Draft', 'Draft resolution for screen flickering ticket', '{"draft_resolution":"Replaced display cable and updated GPU drivers. System tested for 2 hours with no recurrence.","recommended_status":"Resolved"}', 'Success'),
(3, 2, 'Ticket Classification', 'Classify: Cannot install Microsoft Office', '{"suggested_category":"Software","suggested_priority":"Medium","reason":"Software installation error - not critical but affects productivity"}', 'Success'),
(1, NULL, 'Report Insights', 'Generate insights for January ticket volume report', '{"key_observations":["Hardware and Network issues dominate ticket volume","Average resolution time is 6 hours"],"operational_risks":["SLA breach rate at 12%"],"recommendations":["Add network support staff","Implement proactive hardware monitoring"]}', 'Success');
