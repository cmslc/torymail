-- ============================================================
-- Migration 1.4.0: Shared Domains Feature
-- ============================================================

-- Add is_shared flag to domains table
-- Shared domains are available to all users for creating mailboxes
ALTER TABLE `domains` ADD COLUMN `is_shared` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 AFTER `status`;
ALTER TABLE `domains` ADD INDEX `idx_domains_is_shared` (`is_shared`);

-- Allow shared domains to have NULL user_id (system-owned)
ALTER TABLE `domains` MODIFY COLUMN `user_id` INT UNSIGNED DEFAULT NULL;
