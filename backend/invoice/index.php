<?php
/**
 * Backend Endpoint: invoice/index.php
 * Handles CRUD operations for Invoices and Invoice Entries, including attachments
 * and fetching related company/person data.
 * Uses Cookie-based Authentication and PDO database connection.
 */
declare(strict_types=1);

// --- Core Initialisation ---
require_once __DIR__ . '/../bootstrap.php';




// --- Dependencies & DB Connection ---
try {
    require_once __DIR__ . '/../db.php'; // Defines $pdo
} catch (Exception $e) {
    http_response_code(500); echo json_encode(["error" => "Database connection failed."]); exit();
}
require_once __DIR__ . '/../logging/logging.php'; // For logDatabaseChange

// --- Authentication ---
try {
    require_once __DIR__ . '/../auth_check.php'; // Checks cookie, defines $decoded_jwt or exits
} catch (\Throwable $e) {
    error_log("Critical error during auth check include: " . $e->getMessage());
    http_response_code(500); echo json_encode(["error" => "Authentication system error."]); exit();
}

// --- User Context & Authority Validation ---
$userId = $decoded_jwt->userId ?? null;
$userPermissions = $decoded_jwt->permissions ?? [];
$authority = $decoded_jwt->authority ?? null;
$authorityId = $decoded_jwt->authority_id ?? null;

// --- Configuration from .env ---
$baseUploadPath = "../../uploads/{$authority}/invoices/";
$basePublicUrl = "../../uploads/{$authority}/invoices/";

if ($userId === null || !is_int($userId) || $authority === null || !is_string($authority)) {
    http_response_code(400); echo json_encode(["error" => "Invalid token payload."]); exit();
}
require_once __DIR__ . '/../utils/authority_helper.php';
if (!isValidAuthority($pdo, $authorityId)) {
    http_response_code(403); echo json_encode(["error" => "Invalid authority context."]); exit();
}

// Feature-Zugriff prüfen
if (!hasFeatureAccess($pdo, $authorityId, 'invoice')) {
    http_response_code(403); echo json_encode(["error" => "This authority doesn't have access to invoice features."]); exit();
}

// --- Authorization & Action Routing ---
$action = $_REQUEST['action'] ?? '';
$request_method = $_SERVER['REQUEST_METHOD'];

// Map actions to required permissions (module.action format)
$permissions_map = [
    'getInvoices'        => ['module' => 'invoice', 'action' => 'read'],
    'getInvoiceEntries'  => ['module' => 'invoice', 'action' => 'read'],
    'addInvoice'         => ['module' => 'invoice', 'action' => 'write'],
    'editInvoice'        => ['module' => 'invoice', 'action' => 'write'],
    'addInvoiceEntry'    => ['module' => 'invoice', 'action' => 'write'],
    'editInvoiceEntry'   => ['module' => 'invoice', 'action' => 'write'],
    'getCompanies'       => ['module' => 'invoice', 'action' => 'write'], // Needed when creating/editing invoices
    'getCompanyData'     => ['module' => 'invoice', 'action' => 'write'], // Needed when creating/editing invoices
    'getPersons'         => ['module' => 'invoice', 'action' => 'write'], // Needed when creating/editing invoices
    'getPersonData'      => ['module' => 'invoice', 'action' => 'write'], // Needed when creating/editing invoices
    'deleteInvoice'      => ['module' => 'invoice', 'action' => 'delete'],
    'deleteInvoiceEntry' => ['module' => 'invoice', 'action' => 'delete'],
];

$required_permission = $permissions_map[$action] ?? null;
$has_permission = false;

if ($action === '') { http_response_code(400); echo json_encode(["error" => "No action specified."]); exit(); }
if ($required_permission === null) { http_response_code(404); echo json_encode(['error' => 'Invalid action specified.']); exit(); }

// Load permission helper
require_once __DIR__ . '/../utils/permission_helper.php';

$module = $required_permission['module'];
$actionType = $required_permission['action'];

// Check permission levels using module-based permissions
if (hasAllPermissions($userPermissions)) {
    $has_permission = true;
} elseif (hasModulePermission($userPermissions, $module, $actionType)) {
    $has_permission = true; // Has specific permission
} elseif ($actionType === 'read' && hasModulePermission($userPermissions, $module, 'write')) {
    $has_permission = true; // WRITE implies READ
} elseif ($actionType === 'read' && hasModulePermission($userPermissions, $module, 'delete')) {
    $has_permission = true; // DELETE implies READ
} elseif ($actionType === 'write' && hasModulePermission($userPermissions, $module, 'delete')) {
    $has_permission = true; // DELETE implies WRITE
}

// --- Execute Action or Deny ---
if ($has_permission) {
    $is_post_request = ($request_method === 'POST');

    switch ($action) {
        // GET Actions
        case 'getInvoices':         if ($request_method === 'GET') getInvoices($pdo, $authority); else MethodNotAllowed(); break;
        case 'getInvoiceEntries':   if ($request_method === 'GET') getInvoiceEntries($pdo, $authority); else MethodNotAllowed(); break;
        case 'getCompanies':        if ($request_method === 'GET') getCompanies($pdo, $authority); else MethodNotAllowed(); break;
        case 'getPersons':          if ($request_method === 'GET') getPersons($pdo, $authority); else MethodNotAllowed(); break;

        // POST Actions (or PUT/DELETE where appropriate)
        case 'addInvoice':          if ($is_post_request) addInvoice($pdo, $userId, $authority, $baseUploadPath, $basePublicUrl); else MethodNotAllowed(); break;
        case 'editInvoice':         if ($is_post_request) editInvoice($pdo, $userId, $authority, $baseUploadPath, $basePublicUrl); else MethodNotAllowed(); break; // Consider PUT
        case 'deleteInvoice':       if ($is_post_request) deleteInvoice($pdo, $userId, $authority); else MethodNotAllowed(); break; // Consider DELETE
        case 'addInvoiceEntry':     if ($is_post_request) addInvoiceEntry($pdo, $userId, $authority); else MethodNotAllowed(); break;
        case 'editInvoiceEntry':    if ($is_post_request) editInvoiceEntry($pdo, $userId, $authority); else MethodNotAllowed(); break; // Consider PUT
        case 'deleteInvoiceEntry':  if ($is_post_request) deleteInvoiceEntry($pdo, $userId, $authority); else MethodNotAllowed(); break; // Consider DELETE
        case 'getCompanyData':      if ($request_method === 'GET') getCompanyData($pdo, $authority); else MethodNotAllowed(); break; // Needs POST for ID?
        case 'getPersonData':       if ($request_method === 'GET') getPersonData($pdo, $authority); else MethodNotAllowed(); break; // Needs POST for ID?

        default: http_response_code(500); echo json_encode(['error' => 'Action routing error.']); break;
    }
} else {
    http_response_code(403); echo json_encode(["error" => "Permission denied for action: " . htmlspecialchars($action)]);
}

