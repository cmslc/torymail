-- ============================================================
-- Migration 1.5.0 — Public mailbox creation (shared domains)
-- ============================================================

-- Allow mailboxes without an owner user (public mailboxes)
ALTER TABLE `mailboxes` DROP FOREIGN KEY `fk_mailboxes_user_id`;
ALTER TABLE `mailboxes` MODIFY `user_id` INT UNSIGNED DEFAULT NULL;
ALTER TABLE `mailboxes` ADD CONSTRAINT `fk_mailboxes_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
