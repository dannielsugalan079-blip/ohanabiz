<?php
require_once __DIR__ . '/config/auth.php';
requireLogin('admin');
if (empty($_SESSION['admin_logged_in']) || empty($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}
$user = currentUser('admin') ?: currentUser();

$active_tasks = (int) $pdo->query("SELECT COUNT(*) FROM tasks WHERE status IN ('pending','in_progress','for_review')")->fetchColumn();
$pending_leaves = (int) $pdo->query("SELECT COUNT(*) FROM leave_requests WHERE status IN ('pending','endorsed')")->fetchColumn();
$pending_requests = (int) $pdo->query("SELECT COUNT(*) FROM service_requests WHERE status = 'pending'")->fetchColumn();
$pending_approvals = $pending_leaves + $pending_requests;
$completed_today = (int) $pdo->query("SELECT COUNT(*) FROM tasks WHERE status = 'completed' AND DATE(COALESCE(completed_at, updated_at)) = CURDATE()")->fetchColumn();
$staff_total = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role IN ('employee','supervisor') AND status = 'active'")->fetchColumn();
$present_today = (int) $pdo->query("SELECT COUNT(DISTINCT user_id) FROM attendance WHERE date = CURDATE() AND status IN ('present','late','half_day','wfh')")->fetchColumn();
$attendance_pct = $staff_total > 0 ? (int) round(($present_today / $staff_total) * 100) : 0;
$on_leave_today = (int) $pdo->query("SELECT COUNT(*) FROM leave_requests WHERE status = 'approved' AND CURDATE() BETWEEN date_from AND date_to")->fetchColumn();
$completed_week = (int) $pdo->query("SELECT COUNT(*) FROM tasks WHERE status = 'completed' AND YEARWEEK(COALESCE(completed_at, updated_at), 1) = YEARWEEK(CURDATE(), 1)")->fetchColumn();

$urgent_tasks = $pdo->query("
    SELECT t.*, u.full_name AS assignee_name
    FROM tasks t
    LEFT JOIN users u ON t.assigned_to = u.id
    WHERE t.status IN ('pending','in_progress','for_review') AND t.priority IN ('high','critical')
    ORDER BY FIELD(t.priority, 'critical','high'), t.due_date IS NULL, t.due_date ASC
    LIMIT 8
")->fetchAll();

$unread_count = unreadNotificationCount($pdo, (int) $user['id']);
$pending_leave_rows = fetchLeaveRequests($pdo, ['pending', 'endorsed']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ohana Admin Enterprise Portal - Dashboard</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        }
    </style>
</head>
<body class="bg-[#FAF7F2] text-gray-800 antialiased flex h-screen overflow-hidden">

    <!-- SIDEBAR NAVIGATION -->
    <aside class="w-64 bg-[#FAF7F2] border-r border-amber-900/10 flex flex-col justify-between p-6 shrink-0 sticky top-0 h-screen overflow-y-auto">
        <div>
            <!-- Logo / Header -->
            <a href="admin_dashboard.php" class="flex items-center gap-3 mb-8 group">
                <div class="w-10 h-10 rounded-xl bg-white p-1 border border-amber-900/10 shadow-xs flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                    <img src="assets/images/ohana.png" alt="OHANA Logo" class="w-full h-full object-contain">
                </div>
                <div>
                    <h1 class="font-bold text-gray-900 leading-none text-base">Ohana Admin</h1>
                    <span class="text-[10px] tracking-wider text-gray-500 font-semibold uppercase">ENTERPRISE PORTAL</span>
                </div>
            </a>

            <!-- Navigation Links -->
            <div class="space-y-1">
                <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider block mb-2 px-3">NAVIGATION</span>

                <a href="admin_dashboard.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl bg-[#2D5A43] text-white text-sm font-medium shadow-sm">
                    <i class="fa-regular fa-square-minus text-base"></i>
                    Dashboard
                </a>

                <a href="admin_task_management.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-gray-600 hover:bg-black/5 text-sm font-medium transition">
                    <i class="fa-solid fa-list-check text-base"></i>
                    Task Management
                </a>

                <a href="admin_employee_list.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-gray-600 hover:bg-black/5 text-sm font-medium transition">
                    <i class="fa-regular fa-address-book text-base"></i>
                    Employee Directory
                </a>

                <a href="admin_leave_approvals.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-gray-600 hover:bg-black/5 text-sm font-medium transition">
                    <i class="fa-regular fa-calendar-check text-base"></i>
                    Leave Approvals
                </a>

                <a href="admin_employee_list.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-gray-600 hover:bg-black/5 text-sm font-medium transition">
                    <i class="fa-solid fa-gear text-base"></i>
                    Settings & Profile
                </a>
            </div>
        </div>

        <!-- Sidebar Footer -->
        <div class="space-y-4">
            <a href="admin_logout.php" class="flex items-center gap-3 px-3 py-2 text-gray-600 hover:text-gray-900 text-sm font-medium">
                <i class="fa-solid fa-arrow-right-from-bracket rotate-180"></i>
                Log Out
            </a>

            <div class="bg-amber-900/5 p-3.5 rounded-2xl border border-amber-900/10">
                <div class="flex items-center space-x-2 text-xs font-semibold text-gray-700">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    <span>Core Nodes Synchronized</span>
                </div>
            </div>
        </div>
    </aside>

    <!-- MAIN CONTENT AREA -->
    <main class="flex-1 flex flex-col min-w-0 min-h-0 overflow-hidden">

        <!-- TOP NAVBAR -->
        <header class="h-20 border-b border-amber-900/10 flex items-center justify-between px-8 bg-[#FAF7F2] shrink-0">
            <!-- Search Bar -->
            <div class="relative w-96">
                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                <input type="text" placeholder="Search tasks, staff, records..." class="w-full bg-white/60 border border-gray-200/80 rounded-full pl-10 pr-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#2D5A43]/20 focus:border-[#2D5A43]">
            </div>

            <!-- Right Actions -->
            <div class="flex items-center gap-4">
                <a href="admin_task_management.php" class="bg-[#B85D1B] hover:bg-[#a04f15] text-white px-5 py-2.5 rounded-xl text-sm font-medium flex items-center gap-2 shadow-sm transition">
                    <i class="fa-solid fa-plus text-xs"></i>
                    Create Task
                </a>

                <button class="w-10 h-10 rounded-xl border border-gray-200 flex items-center justify-center text-gray-600 hover:bg-white/80 relative">
                    <i class="fa-regular fa-bell text-base"></i>
                    <span class="w-2 h-2 rounded-full bg-orange-500 absolute top-2.5 right-2.5"></span>
                </button>

                <!-- User Profile -->
                <div class="flex items-center gap-3 pl-2 border-l border-gray-200">
                    <div class="w-9 h-9 rounded-full bg-[#2D5A43] text-white flex items-center justify-center font-bold text-xs shadow-xs">
                        <?= htmlspecialchars($user['initials']) ?>
                    </div>
                    <div class="text-left">
                        <h4 class="text-sm font-bold text-gray-900 leading-none"><?= htmlspecialchars($user['full_name']) ?></h4>
                        <span class="text-xs text-gray-500"><?= htmlspecialchars($user['position'] ?? 'System Administrator') ?></span>
                    </div>
                </div>
            </div>
        </header>

        <!-- CONTENT BODY -->
        <div class="p-8 space-y-8 overflow-y-auto flex-1 min-h-0">

            <!-- Welcome Header & Filter -->
            <div class="flex items-end justify-between">
                <div>
                    <div class="flex items-center space-x-2 text-[10px] font-bold text-gray-500 tracking-wider uppercase mb-1">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        <span>SYSTEM SYNCHRONIZED &bull; LIVE ROSTER ACTIVE</span>
                    </div>
                    <h2 class="text-2xl font-extrabold text-gray-900">Welcome back, <?= htmlspecialchars($user['full_name']) ?></h2>
                </div>

                <!-- Department Selector -->
                <div class="inline-flex items-center gap-2 bg-white border border-gray-200/80 px-3 py-1.5 rounded-xl text-xs font-medium text-gray-700 shadow-sm cursor-pointer">
                    <span class="text-gray-500">Department:</span>
                    <span class="font-bold text-gray-900">All</span>
                    <i class="fa-solid fa-sliders text-gray-400 text-xs ml-2"></i>
                </div>
            </div>

            <!-- 4 Metrics Cards Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                
                <!-- Metric 1: Active Tasks -->
                <div class="bg-white p-5 rounded-2xl border border-gray-200/80 shadow-sm flex flex-col justify-between">
                    <div class="flex justify-between items-start mb-3">
                        <span class="text-[10px] font-bold text-gray-500 uppercase tracking-wider">TOTAL ACTIVE TASKS</span>
                        <div class="w-7 h-7 bg-[#f7f3ed] rounded-lg flex items-center justify-center text-gray-600 text-xs">
                            <i class="fa-regular fa-square-check"></i>
                        </div>
                    </div>
                    <div>
                        <div class="flex items-baseline space-x-2">
                            <span class="text-3xl font-extrabold text-gray-900"><?= $active_tasks ?></span>
                        </div>
                        <p class="text-[11px] text-gray-500 mt-1">Pending, in progress, and for review</p>
                    </div>
                </div>

                <!-- Metric 2: Pending Approvals -->
                <div class="bg-white p-5 rounded-2xl border border-gray-200/80 shadow-sm flex flex-col justify-between">
                    <div class="flex justify-between items-start mb-3">
                        <span class="text-[10px] font-bold text-gray-500 uppercase tracking-wider">PENDING APPROVALS</span>
                        <div class="w-7 h-7 bg-amber-100 rounded-lg flex items-center justify-center text-amber-700 text-xs">
                            <i class="fa-regular fa-bell"></i>
                        </div>
                    </div>
                    <div>
                        <div class="flex items-baseline space-x-2">
                            <span class="text-3xl font-extrabold text-gray-900"><?= $pending_approvals ?></span>
                            <span class="text-[10px] font-bold text-amber-800 bg-amber-200/70 px-2 py-0.5 rounded-md">
                                Immediate Review
                            </span>
                        </div>
                        <p class="text-[11px] text-gray-500 mt-1"><?= $pending_leaves ?> leave requests &bull; <?= $pending_requests ?> service requests</p>
                    </div>
                </div>

                <!-- Metric 3: Completed Today -->
                <div class="bg-white p-5 rounded-2xl border border-gray-200/80 shadow-sm flex flex-col justify-between">
                    <div class="flex justify-between items-start mb-3">
                        <span class="text-[10px] font-bold text-gray-500 uppercase tracking-wider">COMPLETED TODAY</span>
                        <div class="w-7 h-7 bg-emerald-100 rounded-lg flex items-center justify-center text-emerald-700 text-xs">
                            <i class="fa-solid fa-check"></i>
                        </div>
                    </div>
                    <div>
                        <div class="flex items-baseline space-x-2">
                            <span class="text-3xl font-extrabold text-gray-900"><?= $completed_today ?></span>
                            <span class="text-[10px] font-bold text-emerald-800 bg-emerald-100 px-2 py-0.5 rounded-md">
                                <?= $completed_week ?> this week
                            </span>
                        </div>
                        <p class="text-[11px] text-gray-500 mt-1">Tasks marked completed today</p>
                    </div>
                </div>

                <!-- Metric 4: Staff Attendance -->
                <div class="bg-white p-5 rounded-2xl border border-gray-200/80 shadow-sm flex flex-col justify-between">
                    <div class="flex justify-between items-start mb-3">
                        <span class="text-[10px] font-bold text-gray-500 uppercase tracking-wider">STAFF ATTENDANCE</span>
                        <div class="w-7 h-7 bg-[#f7f3ed] rounded-lg flex items-center justify-center text-gray-600 text-xs">
                            <i class="fa-solid fa-hospital-user"></i>
                        </div>
                    </div>
                    <div>
                        <div class="flex items-baseline space-x-2">
                            <span class="text-3xl font-extrabold text-gray-900"><?= $attendance_pct ?>%</span>
                            <span class="text-[11px] text-gray-500 font-semibold"><?= $present_today ?> / <?= $staff_total ?> active</span>
                        </div>
                        <p class="text-[11px] text-gray-500 mt-1"><?= $on_leave_today ?> staff on approved leave today</p>
                    </div>
                </div>

            </div>

            <!-- Urgent Tasks & Escalations Section -->
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <span class="w-2 h-2 rounded-full bg-red-600"></span>
                        <h3 class="font-extrabold text-gray-900 text-base">Urgent Tasks & Escalations</h3>
                        <span class="bg-red-100 text-red-800 text-[10px] font-bold px-2 py-0.5 rounded-md">High Priority</span>
                    </div>
                    <a href="admin_task_management.php" class="text-xs font-semibold text-gray-500 hover:text-gray-800">View All Tasks (<?= $active_tasks ?>)</a>
                </div>

                <div class="space-y-3">
                    <?php if (empty($urgent_tasks)): ?>
                    <div class="bg-white p-8 rounded-2xl border border-gray-200/60 text-center text-gray-500 text-xs">
                        No high-priority tasks at this time.
                    </div>
                    <?php else: foreach ($urgent_tasks as $task): ?>
                    <div class="bg-[#f7f3ed] p-4 rounded-2xl border border-gray-200/60 flex items-center justify-between shadow-sm">
                        <div class="flex items-start space-x-3.5">
                            <div class="w-8 h-8 bg-amber-100 rounded-xl flex items-center justify-center text-amber-800 text-sm shrink-0 mt-0.5">
                                <i class="fa-regular fa-file-lines"></i>
                            </div>
                            <div>
                                <div class="flex items-center space-x-2 flex-wrap gap-1">
                                    <h4 class="font-bold text-xs text-gray-900"><?= htmlspecialchars($task['title']) ?></h4>
                                    <span class="bg-red-200/80 text-red-800 text-[9px] font-bold px-2 py-0.5 rounded uppercase"><?= htmlspecialchars($task['priority']) ?></span>
                                    <span class="bg-gray-200 text-gray-700 text-[9px] font-bold px-2 py-0.5 rounded"><?= htmlspecialchars(str_replace('_', ' ', $task['status'])) ?></span>
                                </div>
                                <p class="text-xs text-gray-600 mt-1"><?= htmlspecialchars($task['description'] ?: 'No description provided.') ?></p>
                                <div class="flex items-center space-x-2 mt-2 text-[11px] text-gray-500">
                                    <span class="w-4 h-4 rounded-full bg-[#2d5a3f] text-white flex items-center justify-center text-[8px] font-bold"><?= htmlspecialchars(getUserInitials($task['assignee_name'] ?? 'U')) ?></span>
                                    <span class="font-semibold text-gray-700"><?= htmlspecialchars($task['assignee_name'] ?? 'Unassigned') ?></span>
                                    <?php if (!empty($task['due_date'])): ?>
                                    <span>&bull; Due <?= htmlspecialchars(date('M d, Y', strtotime($task['due_date']))) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2 shrink-0 ml-4">
                            <a href="admin_task_management.php" class="px-3.5 py-1.5 text-xs font-bold text-gray-700 bg-gray-200/80 hover:bg-gray-300 rounded-xl transition">Reassign</a>
                            <a href="admin_task_management.php" class="px-4 py-1.5 text-xs font-bold text-white bg-[#2d5a3f] hover:bg-[#234832] rounded-xl shadow-sm transition">Review</a>
                        </div>
                    </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>

            <!-- Quick Actions Section -->
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <i class="fa-solid fa-bolt text-gray-800 text-xs"></i>
                        <h3 class="font-extrabold text-gray-900 text-base">Quick Actions</h3>
                    </div>
                    <span class="text-xs text-gray-400 font-semibold">Operational Workflows</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    
                    <!-- Quick Action 1 -->
                    <a href="#" class="bg-[#f7f3ed] p-4 rounded-2xl border border-gray-200/60 flex items-center justify-between hover:bg-amber-100/30 transition shadow-sm group">
                        <div class="flex items-center space-x-3">
                            <div class="w-9 h-9 bg-[#2d5a3f] text-white rounded-xl flex items-center justify-center text-sm shrink-0">
                                <i class="fa-solid fa-user-plus"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-xs text-gray-900">Assign New Task</h4>
                                <p class="text-[11px] text-gray-500">Dispatch ticket to operational...</p>
                            </div>
                        </div>
                        <i class="fa-solid fa-chevron-right text-xs text-gray-400 group-hover:translate-x-1 transition"></i>
                    </a>

                    <!-- Quick Action 2 -->
                    <a href="admin_employee_list.php" class="bg-[#f7f3ed] p-4 rounded-2xl border border-gray-200/60 flex items-center justify-between hover:bg-amber-100/30 transition shadow-sm group">
                        <div class="flex items-center space-x-3">
                            <div class="w-9 h-9 bg-[#b45309] text-white rounded-xl flex items-center justify-center text-sm shrink-0">
                                <i class="fa-regular fa-file-lines"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-xs text-gray-900">Employee Directory</h4>
                                <p class="text-[11px] text-gray-500">Review staff roster and last login...</p>
                            </div>
                        </div>
                        <i class="fa-solid fa-chevron-right text-xs text-gray-400 group-hover:translate-x-1 transition"></i>
                    </a>

                    <!-- Quick Action 3 -->
                    <a href="#" class="bg-[#f7f3ed] p-4 rounded-2xl border border-gray-200/60 flex items-center justify-between hover:bg-amber-100/30 transition shadow-sm group">
                        <div class="flex items-center space-x-3">
                            <div class="w-9 h-9 bg-[#8b5e3c] text-white rounded-xl flex items-center justify-center text-sm shrink-0">
                                <i class="fa-solid fa-[#8b5e3c] fa-bullhorn"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-xs text-gray-900 leading-tight">Broadcast Announcement</h4>
                                <p class="text-[11px] text-gray-500">Push bulletin to all 30 field...</p>
                            </div>
                        </div>
                        <i class="fa-solid fa-chevron-right text-xs text-gray-400 group-hover:translate-x-1 transition"></i>
                    </a>

                </div>
            </div>

            <!-- Pending Leave Approvals Section -->
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <i class="fa-regular fa-calendar-check text-gray-800 text-xs"></i>
                        <h3 class="font-extrabold text-gray-900 text-base">Pending Leave Approvals</h3>
                        <span class="w-5 h-5 rounded-full bg-amber-200/80 text-amber-900 text-[11px] font-bold flex items-center justify-center">2</span>
                    </div>
                    <a href="#" class="text-xs font-semibold text-gray-500 hover:text-gray-800 flex items-center gap-1">
                        <span>Open Leave Management Console</span>
                        <i class="fa-solid fa-arrow-right text-[10px]"></i>
                    </a>
                </div>
                <p class="text-xs text-gray-400 -mt-2">Time-off requests submitted by team members requiring administrative sanction.</p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-1">
                    
                    <!-- Leave Card 1 -->
                    <div class="bg-[#f7f3ed] p-5 rounded-2xl border border-gray-200/60 space-y-4 shadow-sm">
                        <div class="flex items-start justify-between">
                            <div class="flex items-center space-x-3">
                                <img src="https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=100&auto=format&fit=crop&q=80" class="w-9 h-9 rounded-full object-cover">
                                <div>
                                    <h4 class="font-bold text-xs text-gray-900">Sarah Jenkins</h4>
                                    <p class="text-[11px] text-gray-500 font-medium">Design &bull; Frontend</p>
                                </div>
                            </div>
                            <span class="bg-amber-100 text-amber-900 text-[10px] font-bold px-2.5 py-1 rounded-full">Sick Leave</span>
                        </div>

                        <div class="flex items-center justify-between text-xs text-gray-600 font-medium bg-white/60 p-2.5 rounded-xl border border-gray-200/40">
                            <span class="flex items-center gap-1.5">
                                <i class="fa-regular fa-calendar text-gray-400"></i>
                                Oct 25 - Oct 26, 2024
                            </span>
                            <span class="text-gray-500 font-bold">2 days</span>
                        </div>

                        <p class="text-xs text-gray-500 italic">"Medical checkup and recovery period following dental surgery."</p>

                        <div class="grid grid-cols-2 gap-3 pt-1">
                            <button class="w-full py-2 bg-gray-200/80 hover:bg-gray-300 text-gray-800 text-xs font-bold rounded-xl transition">
                                &times; Reject
                            </button>
                            <button class="w-full py-2 bg-[#2d5a3f] hover:bg-[#234832] text-white text-xs font-bold rounded-xl shadow-sm transition">
                                &check; Approve
                            </button>
                        </div>
                    </div>

                    <!-- Leave Card 2 -->
                    <div class="bg-[#f7f3ed] p-5 rounded-2xl border border-gray-200/60 space-y-4 shadow-sm">
                        <div class="flex items-start justify-between">
                            <div class="flex items-center space-x-3">
                                <img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=100&auto=format&fit=crop&q=80" class="w-9 h-9 rounded-full object-cover">
                                <div>
                                    <h4 class="font-bold text-xs text-gray-900">Marcus Rodriguez</h4>
                                    <p class="text-[11px] text-gray-500 font-medium">Logistics &bull; Dispatch</p>
                                </div>
                            </div>
                            <span class="bg-emerald-100 text-emerald-900 text-[10px] font-bold px-2.5 py-1 rounded-full">Vacation</span>
                        </div>

                        <div class="flex items-center justify-between text-xs text-gray-600 font-medium bg-white/60 p-2.5 rounded-xl border border-gray-200/40">
                            <span class="flex items-center gap-1.5">
                                <i class="fa-regular fa-calendar text-gray-400"></i>
                                Nov 04 - Nov 08, 2024
                            </span>
                            <span class="text-gray-500 font-bold">5 days</span>
                        </div>

                        <p class="text-xs text-gray-500 italic">"Annual family leave. Handover completed with Miriam Al-Mansoor."</p>

                        <div class="grid grid-cols-2 gap-3 pt-1">
                            <button class="w-full py-2 bg-gray-200/80 hover:bg-gray-300 text-gray-800 text-xs font-bold rounded-xl transition">
                                &times; Reject
                            </button>
                            <button class="w-full py-2 bg-[#2d5a3f] hover:bg-[#234832] text-white text-xs font-bold rounded-xl shadow-sm transition">
                                &check; Approve
                            </button>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </main>

</body>
</html>