exit();



// --- Function Implementations (PDO Refactored) ---

/**
 * Handles attachment uploads for invoices.
 */
function uploadAttachments(string $authority, string $baseUploadPath, string $basePublicUrl): array {
    $authorityUploadDir = $baseUploadPath . $authority . '/invoices/';
    $authorityPublicUrl = $basePublicUrl . $authority . '/invoices/';
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf', 'docx', 'doc', 'txt']; // Define allowed types
    $uploadedFiles = []; // Stores public URLs of successfully uploaded files

    if (!isset($_FILES['newAttachments']) || !is_array($_FILES['newAttachments']['name'])) {
        return []; // No attachments or incorrect format
    }

    // Create directory if it doesn't exist
    if (!is_dir($authorityUploadDir)) {
        if (!mkdir($authorityUploadDir, 0775, true)) {
            error_log("Failed to create invoice upload directory: $authorityUploadDir");
            throw new \RuntimeException("Server setup error: Cannot create upload directory.");
        }
    }

    foreach ($_FILES['newAttachments']['name'] as $key => $originalName) {
        if ($_FILES['newAttachments']['error'][$key] !== UPLOAD_ERR_OK) {
            error_log("Upload error for file '{$originalName}': Code " . $_FILES['newAttachments']['error'][$key]);
            continue; // Skip this file
        }

        $tmpName = $_FILES['newAttachments']['tmp_name'][$key];
        $size = $_FILES['newAttachments']['size'][$key];
        $safeOriginalName = preg_replace("/[^a-zA-Z0-9\.\s\-_]/", "_", basename($originalName));
        $extension = strtolower(pathinfo($safeOriginalName, PATHINFO_EXTENSION));
        $baseName = pathinfo($safeOriginalName, PATHINFO_FILENAME);

        // Validate extension
        if (!in_array($extension, $allowedExtensions)) {
            error_log("Invalid file type '{$extension}' for invoice attachment '{$safeOriginalName}'.");
            continue; // Skip
        }
        // Add size validation if needed

        // Create unique filename (e.g., base-timestamp.ext)
        $timestamp = time();
        $newName = "{$baseName}-{$timestamp}.{$extension}";
        $i = 1;
        while (file_exists($authorityUploadDir . $newName)) {
            $newName = "{$baseName}-{$timestamp}({$i}).{$extension}";
            $i++;
        }
        $targetFilePath = $authorityUploadDir . $newName;
        $publicFileUrl = $authorityPublicUrl . $newName;

        // Move uploaded file
        if (move_uploaded_file($tmpName, $targetFilePath)) {
            $uploadedFiles[] = $publicFileUrl; // Store the public URL
        } else {
            error_log("Failed to move uploaded invoice attachment '{$safeOriginalName}' to destination.");
            // Optionally throw exception to stop the whole process
            // throw new \RuntimeException("Failed to process upload for {$safeOriginalName}");
        }
    }
    return $uploadedFiles;
}

function getInvoices(PDO $pdo, string $authority): void {
    try {
        // Get the authority ID first
        global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
        if (!$authorityId) {
            http_response_code(400);
            echo json_encode(["error" => "Invalid authority"]);
            return;
        }
        
        // Fetch invoices and aggregate items using GROUP_CONCAT
        // Note: GROUP_CONCAT has size limits (group_concat_max_len in MySQL config)
        // Consider fetching items in a separate query if item data becomes large or numerous.
        $sql = "SELECT i.*,
                       GROUP_CONCAT( DISTINCT
                           CONCAT_WS('::', -- Use a safer separator less likely in data
                               ie.id,
                               ie.item_id,
                               COALESCE(it.item_name, ie.item_name, ''), -- Use item_name from entry if null in items table
                               COALESCE(ie.description, ''),
                               COALESCE(ie.price, 0),
                               COALESCE(ie.quantity, 0)
                           ) SEPARATOR '||' -- Use a safer row separator
                       ) AS items_concat
                FROM kdd_invoices i
                LEFT JOIN kdd_invoice_entries ie ON i.id = ie.invoice_id AND ie.is_deleted = 0
                LEFT JOIN kdd_invoice_items it ON ie.item_id = it.id -- Join to get master item name
                WHERE i.is_deleted = 0 AND i.authority_id = :authority_id
                GROUP BY i.id
                ORDER BY i.created_at DESC"; // Order by creation date

        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':authority_id', $authorityId, PDO::PARAM_INT);
        $stmt->execute();
        $invoiceData = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Post-process results to parse items_concat string
        $invoices = array_map(function($row) {
            $items = [];
            if (!empty($row['items_concat'])) {
                $itemStrings = explode('||', $row['items_concat']);
                foreach ($itemStrings as $itemStr) {
                    $parts = explode('::', $itemStr, 6); // Limit to 6 parts
                    if (count($parts) === 6) {
                        $items[] = [
                            'id' => (int)$parts[0],
                            'item_id' => $parts[1] ? (int)$parts[1] : null, // item_id can be NULL?
                            'item_name' => $parts[2],
                            'description' => $parts[3],
                            'price' => (float)$parts[4],
                            'quantity' => (int)$parts[5],
                        ];
                    }
                }
            }
            $row['items'] = $items;
            unset($row['items_concat']); // Remove the temporary field
            // Decode attachments JSON string if stored as JSON
            $row['attachments'] = json_decode($row['attachments'] ?? '[]', true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $row['attachments'] = []; // Default to empty array on decode error
            }
            return $row;
        }, $invoiceData);

        http_response_code(200);
        echo json_encode(['invoices' => $invoices]);

    } catch (\PDOException $e) {
        error_log("DB error in getInvoices ($authority): " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not retrieve invoices."]);
    }
}

