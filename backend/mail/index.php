<?php
/**
 * Backend Endpoint: mail/index.php
 * Complete Mail System with Accounts, Domains, Send/Receive, Company Mailboxes
 * Uses Cookie-based Authentication and PDO database connection.
 */
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

// --- Dependencies & DB Connection ---
try {
    require_once __DIR__ . '/../db.php'; // Defines $pdo
} catch (Exception $e) {
    http_response_code(500); echo json_encode(["error" => "Database connection failed."]); exit();
}
require_once __DIR__ . '/../logging/logging.php';

// --- Authentication via JWT/Cookie ---
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

if ($userId === null || !is_int($userId) || $authority === null || !is_string($authority) || $authorityId === null) {
    http_response_code(400); echo json_encode(["error" => "Invalid token payload."]); exit();
}

require_once __DIR__ . '/../utils/authority_helper.php';
if (!isValidAuthority($pdo, $authorityId)) {
    http_response_code(403); echo json_encode(["error" => "Invalid authority context."]); exit();
}

// Feature-Zugriff prüfen
if (!hasFeatureAccess($pdo, $authorityId, 'mail')) {
    http_response_code(403); echo json_encode(["error" => "This authority doesn't have access to mail features."]); exit();
}

// --- Authorization & Action Routing ---
$request_method = $_SERVER['REQUEST_METHOD'];
$action = $_REQUEST['action'] ?? '';

