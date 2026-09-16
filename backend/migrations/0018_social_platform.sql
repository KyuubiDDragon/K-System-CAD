-- Social profiles have private CAD tenants; existing authorities are not reclassified blindly.
ALTER TABLE kdd_authorities MODIFY authority_type ENUM('faction','company','government','private','personal') DEFAULT 'private';
CREATE TABLE IF NOT EXISTS kdd_social_settings (id INT PRIMARY KEY, settings LONGTEXT NOT NULL, revision INT NOT NULL DEFAULT 1);
INSERT IGNORE INTO kdd_social_settings VALUES (1,'{}',1);
CREATE TABLE IF NOT EXISTS kdd_social_profiles (
 id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL UNIQUE, authority_id INT NOT NULL UNIQUE,
 handle VARCHAR(40) NOT NULL UNIQUE, display_name VARCHAR(100) NOT NULL, bio TEXT, signature TEXT,
 avatar_id INT NULL, cover_id INT NULL, privacy VARCHAR(20) NOT NULL DEFAULT 'public', preferences LONGTEXT NOT NULL,
 status VARCHAR(20) NOT NULL DEFAULT 'active', suspended_until DATETIME NULL,
 role VARCHAR(20) NOT NULL DEFAULT 'member', recovery_hash VARCHAR(255) NOT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, last_seen DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS kdd_social_sessions (
 token_hash CHAR(64) PRIMARY KEY, profile_id INT NOT NULL, expires_at DATETIME NOT NULL,
 INDEX(profile_id), INDEX(expires_at)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS kdd_social_limits (
 bucket CHAR(64) PRIMARY KEY, hits INT NOT NULL, expires_at DATETIME NOT NULL
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS kdd_social_friends (
 sender INT NOT NULL, recipient INT NOT NULL, status VARCHAR(20) NOT NULL DEFAULT 'pending',
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY(sender,recipient), INDEX(recipient,status)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS kdd_social_blocks (blocker INT NOT NULL, blocked INT NOT NULL, PRIMARY KEY(blocker,blocked)) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS kdd_social_companies (
 id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL, description TEXT, created_by INT NOT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS kdd_social_members (company_id INT NOT NULL, profile_id INT NOT NULL, PRIMARY KEY(company_id,profile_id)) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS kdd_social_posts (
 id INT AUTO_INCREMENT PRIMARY KEY, author_id INT NOT NULL, company_id INT NULL, wall_id INT NULL,
 module VARCHAR(20) NOT NULL, title VARCHAR(160) NOT NULL DEFAULT '', body TEXT NOT NULL,
 visibility VARCHAR(20) NOT NULL DEFAULT 'public', state VARCHAR(20) NOT NULL DEFAULT 'published',
 review_reason TEXT, price DECIMAL(12,2) NULL, category VARCHAR(60) NULL, sale_state VARCHAR(20) NOT NULL DEFAULT 'available',
 shared_id INT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 INDEX(module,state,id), INDEX(author_id), INDEX(wall_id), INDEX(shared_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS kdd_social_media (
 id INT AUTO_INCREMENT PRIMARY KEY, owner_id INT NOT NULL, module VARCHAR(20) NOT NULL, purpose VARCHAR(20) NOT NULL DEFAULT 'post',
 storage VARCHAR(20) NOT NULL DEFAULT 'local', storage_key VARCHAR(200) NOT NULL, mime VARCHAR(100) NOT NULL,
 filename VARCHAR(180) NOT NULL, bytes BIGINT NOT NULL, state VARCHAR(20) NOT NULL DEFAULT 'ready', claimed_at DATETIME NULL,
 post_id INT NULL, message_id INT NULL, ad_id INT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 INDEX(post_id), INDEX(owner_id), INDEX(message_id), INDEX(ad_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS kdd_social_comments (
 id INT AUTO_INCREMENT PRIMARY KEY, post_id INT NOT NULL, author_id INT NOT NULL, body TEXT NOT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX(post_id,id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS kdd_social_reactions (post_id INT NOT NULL, profile_id INT NOT NULL, value SMALLINT NOT NULL, PRIMARY KEY(post_id,profile_id)) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS kdd_social_messages (
 id INT AUTO_INCREMENT PRIMARY KEY, sender INT NOT NULL, recipient INT NOT NULL, body TEXT NOT NULL,
 read_at DATETIME NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 INDEX(sender,recipient,id), INDEX(recipient,id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS kdd_social_notifications (
 id INT AUTO_INCREMENT PRIMARY KEY, profile_id INT NOT NULL, body TEXT NOT NULL, target VARCHAR(150) NOT NULL DEFAULT '',
 read_at DATETIME NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX(profile_id,id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS kdd_social_reports (
 id INT AUTO_INCREMENT PRIMARY KEY, reporter INT NOT NULL, kind VARCHAR(20) NOT NULL, target_id INT NOT NULL,
 reason TEXT NOT NULL, state VARCHAR(20) NOT NULL DEFAULT 'open', resolution TEXT,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS kdd_social_slots (
 id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL, placement VARCHAR(20) NOT NULL,
 starts_at DATETIME NOT NULL, ends_at DATETIME NOT NULL, price DECIMAL(12,2) NOT NULL DEFAULT 0,
 active TINYINT NOT NULL DEFAULT 1, conditions TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS kdd_social_ads (
 id INT AUTO_INCREMENT PRIMARY KEY, company_id INT NOT NULL, applicant INT NOT NULL, slot_id INT NOT NULL,
 title VARCHAR(120) NOT NULL, body TEXT NOT NULL, target VARCHAR(500) NOT NULL DEFAULT '',
 starts_at DATETIME NOT NULL, ends_at DATETIME NOT NULL, countdown_at DATETIME NULL,
 state VARCHAR(20) NOT NULL DEFAULT 'pending', paid TINYINT NOT NULL DEFAULT 0,
 amount DECIMAL(12,2) NOT NULL DEFAULT 0, decision TEXT, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 INDEX(slot_id,state,starts_at,ends_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS kdd_social_audit (
 id INT AUTO_INCREMENT PRIMARY KEY, actor INT NOT NULL, action VARCHAR(60) NOT NULL,
 details TEXT NOT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS kdd_social_garbage (
 storage VARCHAR(20) NOT NULL, storage_key VARCHAR(200) NOT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY(storage,storage_key)
) ENGINE=InnoDB;