// --- Funktion: Rechnung hinzufügen mit PDO ---
// --- Funktion: Rechnung hinzufügen mit PDO ---
// --- Funktion: Rechnung hinzufügen mit PDO ---
function addInvoice(PDO $pdo, int $requestingUserId, string $authority, string $baseUploadPath, string $basePublicUrl) {
    // Get the authority ID first
    global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
    if (!$authorityId) {
        http_response_code(400);
        echo json_encode(["error" => "Invalid authority"]);
        return;
    }

    // --- Input Verarbeitung (aus $_POST) ---
    $title = trim($_POST['title'] ?? '');
    $customer = trim($_POST['customer'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $description = trim($_POST['description'] ?? '');

    $linked_person_input = $_POST['linked_person'] ?? null;
    $linked_person = ($linked_person_input === '' || $linked_person_input === null || $linked_person_input === '0') ? null : intval($linked_person_input);

    $linked_company_input = $_POST['linked_company'] ?? null;
    $linked_company = ($linked_company_input === '' || $linked_company_input === null || $linked_company_input === '0') ? null : intval($linked_company_input);

    $is_delivered = (isset($_POST['is_delivered']) && ($_POST['is_delivered'] === 'true' || $_POST['is_delivered'] === '1')) ? 1 : 0;
    $is_sent = (isset($_POST['is_sent']) && ($_POST['is_sent'] === 'true' || $_POST['is_sent'] === '1')) ? 1 : 0;
    $is_paid = (isset($_POST['is_paid']) && ($_POST['is_paid'] === 'true' || $_POST['is_paid'] === '1')) ? 1 : 0;
    $outgoing = (isset($_POST['outgoing']) && ($_POST['outgoing'] === 'true' || $_POST['outgoing'] === '1')) ? 1 : 0;

    $discount = filter_var($_POST['discount'] ?? 0.00, FILTER_VALIDATE_FLOAT);
    if ($discount === false) $discount = 0.00;

    // Items direkt aus POST nehmen (erwartet Array-Struktur)
    $items = $_POST['items'] ?? [];
    if (!is_array($items)) {
         error_log("Warnung: 'items' Feld in addInvoice erhalten, aber kein Array. Input: " . print_r($_POST['items'] ?? 'null', true));
         $items = [];
    }

    // --- Anhänge hochladen ---
    $attachments = []; // Leeres Array initialisieren
    try {
         // Nur aufrufen, wenn Dateien gesendet wurden
        if (isset($_FILES['newAttachments']) && !empty(array_filter($_FILES['newAttachments']['name']))) {
            $attachments = uploadAttachments($authority, $baseUploadPath, $basePublicUrl); // Annahme: Diese Funktion existiert bereits
        }
    } catch (Exception $e) {
        // Upload-Fehler behandeln, bevor die DB-Transaktion beginnt
        http_response_code(400);
        echo json_encode(['error' => "Anhangs-Upload fehlgeschlagen: " . $e->getMessage()]);
        exit;
    }
    $attachmentsJson = json_encode($attachments); // URLs als JSON speichern

    // --- Pflichtfeld-Validierung ---
    if (empty($title) || empty($customer)) {
        http_response_code(400);
        echo json_encode(['error' => 'Titel und Kunde sind Pflichtfelder.']);
        exit;
    }

    // --- Datenbank Transaktion ---
    try {
        $pdo->beginTransaction();

        // 1. Hauptrechnung einfügen
        $sql_insert_invoice = "INSERT INTO kdd_invoices
                   (title, customer, phone_number, email, description,
                    is_delivered, is_delivered_date, is_sent, is_sent_date, is_paid, is_paid_date,
                    discount, created_at, linked_person, linked_company, attachments, outgoing, is_deleted, authority_id)
                   VALUES (:title, :customer, :phone_number, :email, :description,
                           :is_delivered, IF(:is_delivered_if = 1, NOW(), NULL), :is_sent, IF(:is_sent_if = 1, NOW(), NULL), :is_paid, IF(:is_paid_if = 1, NOW(), NULL),
                           :discount, NOW(), :linked_person, :linked_company, :attachments, :outgoing, 0, :authority_id)";

        $stmt = $pdo->prepare($sql_insert_invoice);
        if (!$stmt) {
            throw new Exception("Prepare fehlgeschlagen (insert invoice): " . implode(" ", $pdo->errorInfo()));
        }

        // Parameter binden
        $stmt->bindParam(':title', $title, PDO::PARAM_STR);
        $stmt->bindParam(':customer', $customer, PDO::PARAM_STR);
        $stmt->bindParam(':phone_number', $phone_number, PDO::PARAM_STR);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->bindParam(':description', $description, PDO::PARAM_STR);
        $stmt->bindParam(':is_delivered', $is_delivered, PDO::PARAM_INT);
        $stmt->bindParam(':is_delivered_if', $is_delivered, PDO::PARAM_INT);
        $stmt->bindParam(':is_sent', $is_sent, PDO::PARAM_INT);
        $stmt->bindParam(':is_sent_if', $is_sent, PDO::PARAM_INT);
        $stmt->bindParam(':is_paid', $is_paid, PDO::PARAM_INT);
        $stmt->bindParam(':is_paid_if', $is_paid, PDO::PARAM_INT);
        $stmt->bindParam(':discount', $discount, PDO::PARAM_STR); // PDO::PARAM_STR für Dezimalzahlen
        // Null-Werte richtig binden
        if ($linked_person === null) {
            $stmt->bindValue(':linked_person', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindParam(':linked_person', $linked_person, PDO::PARAM_INT);
        }
        if ($linked_company === null) {
            $stmt->bindValue(':linked_company', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindParam(':linked_company', $linked_company, PDO::PARAM_INT);
        }
        $stmt->bindParam(':attachments', $attachmentsJson, PDO::PARAM_STR);
        $stmt->bindParam(':outgoing', $outgoing, PDO::PARAM_INT);
        $stmt->bindParam(':authority_id', $authorityId, PDO::PARAM_INT);

        if (!$stmt->execute()) {
            throw new Exception("Execute fehlgeschlagen (insert invoice): " . implode(" ", $stmt->errorInfo()));
        }
        
        $invoice_id = $pdo->lastInsertId(); // ID der neuen Rechnung holen

        if (!$invoice_id) { // Zusätzliche Prüfung
            throw new Exception("Konnte keine Rechnungs-ID nach dem Einfügen erhalten.");
        }

        // Log invoice creation
        $changes = [
            ['column_name' => 'title', 'old_value' => null, 'new_value' => $title],
            ['column_name' => 'customer', 'old_value' => null, 'new_value' => $customer],
            ['column_name' => 'outgoing', 'old_value' => null, 'new_value' => $outgoing],
            ['column_name' => 'discount', 'old_value' => null, 'new_value' => $discount]
        ];
        logDatabaseChange($authorityId, $pdo, 'INSERT', 'kdd_invoices', $invoice_id, $requestingUserId, $changes);

        // 2. Rechnungspositionen (Items) einfügen
        if (!empty($items)) {
            $sql_insert_item = "INSERT INTO kdd_invoice_entries
                               (invoice_id, item_id, item_name, description, price, quantity, created_at, is_deleted)
                               VALUES (:invoice_id, :item_id, :item_name, :description, :price, :quantity, NOW(), 0)";
                               
            $stmt = $pdo->prepare($sql_insert_item);
            if (!$stmt) {
                throw new Exception("Prepare fehlgeschlagen (insert item): " . implode(" ", $pdo->errorInfo()));
            }

            foreach ($items as $item) {
                // Extrahiere und validiere Item-Daten
                $item_id = filter_var($item['item_id'] ?? ($item['id'] ?? null), FILTER_VALIDATE_INT, ['options' => ['default' => null]]);
                $item_name = trim($item['item_name'] ?? '');
                $item_description = trim($item['description'] ?? '');
                $price = filter_var($item['price'] ?? 0.00, FILTER_VALIDATE_FLOAT);
                $quantity = filter_var($item['quantity'] ?? 1, FILTER_VALIDATE_INT);

                // Überspringe ungültige Einträge
                if ($item_id === null && empty($item_name)) { continue; }
                if ($quantity === false || $quantity <= 0) { continue; }
                if ($price === false || $price < 0) { continue; }

                // Parameter binden
                $stmt->bindParam(':invoice_id', $invoice_id, PDO::PARAM_INT);
                if ($item_id === null) {
                    $stmt->bindValue(':item_id', null, PDO::PARAM_NULL);
                } else {
                    $stmt->bindParam(':item_id', $item_id, PDO::PARAM_INT);
                }
                $stmt->bindParam(':item_name', $item_name, PDO::PARAM_STR);
                $stmt->bindParam(':description', $item_description, PDO::PARAM_STR);
                $stmt->bindParam(':price', $price, PDO::PARAM_STR); // PDO::PARAM_STR für Dezimalzahlen
                $stmt->bindParam(':quantity', $quantity, PDO::PARAM_INT);

                if (!$stmt->execute()) {
                    throw new Exception("Execute fehlgeschlagen (insert item " . ($item_id ?? $item_name) . "): " . implode(" ", $stmt->errorInfo()));
                }
            }
        }

        // Alles erfolgreich -> Transaktion bestätigen
        $pdo->commit();
        http_response_code(201); // 201 Created
        echo json_encode(["success" => "Invoice added successfully", "id" => $invoice_id]);

    } catch (Exception $e) {
        // Bei Fehler zurückrollen
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("FEHLER in addInvoice: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Rechnung konnte nicht hinzugefügt werden: " . $e->getMessage()]);
    }
}


// --- Funktion: Rechnung bearbeiten mit PDO ---
function editInvoice(PDO $pdo, int $requestingUserId, string $authority, string $baseUploadPath, string $basePublicUrl) {
    // Get the authority ID first
    global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
    if (!$authorityId) {
        http_response_code(400);
        echo json_encode(["error" => "Invalid authority"]);
        return;
    }

    // --- Input Validierung & Verarbeitung ---
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'Rechnungs-ID ist erforderlich und muss eine Zahl sein.']);
        exit;
    }

    $title = trim($_POST['title'] ?? '');
    $customer = trim($_POST['customer'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $description = trim($_POST['description'] ?? '');

    $linked_person_input = $_POST['linked_person'] ?? null;
    $linked_person = ($linked_person_input === '' || $linked_person_input === null || $linked_person_input === '0') ? null : intval($linked_person_input);

    $linked_company_input = $_POST['linked_company'] ?? null;
    $linked_company = ($linked_company_input === '' || $linked_company_input === null || $linked_company_input === '0') ? null : intval($linked_company_input);

    $is_delivered = (isset($_POST['is_delivered']) && ($_POST['is_delivered'] === 'true' || $_POST['is_delivered'] === '1')) ? 1 : 0;
    $is_sent = (isset($_POST['is_sent']) && ($_POST['is_sent'] === 'true' || $_POST['is_sent'] === '1')) ? 1 : 0;
    $is_paid = (isset($_POST['is_paid']) && ($_POST['is_paid'] === 'true' || $_POST['is_paid'] === '1')) ? 1 : 0;
    $outgoing = (isset($_POST['outgoing']) && ($_POST['outgoing'] === 'true' || $_POST['outgoing'] === '1')) ? 1 : 0;

    $discount = filter_var($_POST['discount'] ?? 0.00, FILTER_VALIDATE_FLOAT);
    if ($discount === false) $discount = 0.00;

    // Items direkt aus POST nehmen
    $items = $_POST['items'] ?? [];
    if (!is_array($items)) {
        error_log("Warnung: 'items' Feld in editInvoice erhalten, aber kein Array. Input: " . print_r($_POST['items'] ?? 'null', true));
        $items = [];
    }

    // --- Anhänge: Kombinieren von bestehenden und neuen ---
    $existingAttachments = [];
    if (isset($_POST['existingAttachments']) && is_string($_POST['existingAttachments'])) {
        $decoded = json_decode($_POST['existingAttachments'], true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $existingAttachments = array_values(array_filter(array_map('trim', $decoded))); // Bereinigen
        } else {
            error_log("Warnung: Konnte 'existingAttachments' JSON nicht dekodieren: " . json_last_error_msg());
        }
    }

    $newAttachments = [];
    try {
         if (isset($_FILES['newAttachments']) && !empty(array_filter($_FILES['newAttachments']['name']))) {
            $newAttachments = uploadAttachments($authority, $baseUploadPath, $basePublicUrl);
         }
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => "Anhangs-Upload fehlgeschlagen: " . $e->getMessage()]);
        exit;
    }

    $finalAttachments = array_values(array_unique(array_merge($existingAttachments, $newAttachments))); // Sicherstellen, dass es ein Array ohne Duplikate ist
    $attachmentsJson = json_encode($finalAttachments);


    // --- Pflichtfeld-Validierung ---
    if (empty($title) || empty($customer)) {
        http_response_code(400);
        echo json_encode(['error' => 'Titel und Kunde sind Pflichtfelder.']);
        exit;
    }


    // --- Datenbank Transaktion ---
    try {
        $pdo->beginTransaction();

        // 1. Hauptrechnung aktualisieren (mit verbesserter Datumslogik)
        $sql_update_invoice = "UPDATE kdd_invoices
                SET title = :title, customer = :customer, phone_number = :phone_number, 
                    email = :email, description = :description,
                    is_delivered = :is_delivered,
                    is_delivered_date = CASE
                        WHEN :is_delivered_case1 = 1 THEN COALESCE(is_delivered_date, NOW())
                        WHEN :is_delivered_case2 = 0 THEN NULL
                    END,
                    is_sent = :is_sent,
                    is_sent_date = CASE
                        WHEN :is_sent_case1 = 1 THEN COALESCE(is_sent_date, NOW())
                        WHEN :is_sent_case2 = 0 THEN NULL
                    END,
                    is_paid = :is_paid,
                    is_paid_date = CASE
                        WHEN :is_paid_case1 = 1 THEN COALESCE(is_paid_date, NOW())
                        WHEN :is_paid_case2 = 0 THEN NULL
                    END,
                    discount = :discount, updated_at = NOW(),
                    linked_person = :linked_person, linked_company = :linked_company, 
                    attachments = :attachments, outgoing = :outgoing
                WHERE id = :id AND is_deleted = 0 AND authority_id = :authority_id";

        $stmt = $pdo->prepare($sql_update_invoice);
        if (!$stmt) { 
            throw new Exception("Prepare fehlgeschlagen (update invoice): " . implode(" ", $pdo->errorInfo())); 
        }

        // Parameter binden
        $stmt->bindParam(':title', $title, PDO::PARAM_STR);
        $stmt->bindParam(':customer', $customer, PDO::PARAM_STR);
        $stmt->bindParam(':phone_number', $phone_number, PDO::PARAM_STR);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->bindParam(':description', $description, PDO::PARAM_STR);
        $stmt->bindParam(':is_delivered', $is_delivered, PDO::PARAM_INT);
        $stmt->bindParam(':is_delivered_case1', $is_delivered, PDO::PARAM_INT);
        $stmt->bindParam(':is_delivered_case2', $is_delivered, PDO::PARAM_INT);
        $stmt->bindParam(':is_sent', $is_sent, PDO::PARAM_INT);
        $stmt->bindParam(':is_sent_case1', $is_sent, PDO::PARAM_INT);
        $stmt->bindParam(':is_sent_case2', $is_sent, PDO::PARAM_INT);
        $stmt->bindParam(':is_paid', $is_paid, PDO::PARAM_INT);
        $stmt->bindParam(':is_paid_case1', $is_paid, PDO::PARAM_INT);
        $stmt->bindParam(':is_paid_case2', $is_paid, PDO::PARAM_INT);
        $stmt->bindParam(':discount', $discount, PDO::PARAM_STR); // PDO::PARAM_STR für Dezimalzahlen
        
        // Null-Werte richtig binden
        if ($linked_person === null) {
            $stmt->bindValue(':linked_person', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindParam(':linked_person', $linked_person, PDO::PARAM_INT);
        }
        if ($linked_company === null) {
            $stmt->bindValue(':linked_company', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindParam(':linked_company', $linked_company, PDO::PARAM_INT);
        }
        
        $stmt->bindParam(':attachments', $attachmentsJson, PDO::PARAM_STR);
        $stmt->bindParam(':outgoing', $outgoing, PDO::PARAM_INT);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':authority_id', $authorityId, PDO::PARAM_INT);

        if (!$stmt->execute()) { 
            throw new Exception("Execute fehlgeschlagen (update invoice): " . implode(" ", $stmt->errorInfo())); 
        }
        
        $affected_rows = $stmt->rowCount();
        
        if ($affected_rows === 0) {
             // Prüfe, ob die Rechnung existiert
             $checkSql = "SELECT id FROM kdd_invoices WHERE id = :id AND is_deleted = 0 AND authority_id = :authority_id";
             $checkStmt = $pdo->prepare($checkSql);
             if ($checkStmt) {
                 $checkStmt->bindParam(':id', $id, PDO::PARAM_INT);
                 $checkStmt->bindParam(':authority_id', $authorityId, PDO::PARAM_INT);
                 $checkStmt->execute();
                 
                 if ($checkStmt->rowCount() === 0) { 
                     throw new Exception("Rechnung mit ID {$id} nicht gefunden oder gelöscht."); 
                 }
                 
                 error_log("Invoice update ID {$id} affected 0 rows (Daten möglicherweise identisch).");
             }
        }

        // 2. Alte Items löschen (Soft-Delete)
        $sql_delete_items = "UPDATE kdd_invoice_entries
                             SET is_deleted = 1
                             WHERE invoice_id = :invoice_id AND is_deleted = 0";
                             
        $stmt = $pdo->prepare($sql_delete_items);
        if (!$stmt) { 
            throw new Exception("Prepare fehlgeschlagen (delete items): " . implode(" ", $pdo->errorInfo())); 
        }
        
        $stmt->bindParam(':invoice_id', $id, PDO::PARAM_INT);
        
        if (!$stmt->execute()) { 
            throw new Exception("Execute fehlgeschlagen (delete items): " . implode(" ", $stmt->errorInfo())); 
        }

        // 3. Neue/Aktualisierte Items einfügen
        if (!empty($items)) {
            $sql_insert_item = "INSERT INTO kdd_invoice_entries
                               (invoice_id, item_id, item_name, description, price, quantity, created_at, is_deleted)
                               VALUES (:invoice_id, :item_id, :item_name, :description, :price, :quantity, NOW(), 0)";
                               
            $stmt = $pdo->prepare($sql_insert_item);
            if (!$stmt) { 
                throw new Exception("Prepare fehlgeschlagen (insert item): " . implode(" ", $pdo->errorInfo())); 
            }

            foreach ($items as $item) {
                // Extrahiere und validiere Item-Daten
                $item_id = filter_var($item['item_id'] ?? ($item['id'] ?? null), FILTER_VALIDATE_INT, ['options' => ['default' => null]]);
                $item_name = trim($item['item_name'] ?? '');
                $item_description = trim($item['description'] ?? '');
                $price = filter_var($item['price'] ?? 0.00, FILTER_VALIDATE_FLOAT);
                $quantity = filter_var($item['quantity'] ?? 1, FILTER_VALIDATE_INT);

                // Überspringe ungültige Einträge
                if (($item_id === null && empty($item_name)) || $quantity === false || $quantity <= 0 || $price === false || $price < 0) {
                    error_log("Überspringe ungültiges Item bei editInvoice ID {$id}: " . print_r($item, true));
                    continue;
                }

                // Parameter binden
                $stmt->bindParam(':invoice_id', $id, PDO::PARAM_INT);
                if ($item_id === null) {
                    $stmt->bindValue(':item_id', null, PDO::PARAM_NULL);
                } else {
                    $stmt->bindParam(':item_id', $item_id, PDO::PARAM_INT);
                }
                $stmt->bindParam(':item_name', $item_name, PDO::PARAM_STR);
                $stmt->bindParam(':description', $item_description, PDO::PARAM_STR);
                $stmt->bindParam(':price', $price, PDO::PARAM_STR); // PDO::PARAM_STR für Dezimalzahlen
                $stmt->bindParam(':quantity', $quantity, PDO::PARAM_INT);

                if (!$stmt->execute()) {
                    throw new Exception("Execute fehlgeschlagen (insert item " . ($item_id ?? $item_name) . "): " . implode(" ", $stmt->errorInfo()));
                }
            }
        }

        // Alles erfolgreich -> Transaktion bestätigen
        $pdo->commit();
        http_response_code(200); // OK
        echo json_encode(["success" => "Invoice updated successfully"]);

    } catch (Exception $e) {
        // Bei Fehler zurückrollen
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("FEHLER in editInvoice (ID: {$id}): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Rechnung konnte nicht aktualisiert werden: " . $e->getMessage()]);
    }
}