// Map actions to required permissions (module.action format)
$permissions_map = [
    // Mail Account Management
    'getMyMailAccounts'      => ['module' => 'mail', 'action' => 'read'],
    'createMailAccount'      => ['module' => 'mail', 'action' => 'write'],
    'linkMailAccount'        => ['module' => 'mail', 'action' => 'write'],
    'unlinkMailAccount'      => ['module' => 'mail', 'action' => 'write'],
    'changeMailPassword'     => ['module' => 'mail', 'action' => 'write'],
    'updateMailSettings'     => ['module' => 'mail', 'action' => 'write'],

    // Domain Management
    'getAvailableDomains'    => ['module' => 'mail', 'action' => 'read'],
    'checkMailAvailability'  => ['module' => 'mail', 'action' => 'read'],
    'getDomainInfo'          => ['module' => 'mail', 'action' => 'read'],

    // Mail Operations
    'getInbox'               => ['module' => 'mail', 'action' => 'read'],
    'getSent'                => ['module' => 'mail', 'action' => 'read'],
    'getDrafts'              => ['module' => 'mail', 'action' => 'read'],
    'getStarred'             => ['module' => 'mail', 'action' => 'read'],
    'getTrash'               => ['module' => 'mail', 'action' => 'read'],
    'getMail'                => ['module' => 'mail', 'action' => 'read'],
    'sendMail'               => ['module' => 'mail', 'action' => 'write'],
    'saveDraft'              => ['module' => 'mail', 'action' => 'write'],
    'markAsRead'             => ['module' => 'mail', 'action' => 'write'],
    'markAsStarred'          => ['module' => 'mail', 'action' => 'write'],
    'deleteMail'             => ['module' => 'mail', 'action' => 'write'],
    'restoreMail'            => ['module' => 'mail', 'action' => 'write'],
    'permanentDeleteMail'    => ['module' => 'mail', 'action' => 'delete'],

    // Bulk Actions
    'bulkMarkAsRead'         => ['module' => 'mail', 'action' => 'write'],
    'bulkDelete'             => ['module' => 'mail', 'action' => 'write'],
    'bulkMove'               => ['module' => 'mail', 'action' => 'write'],
    'bulkAction'             => ['module' => 'mail', 'action' => 'write'],
    'moveToFolder'           => ['module' => 'mail', 'action' => 'write'],

    // Attachments
    'getAttachments'         => ['module' => 'mail', 'action' => 'read'],
    'uploadAttachment'       => ['module' => 'mail', 'action' => 'write'],
    'downloadAttachment'     => ['module' => 'mail', 'action' => 'read'],
    'deleteAttachment'       => ['module' => 'mail', 'action' => 'write'],

    // Company Mailboxes
    'getCompanyMailboxes'    => ['module' => 'mail', 'action' => 'read'],
    'createCompanyMailbox'   => ['module' => 'mail', 'action' => 'admin'],
    'updateCompanyMailbox'   => ['module' => 'mail', 'action' => 'admin'],
    'deleteCompanyMailbox'   => ['module' => 'mail', 'action' => 'admin'],
    'addMailboxPermission'   => ['module' => 'mail', 'action' => 'admin'],
    'removeMailboxPermission'=> ['module' => 'mail', 'action' => 'admin'],
    'updateMailboxPermission'=> ['module' => 'mail', 'action' => 'admin'],
    'getMailboxPermissions'  => ['module' => 'mail', 'action' => 'read'],
    'getMailboxMembers'      => ['module' => 'mail', 'action' => 'read'],
    'assignMail'             => ['module' => 'mail', 'action' => 'write'],
    'updateMailStatus'       => ['module' => 'mail', 'action' => 'write'],
    'updateMailboxSettings'  => ['module' => 'mail', 'action' => 'admin'],

    // Templates
    'getTemplates'           => ['module' => 'mail', 'action' => 'read'],
    'getTemplateById'        => ['module' => 'mail', 'action' => 'read'],
    'createTemplate'         => ['module' => 'mail', 'action' => 'write'],
    'updateTemplate'         => ['module' => 'mail', 'action' => 'write'],
    'deleteTemplate'         => ['module' => 'mail', 'action' => 'write'],
    'renderTemplate'         => ['module' => 'mail', 'action' => 'read'],

    // Contacts
    'getContacts'            => ['module' => 'mail', 'action' => 'read'],
    'searchContacts'         => ['module' => 'mail', 'action' => 'read'],
    'getGlobalDirectory'     => ['module' => 'mail', 'action' => 'read'],
    'createContact'          => ['module' => 'mail', 'action' => 'write'],
    'updateContact'          => ['module' => 'mail', 'action' => 'write'],
    'deleteContact'          => ['module' => 'mail', 'action' => 'write'],
    'toggleFavorite'         => ['module' => 'mail', 'action' => 'write'],
    'importContacts'         => ['module' => 'mail', 'action' => 'write'],
    'exportContacts'         => ['module' => 'mail', 'action' => 'read'],

    // Folders & Labels
    'getFolders'             => ['module' => 'mail', 'action' => 'read'],
    'createFolder'           => ['module' => 'mail', 'action' => 'write'],
    'updateFolder'           => ['module' => 'mail', 'action' => 'write'],
    'deleteFolder'           => ['module' => 'mail', 'action' => 'write'],
    'getLabels'              => ['module' => 'mail', 'action' => 'read'],
    'createLabel'            => ['module' => 'mail', 'action' => 'write'],
    'updateLabel'            => ['module' => 'mail', 'action' => 'write'],
    'deleteLabel'            => ['module' => 'mail', 'action' => 'write'],
    'applyLabel'             => ['module' => 'mail', 'action' => 'write'],
    'removeLabel'            => ['module' => 'mail', 'action' => 'write'],

    // Signatures
    'getSignatures'          => ['module' => 'mail', 'action' => 'read'],
    'createSignature'        => ['module' => 'mail', 'action' => 'write'],
    'updateSignature'        => ['module' => 'mail', 'action' => 'write'],
    'deleteSignature'        => ['module' => 'mail', 'action' => 'write'],

    // Admin Functions
    'getAllMailAccountsAdmin'    => ['module' => 'mail', 'action' => 'admin'],
    'updateAccountQuota'         => ['module' => 'mail', 'action' => 'admin'],
    'lockUnlockAccount'          => ['module' => 'mail', 'action' => 'admin'],
    'getMailSystemStats'         => ['module' => 'mail', 'action' => 'admin'],
    'updateAccountSettingsAdmin' => ['module' => 'mail', 'action' => 'admin'],
    'deleteAccountAdmin'         => ['module' => 'mail', 'action' => 'admin'],

    // Access Rights
    'grantMailAccess'            => ['module' => 'mail', 'action' => 'write'],
    'revokeMailAccess'           => ['module' => 'mail', 'action' => 'write'],
    'getMailAccessList'          => ['module' => 'mail', 'action' => 'read'],
];

$required_permission = $permissions_map[$action] ?? null;
$has_permission = false;

if ($action === '') {
    http_response_code(400);
    echo json_encode(["error" => "No action specified."]);
    exit();
}

