-- Migration: Add Draft/Publish Status System
-- Version: 0012
-- Description: Adds comprehensive status management for pages and posts with draft, published, scheduled, and archived states
-- Author: Claude Code
-- Date: 2025-10-15
-- Note: This migration is safe to run multiple times (idempotent)

-- ============================================================================
-- UP MIGRATION
-- ============================================================================

-- Add status columns to kdd_website_pages
ALTER TABLE kdd_website_pages
ADD COLUMN status ENUM('draft', 'published', 'scheduled', 'archived') DEFAULT 'draft'
    COMMENT 'Publication status: draft (not visible), published (live), scheduled (auto-publish), archived (hidden)'
    AFTER is_published;

ALTER TABLE kdd_website_pages
ADD COLUMN published_at DATETIME NULL
    COMMENT 'Timestamp when page was published'
    AFTER status;

ALTER TABLE kdd_website_pages
ADD COLUMN scheduled_at DATETIME NULL
    COMMENT 'Timestamp for scheduled publication (NULL if not scheduled)'
    AFTER published_at;

-- Add status columns to kdd_website_posts
ALTER TABLE kdd_website_posts
ADD COLUMN status ENUM('draft', 'published', 'scheduled', 'archived') DEFAULT 'draft'
    COMMENT 'Publication status: draft (not visible), published (live), scheduled (auto-publish), archived (hidden)'
    AFTER is_published;

ALTER TABLE kdd_website_posts
ADD COLUMN published_at DATETIME NULL
    COMMENT 'Timestamp when post was published'
    AFTER status;

ALTER TABLE kdd_website_posts
ADD COLUMN scheduled_at DATETIME NULL
    COMMENT 'Timestamp for scheduled publication (NULL if not scheduled)'
    AFTER published_at;

-- Migrate existing data for kdd_website_pages
-- Set published status and timestamp for currently published pages
UPDATE kdd_website_pages
SET
    status = 'published',
    published_at = COALESCE(updated_at, created_at)
WHERE is_published = 1 AND status = 'draft';

-- Set draft status for unpublished pages
UPDATE kdd_website_pages
SET
    status = 'draft',
    published_at = NULL
WHERE is_published = 0 AND status != 'draft';

-- Migrate existing data for kdd_website_posts
-- Set published status and timestamp for currently published posts
UPDATE kdd_website_posts
SET
    status = 'published',
    published_at = COALESCE(updated_at, created_at)
WHERE is_published = 1 AND status = 'draft';

-- Set draft status for unpublished posts
UPDATE kdd_website_posts
SET
    status = 'draft',
    published_at = NULL
WHERE is_published = 0 AND status != 'draft';

-- Add indexes for efficient querying by status and publication date
CREATE INDEX idx_pages_status ON kdd_website_pages(status);
CREATE INDEX idx_pages_published_at ON kdd_website_pages(published_at);
CREATE INDEX idx_pages_scheduled_at ON kdd_website_pages(scheduled_at);
CREATE INDEX idx_pages_status_published ON kdd_website_pages(status, published_at);

CREATE INDEX idx_posts_status ON kdd_website_posts(status);
CREATE INDEX idx_posts_published_at ON kdd_website_posts(published_at);
CREATE INDEX idx_posts_scheduled_at ON kdd_website_posts(scheduled_at);
CREATE INDEX idx_posts_status_published ON kdd_website_posts(status, published_at);

-- Add comment to is_published column indicating deprecation
ALTER TABLE kdd_website_pages
MODIFY COLUMN is_published TINYINT(1) DEFAULT 0
    COMMENT 'DEPRECATED: Use status column instead. Kept for backward compatibility.';

ALTER TABLE kdd_website_posts
MODIFY COLUMN is_published TINYINT(1) DEFAULT 0
    COMMENT 'DEPRECATED: Use status column instead. Kept for backward compatibility.';

-- ============================================================================
-- Status Field Documentation
-- ============================================================================
--
-- Status Values:
--
-- 'draft' - Content is not visible to public users
--   - Can be edited freely
--   - Only visible in admin interface
--   - No published_at or scheduled_at timestamp
--
-- 'published' - Content is live and visible to public
--   - Visible on website
--   - has published_at timestamp set
--   - Can be edited (changes apply immediately)
--
-- 'scheduled' - Content will be published at specified time
--   - Not yet visible to public
--   - has scheduled_at timestamp set
--   - Automatically transitions to 'published' at scheduled_at time
--   - Requires cron job or scheduled task to process
--
-- 'archived' - Content is hidden but preserved
--   - Not visible to public
--   - Preserved in database for historical reference
--   - Can be restored to draft or published if needed
--   - Keeps original published_at timestamp
--
-- ============================================================================

-- ============================================================================
-- DOWN MIGRATION
-- ============================================================================

/*
-- To revert this migration, run the following commands:

-- Remove indexes
DROP INDEX IF EXISTS idx_pages_status ON kdd_website_pages;
DROP INDEX IF EXISTS idx_pages_published_at ON kdd_website_pages;
DROP INDEX IF EXISTS idx_pages_scheduled_at ON kdd_website_pages;
DROP INDEX IF EXISTS idx_pages_status_published ON kdd_website_pages;

DROP INDEX IF EXISTS idx_posts_status ON kdd_website_posts;
DROP INDEX IF EXISTS idx_posts_published_at ON kdd_website_posts;
DROP INDEX IF EXISTS idx_posts_scheduled_at ON kdd_website_posts;
DROP INDEX IF EXISTS idx_posts_status_published ON kdd_website_posts;

-- Sync is_published with status before removing status columns
UPDATE kdd_website_pages
SET is_published = CASE
    WHEN status = 'published' THEN 1
    ELSE 0
END;

UPDATE kdd_website_posts
SET is_published = CASE
    WHEN status = 'published' THEN 1
    ELSE 0
END;

-- Remove deprecation comment from is_published
ALTER TABLE kdd_website_pages
MODIFY COLUMN is_published TINYINT(1) DEFAULT 0;

ALTER TABLE kdd_website_posts
MODIFY COLUMN is_published TINYINT(1) DEFAULT 0;

-- Remove new columns from kdd_website_pages
ALTER TABLE kdd_website_pages
DROP COLUMN scheduled_at,
DROP COLUMN published_at,
DROP COLUMN status;

-- Remove new columns from kdd_website_posts
ALTER TABLE kdd_website_posts
DROP COLUMN scheduled_at,
DROP COLUMN published_at,
DROP COLUMN status;

*/

-- ============================================================================
-- Implementation Notes
-- ============================================================================
--
-- Backend Implementation:
-- 1. Update API endpoints to use status instead of is_published
-- 2. Add endpoint to handle status transitions (draft->published, etc.)
-- 3. Implement scheduled publishing cron job
-- 4. Add validation to ensure scheduled_at is set when status='scheduled'
--
-- Frontend Implementation:
-- 1. Update UI to show status dropdown instead of publish checkbox
-- 2. Add date/time picker for scheduled publishing
-- 3. Add visual indicators for each status (badges, colors)
-- 4. Filter content by status in admin views
--
-- Query Examples:
-- - Published content: WHERE status = 'published' AND published_at <= NOW()
-- - Scheduled content: WHERE status = 'scheduled' AND scheduled_at > NOW()
-- - All visible content: WHERE status = 'published' AND published_at <= NOW()
-- - Draft content: WHERE status = 'draft'
-- - Archived content: WHERE status = 'archived'
--
-- ============================================================================
