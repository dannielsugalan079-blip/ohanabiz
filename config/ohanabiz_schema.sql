-- ============================================================
--  OHANABIZ — Complete Database Schema
--  File   : config/ohanabiz_schema.sql
--  Author : OHANABIZ Dev Team
--  Date   : 2026
--
--  HOW TO IMPORT:
--  1. Buksan ang phpMyAdmin → http://localhost/phpmyadmin
--  2. I-click ang "New" → pangalanan ang database na "ohanabiz"
--  3. I-click ang database → pumunta sa "Import" tab
--  4. I-upload ang file na ito → I-click ang "Go"
-- ============================================================

CREATE DATABASE IF NOT EXISTS `ohanabiz`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `ohanabiz`;

-- ============================================================
--  TABLE: users
--  Central auth table para sa lahat ng user types
--  (admin, employee/supervisor, client)
-- ============================================================
CREATE TABLE IF NOT EXISTS `users` (
    `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `full_name`     VARCHAR(150)    NOT NULL,
    `email`         VARCHAR(191)    NOT NULL,
    `password_hash` VARCHAR(255)    NOT NULL,           -- bcrypt hash
    `role`          ENUM('admin','supervisor','employee','client')
                                    NOT NULL DEFAULT 'client',
    `phone`         VARCHAR(20)     NULL,
    `position`      VARCHAR(100)    NULL,               -- para sa employees
    `department`    VARCHAR(100)    NULL,               -- para sa employees
    `status`        ENUM('active','inactive','suspended')
                                    NOT NULL DEFAULT 'active',
    `profile_photo` VARCHAR(255)    NULL,
    `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP
                                        ON UPDATE CURRENT_TIMESTAMP,
    `last_login`    DATETIME        NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_email` (`email`),
    INDEX `idx_role` (`role`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  TABLE: clients
--  Extended profile para sa mga client users
-- ============================================================
CREATE TABLE IF NOT EXISTS `clients` (
    `id`                    INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `user_id`               INT UNSIGNED    NOT NULL,
    `company_name`          VARCHAR(200)    NULL,
    `business_type`         VARCHAR(100)    NULL,
    `tin_number`            VARCHAR(30)     NULL,       -- Tax Identification Number
    `sec_dti_number`        VARCHAR(50)     NULL,
    `authorized_rep_name`   VARCHAR(150)    NULL,
    `billing_address`       TEXT            NULL,
    `business_address`      TEXT            NULL,
    `account_number`        VARCHAR(20)     GENERATED ALWAYS AS (
                                CONCAT('CLT-', LPAD(user_id, 6, '0'))
                            ) STORED,
    `created_at`            DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_user_id` (`user_id`),
    CONSTRAINT `fk_clients_user`
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  TABLE: services
--  Master list ng mga serbisyo ng OHANA
-- ============================================================
CREATE TABLE IF NOT EXISTS `services` (
    `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `category`      ENUM(
                        'primary_documentation',
                        'business_corporate',
                        'legal_notarial',
                        'printing_online'
                    )               NOT NULL,
    `name`          VARCHAR(150)    NOT NULL,
    `description`   TEXT            NULL,
    `base_price`    DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
    `estimated_days` TINYINT UNSIGNED NOT NULL DEFAULT 3,
    `is_active`     TINYINT(1)      NOT NULL DEFAULT 1,
    `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_category` (`category`),
    INDEX `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  TABLE: service_requests
--  Client service requests / orders
-- ============================================================
CREATE TABLE IF NOT EXISTS `service_requests` (
    `id`                INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `reference_no`      VARCHAR(20)     NOT NULL,       -- e.g. REQ-2026-00001
    `client_id`         INT UNSIGNED    NOT NULL,       -- FK → users.id (client)
    `service_id`        INT UNSIGNED    NULL,
    `service_name`      VARCHAR(150)    NOT NULL,       -- snapshot ng service name
    `details`           TEXT            NULL,           -- Additional notes / specs
    `status`            ENUM(
                            'pending',
                            'reviewing',
                            'in_progress',
                            'for_pickup',
                            'completed',
                            'cancelled'
                        )               NOT NULL DEFAULT 'pending',
    `priority`          ENUM('normal','urgent','rush')
                                        NOT NULL DEFAULT 'normal',
    `assigned_to`       INT UNSIGNED    NULL,           -- FK → users.id (employee)
    `total_amount`      DECIMAL(10,2)   NULL,
    `payment_status`    ENUM('unpaid','partial','paid')
                                        NOT NULL DEFAULT 'unpaid',
    `notes`             TEXT            NULL,           -- Internal staff notes
    `requested_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP
                                            ON UPDATE CURRENT_TIMESTAMP,
    `completed_at`      DATETIME        NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_reference_no` (`reference_no`),
    INDEX `idx_client_id` (`client_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_assigned_to` (`assigned_to`),
    CONSTRAINT `fk_requests_client`
        FOREIGN KEY (`client_id`) REFERENCES `users`(`id`)
        ON UPDATE CASCADE,
    CONSTRAINT `fk_requests_service`
        FOREIGN KEY (`service_id`) REFERENCES `services`(`id`)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_requests_assignee`
        FOREIGN KEY (`assigned_to`) REFERENCES `users`(`id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  TABLE: tasks
--  Internal tasks assigned to employees by supervisors/admin
-- ============================================================
CREATE TABLE IF NOT EXISTS `tasks` (
    `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `request_id`    INT UNSIGNED    NULL,               -- Linked service request (optional)
    `title`         VARCHAR(200)    NOT NULL,
    `description`   TEXT            NULL,
    `assigned_to`   INT UNSIGNED    NOT NULL,           -- FK → users.id (employee)
    `assigned_by`   INT UNSIGNED    NOT NULL,           -- FK → users.id (supervisor/admin)
    `priority`      ENUM('low','normal','high','critical')
                                    NOT NULL DEFAULT 'normal',
    `status`        ENUM('pending','in_progress','for_review','completed','cancelled')
                                    NOT NULL DEFAULT 'pending',
    `due_date`      DATE            NULL,
    `completed_at`  DATETIME        NULL,
    `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP
                                        ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_assigned_to` (`assigned_to`),
    INDEX `idx_assigned_by` (`assigned_by`),
    INDEX `idx_status` (`status`),
    INDEX `idx_due_date` (`due_date`),
    CONSTRAINT `fk_tasks_request`
        FOREIGN KEY (`request_id`) REFERENCES `service_requests`(`id`)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_tasks_assignee`
        FOREIGN KEY (`assigned_to`) REFERENCES `users`(`id`)
        ON UPDATE CASCADE,
    CONSTRAINT `fk_tasks_assigner`
        FOREIGN KEY (`assigned_by`) REFERENCES `users`(`id`)
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  TABLE: attendance
--  Employee daily attendance tracking
-- ============================================================
CREATE TABLE IF NOT EXISTS `attendance` (
    `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `user_id`       INT UNSIGNED    NOT NULL,           -- FK → users.id (employee)
    `date`          DATE            NOT NULL,
    `time_in`       TIME            NULL,
    `time_out`      TIME            NULL,
    `status`        ENUM('present','absent','late','half_day','on_leave','wfh')
                                    NOT NULL DEFAULT 'present',
    `total_hours`   DECIMAL(5,2)    GENERATED ALWAYS AS (
                        CASE
                            WHEN `time_in` IS NOT NULL AND `time_out` IS NOT NULL
                            THEN ROUND(TIME_TO_SEC(TIMEDIFF(`time_out`, `time_in`)) / 3600, 2)
                            ELSE NULL
                        END
                    ) STORED,
    `remarks`       VARCHAR(255)    NULL,
    `recorded_by`   INT UNSIGNED    NULL,               -- Kung may nag-record (optional)
    `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_user_date` (`user_id`, `date`),     -- Isang record per day per user
    INDEX `idx_date` (`date`),
    INDEX `idx_status` (`status`),
    CONSTRAINT `fk_attendance_user`
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  TABLE: leave_requests
--  Employee leave request tracking
-- ============================================================
CREATE TABLE IF NOT EXISTS `leave_requests` (
    `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `user_id`       INT UNSIGNED    NOT NULL,
    `leave_type`    ENUM('vacation','sick','emergency','maternity','paternity','other')
                                    NOT NULL DEFAULT 'vacation',
    `date_from`     DATE            NOT NULL,
    `date_to`       DATE            NOT NULL,
    `total_days`    TINYINT UNSIGNED GENERATED ALWAYS AS (
                        DATEDIFF(`date_to`, `date_from`) + 1
                    ) STORED,
    `reason`        TEXT            NOT NULL,
    `status`        ENUM('pending','endorsed','approved','rejected')
                                    NOT NULL DEFAULT 'pending',
    `endorsed_by`   INT UNSIGNED    NULL,               -- Supervisor who endorsed
    `approved_by`   INT UNSIGNED    NULL,               -- Admin who approved
    `remarks`       TEXT            NULL,
    `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP
                                        ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_status` (`status`),
    CONSTRAINT `fk_leave_user`
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_leave_endorsed`
        FOREIGN KEY (`endorsed_by`) REFERENCES `users`(`id`)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_leave_approved`
        FOREIGN KEY (`approved_by`) REFERENCES `users`(`id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  TABLE: notifications
--  In-app notifications para sa lahat ng users
-- ============================================================
CREATE TABLE IF NOT EXISTS `notifications` (
    `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `user_id`       INT UNSIGNED    NOT NULL,
    `title`         VARCHAR(200)    NOT NULL,
    `message`       TEXT            NOT NULL,
    `type`          ENUM('info','success','warning','error','task','request')
                                    NOT NULL DEFAULT 'info',
    `is_read`       TINYINT(1)      NOT NULL DEFAULT 0,
    `link`          VARCHAR(255)    NULL,               -- Optional redirect URL
    `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_is_read` (`is_read`),
    CONSTRAINT `fk_notif_user`
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  SEED DATA: Services Master List
-- ============================================================
INSERT INTO `services` (`category`, `name`, `description`, `base_price`, `estimated_days`) VALUES
-- Primary Documentation
('primary_documentation', 'PSA Birth Certificate',        'Philippine Statistics Authority Birth Certificate request and delivery', 450.00, 5),
('primary_documentation', 'PSA Marriage Certificate',     'PSA Marriage Certificate request and delivery',                          450.00, 5),
('primary_documentation', 'PSA Death Certificate',        'PSA Death Certificate request and delivery',                             450.00, 5),
('primary_documentation', 'NBI Clearance',                'NBI Clearance application assistance',                                   350.00, 3),
('primary_documentation', 'Police Clearance',             'Local Police Clearance application',                                     200.00, 2),
('primary_documentation', 'Passport Appointment',         'DFA Passport appointment scheduling assistance',                          500.00, 1),
-- Business & Corporate
('business_corporate',    'DTI Business Registration',    'Department of Trade and Industry business name registration',            800.00,  7),
('business_corporate',    'SEC Incorporation',            'Securities and Exchange Commission corporation registration',           3500.00, 14),
("business_corporate",    "Mayor's Permit Application",   "Local government unit mayor's permit assistance",                        600.00,  5),
('business_corporate',    'BIR Registration & Filing',    'Bureau of Internal Revenue business registration and tax filing',        700.00,  5),
('business_corporate',    'Annual Business Renewal',      'Complete annual renewal of business licenses and permits',              1200.00,  7),
-- Legal & Notarial
('legal_notarial',        'Affidavit Preparation',        'General affidavit drafting and notarization',                           500.00,  2),
('legal_notarial',        'Special Power of Attorney',    'SPA document drafting and notarization',                                750.00,  3),
('legal_notarial',        'Contract Drafting',            'Business contract or agreement preparation',                           1500.00,  5),
('legal_notarial',        'Deed of Sale',                 'Deed of Sale preparation and notarization',                           2000.00,  5),
-- Printing & Online
('printing_online',       'Document Printing',            'High-quality document printing services (per page)',                      10.00,  1),
('printing_online',       'Scanning & Encoding',          'Document scanning and digital encoding services',                       150.00,  1),
('printing_online',       'Online Form Assistance',       'Online government form fill-out and submission',                        200.00,  1),
('printing_online',       'ID Photo & Layout',            'Professional ID photo and layout creation',                             250.00,  1);

-- ============================================================
--  SEED DATA: Default Accounts
--  Accounts are ready for login testing:
--  Admin:      admin@ohanabiz.com      / Admin@2026!
--  Supervisor: supervisor@ohanabiz.com / Supervisor@2026!
--  Employee:   employee@ohanabiz.com   / Employee@2026!
--  Client:     client@ohanabiz.com     / Client@2026!
-- ============================================================
INSERT INTO `users` (`full_name`, `email`, `password_hash`, `role`, `phone`, `position`, `department`, `status`) VALUES
(
    'OHANA System Administrator',
    'admin@ohanabiz.com',
    '$2y$10$7gLy78PahVl7yH5Z73FW..7N.ZVElwfCutryKiA3apqrEf8L0bMbG', -- Admin@2026!
    'admin',
    '+63 912 000 0000',
    'System Administrator',
    'Executive & IT',
    'active'
),
(
    'Maria Santos',
    'supervisor@ohanabiz.com',
    '$2y$10$fVkWcxBGO5E4EM1/vM3f1e24q0yVQ7xziFHoNUwb71ar.LzAJl1nu', -- Supervisor@2026!
    'supervisor',
    '+63 912 000 0001',
    'Operations Supervisor',
    'Operations',
    'active'
),
(
    'Juan Dela Cruz',
    'employee@ohanabiz.com',
    '$2y$10$hd0zt0pYFKk4UbNvjkui0.C/YO8v7CQnSj3cy8Mssbur4fzCI4VP6', -- Employee@2026!
    'employee',
    '+63 912 000 0002',
    'Documentation Specialist',
    'Operations',
    'active'
),
(
    'Enterprise Client Partner',
    'client@ohanabiz.com',
    '$2y$10$WxYR1CGPUaPbyL5BfGV8b.I6g9ArdJPJawCWcqkUmFW6zF7uZxDTG', -- Client@2026!
    'client',
    '+63 912 000 0003',
    'Business Owner',
    'External',
    'active'
);

-- Seed Client Extended Profile
INSERT INTO `clients` (`user_id`, `company_name`, `business_type`, `tin_number`, `sec_dti_number`, `authorized_rep_name`, `billing_address`, `business_address`) VALUES
(
    4,
    'OHANA Client Enterprise',
    'Commercial Services / Trading',
    '123-456-789-000',
    'DTI-BLC-2026-0089',
    'Enterprise Client Partner',
    'Unit 102 Greenery Commercial Arcade, Malolos, Bulacan',
    'Unit 102 Greenery Commercial Arcade, Malolos, Bulacan'
);

-- Seed Sample Service Request
INSERT INTO `service_requests` (`reference_no`, `client_id`, `service_id`, `service_name`, `details`, `status`, `priority`, `assigned_to`, `total_amount`, `payment_status`) VALUES
(
    'REQ-2026-00001',
    4,
    7,
    'DTI Business Registration',
    'New sole proprietorship renewal and secondary branch permit certification',
    'in_progress',
    'urgent',
    3,
    800.00,
    'paid'
);

-- Seed Sample Task
INSERT INTO `tasks` (`request_id`, `title`, `description`, `assigned_to`, `assigned_by`, `priority`, `status`, `due_date`) VALUES
(
    1,
    'DTI Certificate Submission & Branch Clearance',
    'Process verified application docs at Malolos DTI Regional Center',
    3,
    2,
    'high',
    'in_progress',
    DATE_ADD(CURDATE(), INTERVAL 2 DAY)
);

-- Seed Sample Attendance Record
INSERT INTO `attendance` (`user_id`, `date`, `time_in`, `time_out`, `status`, `remarks`) VALUES
(
    3,
    CURDATE(),
    '08:00:00',
    '17:00:00',
    'present',
    'Regular shift completed'
),
(
    2,
    CURDATE(),
    '07:45:00',
    '17:15:00',
    'present',
    'Duty supervisor on site'
);

-- ============================================================
--  END OF SCHEMA
-- ============================================================

