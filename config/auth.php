<?php
/**
 * ============================================================
 *  OHANABIZ — Centralized Session Auth & Helpers
 *  File   : config/auth.php
 * ============================================================
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';

const OHANA_PORTALS = ['client', 'admin', 'employee', 'supervisor'];

function getUserInitials(string $name): string
{
    $name = trim($name);
    if ($name === '') {
        return 'OB';
    }
    $parts = preg_split('/\s+/', $name) ?: [];
    $initials = '';
    foreach (array_slice($parts, 0, 2) as $p) {
        if ($p === '') {
            continue;
        }
        $char = function_exists('mb_substr') ? mb_substr($p, 0, 1) : substr($p, 0, 1);
        $initials .= strtoupper($char);
    }
    return $initials !== '' ? $initials : 'OB';
}

function portalIdKey(string $role): string
{
    return $role . '_id';
}

function portalFieldKey(string $role, string $field): string
{
    return $role . '_' . $field;
}

function normalizePortal(?string $role): ?string
{
    return in_array($role, OHANA_PORTALS, true) ? $role : null;
}

function requestPortal(?string $role = null): ?string
{
    if ($role !== null) {
        $GLOBALS['OHANA_REQUEST_PORTAL'] = $role;
    }
    $active = $GLOBALS['OHANA_REQUEST_PORTAL'] ?? null;
    return normalizePortal(is_string($active) ? $active : null);
}

function buildPortalPayload(array $user, string $role): array
{
    return [
        'id'         => (int) ($user['id'] ?? 0),
        'full_name'  => $user['full_name'] ?? 'User',
        'email'      => $user['email'] ?? '',
        'role'       => $role,
        'position'   => $user['position'] ?? '',
        'department' => $user['department'] ?? '',
        'phone'      => $user['phone'] ?? '',
    ];
}

function storePortalUser(string $role, array $user): void
{
    $role = normalizePortal($role);
    if ($role === null) {
        return;
    }

    $payload = buildPortalPayload($user, $role);
    $_SESSION['auth_portals'][$role] = $payload;
    $_SESSION[portalIdKey($role)] = $payload['id'];
    $_SESSION[portalFieldKey($role, 'full_name')]  = $payload['full_name'];
    $_SESSION[portalFieldKey($role, 'email')]      = $payload['email'];
    $_SESSION[portalFieldKey($role, 'position')]   = $payload['position'];
    $_SESSION[portalFieldKey($role, 'department')] = $payload['department'];
    $_SESSION[portalFieldKey($role, 'phone')]      = $payload['phone'];
    if ($role === 'admin') {
        $_SESSION['admin_logged_in'] = true;
    }
}

function portalUser(?string $role): ?array
{
    $role = normalizePortal($role);
    if ($role === null) {
        return null;
    }

    if ($role === 'admin' && empty($_SESSION['admin_logged_in'])) {
        return null;
    }

    $id = $_SESSION[portalIdKey($role)] ?? null;
    if (empty($id)) {
        return null;
    }

    $data = $_SESSION['auth_portals'][$role] ?? [
        'id'         => (int) $id,
        'full_name'  => $_SESSION[portalFieldKey($role, 'full_name')] ?? 'User',
        'email'      => $_SESSION[portalFieldKey($role, 'email')] ?? '',
        'role'       => $role,
        'position'   => $_SESSION[portalFieldKey($role, 'position')] ?? '',
        'department' => $_SESSION[portalFieldKey($role, 'department')] ?? '',
        'phone'      => $_SESSION[portalFieldKey($role, 'phone')] ?? '',
    ];

    $data['id'] = (int) $id;
    $data['role'] = $role;
    return $data;
}

function activatePortal(string $role): void
{
    if (portalUser($role) === null) {
        return;
    }
    requestPortal($role);
}

function clearActiveAliases(): void
{
    unset(
        $_SESSION['_active_portal'],
        $_SESSION['user_id'],
        $_SESSION['full_name'],
        $_SESSION['email'],
        $_SESSION['role'],
        $_SESSION['position'],
        $_SESSION['department'],
        $_SESSION['phone']
    );
}

function clearPortalSession(string $role): void
{
    $role = normalizePortal($role);
    if ($role === null) {
        return;
    }

    unset(
        $_SESSION['auth_portals'][$role],
        $_SESSION[portalIdKey($role)],
        $_SESSION[portalFieldKey($role, 'full_name')],
        $_SESSION[portalFieldKey($role, 'email')],
        $_SESSION[portalFieldKey($role, 'position')],
        $_SESSION[portalFieldKey($role, 'department')],
        $_SESSION[portalFieldKey($role, 'phone')]
    );

    if ($role === 'admin') {
        unset($_SESSION['admin_logged_in']);
    }

    if (requestPortal() === $role) {
        unset($GLOBALS['OHANA_REQUEST_PORTAL']);
    }
}

/**
 * Copy a single-role legacy session into isolated portal keys.
 */
