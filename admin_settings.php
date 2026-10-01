<?php
require_once __DIR__ . '/config/auth.php';
requireLogin('admin');
if (empty($_SESSION['admin_logged_in']) || empty($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}
$user = currentUser('admin') ?: currentUser();

$admin = $pdo->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
$admin->execute([(int) $user['id']]);
$profile = $admin->fetch(PDO::FETCH_ASSOC) ?: $user;

$notice = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_profile') {
    $fullName = trim((string) ($_POST['full_name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $phone = trim((string) ($_POST['phone'] ?? ''));
    $position = trim((string) ($_POST['position'] ?? ''));
    $department = trim((string) ($_POST['department'] ?? ''));

    if ($fullName === '' || $email === '') {
        $notice = 'Full name and email are required.';
    } else {
        $check = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1');
        $check->execute([$email, (int) $user['id']]);
        if ($check->fetch()) {
            $notice = 'That email address is already in use by another user.';
        } else {
            $stmt = $pdo->prepare('UPDATE users SET full_name = ?, email = ?, phone = ?, position = ?, department = ?, updated_at = NOW() WHERE id = ?');
            $stmt->execute([$fullName, $email, $phone, $position, $department, (int) $user['id']]);

            $_SESSION['auth_portals']['admin']['full_name'] = $fullName;
            $_SESSION['auth_portals']['admin']['email'] = $email;
            $_SESSION['auth_portals']['admin']['position'] = $position;
            $_SESSION['auth_portals']['admin']['department'] = $department;
            $_SESSION['auth_portals']['admin']['phone'] = $phone;

            $profile = array_merge((array) $profile, [
                'full_name' => $fullName,
                'email' => $email,
                'phone' => $phone,
                'position' => $position,
                'department' => $department,
            ]);

            $notice = 'Profile updated successfully.';
        }
    }
}

$session_count = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role IN ('employee','supervisor') AND status = 'active'")->fetchColumn();
$task_count = (int) $pdo->query("SELECT COUNT(*) FROM tasks WHERE status IN ('pending','in_progress','for_review')")->fetchColumn();
$leave_count = (int) $pdo->query("SELECT COUNT(*) FROM leave_requests WHERE status IN ('pending','endorsed')")->fetchColumn();
$notifications_count = unreadNotificationCount($pdo, (int) $user['id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ohana Admin - Settings & Profile</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif; }
    </style>
</head>
<body class="bg-[#FAF7F2] text-gray-800 antialiased flex h-screen overflow-hidden">
    <aside class="w-64 bg-[#FAF7F2] border-r border-amber-900/10 flex flex-col justify-between p-6 shrink-0 sticky top-0 h-screen overflow-y-auto">
        <div>
            <a href="admin_dashboard.php" class="flex items-center gap-3 mb-8 group">
                <div class="w-10 h-10 rounded-xl bg-white p-1 border border-amber-900/10 shadow-xs flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                    <img src="assets/images/ohana.png" alt="OHANA Logo" class="w-full h-full object-contain">
                </div>
                <div>
                    <h1 class="font-bold text-gray-900 leading-none text-base">Ohana Admin</h1>
                    <span class="text-[10px] tracking-wider text-gray-500 font-semibold uppercase">ENTERPRISE PORTAL</span>
                </div>
            </a>

            <div class="space-y-1">
                <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider block mb-2 px-3">NAVIGATION</span>
                <a href="admin_dashboard.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-gray-600 hover:bg-black/5 text-sm font-medium transition"><i class="fa-regular fa-square-minus text-base"></i>Dashboard</a>
                <a href="admin_task_management.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-gray-600 hover:bg-black/5 text-sm font-medium transition"><i class="fa-solid fa-list-check text-base"></i>Task Management</a>
                <a href="admin_employee_list.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-gray-600 hover:bg-black/5 text-sm font-medium transition"><i class="fa-regular fa-address-book text-base"></i>Employee Directory</a>
                <a href="admin_leave_approvals.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-gray-600 hover:bg-black/5 text-sm font-medium transition"><i class="fa-regular fa-calendar-check text-base"></i>Leave Approvals</a>
                <a href="admin_settings.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl bg-[#2D5A43] text-white text-sm font-medium shadow-sm"><i class="fa-solid fa-gear text-base"></i>Settings & Profile</a>
            </div>
        </div>

        <div class="space-y-4">
            <a href="admin_logout.php" class="flex items-center gap-3 px-3 py-2 text-gray-600 hover:text-gray-900 text-sm font-medium"><i class="fa-solid fa-arrow-right-from-bracket rotate-180"></i>Log Out</a>
            <div class="bg-amber-900/5 p-3.5 rounded-2xl border border-amber-900/10">
                <div class="flex items-center gap-2 text-xs font-semibold text-gray-700 mb-1"><i class="fa-regular fa-shield-check text-amber-800"></i>SYSTEM HEALTH</div>
                <p class="text-[11px] text-gray-500 leading-tight">All services synchronized with central roster.</p>
            </div>
        </div>
    </aside>

    <main class="flex-1 flex flex-col min-w-0 min-h-0 overflow-hidden">
        <header class="h-20 border-b border-amber-900/10 flex items-center justify-between px-8 bg-[#FAF7F2] shrink-0">
            <div class="relative w-96">
                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                <input type="text" placeholder="Search tasks, staff, records..." class="w-full bg-white/60 border border-gray-200/80 rounded-full pl-10 pr-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#2D5A43]/20 focus:border-[#2D5A43]">
            </div>
            <div class="flex items-center gap-4">
                <button class="w-10 h-10 rounded-xl border border-gray-200 flex items-center justify-center text-gray-600 hover:bg-white/80 relative">
                    <i class="fa-regular fa-bell text-base"></i>
                    <?php if ($notifications_count > 0): ?><span class="w-4 h-4 rounded-full bg-orange-500 text-white text-[9px] font-bold absolute -top-1 -right-1 flex items-center justify-center"><?= (int) $notifications_count ?></span><?php endif; ?>
                </button>
                <div class="flex items-center gap-3 pl-2 border-l border-gray-200">
                    <div class="w-9 h-9 rounded-full bg-[#2D5A43] text-white flex items-center justify-center font-bold text-xs shadow-xs"><?= htmlspecialchars($user['initials']) ?></div>
                    <div class="text-left">
                        <h4 class="text-sm font-bold text-gray-900 leading-none"><?= htmlspecialchars($user['full_name']) ?></h4>
                        <span class="text-xs text-gray-500"><?= htmlspecialchars($user['position'] ?? 'System Administrator') ?></span>
                    </div>
                </div>
            </div>
        </header>

        <div class="p-8 space-y-6 overflow-y-auto flex-1 min-h-0">
            <?php if ($notice): ?>
                <div class="rounded-xl border <?= str_contains($notice, 'success') ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-red-200 bg-red-50 text-red-700' ?> px-4 py-2 text-xs font-medium"><?= htmlspecialchars($notice) ?></div>
            <?php endif; ?>

            <div class="flex items-center justify-between">
                <div>
                    <span class="text-[10px] font-bold text-gray-500 tracking-wider uppercase">ADMINISTRATOR SETTINGS</span>
                    <h2 class="text-2xl font-extrabold text-gray-900">Profile & System Access</h2>
                </div>
                <div class="flex gap-2 text-xs text-gray-500">
                    <span class="bg-white border border-gray-200 rounded-lg px-2 py-1.5"><?= $task_count ?> active tasks</span>
                    <span class="bg-white border border-gray-200 rounded-lg px-2 py-1.5"><?= $leave_count ?> pending leaves</span>
                </div>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-[1.2fr_0.8fr] gap-6">
                <form method="POST" class="bg-white rounded-2xl border border-gray-200/80 p-6 shadow-sm space-y-5">
                    <input type="hidden" name="action" value="save_profile">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-2xl bg-[#2D5A43] text-white flex items-center justify-center font-bold text-lg">
                                <?= htmlspecialchars($user['initials']) ?>
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-900 text-lg"><?= htmlspecialchars($profile['full_name'] ?? $user['full_name']) ?></h3>
                                <p class="text-xs text-gray-500"><?= htmlspecialchars($profile['position'] ?? 'System Administrator') ?> • <?= htmlspecialchars($profile['department'] ?? 'Administration') ?></p>
                            </div>
                        </div>
                        <span class="bg-emerald-100 text-emerald-800 text-[10px] font-bold px-2.5 py-1 rounded-full">Active admin</span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 mb-1">Full name</label>
                            <input name="full_name" value="<?= htmlspecialchars($profile['full_name'] ?? '') ?>" class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:border-[#2D5A43]">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 mb-1">Email address</label>
                            <input type="email" name="email" value="<?= htmlspecialchars($profile['email'] ?? '') ?>" class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:border-[#2D5A43]">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 mb-1">Phone</label>
                            <input name="phone" value="<?= htmlspecialchars($profile['phone'] ?? '') ?>" class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:border-[#2D5A43]">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 mb-1">Position</label>
                            <input name="position" value="<?= htmlspecialchars($profile['position'] ?? '') ?>" class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:border-[#2D5A43]">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-[11px] font-bold text-gray-700 mb-1">Department</label>
                            <input name="department" value="<?= htmlspecialchars($profile['department'] ?? '') ?>" class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:border-[#2D5A43]">
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <a href="admin_dashboard.php" class="px-4 py-2 text-xs font-bold text-gray-700 hover:bg-gray-100 rounded-xl">Back to dashboard</a>
                        <button type="submit" class="px-5 py-2.5 bg-[#2D5A43] hover:bg-[#234734] text-white text-xs font-bold rounded-xl shadow-sm">Save profile</button>
                    </div>
                </form>

                <div class="space-y-6">
                    <div class="bg-white rounded-2xl border border-gray-200/80 p-5 shadow-sm space-y-4">
                        <div class="flex items-center justify-between">
                            <h3 class="font-bold text-sm text-gray-900">System Overview</h3>
                            <span class="bg-gray-100 text-gray-700 text-[10px] font-bold px-2 py-0.5 rounded-full">Live</span>
                        </div>
                        <div class="space-y-3 text-xs text-gray-600">
                            <div class="flex justify-between py-2 border-b border-gray-100"><span>Active staff</span><strong class="text-gray-900"><?= (int) $session_count ?></strong></div>
                            <div class="flex justify-between py-2 border-b border-gray-100"><span>Pending tasks</span><strong class="text-gray-900"><?= (int) $task_count ?></strong></div>
                            <div class="flex justify-between py-2"><span>Pending leave requests</span><strong class="text-gray-900"><?= (int) $leave_count ?></strong></div>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl border border-gray-200/80 p-5 shadow-sm space-y-4">
                        <h3 class="font-bold text-sm text-gray-900">Access Controls</h3>
                        <div class="rounded-xl bg-[#FAF7F2] border border-amber-900/10 p-3 text-xs text-gray-600 space-y-2">
                            <div class="flex items-center gap-2"><i class="fa-solid fa-shield-halved text-emerald-700 text-[11px]"></i> Admin privileges enabled</div>
                            <div class="flex items-center gap-2"><i class="fa-solid fa-user-shield text-amber-700 text-[11px]"></i> Monitoring role active</div>
                            <div class="flex items-center gap-2"><i class="fa-solid fa-sliders text-sky-700 text-[11px]"></i> Department oversight enabled</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
