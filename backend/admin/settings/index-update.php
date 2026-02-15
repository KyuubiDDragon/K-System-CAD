<?php
/**
 * Funktion zum Update der Einstellungen - Code-Auszug für Integration
 */

/**
 * Updated settings function to handle all settings fields
 */
function updateSettings(PDO $pdo, int $requestingUserId, string $authority): void {
    // Get data from JSON
    $data = getJsonRequestData();
    if (!$data) return;
    
    // Expect data within a 'settings' key in the JSON payload
    $settingsData = $data['settings'] ?? null;
    if (!is_array($settingsData)) {
    // Get authority ID
    global $decoded;
    $authority = $decoded->authority ?? null;
    global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
    if (!$authorityId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid authority']);
        exit;
    }

        http_response_code(400); echo json_encode(['error' => 'Invalid or missing settings data in request.']); return;
    }

    // Define which settings are allowed to be updated (all settings that can be edited)
    $allowedSettings = [
        // Grundeinstellungen
        'siteName', 'siteLogo', 'employeeNavigation', 'companyName',
        // Theme-Einstellungen
        'darkMode', 'primaryColor', 'secondaryColor', 'accentColor', 'backgroundColor', 
        'surfaceColor', 'tertiaryColor', 'infoColor', 'successColor', 'warningColor', 'errorColor',
        // Text-auf-Farben
        'onPrimaryColor', 'onSecondaryColor', 'onAccentColor', 'onBackgroundColor', 'onSurfaceColor', 
        'onSuccessColor', 'onInfoColor', 'onWarningColor', 'onErrorColor',
        // Weitere Theme-Einstellungen
        'borderRadius', 'enableGradients',
        // Systemeinstellungen
        'defaultLanguage', 'dateFormat', 'defaultStartPage', 'sessionTimeout', 
        'enableNotifications', 'enableUserTracking',
        // Branding & Export
        'pdfHeaderLogo', 'pdfFooterText', 'exportDateFormat', 'defaultExportFormat',
        // Kontakt & Support
        'supportEmail', 'supportPhone', 'helpPageContent',
        // Andere, fireguard-spezifische Einstellungen
        'extinguisherNr', 'marquee_dashboard'
    ];
    
    // Filter settings to only include allowed ones
    $settingsToUpdate = array_intersect_key($settingsData, array_flip($allowedSettings));

    if (empty($settingsToUpdate)) {
        http_response_code(400); echo json_encode(['error' => 'No valid settings provided for update.']); return;
    }

    try {
        $pdo->beginTransaction();
        $success = true;
        $changes = [];

        // Schleife durch alle Einstellungen
        foreach ($settingsToUpdate as $key => $value) {
            // Boolesche Werte in Strings umwandeln für die Datenbank
            if (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            }
            
            // Numerische Werte in Strings umwandeln
            if (is_numeric($value)) {
                $value = (string)$value;
            }
            
            // Prüfen, ob die Einstellung bereits existiert
            $checkStmt = $pdo->prepare("SELECT id FROM `kdd_reports` AS WHERE authority_id = ? key_name = ?");
            $checkStmt->execute([$key]);
            $existingId = $checkStmt->fetchColumn();

            if ($existingId) {
                // Einstellung aktualisieren
                $stmt = $pdo->prepare("UPDATE `kdd_global_settings` SET value = ? WHERE authority_id = ? AND key_name = ?");
                $result = $stmt->execute([$authorityId, $value, $key]);
            } else {
                // Neue Einstellung einfügen
                $stmt = $pdo->prepare("INSERT INTO `kdd_global_settings` (authority_id, key_name, value) VALUES (?, ?, ?)");
                $result = $stmt->execute([$authorityId, $key, $value]);
            }

            if (!$result) {
                $success = false;
                error_log("Failed to update setting '{$key}' for authority '{$authority}'.");
                break; // Stop on first error
            }
            
            // Collect changes for logging
            $changes[] = ['column_name' => $key, 'old_value' => '[previous]', 'new_value' => $value];
        }

        if ($success) {
            $pdo->commit();
            http_response_code(200); echo json_encode(["success" => true, "message" => "Settings updated successfully."]);
            // Log all changes made in this transaction
            if (!empty($changes) && function_exists('logDatabaseChange')) {
                $logSummary = [['column_name' => 'batch_update', 'old_value' => null, 'new_value' => json_encode($settingsToUpdate)]];
                global $decoded_jwt;
                $authorityId = $decoded_jwt->authority_id ?? 0;
                logDatabaseChange($authorityId, $pdo, 'UPDATE', "global_settings", 0, $requestingUserId, $logSummary); 
            }
        } else {
            $pdo->rollBack();
            http_response_code(500); echo json_encode(['error' => 'Failed to update one or more settings. Transaction rolled back.']);
        }

    } catch (\PDOException $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        error_log("DB error in updateSettings [ADMIN] ($authority): " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not update settings: " . $e->getMessage()]);
    }
}

/**
 * Funktion zum Abrufen aller Einstellungen
 */
function getGlobalSettings(PDO $pdo, string $authority): void {
    try {
        // Holen Sie alle globalen Einstellungen
        $stmt = $pdo->prepare("SELECT key_name, value FROM `kdd_global_settings` WHERE authority_id = ?");
        $stmt->execute();
        $settingsRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Konvertieren Sie die Zeilen in ein assoziatives Array
        $settings = [];
        foreach ($settingsRows as $row) {
    // Get authority ID
    global $decoded;
    $authority = $decoded->authority ?? null;
    global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
    if (!$authorityId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid authority']);
        exit;
    }

            $key = $row['key_name'];
            $value = $row['value'];
            
            // Konvertieren von booleschen Werten
            if ($value === 'true') $value = true;
            if ($value === 'false') $value = false;
            
            // Konvertieren von numerischen Werten
            if (is_numeric($value) && strpos($value, '.') === false) {
                $value = (int)$value;
            } else if (is_numeric($value)) {
                $value = (float)$value;
            }
            
            $settings[$key] = $value;
        }
        
        // Antwort zurückgeben
        http_response_code(200);
        echo json_encode([
            "success" => true, 
            "settings" => $settings
        ]);
    } catch (\PDOException $e) {
        error_log("DB error in getGlobalSettings [ADMIN] ($authority): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not fetch settings."]);
    }
}
?> 