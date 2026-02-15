<?php
/**
 * Mail System: Domain Management Module
 * Handles domain operations for mail system
 */

declare(strict_types=1);

/**
 * Get domains available for user's authority
 * Always includes mail.ls (default domain)
 * If authority_type = 'faction': Include custom domain from kdd_authorities.mail_domain
 *
 * @param PDO $pdo Database connection
 * @param int $authorityId Authority ID
 * @return void Outputs JSON response
 */
function getAvailableDomains(PDO $pdo, int $authorityId): void {
    try {
        $domains = [];

        // Get all available domains for this authority
        // 1. Default domains (domain_type='default', authority_id IS NULL) - e.g., mail.ls
        // 2. Faction domains (domain_type='faction', authority_id matches)
        $stmt = $pdo->prepare("
            SELECT
                id,
                domain,
                domain_type as type,
                display_name,
                logo_url,
                is_active,
                verified,
                max_accounts,
                total_accounts
            FROM kdd_mail_domains
            WHERE is_active = 1
            AND (
                (domain_type = 'default' AND authority_id IS NULL)
                OR (domain_type = 'faction' AND authority_id = :authority_id)
            )
            ORDER BY
                CASE WHEN domain = 'mail.ls' THEN 0 ELSE 1 END,
                domain_type,
                domain
        ");
        $stmt->bindParam(':authority_id', $authorityId, PDO::PARAM_INT);
        $stmt->execute();

        while ($domain = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $domains[] = [
                'id' => (int)$domain['id'],
                'domain' => $domain['domain'],
                'type' => $domain['type'],
                'description' => $domain['type'] === 'default' ? 'Default mail domain' : 'Custom faction domain',
                'display_name' => $domain['display_name'] ?? ucfirst(str_replace(['-', '.ls'], [' ', ''], $domain['domain'])),
                'logo_url' => $domain['logo_url'] ?? null,
                'is_active' => (bool)$domain['is_active'],
                'verified' => (bool)$domain['verified'],
                'max_accounts' => (int)$domain['max_accounts'],
                'total_accounts' => (int)$domain['total_accounts']
            ];
        }

        // Fallback: If no domains found, add mail.ls manually
        if (empty($domains)) {
            $domains[] = [
                'id' => 0,
                'domain' => 'mail.ls',
                'type' => 'default',
                'description' => 'Default mail domain',
                'display_name' => 'LS Mail',
                'is_active' => true,
                'verified' => true,
                'max_accounts' => 1000,
                'total_accounts' => 0
            ];
        }

        $response = [
            'success' => true,
            'domains' => $domains
        ];

        http_response_code(200);
        echo json_encode($response);

    } catch (PDOException $e) {
        error_log("Database error in getAvailableDomains: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'error' => 'Failed to retrieve available domains',
            'details' => $e->getMessage()
        ]);
    } catch (Exception $e) {
        error_log("General error in getAvailableDomains: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'error' => 'Unexpected error',
            'details' => $e->getMessage()
        ]);
    }
}

/**
 * Check if email address is available
 * Searches kdd_mail_accounts for existing email
 * Provides suggestion if email is taken
 *
 * @param PDO $pdo Database connection
 * @return void Outputs JSON response
 */
