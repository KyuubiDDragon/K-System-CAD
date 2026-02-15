<?php
/**
 * Cheatsheet API
 *
 * Manages cheatsheet categories and items
 * Authority-specific with multi-content support
 */

// Use bootstrap which loads .env and creates $pdo
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../db.php'; // Recreates $pdo with loaded .env variables
require_once __DIR__ . '/../auth_check.php';
require_once __DIR__ . '/../logging/logging.php';
require_once __DIR__ . '/../utils/permission_helper.php';

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

// Get authority_id and user_id from decoded JWT
$authority_id = $decoded_jwt->authority_id ?? null;
$user_id = $decoded_jwt->userId ?? null;

if (!$authority_id || !$user_id) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized - Missing authority or user ID']);
    exit;
}

// =====================================================
// USER ENDPOINTS
// =====================================================

/**
 * GET /cheatsheet/?action=getAll
 * Get all enabled categories with their items
 */
if ($action === 'getAll' && $method === 'GET') {
    try {
        // Get categories
        $stmt = $pdo->prepare("
            SELECT id, name, description, icon, color, content_type, width, height,
                   order_index, collapsible, default_collapsed, enabled,
                   show_headers, auto_height, grid_x, grid_y, grid_w, grid_h
            FROM kdd_cheatsheet_categories
            WHERE authority_id = ? AND enabled = TRUE
            ORDER BY order_index ASC, name ASC
        ");
        $stmt->execute([$authority_id]);
        $categories = $stmt->fetchAll();

        // Get items for all categories
        $items = [];
        foreach ($categories as $category) {
            $stmt = $pdo->prepare("
                SELECT id, title, description, content_text, image_url, image_caption,
                       layout_json, order_index
                FROM kdd_cheatsheet_items
                WHERE category_id = ? AND authority_id = ? AND enabled = TRUE
                ORDER BY order_index ASC
            ");
            $stmt->execute([$category['id'], $authority_id]);
            $categoryItems = $stmt->fetchAll();

            // Decode JSON if present
            foreach ($categoryItems as &$item) {
                if ($item['layout_json']) {
                    $item['layout_json'] = json_decode($item['layout_json'], true);
                }
            }

            $items[$category['id']] = $categoryItems;
        }

        echo json_encode([
            'success' => true,
            'categories' => $categories,
            'items' => $items
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

/**
 * GET /cheatsheet/?action=getCategory&id=X
 * Get single category with items
 */
if ($action === 'getCategory' && $method === 'GET') {
    $id = $_GET['id'] ?? null;

    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing category ID']);
        exit;
    }

    try {
        // Get category
        $stmt = $pdo->prepare("
            SELECT * FROM kdd_cheatsheet_categories
            WHERE id = ? AND authority_id = ? AND enabled = TRUE
        ");
        $stmt->execute([$id, $authority_id]);
        $category = $stmt->fetch();

        if (!$category) {
            http_response_code(404);
            echo json_encode(['error' => 'Category not found']);
            exit;
        }

        // Get items
        $stmt = $pdo->prepare("
            SELECT * FROM kdd_cheatsheet_items
            WHERE category_id = ? AND authority_id = ? AND enabled = TRUE
            ORDER BY order_index ASC
        ");
        $stmt->execute([$id, $authority_id]);
        $items = $stmt->fetchAll();

        // Decode JSON
        foreach ($items as &$item) {
            if ($item['layout_json']) {
                $item['layout_json'] = json_decode($item['layout_json'], true);
            }
        }

        echo json_encode([
            'success' => true,
            'category' => $category,
            'items' => $items
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

// =====================================================
// ADMIN ENDPOINTS
// =====================================================

// Check admin permission from decoded JWT
$hasAdminPermission = false;
if (isset($decoded_jwt->permissions)) {
    $userPerms = $decoded_jwt->permissions;
    $hasAdminPermission = hasPermission($userPerms, 'ADMIN_CHEATSHEET') ||
                         hasAllPermissions($userPerms);
}

/**
 * GET /cheatsheet/?action=getCategoriesAdmin
 * Get all categories (including disabled) for admin
 */
if ($action === 'getCategoriesAdmin' && $method === 'GET') {
    if (!$hasAdminPermission) {
        http_response_code(403);
        echo json_encode(['error' => 'Insufficient permissions']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("
            SELECT c.*, COUNT(i.id) as item_count
            FROM kdd_cheatsheet_categories c
            LEFT JOIN kdd_cheatsheet_items i ON c.id = i.category_id AND i.enabled = TRUE
            WHERE c.authority_id = ?
            GROUP BY c.id
            ORDER BY c.order_index ASC, c.name ASC
        ");
        $stmt->execute([$authority_id]);
        $categories = $stmt->fetchAll();

        // Convert grid values to integers (they might come as strings)
        foreach ($categories as &$category) {
            if (isset($category['grid_x'])) $category['grid_x'] = (int)$category['grid_x'];
            if (isset($category['grid_y'])) $category['grid_y'] = (int)$category['grid_y'];
            if (isset($category['grid_w'])) $category['grid_w'] = (int)$category['grid_w'];
            if (isset($category['grid_h'])) $category['grid_h'] = (int)$category['grid_h'];
        }

        echo json_encode([
            'success' => true,
            'categories' => $categories
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

/**
 * POST /cheatsheet/?action=createCategory
 * Create new category
 */
if ($action === 'createCategory' && $method === 'POST') {
    if (!$hasAdminPermission) {
        http_response_code(403);
        echo json_encode(['error' => 'Insufficient permissions']);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);

    $name = $data['name'] ?? '';
    $description = $data['description'] ?? null;
    $icon = $data['icon'] ?? 'mdi-information';
    $color = $data['color'] ?? 'primary';
    $content_type = $data['content_type'] ?? 'table';
    $width = $data['width'] ?? 'third';
    $height = $data['height'] ?? 'medium';
    $collapsible = ($data['collapsible'] ?? false) ? 1 : 0;
    $default_collapsed = ($data['default_collapsed'] ?? false) ? 1 : 0;
    $show_headers = ($data['show_headers'] ?? true) ? 1 : 0;
    $auto_height = ($data['auto_height'] ?? false) ? 1 : 0;

    if (empty($name)) {
        http_response_code(400);
        echo json_encode(['error' => 'Name is required']);
        exit;
    }

    try {
        // Get max order_index
        $stmt = $pdo->prepare("SELECT MAX(order_index) as max_order FROM kdd_cheatsheet_categories WHERE authority_id = ?");
        $stmt->execute([$authority_id]);
        $max_order = $stmt->fetch()['max_order'] ?? 0;

        $stmt = $pdo->prepare("
            INSERT INTO kdd_cheatsheet_categories
            (authority_id, name, description, icon, color, content_type, width, height, collapsible, default_collapsed, show_headers, auto_height, order_index)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $authority_id, $name, $description, $icon, $color, $content_type,
            $width, $height, $collapsible, $default_collapsed, $show_headers, $auto_height, $max_order + 1
        ]);

        $category_id = $pdo->lastInsertId();

        // Log category creation
        $changes = [
            ['column_name' => 'name', 'old_value' => null, 'new_value' => $name],
            ['column_name' => 'description', 'old_value' => null, 'new_value' => $description],
            ['column_name' => 'icon', 'old_value' => null, 'new_value' => $icon],
            ['column_name' => 'color', 'old_value' => null, 'new_value' => $color],
            ['column_name' => 'content_type', 'old_value' => null, 'new_value' => $content_type],
            ['column_name' => 'width', 'old_value' => null, 'new_value' => $width],
            ['column_name' => 'height', 'old_value' => null, 'new_value' => $height]
        ];
        logDatabaseChange($authority_id, $pdo, 'INSERT', 'kdd_cheatsheet_categories', $category_id, $user_id, $changes);

        echo json_encode([
            'success' => true,
            'id' => $category_id,
            'message' => 'Category created successfully'
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

/**
 * POST /cheatsheet/?action=updateCategory
 * Update existing category
 */
if ($action === 'updateCategory' && $method === 'POST') {
    if (!$hasAdminPermission) {
        http_response_code(403);
        echo json_encode(['error' => 'Insufficient permissions']);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? null;

    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'Category ID is required']);
        exit;
    }

    try {
        // Get old data for logging
        $oldCategory = getEntryById($pdo, $id, 'kdd_cheatsheet_categories', $authority_id);

        // Build dynamic UPDATE query
        $fields = [];
        $values = [];

        $allowedFields = ['name', 'description', 'icon', 'color', 'content_type', 'width',
                         'height', 'enabled', 'collapsible', 'default_collapsed', 'order_index',
                         'show_headers', 'auto_height'];

        $booleanFields = ['enabled', 'collapsible', 'default_collapsed', 'show_headers', 'auto_height'];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $value = $data[$field];

                // Convert boolean fields to 0/1
                if (in_array($field, $booleanFields)) {
                    $value = $value ? 1 : 0;
                }

                $fields[] = "$field = ?";
                $values[] = $value;
            }
        }

        if (empty($fields)) {
            http_response_code(400);
            echo json_encode(['error' => 'No fields to update']);
            exit;
        }

        $values[] = $id;
        $values[] = $authority_id;

        $sql = "UPDATE kdd_cheatsheet_categories SET " . implode(', ', $fields) . " WHERE id = ? AND authority_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);

        // Log category update
        if ($oldCategory) {
            $changes = [];
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $newValue = $data[$field];
                    if (in_array($field, $booleanFields)) {
                        $newValue = $newValue ? 1 : 0;
                    }
                    if (isset($oldCategory[$field]) && $oldCategory[$field] != $newValue) {
                        $changes[] = ['column_name' => $field, 'old_value' => $oldCategory[$field], 'new_value' => $newValue];
                    }
                }
            }
            if (!empty($changes)) {
                logDatabaseChange($authority_id, $pdo, 'UPDATE', 'kdd_cheatsheet_categories', $id, $user_id, $changes);
            }
        }

        echo json_encode([
            'success' => true,
            'message' => 'Category updated successfully'
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

/**
 * POST /cheatsheet/?action=deleteCategory
 * Delete category (and all its items via CASCADE)
 */
if ($action === 'deleteCategory' && $method === 'POST') {
    if (!$hasAdminPermission) {
        http_response_code(403);
        echo json_encode(['error' => 'Insufficient permissions']);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? null;

    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'Category ID is required']);
        exit;
    }

    try {
        // Get old data before deletion for logging
        $oldCategory = getEntryById($pdo, $id, 'kdd_cheatsheet_categories', $authority_id);

        $stmt = $pdo->prepare("DELETE FROM kdd_cheatsheet_categories WHERE id = ? AND authority_id = ?");
        $stmt->execute([$id, $authority_id]);

        // Log hard delete
        if ($oldCategory) {
            $changes = [
                ['column_name' => 'PERMANENT_DELETE', 'old_value' => json_encode($oldCategory), 'new_value' => null]
            ];
            logDatabaseChange($authority_id, $pdo, 'DELETE', 'kdd_cheatsheet_categories', $id, $user_id, $changes);
        }

        echo json_encode([
            'success' => true,
            'message' => 'Category deleted successfully'
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

/**
 * POST /cheatsheet/?action=reorderCategories
 * Update order_index for multiple categories
 */
if ($action === 'reorderCategories' && $method === 'POST') {
    if (!$hasAdminPermission) {
        http_response_code(403);
        echo json_encode(['error' => 'Insufficient permissions']);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $categories = $data['categories'] ?? [];

    if (empty($categories)) {
        http_response_code(400);
        echo json_encode(['error' => 'Categories array is required']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("UPDATE kdd_cheatsheet_categories SET order_index = ? WHERE id = ? AND authority_id = ?");

        foreach ($categories as $index => $categoryId) {
            // Get old data for logging
            $oldCategory = getEntryById($pdo, $categoryId, 'kdd_cheatsheet_categories', $authority_id);

            $stmt->execute([$index, $categoryId, $authority_id]);

            // Log order change
            if ($oldCategory && $oldCategory['order_index'] != $index) {
                $changes = [
                    ['column_name' => 'order_index', 'old_value' => $oldCategory['order_index'], 'new_value' => $index]
                ];
                logDatabaseChange($authority_id, $pdo, 'UPDATE', 'kdd_cheatsheet_categories', $categoryId, $user_id, $changes);
            }
        }

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Categories reordered successfully'
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

/**
 * POST /cheatsheet/?action=updateGridLayout
 * Update grid layout positions and sizes for multiple categories
 */
if ($action === 'updateGridLayout' && $method === 'POST') {
    if (!$hasAdminPermission) {
        http_response_code(403);
        echo json_encode(['error' => 'Insufficient permissions']);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $categories = $data['categories'] ?? [];

    if (empty($categories)) {
        http_response_code(400);
        echo json_encode(['error' => 'Categories array is required']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            UPDATE kdd_cheatsheet_categories
            SET grid_x = ?, grid_y = ?, grid_w = ?, grid_h = ?, width = ?, height = ?
            WHERE id = ? AND authority_id = ?
        ");

        foreach ($categories as $category) {
            $id = $category['id'] ?? null;
            $grid_x = $category['grid_x'] ?? null;
            $grid_y = $category['grid_y'] ?? null;
            $grid_w = $category['grid_w'] ?? null;
            $grid_h = $category['grid_h'] ?? null;
            $width = $category['width'] ?? 'third';
            $height = $category['height'] ?? 'medium';

            if ($id) {
                // Get old data for logging
                $oldCategory = getEntryById($pdo, $id, 'kdd_cheatsheet_categories', $authority_id);

                $stmt->execute([$grid_x, $grid_y, $grid_w, $grid_h, $width, $height, $id, $authority_id]);

                // Log grid layout changes
                if ($oldCategory) {
                    $changes = [];
                    if ($oldCategory['grid_x'] != $grid_x) $changes[] = ['column_name' => 'grid_x', 'old_value' => $oldCategory['grid_x'], 'new_value' => $grid_x];
                    if ($oldCategory['grid_y'] != $grid_y) $changes[] = ['column_name' => 'grid_y', 'old_value' => $oldCategory['grid_y'], 'new_value' => $grid_y];
                    if ($oldCategory['grid_w'] != $grid_w) $changes[] = ['column_name' => 'grid_w', 'old_value' => $oldCategory['grid_w'], 'new_value' => $grid_w];
                    if ($oldCategory['grid_h'] != $grid_h) $changes[] = ['column_name' => 'grid_h', 'old_value' => $oldCategory['grid_h'], 'new_value' => $grid_h];
                    if ($oldCategory['width'] != $width) $changes[] = ['column_name' => 'width', 'old_value' => $oldCategory['width'], 'new_value' => $width];
                    if ($oldCategory['height'] != $height) $changes[] = ['column_name' => 'height', 'old_value' => $oldCategory['height'], 'new_value' => $height];
                    if (!empty($changes)) {
                        logDatabaseChange($authority_id, $pdo, 'UPDATE', 'kdd_cheatsheet_categories', $id, $user_id, $changes);
                    }
                }
            }
        }

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Grid layout updated successfully'
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

/**
 * GET /cheatsheet/?action=getItems&category_id=X
 * Get items for category (admin version - includes disabled)
 */
if ($action === 'getItems' && $method === 'GET') {
    if (!$hasAdminPermission) {
        http_response_code(403);
        echo json_encode(['error' => 'Insufficient permissions']);
        exit;
    }

    $category_id = $_GET['category_id'] ?? null;

    if (!$category_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Category ID is required']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("
            SELECT * FROM kdd_cheatsheet_items
            WHERE category_id = ? AND authority_id = ?
            ORDER BY order_index ASC
        ");
        $stmt->execute([$category_id, $authority_id]);
        $items = $stmt->fetchAll();

        // Decode JSON
        foreach ($items as &$item) {
            if ($item['layout_json']) {
                $item['layout_json'] = json_decode($item['layout_json'], true);
            }
        }

        echo json_encode([
            'success' => true,
            'items' => $items
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

/**
 * POST /cheatsheet/?action=createItem
 * Create new item
 */
if ($action === 'createItem' && $method === 'POST') {
    if (!$hasAdminPermission) {
        http_response_code(403);
        echo json_encode(['error' => 'Insufficient permissions']);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);

    $category_id = $data['category_id'] ?? null;
    $title = $data['title'] ?? null;
    $description = $data['description'] ?? null;
    $content_text = $data['content_text'] ?? null;
    $image_url = $data['image_url'] ?? null;
    $image_caption = $data['image_caption'] ?? null;
    $layout_json = $data['layout_json'] ?? null;

    if (!$category_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Category ID is required']);
        exit;
    }

    try {
        // Get max order_index for this category
        $stmt = $pdo->prepare("SELECT MAX(order_index) as max_order FROM kdd_cheatsheet_items WHERE category_id = ?");
        $stmt->execute([$category_id]);
        $max_order = $stmt->fetch()['max_order'] ?? 0;

        // Encode JSON if present
        if ($layout_json && is_array($layout_json)) {
            $layout_json = json_encode($layout_json);
        }

        $stmt = $pdo->prepare("
            INSERT INTO kdd_cheatsheet_items
            (category_id, authority_id, title, description, content_text, image_url, image_caption, layout_json, order_index)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $category_id, $authority_id, $title, $description, $content_text,
            $image_url, $image_caption, $layout_json, $max_order + 1
        ]);

        $item_id = $pdo->lastInsertId();

        // Log item creation
        $changes = [
            ['column_name' => 'category_id', 'old_value' => null, 'new_value' => $category_id],
            ['column_name' => 'title', 'old_value' => null, 'new_value' => $title],
            ['column_name' => 'description', 'old_value' => null, 'new_value' => $description],
            ['column_name' => 'content_text', 'old_value' => null, 'new_value' => $content_text]
        ];
        logDatabaseChange($authority_id, $pdo, 'INSERT', 'kdd_cheatsheet_items', $item_id, $user_id, $changes);

        echo json_encode([
            'success' => true,
            'id' => $item_id,
            'message' => 'Item created successfully'
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

/**
 * POST /cheatsheet/?action=updateItem
 * Update existing item
 */
if ($action === 'updateItem' && $method === 'POST') {
    if (!$hasAdminPermission) {
        http_response_code(403);
        echo json_encode(['error' => 'Insufficient permissions']);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? null;

    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'Item ID is required']);
        exit;
    }

    try {
        // Get old data for logging
        $oldItem = getEntryById($pdo, $id, 'kdd_cheatsheet_items', $authority_id);

        $fields = [];
        $values = [];

        $allowedFields = ['title', 'description', 'content_text', 'image_url', 'image_caption',
                         'layout_json', 'enabled', 'order_index'];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $value = $data[$field];

                // Encode JSON if it's layout_json and is array
                if ($field === 'layout_json' && is_array($value)) {
                    $value = json_encode($value);
                }

                // Convert boolean fields to 0/1
                if ($field === 'enabled') {
                    $value = $value ? 1 : 0;
                }

                $fields[] = "$field = ?";
                $values[] = $value;
            }
        }

        if (empty($fields)) {
            http_response_code(400);
            echo json_encode(['error' => 'No fields to update']);
            exit;
        }

        $values[] = $id;
        $values[] = $authority_id;

        $sql = "UPDATE kdd_cheatsheet_items SET " . implode(', ', $fields) . " WHERE id = ? AND authority_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);

        // Log item update
        if ($oldItem) {
            $changes = [];
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $newValue = $data[$field];
                    if ($field === 'layout_json' && is_array($newValue)) {
                        $newValue = json_encode($newValue);
                    }
                    if ($field === 'enabled') {
                        $newValue = $newValue ? 1 : 0;
                    }
                    if (isset($oldItem[$field]) && $oldItem[$field] != $newValue) {
                        $changes[] = ['column_name' => $field, 'old_value' => $oldItem[$field], 'new_value' => $newValue];
                    }
                }
            }
            if (!empty($changes)) {
                logDatabaseChange($authority_id, $pdo, 'UPDATE', 'kdd_cheatsheet_items', $id, $user_id, $changes);
            }
        }

        echo json_encode([
            'success' => true,
            'message' => 'Item updated successfully'
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

/**
 * POST /cheatsheet/?action=deleteItem
 * Delete item
 */
if ($action === 'deleteItem' && $method === 'POST') {
    if (!$hasAdminPermission) {
        http_response_code(403);
        echo json_encode(['error' => 'Insufficient permissions']);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? null;

    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'Item ID is required']);
        exit;
    }

    try {
        // Get old data before deletion for logging
        $oldItem = getEntryById($pdo, $id, 'kdd_cheatsheet_items', $authority_id);

        $stmt = $pdo->prepare("DELETE FROM kdd_cheatsheet_items WHERE id = ? AND authority_id = ?");
        $stmt->execute([$id, $authority_id]);

        // Log hard delete
        if ($oldItem) {
            $changes = [
                ['column_name' => 'PERMANENT_DELETE', 'old_value' => json_encode($oldItem), 'new_value' => null]
            ];
            logDatabaseChange($authority_id, $pdo, 'DELETE', 'kdd_cheatsheet_items', $id, $user_id, $changes);
        }

        echo json_encode([
            'success' => true,
            'message' => 'Item deleted successfully'
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

/**
 * POST /cheatsheet/?action=reorderItems
 * Update order_index for multiple items
 */
if ($action === 'reorderItems' && $method === 'POST') {
    if (!$hasAdminPermission) {
        http_response_code(403);
        echo json_encode(['error' => 'Insufficient permissions']);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $items = $data['items'] ?? [];

    if (empty($items)) {
        http_response_code(400);
        echo json_encode(['error' => 'Items array is required']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("UPDATE kdd_cheatsheet_items SET order_index = ? WHERE id = ? AND authority_id = ?");

        foreach ($items as $index => $itemId) {
            // Get old data for logging
            $oldItem = getEntryById($pdo, $itemId, 'kdd_cheatsheet_items', $authority_id);

            $stmt->execute([$index, $itemId, $authority_id]);

            // Log order change
            if ($oldItem && $oldItem['order_index'] != $index) {
                $changes = [
                    ['column_name' => 'order_index', 'old_value' => $oldItem['order_index'], 'new_value' => $index]
                ];
                logDatabaseChange($authority_id, $pdo, 'UPDATE', 'kdd_cheatsheet_items', $itemId, $user_id, $changes);
            }
        }

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Items reordered successfully'
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

/**
 * POST /cheatsheet/?action=uploadImage
 * Upload image for cheatsheet
 */
if ($action === 'uploadImage' && $method === 'POST') {
    if (!$hasAdminPermission) {
        http_response_code(403);
        echo json_encode(['error' => 'Insufficient permissions']);
        exit;
    }

    if (!isset($_FILES['file'])) {
        http_response_code(400);
        echo json_encode(['error' => 'No file uploaded']);
        exit;
    }

    $file = $_FILES['file'];
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($extension, $allowed)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid file type']);
        exit;
    }

    try {
        $upload_dir = __DIR__ . '/../../uploads/cheatsheet/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $filename = uniqid() . '_' . time() . '.' . $extension;
        $filepath = $upload_dir . $filename;

        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            echo json_encode([
                'success' => true,
                'url' => '/uploads/cheatsheet/' . $filename
            ]);
        } else {
            throw new Exception('Failed to move uploaded file');
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

/**
 * POST /cheatsheet/?action=saveScreenshot
 * Save auto-generated cheatsheet screenshot
 */
if ($action === 'saveScreenshot' && $method === 'POST') {
    if (!$hasAdminPermission) {
        http_response_code(403);
        echo json_encode(['error' => 'Insufficient permissions']);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $imageData = $data['image'] ?? null;

    if (!$imageData) {
        http_response_code(400);
        echo json_encode(['error' => 'No image data provided']);
        exit;
    }

    try {
        // Remove data:image/png;base64, prefix if present
        if (strpos($imageData, 'data:image/png;base64,') === 0) {
            $imageData = substr($imageData, strlen('data:image/png;base64,'));
        }

        // Decode base64
        $imageDecoded = base64_decode($imageData);
        if ($imageDecoded === false) {
            throw new Exception('Invalid base64 image data');
        }

        // Create uploads/cheatsheet directory if it doesn't exist
        $upload_dir = __DIR__ . '/../uploads/cheatsheet/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        // Save as authority_{id}.png
        $filename = 'authority_' . $authority_id . '.png';
        $filepath = $upload_dir . $filename;

        if (file_put_contents($filepath, $imageDecoded) === false) {
            throw new Exception('Failed to save image file');
        }

        // Generate public URL (accessible via /uploads/)
        $publicUrl = '/uploads/cheatsheet/' . $filename;

        echo json_encode([
            'success' => true,
            'url' => $publicUrl,
            'message' => 'Screenshot saved successfully'
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

/**
 * GET /cheatsheet/?action=exportCategory&id=X
 * Export category with items as JSON
 */
if ($action === 'exportCategory' && $method === 'GET') {
    if (!$hasAdminPermission) {
        http_response_code(403);
        echo json_encode(['error' => 'Insufficient permissions']);
        exit;
    }

    $id = $_GET['id'] ?? null;

    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'Category ID is required']);
        exit;
    }

    try {
        // Get category
        $stmt = $pdo->prepare("SELECT * FROM kdd_cheatsheet_categories WHERE id = ? AND authority_id = ?");
        $stmt->execute([$id, $authority_id]);
        $category = $stmt->fetch();

        if (!$category) {
            http_response_code(404);
            echo json_encode(['error' => 'Category not found']);
            exit;
        }

        // Get items
        $stmt = $pdo->prepare("SELECT * FROM kdd_cheatsheet_items WHERE category_id = ? AND authority_id = ? ORDER BY order_index ASC");
        $stmt->execute([$id, $authority_id]);
        $items = $stmt->fetchAll();

        // Decode JSON
        foreach ($items as &$item) {
            if ($item['layout_json']) {
                $item['layout_json'] = json_decode($item['layout_json'], true);
            }
        }

        // Remove IDs and timestamps
        unset($category['id'], $category['authority_id'], $category['created_at'], $category['updated_at']);
        foreach ($items as &$item) {
            unset($item['id'], $item['category_id'], $item['authority_id'], $item['created_at'], $item['updated_at']);
        }

        $export = [
            'category' => $category,
            'items' => $items
        ];

        header('Content-Disposition: attachment; filename="cheatsheet_' . $category['name'] . '_' . date('Y-m-d') . '.json"');
        echo json_encode($export, JSON_PRETTY_PRINT);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

/**
 * POST /cheatsheet/?action=importCategory
 * Import category with items from JSON
 */
if ($action === 'importCategory' && $method === 'POST') {
    if (!$hasAdminPermission) {
        http_response_code(403);
        echo json_encode(['error' => 'Insufficient permissions']);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['category']) || !isset($data['items'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid import format']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        $category = $data['category'];
        $items = $data['items'];

        // Get max order_index
        $stmt = $pdo->prepare("SELECT MAX(order_index) as max_order FROM kdd_cheatsheet_categories WHERE authority_id = ?");
        $stmt->execute([$authority_id]);
        $max_order = $stmt->fetch()['max_order'] ?? 0;

        // Insert category
        $stmt = $pdo->prepare("
            INSERT INTO kdd_cheatsheet_categories
            (authority_id, name, description, icon, color, content_type, width, height, collapsible, default_collapsed, enabled, order_index)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $authority_id,
            $category['name'] ?? 'Imported Category',
            $category['description'] ?? null,
            $category['icon'] ?? 'mdi-information',
            $category['color'] ?? 'primary',
            $category['content_type'] ?? 'table',
            $category['width'] ?? 'third',
            $category['height'] ?? 'medium',
            $category['collapsible'] ?? false,
            $category['default_collapsed'] ?? false,
            $category['enabled'] ?? true,
            $max_order + 1
        ]);

        $category_id = $pdo->lastInsertId();

        // Log category import
        $changes = [
            ['column_name' => 'name', 'old_value' => null, 'new_value' => $category['name'] ?? 'Imported Category'],
            ['column_name' => 'description', 'old_value' => null, 'new_value' => $category['description'] ?? null],
            ['column_name' => 'content_type', 'old_value' => null, 'new_value' => $category['content_type'] ?? 'table']
        ];
        logDatabaseChange($authority_id, $pdo, 'INSERT', 'kdd_cheatsheet_categories', $category_id, $user_id, $changes);

        // Insert items
        foreach ($items as $index => $item) {
            $layout_json = $item['layout_json'] ?? null;
            if ($layout_json && is_array($layout_json)) {
                $layout_json = json_encode($layout_json);
            }

            $stmt = $pdo->prepare("
                INSERT INTO kdd_cheatsheet_items
                (category_id, authority_id, title, description, content_text, image_url, image_caption, layout_json, enabled, order_index)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $category_id,
                $authority_id,
                $item['title'] ?? null,
                $item['description'] ?? null,
                $item['content_text'] ?? null,
                $item['image_url'] ?? null,
                $item['image_caption'] ?? null,
                $layout_json,
                $item['enabled'] ?? true,
                $index
            ]);

            // Log item import
            $item_id = $pdo->lastInsertId();
            $changes = [
                ['column_name' => 'category_id', 'old_value' => null, 'new_value' => $category_id],
                ['column_name' => 'title', 'old_value' => null, 'new_value' => $item['title'] ?? null],
                ['column_name' => 'description', 'old_value' => null, 'new_value' => $item['description'] ?? null]
            ];
            logDatabaseChange($authority_id, $pdo, 'INSERT', 'kdd_cheatsheet_items', $item_id, $user_id, $changes);
        }

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Category imported successfully',
            'category_id' => $category_id
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

// Invalid action
http_response_code(400);
echo json_encode(['error' => 'Invalid action']);
