-- Migration: Add versioning system for website pages and posts
-- Created: 2025-10-15
-- Description: Implements version tracking and history for pages and posts
--              allowing draft versions and publishing workflow

-- ============================================================================
-- FORWARD MIGRATION
-- ============================================================================

-- Add version columns to existing pages table
ALTER TABLE kdd_website_pages
    ADD COLUMN version INT DEFAULT 1 COMMENT 'Current working version number',
    ADD COLUMN published_version INT DEFAULT NULL COMMENT 'Last published version number (NULL = never published)';

-- Add version columns to existing posts table
ALTER TABLE kdd_website_posts
    ADD COLUMN version INT DEFAULT 1 COMMENT 'Current working version number',
    ADD COLUMN published_version INT DEFAULT NULL COMMENT 'Last published version number (NULL = never published)';

-- Create page versions history table
CREATE TABLE kdd_website_page_versions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    page_id INT NOT NULL COMMENT 'Reference to parent page',
    version INT NOT NULL COMMENT 'Version number',
    authority_id INT NOT NULL COMMENT 'Multi-tenant isolation',
    title VARCHAR(255) NOT NULL COMMENT 'Page title at this version',
    slug VARCHAR(255) NOT NULL COMMENT 'URL slug at this version',
    content LONGTEXT COMMENT 'Page content at this version',
    meta_title VARCHAR(255) COMMENT 'SEO meta title',
    meta_description TEXT COMMENT 'SEO meta description',
    status VARCHAR(50) DEFAULT 'draft' COMMENT 'Version status: draft, published, archived',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'When this version was created',
    created_by INT COMMENT 'User ID who created this version',

    FOREIGN KEY (page_id) REFERENCES kdd_website_pages(id) ON DELETE CASCADE,
    FOREIGN KEY (authority_id) REFERENCES kdd_authorities(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES kdd_users(id) ON DELETE SET NULL,

    UNIQUE KEY unique_page_version (page_id, version),
    INDEX idx_page_authority (page_id, authority_id),
    INDEX idx_version_status (version, status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Version history for website pages';

-- Create post versions history table
CREATE TABLE kdd_website_post_versions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    post_id INT NOT NULL COMMENT 'Reference to parent post',
    version INT NOT NULL COMMENT 'Version number',
    authority_id INT NOT NULL COMMENT 'Multi-tenant isolation',
    title VARCHAR(255) NOT NULL COMMENT 'Post title at this version',
    slug VARCHAR(255) NOT NULL COMMENT 'URL slug at this version',
    content LONGTEXT COMMENT 'Post content at this version',
    excerpt TEXT COMMENT 'Post excerpt at this version',
    featured_image VARCHAR(500) COMMENT 'Featured image URL at this version',
    meta_title VARCHAR(255) COMMENT 'SEO meta title',
    meta_description TEXT COMMENT 'SEO meta description',
    status VARCHAR(50) DEFAULT 'draft' COMMENT 'Version status: draft, published, archived',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'When this version was created',
    created_by INT COMMENT 'User ID who created this version',

    FOREIGN KEY (post_id) REFERENCES kdd_website_posts(id) ON DELETE CASCADE,
    FOREIGN KEY (authority_id) REFERENCES kdd_authorities(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES kdd_users(id) ON DELETE SET NULL,

    UNIQUE KEY unique_post_version (post_id, version),
    INDEX idx_post_authority (post_id, authority_id),
    INDEX idx_version_status (version, status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Version history for website posts';

-- Add indexes to parent tables for version queries
ALTER TABLE kdd_website_pages
    ADD INDEX idx_version (version),
    ADD INDEX idx_published_version (published_version);

ALTER TABLE kdd_website_posts
    ADD INDEX idx_version (version),
    ADD INDEX idx_published_version (published_version);

-- ============================================================================
-- NOTES ON VERSIONING SYSTEM
-- ============================================================================
--
-- How the versioning system works:
--
-- 1. Draft Workflow:
--    - Each save creates a new version in the _versions table
--    - Main table (kdd_website_pages/posts) always has current working version
--    - `version` column increments with each save
--    - `published_version` remains NULL or points to last published version
--
-- 2. Publishing:
--    - When publishing, set `published_version` = current `version`
--    - Mark that version as 'published' in the _versions table
--    - Previous published versions are marked as 'archived'
--
-- 3. Version History:
--    - All versions are kept in _versions tables
--    - Can compare versions, rollback to previous versions
--    - Audit trail of who changed what and when
--
-- 4. Multi-tenant:
--    - All version tables include authority_id for data isolation
--    - Queries must filter by authority_id
--
-- Example usage:
--
-- Creating a new version:
--   INSERT INTO kdd_website_page_versions
--     (page_id, version, authority_id, title, slug, content, created_by)
--   SELECT id, version, authority_id, title, slug, content, updated_by
--   FROM kdd_website_pages WHERE id = ?;
--
--   UPDATE kdd_website_pages SET version = version + 1 WHERE id = ?;
--
-- Publishing:
--   UPDATE kdd_website_pages
--   SET published_version = version
--   WHERE id = ?;
--
--   UPDATE kdd_website_page_versions
--   SET status = 'published'
--   WHERE page_id = ? AND version = ?;
--
-- Reverting to previous version:
--   UPDATE kdd_website_pages p
--   JOIN kdd_website_page_versions v ON p.id = v.page_id
--   SET p.title = v.title, p.slug = v.slug, p.content = v.content,
--       p.version = p.version + 1
--   WHERE p.id = ? AND v.version = ?;
-- ============================================================================

-- ============================================================================
-- ROLLBACK MIGRATION
-- ============================================================================

-- To rollback this migration, run the following SQL:
--
-- -- Remove indexes from parent tables
-- ALTER TABLE kdd_website_posts
--     DROP INDEX idx_published_version,
--     DROP INDEX idx_version;
--
-- ALTER TABLE kdd_website_pages
--     DROP INDEX idx_published_version,
--     DROP INDEX idx_version;
--
-- -- Drop version history tables
-- DROP TABLE IF EXISTS kdd_website_post_versions;
-- DROP TABLE IF EXISTS kdd_website_page_versions;
--
-- -- Remove version columns from parent tables
-- ALTER TABLE kdd_website_posts
--     DROP COLUMN published_version,
--     DROP COLUMN version;
--
-- ALTER TABLE kdd_website_pages
--     DROP COLUMN published_version,
--     DROP COLUMN version;

-- ============================================================================
-- END OF MIGRATION
-- ============================================================================
