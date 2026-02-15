-- ========================================
-- Migration 0013: Website Templates & Sections System
-- Date: 2025-10-16
-- Description: Adds multi-template support, sections for One-Pager, and News/Documents system
-- ========================================

-- --------------------------------------------------------
-- 1. Erweitere kdd_website_config für Templates
-- --------------------------------------------------------

-- Ändere layout_template von VARCHAR zu ENUM
ALTER TABLE `kdd_website_config`
    MODIFY COLUMN `layout_template` ENUM('default', 'onepager', 'landing', 'portfolio', 'business')
    NOT NULL DEFAULT 'default'
    COMMENT 'Website Template Type';

-- Füge template-spezifische Einstellungen hinzu
ALTER TABLE `kdd_website_config`
    ADD COLUMN IF NOT EXISTS `template_settings` TEXT DEFAULT NULL
    COMMENT 'JSON für template-spezifische Einstellungen' AFTER `layout_template`;

-- Füge Section-Reihenfolge für One-Pager hinzu
ALTER TABLE `kdd_website_config`
    ADD COLUMN IF NOT EXISTS `sections_order` TEXT DEFAULT NULL
    COMMENT 'JSON Array für Section-Reihenfolge bei One-Pager' AFTER `template_settings`;

-- --------------------------------------------------------
-- 2. Erstelle kdd_website_sections Tabelle
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `kdd_website_sections` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `website_id` INT(11) NOT NULL COMMENT 'Verknüpfung zur Website-Konfiguration',
    `section_type` ENUM(
        'hero',
        'about',
        'services',
        'portfolio',
        'team',
        'testimonials',
        'contact',
        'features',
        'pricing',
        'cta',
        'custom'
    ) NOT NULL COMMENT 'Typ der Section',
    `title` VARCHAR(255) DEFAULT NULL COMMENT 'Section Titel',
    `subtitle` VARCHAR(255) DEFAULT NULL COMMENT 'Section Untertitel',
    `content` LONGTEXT DEFAULT NULL COMMENT 'Section Inhalt (HTML oder JSON)',
    `settings` TEXT DEFAULT NULL COMMENT 'JSON für section-spezifische Einstellungen (Farben, Bilder, etc.)',
    `sort_order` INT(11) NOT NULL DEFAULT 0 COMMENT 'Sortierreihenfolge',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Ist die Section aktiv',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `authority_id` INT(11) NOT NULL DEFAULT 1 COMMENT 'Multi-Tenant Authority ID',
    PRIMARY KEY (`id`),
    KEY `website_id` (`website_id`),
    KEY `authority_id` (`authority_id`),
    KEY `sort_order` (`sort_order`),
    KEY `is_active` (`is_active`),
    KEY `idx_website_active_order` (`website_id`, `is_active`, `sort_order`),
    CONSTRAINT `fk_sections_website` FOREIGN KEY (`website_id`)
        REFERENCES `kdd_website_config` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Sections für One-Pager und modular aufgebaute Templates';

-- --------------------------------------------------------
-- 3. Erstelle kdd_website_news Tabelle (für Dokument/News System)
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `kdd_website_news` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `website_id` INT(11) NOT NULL COMMENT 'Verknüpfung zur Website-Konfiguration',
    `title` VARCHAR(255) NOT NULL COMMENT 'News Titel',
    `excerpt` TEXT DEFAULT NULL COMMENT 'Kurzzusammenfassung',
    `content` LONGTEXT DEFAULT NULL COMMENT 'Vollständiger Inhalt',
    `document_id` INT(11) DEFAULT NULL COMMENT 'Verknüpfung zu kdd_website_media (optional)',
    `thumbnail` VARCHAR(255) DEFAULT NULL COMMENT 'Vorschaubild',
    `is_published` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Veröffentlicht Status',
    `published_at` DATETIME DEFAULT NULL COMMENT 'Veröffentlichungsdatum',
    `expires_at` DATETIME DEFAULT NULL COMMENT 'Ablaufdatum (optional)',
    `priority` ENUM('low', 'normal', 'high', 'urgent') NOT NULL DEFAULT 'normal' COMMENT 'Priorität',
    `category` VARCHAR(100) DEFAULT 'announcement' COMMENT 'Kategorie (announcement, update, download, news)',
    `download_count` INT(11) NOT NULL DEFAULT 0 COMMENT 'Anzahl der Downloads',
    `view_count` INT(11) NOT NULL DEFAULT 0 COMMENT 'Anzahl der Ansichten',
    `sort_order` INT(11) NOT NULL DEFAULT 0 COMMENT 'Sortierreihenfolge',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `authority_id` INT(11) NOT NULL DEFAULT 1 COMMENT 'Multi-Tenant Authority ID',
    PRIMARY KEY (`id`),
    KEY `website_id` (`website_id`),
    KEY `document_id` (`document_id`),
    KEY `authority_id` (`authority_id`),
    KEY `is_published` (`is_published`, `published_at`),
    KEY `priority` (`priority`),
    KEY `category` (`category`),
    KEY `idx_website_published` (`website_id`, `is_published`, `published_at`),
    CONSTRAINT `fk_news_website` FOREIGN KEY (`website_id`)
        REFERENCES `kdd_website_config` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_news_document` FOREIGN KEY (`document_id`)
        REFERENCES `kdd_website_media` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='News, Ankündigungen und Dokumente für die Website';

-- --------------------------------------------------------
-- 4. Erstelle kdd_website_news_downloads Tabelle (Download Tracking)
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `kdd_website_news_downloads` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `news_id` INT(11) NOT NULL COMMENT 'Verknüpfung zu kdd_website_news',
    `ip_address` VARCHAR(45) DEFAULT NULL COMMENT 'IP-Adresse des Downloads',
    `user_agent` TEXT DEFAULT NULL COMMENT 'Browser User Agent',
    `downloaded_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Download-Zeitpunkt',
    PRIMARY KEY (`id`),
    KEY `news_id` (`news_id`),
    KEY `downloaded_at` (`downloaded_at`),
    CONSTRAINT `fk_downloads_news` FOREIGN KEY (`news_id`)
        REFERENCES `kdd_website_news` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Download-Tracking für News-Dokumente';

-- --------------------------------------------------------
-- 5. Erweitere kdd_website_media für News-Dokumente
-- --------------------------------------------------------

-- Füge 'news_doc' zum media_type ENUM hinzu (falls die Spalte existiert)
ALTER TABLE `kdd_website_media`
    MODIFY COLUMN `media_type` ENUM('image', 'document', 'video', 'audio', 'news_doc', 'logo', 'banner', 'hero_image', 'background_image', 'content')
    NOT NULL DEFAULT 'image'
    COMMENT 'Typ des Mediums';

-- ========================================
-- Migration 0013 erfolgreich!
-- ========================================