function migrateLegacySession(): void
{
    $hasPortal = false;
    foreach (OHANA_PORTALS as $role) {
        if (!empty($_SESSION[portalIdKey($role)])) {
            $hasPortal = true;
            break;
        }
    }

    $legacyRole = normalizePortal($_SESSION['role'] ?? null);
    $legacyId   = $_SESSION['user_id'] ?? null;

    if (!$hasPortal && !empty($legacyId) && $legacyRole !== null) {
        storePortalUser($legacyRole, [
            'id'         => $legacyId,
            'full_name'  => $_SESSION['full_name'] ?? 'User',
            'email'      => $_SESSION['email'] ?? '',
            'position'   => $_SESSION['position'] ?? '',
            'department' => $_SESSION['department'] ?? '',
            'phone'      => $_SESSION['phone'] ?? '',
        ]);
    }

    clearActiveAliases();
}

migrateLegacySession();

/**
 * Persist a portal-specific session after login / registration.
 * Other role keys in the same browser stay independent.
 */
function establishUserSession(array $user): void
{
    $role = normalizePortal($user['role'] ?? null);
    if ($role === null) {
        return;
    }

    storePortalUser($role, $user);
    activatePortal($role);
}

function loginPageForRole(?string $role = null): string
{
    $map = [
        'admin'      => 'admin_login.php',
        'supervisor' => 'employee_login.php',
        'employee'   => 'employee_login.php',
        'client'     => 'login.php',
    ];
    return $map[$role ?? ''] ?? 'login.php';
}

function dashboardForRole(?string $role = null): string
{
    $map = [
        'admin'      => 'admin_dashboard.php',
        'supervisor' => 'supervisor_dashboard.php',
        'employee'   => 'employee_dashboard.php',
        'client'     => 'client_dashboard.php',
    ];
    return $map[$role ?? ''] ?? 'index.php';
}

/**
 * Redirect only when THIS portal is already signed in.
 * A client session must not bounce admin/staff login pages.
 */
function redirectIfLoggedIn($portals = null): void
{
    $roles = $portals === null ? OHANA_PORTALS : (array) $portals;
    foreach ($roles as $role) {
        if (portalUser($role)) {
            header('Location: ' . dashboardForRole($role));
            exit;
        }
    }
}

function requireRole($allowed_roles = null): void
{
    if ($allowed_roles === null) {
        foreach (OHANA_PORTALS as $role) {
            if (!empty($_SESSION[portalIdKey($role)]) && portalUser($role)) {
                activatePortal($role);
                return;
            }
        }
        header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI'] ?? ''));
        exit;
    }

    $allowed = (array) $allowed_roles;
    foreach ($allowed as $role) {
        $role = normalizePortal((string) $role);
        if ($role === 'admin') {
            if (!empty($_SESSION['admin_logged_in']) && !empty($_SESSION['admin_id']) && portalUser('admin')) {
                activatePortal('admin');
                return;
            }
        } elseif ($role && !empty($_SESSION[portalIdKey($role)]) && portalUser($role)) {
            activatePortal($role);
            return;
        }
    }

    $target = loginPageForRole($allowed[0] ?? null);
    header('Location: ' . $target . '?redirect=' . urlencode($_SERVER['REQUEST_URI'] ?? ''));
    exit;
}

function requireLogin($allowed_roles = null): void
{
    requireRole($allowed_roles);
}

function requireAdminLogin(): void
{
    requireRole('admin');
}

function requireEmployeeLogin(): void
{
    requireRole('employee');
}

function requireSupervisorLogin(): void
{
    requireRole('supervisor');
}

function isLoggedIn($role = null): bool
{
    if ($role === null) {
        foreach (OHANA_PORTALS as $portal) {
            if (portalUser($portal)) {
                return true;
            }
        }
        return false;
    }

    foreach ((array) $role as $portal) {
        if (portalUser($portal)) {
            return true;
        }
    }
    return false;
}

function hasRole($role): bool
{
    return isLoggedIn($role);
}