function checkMailAvailability(PDO $pdo): void {
    try {
        // Accept either full email OR email+domain separately
        $email = $_REQUEST['email'] ?? $_GET['email'] ?? '';
        $domain = $_REQUEST['domain'] ?? $_GET['domain'] ?? '';

        // If domain is provided separately, combine them
        if (!empty($domain) && !str_contains($email, '@')) {
            $email = $email . '@' . $domain;
        }

        if (empty($email)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Email parameter is required'
            ]);
            return;
        }

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Invalid email format'
            ]);
            return;
        }

        // Check if email exists
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count
            FROM kdd_mail_accounts
            WHERE email = :email
        ");
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $available = ($result['count'] == 0);
        $suggestion = null;

        // Generate suggestion if email is taken
        if (!$available) {
            list($username, $domain) = explode('@', $email, 2);

            // Try adding numbers
            for ($i = 1; $i <= 99; $i++) {
                $suggestedEmail = $username . $i . '@' . $domain;

                $checkStmt = $pdo->prepare("
                    SELECT COUNT(*) as count
                    FROM kdd_mail_accounts
                    WHERE email = :email
                ");
                $checkStmt->bindParam(':email', $suggestedEmail, PDO::PARAM_STR);
                $checkStmt->execute();
                $checkResult = $checkStmt->fetch(PDO::FETCH_ASSOC);

                if ($checkResult['count'] == 0) {
                    $suggestion = $suggestedEmail;
                    break;
                }
            }
        }

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'available' => $available,
            'email' => $email,
            'suggestion' => $suggestion
        ]);

    } catch (PDOException $e) {
        error_log("Database error in checkMailAvailability: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'error' => 'Failed to check email availability',
            'details' => $e->getMessage()
        ]);
    }
}

/**
 * Get domain statistics
 * Returns information about a specific domain
 *
 * @param PDO $pdo Database connection
 * @param int $authorityId Authority ID
 * @return void Outputs JSON response
 */
function getDomainInfo(PDO $pdo, int $authorityId): void {
    try {
        $domain = $_REQUEST['domain'] ?? $_GET['domain'] ?? 'mail.ls';

        // Default to mail.ls if no domain specified
        // For default domain (mail.ls), get global stats
        if ($domain === 'mail.ls') {
            $stmt = $pdo->prepare("
                SELECT
                    COUNT(*) as total_accounts,
                    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_accounts,
                    COALESCE(SUM(storage_used), 0) as total_storage_used
                FROM kdd_mail_accounts
                WHERE email LIKE :domain
            ");
            $domainPattern = '%@mail.ls';
            $stmt->bindParam(':domain', $domainPattern, PDO::PARAM_STR);
            $stmt->execute();
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);

            // Get total mails sent
            $mailStmt = $pdo->prepare("
                SELECT COUNT(*) as total_mails_sent
                FROM kdd_mails
                WHERE from_address LIKE :domain
            ");
            $mailStmt->bindParam(':domain', $domainPattern, PDO::PARAM_STR);
            $mailStmt->execute();
            $mailStats = $mailStmt->fetch(PDO::FETCH_ASSOC);

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'domain' => $domain,
                'type' => 'default',
                'total_accounts' => (int)$stats['total_accounts'],
                'active_accounts' => (int)$stats['active_accounts'],
                'total_storage_used' => (int)$stats['total_storage_used'],
                'total_mails_sent' => (int)$mailStats['total_mails_sent'],
                'is_active' => true,
                'verified' => true
            ]);
            return;
        }

        // For custom faction domain
        $stmt = $pdo->prepare("
            SELECT
                md.id,
                md.domain,
                md.domain_type,
                md.authority_id,
                md.is_active,
                md.verified,
                md.max_accounts,
                md.display_name,
                md.logo_url,
                md.total_accounts,
                md.total_mails_sent,
                COALESCE(SUM(ma.storage_used), 0) as total_storage_used,
                COUNT(CASE WHEN ma.is_active = 1 THEN 1 END) as active_accounts
            FROM kdd_mail_domains md
            LEFT JOIN kdd_mail_accounts ma ON ma.email LIKE CONCAT('%@', md.domain)
            WHERE md.domain = :domain
                AND md.authority_id = :authority_id
            GROUP BY md.id
        ");
        $stmt->bindParam(':domain', $domain, PDO::PARAM_STR);
        $stmt->bindParam(':authority_id', $authorityId, PDO::PARAM_INT);
        $stmt->execute();
        $domainInfo = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$domainInfo) {
            http_response_code(404);
            echo json_encode([
                'error' => 'Domain not found or access denied'
            ]);
            return;
        }

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'domain' => $domainInfo['domain'],
            'type' => $domainInfo['domain_type'],
            'display_name' => $domainInfo['display_name'],
            'logo_url' => $domainInfo['logo_url'],
            'is_active' => (bool)$domainInfo['is_active'],
            'verified' => (bool)$domainInfo['verified'],
            'max_accounts' => (int)$domainInfo['max_accounts'],
            'total_accounts' => (int)$domainInfo['total_accounts'],
            'active_accounts' => (int)$domainInfo['active_accounts'],
            'total_storage_used' => (int)$domainInfo['total_storage_used'],
            'total_mails_sent' => (int)$domainInfo['total_mails_sent']
        ]);

    } catch (PDOException $e) {
        error_log("Database error in getDomainInfo: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'error' => 'Failed to retrieve domain information',
            'details' => $e->getMessage()
        ]);
    }
}

