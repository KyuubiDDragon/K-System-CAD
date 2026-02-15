<?php
/**
 * Backend Endpoint: search/index.php
 * Handles global search across multiple entity types.
 * Uses JWT Authentication compatible with K-Systems architecture.
 */
declare(strict_types=1);

// --- Core Initialisation ---
require_once __DIR__ . '/../bootstrap.php';

// --- Dependencies & DB Connection ---
try {
    // $pdo should already be defined by bootstrap.php
    if (!isset($pdo) || !($pdo instanceof PDO)) {
        throw new Exception("Database connection not established");
    }
} catch (Exception $e) {
    http_response_code(500); 
    echo json_encode(["error" => "Database connection failed: " . $e->getMessage()]); 
    exit();
}

// --- Authentication ---
try {
    require_once __DIR__ . '/../auth_check.php'; // Checks JWT token, defines $decoded_jwt or exits
} catch (\Throwable $e) {
    error_log("Critical error during auth check include: " . $e->getMessage());
    http_response_code(500); 
    echo json_encode(["error" => "Authentication system error."]); 
    exit();
}

// --- User Context & Authority Validation ---
$userId = $decoded_jwt->userId ?? null;
$userPermissions = $decoded_jwt->permissions ?? [];
$authority = $decoded_jwt->authority ?? null;
$authorityId = $decoded_jwt->authority_id ?? null;

if ($userId === null || !is_int($userId) || $authority === null || !is_string($authority) || $authorityId === null) {
    http_response_code(400); 
    echo json_encode(["error" => "Invalid token payload."]); 
    exit();
}

// Check if user has permission to use search
require_once __DIR__ . '/../utils/permission_helper.php';
if (!hasAllPermissions($userPermissions) && !hasPermission($userPermissions, 'CAN_LOGIN')) {
    http_response_code(403); 
    echo json_encode(["error" => "Permission denied for search functionality."]); 
    exit();
}

