<?php
require_once '../../bootstrap.php';
require_once '../../jwt.php';

// Handle both cookie-based and header-based authentication
$decoded_jwt = null;
$user_id = null;
$authority_id = null;

// Try header-based authentication first (for API calls)
$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;

if ($authHeader && preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
    // Header-based JWT authentication
    $jwt = $matches[1];
    try {
        $decoded_jwt = decode_jwt($jwt);
        $user_id = $decoded_jwt->userId ?? null;
        $authority_id = $decoded_jwt->authority_id ?? null;
        
        // If no authority_id in token, try to get it from authority name
        if (!$authority_id && isset($decoded_jwt->authority) && isset($pdo)) {
            $sqlAuthority = "SELECT id FROM kdd_authorities WHERE name = ?";
            $stmtAuthority = $pdo->prepare($sqlAuthority);
            $stmtAuthority->execute([$decoded_jwt->authority]);
            $authority_id = $stmtAuthority->fetchColumn();
        }
    } catch (Exception $e) {
        error_log("JWT validation failed: " . $e->getMessage());
    }
} else if (isset($_COOKIE['auth_token'])) {
    // Cookie-based authentication fallback
    try {
        require_once '../../auth_check.php';
        // auth_check.php sets $decoded_jwt
        $user_id = $decoded_jwt->userId ?? null;
        $authority_id = $decoded_jwt->authority_id ?? null;
    } catch (Exception $e) {
        error_log("Cookie auth failed: " . $e->getMessage());
    }
}

// Check if authentication was successful
if (!$user_id || !$authority_id) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Set session variables for compatibility
$_SESSION['user_id'] = $user_id;
$_SESSION['authority_id'] = $authority_id;

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

switch ($action) {
    case 'getHighscores':
        if ($method === 'GET') {
            getHighscores();
        }
        break;
    
    case 'saveScore':
        if ($method === 'POST') {
            saveScore();
        }
        break;
    
    case 'getPlayerStats':
        if ($method === 'GET') {
            getPlayerStats();
        }
        break;
    
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
}

function getHighscores() {
    global $pdo;
    
    try {
        $authorityId = $_SESSION['authority_id'] ?? null;
        
        if (!$authorityId) {
            echo json_encode(['scores' => []]);
            return;
        }
        
        // Get top 10 scores for this authority
        $stmt = $pdo->prepare("
            SELECT 
                id,
                player_name,
                score,
                height_reached,
                play_time,
                power_ups_collected,
                enemies_defeated,
                created_at
            FROM quacklejump_scores
            WHERE authority_id = :authority_id
            ORDER BY score DESC
            LIMIT 10
        ");
        
        $stmt->execute(['authority_id' => $authorityId]);
        $scores = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['scores' => $scores]);
        
    } catch (Exception $e) {
        error_log("Error in getHighscores: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to retrieve high scores']);
    }
}

function saveScore() {
    global $pdo;
    
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid data']);
            return;
        }
        
        $userId = $_SESSION['user_id'];
        $authorityId = $_SESSION['authority_id'] ?? null;
        
        if (!$authorityId) {
            http_response_code(400);
            echo json_encode(['error' => 'No authority context']);
            return;
        }
        
        // Validate required fields
        $playerName = $data['player_name'] ?? '';
        $score = $data['score'] ?? 0;
        $heightReached = $data['height_reached'] ?? 0;
        $playTime = $data['play_time'] ?? 0;
        $powerUpsCollected = $data['power_ups_collected'] ?? 0;
        $enemiesDefeated = $data['enemies_defeated'] ?? 0;
        
        if (empty($playerName) || $score <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid player name or score']);
            return;
        }
        
        // Insert new score
        $stmt = $pdo->prepare("
            INSERT INTO quacklejump_scores (
                player_name,
                score,
                height_reached,
                play_time,
                power_ups_collected,
                enemies_defeated,
                authority_id,
                user_id
            ) VALUES (
                :player_name,
                :score,
                :height_reached,
                :play_time,
                :power_ups_collected,
                :enemies_defeated,
                :authority_id,
                :user_id
            )
        ");
        
        $stmt->execute([
            'player_name' => $playerName,
            'score' => $score,
            'height_reached' => $heightReached,
            'play_time' => $playTime,
            'power_ups_collected' => $powerUpsCollected,
            'enemies_defeated' => $enemiesDefeated,
            'authority_id' => $authorityId,
            'user_id' => $userId
        ]);
        
        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
        
    } catch (Exception $e) {
        error_log("Error in saveScore: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to save score']);
    }
}

function getPlayerStats() {
    global $pdo;
    
    try {
        $userId = $_SESSION['user_id'];
        $authorityId = $_SESSION['authority_id'] ?? null;
        
        if (!$authorityId) {
            echo json_encode(['stats' => null]);
            return;
        }
        
        // Get player statistics
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as games_played,
                MAX(score) as high_score,
                MAX(height_reached) as max_height,
                SUM(play_time) as total_play_time,
                SUM(power_ups_collected) as total_power_ups,
                SUM(enemies_defeated) as total_enemies,
                AVG(score) as avg_score
            FROM quacklejump_scores
            WHERE authority_id = :authority_id
            AND user_id = :user_id
        ");
        
        $stmt->execute([
            'authority_id' => $authorityId,
            'user_id' => $userId
        ]);
        
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get rank
        $stmt = $pdo->prepare("
            SELECT COUNT(DISTINCT user_id) + 1 as rank
            FROM quacklejump_scores
            WHERE authority_id = :authority_id
            AND score > (
                SELECT MAX(score)
                FROM quacklejump_scores
                WHERE authority_id = :authority_id2
                AND user_id = :user_id
            )
        ");
        
        $stmt->execute([
            'authority_id' => $authorityId,
            'authority_id2' => $authorityId,
            'user_id' => $userId
        ]);
        
        $rankResult = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['rank'] = $rankResult['rank'] ?? null;
        
        echo json_encode(['stats' => $stats]);
        
    } catch (Exception $e) {
        error_log("Error in getPlayerStats: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to retrieve player statistics']);
    }
}
?>