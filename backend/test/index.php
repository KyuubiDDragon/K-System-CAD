<?php
/**
 * Backend Endpoint: test/index.php
 * Handles CRUD operations for Test Questions and Answers, and test generation.
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

if ($userId === null || !is_int($userId) || $authority === null || !is_string($authority)) {
    http_response_code(400); echo json_encode(["error" => "Invalid token payload."]); exit();
}
require_once __DIR__ . '/../utils/authority_helper.php';
if (!isValidAuthority($pdo, $authorityId)) {
    http_response_code(403); echo json_encode(["error" => "Invalid authority context."]); exit();
}

// Feature-Zugriff prüfen
if (!hasFeatureAccess($pdo, $authorityId, 'training')) {
    http_response_code(403); echo json_encode(["error" => "This authority doesn't have access to training features."]); exit();
}


// --- Authorization & Action Routing ---
$action = $_REQUEST['action'] ?? '';
$request_method = $_SERVER['REQUEST_METHOD'];

// Map actions to required permissions (module.action format)
$permissions_map = [
    'getQuestionsAndAnswers' => ['module' => 'training.test', 'action' => 'read'],
    'generateRandomTest'     => ['module' => 'training.test', 'action' => 'read'],
    'addQuestion'            => ['module' => 'training.test', 'action' => 'write'],
    'editQuestion'           => ['module' => 'training.test', 'action' => 'write'],
    'deleteQuestion'         => ['module' => 'training.test', 'action' => 'delete'],
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
        case 'getQuestionsAndAnswers': if ($request_method === 'GET') getQuestionsAndAnswers($pdo, $authority); else MethodNotAllowed(); break;
        case 'generateRandomTest':     if ($request_method === 'GET') generateRandomTest($pdo, $authority); else MethodNotAllowed(); break;
        case 'addQuestion':            if ($is_post_request) addQuestion($pdo, $userId, $authority); else MethodNotAllowed(); break;
        case 'editQuestion':           if ($is_post_request) editQuestion($pdo, $userId, $authority); else MethodNotAllowed(); break; // Consider PUT
        case 'deleteQuestion':         if ($is_post_request) deleteQuestion($pdo, $userId, $authority); else MethodNotAllowed(); break; // Consider DELETE

        default: http_response_code(500); echo json_encode(['error' => 'Action routing error.']); break;
    }
} else {
    http_response_code(403); echo json_encode(["error" => "Permission denied for action: " . htmlspecialchars($action)]);
}

exit();



// --- Function Implementations (PDO Refactored) ---

/**
 * Fetches all non-deleted questions and their non-deleted answers.
 */
function getQuestionsAndAnswers(PDO $pdo, string $authority): void {
    try {
        // Get authority ID for proper querying
        global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
        if (!$authorityId) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid authority']);
            return;
        }
        
        // 1. Fetch all non-deleted questions
        $sqlQuestions = "SELECT id, question, isKO FROM `kdd_test_questions` WHERE authority_id = ? AND deleted = 0 ORDER BY id ASC";
        $stmtQuestions = $pdo->prepare($sqlQuestions);
        $stmtQuestions->execute([$authorityId]);
        $questionsResult = $stmtQuestions->fetchAll(PDO::FETCH_ASSOC);

        if (empty($questionsResult)) {
             http_response_code(200); echo json_encode([]); return;
        }

        $questionsById = [];
        $questionIds = [];
        foreach ($questionsResult as $q) {
             $q['isKO'] = (bool)$q['isKO']; // Cast to boolean
             $q['answers'] = []; // Initialize answers array
             $questionsById[$q['id']] = $q;
             $questionIds[] = $q['id'];
        }

        // 2. Fetch all non-deleted answers for these questions
        $placeholders = rtrim(str_repeat('?,', count($questionIds)), ',');
        $params = array_merge([$authorityId], $questionIds);
        $sqlAnswers = "SELECT id, question_id, answer, result FROM `kdd_test_answers` WHERE authority_id = ? AND deleted = 0 AND question_id IN ({$placeholders}) ORDER BY id ASC"; // Keep a consistent order
        $stmtAnswers = $pdo->prepare($sqlAnswers);
        $stmtAnswers->execute($params);
        $answersResult = $stmtAnswers->fetchAll(PDO::FETCH_ASSOC);

        // 3. Assign answers to questions
        foreach ($answersResult as $a) {
            $qid = $a['question_id'];
            if (isset($questionsById[$qid])) {
                $questionsById[$qid]['answers'][] = [
                    'id' => (int)$a['id'],
                    'answer' => $a['answer'],
                    'result' => (bool)$a['result'] // Cast to boolean
                ];
            }
        }

        http_response_code(200);
        echo json_encode(array_values($questionsById)); // Return indexed array

    } catch (\PDOException $e) {
        error_log("DB error in getQuestionsAndAnswers ($authority): " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not retrieve questions and answers."]);
    }
}

