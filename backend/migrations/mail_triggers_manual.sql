-- =====================================================
-- Mail System Triggers
-- Run this after 0015_mail_system_complete.sql
-- =====================================================

-- Drop existing triggers if they exist
DROP TRIGGER IF EXISTS `trg_mail_attachments_after_insert`;
DROP TRIGGER IF EXISTS `trg_mail_attachments_after_delete`;
DROP TRIGGER IF EXISTS `trg_mail_accounts_after_insert`;

-- Trigger 1: Update storage_used when attachment is added
DELIMITER $$
CREATE TRIGGER `trg_mail_attachments_after_insert`
AFTER INSERT ON `kdd_mail_attachments`
FOR EACH ROW
BEGIN
  DECLARE sender_mail_account INT;

  -- Get sender's mail account
  SELECT ma.id INTO sender_mail_account
  FROM kdd_mails m
  JOIN kdd_mail_accounts ma ON m.from_address = ma.email
  WHERE m.id = NEW.mail_id
  LIMIT 1;

  -- Update storage_used
  IF sender_mail_account IS NOT NULL THEN
    UPDATE kdd_mail_accounts
    SET storage_used = storage_used + NEW.file_size
    WHERE id = sender_mail_account;
  END IF;
END$$

-- Trigger 2: Update storage_used when attachment is deleted
CREATE TRIGGER `trg_mail_attachments_after_delete`
AFTER DELETE ON `kdd_mail_attachments`
FOR EACH ROW
BEGIN
  DECLARE sender_mail_account INT;

  -- Get sender's mail account
  SELECT ma.id INTO sender_mail_account
  FROM kdd_mails m
  JOIN kdd_mail_accounts ma ON m.from_address = ma.email
  WHERE m.id = OLD.mail_id
  LIMIT 1;

  -- Update storage_used
  IF sender_mail_account IS NOT NULL THEN
    UPDATE kdd_mail_accounts
    SET storage_used = storage_used - OLD.file_size
    WHERE id = sender_mail_account AND storage_used >= OLD.file_size;
  END IF;
END$$

-- Trigger 3: Update total_accounts in domains table
CREATE TRIGGER `trg_mail_accounts_after_insert`
AFTER INSERT ON `kdd_mail_accounts`
FOR EACH ROW
BEGIN
  DECLARE account_domain VARCHAR(191);
  SET account_domain = SUBSTRING_INDEX(NEW.email, '@', -1);

  UPDATE kdd_mail_domains
  SET total_accounts = total_accounts + 1
  WHERE domain = account_domain;
END$$

DELIMITER ;
