-- =====================================================
-- Migration: Cheatsheet System
-- Description: Editable, multi-content cheatsheet with authority-specific data
-- Date: 2025-01-21
-- Author: K-Systems
-- =====================================================

-- Note: Database connection is already established by migration system
-- No USE statement needed - causes permission errors

-- =====================================================
-- Table: cheatsheet_categories
-- =====================================================
CREATE TABLE IF NOT EXISTS kdd_cheatsheet_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    authority_id INT NOT NULL,

    -- Basis-Info
    name VARCHAR(100) NOT NULL,
    description TEXT,

    -- Darstellung
    icon VARCHAR(50) DEFAULT 'mdi-information',
    color VARCHAR(20) DEFAULT 'primary',
    content_type ENUM('table', 'text', 'image', 'mixed', 'grid') DEFAULT 'table',

    -- Layout
    width ENUM('full', 'half', 'third', 'quarter') DEFAULT 'third',
    height ENUM('auto', 'small', 'medium', 'large') DEFAULT 'medium',

    -- Optionen
    order_index INT DEFAULT 0,
    enabled BOOLEAN DEFAULT TRUE,
    collapsible BOOLEAN DEFAULT FALSE,
    default_collapsed BOOLEAN DEFAULT FALSE,

    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (authority_id) REFERENCES kdd_authorities(id) ON DELETE CASCADE,
    INDEX idx_authority_order (authority_id, order_index),
    INDEX idx_enabled (enabled)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Table: cheatsheet_items