/**
 * Helper function to get authority ID from the authority name
 * 
 * @param PDO $pdo Database connection
 * @param string|null $authority Authority name
 * @return int|null Authority ID or null if not found
 */
function getAuthorityId(PDO $pdo, ?string $authority): ?int {
    if (!$authority) return null;
    
    try {
        $stmt = $pdo->prepare("SELECT id FROM kdd_authorities WHERE name = ?");
        $stmt->execute([$authority]);
        $authorityId = $stmt->fetchColumn();
        
        return $authorityId ? (int)$authorityId : null;
    } catch (\PDOException $e) {
        error_log("Error fetching authority_id: " . $e->getMessage());
        return null;
    }
}

/**
 * Adds a new question and its answers.
 */
function addQuestion(PDO $pdo, int $requestingUserId, string $authority): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { MethodNotAllowed(); return; }

    $data = getJsonRequestData();

    $questionText = $data['question'] ?? null;
    $isKO = filter_var($data['isKO'] ?? false, FILTER_VALIDATE_BOOLEAN);
    $answers = []; // Expecting answers like answer1, answer1_result, answer2, ...
    for ($i = 1; $i <= 3; $i++) { // Assuming max 3 answers like original
        if (isset($data["answer{$i}"]) && $data["answer{$i}"] !== '') {
            $answers[] = [
                'answer' => $data["answer{$i}"],
                'result' => filter_var($data["answer{$i}_result"] ?? false, FILTER_VALIDATE_BOOLEAN)
            ];
        }
    }

    if (empty($questionText) || empty($answers)) {
        http_response_code(400); echo json_encode(['error' => 'Question text and at least one answer are required.']); return;
    }
    // Validate if at least one answer is marked as correct? Optional.

    // Get authority ID for proper querying
    global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
    if (!$authorityId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid authority']);
        return;
    }

    try {
        $pdo->beginTransaction();

        // Insert Question
        $sqlQuestion = "INSERT INTO `kdd_test_questions` (authority_id, question, isKO) VALUES (?, ?, ?)";
        $stmtQuestion = $pdo->prepare($sqlQuestion);
        $successQ = $stmtQuestion->execute([$authorityId, $questionText, $isKO ? 1 : 0]);
        if (!$successQ) throw new \Exception("Failed to insert question.");
        $questionId = $pdo->lastInsertId();

        // Insert Answers
        $sqlAnswer = "INSERT INTO `kdd_test_answers` (authority_id, question_id, answer, result) VALUES (?, ?, ?, ?)";
        $stmtAnswer = $pdo->prepare($sqlAnswer);
        foreach ($answers as $answer) {
            $result = $answer['result'] ? 1 : 0;
            if (!$stmtAnswer->execute([$authorityId, $questionId, $answer['answer'], $result])) {
                throw new \Exception("Failed to insert answer: " . $answer['answer']);
            }

            // Log answer insert
            $answerId = $pdo->lastInsertId();
            $answerChanges = [
                ['column_name' => 'question_id', 'old_value' => null, 'new_value' => $questionId],
                ['column_name' => 'answer', 'old_value' => null, 'new_value' => $answer['answer']],
                ['column_name' => 'result', 'old_value' => null, 'new_value' => $result]
            ];
            logDatabaseChange($authorityId, $pdo, 'INSERT', 'kdd_test_answers', $answerId, $requestingUserId, $answerChanges);
        }

        $pdo->commit();
        http_response_code(201); echo json_encode(["success" => true, "message" => "Question added successfully.", "id" => $questionId]);
        // Log change
        $logData = json_encode(['question' => $questionText, 'isKO' => $isKO, 'answers' => $answers]);
        $changes = [['column_name' => 'question_data', 'old_value' => null, 'new_value' => $logData]];
        global $decoded_jwt;
                $authorityId = $decoded_jwt->authority_id ?? 0;
                logDatabaseChange($authorityId, $pdo, 'INSERT', "test_questions", $questionId, $requestingUserId, $changes);

    } catch (\PDOException | \Exception $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        error_log("Error in addQuestion ($authority): " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not add question: " . $e->getMessage()]);
    }
}

