<?php
/**
 * Utility Script: downloadImages.php
 * Downloads external images embedded in document content for specified authorities
 * and replaces the URLs with local ones.
 * Intended for CLI or Cron execution.
 * Uses PDO and reads configuration from .env.
 */
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

// --- Core Initialisation ---
// Ensure running from CLI or secure context if needed

// Change to the script's directory to ensure relative paths work
chdir(__DIR__);

// Error Reporting (Set high for CLI scripts)
error_reporting(E_ALL);
ini_set('display_errors', '1'); // Display errors to console
ini_set('log_errors', '1'); // Also log errors (configure error_log in php.ini)

// --- Dependencies & DB Connection ---
try {
    require_once __DIR__ . '/../db.php'; // Defines $pdo
} catch (Exception $e) {
    echo "Error: Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}
// No need for logging.php unless using its helpers specifically
// require_once __DIR__ . '/../logging/logging.php';

// --- Configuration from .env ---
$absoluteImageBaseDir = $_ENV['DOCUMENT_IMAGE_STORAGE_PATH'] ?? null;
$relativeImageBaseDir = $_ENV['DOCUMENT_IMAGE_PUBLIC_URL'] ?? null;

if (!$absoluteImageBaseDir || !$relativeImageBaseDir) {
    echo "Error: DOCUMENT_IMAGE_STORAGE_PATH or DOCUMENT_IMAGE_PUBLIC_URL not set in .env file.\n";
    exit(1);
}
// Ensure trailing slash for easy concatenation
$absoluteImageBaseDir = rtrim($absoluteImageBaseDir, '/') . '/';
$relativeImageBaseDir = rtrim($relativeImageBaseDir, '/') . '/';

// --- Main Logic ---

echo "Starting document image processing...\n";

try {
    // 1. Get all distinct, valid authorities
    // Adjust table/column if needed, filter out invalid ones
    $allowedAuthorities = ["fire", "police", "medic", "justice", "statepark", "casa", "test", "fireguard"];
    $placeholders = rtrim(str_repeat('?,', count($allowedAuthorities)), ',');
    $sqlAuthorities = "SELECT DISTINCT authority FROM `kdd_reports` AS WHERE authority_id = ? authority IN ({$placeholders})";
    $stmtAuth = $pdo->prepare($sqlAuthorities);
    $stmtAuth->execute($allowedAuthorities);
    $authoritiesToProcess = $stmtAuth->fetchAll(PDO::FETCH_COLUMN, 0);

    if (empty($authoritiesToProcess)) {
        echo "No valid authorities found to process.\n";
        exit(0);
    }

    echo "Found authorities to process: " . implode(', ', $authoritiesToProcess) . "\n";

    // 2. Loop through each authority and update documents
    foreach ($authoritiesToProcess as $authority) {
        echo "\nProcessing authority: {$authority}\n";
        // Ensure authority-specific directory exists (optional, based on needs)
        // Example: $currentAbsoluteDir = $absoluteImageBaseDir . $authority . '/';
        // For now, use one common directory defined in .env
        $currentAbsoluteDir = $absoluteImageBaseDir;
        $currentRelativeDir = $relativeImageBaseDir;

        updateDocumentsForAuthority($pdo, $authority, $currentAbsoluteDir, $currentRelativeDir);
    }

    echo "\nDocument image processing finished successfully.\n";
    exit(0);

} catch (\PDOException $e) {
    echo "Database Error during processing: " . $e->getMessage() . "\n";
    exit(1);
} catch (\Throwable $e) {
    echo "General Error during processing: " . $e->getMessage() . "\n";
    exit(1);
}


// --- Function Definitions ---

/**
 * Downloads external images from content, saves them locally, and updates URLs.
 *
 * @param string $content Original HTML content.
 * @param string $absoluteImageDir Absolute path to save images.
 * @param string $relativeImageDir Public URL base path for saved images.
 * @return string Content with updated image URLs.
 */
function downloadAndUpdateImages(string $content, string $absoluteImageDir, string $relativeImageDir): string {
    // Create directory if it doesn't exist
    if (!is_dir($absoluteImageDir)) {
        // Set permissions carefully, 0775 might be better than 0777
        if (!mkdir($absoluteImageDir, 0775, true)) { // Use 0775, allow recursive creation
            error_log("Failed to create directory: $absoluteImageDir");
            return $content; // Return original content on directory error
        }
    }

    // Regex to find image URLs (adjust if needed)
    // Consider adding more protocols or data URIs if necessary
    $regex = '/(http(?:s?):)([\/|.|\w|\s|%|-])*\.(?:jpg|jpeg|gif|png|webp|svg)(\?([\/|.|\w|\s|-|=|&amp;|%])*)?/i';
    if (!preg_match_all($regex, $content, $matches)) {
        return $content; // No external images found
    }

    $processedUrls = []; // Avoid processing the same URL multiple times

    foreach ($matches[0] as $originalUrl) {
        // Trim whitespace which might be caught by regex
        $url = trim($originalUrl);

        // Skip already processed URLs in this content block
        if (isset($processedUrls[$url])) {
            continue;
        }

        // Skip specific domains if needed (e.g., already local, trusted CDNs)
        // Use parse_url to reliably get the host
        $host = parse_url($url, PHP_URL_HOST);
        if ($host === false || $host === null) {
            $processedUrls[$url] = $url; // Mark as processed but skip download
            continue;
        }
        // Example: Skip own domains and common CDNs
        if (in_array(strtolower($host), ['dropbox.com', 'cdn.example.com'])) {
             $processedUrls[$url] = $url; // Mark as processed but skip download
            continue;
        }

        // Generate unique filename while preserving extension
        $pathInfo = pathinfo(parse_url($url, PHP_URL_PATH) ?: '', PATHINFO_EXTENSION);
        $fileExtension = strtolower($pathInfo ?: 'jpg'); // Default extension if parse fails
        // Ensure extension is safe (e.g., jpg, png, gif)
        if (!in_array($fileExtension, ['jpg', 'jpeg', 'gif', 'png', 'webp', 'svg'])) {
            $fileExtension = 'jpg'; // Default to jpg if unknown/unsafe extension
        }
        $fileName = uniqid('img_', true) . '.' . $fileExtension; // More unique prefix
        $absoluteFilePath = $absoluteImageDir . $fileName;

        // Download using cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Follow redirects
        curl_setopt($ch, CURLOPT_TIMEOUT, 10); // Set timeout (e.g., 10 seconds)
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); // Verify SSL cert
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        // Set a User-Agent to avoid potential blocks
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; ImageDownloaderScript/1.0; +http://yourdomain.com/botinfo)');

        $imageData = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError || $httpCode >= 400 || $imageData === false || empty(trim($imageData))) {
            error_log("Failed to download image from {$url}. HTTP Code: {$httpCode}. cURL Error: {$curlError}");
            $processedUrls[$url] = $url; // Mark as processed but skip replacement
        } else {
            // Save the image data
            if (file_put_contents($absoluteFilePath, $imageData) !== false) {
                // Successfully saved, replace URL in content
                $relativeFilePath = $relativeImageDir . $fileName;
                // Use preg_quote on the original URL for safe replacement, handle potential HTML entities
                $escapedOriginalUrl = preg_quote(htmlspecialchars_decode($url), '/');
                $content = preg_replace('/' . $escapedOriginalUrl . '/', $relativeFilePath, $content, 1); // Replace only once per unique URL initially found
                $processedUrls[$url] = $relativeFilePath; // Mark as processed with new path
                echo "  Downloaded and replaced: {$url} -> {$relativeFilePath}\n";
            } else {
                error_log("Failed to save image data to: $absoluteFilePath");
                $processedUrls[$url] = $url; // Mark as processed but skip replacement
            }
        }
    } // End foreach matches

    return $content;
}