function deleteInvoice(PDO $pdo, int $requestingUserId, string $authority): void {
    // Get the authority ID first
    global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
    if (!$authorityId) {
        http_response_code(400);
        echo json_encode(["error" => "Invalid authority"]);
        return;
    }

    // Get data from both POST and JSON input
    $data = $_POST;
    
    // If we don't have key fields in POST data, try JSON
    if (empty($data['id'])) {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (strpos($contentType, 'json') !== false) {
            try {
                $jsonData = json_decode(file_get_contents('php://input'), true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($jsonData)) {
                    $data = $jsonData;
                }
            } catch (\Exception $e) {
                error_log("JSON parsing error in deleteInvoice: " . $e->getMessage());
            }
        }
    }
    
    $id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
    if (!$id) { http_response_code(400); echo json_encode(['error' => 'Invalid or missing invoice ID.']); return; }

    try {
        // Also delete related entries? Or just the main invoice? Assume just main for now.
        $sql = "UPDATE kdd_invoices SET is_deleted = 1 WHERE id = ? AND is_deleted = 0 AND authority_id = ?";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([$id, $authorityId]);

        if ($success && $stmt->rowCount() > 0) {
            http_response_code(200); echo json_encode(["success" => true, "message" => "Invoice marked as deleted."]);
            // Log change
            $changes = [['column_name' => 'is_deleted', 'old_value' => 0, 'new_value' => 1]];
            global $decoded_jwt;
                $authorityId = $decoded_jwt->authority_id ?? 0;
                logDatabaseChange($authorityId, $pdo, 'DELETE', "invoices", $id, $requestingUserId, $changes);
        } elseif ($success) { http_response_code(404); echo json_encode(["error" => "Invoice not found or already deleted."]);
        } else { http_response_code(500); echo json_encode(['error' => 'Failed to execute invoice deletion.']); }
    } catch (\PDOException $e) {
        error_log("DB error in deleteInvoice (ID: $id, Authority: $authority): " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not delete invoice."]);
    }
}

function getInvoiceEntries(PDO $pdo, string $authority): void {
    // Get the authority ID first
    global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
    if (!$authorityId) {
        http_response_code(400);
        echo json_encode(["error" => "Invalid authority"]);
        return;
    }

    $invoice_id = filter_var($_GET['invoice_id'] ?? null, FILTER_VALIDATE_INT);
    if (!$invoice_id) { 
        http_response_code(400); 
        echo json_encode(['error' => 'Invoice ID is required.']); 
        return; 
    }

    try {
        // First check if the invoice belongs to this authority
        $checkSql = "SELECT id FROM kdd_invoices WHERE id = ? AND authority_id = ? AND is_deleted = 0";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute([$invoice_id, $authorityId]);
        
        if ($checkStmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(["error" => "Invoice not found or not accessible."]);
            return;
        }

        // Now get the entries
        $sql = "SELECT ie.*, it.item_name as master_item_name
                FROM kdd_invoice_entries ie
                LEFT JOIN kdd_invoice_items it ON ie.item_id = it.id
                WHERE ie.invoice_id = ? AND ie.is_deleted = 0
                ORDER BY ie.id"; // Or other relevant order
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$invoice_id]);
        $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
        http_response_code(200);
        echo json_encode($entries);
    } catch (\PDOException $e) {
        error_log("DB error in getInvoiceEntries (InvoiceID: $invoice_id, Authority: $authority): " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not retrieve invoice entries."]);
    }
}

