<?php
/**
 * ============================================================
 *  ADMIN LOGOUT — admin_logout.php
 *  Completely destroys admin session and redirects to admin_login.php
 * ============================================================
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/auth.php';

// Explicitly clear all admin-specific session data
unset(
    $_SESSION['admin_logged_in'],
    $_SESSION['admin_id'],
    $_SESSION['admin_full_name'],
    $_SESSION['admin_email'],
    $_SESSION['admin_position'],
    $_SESSION['admin_department'],
    $_SESSION['admin_phone'],
    $_SESSION['auth_portals']['admin']
);
clearPortalSession('admin');

// Clear any legacy role variables if set to admin
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    clearActiveAliases();
}

// Check if any other portal session is active; if not, fully destroy the session
$hasOther = false;
foreach (['employee', 'supervisor', 'client'] as $role) {
    if (!empty($_SESSION[$role . '_id']) || !empty($_SESSION['auth_portals'][$role])) {
        $hasOther = true;
        break;
    }
}

if (!$hasOther) {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
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
    session_destroy();
}

header('Location: admin_login.php?logged_out=1');
exit;
