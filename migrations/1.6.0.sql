-- ============================================================
-- Migration 1.6.0 — Add bcrypt password column for Dovecot auth
-- ============================================================

ALTER TABLE `mailboxes` ADD COLUMN `password` VARCHAR(255) DEFAULT NULL AFTER `password_encrypted`;
