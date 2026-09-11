-- ============================================================
-- K-Systems - Grundstock der Datenbank
--
-- Diese Datei legt eine vollstaendige, leere Anlage an: alle Tabellen und
-- Ansichten, dazu die Stammdaten, ohne die nichts laeuft - eine Behoerde, die
-- Rollen, saemtliche Rechte, die Modul-Freischaltungen, die Grundeinstellungen
-- und die Dashboard-Vorlagen.
--
-- Sie wird nicht von Hand eingespielt. docker-compose haengt sie als
-- docker-entrypoint-initdb.d/01-init.sql ein, MySQL fuehrt sie genau einmal
-- aus: beim ersten Start mit leerem Datenverzeichnis.
--
-- ES IST ABSICHTLICH KEIN BENUTZER ENTHALTEN.
--
-- Hier stand zuvor ein Konto "admin" mit dem allgemein bekannten Hash eines
-- Standardpassworts - jede Anlage waere mit demselben Zugang offen gewesen.
-- Zudem verwies kdd_user_roles auf die Rolle 20, die diese Datei gar nicht
-- anlegte: der mitgelieferte Admin haette keine Rechte gehabt.
--
-- Stattdessen legt die Anwendung den ersten Zugang selbst an. Solange
-- kdd_users leer ist, oeffnet sie die Ersteinrichtung; das dort angelegte
-- Konto bekommt die Rolle 20 "System Administrator" und damit ALL_PERMISSIONS
-- und SYSTEM_ADMIN. Ist ein Benutzer vorhanden, verweigert die Einrichtung
-- den Dienst.
--
-- Die Tabelle schema_migrations ist mitgefuellt: der Stand dieser Datei
-- entspricht allen bisherigen Migrationen, sie duerfen nicht noch einmal
-- laufen.
-- ============================================================

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

-- ------------------------------------------------------------
-- Struktur
-- ------------------------------------------------------------

