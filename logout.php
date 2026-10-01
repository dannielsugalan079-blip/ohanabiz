<?php
require_once __DIR__ . '/config/auth.php';

$allowedNext = [
    'employee_login.php',
    'login.php',
    'admin_login.php',
];

$forceReset = isset($_GET['reset']);
$next       = basename((string) ($_GET['to'] ?? ''));

if ($forceReset) {
    session_unset();
    logoutUser(null);

    $redirect = in_array($next, $allowedNext, true) ? $next : 'employee_login.php';
    header('Location: ' . $redirect);
    exit;
}

$requested = $_GET['portal'] ?? null;
$portal    = normalizePortal(is_string($requested) ? $requested : null);

// If no explicit portal in query string, detect active session
if ($portal === null) {
    if (!empty($_SESSION['admin_logged_in']) || !empty($_SESSION['admin_id'])) {
        $portal = 'admin';
    } elseif (portalUser('supervisor')) {
        $portal = 'supervisor';
    } elseif (portalUser('employee')) {
        $portal = 'employee';
    } elseif (portalUser('client')) {
        $portal = 'client';
    }
}

if ($portal === 'admin') {
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

    $hasOther = false;
    foreach (['employee', 'supervisor', 'client'] as $role) {
        if (!empty($_SESSION[$role . '_id']) || !empty($_SESSION['auth_portals'][$role])) {
            $hasOther = true;
            break;
        }
    }
    if (!$hasOther) {
        $_SESSION = [];
        destroyAuthCookie();
        session_destroy();
    }

    header('Location: admin_login.php?logged_out=1');
    exit;
}

if ($portal === null) {
    $_SESSION = [];
    destroyAuthCookie();
    session_destroy();
    header('Location: admin_login.php');
    exit;
}

$redirect = loginPageForRole($portal);
logoutUser($portal);

header('Location: ' . $redirect . '?logged_out=1');
exit;