function addInvoiceEntry(PDO $pdo, int $requestingUserId, string $authority): void {
    // Get data from both POST and JSON input
    $data = $_POST;
    
    // If we don't have key fields in POST data, try JSON
    if (empty($data['invoice_id'])) {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (strpos($contentType, 'json') !== false) {
            try {
                $jsonData = json_decode(file_get_contents('php://input'), true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($jsonData)) {
                    $data = $jsonData;
                }
            } catch (\Exception $e) {
                error_log("JSON parsing error in addInvoiceEntry: " . $e->getMessage());
            }
        }
    }
    
    $invoice_id = filter_var($data['invoice_id'] ?? null, FILTER_VALIDATE_INT);
    $item_id = filter_var($data['item_id'] ?? null, FILTER_VALIDATE_INT, ['options'=>['default'=>null]]);
    $item_name = $data['item_name'] ?? '';
    $description = $data['description'] ?? '';
    $price = filter_var($data['price'] ?? 0.00, FILTER_VALIDATE_FLOAT);
    $quantity = filter_var($data['quantity'] ?? 1, FILTER_VALIDATE_INT);

    if (!$invoice_id || ($item_id === null && empty($item_name)) || $quantity <= 0) {
        http_response_code(400); echo json_encode(['error' => 'Invoice ID, item ID or item name, and positive quantity are required.']); return;
    }
    // Optional: If item_id provided, fetch item_name from master table?

    try {
        $sql = "INSERT INTO kdd_invoice_entries
                    (invoice_id, item_id, item_name, description, price, quantity, created_at)
                  VALUES (?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([$invoice_id, $item_id, $item_name, $description, $price, $quantity]);

        if ($success) {
            $newId = $pdo->lastInsertId();

            // Log change
            global $decoded_jwt;
            $authorityId = $decoded_jwt->authority_id ?? 0;
            $requestingUserId = $decoded_jwt->userId ?? 0;
            $changes = [
                ['column_name' => 'invoice_id', 'old_value' => null, 'new_value' => $invoice_id],
                ['column_name' => 'item_name', 'old_value' => null, 'new_value' => $item_name],
                ['column_name' => 'price', 'old_value' => null, 'new_value' => $price],
                ['column_name' => 'quantity', 'old_value' => null, 'new_value' => $quantity]
            ];
            logDatabaseChange($authorityId, $pdo, 'INSERT', 'kdd_invoice_entries', $newId, $requestingUserId, $changes);

            http_response_code(201); echo json_encode(["success" => true, "message" => "Invoice entry added successfully.", "id" => $newId]);
        } else { http_response_code(500); echo json_encode(['error' => 'Failed to add invoice entry.']); }
    } catch (\PDOException $e) {
        error_log("DB error in addInvoiceEntry (InvoiceID: $invoice_id, Authority: $authority): " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not add invoice entry."]);
    }
}

function editInvoiceEntry(PDO $pdo, int $requestingUserId, string $authority): void {
    // Get data from both POST and JSON input
    $data = $_POST;
    
    // If we don't have key fields in POST data, try JSON
    if (empty($data['id'])) {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (strpos($contentType, 'json') !== false) {
            try {
                $jsonData = json_decode(file_get_contents('php://input'), true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($jsonData)) {
                    $data = $jsonData;
                }
            } catch (\Exception $e) {
                error_log("JSON parsing error in editInvoiceEntry: " . $e->getMessage());
            }
        }
    }
    
    $id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
    $item_id = filter_var($data['item_id'] ?? null, FILTER_VALIDATE_INT, ['options'=>['default'=>null]]);
    $item_name = $data['item_name'] ?? null;
    $description = $data['description'] ?? null;
    $price = filter_var($data['price'] ?? null, FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE);
    $quantity = filter_var($data['quantity'] ?? null, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);

    if (!$id) { http_response_code(400); echo json_encode(['error' => 'Entry ID is required.']); return; }
    // Check if at least one field to update is provided
    if ($item_id === null && $item_name === null && $description === null && $price === null && $quantity === null) {
        http_response_code(400); echo json_encode(['error' => 'No update data provided.']); return;
    }
    if ($quantity !== null && $quantity <= 0) { http_response_code(400); echo json_encode(['error' => 'Quantity must be positive.']); return; }

    try {
        // Build SET part dynamically based on provided fields
        $setParts = []; $params = [];
        if ($item_id !== null) { $setParts[] = "item_id = ?"; $params[] = $item_id; }
        if ($item_name !== null) { $setParts[] = "item_name = ?"; $params[] = $item_name; }
        if ($description !== null) { $setParts[] = "description = ?"; $params[] = $description; }
        if ($price !== null) { $setParts[] = "price = ?"; $params[] = $price; }
        if ($quantity !== null) { $setParts[] = "quantity = ?"; $params[] = $quantity; }

        if (empty($setParts)) { http_response_code(400); echo json_encode(['error' => 'No valid update data provided.']); return; } // Should be caught above

        $setParts[] = "updated_at = NOW()";

        // Get old entry for logging
        global $decoded_jwt;
        $authorityId = $decoded_jwt->authority_id ?? 0;
        $requestingUserId = $decoded_jwt->userId ?? 0;

        $getOldSql = "SELECT * FROM kdd_invoice_entries WHERE id = ? AND is_deleted = 0";
        $getOldStmt = $pdo->prepare($getOldSql);
        $getOldStmt->execute([$id]);
        $oldEntry = $getOldStmt->fetch(PDO::FETCH_ASSOC);

        $sql = "UPDATE kdd_invoice_entries SET " . implode(', ', $setParts) . " WHERE id = ? AND is_deleted = 0";
        $params[] = $id; // Add ID for WHERE clause
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute($params);

        if ($success && $stmt->rowCount() > 0) {
            // Log changes
            if ($oldEntry) {
                $changes = [];
                if ($item_id !== null && $oldEntry['item_id'] != $item_id) $changes[] = ['column_name' => 'item_id', 'old_value' => $oldEntry['item_id'], 'new_value' => $item_id];
                if ($item_name !== null && $oldEntry['item_name'] != $item_name) $changes[] = ['column_name' => 'item_name', 'old_value' => $oldEntry['item_name'], 'new_value' => $item_name];
                if ($price !== null && $oldEntry['price'] != $price) $changes[] = ['column_name' => 'price', 'old_value' => $oldEntry['price'], 'new_value' => $price];
                if ($quantity !== null && $oldEntry['quantity'] != $quantity) $changes[] = ['column_name' => 'quantity', 'old_value' => $oldEntry['quantity'], 'new_value' => $quantity];
                if (!empty($changes)) {
                    logDatabaseChange($authorityId, $pdo, 'UPDATE', 'kdd_invoice_entries', $id, $requestingUserId, $changes);
                }
            }

            http_response_code(200); echo json_encode(["success" => true, "message" => "Invoice entry updated successfully."]);
        } elseif ($success) { http_response_code(200); echo json_encode(["error" => "No changes made to invoice entry."]);
        } else { http_response_code(500); echo json_encode(['error' => 'Failed to execute invoice entry update.']); }
    } catch (\PDOException $e) {
        error_log("DB error in editInvoiceEntry (ID: $id, Authority: $authority): " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not update invoice entry."]);
    }
}

function deleteInvoiceEntry(PDO $pdo, int $requestingUserId, string $authority): void {
    // Get data from both POST and JSON input
    $data = $_POST;
    
    // If we don't have key fields in POST data, try JSON
    if (empty($data['id'])) {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (strpos($contentType, 'json') !== false) {
            try {
                $jsonData = json_decode(file_get_contents('php://input'), true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($jsonData)) {
                    $data = $jsonData;
                }
            } catch (\Exception $e) {
                error_log("JSON parsing error in deleteInvoiceEntry: " . $e->getMessage());
            }
        }
    }
    
    $id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
    if (!$id) { http_response_code(400); echo json_encode(['error' => 'Invalid or missing entry ID.']); return; }

    try {
        $sql = "UPDATE kdd_invoice_entries SET is_deleted = 1 WHERE id = ? AND is_deleted = 0";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([$id]);

        if ($success && $stmt->rowCount() > 0) {
            http_response_code(200); echo json_encode(["success" => true, "message" => "Invoice entry marked as deleted."]);
            // Log change
            // $changes = [['column_name' => 'is_deleted', 'old_value' => 0, 'new_value' => 1]];
            // global $decoded_jwt;
                $authorityId = $decoded_jwt->authority_id ?? 0;
                logDatabaseChange($authorityId, $pdo, 'DELETE', "invoice_entries", $id, $requestingUserId, $changes);
        } elseif ($success) { http_response_code(404); echo json_encode(["error" => "Entry not found or already deleted."]);
        } else { http_response_code(500); echo json_encode(['error' => 'Failed to execute entry deletion.']); }
    } catch (\PDOException $e) {
        error_log("DB error in deleteInvoiceEntry (ID: $id, Authority: $authority): " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not delete invoice entry."]);
    }
}

// Helper: Get Companies (Uses $authority, NOT hardcoded 'fireguard')
function getCompanies(PDO $pdo, string $authority): void {
    try {
        // Assuming a general company table exists per authority, adjust table name if needed
        global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
        $sql = "SELECT id, name, phonenumber, email FROM kdd_companies WHERE is_deleted = 0 AND authority_id = ? ORDER BY name";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$authorityId]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch (\PDOException $e) {
        error_log("DB error in getCompanies ($authority): " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not retrieve companies."]);
    }
}

// Helper: Get Persons (Uses $authority)
function getPersons(PDO $pdo, string $authority): void {
    try {
        // Assuming a general person table exists per authority
        $sql = "SELECT id, firstname, lastname, phonenumber, mail FROM kdd_person_file WHERE is_deleted = 0 ORDER BY lastname, firstname";
        $stmt = $pdo->query($sql);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch (\PDOException $e) {
        error_log("DB error in getPersons ($authority): " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not retrieve persons."]);
    }
}



// Helper: Get Company Data (Uses $authority)
function getCompanyData(PDO $pdo, string $authority): void {
    // Try to get data from multiple sources - first URL query parameters, then JSON body
    $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
    
    // If not found in query parameters, try JSON body
    if (!$id) {
        try {
            $json = file_get_contents('php://input');
            if (!empty($json)) {
                $data = json_decode($json, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
                }
            }
        } catch (\Exception $e) {
            // Silently continue if JSON parsing fails
            error_log("JSON parsing error in getCompanyData: " . $e->getMessage());
        }
    }
    
    if (!$id) { 
        http_response_code(400); 
        echo json_encode(["error" => "Company ID is required."]); 
        return; 
    }

    try {
        $sql = "SELECT name, phonenumber, email FROM kdd_companies WHERE id = ? AND is_deleted = 0";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($data) { 
            http_response_code(200); 
            echo json_encode($data); 
        }
        else { 
            http_response_code(404); 
            echo json_encode(["error" => "Company not found."]); 
        }
    } catch (\PDOException $e) {
        error_log("DB error in getCompanyData (ID: $id, Authority: $authority): " . $e->getMessage());
        http_response_code(500); 
        echo json_encode(["error" => "Could not retrieve company data."]);
    }
}

// Helper: Get Person Data (Uses $authority)
function getPersonData(PDO $pdo, string $authority): void {
    // Try to get data from multiple sources - first URL query parameters, then JSON body
    $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
    
    // If not found in query parameters, try JSON body
    if (!$id) {
        try {
            $json = file_get_contents('php://input');
            if (!empty($json)) {
                $data = json_decode($json, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
                }
            }
        } catch (\Exception $e) {
            // Silently continue if JSON parsing fails
            error_log("JSON parsing error in getPersonData: " . $e->getMessage());
        }
    }
    
    if (!$id) { 
        http_response_code(400); 
        echo json_encode(["error" => "Person ID is required."]); 
        return; 
    }

    try {
        $sql = "SELECT firstname, lastname, phonenumber, mail FROM kdd_person_file WHERE id = ? AND is_deleted = 0";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($data) { 
            http_response_code(200); 
            echo json_encode($data); 
        }
        else { 
            http_response_code(404); 
            echo json_encode(["error" => "Person not found."]); 
        }
    } catch (\PDOException $e) {
        error_log("DB error in getPersonData (ID: $id, Authority: $authority): " . $e->getMessage());
        http_response_code(500); 
        echo json_encode(["error" => "Could not retrieve person data."]);
    }
}

?>