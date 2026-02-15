<?php
/**
 * Backend Endpoint: admin/application/index.php
 * Handles ADMIN CRUD operations for Applications and Questions.
 * Uses Cookie-based Authentication and PDO database connection.
 * NOTE: Contains functions potentially duplicated in non-admin application endpoint.
 */
declare(strict_types=1);

// --- Core Initialisation ---
require_once __DIR__ . '/../../bootstrap.php';



// --- Dependencies & DB Connection ---
try {
    require_once __DIR__ . '/../../db.php'; // Defines $pdo
} catch (Exception $e) {
    http_response_code(500); echo json_encode(["error" => "Database connection failed."]); exit();
}
require_once __DIR__ . '/../../logging/logging.php';
require_once __DIR__ . '/../../calendar/calendar.php'; // Needs PDO-compatible functions

// --- Authentication ---
try {
    require_once __DIR__ . '/../../auth_check.php'; // Checks cookie, defines $decoded_jwt or exits
} catch (\Throwable $e) {
    error_log("Critical error during auth check include: " . $e->getMessage());
    http_response_code(500); echo json_encode(["error" => "Authentication system error."]); exit();
}

// --- User Context & Authority Validation ---
$userId = $decoded_jwt->userId ?? null;
$userPermissions = $decoded_jwt->permissions ?? [];
$authority = $decoded_jwt->authority ?? null; // Authority from user's token
$authorityId = $decoded_jwt->authority_id ?? null; // Extract authority ID for queries

if ($userId === null || !is_int($userId) || $authority === null || !is_string($authority) || $authorityId === null) {
    http_response_code(400); echo json_encode(["error" => "Invalid token payload."]); exit();
}
require_once __DIR__ . '/../../utils/authority_helper.php';
if (!isValidAuthority($pdo, $authorityId)) {
    http_response_code(403); echo json_encode(["error" => "Invalid authority context."]); exit();
}

// Feature-Zugriff prüfen
if (!hasFeatureAccess($pdo, $authorityId, 'application')) {
    http_response_code(403); echo json_encode(["error" => "This authority doesn't have access to apartment features."]); exit();
}

// --- Authorization & Action Routing ---
$action = $_REQUEST['action'] ?? '';
$request_method = $_SERVER['REQUEST_METHOD'];

// Map actions to required ADMIN permissions
// **WICHTIG:** Passe diese Berechtigungsnamen genau an dein System an!
$permissions_map = [
    'getApplicants'         => 'ADMIN_READ_APPLICATION',
    'getQuestions'          => 'ADMIN_READ_APPLICATION',
    'getPendingApplicant'   => 'ADMIN_READ_APPLICATION',
    'submitApplication'     => 'ADMIN_WRITE_APPLICATION',
    'saveQuestionSorting'   => 'ADMIN_WRITE_APPLICATION',
    'addQuestion'           => 'ADMIN_WRITE_APPLICATION',
    'saveQuestions'         => 'ADMIN_WRITE_APPLICATION',
    'deleteQuestion'        => 'ADMIN_WRITE_APPLICATION', // Assuming WRITE covers DELETE here based on original
];

$required_permission = $permissions_map[$action] ?? 'ACTION_NOT_DEFINED';
$has_permission = false;

if ($action === '') { http_response_code(400); echo json_encode(["error" => "No action specified."]); exit(); }
if ($required_permission === 'ACTION_NOT_DEFINED') { http_response_code(404); echo json_encode(['error' => 'Invalid action specified.']); exit(); }

// Load permission helper
require_once __DIR__ . '/../../utils/permission_helper.php';

// Check permission levels (ALL > ADMIN_WRITE > ADMIN_READ)
if (hasAllPermissions($userPermissions)) {
    $has_permission = true;
} elseif ($required_permission && hasPermission($userPermissions, $required_permission)) {
    $has_permission = true; // Has specific admin permission
} elseif ($required_permission === 'ADMIN_READ_APPLICATION' && hasPermission($userPermissions, 'ADMIN_WRITE_APPLICATION')) {
    $has_permission = true; // WRITE implies READ
}