function currentUser(?string $role = null): ?array
{
    $portal = normalizePortal($role) ?? requestPortal();
    $user = portalUser($portal);
    if ($user === null) {
        return null;
    }

    $name = $user['full_name'] ?? 'User';
    return [
        'id'         => (int) $user['id'],
        'full_name'  => $name,
        'email'      => $user['email'] ?? '',
        'role'       => $user['role'] ?? '',
        'position'   => $user['position'] ?? '',
        'department' => $user['department'] ?? '',
        'phone'      => $user['phone'] ?? '',
        'initials'   => getUserInitials($name),
    ];
}

function destroyAuthCookie(): void
{
    if (!ini_get('session.use_cookies')) {
        return;
    }
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

function logoutUser(?string $portal = null): void
{
    $portal = normalizePortal($portal);

    if ($portal !== null) {
        clearPortalSession($portal);
        if ($portal === 'admin') {
            unset($_SESSION['admin_logged_in'], $_SESSION['admin_id']);
        }
        $stillLoggedIn = false;
        foreach (OHANA_PORTALS as $role) {
            if (portalUser($role)) {
                $stillLoggedIn = true;
                break;
            }
        }
        if ($stillLoggedIn) {
            return;
        }
    }

    $_SESSION = [];
    destroyAuthCookie();
    session_destroy();
}

function createNotification(
    PDO $pdo,
    int $userId,
    string $title,
    string $message,
    string $type = 'info',
    ?string $link = null
): void {
    $allowed = ['info', 'success', 'warning', 'error', 'task', 'request'];
    if (!in_array($type, $allowed, true)) {
        $type = 'info';
    }
    $stmt = $pdo->prepare(
        'INSERT INTO notifications (user_id, title, message, type, link) VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->execute([$userId, $title, $message, $type, $link]);
}

function unreadNotificationCount(PDO $pdo, int $userId): int
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
    $stmt->execute([$userId]);
    return (int) $stmt->fetchColumn();
}

function fetchPrimarySupervisor(PDO $pdo): ?array
{
    $stmt = $pdo->query(
        "SELECT id, full_name, email, position, phone
         FROM users
         WHERE role = 'supervisor' AND status = 'active'
         ORDER BY id ASC
         LIMIT 1"
    );
    $row = $stmt->fetch();
    return $row ?: null;
}

function notifyUsersByRole(
    PDO $pdo,
    string $role,
    string $title,
    string $message,
    string $type = 'info',
    ?string $link = null
): void {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE role = ? AND status = 'active'");
    $stmt->execute([$role]);
    foreach ($stmt->fetchAll() as $row) {
        createNotification($pdo, (int) $row['id'], $title, $message, $type, $link);
    }
}

function generateServiceReference(PDO $pdo): string
{
    $year = date('Y');
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM service_requests WHERE reference_no LIKE ?');
    $stmt->execute(['REQ-' . $year . '-%']);
    $next = (int) $stmt->fetchColumn() + 1;
    return sprintf('REQ-%s-%05d', $year, $next);
}

function formatTimeValue(?string $time): string
{
    if ($time === null || $time === '') {
        return '—';
    }
    $ts = strtotime($time);
    return $ts ? date('h:i A', $ts) : $time;
}

function formatLeaveType(string $type): string
{
    $map = [
        'vacation'  => 'Vacation Leave',
        'sick'      => 'Sick Leave',
        'emergency' => 'Emergency Leave',
        'maternity' => 'Maternity Leave',
        'paternity' => 'Paternity Leave',
        'other'     => 'Other',
    ];
    return $map[$type] ?? ucfirst($type);
}

function fetchLeaveRequests(PDO $pdo, array $statuses = []): array
{
    $sql = "SELECT lr.*, u.full_name, u.position, u.department, u.email,
                   eu.full_name AS endorsed_name, au.full_name AS approved_name
            FROM leave_requests lr
            JOIN users u ON lr.user_id = u.id
            LEFT JOIN users eu ON lr.endorsed_by = eu.id
            LEFT JOIN users au ON lr.approved_by = au.id";
    $params = [];
    if ($statuses) {
        $placeholders = implode(',', array_fill(0, count($statuses), '?'));
        $sql .= " WHERE lr.status IN ($placeholders)";
        $params = $statuses;
    }
    $sql .= ' ORDER BY lr.created_at DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function fetchUserNotifications(PDO $pdo, int $userId, int $limit = 40): array
{
    $stmt = $pdo->prepare(
        'SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ' . (int) $limit
    );
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function markAllNotificationsRead(PDO $pdo, int $userId): void
{
    $stmt = $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0');
    $stmt->execute([$userId]);
}
