-- ============================================================
-- IT Helpdesk Ticketing System - Database Setup
-- ============================================================

CREATE DATABASE IF NOT EXISTS it_helpdesk CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE it_helpdesk;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id VARCHAR(20) NOT NULL UNIQUE,
    full_name VARCHAR(100) NOT NULL,
    department VARCHAR(100) DEFAULT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    contact_number VARCHAR(20) DEFAULT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('Admin','Support Staff','End User') NOT NULL DEFAULT 'End User',
    status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
    created_datetime DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_datetime DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_users_role (role),
    INDEX idx_users_status (status),
    INDEX idx_users_email (email)
) ENGINE=InnoDB;

-- Support Staff table
CREATE TABLE IF NOT EXISTS support_staff (
    staff_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    staff_number VARCHAR(20) NOT NULL UNIQUE,
    full_name VARCHAR(100) NOT NULL,
    specialization VARCHAR(100) DEFAULT NULL,
    shift_schedule VARCHAR(100) DEFAULT NULL,
    status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
    created_datetime DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_datetime DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_staff_status (status),
    INDEX idx_staff_specialization (specialization)
) ENGINE=InnoDB;

-- Categories table
CREATE TABLE IF NOT EXISTS categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT DEFAULT NULL,
    sla_hours INT NOT NULL DEFAULT 24,
    created_datetime DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_datetime DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tickets table
CREATE TABLE IF NOT EXISTS tickets (
    ticket_id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_number VARCHAR(20) NOT NULL UNIQUE,
    user_id INT NOT NULL,
    category_id INT DEFAULT NULL,
    assigned_staff_id INT DEFAULT NULL,
    issue_title VARCHAR(255) NOT NULL,
    issue_description TEXT NOT NULL,
    priority_level ENUM('Low','Medium','High','Critical') NOT NULL DEFAULT 'Medium',
    status ENUM('Open','Assigned','In Progress','Escalated','Resolved','Closed') NOT NULL DEFAULT 'Open',
    ai_suggested_category VARCHAR(100) DEFAULT NULL,
    ai_suggested_priority VARCHAR(20) DEFAULT NULL,
    ai_recommendation_reason TEXT DEFAULT NULL,
    sla_due_datetime DATETIME DEFAULT NULL,
    resolved_datetime DATETIME DEFAULT NULL,
    closed_datetime DATETIME DEFAULT NULL,
    created_datetime DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_datetime DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_staff_id) REFERENCES support_staff(staff_id) ON DELETE SET NULL,
    INDEX idx_tickets_status (status),
    INDEX idx_tickets_priority (priority_level),
    INDEX idx_tickets_user (user_id),
    INDEX idx_tickets_staff (assigned_staff_id),
    INDEX idx_tickets_category (category_id),
    INDEX idx_tickets_sla (sla_due_datetime),
    INDEX idx_tickets_created (created_datetime)
) ENGINE=InnoDB;

-- Resolutions table
CREATE TABLE IF NOT EXISTS resolutions (
    resolution_id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    staff_id INT NOT NULL,
    resolution_details TEXT NOT NULL,
    ai_drafted_resolution TEXT DEFAULT NULL,
    resolution_status VARCHAR(50) DEFAULT 'Final',
    resolution_time_minutes INT DEFAULT NULL,
    created_datetime DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_datetime DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tickets(ticket_id) ON DELETE CASCADE,
    FOREIGN KEY (staff_id) REFERENCES support_staff(staff_id) ON DELETE CASCADE,
    INDEX idx_resolutions_ticket (ticket_id)
) ENGINE=InnoDB;

-- Ticket Logs table
CREATE TABLE IF NOT EXISTS ticket_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    user_id INT DEFAULT NULL,
    action VARCHAR(100) NOT NULL,
    old_status VARCHAR(50) DEFAULT NULL,
    new_status VARCHAR(50) DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    created_datetime DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tickets(ticket_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_logs_ticket (ticket_id),
    INDEX idx_logs_created (created_datetime)
) ENGINE=InnoDB;

-- Ticket Feedback table
CREATE TABLE IF NOT EXISTS ticket_feedback (
    feedback_id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    user_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comments TEXT DEFAULT NULL,
    satisfaction_status VARCHAR(50) DEFAULT NULL,
    created_datetime DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tickets(ticket_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_feedback_ticket (ticket_id)
) ENGINE=InnoDB;

-- AI Interactions table
CREATE TABLE IF NOT EXISTS ai_interactions (
    ai_interaction_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    ticket_id INT DEFAULT NULL,
    feature_name VARCHAR(100) NOT NULL,
    prompt_summary TEXT DEFAULT NULL,
    ai_response TEXT DEFAULT NULL,
    status ENUM('Success','Failed') NOT NULL DEFAULT 'Success',
    error_message TEXT DEFAULT NULL,
    created_datetime DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (ticket_id) REFERENCES tickets(ticket_id) ON DELETE SET NULL,
    INDEX idx_ai_feature (feature_name),
    INDEX idx_ai_status (status),
    INDEX idx_ai_created (created_datetime)
) ENGINE=InnoDB;