// --- Execute Action or Deny ---
if ($has_permission) {
    $is_post_request = ($request_method === 'POST');

    switch ($action) {
        // GET Actions
        case 'getApplicants':       if ($request_method === 'GET') getApplicants($pdo, $authority, $authorityId); else MethodNotAllowed(); break;
        case 'getQuestions':        if ($request_method === 'GET') getQuestions($pdo, $authority, $authorityId); else MethodNotAllowed(); break;
        case 'getPendingApplicant': if ($request_method === 'GET') getPendingApplicant($pdo, $authority, $authorityId); else MethodNotAllowed(); break;

        // POST Actions
        case 'submitApplication':   if ($is_post_request) submitApplication($pdo, $userId, $authority, $authorityId); else MethodNotAllowed(); break;
        case 'saveQuestionSorting': if ($is_post_request) saveQuestionSorting($pdo, $userId, $authority, $authorityId); else MethodNotAllowed(); break;
        case 'addQuestion':         if ($is_post_request) addQuestion($pdo, $userId, $authority, $authorityId); else MethodNotAllowed(); break;
        case 'saveQuestions':       if ($is_post_request) saveQuestions($pdo, $userId, $authority, $authorityId); else MethodNotAllowed(); break;
        case 'deleteQuestion':      if ($is_post_request) deleteQuestion($pdo, $userId, $authority, $authorityId); else MethodNotAllowed(); break; // Consider DELETE method

        default: http_response_code(500); echo json_encode(['error' => 'Action routing error.']); break;
    }
} else {
    http_response_code(403); echo json_encode(["error" => "Permission denied for action: " . htmlspecialchars($action)]);
}

exit();




// --- Function Implementations (PDO Refactored - Copied & Verified) ---
// !! NOTE: Consider moving these functions to a shared include/class to avoid duplication. !!

function getApplicants(PDO $pdo, string $authority, int $authorityId): void {
     try {
        $sql = "SELECT
                   ap.id as applicant_id, ap.name, ap.birthdate, ap.email, ap.status, ap.info,
                   ap.type, ap.jobinterviewDate, ap.jobinterviewTime, ap.phonenumber, ap.added,
                   aa.question_id as aa_question_id, aa.answer,
                   aq.id as aq_question_id, aq.question, aq.type as aq_type, aq.sort_order,
                   (CASE WHEN EXISTS (SELECT 1 FROM kdd_calendar c 
                                      WHERE c.authority_id = ? AND c.linked_to_table = ? AND c.linked_to_id = ap.id)
                         THEN 1 ELSE 0 END) as addToCalendar
                FROM kdd_applicant ap
                JOIN kdd_applicant_questions aq ON aq.authority_id = ? AND ap.type = aq.type AND aq.is_deleted = 0
                LEFT JOIN kdd_applicant_answers aa ON aa.authority_id = ? AND ap.id = aa.applicant_id AND aa.question_id = aq.id
                WHERE ap.authority_id = ?
                -- Add WHERE ap.is_deleted = 0 if applicable
                ORDER BY ap.id, aq.sort_order";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$authorityId, "kdd_applicant", $authorityId, $authorityId, $authorityId]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Group results by applicant ID in PHP
        $applicants = [];
        foreach ($results as $row) {
            $applicantId = $row["applicant_id"];
            if (!isset($applicants[$applicantId])) {
                $applicants[$applicantId] = [
                    "id" => $applicantId,
                    "name" => $row["name"],
                    "birthdate" => $row["birthdate"],
                    "email" => $row["email"],
                    "status" => $row["status"],
                    "info" => $row["info"],
                    "type" => $row["type"],
                    "phonenumber" => $row["phonenumber"],
                    "jobinterviewDate" => $row["jobinterviewDate"],
                    "jobinterviewTime" => $row["jobinterviewTime"],
                    "addToCalendar" => (bool)$row["addToCalendar"], // Cast to boolean
                    "added" => $row["added"],
                    "answers" => []
                ];
            }
            // Add answer only if it exists (LEFT JOIN might produce NULLs)
            if ($row['aq_question_id'] !== null) {
                 $applicants[$applicantId]["answers"][] = [
                    "applicant_id" => $applicantId,
                    "question_id" => (int)$row["aq_question_id"], // Question ID always exists due to JOIN
                    "question" => $row["question"],
                    "answer" => $row["answer"] // Can be NULL if no answer submitted
                ];
            }
        }

        http_response_code(200);
        echo json_encode(array_values($applicants)); // Return indexed array

    } catch (\PDOException $e) {
        error_log("DB error in getApplicants [ADMIN] ($authority): " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not retrieve applicants."]);
    }
}

function getQuestions(PDO $pdo, string $authority, int $authorityId): void {
     try {
        $sql = "SELECT * FROM kdd_applicant_questions WHERE authority_id = ? AND is_deleted = 0 ORDER BY sort_order";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$authorityId]);
        $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        http_response_code(200);
        echo json_encode($questions);
    } catch (\PDOException $e) {
        error_log("DB error in getQuestions [ADMIN] ($authority): " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not retrieve questions."]);
    }
}

