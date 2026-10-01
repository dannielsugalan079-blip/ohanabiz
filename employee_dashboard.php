<?php
require_once __DIR__ . '/config/auth.php';
requireEmployeeLogin();
$user = currentUser('employee');

$today = date('Y-m-d');
$att_feedback = '';

// Handle Time-In / Time-Out action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['attendance_action'])) {
    $action = $_POST['attendance_action'];

    // Check existing attendance for today
    $check_att = $pdo->prepare("SELECT * FROM attendance WHERE user_id = ? AND date = ? LIMIT 1");
    $check_att->execute([$user['id'], $today]);
    $current_att = $check_att->fetch(PDO::FETCH_ASSOC);

    if ($action === 'time_in') {
        if (!$current_att) {
            $status = (date('H:i:s') > '09:00:00') ? 'late' : 'present';
            $ins_att = $pdo->prepare("INSERT INTO attendance (user_id, date, time_in, status, created_at) VALUES (?, ?, CURTIME(), ?, NOW())");
            $ins_att->execute([$user['id'], $today, $status]);
            $att_feedback = "success:Successfully recorded your TIME-IN at " . date('h:i A') . "!";
        } else {
            $att_feedback = "info:You have already timed in for today.";
        }
    } elseif ($action === 'time_out') {
        if ($current_att && empty($current_att['time_out'])) {
            $upd_att = $pdo->prepare("UPDATE attendance SET time_out = CURTIME() WHERE id = ?");
            $upd_att->execute([$current_att['id']]);
            $att_feedback = "success:Successfully recorded your TIME-OUT at " . date('h:i A') . "!";
        } else {
            $att_feedback = "warning:You have already timed out or have no time-in record.";
        }
    }
}

// Fetch today's attendance record
$att_stmt = $pdo->prepare("SELECT * FROM attendance WHERE user_id = ? AND date = ? LIMIT 1");
$att_stmt->execute([$user['id'], $today]);
$today_att = $att_stmt->fetch(PDO::FETCH_ASSOC);

// Fetch counts and metrics
$tasks_count_stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status IN ('pending', 'in_progress') THEN 1 ELSE 0 END) as active,
        SUM(CASE WHEN priority IN ('high', 'critical') AND status NOT IN ('completed', 'cancelled') THEN 1 ELSE 0 END) as urgent,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
    FROM tasks 
    WHERE assigned_to = ?
");
$tasks_count_stmt->execute([$user['id']]);
$task_stats = $tasks_count_stmt->fetch(PDO::FETCH_ASSOC);

// Fetch days present this month
$month_start = date('Y-m-01');
$att_days_stmt = $pdo->prepare("SELECT COUNT(*) FROM attendance WHERE user_id = ? AND date >= ? AND status IN ('present', 'late', 'half_day')");
$att_days_stmt->execute([$user['id'], $month_start]);
$days_present = (int)$att_days_stmt->fetchColumn();

