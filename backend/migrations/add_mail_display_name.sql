-- Add display_name to mail accounts
-- This will be used for sender name display in emails and global directory

ALTER TABLE kdd_mail_accounts
ADD COLUMN display_name VARCHAR(255) NULL AFTER email;

-- Update existing accounts to use the local part of email as default display name
UPDATE kdd_mail_accounts
SET display_name = SUBSTRING_INDEX(email, '@', 1)
WHERE display_name IS NULL;