function getPendingApplicant(PDO $pdo, string $authority, int $authorityId): void {
     try {
        $sql = "SELECT name, email, status, info, jobinterviewDate, jobinterviewTime, phonenumber FROM kdd_applicant
                WHERE authority_id = ? AND status = 'pending' AND is_deleted = 0 AND DATE(jobinterviewDate) >= CURDATE() 
                ORDER BY jobinterviewDate, jobinterviewTime";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$authorityId]);
        $applicants = $stmt->fetchAll(PDO::FETCH_ASSOC);
        http_response_code(200);
        echo json_encode($applicants);
    } catch (\PDOException $e) {
        error_log("DB error in getPendingApplicant [ADMIN] ($authority): " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not retrieve pending applicants."]);
    }
}

function submitApplication(PDO $pdo, int $requestingUserId, string $authority, int $authorityId): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        MethodNotAllowed(); 
        return;
    }
    
    try {
        // Get data from JSON instead of POST
        $data = getJsonRequestData();
        if (!$data) return;

        // --- Basic Validation ---
        $name = $data['name'] ?? null;
        $email = $data['email'] ?? null;
        $birthdate = !empty($data['birthdate']) ? date('Y-m-d', strtotime($data['birthdate'])) : null;
        $status = $data['status'] ?? 'pending';
        $info = $data['info'] ?? '';
        $type = $data['type'] ?? null;
        $phonenumber = $data['phonenumber'] ?? '';
        $jobinterviewDate = !empty($data['jobinterviewDate']) ? date('Y-m-d', strtotime($data['jobinterviewDate'])) : null;
        $jobinterviewTime = !empty($data['jobinterviewTime']) ? date('H:i:s', strtotime($data['jobinterviewTime'])) : null;
        $applicantId = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['default' => null]]);
        $newApplication = ($applicantId === null);
        $answers = isset($data['answers']) && is_array($data['answers']) ? $data['answers'] : [];
        $addToCalendar = filter_var($data['addToCalendar'] ?? false, FILTER_VALIDATE_BOOLEAN);


        if (empty($name) || empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL) || empty($birthdate) || empty($type)) {
             http_response_code(400); echo json_encode(['error' => 'Missing or invalid required fields (name, email, birthdate, type).']); return;
        }

        $pdo->beginTransaction();

        $idForCalendar = null;
        $logAction = '';
        $oldApplicantData = null;

        // 1. Insert or Update Applicant
        if ($newApplication) {
            $logAction = 'INSERT';
            $sqlApplicant = "INSERT INTO kdd_applicant (authority_id, name, email, birthdate, status, info, type, phonenumber, jobinterviewDate, jobinterviewTime)
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmtApplicant = $pdo->prepare($sqlApplicant);
            $stmtApplicant->execute([$authorityId, $name, $email, $birthdate, $status, $info, $type, $phonenumber, $jobinterviewDate, $jobinterviewTime]);
            $idForCalendar = $pdo->lastInsertId();
            if (!$idForCalendar) throw new \Exception("Failed to insert new applicant record.");

        } else {
            // Update existing
            $logAction = 'UPDATE';
            $idForCalendar = $applicantId;
            // Fetch old data for logging
            $oldApplicantData = getEntryById($pdo, $idForCalendar, "kdd_applicant");
            if (!$oldApplicantData) throw new \Exception("Applicant with ID {$idForCalendar} not found for update.");

            $sqlApplicant = "UPDATE kdd_applicant SET name=?, email=?, birthdate=?, status=?, info=?, type=?, phonenumber=?,
                                 jobinterviewDate=?, jobinterviewTime=?
                             WHERE id=? AND authority_id = ? AND is_deleted = 0";
            $stmtApplicant = $pdo->prepare($sqlApplicant);
            $success = $stmtApplicant->execute([$name, $email, $birthdate, $status, $info, $type, $phonenumber, $jobinterviewDate, $jobinterviewTime, $idForCalendar, $authorityId]);
            if (!$success) throw new \Exception("Failed to update applicant data.");
            // Optional: Check rowCount > 0 if needed
        }

        // 2. Process Answers (using INSERT ... ON DUPLICATE KEY UPDATE)
        // Assumes UNIQUE KEY on (applicant_id, question_id)
        $sqlAnswer = "INSERT INTO kdd_applicant_answers (authority_id, applicant_id, question_id, answer)
                      VALUES (?, ?, ?, ?)
                      ON DUPLICATE KEY UPDATE answer = VALUES(answer)";
        $stmtAnswer = $pdo->prepare($sqlAnswer);
        
        // Fetch old answers for logging comparison (more complex logging logic needed here)
        // ...
        foreach ($answers as $answerData) {
            $question_id = filter_var($answerData['question_id'] ?? null, FILTER_VALIDATE_INT);
            $answer = $answerData['answer'] ?? '';
            if ($question_id) {
                $stmtAnswer->execute([$authorityId, $idForCalendar, $question_id, $answer]);
            }
        }

        // 3. Handle Calendar Entry (Requires calendar.php functions to be PDO ready)
        $calendarTableName = "kdd_applicant";
        if ($addToCalendar && $jobinterviewDate && $jobinterviewTime) {
            $eventTitle = 'Bewerbung ' . $name; $eventContent = '';
            $eventContentFull = "Tel: " . ($phonenumber ?: 'N/A') . "\nMail: " . $email;
            if (calendarEntryExist($pdo, $authority, $calendarTableName, $idForCalendar)) { // Pass $pdo & $authority
                 editEntryToCalendar($pdo, $requestingUserId, $authority, $eventTitle, $eventContent, $jobinterviewDate, $jobinterviewTime, $eventContentFull, $calendarTableName, $idForCalendar); // Pass $pdo etc.
            } else {
                 addEntryToCalendar($pdo, $requestingUserId, $authority, $eventTitle, $eventContent, $jobinterviewDate, $jobinterviewTime, $eventContentFull, $calendarTableName, $idForCalendar); // Pass $pdo etc.
            }
        } else {
            if (calendarEntryExist($pdo, $authority, $calendarTableName, $idForCalendar)) { // Pass $pdo & $authority
                deleteEntryFromCalendar($pdo, $requestingUserId, $authority, $calendarTableName, $idForCalendar); // Pass $pdo etc.
            }
        }

        // Commit
        $pdo->commit();

        http_response_code($newApplication ? 201 : 200);
        echo json_encode(["success" => true, "message" => "Application submitted successfully.", "applicantId" => $idForCalendar]);

        // Log change (Simplified logging for now)
        $currentData = ['name'=>$name, 'email'=>$email, 'status'=>$status, 'type'=>$type, /*...*/ 'answers'=>count($answers)];
        $changes = getEntryChanges($oldApplicantData, $currentData);
        if (!empty($changes) || $newApplication) {
            global $decoded_jwt;
                $authorityId = $decoded_jwt->authority_id ?? 0;
                logDatabaseChange($authorityId, $pdo, $logAction, "applicant", $idForCalendar, $requestingUserId, $changes);
        }


     } catch(\PDOException | \Exception $e) {
         if ($pdo->inTransaction()) { $pdo->rollBack(); }
         error_log("Error in submitApplication [ADMIN] ($authority): " . $e->getMessage());
         http_response_code(500); echo json_encode(["error" => "Could not submit application: " . $e->getMessage()]);
     }
}