// Fetch active tasks for dashboard list
$recent_tasks_stmt = $pdo->prepare("
    SELECT t.*, u.full_name as supervisor_name, sr.reference_no, sr.service_name
    FROM tasks t
    JOIN users u ON t.assigned_by = u.id
    LEFT JOIN service_requests sr ON t.request_id = sr.id
    WHERE t.assigned_to = ? AND t.status NOT IN ('completed', 'cancelled')
    ORDER BY FIELD(t.priority, 'critical', 'high', 'normal', 'low'), t.due_date ASC, t.id DESC
    LIMIT 5
");
$recent_tasks_stmt->execute([$user['id']]);
$active_tasks = $recent_tasks_stmt->fetchAll(PDO::FETCH_ASSOC);

// User initials
$initials = '';
$name_parts = explode(' ', trim($user['full_name']));
foreach (array_slice($name_parts, 0, 2) as $p) {
    if (!empty($p)) $initials .= strtoupper($p[0]);
}
if (empty($initials)) $initials = 'EM';
$unread_count = unreadNotificationCount($pdo, (int) $user['id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ohana Specialist - Enterprise Portal</title>
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
                <a href="employee_dashboard.php" class="p-5 border-b border-gray-100 flex items-center gap-2.5 group">
                    <div class="w-8 h-8 rounded-lg bg-white p-0.5 border border-gray-200 shadow-xs flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                        <img src="assets/images/ohana.png" alt="OHANA Logo" class="w-full h-full object-contain">
                    </div>
                    <div>
                        <span class="font-bold text-xs text-gray-900 tracking-tight block">Ohana Specialist</span>
                        <span class="text-[9px] uppercase tracking-wider text-gray-400 font-semibold block">Enterprise Portal</span>
                    </div>
                </a>

                <!-- Specialist Status Badge -->
                <div class="p-4 mx-3 my-3 bg-[#f9f7f4] rounded-xl border border-gray-200/60 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 inline-block"></span>
                        <span class="text-xs font-bold text-gray-900">Specialist Desk</span>
                    </div>
                    <span class="text-[10px] bg-emerald-100 text-emerald-800 font-bold px-2 py-0.5 rounded-full">
                        <?= $today_att ? ($today_att['time_out'] ? 'Off Duty' : 'On Duty') : 'Ready' ?>
                    </span>
                </div>

                <!-- Navigation Header -->
                <div class="px-5 pt-2 pb-2">
                    <span class="text-[10px] uppercase font-bold text-gray-400 tracking-wider">Navigation</span>
                </div>

                <!-- Navigation Links -->
                <nav class="px-3 space-y-1 text-xs font-medium">
                    <a href="employee_dashboard.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl bg-[#1c482c] text-white font-semibold shadow-sm">
                        <i class="fa-solid fa-house text-xs"></i> Dashboard
                    </a>
                    <a href="employee_task_management.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-gray-600 hover:bg-gray-100/70 transition-colors">
                        <i class="fa-solid fa-list-check text-xs"></i> My Tasks & Dispatches
                    </a>
                    <a href="employee_calendar.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-gray-600 hover:bg-gray-100/70 transition-colors">
                        <i class="fa-regular fa-calendar text-xs"></i> Calendar & Leaves
                    </a>
                    <a href="employee_notifications.php" class="flex items-center justify-between px-3 py-2.5 rounded-xl text-gray-600 hover:bg-gray-100/70 transition-colors">
                        <div class="flex items-center gap-3">
                            <i class="fa-regular fa-bell text-xs"></i> Shift Notifications
                        </div>
                        <?php if ($unread_count > 0): ?>
                        <span class="bg-amber-100 text-amber-800 text-[10px] font-bold px-2 py-0.5 rounded-full"><?= (int) $unread_count ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="employee_profile.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-gray-600 hover:bg-gray-100/70 transition-colors">
                        <i class="fa-solid fa-gear text-xs"></i> Profile & Settings
                    </a>
                </nav>
            </div>

            <!-- Sidebar Footer -->
            <div class="p-4 border-t border-gray-100 space-y-3">
                <a href="logout.php?portal=employee" class="flex items-center gap-2 text-xs text-red-600 font-semibold px-3 py-2 hover:bg-red-50 rounded-lg transition-colors">
                    <i class="fa-solid fa-right-from-bracket"></i> Log Out
                </a>
                <div class="flex items-center gap-1.5 text-[10px] text-gray-400 px-3">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 inline-block"></span>
                    <span>Core Nodes Synchronized</span>
                </div>
            </div>
        </aside>

        <!-- MAIN CONTENT WRAPPER -->
        <div class="flex-1 flex flex-col min-w-0">
            
            <!-- TOP HEADER -->
            <header class="bg-white border-b border-gray-200/60 h-16 px-6 flex items-center justify-between sticky top-0 z-30">
                <div class="flex items-center gap-4 flex-1 max-w-md">
                    <div class="relative w-full">
                        <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                        <input type="text" placeholder="Search tasks, directives, files..." class="w-full bg-[#f9f7f4] border border-gray-200 rounded-xl pl-9 pr-4 py-2 text-xs focus:outline-none focus:border-[#1c482c] transition-all">
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <div class="hidden md:flex items-center gap-2 bg-[#f9f7f4] border border-gray-200 px-3 py-1.5 rounded-xl text-xs font-semibold text-gray-700">
                        <i class="fa-regular fa-clock text-gray-400"></i>
                        <span id="liveClock"><?= date('h:i:s A') ?></span>
                    </div>

                    <div class="h-6 w-[1px] bg-gray-200"></div>

                    <div class="flex items-center gap-3">
                        <div class="text-right hidden sm:block">
                            <span class="block text-xs font-bold text-gray-900"><?= htmlspecialchars($user['full_name']) ?></span>
                            <span class="block text-[10px] text-gray-400"><?= htmlspecialchars($user['position'] ?? 'Specialist') ?></span>
                        </div>
                        <div class="w-9 h-9 rounded-full bg-emerald-800 text-white font-bold flex items-center justify-center text-xs shadow-xs">
                            <?= htmlspecialchars($initials) ?>
                        </div>
                    </div>
                </div>
            </header>

            <!-- DASHBOARD CONTAINER -->
            <main class="p-6 md:p-8 space-y-8 max-w-7xl w-full mx-auto">

                <!-- ATTENDANCE FEEDBACK -->
                <?php if (!empty($att_feedback)): 
                    $type = explode(':', $att_feedback)[0];
                    $msg  = explode(':', $att_feedback)[1] ?? $att_feedback;
                    $bg = ($type === 'success') ? 'bg-emerald-50 border-emerald-200 text-emerald-900' : 'bg-amber-50 border-amber-200 text-amber-900';
                ?>
                <div class="p-4 <?= $bg ?> border rounded-2xl flex items-center gap-3 text-xs shadow-xs">
                    <i class="fa-solid fa-circle-check text-base"></i>
                    <span class="font-bold"><?= htmlspecialchars($msg) ?></span>
                </div>
                <?php endif; ?>

                <!-- STATUS BANNER & ATTENDANCE TIME-IN / TIME-OUT CONSOLE -->
                <div class="bg-white p-6 rounded-3xl border border-gray-200/60 shadow-sm space-y-5">
                    
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 pb-4 border-b border-gray-100 text-xs">
                        <div class="flex items-center gap-2 text-gray-500 font-medium">
                            <?php if ($today_att && empty($today_att['time_out'])): ?>
                                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse inline-block"></span>
                                <span class="text-emerald-800 font-bold uppercase tracking-wider">ON-DUTY STATION ACTIVE</span>
                            <?php elseif ($today_att && !empty($today_att['time_out'])): ?>
                                <span class="w-2.5 h-2.5 rounded-full bg-gray-400 inline-block"></span>
                                <span class="text-gray-600 font-bold uppercase tracking-wider">SHIFT COMPLETED</span>
                            <?php else: ?>
                                <span class="w-2.5 h-2.5 rounded-full bg-amber-500 inline-block"></span>
                                <span class="text-amber-800 font-bold uppercase tracking-wider">NOT TIMED IN YET</span>
                            <?php endif; ?>
                            <span>•</span>
                            <span>MALOLOS OPERATIONS</span>
                            <span>•</span>
                            <span class="font-bold text-gray-700"><?= strtoupper(date('l, F d, Y')) ?></span>
                        </div>

                        <div class="bg-[#f9f7f4] border border-gray-200 px-3 py-1.5 rounded-xl text-gray-700 font-medium flex items-center gap-2">
                            <i class="fa-regular fa-clock text-gray-400"></i>
                            <span>Standard Shift: Morning (08:00 - 17:00)</span>
                        </div>
                    </div>

                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                        <div class="space-y-1">
                            <h1 class="text-2xl md:text-3xl font-extrabold text-gray-900 tracking-tight">
                                Good day, <?= htmlspecialchars(explode(' ', trim($user['full_name']))[0]) ?>!
                            </h1>
                            <p class="text-xs text-gray-500 italic max-w-xl">
                                "Precision and diligence turn operational tempo into seamless mastery." You have <?= (int)$task_stats['active'] ?> active tasks in your list.
                            </p>
                        </div>

                        <!-- ATTENDANCE ACTION BUTTON PANEL -->
                        <div class="bg-[#FAF7F2] p-4 rounded-2xl border border-amber-900/10 flex flex-col sm:flex-row items-center gap-4 shrink-0">
                            <div class="text-xs">
                                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Today's Attendance</span>
                                <?php if (!$today_att): ?>
                                    <span class="font-extrabold text-amber-800 text-sm block">Not Timed In</span>
                                    <span class="text-[10px] text-gray-500">Click Time-In to start.</span>
                                <?php elseif (empty($today_att['time_out'])): ?>
                                    <span class="font-extrabold text-emerald-800 text-sm block">In: <?= date('h:i A', strtotime($today_att['time_in'])) ?></span>
                                    <span class="text-[10px] text-emerald-700 font-semibold">Status: <?= ucfirst($today_att['status']) ?></span>
                                <?php else: ?>
                                    <span class="font-extrabold text-gray-800 text-sm block">In: <?= date('h:i A', strtotime($today_att['time_in'])) ?> • Out: <?= date('h:i A', strtotime($today_att['time_out'])) ?></span>
                                    <span class="text-[10px] text-gray-500">Total Rendered: <?= $today_att['total_hours'] ?? '0' ?> hours</span>
                                <?php endif; ?>
                            </div>

                            <div class="flex items-center gap-2">
                                <?php if (!$today_att): ?>
                                    <form method="POST" action="employee_dashboard.php">
                                        <input type="hidden" name="attendance_action" value="time_in">
                                        <button type="submit" class="bg-[#1c482c] hover:bg-[#153721] text-white font-bold text-xs px-5 py-2.5 rounded-xl shadow-sm flex items-center gap-2 transition">
                                            <i class="fa-solid fa-right-to-bracket text-xs"></i> TIME IN
                                        </button>
                                    </form>
                                <?php elseif (empty($today_att['time_out'])): ?>
                                    <form method="POST" action="employee_dashboard.php">
                                        <input type="hidden" name="attendance_action" value="time_out">
                                        <button type="submit" class="bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs px-5 py-2.5 rounded-xl shadow-sm flex items-center gap-2 transition">
                                            <i class="fa-solid fa-right-from-bracket text-xs"></i> TIME OUT
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <div class="bg-emerald-100 text-emerald-800 font-bold text-xs px-4 py-2 rounded-xl flex items-center gap-1.5">
                                        <i class="fa-solid fa-circle-check text-xs"></i> Shift Recorded
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                    </div>

                </div>

                <!-- METRICS GRID (4 CARDS) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <!-- Card 1 -->
                    <div class="bg-white p-5 rounded-3xl border border-gray-200/60 shadow-sm space-y-4">
                        <div class="flex justify-between items-start">
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">TOTAL ACTIVE TASKS</span>
                            <div class="w-7 h-7 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center text-xs">
                                <i class="fa-solid fa-list-check"></i>
                            </div>
                        </div>
                        <div class="flex items-baseline gap-2">
                            <span class="text-4xl font-extrabold text-gray-900"><?= (int)$task_stats['active'] ?></span>
                            <span class="text-xs font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full">Assigned</span>
                        </div>
                        <p class="text-[11px] text-gray-500 pt-2 border-t border-gray-100">Pending & In-Progress Directives</p>
                    </div>

                    <!-- Card 2 -->
                    <div class="bg-white p-5 rounded-3xl border border-gray-200/60 shadow-sm space-y-4">
                        <div class="flex justify-between items-start">
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">URGENT DIRECTIVES</span>
                            <div class="w-7 h-7 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center text-xs">
                                <i class="fa-solid fa-triangle-exclamation"></i>
                            </div>
                        </div>
                        <div class="flex items-baseline gap-2">
                            <span class="text-4xl font-extrabold text-amber-700"><?= (int)$task_stats['urgent'] ?></span>
                            <span class="text-[10px] font-bold text-amber-700 bg-amber-100 px-2 py-0.5 rounded-full">Immediate Action</span>
                        </div>
                        <p class="text-[11px] text-gray-500 pt-2 border-t border-gray-100">High & Critical SLA priorities</p>
                    </div>

                    <!-- Card 3 -->
                    <div class="bg-white p-5 rounded-3xl border border-gray-200/60 shadow-sm space-y-4">
                        <div class="flex justify-between items-start">
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">DAYS PRESENT (THIS MONTH)</span>
                            <div class="w-7 h-7 rounded-xl bg-blue-50 text-blue-700 flex items-center justify-center text-xs">
                                <i class="fa-regular fa-calendar-days"></i>
                            </div>
                        </div>
                        <div class="flex items-baseline gap-2">
                            <span class="text-4xl font-extrabold text-gray-900"><?= $days_present ?></span>
                            <span class="text-xs text-gray-400">days recorded</span>
                        </div>
                        <p class="text-[11px] text-gray-500 pt-2 border-t border-gray-100">Tracked in OHANABIZ Attendance</p>
                    </div>

                    <!-- Card 4 -->
                    <div class="bg-white p-5 rounded-3xl border border-gray-200/60 shadow-sm space-y-4">
                        <div class="flex justify-between items-start">
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">COMPLETED TASKS</span>
                            <div class="w-7 h-7 rounded-xl bg-purple-50 text-purple-700 flex items-center justify-center text-xs">
                                <i class="fa-solid fa-circle-check"></i>
                            </div>
                        </div>
                        <div class="flex items-baseline gap-2">
                            <span class="text-4xl font-extrabold text-emerald-800"><?= (int)$task_stats['completed'] ?></span>
                            <span class="text-xs text-emerald-700 font-bold bg-emerald-50 px-2 py-0.5 rounded-full">Done</span>
                        </div>
                        <p class="text-[11px] text-gray-500 pt-2 border-t border-gray-100">Finished orders & directives</p>
                    </div>
                </div>

                <!-- ASSIGNED TASKS & DIRECTIVES SECTION -->
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-amber-500 inline-block"></span>
                            <h2 class="font-bold text-base text-gray-900">Assigned Tasks & Directives</h2>
                            <span class="bg-[#f9f7f4] border border-gray-200 text-gray-600 text-[10px] font-bold px-2.5 py-0.5 rounded-full">Priority Queue</span>
                        </div>
                        <a href="employee_task_management.php" class="text-xs font-semibold text-[#1c482c] hover:underline">
                            View All Tasks (<?= (int)$task_stats['total'] ?>) →
                        </a>
                    </div>

                    <div class="space-y-4">
                        <?php if (empty($active_tasks)): ?>
                            <div class="bg-white p-8 rounded-3xl border border-gray-200/60 text-center space-y-2">
                                <i class="fa-solid fa-clipboard-check text-2xl text-emerald-700"></i>
                                <h3 class="font-bold text-sm text-gray-900">No active tasks assigned. Check back later.</h3>
                                <p class="text-xs text-gray-500">All tasks are completed or there are no new dispatches from the Supervisor.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($active_tasks as $atask): 
                                $prio_badge = 'bg-gray-100 text-gray-700';
                                if ($atask['priority'] === 'critical') $prio_badge = 'bg-red-50 text-red-700';
                                elseif ($atask['priority'] === 'high') $prio_badge = 'bg-amber-50 text-amber-700';
                                elseif ($atask['priority'] === 'normal') $prio_badge = 'bg-blue-50 text-blue-700';
                            ?>
                            <div class="bg-white p-5 rounded-3xl border border-gray-200/60 shadow-sm flex flex-col md:flex-row justify-between items-start md:items-center gap-4 hover:shadow-md transition-all">
                                <div class="flex items-start gap-4 flex-1">
                                    <div class="w-10 h-10 rounded-2xl bg-emerald-50 text-[#1c482c] flex items-center justify-center text-sm font-bold shrink-0 mt-0.5">
                                        #<?= $atask['id'] ?>
                                    </div>
                                    <div class="space-y-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <h3 class="font-bold text-sm text-gray-900"><?= htmlspecialchars($atask['title']) ?></h3>
                                            <span class="<?= $prio_badge ?> text-[10px] font-bold px-2.5 py-0.5 rounded-full uppercase">
                                                <?= htmlspecialchars($atask['priority']) ?>
                                            </span>
                                            <span class="bg-gray-100 text-gray-700 text-[10px] font-bold px-2 py-0.5 rounded-full capitalize">
                                                <?= str_replace('_', ' ', $atask['status']) ?>
                                            </span>
                                        </div>
                                        <div class="flex items-center gap-3 text-xs text-gray-500">
                                            <?php if (!empty($atask['reference_no'])): ?>
                                                <span class="text-[#1c482c] font-semibold"><?= htmlspecialchars($atask['reference_no']) ?></span>
                                                <span>•</span>
                                            <?php endif; ?>
                                            <span>Due: <?= !empty($atask['due_date']) ? date('M d, Y', strtotime($atask['due_date'])) : 'Open' ?></span>
                                        </div>
                                        <div class="flex items-center gap-2 pt-1 text-xs text-gray-600">
                                            <div class="w-5 h-5 rounded-full bg-emerald-100 text-[#1c482c] font-bold text-[9px] flex items-center justify-center">SV</div>
                                            <span><?= htmlspecialchars($atask['supervisor_name']) ?> (Supervisor)</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2.5 w-full md:w-auto justify-end shrink-0">
                                    <a href="employee_task_management.php" class="bg-[#1c482c] hover:bg-[#153721] text-white text-xs font-semibold py-2 px-4 rounded-xl flex items-center gap-1.5 transition-all">
                                        Manage Task <i class="fa-solid fa-arrow-right text-[9px]"></i>
                                    </a>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- QUICK ACTIONS SECTION -->
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <div class="flex items-center gap-2">
                            <i class="fa-solid fa-bolt text-[#1c482c] text-sm"></i>
                            <h2 class="font-bold text-base text-gray-900">Quick Actions</h2>
                        </div>
                        <span class="text-xs text-gray-400 font-medium">Specialist Workflows</span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Action 1 -->
                        <a href="employee_task_management.php" class="bg-white p-5 rounded-3xl border border-gray-200/60 shadow-sm flex items-center justify-between hover:shadow-md transition-all group">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 rounded-2xl bg-emerald-50 text-[#1c482c] flex items-center justify-center text-sm shrink-0">
                                    <i class="fa-solid fa-list-check"></i>
                                </div>
                                <div>
                                    <h3 class="font-bold text-sm text-gray-900 group-hover:text-[#1c482c] transition-colors">Directives Console</h3>
                                    <p class="text-[11px] text-gray-500 mt-0.5">Manage work orders & tasks...</p>
                                </div>
                            </div>
                            <i class="fa-solid fa-chevron-right text-gray-300 text-xs"></i>
                        </a>

                        <!-- Action 2 -->
                        <a href="employee_calendar.php" class="bg-white p-5 rounded-3xl border border-gray-200/60 shadow-sm flex items-center justify-between hover:shadow-md transition-all group">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 rounded-2xl bg-orange-50 text-orange-700 flex items-center justify-center text-sm shrink-0">
                                    <i class="fa-regular fa-calendar"></i>
                                </div>
                                <div>
                                    <h3 class="font-bold text-sm text-gray-900 group-hover:text-orange-700 transition-colors">Calendar & Shifts</h3>
                                    <p class="text-[11px] text-gray-500 mt-0.5">Check schedule and leaves...</p>
                                </div>
                            </div>
                            <i class="fa-solid fa-chevron-right text-gray-300 text-xs"></i>
                        </a>

                        <!-- Action 3 -->
                        <a href="employee_profile.php" class="bg-white p-5 rounded-3xl border border-gray-200/60 shadow-sm flex items-center justify-between hover:shadow-md transition-all group">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 rounded-2xl bg-blue-50 text-blue-700 flex items-center justify-center text-sm shrink-0">
                                    <i class="fa-solid fa-user-gear"></i>
                                </div>
                                <div>
                                    <h3 class="font-bold text-sm text-gray-900 group-hover:text-blue-700 transition-colors">Specialist Profile</h3>
                                    <p class="text-[11px] text-gray-500 mt-0.5">View station & credentials...</p>
                                </div>
                            </div>
                            <i class="fa-solid fa-chevron-right text-gray-300 text-xs"></i>
                        </a>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <script>
        // Update live clock every second
        function updateClock() {
            const now = new Date();
            const timeStr = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            const clockEl = document.getElementById('liveClock');
            if (clockEl) clockEl.textContent = timeStr;
        }
        setInterval(updateClock, 1000);
    </script>

</body>
</html>