/**
 * Create faction domain
 * Helper function called when creating faction authority
 * Generates domain from authority name (e.g., "Fire Department" → "fire-department.ls")
 *
 * @param PDO $pdo Database connection
 * @param int $authorityId Authority ID
 * @param string $authorityName Authority name
 * @return string Generated domain name
 * @throws Exception If domain creation fails
 */
function createFactionDomain(PDO $pdo, int $authorityId, string $authorityName): string {
    try {
        // Generate domain from authority name
        $domain = strtolower($authorityName);
        $domain = preg_replace('/[^a-z0-9\s-]/', '', $domain); // Remove special chars
        $domain = preg_replace('/\s+/', '-', $domain); // Replace spaces with hyphens
        $domain = preg_replace('/-+/', '-', $domain); // Replace multiple hyphens with single
        $domain = trim($domain, '-'); // Remove leading/trailing hyphens
        $domain = $domain . '.ls';

        // Check if domain already exists
        $checkStmt = $pdo->prepare("
            SELECT COUNT(*) as count
            FROM kdd_mail_domains
            WHERE domain = :domain
        ");
        $checkStmt->bindParam(':domain', $domain, PDO::PARAM_STR);
        $checkStmt->execute();
        $result = $checkStmt->fetch(PDO::FETCH_ASSOC);

        // If domain exists, add number suffix
        if ($result['count'] > 0) {
            $baseDomain = str_replace('.ls', '', $domain);
            for ($i = 2; $i <= 99; $i++) {
                $testDomain = $baseDomain . '-' . $i . '.ls';
                $checkStmt->bindParam(':domain', $testDomain, PDO::PARAM_STR);
                $checkStmt->execute();
                $testResult = $checkStmt->fetch(PDO::FETCH_ASSOC);

                if ($testResult['count'] == 0) {
                    $domain = $testDomain;
                    break;
                }
            }
        }

        // Insert into kdd_mail_domains
        $insertStmt = $pdo->prepare("
            INSERT INTO kdd_mail_domains (
                domain,
                domain_type,
                authority_id,
                is_active,
                verified,
                max_accounts,
                display_name,
                total_accounts,
                total_mails_sent
            ) VALUES (
                :domain,
                'faction',
                :authority_id,
                1,
                1,
                100,
                :display_name,
                0,
                0
            )
        ");
        $insertStmt->bindParam(':domain', $domain, PDO::PARAM_STR);
        $insertStmt->bindParam(':authority_id', $authorityId, PDO::PARAM_INT);
        $insertStmt->bindParam(':display_name', $authorityName, PDO::PARAM_STR);
        $insertStmt->execute();

        // Update kdd_authorities.mail_domain
        $updateStmt = $pdo->prepare("
            UPDATE kdd_authorities
            SET mail_domain = :domain
            WHERE id = :authority_id
        ");
        $updateStmt->bindParam(':domain', $domain, PDO::PARAM_STR);
        $updateStmt->bindParam(':authority_id', $authorityId, PDO::PARAM_INT);
        $updateStmt->execute();

        error_log("Created faction domain: $domain for authority: $authorityId");

        return $domain;

    } catch (PDOException $e) {
        error_log("Database error in createFactionDomain: " . $e->getMessage());
        throw new Exception('Failed to create faction domain: ' . $e->getMessage());
    }
}

?>