/*M!999999\- enable the sandbox mode */ 
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;
CREATE TABLE `kdd_access_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `table` varchar(255) NOT NULL COMMENT 'Table name being accessed',
  `userid` int(11) NOT NULL COMMENT 'ID of the user accessing the resource',
  `entry_id` int(11) DEFAULT NULL COMMENT 'ID of the specific entry being accessed',
  `date` datetime DEFAULT current_timestamp() COMMENT 'Timestamp of the access',
  `authority_id` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `fk_access_logs_authority` (`authority_id`),
  CONSTRAINT `fk_access_logs_authority` FOREIGN KEY (`authority_id`) REFERENCES `kdd_authorities` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Logs for tracking access to various tables';
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `kdd_active_sessions` AS SELECT
 1 AS `id`,
  1 AS `user_id`,
  1 AS `authority_id`,
  1 AS `token_hash`,
  1 AS `jti`,
  1 AS `device_name`,
  1 AS `device_type`,
  1 AS `ip_address`,
  1 AS `user_agent`,
  1 AS `last_activity`,
  1 AS `created_at`,
  1 AS `expires_at`,
  1 AS `is_active`,
  1 AS `username`,
  1 AS `email` */;
SET character_set_client = @saved_cs_client;
CREATE TABLE `kdd_apartment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `location` varchar(255) NOT NULL,
  `street` varchar(255) NOT NULL,
  `housenumber` varchar(50) NOT NULL,
  `units` int(11) NOT NULL,
  `bought` tinyint(1) DEFAULT 0,
  `rented` tinyint(1) DEFAULT 0,
  `text` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_deleted` tinyint(1) DEFAULT 0,
  `authority_id` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `fk_apartment_authority` (`authority_id`),
  CONSTRAINT `fk_apartment_authority` FOREIGN KEY (`authority_id`) REFERENCES `kdd_authorities` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
CREATE TABLE `kdd_apartment_rel` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `apartment_id` int(11) NOT NULL,
  `person_id` int(11) NOT NULL,
  `type` enum('owner','tenant','landlord','subtenant') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `authority_id` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `apartment_id` (`apartment_id`),
  KEY `person_id` (`person_id`),
  KEY `fk_apartment_rel_authority` (`authority_id`),
  CONSTRAINT `fk_apartment_rel_authority` FOREIGN KEY (`authority_id`) REFERENCES `kdd_authorities` (`id`),
  CONSTRAINT `kdd_apartment_rel_ibfk__fireguard_1` FOREIGN KEY (`apartment_id`) REFERENCES `kdd_apartment` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
CREATE TABLE `kdd_applicant` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `birthdate` date NOT NULL,
  `email` varchar(255) NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `info` text DEFAULT NULL,
  `type` int(11) NOT NULL COMMENT '1= firefighter, 2= administration',
  `jobinterviewDate` date NOT NULL,
  `jobinterviewTime` time NOT NULL,
  `phonenumber` varchar(30) NOT NULL,
  `added` datetime NOT NULL DEFAULT current_timestamp(),
  `authority_id` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `fk_applicant_authority` (`authority_id`),
  CONSTRAINT `fk_applicant_authority` FOREIGN KEY (`authority_id`) REFERENCES `kdd_authorities` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `kdd_applicant_answers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `applicant_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `answer` mediumtext NOT NULL,
  `authority_id` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `applicant_id` (`applicant_id`,`question_id`),
  KEY `question_id` (`question_id`),
  KEY `fk_applicant_answers_authority` (`authority_id`),
  CONSTRAINT `fk_applicant_answers_authority` FOREIGN KEY (`authority_id`) REFERENCES `kdd_authorities` (`id`),
  CONSTRAINT `kdd_applicant_answers_ibfk__fireguard_1` FOREIGN KEY (`applicant_id`) REFERENCES `kdd_applicant` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `kdd_applicant_answers_ibfk__fireguard_2` FOREIGN KEY (`question_id`) REFERENCES `kdd_applicant_questions` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `kdd_applicant_questions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` int(2) NOT NULL COMMENT '1= firefighter, 2 = admin, 3= both',
  `question` mediumtext NOT NULL,
  `sort_order` int(11) NOT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `authority_id` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `fk_applicant_questions_authority` (`authority_id`),
  CONSTRAINT `fk_applicant_questions_authority` FOREIGN KEY (`authority_id`) REFERENCES `kdd_authorities` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `kdd_authorities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `display_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `logo_url` varchar(255) DEFAULT NULL COMMENT 'URL zum Authority-Logo',
  `primary_color` varchar(7) DEFAULT '#3B82F6' COMMENT 'Haupt-Farbe für Buttons etc.',
  `secondary_color` varchar(7) DEFAULT '#6B7280' COMMENT 'Sekundär-Farbe für Akzente',
  `app_title` varchar(100) DEFAULT NULL COMMENT 'Custom App-Titel (z.B. LSFD Los Santos)',
  `default_background` varchar(255) DEFAULT NULL COMMENT 'Standard-Hintergrund für neue Member',
  `theme_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Complete theme configuration as JSON' CHECK (json_valid(`theme_settings`)),
  `authority_type` enum('faction','company','government','private') DEFAULT 'private',
  `has_custom_domain` tinyint(1) DEFAULT 0,
  `mail_domain` varchar(191) DEFAULT NULL,
  `default_mail_quota` int(11) DEFAULT 5 COMMENT 'Wie viele Mails kann ein User dieser Authority haben?',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `idx_active` (`active`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
CREATE TABLE `kdd_authority_features` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `code` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `kdd_authority_features_rel` (
  `authority_id` int(11) NOT NULL,
  `feature_id` int(11) NOT NULL,
  PRIMARY KEY (`authority_id`,`feature_id`),
  KEY `feature_id` (`feature_id`),
  CONSTRAINT `fk_auth_feat_rel_auth` FOREIGN KEY (`authority_id`) REFERENCES `kdd_authorities` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_auth_feat_rel_feat` FOREIGN KEY (`feature_id`) REFERENCES `kdd_authority_features` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `kdd_authority_fields` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `authority_id` int(11) NOT NULL,
  `field_name` varchar(50) NOT NULL,
  `display_name` varchar(100) NOT NULL,
  `field_type` enum('text','number','date','boolean','select','textarea','multiselect') NOT NULL,
  `required` tinyint(1) DEFAULT 0,
  `options` text DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `auth_field_unique` (`authority_id`,`field_name`),
  CONSTRAINT `kdd_authority_fields_ibfk_1` FOREIGN KEY (`authority_id`) REFERENCES `kdd_authorities` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
CREATE TABLE `kdd_blackboard` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(50) DEFAULT NULL,
  `author` varchar(50) DEFAULT NULL,
  `created` timestamp NULL DEFAULT current_timestamp(),
  `text` text DEFAULT NULL,
  `color` varchar(7) DEFAULT '#212121',
  `deleted` tinyint(4) DEFAULT 0,
  `need_readed` tinyint(4) DEFAULT 0,
  `pinned` tinyint(4) DEFAULT 0,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `blackboard` varchar(50) DEFAULT 'normal',
  `authority_id` int(11) NOT NULL DEFAULT 1,
  `area_id` int(11) DEFAULT NULL COMMENT 'Referenz zu kdd_blackboard_areas (NULL für global/legacy)',
  KEY `id` (`id`),
  KEY `fk_blackboard_authority` (`authority_id`),
  KEY `idx_area_id` (`area_id`),
  CONSTRAINT `fk_blackboard_authority` FOREIGN KEY (`authority_id`) REFERENCES `kdd_authorities` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
CREATE TABLE `kdd_blackboard_area_permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `area_id` int(11) NOT NULL COMMENT 'Referenz zu kdd_blackboard_areas',
  `role_id` int(11) NOT NULL COMMENT 'Referenz zu kdd_roles',
  `authority_id` int(11) NOT NULL COMMENT 'Multi-Tenancy: Behörden-Kontext',
  `can_read` tinyint(1) DEFAULT 0 COMMENT 'Darf lesen?',
  `can_write` tinyint(1) DEFAULT 0 COMMENT 'Darf schreiben/bearbeiten?',
  `can_delete` tinyint(1) DEFAULT 0 COMMENT 'Darf löschen?',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_area_role_auth` (`area_id`,`role_id`,`authority_id`),
  KEY `idx_area` (`area_id`),
  KEY `idx_role` (`role_id`),
  KEY `idx_authority` (`authority_id`),
  CONSTRAINT `kdd_blackboard_area_permissions_ibfk_1` FOREIGN KEY (`area_id`) REFERENCES `kdd_blackboard_areas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `kdd_blackboard_area_permissions_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `kdd_roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `kdd_blackboard_area_permissions_ibfk_3` FOREIGN KEY (`authority_id`) REFERENCES `kdd_authorities` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Berechtigungen für Blackboard-Bereiche pro Rolle';
CREATE TABLE `kdd_blackboard_areas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `authority_id` int(11) NOT NULL COMMENT 'Behörde, der dieser Bereich gehört',
  `key` varchar(50) NOT NULL COMMENT 'Eindeutiger Schlüssel (z.B. schichtubergabe)',
  `name` varchar(100) NOT NULL COMMENT 'Anzeigename des Bereichs',
  `description` text DEFAULT NULL COMMENT 'Optionale Beschreibung',
  `icon` varchar(50) DEFAULT 'mdi-bulletin-board' COMMENT 'Material Design Icon',
  `is_active` tinyint(1) DEFAULT 1 COMMENT 'Ist dieser Bereich aktiv?',
  `sort_order` int(11) DEFAULT 0 COMMENT 'Sortierreihenfolge',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_key_per_authority` (`authority_id`,`key`),
  KEY `idx_authority` (`authority_id`),
  KEY `idx_active` (`is_active`),
  KEY `idx_sort` (`sort_order`),
  CONSTRAINT `kdd_blackboard_areas_ibfk_1` FOREIGN KEY (`authority_id`) REFERENCES `kdd_authorities` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Konfigurierbare Blackboard-Bereiche pro Behörde';
CREATE TABLE `kdd_blackboard_rel` (
  `entry_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `servicenumber` varchar(4) DEFAULT NULL,
  `readed_at` timestamp NULL DEFAULT current_timestamp(),
  `authority_id` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`entry_id`,`employee_id`),
  KEY `FK__kdd_employee` (`employee_id`),
  KEY `fk_blackboard_rel_authority` (`authority_id`),
  CONSTRAINT `FK__kddfireguard__blackboard` FOREIGN KEY (`entry_id`) REFERENCES `kdd_blackboard` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK__kddfireguard__employee` FOREIGN KEY (`employee_id`) REFERENCES `kdd_employee` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_blackboard_rel_authority` FOREIGN KEY (`authority_id`) REFERENCES `kdd_authorities` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
CREATE TABLE `kdd_calendar` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `content` varchar(255) NOT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `color` varchar(10) NOT NULL,
  `contentFull` mediumtext DEFAULT NULL,
  `recurring` varchar(255) DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `linked_to_table` varchar(50) DEFAULT NULL,
  `linked_to_id` int(11) DEFAULT NULL,
  `authority_id` int(11) NOT NULL DEFAULT 1,
  `group_id` int(11) DEFAULT NULL,
  `is_private` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `fk_calendar_authority` (`authority_id`),
  KEY `group_id` (`group_id`),
  CONSTRAINT `fk_calendar_authority` FOREIGN KEY (`authority_id`) REFERENCES `kdd_authorities` (`id`),
  CONSTRAINT `kdd_calendar_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `kdd_calendar_groups` (`id`),
  CONSTRAINT `kdd_calendar_ibfk_2` FOREIGN KEY (`group_id`) REFERENCES `kdd_calendar_groups` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `kdd_calendar_assigned` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `event_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `authority_id` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `event_id` (`event_id`),
  KEY `fk_calendar_assigned_authority` (`authority_id`),
  CONSTRAINT `fk_calendar_assigned_authority` FOREIGN KEY (`authority_id`) REFERENCES `kdd_authorities` (`id`),
  CONSTRAINT `kdd_calendar_assigned_ibfk__fireguard_1` FOREIGN KEY (`event_id`) REFERENCES `kdd_calendar` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `kdd_calendar_group_members` (
  `group_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `authority_id` int(11) NOT NULL,
  `can_edit` tinyint(1) DEFAULT 0,
  `can_delete` tinyint(1) DEFAULT 0,
  `can_manage` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `is_deleted` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`group_id`,`user_id`),
  KEY `user_id` (`user_id`),
  KEY `authority_id` (`authority_id`),
  CONSTRAINT `kdd_calendar_group_members_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `kdd_calendar_groups` (`id`),
  CONSTRAINT `kdd_calendar_group_members_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `kdd_users` (`id`),
  CONSTRAINT `kdd_calendar_group_members_ibfk_3` FOREIGN KEY (`authority_id`) REFERENCES `kdd_authorities` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
CREATE TABLE `kdd_calendar_groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `authority_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `color` varchar(7) DEFAULT '#3788d8',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `is_deleted` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `authority_id` (`authority_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `kdd_calendar_groups_ibfk_1` FOREIGN KEY (`authority_id`) REFERENCES `kdd_authorities` (`id`),
  CONSTRAINT `kdd_calendar_groups_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `kdd_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
CREATE TABLE `kdd_cheatsheet_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `authority_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(50) DEFAULT 'mdi-information',
  `color` varchar(20) DEFAULT 'primary',
  `content_type` enum('table','text','image','mixed','grid') DEFAULT 'table',
  `width` enum('full','half','third','quarter') DEFAULT 'third',
  `height` enum('auto','small','medium','large') DEFAULT 'medium',
  `order_index` int(11) DEFAULT 0,
  `enabled` tinyint(1) DEFAULT 1,
  `collapsible` tinyint(1) DEFAULT 0,
  `default_collapsed` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `grid_x` int(11) DEFAULT NULL COMMENT 'Grid X position for vue-grid-layout',
  `grid_y` int(11) DEFAULT NULL COMMENT 'Grid Y position for vue-grid-layout',
  `grid_w` int(11) DEFAULT NULL COMMENT 'Grid width (columns) for vue-grid-layout',
  `grid_h` int(11) DEFAULT NULL COMMENT 'Grid height (rows) for vue-grid-layout',
  `show_headers` tinyint(1) DEFAULT 1 COMMENT 'Show table headers for table content types',
  `auto_height` tinyint(1) DEFAULT 0 COMMENT 'Table height adapts to number of entries',
  PRIMARY KEY (`id`),
  KEY `idx_authority_order` (`authority_id`,`order_index`),
  KEY `idx_enabled` (`enabled`),
  KEY `idx_grid_position` (`grid_x`,`grid_y`),
  CONSTRAINT `kdd_cheatsheet_categories_ibfk_1` FOREIGN KEY (`authority_id`) REFERENCES `kdd_authorities` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `kdd_cheatsheet_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL,
  `authority_id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `content_text` text DEFAULT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `image_caption` text DEFAULT NULL,
  `layout_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`layout_json`)),
  `order_index` int(11) DEFAULT 0,
  `enabled` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_category_order` (`category_id`,`order_index`),
  KEY `idx_authority` (`authority_id`),
  CONSTRAINT `kdd_cheatsheet_items_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `kdd_cheatsheet_categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `kdd_cheatsheet_items_ibfk_2` FOREIGN KEY (`authority_id`) REFERENCES `kdd_authorities` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=267 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `kdd_companies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `ceo` varchar(255) DEFAULT NULL,
  `co_ceo` varchar(255) DEFAULT NULL,
  `type_id` int(11) NOT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `phonenumber` varchar(255) DEFAULT NULL,
  `last_fire_protection_inspection` date DEFAULT NULL,
  `fire_protection_inspection_valid_until` date DEFAULT NULL,
  `extinguisher_count` int(11) DEFAULT 0,
  `contract_available` tinyint(1) DEFAULT 0,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `is_deleted` tinyint(1) DEFAULT 0,
  `email` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `authority_id` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `type_id` (`type_id`),
  KEY `fk_companies_authority` (`authority_id`),
  CONSTRAINT `fk_companies_authority` FOREIGN KEY (`authority_id`) REFERENCES `kdd_authorities` (`id`),
  CONSTRAINT `kdd_companies_ibfk__fireguard_1` FOREIGN KEY (`type_id`) REFERENCES `kdd_company_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
CREATE TABLE `kdd_company_extinguishers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `identifier` varchar(255) NOT NULL,
  `company_id` int(11) NOT NULL,
  `registration_date` date DEFAULT NULL,
  `last_inspection` date DEFAULT NULL,
  `inspected_by` varchar(255) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `is_deleted` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `authority_id` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `company_id` (`company_id`),
  KEY `fk_company_extinguishers_authority` (`authority_id`),
  CONSTRAINT `fk_company_extinguishers_authority` FOREIGN KEY (`authority_id`) REFERENCES `kdd_authorities` (`id`),
  CONSTRAINT `kdd_company_extinguishers_ibfk__fireguard_1` FOREIGN KEY (`company_id`) REFERENCES `kdd_companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
CREATE TABLE `kdd_company_form_submissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `website_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `message` text NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `authority_id` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `website_id` (`website_id`),
  KEY `fk_company_form_submissions_authority` (`authority_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `kdd_company_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `ceo` varchar(255) NOT NULL,
  `co_ceo` varchar(255) DEFAULT NULL,
  `contact_person` varchar(255) NOT NULL,
  `control_center_number` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `authority_id` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `company_id` (`company_id`),
  KEY `fk_company_history_authority` (`authority_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
CREATE TABLE `kdd_company_mailbox_permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `mailbox_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `group_id` int(11) DEFAULT NULL,
  `authority_id` int(11) NOT NULL,
  `can_read` tinyint(1) DEFAULT 1,
  `can_send` tinyint(1) DEFAULT 1,
  `can_delete` tinyint(1) DEFAULT 0,
  `can_assign` tinyint(1) DEFAULT 1,
  `can_manage_members` tinyint(1) DEFAULT 0,
  `can_manage_settings` tinyint(1) DEFAULT 0,
  `added_at` timestamp NULL DEFAULT current_timestamp(),
  `added_by_user_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_mailbox_user` (`mailbox_id`,`user_id`),
  UNIQUE KEY `unique_mailbox_group` (`mailbox_id`,`group_id`),
  KEY `idx_mailbox` (`mailbox_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_group` (`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `kdd_company_mailboxes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `mail_account_id` int(11) NOT NULL,
  `authority_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `department` varchar(255) DEFAULT NULL,
  `max_mail_addresses` int(11) DEFAULT 5,
  `current_mail_count` int(11) DEFAULT 1,
  `auto_reply_enabled` tinyint(1) DEFAULT 0,
  `auto_reply_message` text DEFAULT NULL,
  `signature` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_mail_account` (`mail_account_id`),
  KEY `idx_authority` (`authority_id`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `kdd_company_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_deleted` tinytext DEFAULT '0',
  `authority_id` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `fk_company_types_authority` (`authority_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
CREATE TABLE `kdd_contacts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `owner_user_id` int(11) NOT NULL,
  `authority_id` int(11) NOT NULL,
  `contact_type` enum('personal','company') DEFAULT 'personal',
  `email` varchar(255) NOT NULL,
  `secondary_emails` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`secondary_emails`)),
  `display_name` varchar(255) NOT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `company` varchar(255) DEFAULT NULL,
  `department` varchar(255) DEFAULT NULL,
  `position` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `mobile` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `tags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tags`)),
  `category` varchar(100) DEFAULT NULL,
  `is_favorite` tinyint(1) DEFAULT 0,
  `notes` text DEFAULT NULL,
  `avatar_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_owner` (`owner_user_id`),
  KEY `idx_email` (`email`),
  KEY `idx_favorite` (`is_favorite`),
  KEY `idx_authority` (`authority_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `kdd_dashboard_audit` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `authority_id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL COMMENT 'created, updated, deleted, exported, imported, template_applied',
  `layout_id` int(11) DEFAULT NULL,
  `template_key` varchar(50) DEFAULT NULL,
  `changes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Details of what changed' CHECK (json_valid(`changes`)),
  `imported_from` varchar(64) DEFAULT NULL COMMENT 'Export hash if imported from another user',
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `authority_id` (`authority_id`),
  KEY `layout_id` (`layout_id`),
  KEY `idx_user_action` (`user_id`,`action`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `kdd_dashboard_audit_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `kdd_users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `kdd_dashboard_audit_ibfk_2` FOREIGN KEY (`authority_id`) REFERENCES `kdd_authorities` (`id`) ON DELETE CASCADE,
  CONSTRAINT `kdd_dashboard_audit_ibfk_3` FOREIGN KEY (`layout_id`) REFERENCES `kdd_dashboard_layout` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Audit log for dashboard layout changes';
CREATE TABLE `kdd_dashboard_layout` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `authority_id` int(11) NOT NULL,
  `layout` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'Widget positions and sizes in grid format' CHECK (json_valid(`layout`)),
  `widgets` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'Widget types and individual widget configurations' CHECK (json_valid(`widgets`)),
  `template` varchar(50) DEFAULT 'custom' COMMENT 'Template type: custom, dispatcher, hr_manager, report_manager, member, admin',
  `is_template` tinyint(1) DEFAULT 0 COMMENT 'Whether this layout is saved as a template',
  `template_name` varchar(100) DEFAULT NULL COMMENT 'Name for saved templates',
  `template_description` text DEFAULT NULL COMMENT 'Description of the template',
  `is_public` tinyint(1) DEFAULT 0 COMMENT 'Whether this template can be imported by other users',
  `export_hash` varchar(64) DEFAULT NULL COMMENT 'Unique hash for export/import identification',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_dashboard` (`user_id`,`authority_id`),
  UNIQUE KEY `export_hash` (`export_hash`),
  KEY `authority_id` (`authority_id`),
  KEY `idx_template` (`is_template`,`is_public`),
  KEY `idx_export_hash` (`export_hash`),
  CONSTRAINT `kdd_dashboard_layout_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `kdd_users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `kdd_dashboard_layout_ibfk_2` FOREIGN KEY (`authority_id`) REFERENCES `kdd_authorities` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='User dashboard layouts with widget configurations';
CREATE TABLE `kdd_dashboard_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `template_key` varchar(50) NOT NULL COMMENT 'Unique identifier: dispatcher, hr_manager, etc.',
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(50) DEFAULT 'mdi-view-dashboard',
  `required_permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Array of permission names required' CHECK (json_valid(`required_permissions`)),
  `layout` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`layout`)),
  `widgets` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`widgets`)),
  `category` varchar(50) DEFAULT 'general',
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `template_key` (`template_key`)
) ENGINE=InnoDB AUTO_INCREMENT=42 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Pre-built dashboard templates';
CREATE TABLE `kdd_database_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `action` enum('INSERT','UPDATE','DELETE','LOGIN') NOT NULL,
  `table_name` varchar(255) NOT NULL,
  `record_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `column_name` varchar(255) NOT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  `authority_id` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `fk_database_logs_authority` (`authority_id`),
  KEY `idx_timestamp` (`timestamp`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_table_name` (`table_name`),
  KEY `idx_action` (`action`),
  KEY `idx_record` (`table_name`,`record_id`),
  KEY `idx_authority_timestamp` (`authority_id`,`timestamp`)
) ENGINE=InnoDB AUTO_INCREMENT=4560 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `kdd_database_logs_archive` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `action` enum('INSERT','UPDATE','DELETE','LOGIN') NOT NULL,
  `table_name` varchar(255) NOT NULL,
  `record_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `column_name` varchar(255) NOT NULL,
  `old_value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_value`)),
  `new_value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_value`)),
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  `authority_id` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `fk_database_logs_authority` (`authority_id`),
  KEY `idx_timestamp` (`timestamp`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_table_name` (`table_name`),
  KEY `idx_action` (`action`),
  KEY `idx_record` (`table_name`,`record_id`),
  KEY `idx_authority_timestamp` (`authority_id`,`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `kdd_desktop_settings` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL,
  `background` varchar(255) NOT NULL DEFAULT '/img/backgrounds/desktop-bg-dark.jpg',
  `icon_positions` longtext NOT NULL,
  `window_layouts` longtext NOT NULL,
  `theme` varchar(50) NOT NULL DEFAULT 'dark',
  `widget_state` longtext NOT NULL DEFAULT '{"active":[],"positions":{}}',
  `taskbar_position` varchar(10) DEFAULT 'bottom',
  `taskbar_size` varchar(10) DEFAULT 'medium',
  `taskbar_autohide` tinyint(1) DEFAULT 0,
  `taskbar_transparency` int(11) DEFAULT 30,
  `taskbar_blur` int(11) DEFAULT 10,
  `taskbar_group_apps` tinyint(1) DEFAULT 1,
  `taskbar_icon_style` varchar(20) DEFAULT 'filled',
  `taskbar_pinned_apps` longtext DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `authority_id` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `authority_user` (`authority_id`,`user_id`),
  KEY `fk_desktop_settings_authority` (`authority_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `kdd_dispatch` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `name` varchar(255) NOT NULL,
  `status` varchar(50) DEFAULT NULL,
  `sort_order` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `authority_id` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `fk_dispatch_authority` (`authority_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
CREATE TABLE `kdd_doc_area_permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `area_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `can_read` tinyint(1) NOT NULL DEFAULT 0,
  `can_write` tinyint(1) NOT NULL DEFAULT 0,
  `can_delete` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_area_role` (`area_id`,`role_id`),
  KEY `fk_area_permissions_role` (`role_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `kdd_doc_areas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key` varchar(50) NOT NULL COMMENT 'Eindeutiger Schlüssel für den Bereich',
  `name` varchar(100) NOT NULL COMMENT 'Anzeigename des Bereichs',
  `description` text DEFAULT NULL,
  `icon` varchar(50) DEFAULT 'mdi-file-document-outline',
  `is_system` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Ob es sich um einen Systembereich handelt',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `authority_id` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `key_authority` (`key`,`authority_id`),
  KEY `fk_doc_areas_authority` (`authority_id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `kdd_doc_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `site` varchar(255) DEFAULT NULL,
  `authority_id` int(11) NOT NULL DEFAULT 1,
  `area_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `parent_id` (`parent_id`),
  KEY `fk_doc_categories_authority` (`authority_id`),
  KEY `fk_categories_area` (`area_id`)
) ENGINE=InnoDB AUTO_INCREMENT=46 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `kdd_doc_documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `content` longtext NOT NULL,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_at_user` varchar(120) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `creator` int(11) DEFAULT NULL,
  `authority_id` int(11) NOT NULL DEFAULT 1,
  `view_type` enum('document','spreadsheet','both') DEFAULT 'document' COMMENT 'Type of view: document only, spreadsheet only, or both switchable',
  `spreadsheet_data` longtext DEFAULT NULL COMMENT 'JSON data for spreadsheet content (Univer format)',
  `default_view` enum('document','spreadsheet') DEFAULT 'document' COMMENT 'Default view to show when opening (only relevant when view_type is both)',
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`),
  KEY `fk_doc_documents_authority` (`authority_id`),
  KEY `idx_view_type` (`view_type`)
) ENGINE=InnoDB AUTO_INCREMENT=165 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `kdd_employee` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(256) NOT NULL,
  `phonenumber` varchar(50) DEFAULT NULL,
  `servicenumber` varchar(50) NOT NULL DEFAULT '',
  `entrydate` date NOT NULL,
  `leavedate` datetime DEFAULT NULL,
  `sidejob` varchar(256) DEFAULT NULL,
  `rank_id` int(11) DEFAULT NULL,
  `personalid` varchar(50) DEFAULT NULL,
  `mail` varchar(100) DEFAULT NULL,
  `bankaccount` varchar(50) DEFAULT NULL,
  `is_terminated` tinyint(1) NOT NULL DEFAULT 0,
  `birthdate` date DEFAULT NULL,
  `notes` text NOT NULL,
  `linked_employee` int(11) DEFAULT 0,
  `dispatch_id` int(11) DEFAULT NULL,
  `is_absent` tinyint(1) NOT NULL DEFAULT 0,
  `authority_id` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `fk_employee_rank` (`rank_id`),
  KEY `fk_dispatch_id` (`dispatch_id`),
  KEY `fk_employee_authority` (`authority_id`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `kdd_employee_company` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `sort_order` int(11) DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `authority_id` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `fk_employee_company_authority` (`authority_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `kdd_employee_company_rel` (
  `employee_id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `authority_id` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`employee_id`,`company_id`),
  KEY `company_id` (`company_id`),
  KEY `fk_employee_company_rel_authority` (`authority_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `kdd_employee_department` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `sort_order` int(11) DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `authority_id` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `fk_employee_department_authority` (`authority_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `kdd_employee_department_rel` (
  `employee_id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `authority_id` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`employee_id`,`department_id`),
  KEY `department_id` (`department_id`),
  KEY `fk_employee_department_rel_authority` (`authority_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `kdd_employee_license` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL,
  `sort_order` int(11) DEFAULT NULL,
  `authority_id` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `fk_employee_license_authority` (`authority_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `kdd_employee_license_rel` (
  `employee_id` int(11) NOT NULL,
  `license_id` int(11) NOT NULL,
  `authority_id` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`employee_id`,`license_id`),
  KEY `license_id` (`license_id`),
  KEY `fk_employee_license_rel_authority` (`authority_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `kdd_employee_promotion` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `old_rank` int(11) DEFAULT NULL,
  `old_rank_name` varchar(255) DEFAULT NULL,
  `new_rank` int(11) DEFAULT NULL,
  `new_rank_name` varchar(255) DEFAULT NULL,
  `employee` int(11) DEFAULT NULL,
  `employee_name` varchar(255) DEFAULT NULL,
  `generated_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `authority_id` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `old_rank` (`old_rank`),
  KEY `new_rank` (`new_rank`),
  KEY `employee` (`employee`),
  KEY `fk_employee_promotion_authority` (`authority_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `kdd_employee_rank` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(255) DEFAULT NULL,
  `rankImage` varchar(255) DEFAULT NULL,
  `sort_order` int(11) DEFAULT NULL,
  `department` varchar(50) DEFAULT NULL COMMENT 'Firefighter, Administration',
  `jobrole_id` int(11) DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `authority_id` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `jobrole_id` (`jobrole_id`),
  KEY `fk_employee_rank_authority` (`authority_id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `kdd_employee_training` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `catId` int(11) NOT NULL DEFAULT 0,
  `name` varchar(255) NOT NULL,
  `sort_order` int(11) DEFAULT NULL,
  `is_deleted` tinyint(4) DEFAULT 0,
  `authority_id` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `catId` (`catId`),
  KEY `fk_employee_training_authority` (`authority_id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `kdd_employee_training_cat` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(250) DEFAULT NULL,
  `short` varchar(250) DEFAULT NULL,
  `sort_order` int(11) DEFAULT NULL,
  `is_deleted` tinyint(4) DEFAULT 0,
  `color` text DEFAULT 'ff0033',
  `authority_id` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `fk_employee_training_cat_authority` (`authority_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
CREATE TABLE `kdd_employee_training_rel` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `training_id` int(11) NOT NULL,
  `instructor` varchar(255) NOT NULL,
  `date` date NOT NULL,
  `authority_id` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`employee_id`,`training_id`,`id`) USING BTREE,
  KEY `training_id` (`training_id`) USING BTREE,
  KEY `id` (`id`),
  KEY `fk_employee_training_rel_authority` (`authority_id`)
) ENGINE=InnoDB AUTO_INCREMENT=57 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `kdd_employee_vacation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reason` varchar(255) DEFAULT NULL,
  `start` date NOT NULL,
  `end` date NOT NULL,
  `employee` int(11) DEFAULT NULL,
  `reported` tinyint(1) DEFAULT 0,
  `other` varchar(255) DEFAULT '',
  `is_deleted` tinyint(4) DEFAULT 0,
  `authority_id` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `employee` (`employee`) USING BTREE,
  KEY `fk_employee_vacation_authority` (`authority_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `kdd_export_presets` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL COMMENT 'Reference to kdd_users.id (NULL for global presets)',
  `authority_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `is_global` tinyint(1) NOT NULL DEFAULT 0,
  `columns` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`columns`)),
  `options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`options`)),
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_fetch_presets` (`authority_id`,`is_deleted`,`is_global`,`user_id`),
  KEY `idx_global_presets` (`authority_id`,`is_global`,`is_deleted`),
  CONSTRAINT `kdd_export_presets_ibfk_1` FOREIGN KEY (`authority_id`) REFERENCES `kdd_authorities` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `kdd_files` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `path` varchar(255) NOT NULL,
  `folder_id` int(10) unsigned DEFAULT NULL,
  `uploaded` datetime DEFAULT current_timestamp(),
  `size` bigint(20) unsigned NOT NULL,
  `is_deleted` tinyint(1) DEFAULT 0,
  `extension` varchar(5) DEFAULT NULL,
  `authority_id` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `folder_id` (`folder_id`),
  KEY `fk_files_authority` (`authority_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `kdd_files_folder` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `parent_id` int(10) unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `is_deleted` tinyint(1) DEFAULT 0,
  `authority_id` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `parent_id` (`parent_id`),
  KEY `fk_files_folder_authority` (`authority_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `kdd_fireprotection_files` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `link` varchar(255) NOT NULL,
  `added` timestamp NOT NULL DEFAULT current_timestamp(),
  `authority_id` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `fk_fireprotection_files_authority` (`authority_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
CREATE TABLE `kdd_global_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `key_name` varchar(255) NOT NULL,
  `value` text DEFAULT NULL,
  `authority_id` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  UNIQUE KEY `key_name` (`key_name`),
  KEY `fk_global_settings_authority` (`authority_id`)
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `kdd_invalid_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `token` text NOT NULL,
  `expiry` int(11) NOT NULL,
  `authority_id` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `fk_invalid_tokens_authority` (`authority_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `kdd_invoice_entries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `invoice_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `quantity` int(11) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_deleted` tinyint(1) DEFAULT 0,
  `authority_id` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `invoice_id` (`invoice_id`),
  KEY `fk_invoice_entries_authority` (`authority_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
CREATE TABLE `kdd_invoice_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `item_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `quantity` int(11) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_deleted` tinyint(1) DEFAULT 0,
  `authority_id` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `fk_invoice_items_authority` (`authority_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
CREATE TABLE `kdd_invoices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `customer` varchar(255) NOT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `account` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_delivered` tinyint(1) DEFAULT 0,
  `is_sent` tinyint(1) DEFAULT 0,
  `is_paid` tinyint(1) DEFAULT 0,
  `discount` decimal(10,2) DEFAULT 0.00,
  `attachments` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`attachments`)),
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_deleted` tinyint(1) DEFAULT 0,
  `is_sent_date` datetime DEFAULT NULL,
  `is_paid_date` datetime DEFAULT NULL,
  `is_delivered_date` datetime DEFAULT NULL,
  `linked_person` int(11) DEFAULT NULL,
  `linked_company` int(11) DEFAULT NULL,
  `outgoing` tinyint(1) DEFAULT 0,
  `authority_id` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `fk_invoices_authority` (`authority_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
CREATE TABLE `kdd_jobroles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `authority_id` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `fk_jobroles_authority` (`authority_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `kdd_jwt_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `authority_id` int(11) NOT NULL,
  `token_hash` varchar(64) NOT NULL COMMENT 'SHA256 hash of the JWT token',
  `expires_at` datetime NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `last_used_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `device_info` varchar(255) DEFAULT NULL COMMENT 'User-Agent string',
  `ip_address` varchar(45) DEFAULT NULL COMMENT 'IPv4 or IPv6 address',
  `is_revoked` tinyint(1) DEFAULT 0 COMMENT 'Manually revoked by user or admin',
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`,`authority_id`),
  KEY `idx_token_hash` (`token_hash`),
  KEY `idx_expires` (`expires_at`),
  KEY `idx_revoked` (`is_revoked`),
  KEY `fk_jwt_tokens_authority` (`authority_id`),
  CONSTRAINT `fk_jwt_tokens_authority` FOREIGN KEY (`authority_id`) REFERENCES `kdd_authorities` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_jwt_tokens_user` FOREIGN KEY (`user_id`) REFERENCES `kdd_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13211 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `kdd_login_history` AS SELECT
 1 AS `id`,
  1 AS `user_id`,
  1 AS `username`,
  1 AS `email`,
  1 AS `device_name`,
  1 AS `device_type`,
  1 AS `ip_address`,
  1 AS `login_time`,
  1 AS `last_activity`,
  1 AS `expires_at`,
  1 AS `is_active`,
  1 AS `status` */;
SET character_set_client = @saved_cs_client;
CREATE TABLE `kdd_mail_access_rights` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `mail_id` int(11) NOT NULL,
  `authority_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `role_id` int(11) DEFAULT NULL,
  `can_read` tinyint(1) DEFAULT 1,
  `can_reply` tinyint(1) DEFAULT 0,
  `can_forward` tinyint(1) DEFAULT 0,
  `can_delete` tinyint(1) DEFAULT 0,
  `granted_by` int(11) NOT NULL,
  `granted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `revoked_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_mail_id` (`mail_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_role_id` (`role_id`),
  KEY `idx_authority_id` (`authority_id`),
  KEY `idx_active` (`is_active`),
  KEY `idx_mail_user_active` (`mail_id`,`user_id`,`is_active`),
  KEY `idx_mail_role_active` (`mail_id`,`role_id`,`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `kdd_mail_accounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `display_name` varchar(255) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `current_user_id` int(11) DEFAULT NULL,
  `authority_id` int(11) NOT NULL,
  `account_type` enum('personal','company','faction') NOT NULL DEFAULT 'personal',
  `is_active` tinyint(1) DEFAULT 1,
  `is_locked` tinyint(1) DEFAULT 0,
  `is_searchable` tinyint(1) DEFAULT 1,
  `storage_used` bigint(20) DEFAULT 0,
  `storage_limit` bigint(20) DEFAULT 1073741824,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `failed_login_attempts` int(11) DEFAULT 0,
  `locked_until` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_email` (`email`),
  KEY `idx_user` (`current_user_id`),
  KEY `idx_authority` (`authority_id`),
  KEY `idx_searchable` (`is_searchable`),
  KEY `idx_active` (`is_active`),
  KEY `idx_type` (`account_type`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `kdd_mail_attachments` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `mail_id` bigint(20) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `original_filename` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` bigint(20) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `is_safe` tinyint(1) DEFAULT 1,
  `scan_status` enum('pending','clean','infected') DEFAULT 'pending',
  `uploaded_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_mail` (`mail_id`),
  KEY `idx_scan_status` (`scan_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `kdd_mail_domains` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `domain` varchar(191) NOT NULL,
  `domain_type` enum('faction','default') NOT NULL,
  `authority_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `verified` tinyint(1) DEFAULT 1,
  `max_accounts` int(11) DEFAULT 1000,
  `display_name` varchar(255) DEFAULT NULL,
  `logo_url` varchar(255) DEFAULT NULL,
  `total_accounts` int(11) DEFAULT 0,
  `total_mails_sent` bigint(20) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_domain` (`domain`),
  KEY `idx_authority` (`authority_id`),
  KEY `idx_type` (`domain_type`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=61 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `kdd_mail_folders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `authority_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `color` varchar(7) DEFAULT '#3B82F6',
  `icon` varchar(50) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_system` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_authority` (`authority_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `kdd_mail_labels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `authority_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `color` varchar(7) DEFAULT '#3B82F6',
  `icon` varchar(50) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_label` (`user_id`,`name`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `kdd_mail_recipients` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `mail_id` bigint(20) NOT NULL,
  `recipient_address` varchar(255) NOT NULL,
  `recipient_user_id` int(11) DEFAULT NULL,
  `recipient_type` enum('to','cc','bcc') NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `read_at` timestamp NULL DEFAULT NULL,
  `is_starred` tinyint(1) DEFAULT 0,
  `is_important` tinyint(1) DEFAULT 0,
  `is_deleted` tinyint(1) DEFAULT 0,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `folder_id` int(11) DEFAULT NULL,
  `label_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`label_ids`)),
  `assigned_to_user_id` int(11) DEFAULT NULL,
  `assigned_at` timestamp NULL DEFAULT NULL,
  `assigned_by_user_id` int(11) DEFAULT NULL,
  `status` enum('new','in_progress','done','archived') DEFAULT 'new',
  `private_note` text DEFAULT NULL,
  `authority_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_mail` (`mail_id`),
  KEY `idx_recipient_user` (`recipient_user_id`),
  KEY `idx_recipient_address` (`recipient_address`),
  KEY `idx_status` (`is_read`,`is_deleted`,`is_starred`),
  KEY `idx_folder` (`folder_id`),
  KEY `idx_assigned` (`assigned_to_user_id`),
  KEY `idx_authority` (`authority_id`),
  KEY `idx_user_status` (`recipient_user_id`,`is_read`,`is_deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `kdd_mail_signatures` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `mail_account_id` int(11) DEFAULT NULL,
  `authority_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `signature_html` text NOT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `use_for_new` tinyint(1) DEFAULT 1,
  `use_for_reply` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_mail_account` (`mail_account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `kdd_mail_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `owner_user_id` int(11) DEFAULT NULL,
  `owner_mailbox_id` int(11) DEFAULT NULL,
  `authority_id` int(11) NOT NULL,
  `template_type` enum('personal','mailbox','system') NOT NULL DEFAULT 'personal',
  `subject_template` varchar(500) DEFAULT NULL,
  `body_template` longtext NOT NULL,
  `available_variables` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'e.g., ["{{name}}", "{{company}}", "{{date}}"]' CHECK (json_valid(`available_variables`)),
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_owner_user` (`owner_user_id`),
  KEY `idx_owner_mailbox` (`owner_mailbox_id`),
  KEY `idx_type` (`template_type`),
  KEY `idx_authority` (`authority_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `kdd_mails` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `message_id` varchar(255) NOT NULL,
  `thread_id` varchar(255) DEFAULT NULL,
  `reply_to_id` bigint(20) DEFAULT NULL,
  `from_address` varchar(255) NOT NULL,
  `from_user_id` int(11) DEFAULT NULL,
  `from_name` varchar(255) DEFAULT NULL,
  `to_addresses` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`to_addresses`)),
  `cc_addresses` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`cc_addresses`)),
  `bcc_addresses` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`bcc_addresses`)),
  `subject` varchar(500) NOT NULL,
  `body_html` longtext NOT NULL,
  `body_text` longtext DEFAULT NULL,
  `has_attachments` tinyint(1) DEFAULT 0,
  `is_draft` tinyint(1) DEFAULT 0,
  `is_sent` tinyint(1) DEFAULT 0,
  `sent_at` timestamp NULL DEFAULT NULL,
  `priority` enum('low','normal','high') DEFAULT 'normal',
  `authority_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `has_restricted_access` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_message_id` (`message_id`),
  KEY `idx_thread` (`thread_id`),
  KEY `idx_reply_to` (`reply_to_id`),
  KEY `idx_from_user` (`from_user_id`),
  KEY `idx_from_address` (`from_address`),
  KEY `idx_authority` (`authority_id`),
  KEY `idx_draft` (`is_draft`),
  KEY `idx_sent` (`is_sent`,`sent_at`),
  KEY `idx_sent_date` (`is_sent`,`sent_at`),
  KEY `idx_has_restricted_access` (`has_restricted_access`),
  FULLTEXT KEY `search_content` (`subject`,`body_text`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `kdd_map` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `ceo` varchar(255) DEFAULT NULL,
  `phonenumber` varchar(255) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `category_id` int(11) NOT NULL,
  `x_coordinate` double NOT NULL,
  `y_coordinate` double NOT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `authority_id` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`),
  KEY `fk_map_authority` (`authority_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1127 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `kdd_map_category` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `icon` varchar(255) NOT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `authority_id` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `fk_map_category_authority` (`authority_id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `kdd_map_icons` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `path` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `authority_id` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_name` (`name`),
  KEY `fk_map_icons_authority` (`authority_id`),
  CONSTRAINT `FK_kdd_map_icons_kdd_authorities` FOREIGN KEY (`authority_id`) REFERENCES `kdd_authorities` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=161 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `kdd_map_shares` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `share_token` varchar(64) NOT NULL COMMENT '64-char hex token from bin2hex(random_bytes(32))',
  `authority_id` int(11) NOT NULL COMMENT 'Authority this share belongs to',
  `map_type` varchar(50) NOT NULL COMMENT 'Map type: global, authority name, defaultmap',
  `show_sidebar` tinyint(1) DEFAULT 0 COMMENT '1 = show category sidebar, 0 = hide',
  `category_ids` text DEFAULT NULL COMMENT 'JSON array of category IDs to show, NULL = all categories',
  `created_by` int(11) NOT NULL COMMENT 'User ID who created the share (from kdd_users)',
  `created_at` datetime NOT NULL,
  `expires_at` datetime DEFAULT NULL COMMENT 'Optional expiration date, NULL = never expires',
  PRIMARY KEY (`id`),
  UNIQUE KEY `share_token` (`share_token`),
  KEY `idx_share_token` (`share_token`),
  KEY `idx_authority` (`authority_id`),
  KEY `idx_created_by` (`created_by`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_expires_at` (`expires_at`),
  CONSTRAINT `kdd_map_shares_ibfk_1` FOREIGN KEY (`authority_id`) REFERENCES `kdd_authorities` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `kdd_message_folders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `group_id` int(11) DEFAULT NULL,
  `authority_id` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `group_id` (`group_id`),
  KEY `fk_message_folders_authority` (`authority_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
CREATE TABLE `kdd_message_group_members` (
  `group_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `authority_id` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`group_id`,`user_id`),
  KEY `user_id` (`user_id`),
  KEY `fk_message_group_members_authority` (`authority_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
CREATE TABLE `kdd_message_groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_deleted` tinyint(4) DEFAULT 0,
  `authority_id` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `fk_message_groups_authority` (`authority_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
CREATE TABLE `kdd_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `body` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `sender_id` int(11) DEFAULT NULL,
  `sender_type` varchar(10) DEFAULT NULL,
  `recipient_id` int(11) DEFAULT NULL,
  `recipient_type` varchar(10) DEFAULT NULL,
  `group_id` int(11) DEFAULT NULL,
  `recipient_folder_id` int(11) DEFAULT NULL,
  `sender_folder_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_anonymous` tinyint(1) DEFAULT 0,
  `is_read` tinyint(1) DEFAULT 0,
  `read_at` timestamp NULL DEFAULT NULL,
  `recipient_note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `sender_note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `deleted_by_sender` tinyint(1) DEFAULT 0,
  `deleted_by_recipient` tinyint(1) DEFAULT 0,
  `pinned` tinyint(1) DEFAULT 0,
  `authority_id` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `fk_messages_authority` (`authority_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
CREATE TABLE `kdd_notes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `content` text DEFAULT NULL,
  `color` varchar(20) DEFAULT '#fbbf24',
  `position` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`position`)),
  `tags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tags`)),
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `authority_id` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `fk_notes_authority` (`authority_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
CREATE TABLE `kdd_permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL COMMENT 'Full permission name (e.g., READ_BLACKBOARD_AREA)',
  `module` varchar(64) NOT NULL COMMENT 'Module identifier (e.g., blackboard, employee)',
  `sub_module` varchar(64) DEFAULT NULL COMMENT 'Sub-module for hierarchical permissions (e.g., area, admin)',
  `action` varchar(32) NOT NULL COMMENT 'Action type (read, write, delete, admin, view)',
  `bitmask_value` int(11) DEFAULT NULL COMMENT 'Bitmask value: 1=read, 2=write, 4=delete, 8=admin, 16=view',
  `action_display` varchar(64) DEFAULT NULL COMMENT 'Localized action name (e.g., Lesen, Schreiben)',
  `module_display` varchar(128) DEFAULT NULL COMMENT 'Localized module name (e.g., Schwarzes Brett Bereiche)',
  `display_group` varchar(100) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `authority_id` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_name` (`name`,`authority_id`),
  UNIQUE KEY `unique_permission` (`module`,`sub_module`,`action`,`authority_id`),
  KEY `idx_module` (`module`,`authority_id`),
  KEY `idx_action` (`action`),
  KEY `idx_bitmask` (`module`,`sub_module`,`authority_id`)
) ENGINE=InnoDB AUTO_INCREMENT=299 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Restructured permissions system with module-based organization and bitmask support';
CREATE TABLE `kdd_permissions_backup` (
  `id` int(11) NOT NULL DEFAULT 0,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_alias` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `site` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `authority_id` int(11) NOT NULL DEFAULT 1,
  `authority_code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `kdd_permissions_by_module` AS SELECT
 1 AS `module`,
  1 AS `sub_module`,
  1 AS `authority_id`,
  1 AS `actions`,
  1 AS `total_bitmask`,
  1 AS `permission_count` */;
SET character_set_client = @saved_cs_client;
CREATE TABLE `kdd_permissions_old` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `name_alias` varchar(255) NOT NULL,
  `site` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `authority_id` int(11) NOT NULL DEFAULT 1,
  `authority_code` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `fk_permissions_authority` (`authority_id`),
  KEY `fk_permissions_authority_code` (`authority_code`)
) ENGINE=InnoDB AUTO_INCREMENT=285 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `kdd_permissions_readable` AS SELECT
 1 AS `id`,
  1 AS `name`,
  1 AS `display_name`,
  1 AS `module`,
  1 AS `sub_module`,
  1 AS `action`,
  1 AS `bitmask_value`,
  1 AS `authority_id` */;
SET character_set_client = @saved_cs_client;
CREATE TABLE `kdd_person_file` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `firstname` varchar(100) NOT NULL,
  `lastname` varchar(100) NOT NULL,
  `gender` varchar(100) NOT NULL,
  `title` varchar(100) NOT NULL,
  `birthplace` varchar(100) DEFAULT NULL,
  `birthday` date DEFAULT NULL,
  `phonenumber` varchar(20) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `idcard` varchar(50) DEFAULT NULL,
  `bankaccount` varchar(50) DEFAULT NULL,
  `mail` varchar(100) DEFAULT NULL,
  `entry` datetime DEFAULT current_timestamp(),
  `licenses` varchar(255) DEFAULT NULL,
  `wanted` tinyint(1) DEFAULT 0,
  `text` text DEFAULT NULL,
  `is_deleted` tinyint(1) DEFAULT 0,
  `authority_id` int(11) NOT NULL DEFAULT 1,
  `custom_fields` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`custom_fields`)),
  PRIMARY KEY (`id`),
  KEY `fk_person_file_authority` (`authority_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
CREATE TABLE `kdd_person_rel` (
  `id_person` int(11) NOT NULL,
  `id_vehicle_file` int(11) NOT NULL,
  `type` varchar(50) NOT NULL DEFAULT 'driver',
  `authority_id` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_person`,`id_vehicle_file`,`type`) USING BTREE,
  KEY `fk_fireguard_rel_vehicle` (`id_vehicle_file`),
  KEY `fk_person_rel_authority` (`authority_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
CREATE TABLE `kdd_refresh_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `token` varchar(255) NOT NULL,
  `user_id` int(11) NOT NULL,
  `authority_id` int(11) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `last_used_at` timestamp NULL DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `is_revoked` tinyint(1) DEFAULT 0,
  `revoked_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `idx_token` (`token`),
  KEY `idx_user` (`user_id`,`authority_id`),
  KEY `idx_expires` (`expires_at`),
  KEY `idx_revoked` (`is_revoked`),
  KEY `authority_id` (`authority_id`),
  KEY `idx_cleanup` (`expires_at`,`is_revoked`),
  CONSTRAINT `kdd_refresh_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `kdd_users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `kdd_refresh_tokens_ibfk_2` FOREIGN KEY (`authority_id`) REFERENCES `kdd_authorities` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13211 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Stores refresh tokens for JWT authentication system';
CREATE TABLE `kdd_report_additionals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `units` int(11) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_deleted` tinyint(1) DEFAULT 0,
  `authority_id` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `fk_report_additionals_authority` (`authority_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
CREATE TABLE `kdd_report_additionals_rel` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `additionals_id` int(11) NOT NULL,
  `report_id` int(11) NOT NULL,
  `amount` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `units` int(11) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `authority_id` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`additionals_id`,`report_id`,`id`) USING BTREE,
  KEY `fk__fireguard_kdd_fireguard_report_additionals` (`report_id`),
  KEY `id` (`id`),
  KEY `fk_report_additionals_rel_authority` (`authority_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
CREATE TABLE `kdd_report_category` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL DEFAULT '0',
  `name` varchar(255) NOT NULL,
  `template` text DEFAULT NULL,
  `authority_id` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `fk_report_category_authority` (`authority_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
CREATE TABLE `kdd_report_category_groups` (
  `category_id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`category_id`,`group_id`),
  KEY `group_id` (`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `kdd_report_category_user_roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `report_category_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `kdd_report_category_user_roles_unique` (`report_category_id`,`role_id`),
  KEY `fk_role` (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `kdd_report_code` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `is_deleted` tinyint(1) DEFAULT 0,
  `authority_id` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `fk_report_code_authority` (`authority_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
CREATE TABLE `kdd_report_company_rel` (
  `id_report` int(11) NOT NULL,
  `id_company` int(11) NOT NULL,
  `authority_id` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_report`,`id_company`),
  KEY `id_company` (`id_company`),
  KEY `fk_report_company_rel_authority` (`authority_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
CREATE TABLE `kdd_report_custom_fields` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `report_id` int(10) unsigned NOT NULL,
  `field_name` varchar(50) NOT NULL,
  `field_value` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_report_custom_fields_report` (`report_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `kdd_report_fields` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `authority_id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL COMMENT 'NULL bedeutet allgemeines Feld für alle Berichte',
  `field_name` varchar(255) NOT NULL,
  `field_label` varchar(255) NOT NULL,
  `field_type` enum('text','number','date','boolean','select','textarea','multiselect') NOT NULL,
  `options` text DEFAULT NULL COMMENT 'JSON-Array für select/multiselect Optionen',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_required` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_authority_category` (`authority_id`,`category_id`),
  KEY `idx_report_fields_authority` (`authority_id`),
  KEY `idx_report_fields_category` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `kdd_report_group_permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `group_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `can_read` tinyint(1) DEFAULT 0,
  `can_write` tinyint(1) DEFAULT 0,
  `can_delete` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_group_role` (`group_id`,`role_id`),
  KEY `role_id` (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `kdd_report_groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `authority_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_group_name_per_authority` (`name`,`authority_id`),
  KEY `authority_id` (`authority_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `kdd_report_missing_rel` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) DEFAULT NULL,
  `report_id` int(11) DEFAULT NULL,
  `authority_id` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `report_id` (`report_id`),
  KEY `employee_id` (`employee_id`),
  KEY `fk_report_missing_rel_authority` (`authority_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
CREATE TABLE `kdd_report_person_rel` (
  `id_report` int(11) NOT NULL,
  `id_person` int(11) NOT NULL,
  `authority_id` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_report`,`id_person`),
  KEY `fk__fireguard__kdd_fireguard_person_file` (`id_person`),
  KEY `fk_report_person_rel_authority` (`authority_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
CREATE TABLE `kdd_report_role_access` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `report_id` int(11) NOT NULL,
  `authority_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_access` (`report_id`,`authority_id`,`role_id`),
  KEY `idx_report_role` (`report_id`),
  KEY `idx_authority_role` (`authority_id`),
  KEY `idx_role` (`role_id`),
  KEY `fk_access_role_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `kdd_report_sharing` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `report_id` int(11) NOT NULL,
  `source_authority_id` int(11) NOT NULL,
  `target_authority_id` int(11) NOT NULL,
  `shared_by_user_id` int(11) NOT NULL,
  `access_level` enum('read','edit') NOT NULL DEFAULT 'read',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_sharing` (`report_id`,`source_authority_id`,`target_authority_id`),
  KEY `idx_report` (`report_id`),
  KEY `idx_source` (`source_authority_id`),
  KEY `idx_target` (`target_authority_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `kdd_report_status` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_deleted` tinyint(1) DEFAULT 0,
  `authority_id` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `fk_report_status_authority` (`authority_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
CREATE TABLE `kdd_report_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) DEFAULT NULL,
  `template` text DEFAULT NULL,
  `authority_id` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `fk_report_templates_authority` (`authority_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
CREATE TABLE `kdd_reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` text DEFAULT NULL,
  `text` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `creator` int(11) DEFAULT NULL,
  `approved` tinyint(1) DEFAULT 0,
  `is_deleted` tinyint(1) DEFAULT 0,
  `pinned` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `category_id` int(11) DEFAULT NULL,
  `report_date` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `report_code_id` int(11) NOT NULL,
  `location` varchar(255) NOT NULL,
  `report_status_id` int(11) DEFAULT NULL,
  `authority_id` int(11) NOT NULL DEFAULT 1,
  `custom_fields` text DEFAULT NULL COMMENT 'JSON mit benutzerdefinierten Feldern',
  `visibility_type` enum('public','private','specific_roles') NOT NULL DEFAULT 'public' COMMENT 'Sichtbarkeit innerhalb der Authority',
  `user_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `creator` (`creator`),
  KEY `report_code_id` (`report_code_id`),
  KEY `department_id` (`category_id`) USING BTREE,
  KEY `report_status_id` (`report_status_id`),
  KEY `fk_reports_authority` (`authority_id`),
  KEY `kdd_reports_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
CREATE TABLE `kdd_role_permissions` (
  `role_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL,
  `authority_id` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`role_id`,`permission_id`),
  KEY `permission_id` (`permission_id`),
  KEY `fk_role_permissions_authority` (`authority_id`),
  CONSTRAINT `kdd_role_permissions_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `kdd_permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `kdd_roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `sort_order` int(11) DEFAULT 25,
  `power` int(11) DEFAULT NULL,
  `is_deleted` tinyint(1) DEFAULT 0,
  `authority_id` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_role_per_authority` (`name`,`authority_id`),
  KEY `fk_roles_authority` (`authority_id`)
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `kdd_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `authority_id` int(11) NOT NULL,
  `token_hash` varchar(255) NOT NULL COMMENT 'SHA256 hash of JWT token',
  `jti` varchar(255) NOT NULL COMMENT 'JWT ID (unique identifier)',
  `device_name` varchar(255) DEFAULT NULL COMMENT 'e.g., Chrome on Windows',
  `device_type` varchar(50) DEFAULT NULL COMMENT 'desktop, mobile, tablet',
  `ip_address` varchar(45) DEFAULT NULL COMMENT 'IPv4 or IPv6',
  `user_agent` text DEFAULT NULL,
  `last_activity` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` datetime DEFAULT current_timestamp(),
  `expires_at` datetime NOT NULL,
  `is_active` tinyint(1) DEFAULT 1 COMMENT '0 = logged out, 1 = active',
  PRIMARY KEY (`id`),
  UNIQUE KEY `token_hash` (`token_hash`),
  UNIQUE KEY `jti` (`jti`),
  KEY `idx_user` (`user_id`,`is_active`),
  KEY `idx_token_hash` (`token_hash`),
  KEY `idx_jti` (`jti`),
  KEY `idx_authority` (`authority_id`),
  KEY `idx_active_expires` (`is_active`,`expires_at`),
  KEY `idx_last_activity` (`last_activity`),
  CONSTRAINT `kdd_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `kdd_users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `kdd_sessions_ibfk_2` FOREIGN KEY (`authority_id`) REFERENCES `kdd_authorities` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=171 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `kdd_ta_todo_assigned_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `todo_id` int(11) DEFAULT NULL,
  `authority_id` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `todo_id` (`todo_id`),
  KEY `fk_ta_todo_assigned_users_authority` (`authority_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `kdd_ta_todo_checkboxes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `todo_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `completed` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `authority_id` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `todo_id` (`todo_id`),
  KEY `fk_ta_todo_checkboxes_authority` (`authority_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `kdd_ta_todolist_permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `list_id` int(11) NOT NULL,
  `can_write` tinyint(1) NOT NULL,
  `authority_id` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `list_id` (`list_id`),
  KEY `fk_ta_todolist_permissions_authority` (`authority_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `kdd_ta_todolists` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `parent_list_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `is_deleted` tinyint(1) DEFAULT 0,
  `is_global` tinyint(1) NOT NULL DEFAULT 0,
  `global_permissions` varchar(50) DEFAULT NULL,
  `authority_id` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `parent_list_id` (`parent_list_id`),
  KEY `fk_ta_todolists_authority` (`authority_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `kdd_ta_todos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `list_id` int(11) NOT NULL,
  `parent_todo_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `short_description` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `importance` enum('low','medium','high') NOT NULL,
  `due_date` date DEFAULT NULL,
  `completed` tinyint(1) DEFAULT 0,
  `sort` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `authority_id` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `list_id` (`list_id`),
  KEY `parent_todo_id` (`parent_todo_id`),
  KEY `fk_ta_todos_authority` (`authority_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `kdd_template_fields` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `template_id` int(11) DEFAULT NULL,
  `field_name` varchar(255) NOT NULL,
  `field_value` text DEFAULT NULL,
  `field_description` text DEFAULT NULL,
  `field_label` varchar(255) NOT NULL,
  `field_type` varchar(255) NOT NULL,
  `is_multiple` tinyint(1) NOT NULL DEFAULT 0,
  `is_required` tinyint(1) NOT NULL DEFAULT 0,
  `options` text DEFAULT NULL,
  `sort_by` int(11) DEFAULT NULL,
  `authority_id` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `template_id` (`template_id`),
  KEY `fk_template_fields_authority` (`authority_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `kdd_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `icon` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category_id` int(11) NOT NULL,
  `text` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `sort_order` int(11) DEFAULT NULL,
  `recipient` varchar(255) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `is_deleted` tinyint(1) DEFAULT 0,
  `authority_id` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`),
  KEY `fk_templates_authority` (`authority_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `kdd_templates_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `icon` varchar(255) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_deleted` tinyint(1) DEFAULT 0,
  `authority_id` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `fk_templates_categories_authority` (`authority_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `kdd_test_answers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `question_id` int(11) DEFAULT NULL,
  `answer` text DEFAULT NULL,
  `result` varchar(255) DEFAULT NULL,
  `deleted` tinyint(1) DEFAULT 0,
  `authority_id` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `question_id` (`question_id`),
  KEY `fk_test_answers_authority` (`authority_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
CREATE TABLE `kdd_test_questions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `question` text DEFAULT NULL,
  `isKO` tinyint(4) DEFAULT 0,
  `deleted` tinyint(1) DEFAULT 0,
  `authority_id` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `fk_test_questions_authority` (`authority_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
CREATE TABLE `kdd_user_mail_links` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `mail_account_id` int(11) NOT NULL,
  `authority_id` int(11) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `linked_at` timestamp NULL DEFAULT current_timestamp(),
  `unlinked_at` timestamp NULL DEFAULT NULL,
  `unlinked_reason` enum('user_request','job_change','termination','admin') DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_mail` (`mail_account_id`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `kdd_user_roles` (
  `user_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `authority_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`user_id`,`role_id`),
  KEY `role_id` (`role_id`),
  KEY `idx_user_roles_authority_id` (`authority_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `kdd_user_settings` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL,
  `authority_id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` longtext NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_authority_key` (`user_id`,`authority_id`,`setting_key`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_authority_id` (`authority_id`)
) ENGINE=InnoDB AUTO_INCREMENT=309 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `kdd_user_shortcuts` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT 'Reference to kdd_users.id',
  `authority_id` int(11) NOT NULL COMMENT 'Reference to kdd_authorities.id (Multi-Tenant Isolation)',
  `type` varchar(50) NOT NULL COMMENT 'Resource type: document, person_file, company_file, etc.',
  `resource_id` int(11) unsigned NOT NULL COMMENT 'ID of the resource (e.g., document_id, person_id)',
  `title` varchar(255) NOT NULL COMMENT 'Display title for the shortcut',
  `subtitle` varchar(255) DEFAULT NULL COMMENT 'Optional subtitle/description',
  `icon` varchar(100) DEFAULT NULL COMMENT 'Material Design Icon name (e.g., mdi-file-document)',
  `color` varchar(50) DEFAULT NULL COMMENT 'Vuetify color name (e.g., primary, red, blue)',
  `sort_order` int(11) DEFAULT 0 COMMENT 'Manual sorting order (0 = default)',
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Additional type-specific data (e.g., category info for documents)' CHECK (json_valid(`metadata`)),
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Soft delete flag (0 = active, 1 = deleted)',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Creation timestamp',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'Last update\r\n  timestamp',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_shortcut` (`user_id`,`authority_id`,`type`,`resource_id`,`is_deleted`),
  KEY `idx_fetch_shortcuts` (`user_id`,`authority_id`,`is_deleted`,`type`,`sort_order`),
  KEY `authority_id` (`authority_id`),
  CONSTRAINT `kdd_user_shortcuts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `kdd_users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `kdd_user_shortcuts_ibfk_2` FOREIGN KEY (`authority_id`) REFERENCES `kdd_authorities` (`id`) ON DELETE CASCADE,
  CONSTRAINT `CONSTRAINT_1` CHECK (`type` in ('document','person_file','company_file','apartment_file','document_category','document_area','report','employee','vehicle','training','route'))
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='User-specific quick access shortcuts with multi-tenant support';
CREATE TABLE `kdd_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `mail_header` text DEFAULT NULL,
  `mail_footer` text DEFAULT NULL,
  `mail_header_neutral` text DEFAULT NULL,
  `mail_footer_neutral` text DEFAULT NULL,
  `signature` text DEFAULT NULL,
  `last_login` timestamp NULL DEFAULT NULL,
  `banned` tinyint(1) NOT NULL DEFAULT 0,
  `documentView` char(50) NOT NULL DEFAULT 'tiles',
  `linked_employee` int(11) DEFAULT 0,
  `last_interact` timestamp NULL DEFAULT NULL,
  `authority` varchar(50) DEFAULT NULL,
  `authority_id` int(11) DEFAULT NULL,
  `linked_mail_account_id` int(11) DEFAULT NULL COMMENT 'Verknüpfte Mail-Adresse',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`,`authority`) USING BTREE,
  UNIQUE KEY `email` (`email`,`authority`) USING BTREE,
  KEY `idx_users_authority_id` (`authority_id`),
  KEY `idx_linked_mail` (`linked_mail_account_id`)
) ENGINE=InnoDB AUTO_INCREMENT=63 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `kdd_vehicle_file` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `owner` int(11) DEFAULT NULL,
  `driver` int(11) DEFAULT NULL,
  `brand` varchar(100) DEFAULT NULL,
  `model` varchar(100) DEFAULT NULL,
  `numberplate` varchar(20) DEFAULT NULL,
  `color` varchar(50) DEFAULT NULL,
  `stolen` tinyint(1) DEFAULT 0,
  `wanted` tinyint(1) DEFAULT 0,
  `registered` date DEFAULT NULL,
  `text` text DEFAULT NULL,
  `is_deleted` tinyint(1) DEFAULT 0,
  `authority_id` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `fk_fireguard_vehicle_owner` (`owner`),
  KEY `fk_fireguard_vehicle_driver` (`driver`),
  KEY `fk_vehicle_file_authority` (`authority_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
CREATE TABLE `kdd_vehicles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `title` varchar(255) NOT NULL,
  `numberplate` varchar(50) DEFAULT NULL,
  `rank` varchar(50) DEFAULT NULL,
  `damage` tinyint(1) NOT NULL DEFAULT 0,
  `damage_officer` varchar(255) DEFAULT NULL,
  `damage_description` varchar(255) DEFAULT NULL,
  `damage_time` timestamp NULL DEFAULT NULL,
  `dispatch_id` int(11) DEFAULT NULL,
  `sort_order` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `authority_id` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `dispatch_id` (`dispatch_id`),
  KEY `fk_vehicles_authority` (`authority_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
CREATE TABLE `kdd_weather` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `day` varchar(50) DEFAULT NULL,
  `min_temp` decimal(5,1) DEFAULT NULL,
  `max_temp` decimal(5,1) DEFAULT NULL,
  `icon` varchar(255) DEFAULT NULL,
  `humidity` int(11) DEFAULT NULL,
  `warning` text DEFAULT NULL,
  `wind_speed` decimal(5,2) DEFAULT NULL,
  `authority_id` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `fk_weather_authority` (`authority_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
CREATE TABLE `kdd_website_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `website_id` int(11) NOT NULL COMMENT 'Verknüpfung zur Website-Konfiguration',
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL COMMENT 'URL-freundlicher Pfad',
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `authority_id` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `website_slug` (`website_id`,`slug`),
  KEY `website_id` (`website_id`),
  KEY `fk_website_categories_authority` (`authority_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `kdd_website_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `authority_id` int(11) NOT NULL DEFAULT 1,
  `site_name` varchar(255) NOT NULL,
  `site_slogan` varchar(255) DEFAULT NULL,
  `site_description` text DEFAULT NULL,
  `primary_color` varchar(20) DEFAULT '#3b82f6',
  `secondary_color` varchar(20) DEFAULT '#1e3a8a',
  `background_color` varchar(20) DEFAULT '#ffffff',
  `contact_email` varchar(255) DEFAULT NULL,
  `contact_phone` varchar(50) DEFAULT NULL,
  `footer_text` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `layout_template` enum('default','onepager','landing','portfolio','business') NOT NULL DEFAULT 'default' COMMENT 'Website Template Type',
  `template_settings` text DEFAULT NULL COMMENT 'JSON für template-spezifische Einstellungen',
  `sections_order` text DEFAULT NULL COMMENT 'JSON Array für Section-Reihenfolge bei One-Pager',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `navbar_name` varchar(50) DEFAULT 'Home',
  `show_contact_form` tinyint(1) NOT NULL DEFAULT 0,
  `contact_form_fields` text NOT NULL DEFAULT '[]',
  `social_links` text DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `banner` varchar(255) DEFAULT NULL,
  `background_image` varchar(255) DEFAULT NULL,
  `hero_image` varchar(255) DEFAULT NULL,
  `cta_text` varchar(100) DEFAULT NULL,
  `cta_target_type` enum('page','contact') DEFAULT 'page',
  `cta_target_id` int(11) DEFAULT NULL,
  `maintenance_mode` tinyint(1) NOT NULL DEFAULT 0,
  `maintenance_message` text DEFAULT 'Diese Website befindet sich derzeit im Wartungsmodus und wird in Kürze wieder verfügbar sein.',
  `selected_scheme` varchar(50) DEFAULT 'Blau-Weiß',
  `use_custom_colors` tinyint(1) DEFAULT 0,
  `custom_colors` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`custom_colors`)),
  PRIMARY KEY (`id`),
  UNIQUE KEY `authority_id` (`authority_id`),
  KEY `fk_website_config_authority` (`authority_id`),
  KEY `idx_website_config_id` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `kdd_website_contact` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `website_id` int(11) NOT NULL COMMENT 'Verknüpfung zur Website-Konfiguration',
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `message` text NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `authority_id` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `website_id` (`website_id`),
  KEY `fk_website_contact_authority` (`authority_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `kdd_website_media` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `website_id` int(11) NOT NULL COMMENT 'Verknüpfung zur Website-Konfiguration',
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_type` varchar(100) NOT NULL,
  `file_size` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `alt_text` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `media_type` enum('image','document','video','audio','news_doc','logo','banner','hero_image','background_image','content') NOT NULL DEFAULT 'image' COMMENT 'Typ des Mediums',
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `authority_id` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `website_id` (`website_id`),
  KEY `fk_website_media_authority` (`authority_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `kdd_website_navigation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `website_id` int(11) NOT NULL COMMENT 'Verknüpfung zur Website-Konfiguration',
  `title` varchar(255) NOT NULL COMMENT 'Titel des Navigationspunkts',
  `url` varchar(255) DEFAULT NULL COMMENT 'URL oder Pfad (falls kein page_id)',
  `page_id` int(11) DEFAULT NULL COMMENT 'Verknüpfung zu einer Seite',
  `parent_id` int(11) DEFAULT NULL COMMENT 'Übergeordneter Navigationspunkt für Unterpunkte',
  `sort_order` float NOT NULL DEFAULT 0 COMMENT 'Sortierreihenfolge',
  `target` varchar(10) DEFAULT '_self' COMMENT 'Link-Ziel (_self, _blank, etc.)',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Ist der Punkt aktiv/sichtbar',
  `is_blog` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Ist dieser Punkt eine Blog-Übersicht',
  `blog_categories` text DEFAULT NULL COMMENT 'JSON-Array der anzuzeigenden Blog-Kategorien',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `authority_id` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `website_id` (`website_id`),
  KEY `parent_id` (`parent_id`),
  KEY `page_id` (`page_id`),
  KEY `fk_website_navigation_authority` (`authority_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `kdd_website_news` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `website_id` int(11) NOT NULL COMMENT 'Verknüpfung zur Website-Konfiguration',
  `title` varchar(255) NOT NULL COMMENT 'News Titel',
  `excerpt` text DEFAULT NULL COMMENT 'Kurzzusammenfassung',
  `content` longtext DEFAULT NULL COMMENT 'Vollständiger Inhalt',
  `document_id` int(11) DEFAULT NULL COMMENT 'Verknüpfung zu kdd_website_media (optional)',
  `thumbnail` varchar(255) DEFAULT NULL COMMENT 'Vorschaubild',
  `is_published` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Veröffentlicht Status',
  `published_at` datetime DEFAULT NULL COMMENT 'Veröffentlichungsdatum',
  `expires_at` datetime DEFAULT NULL COMMENT 'Ablaufdatum (optional)',
  `priority` enum('low','normal','high','urgent') NOT NULL DEFAULT 'normal' COMMENT 'Priorität',
  `category` varchar(100) DEFAULT 'announcement' COMMENT 'Kategorie (announcement, update, download, news)',
  `download_count` int(11) NOT NULL DEFAULT 0 COMMENT 'Anzahl der Downloads',
  `view_count` int(11) NOT NULL DEFAULT 0 COMMENT 'Anzahl der Ansichten',
  `sort_order` int(11) NOT NULL DEFAULT 0 COMMENT 'Sortierreihenfolge',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `authority_id` int(11) NOT NULL DEFAULT 1 COMMENT 'Multi-Tenant Authority ID',
  PRIMARY KEY (`id`),
  KEY `website_id` (`website_id`),
  KEY `document_id` (`document_id`),
  KEY `authority_id` (`authority_id`),
  KEY `is_published` (`is_published`,`published_at`),
  KEY `priority` (`priority`),
  KEY `category` (`category`),
  KEY `idx_website_published` (`website_id`,`is_published`,`published_at`),
  CONSTRAINT `fk_news_document` FOREIGN KEY (`document_id`) REFERENCES `kdd_website_media` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_news_website` FOREIGN KEY (`website_id`) REFERENCES `kdd_website_config` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='News, Ankündigungen und Dokumente für die Website';
CREATE TABLE `kdd_website_news_downloads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `news_id` int(11) NOT NULL COMMENT 'Verknüpfung zu kdd_website_news',
  `ip_address` varchar(45) DEFAULT NULL COMMENT 'IP-Adresse des Downloads',
  `user_agent` text DEFAULT NULL COMMENT 'Browser User Agent',
  `downloaded_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Download-Zeitpunkt',
  PRIMARY KEY (`id`),
  KEY `news_id` (`news_id`),
  KEY `downloaded_at` (`downloaded_at`),
  CONSTRAINT `fk_downloads_news` FOREIGN KEY (`news_id`) REFERENCES `kdd_website_news` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Download-Tracking für News-Dokumente';
CREATE TABLE `kdd_website_page_versions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `page_id` int(11) NOT NULL COMMENT 'Reference to parent page',
  `version` int(11) NOT NULL COMMENT 'Version number',
  `authority_id` int(11) NOT NULL COMMENT 'Multi-tenant isolation',
  `title` varchar(255) NOT NULL COMMENT 'Page title at this version',
  `slug` varchar(255) NOT NULL COMMENT 'URL slug at this version',
  `content` longtext DEFAULT NULL COMMENT 'Page content at this version',
  `meta_title` varchar(255) DEFAULT NULL COMMENT 'SEO meta title',
  `meta_description` text DEFAULT NULL COMMENT 'SEO meta description',
  `status` varchar(50) DEFAULT 'draft' COMMENT 'Version status: draft, published, archived',
  `created_at` timestamp NULL DEFAULT current_timestamp() COMMENT 'When this version was created',
  `created_by` int(11) DEFAULT NULL COMMENT 'User ID who created this version',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_page_version` (`page_id`,`version`),
  KEY `authority_id` (`authority_id`),
  KEY `created_by` (`created_by`),
  KEY `idx_page_authority` (`page_id`,`authority_id`),
  KEY `idx_version_status` (`version`,`status`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `kdd_website_page_versions_ibfk_1` FOREIGN KEY (`page_id`) REFERENCES `kdd_website_pages` (`id`) ON DELETE CASCADE,
  CONSTRAINT `kdd_website_page_versions_ibfk_2` FOREIGN KEY (`authority_id`) REFERENCES `kdd_authorities` (`id`) ON DELETE CASCADE,
  CONSTRAINT `kdd_website_page_versions_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `kdd_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Version history for website pages';
CREATE TABLE `kdd_website_pages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `website_id` int(11) NOT NULL COMMENT 'Verknüpfung zur Website-Konfiguration',
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL COMMENT 'URL-freundlicher Pfad',
  `content` longtext DEFAULT NULL,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `is_published` tinyint(1) DEFAULT 0 COMMENT 'DEPRECATED: Use status column instead. Kept for backward compatibility.',
  `status` enum('draft','published','scheduled','archived') DEFAULT 'draft' COMMENT 'Publication status: draft (not visible), published (live), scheduled (auto-publish), archived (hidden)',
  `published_at` datetime DEFAULT NULL COMMENT 'Timestamp when page was published',
  `scheduled_at` datetime DEFAULT NULL COMMENT 'Timestamp for scheduled publication (NULL if not scheduled)',
  `use_blocks` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `authority_id` int(11) NOT NULL DEFAULT 1,
  `version` int(11) DEFAULT 1 COMMENT 'Current working version number',
  `published_version` int(11) DEFAULT NULL COMMENT 'Last published version number (NULL = never published)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `website_slug` (`website_id`,`slug`),
  KEY `website_id` (`website_id`),
  KEY `fk_website_pages_authority` (`authority_id`),
  KEY `idx_version` (`version`),
  KEY `idx_published_version` (`published_version`),
  KEY `idx_pages_status` (`status`),
  KEY `idx_pages_published_at` (`published_at`),
  KEY `idx_pages_scheduled_at` (`scheduled_at`),
  KEY `idx_pages_status_published` (`status`,`published_at`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `kdd_website_post_category_rel` (
  `post_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `authority_id` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`post_id`,`category_id`),
  KEY `category_id` (`category_id`),
  KEY `fk_post_category_rel_authority` (`authority_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `kdd_website_post_versions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `post_id` int(11) NOT NULL COMMENT 'Reference to parent post',
  `version` int(11) NOT NULL COMMENT 'Version number',
  `authority_id` int(11) NOT NULL COMMENT 'Multi-tenant isolation',
  `title` varchar(255) NOT NULL COMMENT 'Post title at this version',
  `slug` varchar(255) NOT NULL COMMENT 'URL slug at this version',
  `content` longtext DEFAULT NULL COMMENT 'Post content at this version',
  `excerpt` text DEFAULT NULL COMMENT 'Post excerpt at this version',
  `featured_image` varchar(500) DEFAULT NULL COMMENT 'Featured image URL at this version',
  `meta_title` varchar(255) DEFAULT NULL COMMENT 'SEO meta title',
  `meta_description` text DEFAULT NULL COMMENT 'SEO meta description',
  `status` varchar(50) DEFAULT 'draft' COMMENT 'Version status: draft, published, archived',
  `created_at` timestamp NULL DEFAULT current_timestamp() COMMENT 'When this version was created',
  `created_by` int(11) DEFAULT NULL COMMENT 'User ID who created this version',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_post_version` (`post_id`,`version`),
  KEY `authority_id` (`authority_id`),
  KEY `created_by` (`created_by`),
  KEY `idx_post_authority` (`post_id`,`authority_id`),
  KEY `idx_version_status` (`version`,`status`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `kdd_website_post_versions_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `kdd_website_posts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `kdd_website_post_versions_ibfk_2` FOREIGN KEY (`authority_id`) REFERENCES `kdd_authorities` (`id`) ON DELETE CASCADE,
  CONSTRAINT `kdd_website_post_versions_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `kdd_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Version history for website posts';
CREATE TABLE `kdd_website_posts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `website_id` int(11) NOT NULL COMMENT 'Verknüpfung zur Website-Konfiguration',
  `category_id` int(11) DEFAULT NULL COMMENT 'Verknüpfung zur Kategorie',
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL COMMENT 'URL-freundlicher Pfad',
  `excerpt` text DEFAULT NULL COMMENT 'Kurzzusammenfassung',
  `content` longtext DEFAULT NULL,
  `featured_image` varchar(255) DEFAULT NULL,
  `is_published` tinyint(1) DEFAULT 0 COMMENT 'DEPRECATED: Use status column instead. Kept for backward compatibility.',
  `status` enum('draft','published','scheduled','archived') DEFAULT 'draft' COMMENT 'Publication status: draft (not visible), published (live), scheduled (auto-publish), archived (hidden)',
  `published_at` datetime DEFAULT NULL COMMENT 'Timestamp when post was published',
  `scheduled_at` datetime DEFAULT NULL COMMENT 'Timestamp for scheduled publication (NULL if not scheduled)',
  `publish_date` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT 0,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `authority_id` int(11) NOT NULL DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `is_featured` tinyint(1) DEFAULT 0,
  `version` int(11) DEFAULT 1 COMMENT 'Current working version number',
  `published_version` int(11) DEFAULT NULL COMMENT 'Last published version number (NULL = never published)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `website_slug` (`website_id`,`slug`),
  KEY `website_id` (`website_id`),
  KEY `category_id` (`category_id`),
  KEY `fk_website_posts_authority` (`authority_id`),
  KEY `idx_version` (`version`),
  KEY `idx_published_version` (`published_version`),
  KEY `idx_posts_status` (`status`),
  KEY `idx_posts_published_at` (`published_at`),
  KEY `idx_posts_scheduled_at` (`scheduled_at`),
  KEY `idx_posts_status_published` (`status`,`published_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `kdd_website_sections` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `website_id` int(11) NOT NULL COMMENT 'Verknüpfung zur Website-Konfiguration',
  `section_type` enum('hero','about','services','portfolio','team','testimonials','contact','features','pricing','cta','custom') NOT NULL COMMENT 'Typ der Section',
  `title` varchar(255) DEFAULT NULL COMMENT 'Section Titel',
  `subtitle` varchar(255) DEFAULT NULL COMMENT 'Section Untertitel',
  `content` longtext DEFAULT NULL COMMENT 'Section Inhalt (HTML oder JSON)',
  `settings` text DEFAULT NULL COMMENT 'JSON für section-spezifische Einstellungen (Farben, Bilder, etc.)',
  `sort_order` int(11) NOT NULL DEFAULT 0 COMMENT 'Sortierreihenfolge',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Ist die Section aktiv',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `authority_id` int(11) NOT NULL DEFAULT 1 COMMENT 'Multi-Tenant Authority ID',
  PRIMARY KEY (`id`),
  KEY `website_id` (`website_id`),
  KEY `authority_id` (`authority_id`),
  KEY `sort_order` (`sort_order`),
  KEY `is_active` (`is_active`),
  KEY `idx_website_active_order` (`website_id`,`is_active`,`sort_order`),
  CONSTRAINT `fk_sections_website` FOREIGN KEY (`website_id`) REFERENCES `kdd_website_config` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Sections für One-Pager und modular aufgebaute Templates';
CREATE TABLE `kdd_whiteboard_elements` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `whiteboard_id` int(10) unsigned NOT NULL,
  `element_id` varchar(36) NOT NULL COMMENT 'nanoid Identifikator',
  `user_id` int(10) unsigned NOT NULL COMMENT 'Benutzer-ID des Erstellers',
  `authority_id` int(10) unsigned NOT NULL COMMENT 'Zugehörige Authority',
  `type` varchar(20) NOT NULL COMMENT 'pen, eraser, text, line, rect, circle, triangle, arrow',
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'Speichert alle Eigenschaften des Elements' CHECK (json_valid(`data`)),
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT 'Soft delete für Versionierung',
  PRIMARY KEY (`id`),
  KEY `idx_whiteboard_id` (`whiteboard_id`),
  KEY `idx_element_id` (`element_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_authority_id` (`authority_id`),
  KEY `idx_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `kdd_whiteboard_permissions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `authority_id` int(10) unsigned NOT NULL,
  `role_id` int(10) unsigned NOT NULL COMMENT 'Verbindung zur Rollen-ID',
  `can_create` tinyint(1) DEFAULT 0 COMMENT 'Darf Whiteboards erstellen',
  `can_edit_any` tinyint(1) DEFAULT 0 COMMENT 'Darf alle Whiteboards bearbeiten',
  `can_view_any` tinyint(1) DEFAULT 0 COMMENT 'Darf alle Whiteboards sehen',
  `can_delete_any` tinyint(1) DEFAULT 0 COMMENT 'Darf alle Whiteboards löschen',
  `can_share` tinyint(1) DEFAULT 0 COMMENT 'Darf Whiteboards teilen',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_authority_role` (`authority_id`,`role_id`),
  KEY `idx_authority_id` (`authority_id`),
  KEY `idx_role_id` (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `kdd_whiteboard_snapshots` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `whiteboard_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL COMMENT 'Benutzer-ID des Erstellers',
  `authority_id` int(10) unsigned NOT NULL COMMENT 'Zugehörige Authority',
  `name` varchar(255) DEFAULT NULL,
  `thumbnail` longblob DEFAULT NULL COMMENT 'Optional: speichert ein Thumbnail-Bild',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_whiteboard_id` (`whiteboard_id`),
  KEY `idx_authority_id` (`authority_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `kdd_whiteboard_users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `whiteboard_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL COMMENT 'Benutzer-ID',
  `authority_id` int(10) unsigned NOT NULL COMMENT 'Zugehörige Authority',
  `role` enum('owner','editor','viewer') NOT NULL DEFAULT 'viewer',
  `joined_at` timestamp NULL DEFAULT current_timestamp(),
  `last_active_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_whiteboard_user` (`whiteboard_id`,`user_id`),
  KEY `idx_whiteboard_id` (`whiteboard_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_authority_id` (`authority_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `kdd_whiteboards` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `owner_id` int(10) unsigned NOT NULL COMMENT 'Benutzer-ID des Erstellers',
  `authority_id` int(10) unsigned NOT NULL COMMENT 'Zugehörige Authority',
  `access_type` enum('public','private','code') NOT NULL DEFAULT 'private',
  `join_code` varchar(10) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `background_color` varchar(7) DEFAULT '#FFFFFF',
  `is_archived` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_owner` (`owner_id`),
  KEY `idx_authority` (`authority_id`),
  KEY `idx_access_type` (`access_type`),
  KEY `idx_join_code` (`join_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `old_doc_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `site` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `parent_id` (`parent_id`),
  CONSTRAINT `old_doc_categories_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `old_doc_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `old_doc_documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_at_user` varchar(120) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `creator` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `old_doc_documents_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `old_doc_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=140 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `quacklejump_scores` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `player_name` varchar(50) NOT NULL,
  `score` int(11) NOT NULL,
  `height_reached` int(11) NOT NULL,
  `play_time` int(11) NOT NULL,
  `power_ups_collected` int(11) DEFAULT 0,
  `enemies_defeated` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `authority_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_authority_score` (`authority_id`,`score`),
  KEY `idx_user_score` (`user_id`,`score`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_authority_id` (`authority_id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `schema_migrations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) NOT NULL,
  `applied_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `filename` (`filename`),
  KEY `idx_filename` (`filename`)
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!50001 DROP VIEW IF EXISTS `kdd_active_sessions`*/;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`cadsystem`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `kdd_active_sessions` AS select `s`.`id` AS `id`,`s`.`user_id` AS `user_id`,`s`.`authority_id` AS `authority_id`,`s`.`token_hash` AS `token_hash`,`s`.`jti` AS `jti`,`s`.`device_name` AS `device_name`,`s`.`device_type` AS `device_type`,`s`.`ip_address` AS `ip_address`,`s`.`user_agent` AS `user_agent`,`s`.`last_activity` AS `last_activity`,`s`.`created_at` AS `created_at`,`s`.`expires_at` AS `expires_at`,`s`.`is_active` AS `is_active`,`u`.`username` AS `username`,`u`.`email` AS `email` from (`kdd_sessions` `s` join `kdd_users` `u` on(`s`.`user_id` = `u`.`id`)) where `s`.`is_active` = 1 and `s`.`expires_at` > current_timestamp() */;
/*!50001 DROP VIEW IF EXISTS `kdd_login_history`*/;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`cadsystem`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `kdd_login_history` AS select `s`.`id` AS `id`,`s`.`user_id` AS `user_id`,`u`.`username` AS `username`,`u`.`email` AS `email`,`s`.`device_name` AS `device_name`,`s`.`device_type` AS `device_type`,`s`.`ip_address` AS `ip_address`,`s`.`created_at` AS `login_time`,`s`.`last_activity` AS `last_activity`,`s`.`expires_at` AS `expires_at`,`s`.`is_active` AS `is_active`,case when `s`.`is_active` = 1 and `s`.`expires_at` > current_timestamp() then 'active' when `s`.`is_active` = 0 then 'logged_out' else 'expired' end AS `status` from (`kdd_sessions` `s` join `kdd_users` `u` on(`s`.`user_id` = `u`.`id`)) order by `s`.`created_at` desc */;
/*!50001 DROP VIEW IF EXISTS `kdd_permissions_by_module`*/;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`cadsystem`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `kdd_permissions_by_module` AS select `kdd_permissions`.`module` AS `module`,`kdd_permissions`.`sub_module` AS `sub_module`,`kdd_permissions`.`authority_id` AS `authority_id`,group_concat(`kdd_permissions`.`action` order by `kdd_permissions`.`bitmask_value` ASC separator ',') AS `actions`,sum(ifnull(`kdd_permissions`.`bitmask_value`,0)) AS `total_bitmask`,count(0) AS `permission_count` from `kdd_permissions` where `kdd_permissions`.`bitmask_value` is not null group by `kdd_permissions`.`module`,`kdd_permissions`.`sub_module`,`kdd_permissions`.`authority_id` */;
/*!50001 DROP VIEW IF EXISTS `kdd_permissions_readable`*/;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`cadsystem`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `kdd_permissions_readable` AS select `kdd_permissions`.`id` AS `id`,`kdd_permissions`.`name` AS `name`,concat(coalesce(`kdd_permissions`.`action_display`,ucase(`kdd_permissions`.`action`)),' - ',coalesce(`kdd_permissions`.`module_display`,ucase(`kdd_permissions`.`module`)),case when `kdd_permissions`.`sub_module` is not null then concat(' (',ucase(`kdd_permissions`.`sub_module`),')') else '' end) AS `display_name`,`kdd_permissions`.`module` AS `module`,`kdd_permissions`.`sub_module` AS `sub_module`,`kdd_permissions`.`action` AS `action`,`kdd_permissions`.`bitmask_value` AS `bitmask_value`,`kdd_permissions`.`authority_id` AS `authority_id` from `kdd_permissions` */;

/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- ------------------------------------------------------------
-- Stammdaten
-- ------------------------------------------------------------

-- kdd_authorities: Die Grundbehoerde. Weitere legt man in der Verwaltung an.
INSERT INTO `kdd_authorities` (`id`, `name`, `display_name`, `description`, `active`, `created_at`, `updated_at`, `logo_url`, `primary_color`, `secondary_color`, `app_title`, `default_background`, `theme_settings`, `authority_type`, `has_custom_domain`, `mail_domain`, `default_mail_quota`) VALUES (1,'system_admin','System Administrator',NULL,1,'2025-04-25 07:16:40','2026-09-10 08:09:08',NULL,'#2B62C4','#6B7280',NULL,NULL,NULL,'private',0,NULL,5);

-- kdd_authority_features: Die Module, die sich je Behoerde freischalten lassen.
INSERT INTO `kdd_authority_features` (`id`, `name`, `code`, `description`, `created_at`, `updated_at`) VALUES (1,'Dispatch System','dispatch','Access to crew/vehicle management system','2025-04-27 09:25:35','2025-04-27 09:25:35');
INSERT INTO `kdd_authority_features` (`id`, `name`, `code`, `description`, `created_at`, `updated_at`) VALUES (2,'Employee Management','employee','Access to employee records and management','2025-04-27 09:25:35','2025-04-27 09:25:35');
INSERT INTO `kdd_authority_features` (`id`, `name`, `code`, `description`, `created_at`, `updated_at`) VALUES (3,'Map System','map','Access to mapping functionality','2025-04-27 09:25:35','2025-04-27 09:25:35');
INSERT INTO `kdd_authority_features` (`id`, `name`, `code`, `description`, `created_at`, `updated_at`) VALUES (4,'Reports','reports','Access to reporting system','2025-04-27 09:25:35','2025-04-27 09:25:35');
INSERT INTO `kdd_authority_features` (`id`, `name`, `code`, `description`, `created_at`, `updated_at`) VALUES (5,'Company Directory','company','Access to company database','2025-04-27 09:25:35','2025-04-27 09:25:35');
INSERT INTO `kdd_authority_features` (`id`, `name`, `code`, `description`, `created_at`, `updated_at`) VALUES (6,'Document Management','document','Access to document management system','2025-04-27 09:25:35','2025-04-27 09:25:35');
INSERT INTO `kdd_authority_features` (`id`, `name`, `code`, `description`, `created_at`, `updated_at`) VALUES (7,'Messaging','messaging','Access to internal messaging system','2025-04-27 09:25:35','2025-04-27 09:25:35');
INSERT INTO `kdd_authority_features` (`id`, `name`, `code`, `description`, `created_at`, `updated_at`) VALUES (8,'Calendar','calendar','Access to scheduling and calendar features','2025-04-27 09:25:35','2025-04-27 09:25:35');
INSERT INTO `kdd_authority_features` (`id`, `name`, `code`, `description`, `created_at`, `updated_at`) VALUES (9,'Application Management','application','Access to job application processing','2025-04-27 09:25:35','2025-04-27 09:25:35');
INSERT INTO `kdd_authority_features` (`id`, `name`, `code`, `description`, `created_at`, `updated_at`) VALUES (10,'File Management','filemanager','Access to file storage and organization','2025-04-27 09:25:35','2025-04-27 09:25:35');
INSERT INTO `kdd_authority_features` (`id`, `name`, `code`, `description`, `created_at`, `updated_at`) VALUES (11,'Blackboard','blackboard','Access to announcement board systems','2025-04-27 09:25:35','2025-04-27 09:25:35');
INSERT INTO `kdd_authority_features` (`id`, `name`, `code`, `description`, `created_at`, `updated_at`) VALUES (12,'Person File System','person_file','Access to civilian record database','2025-04-27 09:25:35','2025-04-27 09:25:35');
INSERT INTO `kdd_authority_features` (`id`, `name`, `code`, `description`, `created_at`, `updated_at`) VALUES (13,'Vehicle File System','vehicle_file','Access to vehicle registration database','2025-04-27 09:25:35','2025-04-27 09:25:35');
INSERT INTO `kdd_authority_features` (`id`, `name`, `code`, `description`, `created_at`, `updated_at`) VALUES (14,'Apartment File System','apartment_file','Access to residential property records','2025-04-27 09:25:35','2025-04-27 09:25:35');
INSERT INTO `kdd_authority_features` (`id`, `name`, `code`, `description`, `created_at`, `updated_at`) VALUES (15,'Invoice System','invoice','Access to billing and payment processing','2025-04-27 09:25:35','2025-04-27 09:25:35');
INSERT INTO `kdd_authority_features` (`id`, `name`, `code`, `description`, `created_at`, `updated_at`) VALUES (16,'Standard','default','Access to default','2025-04-27 09:43:05','2025-04-27 09:43:05');
INSERT INTO `kdd_authority_features` (`id`, `name`, `code`, `description`, `created_at`, `updated_at`) VALUES (17,'Weather','weather','Access to weather edit','2025-04-27 09:43:05','2025-04-27 09:43:05');
INSERT INTO `kdd_authority_features` (`id`, `name`, `code`, `description`, `created_at`, `updated_at`) VALUES (18,'Template','template','Access to templates','2025-04-27 09:54:06','2025-04-27 09:54:06');
INSERT INTO `kdd_authority_features` (`id`, `name`, `code`, `description`, `created_at`, `updated_at`) VALUES (19,'Todo','todo','Access to todo','2025-04-27 09:54:17','2025-04-27 09:54:17');
INSERT INTO `kdd_authority_features` (`id`, `name`, `code`, `description`, `created_at`, `updated_at`) VALUES (20,'Training','training','Access to Training and Tests','2025-04-27 09:54:45','2025-04-27 09:54:45');
INSERT INTO `kdd_authority_features` (`id`, `name`, `code`, `description`, `created_at`, `updated_at`) VALUES (21,'System Admin','system_admin','System Admin','2025-04-27 13:39:35','2025-04-27 13:39:35');
INSERT INTO `kdd_authority_features` (`id`, `name`, `code`, `description`, `created_at`, `updated_at`) VALUES (22,'Report Share','share_reports','Access to Share Reports','2025-04-29 13:08:57','2025-04-29 13:08:57');
INSERT INTO `kdd_authority_features` (`id`, `name`, `code`, `description`, `created_at`, `updated_at`) VALUES (23,'Website','company_websites','Access to company website','2025-04-30 15:35:34','2025-04-30 15:35:55');
INSERT INTO `kdd_authority_features` (`id`, `name`, `code`, `description`, `created_at`, `updated_at`) VALUES (24,'Authorities System','authorities','Access to Authorities (Behörden)','2025-05-07 07:29:11','2025-05-07 07:29:11');
INSERT INTO `kdd_authority_features` (`id`, `name`, `code`, `description`, `created_at`, `updated_at`) VALUES (35,'Mail System','mail','Zugriff auf das Enterprise Mail- und Kommunikationssystem','2025-10-22 13:42:04','2025-10-22 13:42:04');
INSERT INTO `kdd_authority_features` (`id`, `name`, `code`, `description`, `created_at`, `updated_at`) VALUES (36,'Quick Access Shortcuts','shortcuts','Access to personalized quick access shortcuts for frequently used resources','2025-10-26 02:54:34','2025-10-26 02:54:34');

-- kdd_authority_features_rel: Welche Module die Grundbehoerde nutzen darf.
INSERT INTO `kdd_authority_features_rel` (`authority_id`, `feature_id`) VALUES (1,1);
INSERT INTO `kdd_authority_features_rel` (`authority_id`, `feature_id`) VALUES (1,2);
INSERT INTO `kdd_authority_features_rel` (`authority_id`, `feature_id`) VALUES (1,3);
INSERT INTO `kdd_authority_features_rel` (`authority_id`, `feature_id`) VALUES (1,4);
INSERT INTO `kdd_authority_features_rel` (`authority_id`, `feature_id`) VALUES (1,5);
INSERT INTO `kdd_authority_features_rel` (`authority_id`, `feature_id`) VALUES (1,6);
INSERT INTO `kdd_authority_features_rel` (`authority_id`, `feature_id`) VALUES (1,7);
INSERT INTO `kdd_authority_features_rel` (`authority_id`, `feature_id`) VALUES (1,8);
INSERT INTO `kdd_authority_features_rel` (`authority_id`, `feature_id`) VALUES (1,9);
INSERT INTO `kdd_authority_features_rel` (`authority_id`, `feature_id`) VALUES (1,10);
INSERT INTO `kdd_authority_features_rel` (`authority_id`, `feature_id`) VALUES (1,11);
INSERT INTO `kdd_authority_features_rel` (`authority_id`, `feature_id`) VALUES (1,12);
INSERT INTO `kdd_authority_features_rel` (`authority_id`, `feature_id`) VALUES (1,13);
INSERT INTO `kdd_authority_features_rel` (`authority_id`, `feature_id`) VALUES (1,14);
INSERT INTO `kdd_authority_features_rel` (`authority_id`, `feature_id`) VALUES (1,15);
INSERT INTO `kdd_authority_features_rel` (`authority_id`, `feature_id`) VALUES (1,16);
INSERT INTO `kdd_authority_features_rel` (`authority_id`, `feature_id`) VALUES (1,17);
INSERT INTO `kdd_authority_features_rel` (`authority_id`, `feature_id`) VALUES (1,18);
INSERT INTO `kdd_authority_features_rel` (`authority_id`, `feature_id`) VALUES (1,19);
INSERT INTO `kdd_authority_features_rel` (`authority_id`, `feature_id`) VALUES (1,20);
INSERT INTO `kdd_authority_features_rel` (`authority_id`, `feature_id`) VALUES (1,21);
INSERT INTO `kdd_authority_features_rel` (`authority_id`, `feature_id`) VALUES (1,22);
INSERT INTO `kdd_authority_features_rel` (`authority_id`, `feature_id`) VALUES (1,23);
INSERT INTO `kdd_authority_features_rel` (`authority_id`, `feature_id`) VALUES (1,24);
INSERT INTO `kdd_authority_features_rel` (`authority_id`, `feature_id`) VALUES (1,35);
INSERT INTO `kdd_authority_features_rel` (`authority_id`, `feature_id`) VALUES (1,36);

-- kdd_roles: Rollen. 20 ist die Rolle der Ersteinrichtung.
INSERT INTO `kdd_roles` (`id`, `name`, `description`, `created_at`, `sort_order`, `power`, `is_deleted`, `authority_id`) VALUES (1,'FULL_ADMIN','All permissons for the Website.','2023-04-07 07:40:11',1,999,0,1);
INSERT INTO `kdd_roles` (`id`, `name`, `description`, `created_at`, `sort_order`, `power`, `is_deleted`, `authority_id`) VALUES (2,'BANNED','Nothing!','2023-04-30 15:56:50',99,0,0,1);
INSERT INTO `kdd_roles` (`id`, `name`, `description`, `created_at`, `sort_order`, `power`, `is_deleted`, `authority_id`) VALUES (3,'MEMBER','','2023-04-30 15:57:40',20,1,0,1);
INSERT INTO `kdd_roles` (`id`, `name`, `description`, `created_at`, `sort_order`, `power`, `is_deleted`, `authority_id`) VALUES (20,'System Administrator','Kann das gesamte System verwalten','2025-04-27 12:31:38',999,9999,0,1);

-- kdd_permissions: Saemtliche Rechte. Recht 1 ist ALL_PERMISSIONS.
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (1,'ALL_PERMISSIONS','system',NULL,'all',255,'Alle Rechte','Full Site','Full Site','Alle Rechte, Admin sowie Allgemein.',1,'2023-04-07 07:41:05','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (2,'CAN_LOGIN','auth',NULL,'login',NULL,'Einloggen','Full Site','Full Site','Benutzer kann sich anmelden',1,'2023-04-08 04:58:15','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (3,'READ_APPLICATION','application',NULL,'read',1,'Lesen','Application','Application','Inhalt: Organisation - Bewerbungen anzeigen',1,'2023-04-10 03:42:37','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (4,'WRITE_APPLICATION','application',NULL,'write',2,'Schreiben','Application','Application','Kann Bewerbungen hinzufügen und bearbeiten.',1,'2023-04-10 03:42:37','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (5,'READ_CALENDAR','calendar',NULL,'read',1,'Lesen','Calendar','Calendar','Inhalt: Organisation - Kalendar anzeigen',1,'2023-04-10 03:43:34','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (6,'WRITE_CALENDAR','calendar',NULL,'write',2,'Schreiben','Calendar','Calendar','Beiträge im Kalendar hinzufügen und bearbeiten.',1,'2023-04-10 03:43:34','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (7,'READ_ACCOUNT','account',NULL,'read',1,'Lesen','Account','Account','Eigenes Profil lesen',1,'2023-05-03 13:45:16','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (8,'WRITE_ACCOUNT','account',NULL,'write',2,'Schreiben','Account','Account','Can change password, reset and update template image',1,'2023-05-03 13:46:22','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (9,'READ_DOCUMENT','document',NULL,'read',1,'Lesen','Dokumente','Dokumente','Inhalt: Dokumente anzeigen',1,'2023-05-03 13:52:01','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (10,'WRITE_DOCUMENT','document',NULL,'write',2,'Schreiben','Dokumente','Dokumente','Dokumente hinzufügen/bearbeiten',1,'2023-05-03 13:52:08','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (11,'READ_EMPLOYEE','employee',NULL,'read',1,'Lesen','Employee','Employee','Inhalt: Mitarbeiter anzeigen',1,'2023-05-03 13:53:11','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (12,'WRITE_EMPLOYEE','employee',NULL,'write',2,'Schreiben','Employee','Employee','Menüpunkt Firmenname, Mitarbeiterübersicht',1,'2023-05-03 13:53:29','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (13,'CREATE_DOCUMENT','document',NULL,'create',32,'Erstellen','Dokumente','Dokumente','Kann Brandschutzzertifikate ausstellen',1,'2023-05-03 13:54:57','2025-11-03 16:22:26');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (14,'READ_MAP','map',NULL,'read',1,'Lesen','Map','Karte','Inhalt: Karte anzeigen',1,'2023-05-03 13:55:58','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (15,'WRITE_MAP','map',NULL,'write',2,'Schreiben','Map','Karte','Markierungen auf der Karte hinzufügen/bearbeiten',1,'2023-05-03 13:56:11','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (16,'DELETE_MAP','map',NULL,'delete',4,'Löschen','Map','Karte','Marker von der Karte löschen',1,'2023-05-03 13:56:23','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (17,'READ_TEMPLATE','template',NULL,'read',1,'Lesen','Template','Template','Inhalt: Templates anzeigen',1,'2023-05-03 14:00:37','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (18,'WRITE_TEMPLATE','template',NULL,'write',2,'Schreiben','Template','Template','Templates hinzufügen',1,'2023-05-03 14:00:42','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (19,'DELETE_TEMPLATE','template',NULL,'delete',4,'Löschen','Template','Template','Templates löschen',1,'2023-05-03 14:00:49','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (20,'READ_TODO','todo',NULL,'read',1,'Lesen','Todo','Todo','Inhalt: Organisation - TO-DO anzeigen',1,'2023-05-03 14:05:42','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (21,'WRITE_TODO','todo',NULL,'write',2,'Schreiben','Todo','Todo','Einträge in die ToDo Liste erstellen',1,'2023-05-03 14:05:50','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (22,'ADMIN_WRITE_USERS','admin','users','write',2,'Schreiben','Admin_Users','Admin_Users','Benutzer hinzufügen/bearbeiten',1,'2023-05-03 14:11:03','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (23,'ADMIN_READ_USERS','admin','users','read',1,'Lesen','Admin_Users','Admin_Users','Inhalt: Admin - Benutzer anzeigen',1,'2023-05-03 14:11:18','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (24,'ADMIN_READ_APPLICATION','admin','application','read',1,'Lesen','Admin_Application','Admin_Application','Inhalt: Admin - Bewerberfragen anzeigen',1,'2023-05-03 15:01:53','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (25,'READ_FIREPROTECTION','fireprotection',NULL,'read',1,'Lesen','Fire Protection','Fire Protection','Inhalt: Sonstiges - Fire Protection anzeigen',1,'2023-05-03 15:03:28','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (26,'VIEW_EMPLOYEE','employee',NULL,'view',16,'Anzeigen','Employee','Employee','Menüpunkt: Mitarbeiter anzeigen',1,'2023-05-03 15:10:28','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (27,'VIEW_APPLICATION','application',NULL,'view',16,'Anzeigen','Application','Application','Menüpunkt: Organistation - Bewerbungen anzeigen',1,'2023-05-03 15:10:38','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (28,'VIEW_CALENDAR','calendar',NULL,'view',16,'Anzeigen','Calendar','Calendar','Menüpunkt: Organisation - Kalendar anzeigen',1,'2023-05-03 15:10:49','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (29,'VIEW_DOCUMENT','document',NULL,'view',16,'Anzeigen','Dokumente','Dokumente','Menüpunkt: Dokumente anzeigen',1,'2023-05-03 15:11:05','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (30,'VIEW_FIREPROTECTION','fireprotection',NULL,'view',16,'Anzeigen','Fire Protection','Fire Protection','Menüpunkt: Sonstiges - Fire Protection anzeigen',1,'2023-05-03 15:11:16','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (31,'VIEW_TEMPLATE','template',NULL,'view',16,'Anzeigen','Template','Template','Menüpunkt: Template anzeigen',1,'2023-05-03 15:11:24','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (32,'VIEW_TODO','todo',NULL,'view',16,'Anzeigen','Todo','Todo','Menüpunkt: Organisation - TO-DO anzeigen',1,'2023-05-03 15:11:36','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (33,'VIEW_USERS','users',NULL,'view',16,'Anzeigen','Users','Users','Menüpunkt:  anzeigen',1,'2023-05-03 15:11:53','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (34,'VIEW_ACCOUNT','account',NULL,'view',16,'Anzeigen','Account','Account','Eigenes Profil anzeigen',1,'2023-05-03 15:12:10','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (35,'VIEW_MAP','map',NULL,'view',16,'Anzeigen','Map','Karte','Menüpunkt: Karte anzeigen',1,'2023-05-03 15:12:19','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (36,'ADMIN_VIEW_APPLICATION','admin','application','view',16,'Anzeigen','Admin_Application','Admin_Application','Menüpunkt: Admin - Bewerberfragen anzeigen',1,'2023-05-03 15:12:38','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (37,'ADMIN_VIEW_USERS','admin','users','view',16,'Anzeigen','Admin_Users','Admin_Users','Menüpunkt: Admin - Benutzer anzeigen',1,'2023-05-03 15:12:51','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (38,'ADMIN_VIEW_ROLES','admin','roles','view',16,'Anzeigen','Admin_Roles','Admin_Roles','Menüpunkt: Admin - Rollen anzeigen',1,'2023-05-04 08:38:02','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (41,'ADMIN_READ_ROLES','admin','roles','read',1,'Lesen','Admin_Roles','Admin_Roles','Inhalt: Admin - Rollen anzeigen',1,'2023-05-04 09:56:45','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (42,'ADMIN_WRITE_ROLES','admin','roles','write',2,'Schreiben','Admin_Roles','Admin_Roles','Rollen hinzufügen/bearbeiten',1,'2023-05-04 09:56:56','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (44,'ADMIN_WRITE_APPLICATION','admin','application','write',2,'Schreiben','Admin_Application','Admin_Application','Neues Wetter eintragen/bearbeiten',1,'2023-05-04 11:41:13','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (48,'ADMIN_VIEW_SETTINGS','admin','settings','view',16,'Anzeigen','Admin_Settings','Admin_Settings','Menüpunkt: Admin - Settings anzeigen',1,'2023-05-05 09:31:53','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (49,'ADMIN_READ_SETTINGS','admin','settings','read',1,'Lesen','Admin_Settings','Admin_Settings','Inhalt: Admin - Settings anzeigen',1,'2023-05-05 09:32:02','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (50,'ADMIN_WRITE_SETTINGS','admin','settings','write',2,'Schreiben','Admin_Settings','Admin_Settings','Einstellungen bearbeiten',1,'2023-05-05 09:32:31','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (51,'ADMIN_VIEW_EMPLOYEE','admin','employee','view',16,'Anzeigen','Admin_Employee','Admin_Employee','Menüpunkt: Admin - Mitarbeiter anzeigen',1,'2023-05-05 16:42:25','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (52,'ADMIN_READ_EMPLOYEE','admin','employee','read',1,'Lesen','Admin_Employee','Admin_Employee','Inhalt: Admin - Mitarbeiter anzeigen',1,'2023-05-05 16:42:37','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (53,'ADMIN_WRITE_EMPLOYEE','admin','employee','write',2,'Schreiben','Admin_Employee','Admin_Employee','Companies und Departments hinzufügen/bearbeiten',1,'2023-05-05 16:42:50','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (54,'ADMIN_VIEW_MAP','admin','map','view',16,'Anzeigen','Admin_Map','Admin_Map','Menüpunkt: Admin - Karte anzeigen',1,'2023-05-06 08:30:00','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (55,'ADMIN_READ_MAP','admin','map','read',1,'Lesen','Admin_Map','Admin_Map','Inhalt: Admin - Karte anzeigen',1,'2023-05-06 08:30:09','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (56,'ADMIN_WRITE_MAP','admin','map','write',2,'Schreiben','Admin_Map','Admin_Map','Karteneinstellungen bearbeiten',1,'2023-05-06 08:30:19','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (66,'WRITE_TEST','training','test','write',2,'Schreiben','TEST','Schulungen','Prüfungen erstellen',1,'2024-01-27 05:01:57','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (67,'READ_TEST','training','test','read',1,'Lesen','TEST','Schulungen','Inhalt: Schulungen - Test Generieren anzeigen',1,'2024-01-27 05:02:15','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (69,'VIEW_TEST','training','test','view',16,'Anzeigen','TEST','Schulungen','Menüpunkt: Schulungen - Test Generieren anzeigen',1,'2024-01-27 05:01:57','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (70,'ADMIN_VIEW_TRAINING','admin','training','view',16,'Anzeigen','Admin_Training','Admin_Training','Menüpunkt: Admin - Schulungen anzeigen',1,'2024-06-06 15:09:26','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (71,'ADMIN_READ_TRAINING','admin','training','read',1,'Lesen','Admin_Training','Admin_Training','Inhalt: Admin - Schulungen anzeigen',1,'2024-06-06 15:09:55','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (72,'ADMIN_WRITE_TRAINING','admin','training','write',2,'Schreiben','Admin_Training','Admin_Training','Schulungen hinzufügen/bearbeiten',1,'2024-06-06 15:10:11','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (73,'WRITE_TRAININGASSIGN','training','assign','write',2,'Schreiben','Schulungen zuweisen','Schulungen','Schulungen Mitarbeitern zuweisen',1,'2024-06-10 15:18:38','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (74,'READ_TRAININGASSIGN','training','assign','read',1,'Lesen','Schulungen zuweisen','Schulungen','Inhalt: Schulungen - Übersicht  anzeigen',1,'2024-06-10 15:18:38','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (75,'VIEW_TRAININGASSIGN','training','assign','view',16,'Anzeigen','Schulungen zuweisen','Schulungen','Menüpunkt: Schulungen - Übersicht anzeigen',1,'2024-06-10 15:18:38','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (82,'VIEW_DISPATCH','dispatch',NULL,'view',16,'Anzeigen','Leitstelle','Leitstelle','Menüpunkt: Leitstelle anzeigen',1,'2024-06-10 15:18:38','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (83,'READ_DISPATCH','dispatch',NULL,'read',1,'Lesen','Leitstelle','Leitstelle','Inhalt: Leitstelle - Besatzung anzeigen',1,'2024-06-10 15:18:38','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (84,'WRITE_DISPATCH','dispatch',NULL,'write',2,'Schreiben','Leitstelle','Leitstelle','Leistelle bearbeiten',1,'2024-06-10 15:18:38','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (85,'VIEW_VEHICLE','dispatch','vehicle','view',16,'Anzeigen','Leitstelle','Leitstelle','Menüpunkt: Leitstelle - Fahrzeuge anzeigen',1,'2024-06-10 15:18:38','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (86,'READ_VEHICLE','dispatch','vehicle','read',1,'Lesen','Leitstelle','Leitstelle','Inhalt: Leitstelle - Fahrzeuge anzeigen',1,'2024-06-10 15:18:38','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (87,'WRITE_VEHICLE','dispatch','vehicle','write',2,'Schreiben','Leitstelle','Leitstelle','Leitstellenfahrzeuge hinzufügen/bearbeiten',1,'2024-06-10 15:18:38','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (88,'VIEW_CREW','dispatch','crew','view',16,'Anzeigen','Leitstelle','Leitstelle','Menüpunkt: Leitstelle - Besatzung anzeigen',1,'2024-06-10 15:18:38','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (89,'READ_CREW','dispatch','crew','read',1,'Lesen','Leitstelle','Leitstelle','Inhalt: Leitstelle - Besatzung anzeigen',1,'2024-06-10 15:18:38','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (90,'WRITE_CREW','dispatch','crew','write',2,'Schreiben','Leitstelle','Leitstelle','Besatzung unter Leitstelle hinzufügen/bearbeiten',1,'2024-06-10 15:18:38','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (91,'VIEW_REPORT','report',NULL,'view',16,'Anzeigen','Berichte','Berichte','Menüpunkt: Berichte anzeigen',1,'2024-06-27 11:53:46','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (92,'READ_REPORT','report',NULL,'read',1,'Lesen','Berichte','Berichte','Inhalt: Berichte anzeigen',1,'2024-06-27 11:53:46','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (93,'WRITE_REPORT','report',NULL,'write',2,'Schreiben','Berichte','Berichte','Reports hinzufügen/bearbeiten',1,'2024-06-27 11:53:46','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (94,'VIEW_REPORTCATEGORY','report','category','view',16,'Anzeigen','Berichte','Berichte','Menüpunkt: Berichte - Kategorien anzeigen',1,'2024-06-27 11:53:46','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (95,'READ_REPORTCATEGORY','report','category','read',1,'Lesen','Berichte','Berichte','Inhalt: Berichte - Kategorien anzeigen',1,'2024-06-27 11:53:46','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (96,'WRITE_REPORTCATEGORY','report','category','write',2,'Schreiben','Berichte','Berichte','Kategorien für Reports hinzufügen/bearbeiten',1,'2024-06-27 11:53:46','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (97,'VIEW_REPORTTEMPLATE','report','template','view',16,'Anzeigen','Berichte','Berichte','Menüpunkt: Berichte - Vorlagen anzeigen',1,'2024-06-27 11:53:46','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (98,'READ_REPORTTEMPLATE','report','template','read',1,'Lesen','Berichte','Berichte','Inhalt: Berichte - Vorlagen anzeigen',1,'2024-06-27 11:53:46','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (99,'WRITE_REPORTTEMPLATE','report','template','write',2,'Schreiben','Berichte','Berichte','Templates für Reports hinzufügen/bearbeiten',1,'2024-06-27 11:53:46','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (100,'VIEW_MESSAGES','messaging',NULL,'view',16,'Anzeigen','Nachrichten','Nachrichten','Menüpunkt: Nachrichten anzeigen',1,'2024-06-27 11:53:46','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (101,'READ_MESSAGES','messaging',NULL,'read',1,'Lesen','Nachrichten','Nachrichten','Inhalt: Nachrichten anzeigen',1,'2024-06-27 11:53:46','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (102,'WRITE_MESSAGES','messaging',NULL,'write',2,'Schreiben','Nachrichten','Nachrichten','Nachrichten schreiben',1,'2024-06-27 11:53:46','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (103,'ADMIN_VIEW_MESSAGES','admin','messaging','view',16,'Anzeigen','Admin_Nachrichten','Admin_Nachrichten','Menüpunkt: Admin - Nachrichten anzeigen',1,'2024-06-27 11:53:46','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (104,'ADMIN_READ_MESSAGES','admin','messaging','read',1,'Lesen','Admin_Nachrichten','Admin_Nachrichten','Inhalt: Admin - Nachrichten anzeigen',1,'2024-06-27 11:53:46','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (105,'ADMIN_WRITE_MESSAGES','admin','messaging','write',2,'Schreiben','Admin_Nachrichten','Admin_Nachrichten','Neue Gruppen hinzufügen/bearbeiten',1,'2024-06-27 11:53:46','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (106,'VIEW_REPORTCODE','report','code','view',16,'Anzeigen','Berichte','Berichte','Menüpunkt: Berichte - Code anzeigen',1,'2024-06-27 11:53:46','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (107,'READ_REPORTCODE','report','code','read',1,'Lesen','Berichte','Berichte','Inhalt: Berichte - Code anzeigen',1,'2024-06-27 11:53:46','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (108,'WRITE_REPORTCODE','report','code','write',2,'Schreiben','Berichte','Berichte','Code für Reports hinzufügen/bearbeiten',1,'2024-06-27 11:53:46','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (109,'VIEW_COMPANY','company',NULL,'view',16,'Anzeigen','Unternehmen','Unternehmen','Menüpunkt: Unternehmen anzeigen',1,'2024-07-10 18:10:47','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (110,'READ_COMPANY','company',NULL,'read',1,'Lesen','Unternehmen','Unternehmen','Inhalt: Unternehmen anzeigen',1,'2024-07-10 18:10:47','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (111,'WRITE_COMPANY','company',NULL,'write',2,'Schreiben','Unternehmen','Unternehmen','Unternehmen hinzufügen/bearbeiten',1,'2024-07-10 18:10:47','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (112,'VIEW_COMPANYTYPE','company','type','view',16,'Anzeigen','Unternehmen','Unternehmen','Menüpunkt: Unternehmen - Typ anzeigen',1,'2024-07-10 18:10:47','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (113,'READ_COMPANYTYPE','company','type','read',1,'Lesen','Unternehmen','Unternehmen','Inhalt: Unternehmen - Typ anzeigen',1,'2024-07-10 18:10:47','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (114,'WRITE_COMPANYTYPE','company','type','write',2,'Schreiben','Unternehmen','Unternehmen','Unternehmenstypen hinzufügen/bearbeiten',1,'2024-07-10 18:10:47','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (115,'IS_SUSPENDED_GROUP','suspended','group','is',NULL,'Suspendiert','Full Site','Full Site','Gruppen mit diesem Rechte gelten als Suspendiert',1,'2024-08-07 18:26:41','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (116,'WRITE_WEATHER','weather',NULL,'write',2,'Schreiben','Wetter','Wetter','Wetter hinzufügen/bearbeiten',1,'2024-08-07 22:23:30','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (117,'ADMIN_VIEW_WEATHER','admin','weather','view',16,'Anzeigen','Admin_Weather','Admin_Weather','Menüpunkt: Admin - Wetter anzeigen',1,'2024-08-07 22:29:23','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (118,'DELETE_MAP_GLOBAL','map','global','delete',4,'Löschen','Map Global','Behördenaustausch','Marker von der globalen Karte löschen',1,'2024-11-16 00:11:48','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (119,'READ_APARTMENT_FILE','apartment','file','read',1,'Lesen','Wohnungsakten','Wohnungsakten','Wohnungsakten lesen',1,'2024-11-16 00:11:48','2025-11-03 16:22:26');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (120,'READ_BLACKBOARD_GLOBAL','blackboard',NULL,'read',1,'Lesen','Behördenaustausch › Schwarzes Brett','Behördenaustausch','Globale Pinnwandeinträge lesen',1,'2024-11-16 00:11:48','2025-11-03 16:22:26');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (121,'READ_DOCUMENT_GLOBAL','document',NULL,'read',1,'Lesen','Behördenaustausch › Dokumente','Behördenaustausch','Globale Dokumente lesen',1,'2024-11-16 00:11:48','2025-11-03 16:22:26');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (122,'READ_MAP_GLOBAL','map','global','read',1,'Lesen','Map Global','Behördenaustausch','Globale Kartenmarker lesen',1,'2024-11-16 00:11:48','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (123,'READ_PERSON_FILE','person','file','read',1,'Lesen','Personenakten','Personenakten','Personenakten lesen',1,'2024-11-16 00:11:48','2025-11-03 16:22:26');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (124,'READ_REPORT_ADDITIONALS','report',NULL,'read',1,'Lesen','Berichte','Berichte','Zusätzliche Details zu Berichten lesen',1,'2024-11-16 00:11:48','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (125,'READ_REPORT_STATUS','report',NULL,'read',1,'Lesen','Berichte','Berichte','Status von Berichten lesen',1,'2024-11-16 00:11:48','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (126,'READ_VEHICLE_FILE','vehicle','file','read',1,'Lesen','Fahrzeugakten','Fahrzeugakten','Fahrzeugakten lesen',1,'2024-11-16 00:11:48','2025-11-03 16:22:26');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (127,'VIEW_APARTMENT_FILE','apartment','file','view',16,'Anzeigen','Wohnungsakten','Wohnungsakten','Wohnungsakten anzeigen',1,'2024-11-16 00:11:48','2025-11-03 16:22:26');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (128,'VIEW_BLACKBOARD_GLOBAL','blackboard',NULL,'view',16,'Anzeigen','Behördenaustausch › Schwarzes Brett','Behördenaustausch','Globale Pinnwand anzeigen',1,'2024-11-16 00:11:48','2025-11-03 16:22:26');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (129,'VIEW_DOCUMENT_GLOBAL','document',NULL,'view',16,'Anzeigen','Behördenaustausch › Dokumente','Behördenaustausch','Globale Dokumente anzeigen',1,'2024-11-16 00:11:48','2025-11-03 16:22:26');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (130,'VIEW_MAP_GLOBAL','map','global','view',16,'Anzeigen','Behördenaustausch › Karte','Behördenaustausch','Globale Kartenmarker anzeigen',1,'2024-11-16 00:11:48','2025-11-03 16:22:26');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (131,'VIEW_PERSON_FILE','person','file','view',16,'Anzeigen','Personenakten','Personenakten','Personenakten anzeigen',1,'2024-11-16 00:11:48','2025-11-03 16:22:26');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (132,'VIEW_REPORT_ADDITIONALS','report',NULL,'view',16,'Anzeigen','Berichte','Berichte','Zusätzliche Details zu Berichten anzeigen',1,'2024-11-16 00:11:48','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (133,'VIEW_REPORT_STATUS','report',NULL,'view',16,'Anzeigen','Berichte','Berichte','Status von Berichten anzeigen',1,'2024-11-16 00:11:48','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (134,'VIEW_VEHICLE_FILE','vehicle','file','view',16,'Anzeigen','Fahrzeugakten','Fahrzeugakten','Fahrzeugakten anzeigen',1,'2024-11-16 00:11:48','2025-11-03 16:22:26');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (135,'WRITE_APARTMENT_FILE','apartment','file','write',2,'Schreiben','Wohnungsakten','Wohnungsakten','Wohnungsakten bearbeiten',1,'2024-11-16 00:11:48','2025-11-03 16:22:26');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (136,'WRITE_BLACKBOARD_GLOBAL','blackboard',NULL,'write',2,'Schreiben','Behördenaustausch › Schwarzes Brett','Behördenaustausch','Globale Pinnwandeinträge bearbeiten',1,'2024-11-16 00:11:48','2025-11-03 16:22:26');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (137,'WRITE_DOCUMENT_GLOBAL','document',NULL,'write',2,'Schreiben','Behördenaustausch › Dokumente','Behördenaustausch','Globale Dokumente bearbeiten',1,'2024-11-16 00:11:48','2025-11-03 16:22:26');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (138,'WRITE_MAP_GLOBAL','map','global','write',2,'Schreiben','Behördenaustausch › Karte','Behördenaustausch','Globale Kartenmarker bearbeiten',1,'2024-11-16 00:11:48','2025-11-03 16:22:26');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (139,'WRITE_PERSON_FILE','person','file','write',2,'Schreiben','Personenakten','Personenakten','Personenakten bearbeiten',1,'2024-11-16 00:11:48','2025-11-03 16:22:26');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (140,'WRITE_REPORT_ADDITIONALS','report',NULL,'write',2,'Schreiben','Berichte','Berichte','Zusätzliche Details zu Berichten bearbeiten',1,'2024-11-16 00:11:48','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (141,'WRITE_REPORT_STATUS','report',NULL,'write',2,'Schreiben','Berichte','Berichte','Status von Berichten bearbeiten',1,'2024-11-16 00:11:48','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (142,'WRITE_VEHICLE_FILE','vehicle','file','write',2,'Schreiben','Fahrzeugakten','Fahrzeugakten','Fahrzeugakten bearbeiten',1,'2024-11-16 00:11:48','2025-11-03 16:22:26');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (143,'VIEW_INVOICE','invoice',NULL,'view',16,'Anzeigen','Rechnungen','Rechnungen','Menüpunkt: Rechnungen anzeigen',1,'2024-11-18 15:46:59','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (144,'READ_INVOICE','invoice',NULL,'read',1,'Lesen','Rechnungen','Rechnungen','Inhalt: Rechnungen anzeigen',1,'2024-11-18 15:46:59','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (145,'WRITE_INVOICE','invoice',NULL,'write',2,'Schreiben','Rechnungen','Rechnungen','Rechnungen erstellen/bearbeiten',1,'2024-11-18 15:46:59','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (146,'WRITE_INVOICEITEMS','invoiceitems',NULL,'write',2,'Schreiben','Rechnungsposten','Rechnungen','Rechnungsitems erstellen/bearbeiten',1,'2024-11-18 15:46:59','2025-11-03 16:22:26');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (147,'READ_INVOICEITEMS','invoiceitems',NULL,'read',1,'Lesen','Rechnungsposten','Rechnungen','Inhalt: Rechnungen - Gegenstände anzeigen',1,'2024-11-18 15:46:59','2025-11-03 16:22:26');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (148,'VIEW_INVOICEITEMS','invoiceitems',NULL,'view',16,'Anzeigen','Rechnungsposten','Rechnungen','Menüpunkt: Rechnungen - Gegenstände anzeigen',1,'2024-11-18 15:46:59','2025-11-03 16:22:26');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (149,'READ_FILEMANAGER','filemanager',NULL,'read',1,'Lesen','Filemanager','Filemanager','Inhalt: Datei Manager sehen',1,'2023-05-03 13:45:16','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (150,'WRITE_FILEMANAGER','filemanager',NULL,'write',2,'Schreiben','Filemanager','Filemanager','Dateien hochladen/löschen',1,'2023-05-03 13:46:22','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (151,'VIEW_FILEMANAGER','filemanager',NULL,'view',16,'Anzeigen','Filemanager','Filemanager','Menüpunkt: Datei Manager anzeigen',1,'2023-05-03 15:12:10','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (159,'DELETE_APPLICATION','application',NULL,'delete',4,'Löschen','Application','Application','Kann Bewerbungen löschen',1,'2024-11-25 14:21:27','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (160,'DELETE_CALENDAR','calendar',NULL,'delete',4,'Löschen','Calendar','Calendar','Beiträge im Kalendar löschen',1,'2024-11-25 14:21:27','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (161,'DELETE_ACCOUNT','account',NULL,'delete',4,'Löschen','Account','Account','Can change password, reset and update template image',1,'2024-11-25 14:21:27','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (162,'DELETE_DOCUMENT','document',NULL,'delete',4,'Löschen','Dokumente','Dokumente','Dokumente löschen',1,'2024-11-25 14:21:27','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (163,'DELETE_EMPLOYEE','employee',NULL,'delete',4,'Löschen','Employee','Employee','Menüpunkt Firmenname, Mitarbeiterübersicht',1,'2024-11-25 14:21:27','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (164,'DELETE_TODO','todo',NULL,'delete',4,'Löschen','Todo','Todo','Einträge in die ToDo Liste erstellen',1,'2024-11-25 14:21:27','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (168,'DELETE_TEST','training','test','delete',4,'Löschen','TEST','Schulungen','Prüfungen löschen',1,'2024-11-25 14:21:27','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (169,'DELETE_TRAININGASSIGN','training','assign','delete',4,'Löschen','Schulungen zuweisen','Schulungen','Schulungen Mitarbeitern löschen',1,'2024-11-25 14:21:27','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (172,'DELETE_DISPATCH','dispatch',NULL,'delete',4,'Löschen','Leitstelle','Leitstelle','Leistelle löschen',1,'2024-11-25 14:21:27','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (173,'DELETE_VEHICLE','dispatch','vehicle','delete',4,'Löschen','Leitstelle','Leitstelle','Leitstellenfahrzeuge löschen',1,'2024-11-25 14:21:27','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (174,'DELETE_CREW','dispatch','crew','delete',4,'Löschen','Leitstelle','Leitstelle','Besatzung unter Leitstelle löschen',1,'2024-11-25 14:21:27','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (175,'DELETE_REPORT','report',NULL,'delete',4,'Löschen','Berichte','Berichte','Reports löschen',1,'2024-11-25 14:21:27','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (176,'DELETE_REPORTCATEGORY','report','category','delete',4,'Löschen','Berichte','Berichte','Kategorien für Reports löschen',1,'2024-11-25 14:21:27','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (177,'DELETE_REPORTTEMPLATE','report','template','delete',4,'Löschen','Berichte','Berichte','Templates für Reports löschen',1,'2024-11-25 14:21:27','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (178,'DELETE_MESSAGES','messaging',NULL,'delete',4,'Löschen','Nachrichten','Nachrichten','Nachrichten löschen',1,'2024-11-25 14:21:27','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (179,'DELETE_REPORTCODE','report','code','delete',4,'Löschen','Berichte','Berichte','Code für Reports löschen',1,'2024-11-25 14:21:27','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (180,'DELETE_COMPANY','company',NULL,'delete',4,'Löschen','Unternehmen','Unternehmen','Unternehmen löschen',1,'2024-11-25 14:21:27','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (181,'DELETE_COMPANYTYPE','company','type','delete',4,'Löschen','Unternehmen','Unternehmen','Unternehmenstypen löschen',1,'2024-11-25 14:21:27','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (182,'DELETE_WEATHER','weather',NULL,'delete',4,'Löschen','Wetter','Wetter','Wetter löschen',1,'2024-11-25 14:21:27','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (183,'DELETE_APARTMENT_FILE','apartment','file','delete',4,'Löschen','Wohnungsakten','Wohnungsakten','Wohnungsakten löschen',1,'2024-11-25 14:21:27','2025-11-03 16:22:26');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (184,'DELETE_BLACKBOARD_GLOBAL','blackboard',NULL,'delete',4,'Löschen','Behördenaustausch › Schwarzes Brett','Behördenaustausch','Globale Pinnwandeinträge löschen',1,'2024-11-25 14:21:27','2025-11-03 16:22:26');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (185,'DELETE_DOCUMENT_GLOBAL','document',NULL,'delete',4,'Löschen','Behördenaustausch › Dokumente','Behördenaustausch','Globale Dokumente löschen',1,'2024-11-25 14:21:27','2025-11-03 16:22:26');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (186,'DELETE_PERSON_FILE','person','file','delete',4,'Löschen','Personenakten','Personenakten','Personenakten löschen',1,'2024-11-25 14:21:27','2025-11-03 16:22:26');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (187,'DELETE_REPORT_ADDITIONALS','report',NULL,'delete',4,'Löschen','Berichte','Berichte','Zusätzliche Details zu Berichten löschen',1,'2024-11-25 14:21:27','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (188,'DELETE_REPORT_STATUS','report',NULL,'delete',4,'Löschen','Berichte','Berichte','Status von Berichten löschen',1,'2024-11-25 14:21:27','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (189,'DELETE_VEHICLE_FILE','vehicle','file','delete',4,'Löschen','Fahrzeugakten','Fahrzeugakten','Fahrzeugakten löschen',1,'2024-11-25 14:21:27','2025-11-03 16:22:26');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (190,'DELETE_INVOICE','invoice',NULL,'delete',4,'Löschen','Rechnungen','Rechnungen','Rechnungen löschen',1,'2024-11-25 14:21:27','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (191,'DELETE_INVOICEITEMS','invoiceitems',NULL,'delete',4,'Löschen','Rechnungsposten','Rechnungen','Rechnungsitems löschen',1,'2024-11-25 14:21:27','2025-11-03 16:22:26');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (192,'DELETE_FILEMANAGER','filemanager',NULL,'delete',4,'Löschen','Filemanager','Filemanager','Dateien löschen',1,'2024-11-25 14:21:27','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (193,'SYSTEM_ADMIN','admin',NULL,'system',8,'Sysadmin','System Admin','System Admin','System admin',1,'2025-04-27 13:37:34','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (194,'SHARE_REPORT','report',NULL,'share',NULL,'Share','Report Management','Berichte','Berechtigung zum Teilen von Berichten mit anderen Authorities oder Rollen',1,'2025-04-29 11:58:57','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (199,'READ_COMPANY_WEBSITES','company','websites','read',1,'Lesen','Website','Website','Erlaubt das Anzeigen von Firmenwebsites',1,'2025-04-30 15:14:06','2025-11-03 16:24:16');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (201,'WRITE_COMPANY_WEBSITES','company','websites','write',2,'Schreiben','Website','Website','Erlaubt das Bearbeiten von Firmenwebsites',1,'2025-04-30 15:14:06','2025-11-03 16:24:16');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (202,'DELETE_COMPANY_WEBSITES','company','websites','delete',4,'Löschen','Website','Website','Erlaubt das Löschen von Firmenwebseiten-Inhalten',1,'2025-04-30 15:14:06','2025-11-03 16:24:18');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (203,'READ_REPORT_GROUPS','report',NULL,'read',1,'Read','Report','Berichte','Can view report groups',1,'2025-05-07 10:16:01','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (204,'WRITE_REPORT_GROUPS','report',NULL,'write',2,'Write','Report','Berichte','Can create and edit report groups',1,'2025-05-07 10:16:01','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (205,'DELETE_REPORT_GROUPS','report',NULL,'delete',4,'Delete','Report','Berichte','Can delete report groups',1,'2025-05-07 10:16:01','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (206,'MANAGE_REPORT_GROUP_PERMISSIONS','report',NULL,'manage',NULL,'Manage','Report','Berichte','Can manage permissions for report groups',1,'2025-05-07 10:16:01','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (211,'READ_DOCUMENT_AREA','document',NULL,'read',1,'Lesen','Dokumentenbereiche','Dokumente','Erlaubt das Anzeigen von Dokumentenbereichen',1,'2025-05-08 19:01:29','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (212,'ADMIN_DOCUMENT_AREAS','admin','document','admin',8,'Verwalten','Dokumentenbereiche','Dokumentenbereiche','Kann Dokumentenbereiche verwalten (erstellen, bearbeiten, löschen)',1,'2025-05-08 19:01:29','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (217,'ADMIN_AUTHORITY_SETTINGS','admin','authority','admin',8,'Verwalten','Full Site','Full Site','Kann Authority-Einstellungen und Branding verwalten',1,'2025-09-29 06:27:52','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (218,'READ_CHEATSHEET','cheatsheet',NULL,'read',1,'Cheatsheet ansehen','Cheatsheet','Cheatsheet','View cheatsheet reference',1,'2025-10-14 08:21:07','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (219,'ADMIN_CHEATSHEET','admin','cheatsheet','admin',8,'Cheatsheet verwalten','Cheatsheet','Cheatsheet','Manage cheatsheet content',1,'2025-10-14 08:21:07','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (246,'READ_MAIL','mail',NULL,'read',1,'Mail lesen','Mail','Mail','Kann Mails lesen und empfangen',1,'2025-10-22 13:42:04','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (247,'WRITE_MAIL','mail',NULL,'write',2,'Mail schreiben','Mail','Mail','Kann Mails senden und verwalten',1,'2025-10-22 13:42:04','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (248,'ADMIN_MAIL','admin','mail','admin',8,'Mail administrieren','Mail','Mail','Kann Unternehmenspostfächer verwalten',1,'2025-10-22 13:42:04','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (249,'DELETE_MAIL','mail',NULL,'delete',4,'Mail löschen','Mail','Mail','Kann Mails permanent löschen',1,'2025-10-22 13:42:04','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (250,'WRITE_DOCUMENT_AREA','document',NULL,'write',2,'Schreiben','Dokumentenbereiche','Dokumente','Erlaubt das Erstellen von Dokumentenbereichen',1,'2025-10-24 17:46:26','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (251,'DELETE_DOCUMENT_AREA','document',NULL,'delete',4,'Löschen','Dokumentenbereiche','Dokumente','Erlaubt das Löschen von Dokumentenbereichen',1,'2025-10-24 17:46:26','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (252,'WRITE_FIREPROTECTION','fireprotection',NULL,'write',2,'Schreiben','Fire Protection','Fire Protection','Kann Brandschutz-Einträge erstellen und bearbeiten',1,'2025-10-25 06:57:21','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (253,'READ_RANK','rank',NULL,'read',1,'Lesen','Ränge','Ränge','Kann Ränge anzeigen',1,'2025-10-25 06:57:21','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (254,'READ_TRAINING','training',NULL,'read',1,'Lesen','Schulungen','Schulungen','Kann Schulungen anzeigen',1,'2025-10-25 06:57:21','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (255,'WRITE_TRAINING','training',NULL,'write',2,'Schreiben','Schulungen','Schulungen','Kann Schulungen erstellen und bearbeiten',1,'2025-10-25 06:57:21','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (256,'READ_WHITEBOARD','whiteboard',NULL,'read',1,'Lesen','Whiteboard','Whiteboard','Kann Whiteboards anzeigen',1,'2025-10-25 06:57:21','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (257,'WRITE_WHITEBOARD','whiteboard',NULL,'write',2,'Schreiben','Whiteboard','Whiteboard','Kann Whiteboards erstellen und bearbeiten',1,'2025-10-25 06:57:21','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (258,'DELETE_WHITEBOARD','whiteboard',NULL,'delete',4,'Löschen','Whiteboard','Whiteboard','Kann Whiteboards löschen',1,'2025-10-25 06:57:21','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (259,'VIEW_WHITEBOARD','whiteboard',NULL,'view',16,'Anzeigen','Whiteboard','Whiteboard','Menüpunkt: Whiteboard anzeigen',1,'2025-10-25 06:57:21','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (260,'LOG_ACCESS','access',NULL,'log',NULL,'Zugriff','Logs','Logs','Kann System-Logs einsehen',1,'2025-10-25 06:57:21','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (261,'VIEW_ACCESS_LOGS','access','logs','view',16,'Anzeigen','Logs','Logs','Menüpunkt: Logs anzeigen',1,'2025-10-25 06:57:21','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (262,'ADMIN_REPORTS','admin','report','admin',8,'Verwalten','Admin_Berichte','Admin_Berichte','Kann Berichtsfelder und -kategorien verwalten',1,'2025-10-25 06:57:21','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (263,'READ_AUTHORITY_FIELDS','authority','fields','read',1,'Lesen','Authority Felder','Authority Felder','Kann Authority-spezifische Felder anzeigen',1,'2025-10-25 06:57:21','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (264,'WRITE_AUTHORITY_FIELDS','authority','fields','write',2,'Schreiben','Authority Felder','Authority Felder','Kann Authority-spezifische Felder bearbeiten',1,'2025-10-25 06:57:21','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (265,'ADMIN_AUTHORITY_FIELDS','admin','fields','admin',8,'Verwalten','Authority Felder','Authority Felder','Kann Authority-spezifische Felder verwalten',1,'2025-10-25 06:57:21','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (267,'READ_REPORTDEPARTMENT','report',NULL,'read',1,'Lesen','Berichte','Berichte','Kann Berichtskategorien/Abteilungen anzeigen',1,'2025-10-25 06:57:21','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (268,'WRITE_REPORTDEPARTMENT','report',NULL,'write',2,'Schreiben','Berichte','Berichte','Kann Berichtskategorien/Abteilungen erstellen und\r\n  bearbeiten',1,'2025-10-25 06:57:21','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (269,'DELETE_REPORTDEPARTMENT','report',NULL,'delete',4,'Löschen','Berichte','Berichte','Kann Berichtskategorien/Abteilungen löschen',1,'2025-10-25 06:57:21','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (270,'ADMIN_READ_WEATHER','admin','weather','read',1,'Lesen','Admin_Weather','Admin_Weather','Inhalt: Admin - Wetter anzeigen',1,'2025-10-25 06:57:21','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (271,'ADMIN_WRITE_WEATHER','admin','weather','write',2,'Schreiben','Admin_Weather','Admin_Weather','Wetter-Einträge bearbeiten',1,'2025-10-25 06:57:21','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (272,'READ_SETTINGS','settings',NULL,'read',1,'Lesen','Einstellungen','Einstellungen','Kann Einstellungen anzeigen',1,'2025-10-25 06:57:21','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (273,'WRITE_SETTINGS','settings',NULL,'write',2,'Schreiben','Einstellungen','Einstellungen','Kann Einstellungen bearbeiten',1,'2025-10-25 06:57:21','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (280,'READ_BLACKBOARD_AREA','blackboard','area','read',1,'Lesen','Schwarzes Brett Bereiche','Schwarzes Brett','Kann Blackboard-Bereiche lesen',1,'2025-10-29 19:38:10','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (281,'WRITE_BLACKBOARD_AREA','blackboard','area','write',2,'Schreiben','Schwarzes Brett Bereiche','Schwarzes Brett','Kann Einträge in Blackboard-Bereichen erstellen/bearbeiten',1,'2025-10-29 19:38:10','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (282,'DELETE_BLACKBOARD_AREA','blackboard','area','delete',4,'Löschen','Schwarzes Brett Bereiche','Schwarzes Brett','Kann Einträge in Blackboard-Bereichen löschen',1,'2025-10-29 19:38:10','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (283,'ADMIN_BLACKBOARD_AREAS','admin','blackboard','admin',8,'Verwalten','Schwarzes Brett Bereiche','Schwarzes Brett Bereiche','Kann Blackboard-Bereiche verwalten (erstellen, bearbeiten, Rechte vergeben)',1,'2025-10-29 19:38:10','2025-11-03 16:09:02');
INSERT INTO `kdd_permissions` (`id`, `name`, `module`, `sub_module`, `action`, `bitmask_value`, `action_display`, `module_display`, `display_group`, `description`, `authority_id`, `created_at`, `updated_at`) VALUES (284,'VIEW_BLACKBOARD_AREA','blackboard','area','view',16,'Anzeigen','Schwarzes Brett Bereiche','Schwarzes Brett','Menüpunkt: Schwarzes Brett Bereiche anzeigen',1,'2025-10-31 08:30:59','2025-11-03 16:09:02');

-- kdd_role_permissions: Welche Rolle welches Recht traegt.
INSERT INTO `kdd_role_permissions` (`role_id`, `permission_id`, `authority_id`) VALUES (1,1,1);
INSERT INTO `kdd_role_permissions` (`role_id`, `permission_id`, `authority_id`) VALUES (1,246,1);
INSERT INTO `kdd_role_permissions` (`role_id`, `permission_id`, `authority_id`) VALUES (1,247,1);
INSERT INTO `kdd_role_permissions` (`role_id`, `permission_id`, `authority_id`) VALUES (1,248,1);
INSERT INTO `kdd_role_permissions` (`role_id`, `permission_id`, `authority_id`) VALUES (1,249,1);
INSERT INTO `kdd_role_permissions` (`role_id`, `permission_id`, `authority_id`) VALUES (1,280,1);
INSERT INTO `kdd_role_permissions` (`role_id`, `permission_id`, `authority_id`) VALUES (1,281,1);
INSERT INTO `kdd_role_permissions` (`role_id`, `permission_id`, `authority_id`) VALUES (1,282,1);
INSERT INTO `kdd_role_permissions` (`role_id`, `permission_id`, `authority_id`) VALUES (1,283,1);
INSERT INTO `kdd_role_permissions` (`role_id`, `permission_id`, `authority_id`) VALUES (1,284,1);
INSERT INTO `kdd_role_permissions` (`role_id`, `permission_id`, `authority_id`) VALUES (2,115,1);
INSERT INTO `kdd_role_permissions` (`role_id`, `permission_id`, `authority_id`) VALUES (20,1,1);
INSERT INTO `kdd_role_permissions` (`role_id`, `permission_id`, `authority_id`) VALUES (20,193,1);

-- kdd_global_settings: Grundeinstellungen. Was die Anlage betrifft, steht leer.
INSERT INTO `kdd_global_settings` (`id`, `key_name`, `value`, `authority_id`) VALUES (1,'siteName','K-Systems',1);
INSERT INTO `kdd_global_settings` (`id`, `key_name`, `value`, `authority_id`) VALUES (2,'siteLogo','',1);
INSERT INTO `kdd_global_settings` (`id`, `key_name`, `value`, `authority_id`) VALUES (3,'employeeNavigation','Mitarbeiter',1);
INSERT INTO `kdd_global_settings` (`id`, `key_name`, `value`, `authority_id`) VALUES (5,'extinguisherNr','',1);
INSERT INTO `kdd_global_settings` (`id`, `key_name`, `value`, `authority_id`) VALUES (6,'marquee_dashboard','',1);
INSERT INTO `kdd_global_settings` (`id`, `key_name`, `value`, `authority_id`) VALUES (7,'companyName','K-Systems',1);
INSERT INTO `kdd_global_settings` (`id`, `key_name`, `value`, `authority_id`) VALUES (8,'darkMode','true',1);
INSERT INTO `kdd_global_settings` (`id`, `key_name`, `value`, `authority_id`) VALUES (9,'primaryColor','#2B62C4',1);
INSERT INTO `kdd_global_settings` (`id`, `key_name`, `value`, `authority_id`) VALUES (10,'secondaryColor','#343541',1);
INSERT INTO `kdd_global_settings` (`id`, `key_name`, `value`, `authority_id`) VALUES (11,'accentColor','#10B981',1);
INSERT INTO `kdd_global_settings` (`id`, `key_name`, `value`, `authority_id`) VALUES (12,'backgroundColor','#111723',1);
INSERT INTO `kdd_global_settings` (`id`, `key_name`, `value`, `authority_id`) VALUES (13,'surfaceColor','#111827',1);
INSERT INTO `kdd_global_settings` (`id`, `key_name`, `value`, `authority_id`) VALUES (14,'tertiaryColor','#444654',1);
INSERT INTO `kdd_global_settings` (`id`, `key_name`, `value`, `authority_id`) VALUES (15,'infoColor','#2b62c4',1);
INSERT INTO `kdd_global_settings` (`id`, `key_name`, `value`, `authority_id`) VALUES (16,'successColor','#1b7a4f',1);
INSERT INTO `kdd_global_settings` (`id`, `key_name`, `value`, `authority_id`) VALUES (17,'warningColor','#8f590b',1);
INSERT INTO `kdd_global_settings` (`id`, `key_name`, `value`, `authority_id`) VALUES (18,'errorColor','#c0322b',1);
INSERT INTO `kdd_global_settings` (`id`, `key_name`, `value`, `authority_id`) VALUES (19,'onPrimaryColor','#FFFFFF',1);
INSERT INTO `kdd_global_settings` (`id`, `key_name`, `value`, `authority_id`) VALUES (20,'onSecondaryColor','#FFFFFF',1);
INSERT INTO `kdd_global_settings` (`id`, `key_name`, `value`, `authority_id`) VALUES (21,'onAccentColor','#121212',1);
INSERT INTO `kdd_global_settings` (`id`, `key_name`, `value`, `authority_id`) VALUES (22,'onBackgroundColor','#FFFFFF',1);
INSERT INTO `kdd_global_settings` (`id`, `key_name`, `value`, `authority_id`) VALUES (23,'onSurfaceColor','#FFFFFF',1);
INSERT INTO `kdd_global_settings` (`id`, `key_name`, `value`, `authority_id`) VALUES (24,'onSuccessColor','#FFFFFF',1);
INSERT INTO `kdd_global_settings` (`id`, `key_name`, `value`, `authority_id`) VALUES (25,'onInfoColor','#FFFFFF',1);
INSERT INTO `kdd_global_settings` (`id`, `key_name`, `value`, `authority_id`) VALUES (26,'onWarningColor','#121212',1);
INSERT INTO `kdd_global_settings` (`id`, `key_name`, `value`, `authority_id`) VALUES (27,'onErrorColor','#FFFFFF',1);
INSERT INTO `kdd_global_settings` (`id`, `key_name`, `value`, `authority_id`) VALUES (28,'borderRadius','8',1);
INSERT INTO `kdd_global_settings` (`id`, `key_name`, `value`, `authority_id`) VALUES (29,'enableGradients','true',1);
INSERT INTO `kdd_global_settings` (`id`, `key_name`, `value`, `authority_id`) VALUES (30,'defaultLanguage','de',1);
INSERT INTO `kdd_global_settings` (`id`, `key_name`, `value`, `authority_id`) VALUES (31,'dateFormat','DD.MM.YYYY',1);
INSERT INTO `kdd_global_settings` (`id`, `key_name`, `value`, `authority_id`) VALUES (32,'defaultStartPage','/dashboard',1);
INSERT INTO `kdd_global_settings` (`id`, `key_name`, `value`, `authority_id`) VALUES (33,'sessionTimeout','30',1);
INSERT INTO `kdd_global_settings` (`id`, `key_name`, `value`, `authority_id`) VALUES (34,'enableNotifications','true',1);
INSERT INTO `kdd_global_settings` (`id`, `key_name`, `value`, `authority_id`) VALUES (35,'enableUserTracking','true',1);
INSERT INTO `kdd_global_settings` (`id`, `key_name`, `value`, `authority_id`) VALUES (36,'pdfHeaderLogo','',1);
INSERT INTO `kdd_global_settings` (`id`, `key_name`, `value`, `authority_id`) VALUES (37,'pdfFooterText','',1);
INSERT INTO `kdd_global_settings` (`id`, `key_name`, `value`, `authority_id`) VALUES (38,'exportDateFormat','DD.MM.YYYY',1);
INSERT INTO `kdd_global_settings` (`id`, `key_name`, `value`, `authority_id`) VALUES (39,'defaultExportFormat','PDF',1);
INSERT INTO `kdd_global_settings` (`id`, `key_name`, `value`, `authority_id`) VALUES (40,'supportEmail','',1);
INSERT INTO `kdd_global_settings` (`id`, `key_name`, `value`, `authority_id`) VALUES (41,'supportPhone','',1);
INSERT INTO `kdd_global_settings` (`id`, `key_name`, `value`, `authority_id`) VALUES (42,'helpPageContent','',1);

-- kdd_dashboard_templates: Die sechs Dashboard-Vorlagen.
INSERT INTO `kdd_dashboard_templates` (`id`, `template_key`, `name`, `description`, `icon`, `required_permissions`, `layout`, `widgets`, `category`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES (36,'dispatcher','Dispatcher Dashboard','Optimized for dispatch operations with crew, vehicle, and map widgets','mdi-truck-fast','[\"READ_DISPATCH\", \"READ_VEHICLE\", \"READ_CREW\"]','[{\"i\": \"quick-dispatch\", \"x\": 0, \"y\": 0, \"w\": 12, \"h\": 4}, {\"i\": \"vehicle-status\", \"x\": 0, \"y\": 4, \"w\": 4, \"h\": 5}, {\"i\": \"calendar-widget\", \"x\": 4, \"y\": 4, \"w\": 4, \"h\": 6}, {\"i\": \"weather-widget\", \"x\": 8, \"y\": 4, \"w\": 4, \"h\": 5}, {\"i\": \"blackboard-feed\", \"x\": 0, \"y\": 10, \"w\": 8, \"h\": 5}, {\"i\": \"active-users\", \"x\": 8, \"y\": 10, \"w\": 4, \"h\": 5}]','{\"quick-dispatch\": {\"type\": \"quick-dispatch\", \"title\": \"Quick Dispatch\", \"refreshInterval\": 30}, \"vehicle-status\": {\"type\": \"vehicle-status\", \"title\": \"Vehicle Status\", \"showMap\": true}, \"calendar-widget\": {\"type\": \"calendar-widget\", \"title\": \"Calendar\", \"daysAhead\": 7}, \"weather-widget\": {\"type\": \"weather-widget\", \"title\": \"Weather\", \"location\": \"authority\"}, \"blackboard-feed\": {\"type\": \"blackboard-feed\", \"title\": \"Announcements\", \"maxItems\": 5}, \"active-users\": {\"type\": \"active-users\", \"title\": \"Active Users\", \"showStatus\": true}}','operational',1,1,'2025-10-29 13:37:46','2025-10-29 13:37:46');
INSERT INTO `kdd_dashboard_templates` (`id`, `template_key`, `name`, `description`, `icon`, `required_permissions`, `layout`, `widgets`, `category`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES (37,'hr_manager','HR Manager Dashboard','Employee management, vacations, applications, and training overview','mdi-account-tie','[\"READ_EMPLOYEE\"]','[{\"i\": \"employee-overview\", \"x\": 0, \"y\": 0, \"w\": 6, \"h\": 6}, {\"i\": \"vacation-calendar\", \"x\": 6, \"y\": 0, \"w\": 6, \"h\": 6}, {\"i\": \"active-users\", \"x\": 0, \"y\": 6, \"w\": 4, \"h\": 5}, {\"i\": \"recent-documents\", \"x\": 4, \"y\": 6, \"w\": 8, \"h\": 5}]','{\"employee-overview\": {\"type\": \"employee-overview\", \"title\": \"Employee Overview\", \"chartType\": \"donut\"}, \"vacation-calendar\": {\"type\": \"vacation-calendar\", \"title\": \"Vacation Calendar\", \"showTimeline\": true}, \"active-users\": {\"type\": \"active-users\", \"title\": \"Active Users\"}, \"recent-documents\": {\"type\": \"recent-documents\", \"title\": \"Recent Documents\", \"maxItems\": 5}}','management',2,1,'2025-10-29 13:37:46','2025-10-29 13:37:46');
INSERT INTO `kdd_dashboard_templates` (`id`, `template_key`, `name`, `description`, `icon`, `required_permissions`, `layout`, `widgets`, `category`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES (38,'report_manager','Report Manager Dashboard','Report analytics, statistics, and quick report creation','mdi-file-chart','[\"READ_REPORT\"]','[{\"i\": \"report-analytics\", \"x\": 0, \"y\": 0, \"w\": 8, \"h\": 5}, {\"i\": \"open-reports\", \"x\": 8, \"y\": 0, \"w\": 4, \"h\": 5}, {\"i\": \"calendar-widget\", \"x\": 0, \"y\": 5, \"w\": 6, \"h\": 6}, {\"i\": \"messages-feed\", \"x\": 6, \"y\": 5, \"w\": 6, \"h\": 6}]','{\"report-analytics\": {\"type\": \"report-analytics\", \"title\": \"Report Analytics\", \"groupBy\": \"category\"}, \"open-reports\": {\"type\": \"open-reports\", \"title\": \"Open Reports\", \"filterBy\": \"status\"}, \"calendar-widget\": {\"type\": \"calendar-widget\", \"title\": \"Calendar\"}, \"messages-feed\": {\"type\": \"messages-feed\", \"title\": \"Messages\", \"maxItems\": 5}}','management',3,1,'2025-10-29 13:37:46','2025-10-29 13:37:46');
INSERT INTO `kdd_dashboard_templates` (`id`, `template_key`, `name`, `description`, `icon`, `required_permissions`, `layout`, `widgets`, `category`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES (39,'member','Member Dashboard','Personal dashboard with calendar, messages, todos, and weather','mdi-account','[\"CAN_LOGIN\"]','[{\"i\": \"welcome-banner\", \"x\": 0, \"y\": 0, \"w\": 12, \"h\": 3}, {\"i\": \"calendar-widget\", \"x\": 0, \"y\": 3, \"w\": 6, \"h\": 6}, {\"i\": \"my-vacations\", \"x\": 6, \"y\": 3, \"w\": 6, \"h\": 6}, {\"i\": \"messages-feed\", \"x\": 0, \"y\": 9, \"w\": 6, \"h\": 6}, {\"i\": \"todo-list\", \"x\": 6, \"y\": 9, \"w\": 6, \"h\": 6}, {\"i\": \"weather-widget\", \"x\": 0, \"y\": 15, \"w\": 12, \"h\": 5}]','{\"welcome-banner\": {\"type\": \"welcome-banner\", \"title\": \"Welcome\", \"showStats\": true}, \"calendar-widget\": {\"type\": \"calendar-widget\", \"title\": \"My Calendar\", \"daysAhead\": 7}, \"my-vacations\": {\"type\": \"my-vacations\", \"title\": \"My Vacations\", \"showHistory\": false}, \"messages-feed\": {\"type\": \"messages-feed\", \"title\": \"Messages\", \"maxItems\": 10}, \"todo-list\": {\"type\": \"todo-list\", \"title\": \"My Tasks\", \"showCompleted\": false}, \"weather-widget\": {\"type\": \"weather-widget\", \"title\": \"Weather\", \"showForecast\": true}}','personal',4,1,'2025-10-29 13:37:46','2025-10-29 13:37:46');
INSERT INTO `kdd_dashboard_templates` (`id`, `template_key`, `name`, `description`, `icon`, `required_permissions`, `layout`, `widgets`, `category`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES (40,'employee','Employee Dashboard','Typical employee dashboard with crew info, announcements, calendar, and personal widgets','mdi-account-hard-hat','[\"CAN_LOGIN\"]','[{\"i\": \"welcome-banner\", \"x\": 0, \"y\": 0, \"w\": 12, \"h\": 3}, {\"i\": \"my-crew\", \"x\": 0, \"y\": 3, \"w\": 6, \"h\": 6}, {\"i\": \"blackboard-feed\", \"x\": 6, \"y\": 3, \"w\": 6, \"h\": 5}, {\"i\": \"my-vacations\", \"x\": 0, \"y\": 9, \"w\": 6, \"h\": 6}, {\"i\": \"calendar-widget\", \"x\": 6, \"y\": 9, \"w\": 6, \"h\": 6}, {\"i\": \"messages-feed\", \"x\": 0, \"y\": 15, \"w\": 6, \"h\": 6}, {\"i\": \"todo-list\", \"x\": 6, \"y\": 15, \"w\": 6, \"h\": 6}, {\"i\": \"weather-widget\", \"x\": 0, \"y\": 21, \"w\": 12, \"h\": 5}]','{\"welcome-banner\": {\"type\": \"welcome-banner\", \"title\": \"Welcome\", \"showStats\": true, \"showAvatar\": false}, \"my-crew\": {\"type\": \"my-crew\", \"title\": \"My Crew\", \"showMembers\": true}, \"blackboard-feed\": {\"type\": \"blackboard-feed\", \"title\": \"Announcements\", \"boardType\": \"employee\", \"maxItems\": 1}, \"my-vacations\": {\"type\": \"my-vacations\", \"title\": \"My Vacations\", \"showHistory\": false}, \"calendar-widget\": {\"type\": \"calendar-widget\", \"title\": \"My Calendar\", \"daysAhead\": 7}, \"messages-feed\": {\"type\": \"messages-feed\", \"title\": \"Messages\", \"maxItems\": 10}, \"todo-list\": {\"type\": \"todo-list\", \"title\": \"My Tasks\", \"showCompleted\": false}, \"weather-widget\": {\"type\": \"weather-widget\", \"title\": \"Weather\", \"showForecast\": true}}','personal',4,1,'2025-10-29 13:37:46','2026-09-10 19:54:52');
INSERT INTO `kdd_dashboard_templates` (`id`, `template_key`, `name`, `description`, `icon`, `required_permissions`, `layout`, `widgets`, `category`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES (41,'admin','Admin Dashboard','System administration with user activity, health monitoring, and logs','mdi-shield-crown','[\"ADMIN_READ_USERS\", \"SYSTEM_ADMIN\"]','[{\"i\": \"system-health\", \"x\": 0, \"y\": 0, \"w\": 12, \"h\": 4}, {\"i\": \"user-activity\", \"x\": 0, \"y\": 4, \"w\": 6, \"h\": 6}, {\"i\": \"recent-logs\", \"x\": 6, \"y\": 4, \"w\": 6, \"h\": 6}]','{\"system-health\": {\"type\": \"system-health\", \"title\": \"System Health\", \"showMetrics\": true}, \"user-activity\": {\"type\": \"user-activity\", \"title\": \"User Activity\", \"period\": \"24h\"}, \"recent-logs\": {\"type\": \"recent-logs\", \"title\": \"Recent Logs\", \"maxItems\": 10, \"filterLevel\": \"all\"}}','administration',6,1,'2025-10-29 13:37:46','2025-10-29 13:37:46');

-- schema_migrations: Stand der Migrationen zu dieser Datei.
INSERT INTO `schema_migrations` (`id`, `filename`, `applied_at`) VALUES (1,'0001_add_widget_state_column.sql','2025-06-01 04:10:08');
INSERT INTO `schema_migrations` (`id`, `filename`, `applied_at`) VALUES (2,'0002_fix_desktop_settings_schema.sql','2025-06-01 04:10:08');
INSERT INTO `schema_migrations` (`id`, `filename`, `applied_at`) VALUES (3,'0003_add_quacklejump_tables.sql','2025-06-01 04:10:08');
INSERT INTO `schema_migrations` (`id`, `filename`, `applied_at`) VALUES (4,'0004_add_quacklejump_foreign_keys.sql','2025-06-01 04:10:08');
INSERT INTO `schema_migrations` (`id`, `filename`, `applied_at`) VALUES (5,'0005_fix_roles_unique_constraint.sql','2025-06-03 19:03:19');
INSERT INTO `schema_migrations` (`id`, `filename`, `applied_at`) VALUES (6,'0006_add_authority_branding.sql','2025-09-29 06:27:52');
INSERT INTO `schema_migrations` (`id`, `filename`, `applied_at`) VALUES (7,'0007_add_cheatsheet_system.sql','2025-10-14 08:26:44');
INSERT INTO `schema_migrations` (`id`, `filename`, `applied_at`) VALUES (8,'0008_add_grid_layout_to_cheatsheet.sql','2025-10-14 18:56:35');
INSERT INTO `schema_migrations` (`id`, `filename`, `applied_at`) VALUES (9,'0009_fix_inconsistent_grid_positions.sql','2025-10-15 04:49:48');
INSERT INTO `schema_migrations` (`id`, `filename`, `applied_at`) VALUES (10,'0010_reset_all_grid_positions.sql','2025-10-15 04:56:23');
INSERT INTO `schema_migrations` (`id`, `filename`, `applied_at`) VALUES (11,'0011_add_versioning_system.sql','2025-10-15 17:01:40');
INSERT INTO `schema_migrations` (`id`, `filename`, `applied_at`) VALUES (12,'0012_add_draft_status_system.sql','2025-10-15 19:37:29');
INSERT INTO `schema_migrations` (`id`, `filename`, `applied_at`) VALUES (13,'0013_website_templates_sections_news.sql','2025-10-16 12:28:36');
INSERT INTO `schema_migrations` (`id`, `filename`, `applied_at`) VALUES (14,'0014_merge_settings_authority_branding.sql','2025-10-22 04:49:22');
INSERT INTO `schema_migrations` (`id`, `filename`, `applied_at`) VALUES (15,'0015_mail_system_complete.sql','2025-10-22 11:28:41');
INSERT INTO `schema_migrations` (`id`, `filename`, `applied_at`) VALUES (16,'0016_enable_mail_feature.sql','2025-10-22 13:41:04');
INSERT INTO `schema_migrations` (`id`, `filename`, `applied_at`) VALUES (17,'0017_mail_access_rights.sql','2025-10-22 15:07:09');
INSERT INTO `schema_migrations` (`id`, `filename`, `applied_at`) VALUES (18,'2025_01_03_rename_akten_modules.sql','2025-11-03 14:49:58');
INSERT INTO `schema_migrations` (`id`, `filename`, `applied_at`) VALUES (20,'2025_01_03_fix_module_display_grouping.sql','2025-11-03 16:22:26');
INSERT INTO `schema_migrations` (`id`, `filename`, `applied_at`) VALUES (21,'2025_01_03_populate_display_groups.sql','2025-11-03 16:22:26');
INSERT INTO `schema_migrations` (`id`, `filename`, `applied_at`) VALUES (22,'2025_01_03_create_sessions_table.sql','2025-11-03 17:24:05');
INSERT INTO `schema_migrations` (`id`, `filename`, `applied_at`) VALUES (26,'2025_01_23_add_document_view_types.sql','2025-11-23 04:46:49');
INSERT INTO `schema_migrations` (`id`, `filename`, `applied_at`) VALUES (27,'2025_11_29_add_employee_absent_field.sql','2025-11-29 20:25:18');
INSERT INTO `schema_migrations` (`id`, `filename`, `applied_at`) VALUES (29,'2026_09_10_dashboard_banner_hoehe.sql','2026-09-10 19:54:52');
INSERT INTO `schema_migrations` (`id`, `filename`, `applied_at`) VALUES (30,'20251028_create_dashboard_layout_table.sql','2026-09-11 06:56:36');
INSERT INTO `schema_migrations` (`id`, `filename`, `applied_at`) VALUES (31,'20251028_insert_dashboard_templates.sql','2026-09-11 06:56:36');
INSERT INTO `schema_migrations` (`id`, `filename`, `applied_at`) VALUES (32,'20251029_rename_blackboard_admin_to_all.sql','2026-09-11 06:56:36');
INSERT INTO `schema_migrations` (`id`, `filename`, `applied_at`) VALUES (33,'0000_altmigrationen_als_angewendet.sql','2026-09-11 06:56:36');

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