/**
 * Soft deletes a question and its associated answers.
 */
function deleteQuestion(PDO $pdo, int $requestingUserId, string $authority): void {
    $data = getJsonRequestData();
    $questionId = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
    
    if (!$questionId) { http_response_code(400); echo json_encode(['error' => 'Invalid or missing question ID.']); return; }

    // Get authority ID for proper querying
    global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
    if (!$authorityId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid authority']);
        return;
    }

    try {
        $pdo->beginTransaction();

        // Get answers that will be deleted for logging
        $getAnswersSql = "SELECT id FROM kdd_test_answers WHERE authority_id = ? AND question_id = ? AND deleted = 0";
        $getAnswersStmt = $pdo->prepare($getAnswersSql);
        $getAnswersStmt->execute([$authorityId, $questionId]);
        $answerIds = $getAnswersStmt->fetchAll(PDO::FETCH_COLUMN);

        // Mark question as deleted
        $sqlQ = "UPDATE `kdd_test_questions` SET deleted = 1 WHERE authority_id = ? AND id = ? AND deleted = 0";
        $stmtQ = $pdo->prepare($sqlQ);
        $successQ = $stmtQ->execute([$authorityId, $questionId]);
        $rowCountQ = $stmtQ->rowCount();

        // Mark related answers as deleted
        $sqlA = "UPDATE `kdd_test_answers` SET deleted = 1 WHERE authority_id = ? AND question_id = ? AND deleted = 0";
        $stmtA = $pdo->prepare($sqlA);
        $successA = $stmtA->execute([$authorityId, $questionId]);

        // Log answer soft deletes
        foreach ($answerIds as $answerId) {
            $answerChanges = [
                ['column_name' => 'deleted', 'old_value' => 0, 'new_value' => 1]
            ];
            logDatabaseChange($authorityId, $pdo, 'UPDATE', 'kdd_test_answers', $answerId, $requestingUserId, $answerChanges);
        }

        if ($successQ && $rowCountQ > 0) { // Ensure the question existed and was updated
            $pdo->commit();
            http_response_code(200); echo json_encode(["success" => true, "message" => "Question and answers marked as deleted."]);
            // Log change
            $changes = [['column_name' => 'deleted', 'old_value' => 0, 'new_value' => 1]];
            global $decoded_jwt;
                $authorityId = $decoded_jwt->authority_id ?? 0;
                logDatabaseChange($authorityId, $pdo, 'DELETE', "test_questions", $questionId, $requestingUserId, $changes);
        } elseif ($successQ) {
            $pdo->rollBack(); // Nothing changed for the question itself
            http_response_code(404); echo json_encode(["error" => "Question not found or already deleted."]);
        } else {
             throw new \Exception("Failed to execute delete statement for question.");
        }

    } catch (\PDOException | \Exception $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        error_log("Error in deleteQuestion (ID: $questionId, Authority: $authority): " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not delete question: " . $e->getMessage()]);
    }
}

/**
 * Edits an existing question and replaces its answers.
 */
function editQuestion(PDO $pdo, int $requestingUserId, string $authority): void {
     if ($_SERVER['REQUEST_METHOD'] !== 'POST') { MethodNotAllowed(); return; }

     // Get data from JSON request
     $data = getJsonRequestData();
     
     $questionId = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
     $questionText = $data['question'] ?? null;
     $isKO = filter_var($data['isKO'] ?? false, FILTER_VALIDATE_BOOLEAN);
     $answers = [];
     for ($i = 1; $i <= 3; $i++) { // Assuming max 3 answers like original
         if (isset($data["answer{$i}"]) && $data["answer{$i}"] !== '') {
             $answers[] = [
                 'answer' => $data["answer{$i}"],
                 'result' => filter_var($data["answer{$i}_result"] ?? false, FILTER_VALIDATE_BOOLEAN)
             ];
         }
     }

    if (!$questionId || empty($questionText) || empty($answers)) {
        http_response_code(400); echo json_encode(['error' => 'Question ID, question text and at least one answer are required.']); return;
    }

    // Get authority ID for proper querying
    global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
    if (!$authorityId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid authority']);
        return;
    }

    try {
        // $oldEntry = getEntryById(...) // Fetch old question and answers for detailed logging if needed

        $pdo->beginTransaction();

        // 1. Update Question
        $sqlQ = "UPDATE `kdd_test_questions` SET question = ?, isKO = ? WHERE authority_id = ? AND id = ? AND deleted = 0";
        $stmtQ = $pdo->prepare($sqlQ);
        $successQ = $stmtQ->execute([$questionText, $isKO ? 1 : 0, $authorityId, $questionId]);
        if (!$successQ || $stmtQ->rowCount() === 0) {
             throw new \Exception("Question with ID {$questionId} not found or update failed.");
        }

        // 2. Get old answers before deleting for logging
        $getOldAnswersSql = "SELECT * FROM kdd_test_answers WHERE authority_id = ? AND question_id = ?";
        $getOldAnswersStmt = $pdo->prepare($getOldAnswersSql);
        $getOldAnswersStmt->execute([$authorityId, $questionId]);
        $oldAnswers = $getOldAnswersStmt->fetchAll(PDO::FETCH_ASSOC);

        // Delete existing answers for this question (only for this authority)
        $sqlDelA = "DELETE FROM kdd_test_answers WHERE authority_id = ? AND question_id = ?";
        $stmtDelA = $pdo->prepare($sqlDelA);
        $stmtDelA->execute([$authorityId, $questionId]); // We delete directly, no soft delete needed if always replacing

        // Log hard delete of old answers
        foreach ($oldAnswers as $oldAnswer) {
            $deleteChanges = [
                ['column_name' => 'PERMANENT_DELETE', 'old_value' => json_encode($oldAnswer), 'new_value' => null]
            ];
            logDatabaseChange($authorityId, $pdo, 'DELETE', 'kdd_test_answers', $oldAnswer['id'], $requestingUserId, $deleteChanges);
        }

        // 3. Insert new answers
        $sqlInsA = "INSERT INTO `kdd_test_answers` (authority_id, question_id, answer, result) VALUES (?, ?, ?, ?)";
        $stmtInsA = $pdo->prepare($sqlInsA);
        foreach ($answers as $answer) {
            $result = $answer['result'] ? 1 : 0;
            if (!$stmtInsA->execute([$authorityId, $questionId, $answer['answer'], $result])) {
                 throw new \Exception("Failed to insert new answer: " . $answer['answer']);
            }

            // Log answer insert
            $newAnswerId = $pdo->lastInsertId();
            $insertChanges = [
                ['column_name' => 'question_id', 'old_value' => null, 'new_value' => $questionId],
                ['column_name' => 'answer', 'old_value' => null, 'new_value' => $answer['answer']],
                ['column_name' => 'result', 'old_value' => null, 'new_value' => $result]
            ];
            logDatabaseChange($authorityId, $pdo, 'INSERT', 'kdd_test_answers', $newAnswerId, $requestingUserId, $insertChanges);
        }

        $pdo->commit();
        http_response_code(200); echo json_encode(["success" => true, "message" => "Question updated successfully."]);
        // Log change (potentially complex log data comparing old/new question and answers)
        $logData = json_encode(['question' => $questionText, 'isKO' => $isKO, 'answers' => $answers]);
        $changes = [['column_name' => 'question_data', 'old_value' => null, 'new_value' => $logData]];
        global $decoded_jwt;
                $authorityId = $decoded_jwt->authority_id ?? 0;
                logDatabaseChange($authorityId, $pdo, 'UPDATE', "test_questions", $questionId, $requestingUserId, $changes);

    } catch (\PDOException | \Exception $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        error_log("Error in editQuestion (ID: $questionId, Authority: $authority): " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not update question: " . $e->getMessage()]);
    }
}


/**
 * Generates a random test with 50 questions and their answers (randomized).
 * WARNING: ORDER BY RAND() can be slow on large tables.
 */
function generateRandomTest(PDO $pdo, string $authority): void {
    $limit = 50; // Number of questions for the test

    // Get authority ID for proper querying
    global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
    if (!$authorityId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid authority']);
        return;
    }

    try {
        // 1. Select $limit random question IDs
        // Consider alternative random selection for performance on very large tables
        // e.g., fetch all IDs, shuffle in PHP, take $limit.
        $sqlQuestions = "SELECT id, question, isKO FROM `kdd_test_questions` WHERE authority_id = ? AND deleted = 0 ORDER BY RAND() LIMIT :limit";
        $stmtQuestions = $pdo->prepare($sqlQuestions);
        $stmtQuestions->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmtQuestions->execute([$authorityId]);
        $questionsResult = $stmtQuestions->fetchAll(PDO::FETCH_ASSOC);

        if (empty($questionsResult)) {
            http_response_code(200); echo json_encode([]); return; // No questions found
        }

        $questionsById = [];
        $questionIds = [];
        foreach ($questionsResult as $q) {
             $q['isKO'] = (bool)$q['isKO'];
             $q['answers'] = [];
             $questionsById[$q['id']] = $q;
             $questionIds[] = $q['id'];
        }

        // 2. Fetch all answers for the selected questions
        $placeholders = rtrim(str_repeat('?,', count($questionIds)), ',');
        $params = array_merge([$authorityId], $questionIds);
        // ORDER BY RAND() for answers too, within each question group? Original did this.
        $sqlAnswers = "SELECT id, question_id, answer, result FROM `kdd_test_answers` WHERE authority_id = ? AND deleted = 0 AND question_id IN ({$placeholders})
                       ORDER BY RAND()"; // Randomize answer order too
        $stmtAnswers = $pdo->prepare($sqlAnswers);
        $stmtAnswers->execute($params);
        $answersResult = $stmtAnswers->fetchAll(PDO::FETCH_ASSOC);

        // 3. Assign answers to questions
        foreach ($answersResult as $a) {
            $qid = $a['question_id'];
            if (isset($questionsById[$qid])) {
                $questionsById[$qid]['answers'][] = [
                    'id' => (int)$a['id'],
                    'answer' => $a['answer'],
                    'result' => (bool)$a['result']
                ];
            }
        }

        http_response_code(200);
        // Return indexed array, questions still in random order from DB query
        echo json_encode(array_values($questionsById));

    } catch (\PDOException $e) {
        error_log("DB error in generateRandomTest ($authority): " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not generate random test."]);
    }
}
?>