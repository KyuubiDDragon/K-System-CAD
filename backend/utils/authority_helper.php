<?php
/**
 * Prüft, ob eine Authority gültig ist (existiert und ist aktiv)
 */

function hasFeatureAccess(PDO $pdo, int $authorityId, string $featureCode): bool {
    // Spezialfall: Default-Zugriff immer gewähren
    if ($featureCode === 'default') {
        return true;
    }

    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM kdd_authorities a
        JOIN kdd_authority_features_rel afr ON a.id = afr.authority_id
        JOIN kdd_authority_features f ON afr.feature_id = f.id
        WHERE a.id = ? AND f.code = ? AND a.active = 1
    ");
    $stmt->execute([$authorityId, $featureCode]);
    return (bool)$stmt->fetchColumn();
}

/**
 * Get all active feature codes for an authority
 *
 * @param PDO $pdo Database connection
 * @param int $authorityId Authority ID
 * @return array Array of feature codes (e.g., ['employee', 'calendar', 'document'])
 */
function getAuthorityActiveFeatures(PDO $pdo, int $authorityId): array {
    try {
        $stmt = $pdo->prepare("
            SELECT f.code
            FROM kdd_authority_features f
            JOIN kdd_authority_features_rel afr ON f.id = afr.feature_id
            JOIN kdd_authorities a ON afr.authority_id = a.id
            WHERE a.id = ? AND a.active = 1
            ORDER BY f.code
        ");
        $stmt->execute([$authorityId]);

        $features = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $features[] = $row['code'];
        }

        // Always include 'default' feature
        if (!in_array('default', $features)) {
            $features[] = 'default';
        }

        return $features;
    } catch (\PDOException $e) {
        error_log("Error loading authority features: " . $e->getMessage());
        // Return default feature on error
        return ['default'];
    }
}

// Prüfen, ob eine Authority gültig ist
function isValidAuthority(PDO $pdo, int $authorityId): bool {
    $stmt = $pdo->prepare("SELECT 1 FROM kdd_authorities WHERE id = ? AND active = 1");
    $stmt->execute([$authorityId]);
    return (bool)$stmt->fetchColumn();
}