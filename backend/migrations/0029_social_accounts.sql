-- Existing identities and memberships are preserved; new invitations require acceptance.
CREATE TABLE kdd_social_wallets (
 token_hash CHAR(64) PRIMARY KEY, expires_at DATETIME NOT NULL
) ENGINE=InnoDB;
CREATE TABLE kdd_social_wallet_accounts (
 wallet_hash CHAR(64) NOT NULL, profile_id INT NOT NULL, session_hash CHAR(64) NOT NULL,
 PRIMARY KEY(wallet_hash,profile_id)
) ENGINE=InnoDB;
CREATE TABLE kdd_social_account_grants (
 owner_id INT NOT NULL, member_id INT NOT NULL, rights_json TEXT NOT NULL,
 status VARCHAR(16) NOT NULL DEFAULT 'pending', created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY(owner_id,member_id)
) ENGINE=InnoDB;
ALTER TABLE kdd_social_companies ADD COLUMN owner_id INT NULL, ADD COLUMN cad_authority_id INT NULL;
UPDATE kdd_social_companies SET owner_id=created_by;
ALTER TABLE kdd_social_members ADD COLUMN rights_json TEXT NULL, ADD COLUMN status VARCHAR(16) NOT NULL DEFAULT 'active';
CREATE TABLE kdd_social_cad_links (
 id INT AUTO_INCREMENT PRIMARY KEY, cad_user_id INT NOT NULL, profile_id INT NOT NULL,
 is_default TINYINT NOT NULL DEFAULT 0, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 UNIQUE(cad_user_id,profile_id)
) ENGINE=InnoDB;
CREATE TABLE kdd_social_bridge_codes (
 token_hash CHAR(64) PRIMARY KEY, cad_user_id INT NOT NULL, cad_session_hash VARCHAR(255) NOT NULL,
 link_id INT NULL, purpose VARCHAR(16) NOT NULL, request_state VARCHAR(100) NOT NULL,
 target_origin VARCHAR(255) NOT NULL, expires_at DATETIME NOT NULL
) ENGINE=InnoDB;
CREATE TABLE kdd_social_session_links (
 session_hash CHAR(64) PRIMARY KEY, link_id INT NOT NULL, cad_session_hash VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE kdd_social_owner_transfers (
 company_id INT PRIMARY KEY, from_id INT NOT NULL, to_id INT NOT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
ALTER TABLE kdd_social_media ADD COLUMN uploaded_by INT NULL;
UPDATE kdd_social_media SET uploaded_by=owner_id;
CREATE TABLE kdd_social_cad_audit (
 id BIGINT AUTO_INCREMENT PRIMARY KEY, cad_user_id INT NOT NULL,
 action VARCHAR(40) NOT NULL, details TEXT NOT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