// $pdo is already defined by bootstrap.php
$input = json_decode(file_get_contents('php://input'), true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate JSON input
    if ($input === null && json_last_error() !== JSON_ERROR_NONE) {
        header('Content-Type: application/json; charset=UTF-8');
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid JSON in request body',
            'error' => json_last_error_msg()
        ]);
        exit();
    }
    $query = $input['query'] ?? '';
    $category = $input['category'] ?? null;
    $fuzzy = $input['fuzzy'] ?? true;
    $limit = min($input['limit'] ?? 10, 50); // Max 50 results per category
    $authority_id = $authorityId; // Use JWT authority_id
    
    $results = [
        'person' => [],
        'company' => [],
        'report' => [],
        'document' => [],
        'vehicle' => [],
        'employee' => [],
        'calendar' => [],
        'todo' => [],
        'blackboard' => [],
        'invoice' => [],
        'message' => [],
        'application' => [],
        'note' => [],
        'map' => [],
        'dispatch' => []
    ];
    
    // Helper function for fuzzy search
    function createFuzzyPattern($query) {
        $pattern = '%';
        for ($i = 0; $i < strlen($query); $i++) {
            $pattern .= $query[$i] . '%';
        }
        return $pattern;
    }
    
    // Helper function to calculate relevance score
    function calculateRelevance($needle, $haystack) {
        $needle = strtolower($needle);
        $haystack = strtolower($haystack);
        
        // Exact match
        if ($needle === $haystack) return 100;
        
        // Starts with
        if (strpos($haystack, $needle) === 0) return 90;
        
        // Contains
        if (strpos($haystack, $needle) !== false) return 80;
        
        // Fuzzy match
        similar_text($needle, $haystack, $percent);
        return $percent;
    }
    
    try {
        $searchPattern = $fuzzy ? createFuzzyPattern($query) : "%$query%";
        
        // Search in persons
        if (!$category || $category === 'person') {
            $stmt = $pdo->prepare("
                SELECT 
                    p.id,
                    p.lastname,
                    p.firstname,
                    p.mail as email,
                    p.phonenumber as telefon,
                    p.wanted,
                    e.id as employee_id,
                    e.name as employee_name
                FROM kdd_person_file p
                LEFT JOIN kdd_employee e ON p.id = e.linked_employee
                WHERE p.authority_id = :authority_id
                AND p.is_deleted = 0
                AND (
                    CONCAT(p.firstname, ' ', p.lastname) LIKE :pattern
                    OR CONCAT(p.lastname, ' ', p.firstname) LIKE :pattern
                    OR p.mail LIKE :pattern
                    OR p.phonenumber LIKE :pattern
                )
                ORDER BY 
                    CASE 
                        WHEN CONCAT(p.firstname, ' ', p.lastname) LIKE :exactStart THEN 1
                        WHEN CONCAT(p.lastname, ' ', p.firstname) LIKE :exactStart THEN 2
                        ELSE 3
                    END,
                    p.lastname, p.firstname
                LIMIT :limit
            ");
            
            $exactStart = "$query%";
            $stmt->bindParam(':authority_id', $authority_id);
            $stmt->bindParam(':pattern', $searchPattern);
            $stmt->bindParam(':exactStart', $exactStart);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            $persons = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $fullName = $row['firstname'] . ' ' . $row['lastname'];
                $persons[] = [
                    'id' => $row['id'],
                    'type' => 'person',
                    'title' => $fullName,
                    'subtitle' => $row['email'] ?: $row['telefon'],
                    'badge' => $row['wanted'] ? 'Gesucht' : 'Normal',
                    'badgeColor' => $row['wanted'] ? 'error' : 'grey',
                    'route' => "/person?id={$row['id']}",
                    'primaryRelation' => $row['employee_id'] ? [
                        'type' => 'employee',
                        'id' => $row['employee_id'],
                        'label' => $row['employee_name'] ?: 'Mitarbeiter',
                        'icon' => 'mdi-badge-account'
                    ] : null,
                    'relevance' => calculateRelevance($query, $fullName)
                ];
            }
            
            // Sort by relevance
            usort($persons, function($a, $b) {
                return $b['relevance'] - $a['relevance'];
            });
            
            $results['person'] = $persons;
        }
        
        // Search in companies
        if (!$category || $category === 'company') {
            $stmt = $pdo->prepare("
                SELECT 
                    id,
                    name,
                    location as ort,
                    '' as plz,
                    '' as strasse,
                    '' as housenumber,
                    phonenumber as telefon,
                    email,
                    contact_person as ansprechpartner
                FROM kdd_companies
                WHERE authority_id = :authority_id
                AND is_deleted = 0
                AND (
                    name LIKE :pattern
                    OR location LIKE :pattern
                    OR contact_person LIKE :pattern
                    OR email LIKE :pattern
                )
                ORDER BY 
                    CASE 
                        WHEN name LIKE :exactStart THEN 1
                        ELSE 2
                    END,
                    name
                LIMIT :limit
            ");
            
            $exactStart = "$query%";
            $stmt->bindParam(':authority_id', $authority_id);
            $stmt->bindParam(':pattern', $searchPattern);
            $stmt->bindParam(':exactStart', $exactStart);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            $companies = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $companies[] = [
                    'id' => $row['id'],
                    'type' => 'company',
                    'title' => $row['name'],
                    'subtitle' => $row['ort'] . ($row['ansprechpartner'] ? " • {$row['ansprechpartner']}" : ''),
                    'route' => "/company?id={$row['id']}",
                    'relevance' => calculateRelevance($query, $row['name'])
                ];
            }
            
            usort($companies, function($a, $b) {
                return $b['relevance'] - $a['relevance'];
            });
            
            $results['company'] = $companies;
        }
        
        // Search in reports
        if (!$category || $category === 'report') {
            $stmt = $pdo->prepare("
                SELECT 
                    r.id,
                    r.title as titel,
                    r.report_date as datum,
                    rs.name as status,
                    rc.name as category_name,
                    u.username as ersteller
                FROM kdd_reports r
                LEFT JOIN kdd_report_category rc ON r.category_id = rc.id
                LEFT JOIN kdd_users u ON r.creator = u.id
                LEFT JOIN kdd_report_status rs ON r.report_status_id = rs.id
                WHERE r.authority_id = :authority_id
                AND r.is_deleted = 0
                AND (
                    CAST(r.id AS CHAR) LIKE :pattern
                    OR r.title LIKE :pattern
                    OR r.text LIKE :pattern
                    OR u.username LIKE :pattern
                )
                ORDER BY r.report_date DESC
                LIMIT :limit
            ");
            
            $stmt->bindParam(':authority_id', $authority_id);
            $stmt->bindParam(':pattern', $searchPattern);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            $reports = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $reports[] = [
                    'id' => $row['id'],
                    'type' => 'report',
                    'title' => $row['titel'] ?: "Bericht #{$row['id']}",
                    'subtitle' => ($row['datum'] ? date('d.m.Y', strtotime($row['datum'])) : 'Kein Datum') . " • " . ($row['ersteller'] ?: 'Unbekannt'),
                    'badge' => $row['status'] ?: 'Entwurf',
                    'badgeColor' => $row['status'] === 'Abgeschlossen' ? 'success' : ($row['status'] ? 'warning' : 'grey'),
                    'route' => "/report?id={$row['id']}",
                    'metadata' => [
                        'category' => $row['category_name']
                    ],
                    'relevance' => max(
                        calculateRelevance($query, (string)$row['id']),
                        calculateRelevance($query, $row['titel'] ?: '')
                    )
                ];
            }
            
            usort($reports, function($a, $b) {
                return $b['relevance'] - $a['relevance'];
            });
            
            $results['report'] = $reports;
        }
        
        // Search in documents
        if (!$category || $category === 'document') {
            // Use higher limit for documents to ensure we get results from multiple areas
            $documentLimit = 50;

            $stmt = $pdo->prepare("
                SELECT
                    d.id,
                    d.title as name,
                    d.content as beschreibung,
                    dc.name as bereich,
                    dc.area_id,
                    da.key as area_key,
                    da.name as area_name,
                    d.created_at,
                    u.username as ersteller
                FROM kdd_doc_documents d
                LEFT JOIN kdd_users u ON d.creator = u.id
                LEFT JOIN kdd_doc_categories dc ON d.category_id = dc.id
                LEFT JOIN kdd_doc_areas da ON dc.area_id = da.id
                WHERE d.authority_id = :authority_id
                AND d.is_deleted = 0
                AND (
                    d.title LIKE :pattern
                    OR d.content LIKE :pattern
                    OR dc.name LIKE :pattern
                    OR da.name LIKE :pattern
                )
                LIMIT :limit
            ");

            $stmt->bindParam(':authority_id', $authority_id);
            $stmt->bindParam(':pattern', $searchPattern);
            $stmt->bindParam(':limit', $documentLimit, PDO::PARAM_INT);
            $stmt->execute();

            $documents = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                // Determine the correct route based on area_key
                $route = "/document?id={$row['id']}"; // Default fallback

                if ($row['area_key']) {
                    // Use new document area system: /documentarea/{key}?id={id}
                    $route = "/documentarea/{$row['area_key']}?id={$row['id']}";
                }

                $documents[] = [
                    'id' => $row['id'],
                    'type' => 'document',
                    'title' => $row['name'],
                    'subtitle' => $row['beschreibung'] ?: $row['bereich'],
                    'route' => $route,
                    'metadata' => [
                        'area' => $row['bereich'],
                        'area_key' => $row['area_key'],
                        'area_name' => $row['area_name'],
                        'area_id' => $row['area_id'],
                        'created' => $row['created_at'],
                        'creator' => $row['ersteller']
                    ],
                    'relevance' => calculateRelevance($query, $row['name'])
                ];
            }

            usort($documents, function($a, $b) {
                return $b['relevance'] - $a['relevance'];
            });

            $results['document'] = $documents;
        }
        
        // Search in vehicles (both dispatch vehicles and vehicle files)
        if (!$category || $category === 'vehicle') {
            // First search in vehicle files (kdd_vehicle_file)
            $stmt = $pdo->prepare("
                SELECT 
                    vf.id,
                    vf.numberplate,
                    vf.brand,
                    vf.model,
                    vf.color,
                    vf.stolen,
                    vf.wanted,
                    'file' as vehicle_type
                FROM kdd_vehicle_file vf
                WHERE vf.authority_id = :authority_id
                AND vf.is_deleted = 0
                AND (
                    vf.numberplate LIKE :pattern
                    OR vf.brand LIKE :pattern
                    OR vf.model LIKE :pattern
                    OR vf.color LIKE :pattern
                )
                ORDER BY vf.numberplate
                LIMIT :limit
            ");
            
            $stmt->bindParam(':authority_id', $authority_id);
            $stmt->bindParam(':pattern', $searchPattern);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            $vehicles = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $badges = [];
                if ($row['stolen']) $badges[] = 'Gestohlen';
                if ($row['wanted']) $badges[] = 'Gesucht';
                
                $vehicles[] = [
                    'id' => $row['id'],
                    'type' => 'vehicle',
                    'title' => $row['numberplate'],
                    'subtitle' => $row['brand'] . " " . $row['model'] . ($row['color'] ? " • " . $row['color'] : ''),
                    'badge' => !empty($badges) ? implode(' • ', $badges) : null,
                    'badgeColor' => !empty($badges) ? 'error' : null,
                    'route' => "/vehicleFile?id={$row['id']}",
                    'relevance' => max(
                        calculateRelevance($query, $row['numberplate']),
                        calculateRelevance($query, $row['brand'] . ' ' . $row['model'])
                    )
                ];
            }
            
            // Also search in dispatch vehicles (kdd_vehicles) if needed
            if (count($vehicles) < $limit) {
                $remainingLimit = $limit - count($vehicles);
                $stmt = $pdo->prepare("
                    SELECT 
                        v.id,
                        v.numberplate as kennzeichen,
                        v.title as funkrufname,
                        v.rank as fahrzeugtyp,
                        CASE WHEN v.active = 1 THEN 'Einsatzbereit' ELSE 'Nicht einsatzbereit' END as status,
                        'dispatch' as vehicle_type
                    FROM kdd_vehicles v
                    WHERE v.authority_id = :authority_id
                    AND (
                        v.numberplate LIKE :pattern
                        OR v.title LIKE :pattern
                        OR v.rank LIKE :pattern
                    )
                    ORDER BY v.numberplate
                    LIMIT :limit
                ");
                
                $stmt->bindParam(':authority_id', $authority_id);
                $stmt->bindParam(':pattern', $searchPattern);
                $stmt->bindParam(':limit', $remainingLimit, PDO::PARAM_INT);
                $stmt->execute();
                
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $vehicles[] = [
                        'id' => $row['id'],
                        'type' => 'vehicle',
                        'title' => $row['kennzeichen'],
                        'subtitle' => $row['funkrufname'] . " • " . $row['fahrzeugtyp'] . " (Einsatzfahrzeug)",
                        'badge' => $row['status'],
                        'badgeColor' => $row['status'] === 'Einsatzbereit' ? 'success' : 'error',
                        'route' => "/vehicle?id={$row['id']}",
                        'relevance' => max(
                            calculateRelevance($query, $row['kennzeichen']),
                            calculateRelevance($query, $row['funkrufname'])
                        )
                    ];
                }
            }
            
            usort($vehicles, function($a, $b) {
                return $b['relevance'] - $a['relevance'];
            });
            
            $results['vehicle'] = $vehicles;
        }
        
        // Search in employees
        if (!$category || $category === 'employee') {
            $stmt = $pdo->prepare("
                SELECT 
                    e.id,
                    r.name as abteilung,
                    r.name as position,
                    e.entrydate as eintrittsdatum,
                    e.name,
                    e.mail as email,
                    e.phonenumber as telefon,
                    p.id as person_id
                FROM kdd_employee e
                LEFT JOIN kdd_person_file p ON e.linked_employee = p.id
                LEFT JOIN kdd_employee_rank r ON e.rank_id = r.id
                WHERE e.authority_id = :authority_id
                AND e.is_terminated = 0
                AND (
                    e.name LIKE :pattern
                    OR r.name LIKE :pattern
                    OR e.mail LIKE :pattern
                    OR e.phonenumber LIKE :pattern
                )
                ORDER BY e.name
                LIMIT :limit
            ");
            
            $stmt->bindParam(':authority_id', $authority_id);
            $stmt->bindParam(':pattern', $searchPattern);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            $employees = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $employees[] = [
                    'id' => $row['id'],
                    'type' => 'employee',
                    'title' => $row['name'],
                    'subtitle' => $row['position'] . " • " . $row['abteilung'],
                    'route' => "/employee?id={$row['id']}",
                    'primaryRelation' => $row['person_id'] ? [
                        'type' => 'person',
                        'id' => $row['person_id'],
                        'label' => 'Person',
                        'icon' => 'mdi-account'
                    ] : null,
                    'relevance' => calculateRelevance($query, $row['name'])
                ];
            }
            
            usort($employees, function($a, $b) {
                return $b['relevance'] - $a['relevance'];
            });
            
            $results['employee'] = $employees;
        }
        
        // Search in calendar events
        if (!$category || $category === 'calendar') {
            $stmt = $pdo->prepare("
                SELECT 
                    c.id,
                    c.title,
                    c.content,
                    c.contentFull,
                    c.start_date,
                    c.end_date,
                    c.color,
                    '' as creator
                FROM kdd_calendar c
                WHERE c.authority_id = :authority_id
                AND c.is_deleted = 0
                AND (
                    c.title LIKE :pattern
                    OR c.content LIKE :pattern
                    OR c.contentFull LIKE :pattern
                )
                ORDER BY c.start_date DESC
                LIMIT :limit
            ");
            
            $stmt->bindParam(':authority_id', $authority_id);
            $stmt->bindParam(':pattern', $searchPattern);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            $calendars = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $calendars[] = [
                    'id' => $row['id'],
                    'type' => 'calendar',
                    'title' => $row['title'] ?: 'Kalendereintrag',
                    'subtitle' => ($row['start_date'] ? date('d.m.Y H:i', strtotime($row['start_date'])) : 'Kein Datum') . " • " . ($row['creator'] ?: 'System'),
                    'badge' => 'Termin',
                    'badgeColor' => 'primary',
                    'route' => "/calendar?id={$row['id']}",
                    'metadata' => [
                        'start_date' => $row['start_date'],
                        'end_date' => $row['end_date'],
                        'color' => $row['color']
                    ],
                    'relevance' => max(
                        calculateRelevance($query, $row['title'] ?: ''),
                        calculateRelevance($query, $row['content'] ?: '')
                    )
                ];
            }
            
            usort($calendars, function($a, $b) {
                return $b['relevance'] - $a['relevance'];
            });
            
            $results['calendar'] = $calendars;
        }
        
        // Search in todo items
        if (!$category || $category === 'todo') {
            $stmt = $pdo->prepare("
                SELECT 
                    t.id,
                    t.title,
                    t.short_description,
                    t.description,
                    t.created_at,
                    t.due_date,
                    t.completed,
                    tl.name as list_name,
                    u.username as creator
                FROM kdd_ta_todos t
                LEFT JOIN kdd_ta_todolists tl ON t.list_id = tl.id
                LEFT JOIN kdd_users u ON t.user_id = u.id
                WHERE t.authority_id = :authority_id
                AND (
                    t.title LIKE :pattern
                    OR t.short_description LIKE :pattern
                    OR t.description LIKE :pattern
                    OR tl.name LIKE :pattern
                )
                ORDER BY t.created_at DESC
                LIMIT :limit
            ");
            
            $stmt->bindParam(':authority_id', $authority_id);
            $stmt->bindParam(':pattern', $searchPattern);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            $todos = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $status = $row['completed'] ? 'Erledigt' : 'Offen';
                $todos[] = [
                    'id' => $row['id'],
                    'type' => 'todo',
                    'title' => $row['title'] ?: 'Aufgabe',
                    'subtitle' => ($row['list_name'] ? $row['list_name'] . ' • ' : '') . ($row['creator'] ?: 'Unbekannt'),
                    'badge' => $status,
                    'badgeColor' => $row['completed'] ? 'success' : 'grey',
                    'route' => "/todo?id={$row['id']}",
                    'metadata' => [
                        'due_date' => $row['due_date'],
                        'list' => $row['list_name'],
                        'completed' => $row['completed']
                    ],
                    'relevance' => max(
                        calculateRelevance($query, $row['title'] ?: ''),
                        calculateRelevance($query, $row['short_description'] ?: '')
                    )
                ];
            }
            
            usort($todos, function($a, $b) {
                return $b['relevance'] - $a['relevance'];
            });
            
            $results['todo'] = $todos;
        }
        
        // Search in blackboard entries
        if (!$category || $category === 'blackboard') {
            $stmt = $pdo->prepare("
                SELECT 
                    b.id,
                    b.title,
                    b.text,
                    b.author,
                    b.created,
                    b.blackboard
                FROM kdd_blackboard b
                WHERE b.authority_id = :authority_id
                AND b.deleted = 0
                AND (
                    b.title LIKE :pattern
                    OR b.text LIKE :pattern
                    OR b.author LIKE :pattern
                )
                ORDER BY b.created DESC
                LIMIT :limit
            ");
            
            $stmt->bindParam(':authority_id', $authority_id);
            $stmt->bindParam(':pattern', $searchPattern);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            $blackboards = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $blackboards[] = [
                    'id' => $row['id'],
                    'type' => 'blackboard',
                    'title' => $row['title'] ?: 'Ankündigung',
                    'subtitle' => ($row['created'] ? date('d.m.Y', strtotime($row['created'])) : 'Kein Datum') . " • " . ($row['author'] ?: 'Unbekannt'),
                    'badge' => $row['blackboard'] ?: 'Mitteilung',
                    'badgeColor' => 'info',
                    'route' => "/blackboard?id={$row['id']}",
                    'metadata' => [
                        'blackboard' => $row['blackboard'],
                        'created' => $row['created']
                    ],
                    'relevance' => max(
                        calculateRelevance($query, $row['title'] ?: ''),
                        calculateRelevance($query, $row['text'] ?: '')
                    )
                ];
            }
            
            usort($blackboards, function($a, $b) {
                return $b['relevance'] - $a['relevance'];
            });
            
            $results['blackboard'] = $blackboards;
        }
        
        // Search in invoices
        if (!$category || $category === 'invoice') {
            $stmt = $pdo->prepare("
                SELECT 
                    i.id,
                    i.title,
                    i.customer,
                    i.description,
                    i.account,
                    i.created_at,
                    i.is_paid,
                    i.is_sent,
                    i.is_delivered
                FROM kdd_invoices i
                WHERE i.authority_id = :authority_id
                AND i.is_deleted = 0
                AND (
                    i.title LIKE :pattern
                    OR i.customer LIKE :pattern
                    OR i.description LIKE :pattern
                    OR i.account LIKE :pattern
                )
                ORDER BY i.created_at DESC
                LIMIT :limit
            ");
            
            $stmt->bindParam(':authority_id', $authority_id);
            $stmt->bindParam(':pattern', $searchPattern);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            $invoices = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                // Determine status based on payment/delivery flags
                $status = 'Offen';
                $badgeColor = 'warning';
                if ($row['is_paid']) {
                    $status = 'Bezahlt';
                    $badgeColor = 'success';
                } elseif ($row['is_delivered']) {
                    $status = 'Geliefert';
                    $badgeColor = 'info';
                } elseif ($row['is_sent']) {
                    $status = 'Gesendet';
                    $badgeColor = 'primary';
                }
                
                $invoices[] = [
                    'id' => $row['id'],
                    'type' => 'invoice',
                    'title' => $row['title'] ?: "Rechnung #{$row['id']}",
                    'subtitle' => ($row['customer'] ?: 'Kein Kunde') . " • " . $status,
                    'badge' => $status,
                    'badgeColor' => $badgeColor,
                    'route' => "/invoice?id={$row['id']}",
                    'metadata' => [
                        'customer' => $row['customer'],
                        'is_paid' => $row['is_paid'],
                        'is_sent' => $row['is_sent'],
                        'is_delivered' => $row['is_delivered']
                    ],
                    'relevance' => max(
                        calculateRelevance($query, $row['title'] ?: ''),
                        calculateRelevance($query, $row['customer'] ?: '')
                    )
                ];
            }
            
            usort($invoices, function($a, $b) {
                return $b['relevance'] - $a['relevance'];
            });
            
            $results['invoice'] = $invoices;
        }
        
        // Search in messages
        if (!$category || $category === 'message') {
            $stmt = $pdo->prepare("
                SELECT 
                    m.id,
                    m.title,
                    m.body,
                    m.created_at,
                    m.sender_id,
                    m.recipient_id,
                    m.is_read,
                    us.username as sender_name,
                    ur.username as recipient_name
                FROM kdd_messages m
                LEFT JOIN kdd_users us ON m.sender_id = us.id
                LEFT JOIN kdd_users ur ON m.recipient_id = ur.id
                WHERE m.authority_id = :authority_id
                AND m.deleted_by_sender = 0 
                AND m.deleted_by_recipient = 0
                AND (
                    m.title LIKE :pattern
                    OR m.body LIKE :pattern
                    OR us.username LIKE :pattern
                    OR ur.username LIKE :pattern
                )
                ORDER BY m.created_at DESC
                LIMIT :limit
            ");
            
            $stmt->bindParam(':authority_id', $authority_id);
            $stmt->bindParam(':pattern', $searchPattern);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            $messages = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $messages[] = [
                    'id' => $row['id'],
                    'type' => 'message',
                    'title' => $row['title'] ?: 'Nachricht',
                    'subtitle' => ($row['sender_name'] ?: 'Unbekannt') . " → " . ($row['recipient_name'] ?: 'Unbekannt'),
                    'badge' => $row['is_read'] ? 'Gelesen' : 'Ungelesen',
                    'badgeColor' => $row['is_read'] ? 'success' : 'warning',
                    'route' => "/message?id={$row['id']}",
                    'metadata' => [
                        'sender_id' => $row['sender_id'],
                        'recipient_id' => $row['recipient_id'],
                        'sender_name' => $row['sender_name'],
                        'recipient_name' => $row['recipient_name'],
                        'created' => $row['created_at']
                    ],
                    'relevance' => max(
                        calculateRelevance($query, $row['title'] ?: ''),
                        calculateRelevance($query, $row['body'] ?: '')
                    )
                ];
            }
            
            usort($messages, function($a, $b) {
                return $b['relevance'] - $a['relevance'];
            });
            
            $results['message'] = $messages;
        }

        // Search in applications
        if (!$category || $category === 'application') {
            $stmt = $pdo->prepare("
                SELECT 
                    a.id,
                    a.name,
                    a.email,
                    a.info,
                    a.status,
                    a.added,
                    a.type,
                    a.jobinterviewDate
                FROM kdd_applicant a
                WHERE a.authority_id = :authority_id
                AND (
                    a.name LIKE :pattern
                    OR a.email LIKE :pattern
                    OR a.info LIKE :pattern
                )
                ORDER BY a.added DESC
                LIMIT :limit
            ");
            
            $stmt->bindParam(':authority_id', $authority_id);
            $stmt->bindParam(':pattern', $searchPattern);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            $applications = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $typeLabels = [1 => 'Feuerwehr', 2 => 'Verwaltung'];
                $applications[] = [
                    'id' => $row['id'],
                    'type' => 'application',
                    'title' => $row['name'] ?: 'Bewerbung',
                    'subtitle' => ($row['email'] ?: 'Keine E-Mail') . " • " . ($typeLabels[$row['type']] ?: 'Unbekannt'),
                    'badge' => ucfirst($row['status']) ?: 'Ausstehend',
                    'badgeColor' => $row['status'] === 'approved' ? 'success' : ($row['status'] === 'rejected' ? 'error' : 'warning'),
                    'route' => "/application?id={$row['id']}",
                    'metadata' => [
                        'email' => $row['email'],
                        'interview_date' => $row['jobinterviewDate'],
                        'type' => $typeLabels[$row['type']] ?? 'Unbekannt'
                    ],
                    'relevance' => max(
                        calculateRelevance($query, $row['name'] ?: ''),
                        calculateRelevance($query, $row['email'] ?: '')
                    )
                ];
            }
            
            usort($applications, function($a, $b) {
                return $b['relevance'] - $a['relevance'];
            });
            
            $results['application'] = $applications;
        }
        
        // Search in notes
        if (!$category || $category === 'note') {
            $stmt = $pdo->prepare("
                SELECT 
                    n.id,
                    n.content,
                    n.created_at,
                    n.updated_at,
                    u.username as creator
                FROM kdd_notes n
                LEFT JOIN kdd_users u ON n.user_id = u.id
                WHERE n.authority_id = :authority_id
                AND n.content LIKE :pattern
                ORDER BY n.updated_at DESC
                LIMIT :limit
            ");
            
            $stmt->bindParam(':authority_id', $authority_id);
            $stmt->bindParam(':pattern', $searchPattern);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            $notes = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $contentPreview = substr(strip_tags($row['content']), 0, 100) . '...';
                $notes[] = [
                    'id' => $row['id'],
                    'type' => 'note',
                    'title' => 'Notiz: ' . $contentPreview,
                    'subtitle' => ($row['creator'] ?: 'Unbekannt') . " • " . ($row['updated_at'] ? date('d.m.Y', strtotime($row['updated_at'])) : 'Kein Datum'),
                    'badge' => 'Notiz',
                    'badgeColor' => 'info',
                    'route' => "/note?id={$row['id']}",
                    'metadata' => [
                        'creator' => $row['creator'],
                        'updated' => $row['updated_at']
                    ],
                    'relevance' => calculateRelevance($query, $row['content'] ?: '')
                ];
            }
            
            usort($notes, function($a, $b) {
                return $b['relevance'] - $a['relevance'];
            });
            
            $results['note'] = $notes;
        }
        
        // Search in map locations
        if (!$category || $category === 'map') {
            $stmt = $pdo->prepare("
                SELECT 
                    m.id,
                    m.name,
                    m.ceo,
                    m.location,
                    m.category_id,
                    mc.name as category_name
                FROM kdd_map m
                LEFT JOIN kdd_map_category mc ON m.category_id = mc.id
                WHERE m.authority_id = :authority_id
                AND m.is_deleted = 0
                AND (
                    m.name LIKE :pattern
                    OR m.ceo LIKE :pattern
                    OR m.location LIKE :pattern
                )
                ORDER BY m.name
                LIMIT :limit
            ");
            
            $stmt->bindParam(':authority_id', $authority_id);
            $stmt->bindParam(':pattern', $searchPattern);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            $maps = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $maps[] = [
                    'id' => $row['id'],
                    'type' => 'map',
                    'title' => $row['name'] ?: 'Standort',
                    'subtitle' => ($row['location'] ?: 'Keine Adresse') . ($row['ceo'] ? " • CEO: {$row['ceo']}" : ''),
                    'badge' => $row['category_name'] ?: 'Standort',
                    'badgeColor' => 'primary',
                    'route' => "/map?id={$row['id']}",
                    'metadata' => [
                        'location' => $row['location'],
                        'ceo' => $row['ceo'],
                        'category_id' => $row['category_id'],
                        'category_name' => $row['category_name']
                    ],
                    'relevance' => max(
                        calculateRelevance($query, $row['name'] ?: ''),
                        calculateRelevance($query, $row['location'] ?: '')
                    )
                ];
            }
            
            usort($maps, function($a, $b) {
                return $b['relevance'] - $a['relevance'];
            });
            
            $results['map'] = $maps;
        }
        
        // Search in dispatch units
        if (!$category || $category === 'dispatch') {
            $stmt = $pdo->prepare("
                SELECT 
                    d.id,
                    d.name,
                    d.status,
                    d.updated_at
                FROM kdd_dispatch d
                WHERE d.authority_id = :authority_id
                AND d.active = 1
                AND (
                    d.name LIKE :pattern
                    OR d.status LIKE :pattern
                )
                ORDER BY d.updated_at DESC
                LIMIT :limit
            ");
            
            $stmt->bindParam(':authority_id', $authority_id);
            $stmt->bindParam(':pattern', $searchPattern);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            $dispatches = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $dispatches[] = [
                    'id' => $row['id'],
                    'type' => 'dispatch',
                    'title' => $row['name'] ?: 'Einsatzeinheit',
                    'subtitle' => ($row['updated_at'] ? date('d.m.Y H:i', strtotime($row['updated_at'])) : 'Kein Datum'),
                    'badge' => $row['status'] ?: 'Unbekannt',
                    'badgeColor' => $row['status'] === 'available' ? 'success' : ($row['status'] === 'busy' ? 'error' : 'warning'),
                    'route' => "/dispatch?id={$row['id']}",
                    'metadata' => [
                        'status' => $row['status'],
                        'updated' => $row['updated_at']
                    ],
                    'relevance' => max(
                        calculateRelevance($query, $row['name'] ?: ''),
                        calculateRelevance($query, $row['status'] ?: '')
                    )
                ];
            }
            
            usort($dispatches, function($a, $b) {
                return $b['relevance'] - $a['relevance'];
            });
            
            $results['dispatch'] = $dispatches;
        }
        
        // Remove relevance scores from final output
        foreach ($results as $category => &$items) {
            if (is_array($items)) {
                foreach ($items as &$item) {
                    unset($item['relevance']);
                }
            }
        }
        
        // Filter out empty categories if no specific category was requested
        $filteredResults = [];
        foreach ($results as $cat => $items) {
            if (!empty($items)) {
                $filteredResults[$cat] = $items;
            }
        }
        
        // Always return a valid JSON response
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'success' => true,
            'results' => $category ? $results : $filteredResults,
            'query' => $query,
            'totalResults' => array_sum(array_map('count', $results))
        ]);
        
    } catch (Exception $e) {
        error_log("Search error: " . $e->getMessage());
        header('Content-Type: application/json; charset=UTF-8');
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Fehler bei der Suche',
            'error' => $e->getMessage()
        ]);
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // GET method for testing/debugging
    header('Content-Type: application/json; charset=UTF-8');
    $query = $_GET['query'] ?? '';
    $category = $_GET['category'] ?? null;
    
    if (empty($query)) {
        echo json_encode([
            'success' => true,
            'message' => 'Search endpoint is working. Use POST with query parameter.',
            'method' => 'GET',
            'authority_id' => $authorityId
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Use POST method for search queries',
            'received_query' => $query
        ]);
    }
} else {
    header('Content-Type: application/json; charset=UTF-8');
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>