function saveQuestionSorting(PDO $pdo, int $requestingUserId, string $authority, int $authorityId): void {
    $data = json_decode(file_get_contents('php://input'), true);
    $questions = $data ?? null; // Expect direct array in body

    if (!is_array($questions)) {
        http_response_code(400); 
        echo json_encode(['error' => 'Invalid JSON data received. Expected an array of questions.']); 
        return;
    }

    try {
        $sql = "UPDATE kdd_applicant_questions SET sort_order = ? WHERE authority_id = ? AND id = ?";
        $stmt = $pdo->prepare($sql);
        $pdo->beginTransaction();
        $success = true;
        foreach ($questions as $index => $question) {
            $id = filter_var($question['id'] ?? null, FILTER_VALIDATE_INT);
            $sort_order = $index; // Use loop index for sorting

            if ($id) {
                if (!$stmt->execute([$sort_order, $authorityId, $id])) {
                    $success = false;
                    break;
                }
            } else {
                error_log("Invalid item in saveQuestionSorting: " . json_encode($question));
                $success = false;
                break;
            }
        }

        if ($success) {
            $pdo->commit(); 
            http_response_code(200); 
            echo json_encode(["success" => true, "message" => "Question sorting updated."]);
        } else {
            $pdo->rollBack(); 
            http_response_code(500);
            echo json_encode(["error" => "Failed to update question sorting."]);
        }
    } catch (\PDOException $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        error_log("DB error in saveQuestionSorting [ADMIN] ($authority): " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not update question sorting."]);
    }
}

function addQuestion(PDO $pdo, int $requestingUserId, string $authority, int $authorityId): void {
    // Use JSON data instead of POST
    $data = getJsonRequestData();
    if (!$data) return;
     
    $question = $data['question'] ?? null;
    $type = $data['type'] ?? null; // Application type

    if (empty($question) || empty($type)) {
        http_response_code(400); 
        echo json_encode(['error' => 'Question text and type are required.']); 
        return;
    }

    try {
        // Get next sort order for this type
        $stmtMaxSort = $pdo->prepare("SELECT MAX(sort_order) FROM kdd_applicant_questions WHERE authority_id = ? AND type = ? AND is_deleted = 0");
        $stmtMaxSort->execute([$authorityId, $type]);
        $maxSort = $stmtMaxSort->fetchColumn();
        $sort_order = ($maxSort === null || $maxSort === false) ? 0 : (int)$maxSort + 1;

        $sql = "INSERT INTO kdd_applicant_questions (authority_id, question, type, sort_order) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([$authorityId, $question, $type, $sort_order]);

        if ($success) {
            $newId = $pdo->lastInsertId();
            http_response_code(201); echo json_encode([
                'success' => true, 'message' => 'Question added.', 'id' => $newId,
                'question' => $question, 'type' => $type, 'sort_order' => $sort_order, 'is_deleted' => 0
            ]);
            // Log change
            // logDatabaseChange(...);
        } else {
            http_response_code(500); 
            echo json_encode(['error' => 'Failed to add question.']);
        }
    } catch (\PDOException $e) {
        error_log("DB error in addQuestion [ADMIN] ($authority): " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not add question."]);
    }
}

function saveQuestions(PDO $pdo, int $requestingUserId, string $authority, int $authorityId): void {
    $data = getJsonRequestData();
    if (!$data) return;
     
    // Check if the data is wrapped in a "questions" key
    $questionsArray = $data['questions'] ?? $data;
     
    if (!is_array($questionsArray)) {
        http_response_code(400); 
        echo json_encode(['error' => 'Invalid JSON data received. Expected array of questions.']); 
        return; 
    }

    try {
        $sql = "UPDATE kdd_applicant_questions SET question = ? WHERE authority_id = ? AND id = ? AND is_deleted = 0";
        $stmt = $pdo->prepare($sql);
        $pdo->beginTransaction();
        $success = true;
        $updatedCount = 0;
         
        foreach ($questionsArray as $item) {
            $id = filter_var($item['id'] ?? null, FILTER_VALIDATE_INT);
            $question = $item['question'] ?? null;
            
            if ($id && is_string($question)) {
                if (!$stmt->execute([$question, $authorityId, $id])) {
                    $success = false;
                    error_log("Failed to update question ID $id: " . json_encode($stmt->errorInfo()));
                    break;
                }
                $updatedCount += $stmt->rowCount();
            } else { 
                error_log("Invalid item in saveQuestions: " . json_encode($item)); 
                // Don't fail the whole batch for one bad item
                // Just skip this item and continue
            }
        }
         
        if ($success) { 
            $pdo->commit(); 
            http_response_code(200); 
            echo json_encode([
                "success" => true, 
                "message" => "Questions updated.",
                "count" => $updatedCount
            ]); 
        } else { 
            $pdo->rollBack(); 
            http_response_code(500); 
            echo json_encode(["error" => "Failed to update questions."]); 
        }
    } catch (\PDOException $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        error_log("DB error in saveQuestions [ADMIN] ($authority): " . $e->getMessage());
        http_response_code(500); 
        echo json_encode(["error" => "Could not update questions: " . $e->getMessage()]);
    }
}

function deleteQuestion(PDO $pdo, int $requestingUserId, string $authority, int $authorityId): void {
    // Use JSON data instead of POST
    $data = getJsonRequestData();
    if (!$data) return;
     
    $id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
    if (!$id) {
        http_response_code(400); 
        echo json_encode(['error' => 'Invalid or missing question ID.']); 
        return;
    }

    try {
        // Delete the question and related answers
        $pdo->beginTransaction();

        $sqlQ = "DELETE FROM kdd_applicant_questions WHERE id = ? AND authority_id = ?";
        $stmtQ = $pdo->prepare($sqlQ);
        $successQ = $stmtQ->execute([$id, $authorityId]);
        $rowCountQ = $stmtQ->rowCount();

        // Also delete answers associated with this question
        $sqlA = "DELETE FROM kdd_applicant_answers WHERE question_id = ? AND authority_id = ?";
        $stmtA = $pdo->prepare($sqlA);
        $successA = $stmtA->execute([$id, $authorityId]);

        if (!$successQ || !$successA) {
            throw new \Exception("Failed executing delete statements.");
        }

        if ($rowCountQ > 0) { // Check if the question itself was found and updated
            $pdo->commit();
            http_response_code(200); 
            echo json_encode(["success" => true, "message" => "Question and associated answers deleted."]);
            // Log change
            $changes = [['column_name' => 'is_deleted', 'old_value' => 0, 'new_value' => 1]];
            global $decoded_jwt;
                $authorityId = $decoded_jwt->authority_id ?? 0;
                logDatabaseChange($authorityId, $pdo, 'DELETE', "applicant_questions", $id, $requestingUserId, $changes);
        } else {
            $pdo->rollBack(); // Question wasn't found or already deleted
            http_response_code(404); 
            echo json_encode(["error" => "Question not found or already deleted."]);
        }
    } catch (\PDOException | \Exception $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        error_log("Error in deleteQuestion [ADMIN] (ID: $id, Authority: $authority): " . $e->getMessage());
        http_response_code(500); 
        echo json_encode(["error" => "Could not delete question: " . $e->getMessage()]);
    }
}

?>