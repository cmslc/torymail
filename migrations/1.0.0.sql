-- ============================================================
-- Torymail v1.0.0 - Database Migration
-- Full-featured email CMS system
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------------
-- 1. users
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `fullname` VARCHAR(255) NOT NULL DEFAULT '',
    `email` VARCHAR(255) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('user','admin') NOT NULL DEFAULT 'user',
    `status` ENUM('active','banned','inactive') NOT NULL DEFAULT 'active',
    `token` VARCHAR(255) DEFAULT NULL,
    `avatar` VARCHAR(500) DEFAULT NULL,
    `timezone` VARCHAR(100) NOT NULL DEFAULT 'UTC',
    `signature` TEXT DEFAULT NULL,
    `max_domains` INT UNSIGNED NOT NULL DEFAULT 5,
    `max_mailboxes_per_domain` INT UNSIGNED NOT NULL DEFAULT 50,
    `storage_quota` BIGINT UNSIGNED NOT NULL DEFAULT 1073741824,
    `storage_used` BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `last_activity` DATETIME DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_users_email` (`email`),
    KEY `idx_users_role` (`role`),
    KEY `idx_users_status` (`status`),
    KEY `idx_users_token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 2. domains
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `domains` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `domain_name` VARCHAR(255) NOT NULL,
    `status` ENUM('pending','active','suspended') NOT NULL DEFAULT 'pending',
    `verification_token` VARCHAR(255) DEFAULT NULL,
    `verification_method` ENUM('dns_txt','cname') NOT NULL DEFAULT 'dns_txt',
    `verified_at` DATETIME DEFAULT NULL,
    `mx_verified` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
    `spf_verified` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
    `dkim_verified` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
    `dmarc_verified` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
    `dkim_private_key` TEXT DEFAULT NULL,
    `dkim_public_key` TEXT DEFAULT NULL,
    `dkim_selector` VARCHAR(100) DEFAULT 'default',
    `catch_all_mailbox_id` INT UNSIGNED DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_domains_domain_name` (`domain_name`),
    KEY `idx_domains_user_id` (`user_id`),
    KEY `idx_domains_status` (`status`),
    KEY `idx_domains_catch_all_mailbox_id` (`catch_all_mailbox_id`),
    CONSTRAINT `fk_domains_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 3. mailboxes
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `mailboxes` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `domain_id` INT UNSIGNED NOT NULL,
    `email_address` VARCHAR(255) NOT NULL,
    `display_name` VARCHAR(255) NOT NULL DEFAULT '',
    `password_encrypted` VARCHAR(255) NOT NULL,
    `quota` BIGINT UNSIGNED NOT NULL DEFAULT 1073741824,
    `used_space` BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `status` ENUM('active','disabled') NOT NULL DEFAULT 'active',
    `is_catch_all` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
    `auto_reply_enabled` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
    `auto_reply_subject` VARCHAR(500) DEFAULT NULL,
    `auto_reply_message` TEXT DEFAULT NULL,
    `forwarding_enabled` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
    `forwarding_address` VARCHAR(255) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_mailboxes_email_address` (`email_address`),
    KEY `idx_mailboxes_user_id` (`user_id`),
    KEY `idx_mailboxes_domain_id` (`domain_id`),
    KEY `idx_mailboxes_status` (`status`),
    CONSTRAINT `fk_mailboxes_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_mailboxes_domain_id` FOREIGN KEY (`domain_id`) REFERENCES `domains` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add deferred FK for domains.catch_all_mailbox_id now that mailboxes exists
ALTER TABLE `domains`
    ADD CONSTRAINT `fk_domains_catch_all_mailbox_id` FOREIGN KEY (`catch_all_mailbox_id`) REFERENCES `mailboxes` (`id`) ON DELETE SET NULL;

-- -----------------------------------------------------------
-- 4. emails
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `emails` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `mailbox_id` INT UNSIGNED NOT NULL,
    `message_id` VARCHAR(500) DEFAULT NULL,
    `folder` VARCHAR(100) NOT NULL DEFAULT 'inbox',
    `from_address` VARCHAR(255) NOT NULL DEFAULT '',
    `from_name` VARCHAR(255) NOT NULL DEFAULT '',
    `to_addresses` JSON DEFAULT NULL,
    `cc_addresses` JSON DEFAULT NULL,
    `bcc_addresses` JSON DEFAULT NULL,
    `reply_to` VARCHAR(255) DEFAULT NULL,
    `subject` VARCHAR(1000) NOT NULL DEFAULT '',
    `body_text` LONGTEXT DEFAULT NULL,
    `body_html` LONGTEXT DEFAULT NULL,
    `is_read` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
    `is_starred` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
    `is_flagged` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
    `priority` ENUM('low','normal','high') NOT NULL DEFAULT 'normal',
    `has_attachments` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
    `size` BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `headers` JSON DEFAULT NULL,
    `in_reply_to` VARCHAR(500) DEFAULT NULL,
    `references_header` TEXT DEFAULT NULL,
    `thread_id` VARCHAR(255) DEFAULT NULL,
    `spam_score` DECIMAL(5,2) DEFAULT NULL,
    `sent_at` DATETIME DEFAULT NULL,
    `received_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_emails_mailbox_id` (`mailbox_id`),
    KEY `idx_emails_mailbox_folder` (`mailbox_id`, `folder`),
    KEY `idx_emails_message_id` (`message_id`(191)),
    KEY `idx_emails_thread_id` (`thread_id`),
    KEY `idx_emails_from_address` (`from_address`),
    KEY `idx_emails_is_read` (`is_read`),
    KEY `idx_emails_is_starred` (`is_starred`),
    KEY `idx_emails_received_at` (`received_at`),
    KEY `idx_emails_folder` (`folder`),
    CONSTRAINT `fk_emails_mailbox_id` FOREIGN KEY (`mailbox_id`) REFERENCES `mailboxes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 5. email_attachments
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `email_attachments` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `email_id` BIGINT UNSIGNED NOT NULL,
    `filename` VARCHAR(500) NOT NULL,
    `original_filename` VARCHAR(500) NOT NULL,
    `mime_type` VARCHAR(255) NOT NULL DEFAULT 'application/octet-stream',
    `size` BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `storage_path` VARCHAR(1000) NOT NULL,
    `content_id` VARCHAR(255) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_email_attachments_email_id` (`email_id`),
    KEY `idx_email_attachments_content_id` (`content_id`),
    CONSTRAINT `fk_email_attachments_email_id` FOREIGN KEY (`email_id`) REFERENCES `emails` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 6. contacts
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `contacts` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `email` VARCHAR(255) NOT NULL DEFAULT '',
    `name` VARCHAR(255) NOT NULL DEFAULT '',
    `company` VARCHAR(255) DEFAULT NULL,
    `phone` VARCHAR(50) DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `is_favorite` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
    `group_name` VARCHAR(100) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_contacts_user_id` (`user_id`),
    KEY `idx_contacts_email` (`email`),
    KEY `idx_contacts_user_group` (`user_id`, `group_name`),
    CONSTRAINT `fk_contacts_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 7. email_labels
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `email_labels` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `color` VARCHAR(20) NOT NULL DEFAULT '#6c757d',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_email_labels_user_id` (`user_id`),
    UNIQUE KEY `uk_email_labels_user_name` (`user_id`, `name`),
    CONSTRAINT `fk_email_labels_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 8. email_label_map
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `email_label_map` (
    `email_id` BIGINT UNSIGNED NOT NULL,
    `label_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`email_id`, `label_id`),
    KEY `idx_email_label_map_label_id` (`label_id`),
    CONSTRAINT `fk_email_label_map_email_id` FOREIGN KEY (`email_id`) REFERENCES `emails` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_email_label_map_label_id` FOREIGN KEY (`label_id`) REFERENCES `email_labels` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 9. email_filters
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `email_filters` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `conditions` JSON NOT NULL,
    `actions` JSON NOT NULL,
    `is_active` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
    `priority_order` INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_email_filters_user_id` (`user_id`),
    KEY `idx_email_filters_active_priority` (`is_active`, `priority_order`),
    CONSTRAINT `fk_email_filters_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 10. email_templates
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `email_templates` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `subject` VARCHAR(1000) NOT NULL DEFAULT '',
    `body_html` LONGTEXT DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_email_templates_user_id` (`user_id`),
    CONSTRAINT `fk_email_templates_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 11. email_queue
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `email_queue` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `mailbox_id` INT UNSIGNED NOT NULL,
    `from_address` VARCHAR(255) NOT NULL,
    `to_addresses` JSON NOT NULL,
    `cc_addresses` JSON DEFAULT NULL,
    `bcc_addresses` JSON DEFAULT NULL,
    `subject` VARCHAR(1000) NOT NULL DEFAULT '',
    `body_html` LONGTEXT DEFAULT NULL,
    `body_text` LONGTEXT DEFAULT NULL,
    `attachments` JSON DEFAULT NULL,
    `priority` ENUM('low','normal','high') NOT NULL DEFAULT 'normal',
    `status` ENUM('pending','sending','sent','failed') NOT NULL DEFAULT 'pending',
    `attempts` INT UNSIGNED NOT NULL DEFAULT 0,
    `max_attempts` INT UNSIGNED NOT NULL DEFAULT 3,
    `error_message` TEXT DEFAULT NULL,
    `scheduled_at` DATETIME DEFAULT NULL,
    `sent_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_email_queue_mailbox_id` (`mailbox_id`),
    KEY `idx_email_queue_status` (`status`),
    KEY `idx_email_queue_scheduled` (`status`, `scheduled_at`),
    KEY `idx_email_queue_priority` (`priority`),
    CONSTRAINT `fk_email_queue_mailbox_id` FOREIGN KEY (`mailbox_id`) REFERENCES `mailboxes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 12. activity_logs
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `activity_logs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED DEFAULT NULL,
    `action` VARCHAR(255) NOT NULL,
    `details` TEXT DEFAULT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `user_agent` VARCHAR(500) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_activity_logs_user_id` (`user_id`),
    KEY `idx_activity_logs_action` (`action`),
    KEY `idx_activity_logs_created_at` (`created_at`),
    CONSTRAINT `fk_activity_logs_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 13. settings
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `settings` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `setting_key` VARCHAR(255) NOT NULL,
    `setting_value` TEXT DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_settings_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 14. password_resets
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `password_resets` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `token` VARCHAR(255) NOT NULL,
    `expires_at` DATETIME NOT NULL,
    `used` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_password_resets_token` (`token`),
    KEY `idx_password_resets_user_id` (`user_id`),
    KEY `idx_password_resets_expires` (`expires_at`),
    CONSTRAINT `fk_password_resets_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 15. dns_records
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `dns_records` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `domain_id` INT UNSIGNED NOT NULL,
    `record_type` ENUM('MX','TXT','CNAME','A') NOT NULL,
    `hostname` VARCHAR(255) NOT NULL,
    `value` TEXT NOT NULL,
    `priority` INT UNSIGNED DEFAULT NULL,
    `is_verified` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
    `last_checked` DATETIME DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_dns_records_domain_id` (`domain_id`),
    KEY `idx_dns_records_type` (`record_type`),
    KEY `idx_dns_records_domain_type` (`domain_id`, `record_type`),
    CONSTRAINT `fk_dns_records_domain_id` FOREIGN KEY (`domain_id`) REFERENCES `domains` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- Default settings
-- ============================================================
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
    ('site_name', 'Torymail'),
    ('site_url', ''),
    ('smtp_host', ''),
    ('smtp_port', '587'),
    ('smtp_username', ''),
    ('smtp_password', ''),
    ('smtp_encryption', 'tls'),
    ('max_domains_per_user', '5'),
    ('max_mailboxes_per_domain', '50'),
    ('default_quota', '1073741824'),
    ('max_attachment_size', '26214400'),
    ('allow_registration', '1'),
    ('mail_server_hostname', '');

-- ============================================================
-- Default admin user
-- Password: admin123 (bcrypt hash)
-- ============================================================
INSERT INTO `users` (`fullname`, `email`, `password`, `role`, `status`, `timezone`, `max_domains`, `max_mailboxes_per_domain`, `storage_quota`)
VALUES (
    'Administrator',
    'admin@torymail.local',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'admin',
    'active',
    'UTC',
    999,
    999,
    10737418240
);