-- =====================================================
CREATE TABLE IF NOT EXISTS kdd_cheatsheet_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    authority_id INT NOT NULL,

    -- Simple Content (for table/grid types)
    title VARCHAR(255),
    description TEXT,

    -- Advanced Content (for text/image types)
    content_text TEXT,
    image_url VARCHAR(500),
    image_caption TEXT,

    -- Mixed Content (JSON for complex layouts)
    layout_json JSON,

    -- Optionen
    order_index INT DEFAULT 0,
    enabled BOOLEAN DEFAULT TRUE,

    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (category_id) REFERENCES kdd_cheatsheet_categories(id) ON DELETE CASCADE,
    FOREIGN KEY (authority_id) REFERENCES kdd_authorities(id) ON DELETE CASCADE,
    INDEX idx_category_order (category_id, order_index),
    INDEX idx_authority (authority_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Permissions
-- =====================================================
INSERT INTO kdd_permissions (name, name_alias, site, description, authority_id) VALUES
('READ_CHEATSHEET', 'Cheatsheet ansehen', 'Cheatsheet', 'View cheatsheet reference', 1),
('ADMIN_CHEATSHEET', 'Cheatsheet verwalten', 'Cheatsheet', 'Manage cheatsheet content', 1)
ON DUPLICATE KEY UPDATE name=name;

-- =====================================================
-- Seed Data: Default Categories for ALL Authorities
-- =====================================================
-- Note: Only seed if not already present

-- Category 1: 10-Codes
INSERT INTO kdd_cheatsheet_categories (authority_id, name, icon, color, content_type, width, height, order_index, description)
SELECT
    a.id,
    '10-Codes',
    'mdi-police-badge',
    'primary',
    'table',
    'third',
    'medium',
    1,
    'Funkspruch-Codes für Einsatzkommunikation'
FROM kdd_authorities a
WHERE NOT EXISTS (
    SELECT 1 FROM kdd_cheatsheet_categories
    WHERE authority_id = a.id AND name = '10-Codes'
);

-- Category 2: Notrufnummern
INSERT INTO kdd_cheatsheet_categories (authority_id, name, icon, color, content_type, width, height, order_index, description)
SELECT
    a.id,
    'Notrufnummern',
    'mdi-phone-in-talk',
    'error',
    'table',
    'third',
    'medium',
    2,
    'Wichtige Kontaktnummern für Notdienste'
FROM kdd_authorities a
WHERE NOT EXISTS (
    SELECT 1 FROM kdd_cheatsheet_categories
    WHERE authority_id = a.id AND name = 'Notrufnummern'
);

-- Category 3: Response Codes
INSERT INTO kdd_cheatsheet_categories (authority_id, name, icon, color, content_type, width, height, order_index, description)
SELECT
    a.id,
    'Response Codes',
    'mdi-alert-box',
    'warning',
    'table',
    'quarter',
    'small',
    3,
    'Einsatz-Prioritätscodes'
FROM kdd_authorities a
WHERE NOT EXISTS (
    SELECT 1 FROM kdd_cheatsheet_categories
    WHERE authority_id = a.id AND name = 'Response Codes'
);

-- Category 4: Fahrzeugtypen
INSERT INTO kdd_cheatsheet_categories (authority_id, name, icon, color, content_type, width, height, order_index, description)
SELECT
    a.id,
    'Fahrzeugtypen',
    'mdi-fire-truck',
    'success',
    'table',
    'quarter',
    'small',
    4,
    'Übersicht Einsatzfahrzeuge und deren Zweck'
FROM kdd_authorities a
WHERE NOT EXISTS (
    SELECT 1 FROM kdd_cheatsheet_categories
    WHERE authority_id = a.id AND name = 'Fahrzeugtypen'
);

-- Category 5: Arbeitsgruppen
INSERT INTO kdd_cheatsheet_categories (authority_id, name, icon, color, content_type, width, height, order_index, description)
SELECT
    a.id,
    'Arbeitsgruppen',
    'mdi-account-tie',
    'purple',
    'table',
    'quarter',
    'small',
    5,
    'Organisationsstruktur und Ränge'
FROM kdd_authorities a
WHERE NOT EXISTS (
    SELECT 1 FROM kdd_cheatsheet_categories
    WHERE authority_id = a.id AND name = 'Arbeitsgruppen'
);

-- Category 6: NATO-Alphabet
INSERT INTO kdd_cheatsheet_categories (authority_id, name, icon, color, content_type, width, height, order_index, description)
SELECT
    a.id,
    'NATO-Alphabet',
    'mdi-alphabetical',
    'cyan',
    'grid',
    'quarter',
    'medium',
    6,
    'Buchstabieralphabet für Funkkommunikation'
FROM kdd_authorities a
WHERE NOT EXISTS (
    SELECT 1 FROM kdd_cheatsheet_categories
    WHERE authority_id = a.id AND name = 'NATO-Alphabet'
);

-- Category 7: Gebäudeschema
INSERT INTO kdd_cheatsheet_categories (authority_id, name, icon, color, content_type, width, height, order_index, description)
SELECT
    a.id,
    'Gebäudeschema',
    'mdi-map-marker-radius',
    'info',
    'image',
    'third',
    'medium',
    7,
    'Orientierung bei Gebäudeeinsätzen'
FROM kdd_authorities a
WHERE NOT EXISTS (
    SELECT 1 FROM kdd_cheatsheet_categories
    WHERE authority_id = a.id AND name = 'Gebäudeschema'
);

-- =====================================================
-- Seed Data: Items for Categories
-- =====================================================

-- Items for 10-Codes
INSERT INTO kdd_cheatsheet_items (category_id, authority_id, title, description, order_index)
SELECT
    c.id,
    c.authority_id,
    code_data.title,
    code_data.description,
    code_data.order_index
FROM kdd_cheatsheet_categories c
CROSS JOIN (
    SELECT '10-3' as title, 'Funkspruch wiederholen' as description, 1 as order_index
    UNION ALL SELECT '10-4', 'Verstanden', 2
    UNION ALL SELECT '10-6 | Check', 'Nicht Verfügbar / Pause', 3
    UNION ALL SELECT '10-7', 'Außer Dienst', 4
    UNION ALL SELECT '10-8 | Funkcheck', 'Im Dienst', 5
    UNION ALL SELECT '10-10', 'Bewegungsfahrt', 6
    UNION ALL SELECT '10-20', 'Standortabfrage / Angabe', 7
    UNION ALL SELECT '10-30', 'Transport Material / Personal', 8
    UNION ALL SELECT '10-40', 'Stiller Alarm', 9
    UNION ALL SELECT '10-44', 'Ambulance benötigt', 10
    UNION ALL SELECT '10-45', 'Code Blue', 11
    UNION ALL SELECT '10-60', 'Technischer Rettungseinsatz', 12
    UNION ALL SELECT '10-61', 'Verkehrsunfall', 13
    UNION ALL SELECT '10-75', 'Kleinbrand', 14
    UNION ALL SELECT '10-76', 'Großbrand', 15
    UNION ALL SELECT '10-80', 'Amtshilfe', 16
    UNION ALL SELECT '10-90', 'Naturkatastrophe', 17
    UNION ALL SELECT '10-91', 'Bombeneinsatz', 18
    UNION ALL SELECT '10-92', 'Hazmat inkl. Bestätigung', 19
) code_data
WHERE c.name = '10-Codes'
AND NOT EXISTS (
    SELECT 1 FROM kdd_cheatsheet_items
    WHERE category_id = c.id AND title = code_data.title
);

-- Items for Notrufnummern
INSERT INTO kdd_cheatsheet_items (category_id, authority_id, title, description, order_index)
SELECT
    c.id,
    c.authority_id,
    number_data.title,
    number_data.description,
    number_data.order_index
FROM kdd_cheatsheet_categories c
CROSS JOIN (
    SELECT 'SAPD' as title, '911 | 921' as description, 1 as order_index
    UNION ALL SELECT 'LSMD', '912 | 922', 2
    UNION ALL SELECT 'LSFD', '913 | 923', 3
    UNION ALL SELECT 'SASP', '811 | 821', 4
    UNION ALL SELECT 'BCSO', '910 | 920', 5
    UNION ALL SELECT 'USMS', '550', 6
    UNION ALL SELECT 'DoJ StA', '511', 7
    UNION ALL SELECT 'DoJ Richter', '512', 8
    UNION ALL SELECT 'CASA', '611', 9
    UNION ALL SELECT 'Kanzlei', '623623', 10
    UNION ALL SELECT 'ACLS', '777777', 11
    UNION ALL SELECT 'Bennys', '200200', 12
    UNION ALL SELECT 'Flywheels', '200200', 13
    UNION ALL SELECT '2W1E', '686868', 14
    UNION ALL SELECT 'Eagle Motors', '666777', 15
) number_data
WHERE c.name = 'Notrufnummern'
AND NOT EXISTS (
    SELECT 1 FROM kdd_cheatsheet_items
    WHERE category_id = c.id AND title = number_data.title
);

-- Items for Response Codes
INSERT INTO kdd_cheatsheet_items (category_id, authority_id, title, description, order_index)
SELECT
    c.id,
    c.authority_id,
    response_data.title,
    response_data.description,
    response_data.order_index
FROM kdd_cheatsheet_categories c
CROSS JOIN (
    SELECT 'Code 1' as title, 'Kein akuter Notfall' as description, 1 as order_index
    UNION ALL SELECT 'Code 2', 'Stille und schnellere Anfahrt', 2
    UNION ALL SELECT 'Code 3', 'Kritisch / Unbekannt', 3
    UNION ALL SELECT 'Code 4', 'Einsatz beendet', 4
    UNION ALL SELECT 'Code 6', 'Am Einsatzort angekommen', 5
) response_data
WHERE c.name = 'Response Codes'
AND NOT EXISTS (
    SELECT 1 FROM kdd_cheatsheet_items
    WHERE category_id = c.id AND title = response_data.title
);

-- Items for Fahrzeugtypen
INSERT INTO kdd_cheatsheet_items (category_id, authority_id, title, description, order_index)
SELECT
    c.id,
    c.authority_id,
    vehicle_data.title,
    vehicle_data.description,
    vehicle_data.order_index
FROM kdd_cheatsheet_categories c
CROSS JOIN (
    SELECT 'Engine' as title, 'Löscheinsätze, Verkehrsunfälle, Innenangriff' as description, 1 as order_index
    UNION ALL SELECT 'Tower', 'Löscheinsätze, Innenangriff, Unterstützung Höhenrettung', 2
    UNION ALL SELECT 'Rescue', 'Taucheinsätze, Höhen und Tiefenrettung, Technische Hilfeleistung, Hazmat Einsätze', 3
    UNION ALL SELECT 'Fleet', 'Fleet3 = Flatbed, Fleet 1& 2 = Abschlepper', 4
    UNION ALL SELECT 'Firehawk', '3 = Löschheli, 1&2 = Rescue', 5
) vehicle_data
WHERE c.name = 'Fahrzeugtypen'
AND NOT EXISTS (
    SELECT 1 FROM kdd_cheatsheet_items
    WHERE category_id = c.id AND title = vehicle_data.title
);

-- Items for Arbeitsgruppen
INSERT INTO kdd_cheatsheet_items (category_id, authority_id, title, description, order_index)
SELECT
    c.id,
    c.authority_id,
    group_data.title,
    group_data.description,
    group_data.order_index
FROM kdd_cheatsheet_categories c
CROSS JOIN (
    SELECT 'Führung/Leitung' as title, 'Fire Chief, Assistant Chief' as description, 1 as order_index
    UNION ALL SELECT 'leit. Fachpersonal', 'Battalion Chief, General Secretary, Captain', 2
    UNION ALL SELECT 'Personal', 'Alle übrigen', 3
    UNION ALL SELECT 'Beamte', 'Jeder Angestellte', 4
) group_data
WHERE c.name = 'Arbeitsgruppen'
AND NOT EXISTS (
    SELECT 1 FROM kdd_cheatsheet_items
    WHERE category_id = c.id AND title = group_data.title
);

-- Items for NATO-Alphabet
INSERT INTO kdd_cheatsheet_items (category_id, authority_id, title, description, order_index)
SELECT
    c.id,
    c.authority_id,
    nato_data.title,
    nato_data.description,
    nato_data.order_index
FROM kdd_cheatsheet_categories c
CROSS JOIN (
    SELECT 'Alpha' as title, 'November' as description, 1 as order_index
    UNION ALL SELECT 'Bravo', 'Oscar', 2
    UNION ALL SELECT 'Charlie', 'Papa', 3
    UNION ALL SELECT 'Delta', 'Quebec', 4
    UNION ALL SELECT 'Echo', 'Romeo', 5
    UNION ALL SELECT 'Foxtrott', 'Sierra', 6
    UNION ALL SELECT 'Golf', 'Tango', 7
    UNION ALL SELECT 'Hotel', 'Uniform', 8
    UNION ALL SELECT 'India', 'Victor', 9
    UNION ALL SELECT 'Juliette', 'Whiskey', 10
    UNION ALL SELECT 'Kilo', 'X-Ray', 11
    UNION ALL SELECT 'Lima', 'Yankee', 12
    UNION ALL SELECT 'Mike', 'Zulu', 13
) nato_data
WHERE c.name = 'NATO-Alphabet'
AND NOT EXISTS (
    SELECT 1 FROM kdd_cheatsheet_items
    WHERE category_id = c.id AND title = nato_data.title
);

-- Items for Gebäudeschema
INSERT INTO kdd_cheatsheet_items (category_id, authority_id, image_url, image_caption, content_text, order_index)
SELECT
    c.id,
    c.authority_id,
    '/img/cheatsheet.png',
    'Gebäude-Orientierungsschema',
    'Alpha = Straßenseite (ggf. leichterer Zugang). Danach im Uhrzeigersinn Bravo, Charlie, Delta. Division X = Stockwerk (1 = EG, 0 = Keller, Roof = Dach)',
    1
FROM kdd_cheatsheet_categories c
WHERE c.name = 'Gebäudeschema'
AND NOT EXISTS (
    SELECT 1 FROM kdd_cheatsheet_items
    WHERE category_id = c.id AND order_index = 1
);
