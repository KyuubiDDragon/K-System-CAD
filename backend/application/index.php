<?php
/**
 * Backend Endpoint: application/index.php
 * Handles application submissions, questions, and applicant data.
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
$userId = $decoded_jwt->userId ?? null; // ID of user performing the action
$userPermissions = $decoded_jwt->permissions ?? [];
$authority = $decoded_jwt->authority ?? null;
$authorityId = $decoded_jwt->authority_id ?? null;

if ($userId === null || !is_int($userId) || $authority === null || !is_string($authority) || $authorityId === null) {
    http_response_code(400); echo json_encode(["error" => "Invalid token payload."]); exit();
}
require_once __DIR__ . '/../utils/authority_helper.php';
if (!isValidAuthority($pdo, $authorityId)) {
    http_response_code(403); echo json_encode(["error" => "Invalid authority context."]); exit();
}

// Feature-Zugriff prüfen
if (!hasFeatureAccess($pdo, $authorityId, 'application')) {
    http_response_code(403); echo json_encode(["error" => "This authority doesn't have access to application features."]); exit();
}

// --- Authorization & Action Routing ---
$action = $_REQUEST['action'] ?? '';
$request_method = $_SERVER['REQUEST_METHOD'];

// Map actions to required permissions (module.action format)
$permissions_map = [
    'getApplicants'         => ['module' => 'application', 'action' => 'read'],
    'getQuestions'          => ['module' => 'application', 'action' => 'read'],
    'getPendingApplicant'   => ['module' => 'application', 'action' => 'read'],
    'addApplication'        => ['module' => 'application', 'action' => 'write'],
    'saveQuestionSorting'   => ['module' => 'application', 'action' => 'write'],
    'addQuestion'           => ['module' => 'application', 'action' => 'write'],
    'saveQuestions'         => ['module' => 'application', 'action' => 'write'],
    'deleteQuestion'        => ['module' => 'application', 'action' => 'delete'],
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
    $has_permission = true;
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
        case 'getApplicants':         if ($request_method === 'GET') getApplicants($pdo, $authority, $authorityId); else MethodNotAllowed(); break;
        case 'getQuestions':          if ($request_method === 'GET') getQuestions($pdo, $authority, $authorityId); else MethodNotAllowed(); break;
        case 'getPendingApplicant':   if ($request_method === 'GET') getPendingApplicant($pdo, $authority, $authorityId); else MethodNotAllowed(); break;

        // POST Actions
        case 'addApplication':     if ($is_post_request) addApplication($pdo, $userId, $authority, $authorityId); else MethodNotAllowed(); break;
        case 'saveQuestionSorting':   if ($is_post_request) saveQuestionSorting($pdo, $userId, $authority, $authorityId); else MethodNotAllowed(); break;
        case 'addQuestion':           if ($is_post_request) addQuestion($pdo, $userId, $authority, $authorityId); else MethodNotAllowed(); break;
        case 'saveQuestions':         if ($is_post_request) saveQuestions($pdo, $userId, $authority, $authorityId); else MethodNotAllowed(); break;
        case 'deleteQuestion':        if ($is_post_request) deleteQuestion($pdo, $userId, $authority, $authorityId); else MethodNotAllowed(); break;

        default: http_response_code(500); echo json_encode(['error' => 'Action routing error.']); break;
    }
} else {
    http_response_code(403); echo json_encode(["error" => "Permission denied for action: " . htmlspecialchars($action)]);
}

exit();



// Helper function to get authority ID
function getAuthorityId(PDO $pdo, ?string $authority): ?int {
    if (!$authority) return null;
    
    try {
        $stmt = $pdo->prepare("SELECT id FROM kdd_authorities WHERE name = ?");
        $stmt->execute([$authority]);
        $authorityId = $stmt->fetchColumn();
        return $authorityId ? (int)$authorityId : null;
    } catch (\PDOException $e) {
        error_log("Error fetching authority ID: " . $e->getMessage());
        return null;
    }
}



// --- Function Implementations (PDO Refactored) ---

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
                JOIN kdd_applicant_questions aq ON aq.authority_id = ap.authority_id AND ap.type = aq.type AND aq.is_deleted = 0
                LEFT JOIN kdd_applicant_answers aa ON aa.authority_id = ap.authority_id AND ap.id = aa.applicant_id AND aa.question_id = aq.id
                WHERE ap.authority_id = ?
                -- Optional: Add more WHERE conditions if needed, e.g., filter by ap.is_deleted = 0
                ORDER BY ap.id, aq.sort_order";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$authorityId, "kdd_applicant", $authorityId]);
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
                    // Use applicant ID from main row, not potentially NULL answer row
                    "applicant_id" => $applicantId,
                    "question_id" => $row["aq_question_id"], // Question ID always exists due to JOIN
                    "question" => $row["question"],
                    "answer" => $row["answer"] // Can be NULL if no answer submitted
                ];
            }
        }

        http_response_code(200);
        // Return as a numerically indexed array of applicants
        echo json_encode(array_values($applicants));

    } catch (\PDOException $e) {
        error_log("DB error in getApplicants ($authority): " . $e->getMessage());
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
        error_log("DB error in getQuestions ($authority): " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not retrieve questions."]);
    }
}

function getPendingApplicant(PDO $pdo, string $authority, int $authorityId): void {
    try {
        $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
        if (!$id) {
            http_response_code(400); 
            echo json_encode(["error" => "Invalid applicant ID."]); 
            return;
        }

        $sql = "SELECT a.*, GROUP_CONCAT(aa.answer) as answers
                FROM kdd_applicant a
                LEFT JOIN kdd_applicant_answers aa ON aa.authority_id = a.authority_id AND a.id = aa.applicant_id
                WHERE a.authority_id = ? AND a.id = ?
                GROUP BY a.id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$authorityId, $id]);
        $applicant = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$applicant) {
            http_response_code(404);
            echo json_encode(["error" => "Applicant not found."]);
            return;
        }

        http_response_code(200);
        echo json_encode($applicant);
    } catch (\PDOException $e) {
        error_log("DB error in getPendingApplicant ($authority, ID: $id): " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not retrieve applicant information."]);
    }
}

function addApplication(PDO $pdo, int $requestingUserId, string $authority, int $authorityId): void {
    $data = getJsonRequestData();
    if (!$data) return;

    if (empty($data['name']) || empty($data['birthdate']) || 
        empty($data['email']) || empty($data['type']) || empty($data['jobinterviewDate']) || 
        empty($data['jobinterviewTime']) || empty($data['phonenumber'])) {
        http_response_code(400);
        echo json_encode(["error" => "Missing required fields."]);
        return;
    }

    $applicantId = $data['id'] ?? 0;
    $name = filter_var($data['name'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $birthdate = $data['birthdate']; // Format validation would be good
    $email = $data['email'];
    $type = filter_var($data['type'], FILTER_VALIDATE_INT);
    $jobinterviewDate = $data['jobinterviewDate']; // Format validation
    $jobinterviewTime = $data['jobinterviewTime']; // Format validation
    $status = $data['status'];
    $phonenumber = filter_var($data['phonenumber'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);

    if (!$email) {
        http_response_code(400);
        echo json_encode(["error" => "Invalid email format."]);
        return;
    }

    // Further validation on date and time could go here...

    try {
        $pdo->beginTransaction();

        // Update or insert applicant
        if ($applicantId > 0) {
            // Get old data for logging
            $oldApplicant = getEntryById($pdo, $applicantId, 'kdd_applicant', $authorityId);

            // Update existing applicant
            $sql = "UPDATE kdd_applicant SET
                    name = ?, birthdate = ?, email = ?, type = ?,
                    jobinterviewDate = ?, jobinterviewTime = ?, phonenumber = ?, status = ?
                    WHERE authority_id = ? AND id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $name, $birthdate, $email, $type,
                $jobinterviewDate, $jobinterviewTime, $phonenumber, $status,
                $authorityId, $applicantId
            ]);

            // Log applicant update
            if ($oldApplicant) {
                $changes = [];
                if ($oldApplicant['name'] != $name) $changes[] = ['column_name' => 'name', 'old_value' => $oldApplicant['name'], 'new_value' => $name];
                if ($oldApplicant['birthdate'] != $birthdate) $changes[] = ['column_name' => 'birthdate', 'old_value' => $oldApplicant['birthdate'], 'new_value' => $birthdate];
                if ($oldApplicant['email'] != $email) $changes[] = ['column_name' => 'email', 'old_value' => $oldApplicant['email'], 'new_value' => $email];
                if ($oldApplicant['type'] != $type) $changes[] = ['column_name' => 'type', 'old_value' => $oldApplicant['type'], 'new_value' => $type];
                if ($oldApplicant['status'] != $status) $changes[] = ['column_name' => 'status', 'old_value' => $oldApplicant['status'], 'new_value' => $status];
                if (!empty($changes)) {
                    logDatabaseChange($authorityId, $pdo, 'UPDATE', 'kdd_applicant', $applicantId, $requestingUserId, $changes);
                }
            }
        } else {
            // Insert new applicant
            $sql = "INSERT INTO kdd_applicant (authority_id, name, birthdate, email, type, jobinterviewDate, jobinterviewTime, phonenumber, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $authorityId, $name, $birthdate, $email, $type,
                $jobinterviewDate, $jobinterviewTime, $phonenumber, $status
            ]);
            $applicantId = $pdo->lastInsertId();

            // Log applicant creation
            $changes = [
                ['column_name' => 'name', 'old_value' => null, 'new_value' => $name],
                ['column_name' => 'email', 'old_value' => null, 'new_value' => $email],
                ['column_name' => 'type', 'old_value' => null, 'new_value' => $type],
                ['column_name' => 'status', 'old_value' => null, 'new_value' => $status]
            ];
            logDatabaseChange($authorityId, $pdo, 'INSERT', 'kdd_applicant', $applicantId, $requestingUserId, $changes);
        }

        // Handle calendar entry
        if (!empty($data['addToCalendar']) && filter_var($data['addToCalendar'], FILTER_VALIDATE_BOOLEAN)) {
            // Assuming createCalendarEntry function now accepts authorityId parameter
            createCalendarEvent($pdo, "Job Interview with $name", 
                               "Applicant: $name", 
                               $jobinterviewDate, $jobinterviewTime, 60, // 60 min duration assumed
                               $authority, $authorityId, 
                               "kdd_applicant", (int)$applicantId);
        }

        // Process answers if provided
        if (isset($data['answers']) && is_array($data['answers'])) {
            // Get old answers for logging before deleting
            $getOldAnswersSql = "SELECT * FROM kdd_applicant_answers WHERE authority_id = ? AND applicant_id = ?";
            $getOldAnswersStmt = $pdo->prepare($getOldAnswersSql);
            $getOldAnswersStmt->execute([$authorityId, $applicantId]);
            $oldAnswers = $getOldAnswersStmt->fetchAll(PDO::FETCH_ASSOC);

            // First, remove all existing answers for this applicant
            $sqlDeleteAnswers = "DELETE FROM kdd_applicant_answers WHERE authority_id = ? AND applicant_id = ?";
            $stmtDeleteAnswers = $pdo->prepare($sqlDeleteAnswers);
            $stmtDeleteAnswers->execute([$authorityId, $applicantId]);

            // Log hard delete of old answers
            foreach ($oldAnswers as $oldAnswer) {
                $changes = [
                    ['column_name' => 'PERMANENT_DELETE', 'old_value' => json_encode($oldAnswer), 'new_value' => null]
                ];
                logDatabaseChange($authorityId, $pdo, 'DELETE', 'kdd_applicant_answers', $oldAnswer['id'], $requestingUserId, $changes);
            }

            // Then insert new answers
            $sqlInsertAnswer = "INSERT INTO kdd_applicant_answers (authority_id, applicant_id, question_id, answer) VALUES (?, ?, ?, ?)";
            $stmtInsertAnswer = $pdo->prepare($sqlInsertAnswer);

            foreach ($data['answers'] as $answer) {
                if (isset($answer['question_id']) && $answer['question_id'] > 0) {
                    $stmtInsertAnswer->execute([
                        $authorityId,
                        $applicantId,
                        $answer['question_id'],
                        $answer['answer'] ?? ''
                    ]);

                    // Log answer insert
                    $answerId = $pdo->lastInsertId();
                    $changes = [
                        ['column_name' => 'applicant_id', 'old_value' => null, 'new_value' => $applicantId],
                        ['column_name' => 'question_id', 'old_value' => null, 'new_value' => $answer['question_id']],
                        ['column_name' => 'answer', 'old_value' => null, 'new_value' => $answer['answer'] ?? '']
                    ];
                    logDatabaseChange($authorityId, $pdo, 'INSERT', 'kdd_applicant_answers', $answerId, $requestingUserId, $changes);
                }
            }
        }

        $pdo->commit();
        http_response_code(200);
        echo json_encode(["success" => true, "applicantId" => $applicantId]);
    } catch (\PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("DB error in addApplication ($authority): " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Failed to save application data."]);
    }
}

function saveQuestionSorting(PDO $pdo, int $requestingUserId, string $authority, int $authorityId): void {
    $data = getJsonRequestData();
    if (!$data || !isset($data['questions']) || !is_array($data['questions'])) {
        http_response_code(400);
        echo json_encode(["error" => "Invalid question sorting data."]);
        return;
    }

    try {
        $pdo->beginTransaction();

        $sqlUpdate = "UPDATE kdd_applicant_questions SET sort_order = ? WHERE authority_id = ? AND id = ?";
        $stmt = $pdo->prepare($sqlUpdate);

        foreach ($data['questions'] as $index => $question) {
            if (isset($question['id']) && is_numeric($question['id']) && $question['id'] > 0) {
                $questionId = $question['id'];
                $newSortOrder = $index + 1;

                // Get old sort order for logging
                $oldQuestion = getEntryById($pdo, $questionId, 'kdd_applicant_questions', $authorityId);

                $stmt->execute([$newSortOrder, $authorityId, $questionId]);

                // Log sort order change
                if ($oldQuestion && $oldQuestion['sort_order'] != $newSortOrder) {
                    $changes = [
                        ['column_name' => 'sort_order', 'old_value' => $oldQuestion['sort_order'], 'new_value' => $newSortOrder]
                    ];
                    logDatabaseChange($authorityId, $pdo, 'UPDATE', 'kdd_applicant_questions', $questionId, $requestingUserId, $changes);
                }
            }
        }

        $pdo->commit();
        http_response_code(200);
        echo json_encode(["success" => true, "message" => "Question sorting updated successfully."]);
    } catch (\PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("DB error in saveQuestionSorting ($authority): " . $e->getMessage());
        http_response_code(500); 
        echo json_encode(["error" => "Failed to update question sorting."]);
    }
}

function saveQuestions(PDO $pdo, int $requestingUserId, string $authority, int $authorityId): void {
    $data = getJsonRequestData();
    if (!$data || !isset($data['questions']) || !is_array($data['questions'])) {
        http_response_code(400);
        echo json_encode(["error" => "Invalid questions data."]);
        return;
    }

    try {
        $pdo->beginTransaction();

        $sqlUpdate = "UPDATE kdd_applicant_questions
                     SET question = ?, type = ?, is_deleted = ?
                     WHERE authority_id = ? AND id = ?";
        $stmt = $pdo->prepare($sqlUpdate);

        foreach ($data['questions'] as $question) {
            if (isset($question['id']) && is_numeric($question['id']) && $question['id'] > 0) {
                $questionId = $question['id'];
                $newQuestionText = $question['question'] ?? '';
                $newType = $question['type'] ?? 1;
                $newIsDeleted = isset($question['is_deleted']) ? $question['is_deleted'] : 0;

                // Get old data for logging
                $oldQuestion = getEntryById($pdo, $questionId, 'kdd_applicant_questions', $authorityId);

                $stmt->execute([
                    $newQuestionText,
                    $newType,
                    $newIsDeleted,
                    $authorityId,
                    $questionId
                ]);

                // Log changes
                if ($oldQuestion) {
                    $changes = [];
                    if ($oldQuestion['question'] != $newQuestionText) {
                        $changes[] = ['column_name' => 'question', 'old_value' => $oldQuestion['question'], 'new_value' => $newQuestionText];
                    }
                    if ($oldQuestion['type'] != $newType) {
                        $changes[] = ['column_name' => 'type', 'old_value' => $oldQuestion['type'], 'new_value' => $newType];
                    }
                    if ($oldQuestion['is_deleted'] != $newIsDeleted) {
                        $changes[] = ['column_name' => 'is_deleted', 'old_value' => $oldQuestion['is_deleted'], 'new_value' => $newIsDeleted];
                    }
                    if (!empty($changes)) {
                        logDatabaseChange($authorityId, $pdo, 'UPDATE', 'kdd_applicant_questions', $questionId, $requestingUserId, $changes);
                    }
                }
            }
        }

        $pdo->commit();
        http_response_code(200);
        echo json_encode(["success" => true, "message" => "Questions updated successfully."]);
    } catch (\PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("DB error in saveQuestions ($authority): " . $e->getMessage());
        http_response_code(500); 
        echo json_encode(["error" => "Failed to update questions."]);
    }
}

function addQuestion(PDO $pdo, int $requestingUserId, string $authority, int $authorityId): void {
    $data = getJsonRequestData();
    if (!$data || empty($data['question']) || !isset($data['type'])) {
        http_response_code(400);
        echo json_encode(["error" => "Missing required fields for new question."]);
        return;
    }

    $question = filter_var($data['question'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $type = filter_var($data['type'], FILTER_VALIDATE_INT);

    if (!$type) {
        http_response_code(400);
        echo json_encode(["error" => "Invalid question type."]);
        return;
    }

    try {
        // Get the highest sort_order currently in use
        $sqlMax = "SELECT MAX(sort_order) as max_order FROM kdd_applicant_questions WHERE authority_id = ?";
        $stmtMax = $pdo->prepare($sqlMax);
        $stmtMax->execute([$authorityId]);
        $result = $stmtMax->fetch(PDO::FETCH_ASSOC);
        $nextOrder = ($result['max_order'] ?? 0) + 1;

        // Insert the new question
        $sqlInsert = "INSERT INTO kdd_applicant_questions (authority_id, question, type, sort_order, is_deleted)
                      VALUES (?, ?, ?, ?, 0)";
        $stmt = $pdo->prepare($sqlInsert);
        $stmt->execute([$authorityId, $question, $type, $nextOrder]);
        $newId = $pdo->lastInsertId();

        // Log question creation
        $changes = [
            ['column_name' => 'question', 'old_value' => null, 'new_value' => $question],
            ['column_name' => 'type', 'old_value' => null, 'new_value' => $type],
            ['column_name' => 'sort_order', 'old_value' => null, 'new_value' => $nextOrder]
        ];
        logDatabaseChange($authorityId, $pdo, 'INSERT', 'kdd_applicant_questions', $newId, $requestingUserId, $changes);

        http_response_code(201); // Created
        echo json_encode([
            "success" => true,
            "id" => $newId,
            "message" => "Question added successfully."
        ]);
    } catch (\PDOException $e) {
        error_log("DB error in addQuestion ($authority): " . $e->getMessage());
        http_response_code(500); 
        echo json_encode(["error" => "Failed to add new question."]);
    }
}

function deleteQuestion(PDO $pdo, int $requestingUserId, string $authority, int $authorityId): void {
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
    if (!$id) {
        http_response_code(400);
        echo json_encode(["error" => "Invalid question ID."]);
        return;
    }

    try {
        // Soft delete the question (set is_deleted flag to 1)
        $sql = "UPDATE kdd_applicant_questions SET is_deleted = 1 WHERE authority_id = ? AND id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$authorityId, $id]);

        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(["error" => "Question not found or already deleted."]);
            return;
        }

        // Log soft delete
        $changes = [
            ['column_name' => 'is_deleted', 'old_value' => 0, 'new_value' => 1]
        ];
        logDatabaseChange($authorityId, $pdo, 'UPDATE', 'kdd_applicant_questions', $id, $requestingUserId, $changes);

        http_response_code(200);
        echo json_encode(["success" => true, "message" => "Question deleted successfully."]);
    } catch (\PDOException $e) {
        error_log("DB error in deleteQuestion ($authority, ID: $id): " . $e->getMessage());
        http_response_code(500); 
        echo json_encode(["error" => "Failed to delete question."]);
    }
}


function createCalendarEvent(PDO $pdo, string $title, string $content, string $startDate, string $startTime, 
                           int $durationMinutes, string $authority, int $authorityId, 
                           string $linkedTable = null, int $linkedId = null): bool {
    try {
        // Format datetime
        $startDateTime = $startDate . ' ' . $startTime;
        $startTimestamp = strtotime($startDateTime);
        
        if (!$startTimestamp) {
            error_log("Invalid date/time format in createCalendarEvent: $startDate $startTime");
            return false;
        }
        
        $start = date('Y-m-d H:i:s', $startTimestamp);
        $end = date('Y-m-d H:i:s', $startTimestamp + ($durationMinutes * 60));
        
        // Determine color based on event type
        $eventType = determineEventType($title);
        $color = '#3788d8'; // Default blue
        
        switch ($eventType) {
            case 'meeting': $color = '#28a745'; break; // Green
            case 'training': $color = '#17a2b8'; break; // Teal
            case 'operation': $color = '#dc3545'; break; // Red
            case 'administrative': $color = '#6c757d'; break; // Gray
            case 'social': $color = '#ffc107'; break; // Yellow
        }
        
        $sql = "INSERT INTO kdd_calendar 
                (authority_id, title, content, start_date, end_date, color, linked_to_table, linked_to_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([
            $authorityId,
            $title,
            $content,
            $start,
            $end,
            $color,
            $linkedTable,
            $linkedId
        ]);
        
        if (!$success) {
            error_log("Failed to insert calendar event in createCalendarEvent");
            return false;
        }
        
        return true;
    } catch (\Throwable $e) {
        error_log("Error in createCalendarEvent: " . $e->getMessage());
        return false;
    }
}

function determineEventType(string $title): string {
    $title = strtolower($title);
    
    // Map of keywords to event types
    $typeKeywords = [
        'meeting' => ['meeting', 'besprechung', 'konferenz', 'briefing'],
        'training' => ['training', 'übung', 'schulung', 'education'],
        'operation' => ['einsatz', 'operation', 'mission', 'notfall'],
        'administrative' => ['verwaltung', 'admin', 'büro', 'paperwork'],
        'social' => ['feier', 'party', 'event', 'social']
    ];
    
    foreach ($typeKeywords as $type => $keywords) {
        foreach ($keywords as $keyword) {
            if (strpos($title, $keyword) !== false) {
                return $type;
            }
        }
    }
    
    // Default type if no keywords match
    return 'other';
}
?>