if ($required_permission === null) {
    http_response_code(404);
    echo json_encode(['error' => 'Invalid action specified.']);
    exit();
}

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
} elseif ($actionType === 'read' && hasModulePermission($userPermissions, $module, 'admin')) {
    $has_permission = true; // ADMIN implies READ
} elseif ($actionType === 'write' && hasModulePermission($userPermissions, $module, 'delete')) {
    $has_permission = true; // DELETE implies WRITE
} elseif ($actionType === 'write' && hasModulePermission($userPermissions, $module, 'admin')) {
    $has_permission = true; // ADMIN implies WRITE
} elseif ($actionType === 'delete' && hasModulePermission($userPermissions, $module, 'admin')) {
    $has_permission = true; // ADMIN implies DELETE
}

// --- Execute Action or Deny ---
if ($has_permission) {
    // Load sub-modules
    require_once __DIR__ . '/modules/accounts.php';
    require_once __DIR__ . '/modules/domains.php';
    require_once __DIR__ . '/modules/attachments.php';
    require_once __DIR__ . '/modules/operations.php';
    require_once __DIR__ . '/modules/company_mailboxes.php';
    require_once __DIR__ . '/modules/templates.php';
    require_once __DIR__ . '/modules/contacts.php';
    require_once __DIR__ . '/modules/folders.php';
    require_once __DIR__ . '/modules/signatures.php';
    require_once __DIR__ . '/modules/admin.php';
    require_once __DIR__ . '/modules/access_rights.php';

    $is_post = ($request_method === 'POST');
    $is_get = ($request_method === 'GET');
    $is_put = ($request_method === 'PUT');
    $is_delete = ($request_method === 'DELETE');

    switch ($action) {
        // Account Management
        case 'getMyMailAccounts':      if ($is_get) getMyMailAccounts($pdo, $userId, $authorityId); else MethodNotAllowed(); break;
        case 'createMailAccount':      if ($is_post) createMailAccount($pdo, $userId, $authorityId); else MethodNotAllowed(); break;
        case 'linkMailAccount':        if ($is_post) linkMailAccount($pdo, $userId, $authorityId); else MethodNotAllowed(); break;
        case 'unlinkMailAccount':      if ($is_post) unlinkMailAccount($pdo, $userId, $authorityId); else MethodNotAllowed(); break;
        case 'changeMailPassword':     if ($is_post) changeMailPassword($pdo, $userId, $authorityId); else MethodNotAllowed(); break;
        case 'updateMailSettings':     if ($is_post) updateMailSettings($pdo, $userId, $authorityId); else MethodNotAllowed(); break;

        // Domain Management
        case 'getAvailableDomains':    if ($is_get) getAvailableDomains($pdo, $authorityId); else MethodNotAllowed(); break;
        case 'checkMailAvailability':  if ($is_get) checkMailAvailability($pdo); else MethodNotAllowed(); break;
        case 'getDomainInfo':          if ($is_get) getDomainInfo($pdo, $authorityId); else MethodNotAllowed(); break;

        // Mail Operations
        case 'getInbox':               if ($is_get) getInbox($pdo, $userId, $authorityId); else MethodNotAllowed(); break;
        case 'getSent':                if ($is_get) getSent($pdo, $userId, $authorityId); else MethodNotAllowed(); break;
        case 'getDrafts':              if ($is_get) getDrafts($pdo, $userId, $authorityId); else MethodNotAllowed(); break;
        case 'getStarred':             if ($is_get) getStarred($pdo, $userId, $authorityId); else MethodNotAllowed(); break;
        case 'getTrash':               if ($is_get) getTrash($pdo, $userId, $authorityId); else MethodNotAllowed(); break;
        case 'getMail':                if ($is_get) getMail($pdo, $userId, $authorityId); else MethodNotAllowed(); break;
        case 'sendMail':               if ($is_post) sendMail($pdo, $userId, $authorityId); else MethodNotAllowed(); break;
        case 'saveDraft':              if ($is_post) saveDraft($pdo, $userId, $authorityId); else MethodNotAllowed(); break;
        case 'markAsRead':             if ($is_post) markAsRead($pdo, $userId, $authorityId); else MethodNotAllowed(); break;
        case 'markAsStarred':          if ($is_post) markAsStarred($pdo, $userId, $authorityId); else MethodNotAllowed(); break;
        case 'deleteMail':             if ($is_post) deleteMail($pdo, $userId, $authorityId); else MethodNotAllowed(); break;
        case 'restoreMail':            if ($is_post) restoreMail($pdo, $userId, $authorityId); else MethodNotAllowed(); break;
        case 'permanentDeleteMail':    if ($is_post) permanentDeleteMail($pdo, $userId, $authorityId); else MethodNotAllowed(); break;

        // Bulk Actions
        case 'bulkMarkAsRead':         if ($is_post) bulkMarkAsRead($pdo, $userId, $authorityId); else MethodNotAllowed(); break;
        case 'bulkDelete':             if ($is_post) bulkDelete($pdo, $userId, $authorityId); else MethodNotAllowed(); break;
        case 'bulkMove':               if ($is_post) bulkMove($pdo, $userId, $authorityId); else MethodNotAllowed(); break;
        case 'bulkAction':             if ($is_post) bulkAction($pdo, $userId, $authorityId); else MethodNotAllowed(); break;
        case 'moveToFolder':           if ($is_post) moveToFolder($pdo, $userId, $authorityId); else MethodNotAllowed(); break;

        // Attachments
        case 'getAttachments':         if ($is_get) getAttachments($pdo, $userId, $authorityId); else MethodNotAllowed(); break;
        case 'uploadAttachment':       if ($is_post) uploadAttachment($pdo, $userId, $authorityId); else MethodNotAllowed(); break;
        case 'downloadAttachment':     if ($is_get) downloadAttachment($pdo, $userId, $authorityId); else MethodNotAllowed(); break;
        case 'deleteAttachment':       if ($is_post) deleteAttachment($pdo, $userId, $authorityId); else MethodNotAllowed(); break;

        // Company Mailboxes
        case 'getCompanyMailboxes':    if ($is_get) getCompanyMailboxes($pdo, $userId, $authorityId); else MethodNotAllowed(); break;
        case 'createCompanyMailbox':   if ($is_post) createCompanyMailbox($pdo, $userId, $authorityId); else MethodNotAllowed(); break;
        case 'updateCompanyMailbox':   if ($is_post) updateCompanyMailbox($pdo, $userId, $authorityId); else MethodNotAllowed(); break;
        case 'updateMailboxSettings':  if ($is_post) updateCompanyMailbox($pdo, $userId, $authorityId); else MethodNotAllowed(); break; // Alias
        case 'deleteCompanyMailbox':   if ($is_post) deleteCompanyMailbox($pdo, $userId, $authorityId); else MethodNotAllowed(); break;
        case 'addMailboxPermission':   if ($is_post) addMailboxPermission($pdo, $userId, $authorityId); else MethodNotAllowed(); break;
        case 'removeMailboxPermission':if ($is_post) removeMailboxPermission($pdo, $userId, $authorityId); else MethodNotAllowed(); break;
        case 'updateMailboxPermission':if ($is_post) updateMailboxPermission($pdo, $userId, $authorityId); else MethodNotAllowed(); break;
        case 'getMailboxPermissions':  if ($is_get) getMailboxPermissions($pdo, $userId, $authorityId); else MethodNotAllowed(); break;
        case 'getMailboxMembers':      if ($is_get) getMailboxMembers($pdo, $userId, $authorityId); else MethodNotAllowed(); break;
        case 'assignMail':             if ($is_post) assignMail($pdo, $userId, $authorityId); else MethodNotAllowed(); break;
        case 'updateMailStatus':       if ($is_post) updateMailStatus($pdo, $userId, $authorityId); else MethodNotAllowed(); break;

        // Templates
        case 'getTemplates':           if ($is_get) getTemplates($pdo, $userId, $authorityId); else MethodNotAllowed(); break;
        case 'getTemplateById':        if ($is_get) getTemplateById($pdo, $userId, $authorityId); else MethodNotAllowed(); break;
        case 'createTemplate':         if ($is_post) createTemplate($pdo, $userId, $authorityId); else MethodNotAllowed(); break;
        case 'updateTemplate':         if ($is_post) updateTemplate($pdo, $userId, $authorityId); else MethodNotAllowed(); break;
        case 'deleteTemplate':         if ($is_post) deleteTemplate($pdo, $userId, $authorityId); else MethodNotAllowed(); break;
        case 'renderTemplate':         if ($is_post) renderTemplate($pdo, $userId, $authorityId); else MethodNotAllowed(); break;

        // Contacts
        case 'getContacts':            if ($is_get) getContacts($pdo, $userId, $authorityId); else MethodNotAllowed(); break;
        case 'searchContacts':         if ($is_get) searchContacts($pdo, $userId, $authorityId); else MethodNotAllowed(); break;
        case 'getGlobalDirectory':     if ($is_get) getGlobalDirectory($pdo, $userId, $authorityId); else MethodNotAllowed(); break;
        case 'createContact':          if ($is_post) createContact($pdo, $userId, $authorityId); else MethodNotAllowed(); break;
        case 'updateContact':          if ($is_post) updateContact($pdo, $userId, $authorityId); else MethodNotAllowed(); break;
        case 'deleteContact':          if ($is_post) deleteContact($pdo, $userId, $authorityId); else MethodNotAllowed(); break;
        case 'toggleFavorite':         if ($is_post) toggleFavorite($pdo, $userId, $authorityId); else MethodNotAllowed(); break;
        case 'importContacts':         if ($is_post) importContacts($pdo, $userId, $authorityId); else MethodNotAllowed(); break;
        case 'exportContacts':         if ($is_get) exportContacts($pdo, $userId, $authorityId); else MethodNotAllowed(); break;

        // Folders & Labels
        case 'getFolders':             if ($is_get) getFolders($pdo, $userId, $authorityId); else MethodNotAllowed(); break;
        case 'createFolder':           if ($is_post) createFolder($pdo, $userId, $authorityId); else MethodNotAllowed(); break;
        case 'updateFolder':           if ($is_post) updateFolder($pdo, $userId, $authorityId); else MethodNotAllowed(); break;
        case 'deleteFolder':           if ($is_post) deleteFolder($pdo, $userId, $authorityId); else MethodNotAllowed(); break;
        case 'getLabels':              if ($is_get) getLabels($pdo, $userId, $authorityId); else MethodNotAllowed(); break;
        case 'createLabel':            if ($is_post) createLabel($pdo, $userId, $authorityId); else MethodNotAllowed(); break;
        case 'updateLabel':            if ($is_post) updateLabel($pdo, $userId, $authorityId); else MethodNotAllowed(); break;
        case 'deleteLabel':            if ($is_post) deleteLabel($pdo, $userId, $authorityId); else MethodNotAllowed(); break;
        case 'applyLabel':             if ($is_post) applyLabel($pdo, $userId, $authorityId); else MethodNotAllowed(); break;
        case 'removeLabel':            if ($is_post) removeLabel($pdo, $userId, $authorityId); else MethodNotAllowed(); break;

        // Signatures
        case 'getSignatures':          if ($is_get) getSignatures($pdo, $userId, $authorityId); else MethodNotAllowed(); break;
        case 'createSignature':        if ($is_post) createSignature($pdo, $userId, $authorityId); else MethodNotAllowed(); break;
        case 'updateSignature':        if ($is_post) updateSignature($pdo, $userId, $authorityId); else MethodNotAllowed(); break;
        case 'deleteSignature':        if ($is_post) deleteSignature($pdo, $userId, $authorityId); else MethodNotAllowed(); break;

        // Admin Functions
        case 'getAllMailAccountsAdmin':    if ($is_get) getAllMailAccountsAdmin($pdo, $userId, $authorityId); else MethodNotAllowed(); break;
        case 'updateAccountQuota':         if ($is_post) updateAccountQuota($pdo, $userId, $authorityId); else MethodNotAllowed(); break;
        case 'lockUnlockAccount':          if ($is_post) lockUnlockAccount($pdo, $userId, $authorityId); else MethodNotAllowed(); break;
        case 'getMailSystemStats':         if ($is_get) getMailSystemStats($pdo, $userId, $authorityId); else MethodNotAllowed(); break;
        case 'updateAccountSettingsAdmin': if ($is_post) updateAccountSettingsAdmin($pdo, $userId, $authorityId); else MethodNotAllowed(); break;
        case 'deleteAccountAdmin':         if ($is_post) deleteAccountAdmin($pdo, $userId, $authorityId); else MethodNotAllowed(); break;

        // Access Rights
        case 'grantMailAccess':            if ($is_post) grantMailAccess($pdo, $userId, $authorityId); else MethodNotAllowed(); break;
        case 'revokeMailAccess':           if ($is_post) revokeMailAccess($pdo, $userId, $authorityId); else MethodNotAllowed(); break;
        case 'getMailAccessList':          if ($is_get) getMailAccessList($pdo, $userId, $authorityId); else MethodNotAllowed(); break;

        default: http_response_code(500); echo json_encode(['error' => 'Action routing error.']); break;
    }
} else {
    http_response_code(403);
    echo json_encode(["error" => "Permission denied for action: " . htmlspecialchars($action)]);
}

exit();
?>