/**
 * Processes documents for a specific authority.
 */
function updateDocumentsForAuthority(PDO $pdo, string $authority, string $absoluteImageDir, string $relativeImageDir): void {
    $tableName = "kdd_doc_documents";
    echo "  Checking documents in table: {$tableName}\n";

    try {
        // Select documents that might contain external images (more efficient than loading all)
        // This WHERE clause is basic, could be refined if content structure allows
        $sqlSelect = "SELECT id, content FROM {$tableName} WHERE content LIKE '%<img %src=\"http%' AND is_deleted = 0"; // Basic check
        $stmtSelect = $pdo->prepare($sqlSelect);
        $stmtSelect->execute();

        $updateCount = 0;
        $sqlUpdates = []; // Array to store updates if needed later

        while ($row = $stmtSelect->fetch(PDO::FETCH_ASSOC)) {
            $documentId = $row['id'];
            $originalContent = $row['content'];

            // Process content to download images and update URLs
            $updatedContent = downloadAndUpdateImages($originalContent, $absoluteImageDir, $relativeImageDir);

            // Update database only if content has actually changed
            if ($updatedContent !== $originalContent) {
                echo "    Updating document ID: {$documentId}\n";
                $sqlUpdate = "UPDATE {$tableName} SET content = ? WHERE id = ?";
                $stmtUpdate = $pdo->prepare($sqlUpdate);
                $success = $stmtUpdate->execute([$updatedContent, $documentId]);
                if ($success) {
                    $updateCount++;
                } else {
                     error_log("    Failed to update document ID {$documentId} for authority {$authority}.");
                }
            }
        }
        echo "  Finished processing for authority {$authority}. Updated {$updateCount} documents.\n";

    } catch (\PDOException $e) {
        error_log("Database error while processing documents for authority {$authority}: " . $e->getMessage());
        // Continue to next authority? Or rethrow?
    }
} // End updateDocumentsForAuthority

?>