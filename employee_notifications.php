<?php
require_once __DIR__ . '/config/auth.php';
requireEmployeeLogin();
$user = currentUser('employee');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'mark_all_read') {
    markAllNotificationsRead($pdo, (int) $user['id']);
    header('Location: employee_notifications.php');
    exit;
}

$unread_count = unreadNotificationCount($pdo, (int) $user['id']);
$notes = fetchUserNotifications($pdo, (int) $user['id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ohana Employee Portal - Notifications</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background-color: #f8f6f0;
            color: #2d3748;
        }
    </style>
</head>
<body class="bg-[#f8f6f0] text-gray-800 antialiased">

    <div class="flex min-h-screen">
        
        <!-- SIDEBAR -->
        <aside class="w-64 bg-white border-r border-gray-200/70 flex flex-col justify-between shrink-0 sticky top-0 h-screen z-40 hidden lg:flex">
            <div>
                <!-- Top Brand Logo -->
                <a href="employee_dashboard.php" class="p-4 border-b border-gray-100 flex items-center gap-2.5 group">
                    <div class="w-8 h-8 rounded-lg bg-white p-0.5 border border-gray-200 shadow-xs flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                        <img src="assets/images/ohana.png" alt="OHANA Logo" class="w-full h-full object-contain">
                    </div>
                    <div>
                        <span class="font-bold text-xs text-gray-900 tracking-tight block">Ohana Specialist</span>
                        <span class="text-[9px] uppercase tracking-wider text-gray-400 font-semibold block">Enterprise Portal</span>
                    </div>
                </a>

                <div class="p-3 mx-3 my-2.5 bg-[#f9f7f4] rounded-xl border border-gray-200/60 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 inline-block"></span>
                        <span class="text-xs font-bold text-gray-900">Specialist Desk</span>
                    </div>
                    <span class="text-[10px] bg-emerald-100 text-emerald-800 font-bold px-2 py-0.5 rounded-full">Online</span>
                </div>

                <nav class="px-3 space-y-0.5 text-xs font-medium">
                    <a href="employee_dashboard.php" class="flex items-center gap-3 px-3 py-2 rounded-xl text-gray-600 hover:bg-gray-100/70 transition-colors">
                        <i class="fa-solid fa-house text-xs"></i> My Dashboard
                    </a>
                    <a href="employee_task_management.php" class="flex items-center gap-3 px-3 py-2 rounded-xl text-gray-600 hover:bg-gray-100/70 transition-colors">
                        <i class="fa-solid fa-list-check text-xs"></i> My Tasks & Directives
                    </a>
                    <a href="employee_calendar.php" class="flex items-center gap-3 px-3 py-2 rounded-xl text-gray-600 hover:bg-gray-100/70 transition-colors">
                        <i class="fa-regular fa-calendar text-xs"></i> Calendar & Leaves
                    </a>
                    <!-- Active Notifications Page -->
                    <a href="employee_notifications.php" class="flex items-center justify-between px-3 py-2 rounded-xl bg-[#1c482c] text-white font-semibold shadow-sm">
                        <div class="flex items-center gap-3">
                            <i class="fa-regular fa-bell text-xs"></i> Notifications
                        </div>
                        <?php if ($unread_count > 0): ?>
                        <span class="bg-amber-100 text-amber-900 text-[10px] font-bold px-2 py-0.5 rounded-full"><?= (int) $unread_count ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="employee_profile.php" class="flex items-center gap-3 px-3 py-2 rounded-xl text-gray-600 hover:bg-gray-100/70 transition-colors">
                        <i class="fa-solid fa-gear text-xs"></i> Profile & Settings
                    </a>
                </nav>
            </div>

            <div class="p-3 border-t border-gray-100">
                <a href="logout.php?portal=employee" class="flex items-center gap-2 text-xs text-red-600 font-semibold px-3 py-1.5 hover:bg-red-50 rounded-lg transition-colors">
                    <i class="fa-solid fa-right-from-bracket"></i> Log Out
                </a>
            </div>
        </aside>

        <!-- MAIN CONTENT WRAPPER -->
        <div class="flex-1 flex flex-col min-w-0">
            
            <!-- TOP HEADER -->
            <header class="bg-white border-b border-gray-200/60 h-14 px-6 flex items-center justify-between sticky top-0 z-30">
                <div class="flex items-center gap-4 flex-1 max-w-md">
                    <div class="relative w-full">
                        <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                        <input type="text" placeholder="Search tasks, operational directives, policies..." class="w-full bg-[#f9f7f4] border border-gray-200 rounded-xl pl-9 pr-4 py-1.5 text-xs focus:outline-none focus:border-[#1c482c] transition-all">
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <a href="employee_notifications.php" class="w-7 h-7 rounded-full bg-[#f9f7f4] flex items-center justify-center text-gray-500 hover:bg-gray-200/60 transition-colors relative">
                        <i class="fa-regular fa-bell text-xs"></i>
                        <?php if ($unread_count > 0): ?>
                        <span class="absolute top-1 right-1 w-2 h-2 bg-amber-500 rounded-full"></span>
                        <?php endif; ?>
                    </a>
                    <button class="w-7 h-7 rounded-full bg-[#f9f7f4] flex items-center justify-center text-gray-500 hover:bg-gray-200/60 transition-colors">
                        <i class="fa-regular fa-circle-question text-xs"></i>
                    </button>
                    <div class="h-5 w-[1px] bg-gray-200"></div>
                    <div class="flex items-center gap-2.5">
                        <div class="w-7 h-7 rounded-full bg-emerald-800 text-white font-bold flex items-center justify-center text-xs">
                            <?= htmlspecialchars($user['initials']) ?>
                        </div>
                        <div class="text-left hidden sm:block">
                            <span class="block text-xs font-bold text-gray-900 leading-tight"><?= htmlspecialchars($user['full_name']) ?></span>
                            <span class="block text-[10px] text-gray-400"><?= htmlspecialchars($user['position'] ?? 'Specialist') ?></span>
                        </div>
                    </div>
                </div>
            </header>

            <!-- NOTIFICATIONS CONTAINER -->
            <main class="p-4 md:p-6 space-y-4 max-w-5xl w-full mx-auto">

                <!-- HEADER & ACTIONS -->
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-3 bg-white p-5 rounded-2xl border border-gray-200/60 shadow-sm">
                    <div>
                        <div class="flex items-center gap-2 text-[11px] text-gray-500 font-medium">
                            <i class="fa-regular fa-bell text-amber-600"></i>
                            <span>SYSTEM ALERTS & UPDATES</span>
                            <span>•</span>
                            <span><?= (int) $unread_count ?> Unread Items</span>
                        </div>
                        <h1 class="text-xl md:text-2xl font-extrabold text-gray-900 tracking-tight">
                            Notifications Center
                        </h1>
                    </div>

                    <div class="flex items-center gap-2">
                        <form method="POST">
                            <input type="hidden" name="action" value="mark_all_read">
                            <button type="submit" class="bg-[#f9f7f4] hover:bg-gray-100 text-gray-700 border border-gray-200 text-xs font-semibold py-1.5 px-3 rounded-xl flex items-center gap-1.5 transition-all">
                                <i class="fa-solid fa-check-double text-emerald-700 text-[10px]"></i> Mark all as read
                            </button>
                        </form>
                        <button class="bg-[#f9f7f4] hover:bg-gray-100 text-gray-700 border border-gray-200 text-xs font-semibold py-1.5 px-3 rounded-xl flex items-center gap-1.5 transition-all">
                            <i class="fa-solid fa-sliders text-gray-400 text-[10px]"></i> Filter Settings
                        </button>
                    </div>
                </div>

                <!-- NOTIFICATIONS LIST -->
                <div class="space-y-3">
                    <?php if (!$notes): ?>
                    <div class="text-center py-16 text-gray-400">
                      <i class="fa-regular fa-bell text-4xl mb-3 block"></i>
                      <h3 class="text-sm font-semibold text-gray-600 mb-1">No Notifications</h3>
                      <p class="text-xs">You are all caught up. New notifications will appear here when leave requests or tasks are updated.</p>
                    </div>
                    <?php else: ?>
                    <?php foreach ($notes as $n): ?>
                    <a href="<?= htmlspecialchars($n['link'] ?: 'employee_notifications.php') ?>" class="block bg-white p-4 rounded-2xl border <?= empty($n['is_read']) ? 'border-amber-200' : 'border-gray-200/60' ?> shadow-sm">
                      <div class="flex items-start justify-between gap-3">
                        <div>
                          <h3 class="text-sm font-bold text-gray-900"><?= htmlspecialchars($n['title']) ?></h3>
                          <p class="text-xs text-gray-500 mt-1"><?= htmlspecialchars($n['message']) ?></p>
                        </div>
                        <span class="text-[10px] text-gray-400 whitespace-nowrap"><?= date('M j, g:i A', strtotime($n['created_at'])) ?></span>
                      </div>
                    </a>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>

            </main>
        </div>
    </div>

</body>
</html>