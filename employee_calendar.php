<?php
require_once __DIR__ . '/config/auth.php';
requireEmployeeLogin();
$user = currentUser('employee');

$unread_count = unreadNotificationCount($pdo, (int) $user['id']);
$success_msg = '';
$error_msg = '';

if (isset($_GET['leave'])) {
    $success_msg = 'Leave request submitted for supervisor endorsement.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'submit_leave') {
    $leave_type = $_POST['leave_type'] ?? 'vacation';
    $allowed_types = ['vacation', 'sick', 'emergency', 'maternity', 'paternity', 'other'];
    if (!in_array($leave_type, $allowed_types, true)) {
        $leave_type = 'vacation';
    }
    $date_from = trim($_POST['date_from'] ?? '');
    $date_to = trim($_POST['date_to'] ?? '');
    $reason = trim($_POST['reason'] ?? '');

    if ($date_from === '' || $date_to === '' || $reason === '') {
        $error_msg = 'Please complete leave type, dates, and reason.';
    } elseif (!strtotime($date_from) || !strtotime($date_to)) {
        $error_msg = 'Please enter valid start and end dates.';
    } elseif ($date_to < $date_from) {
        $error_msg = 'End date cannot be earlier than the start date.';
    } else {
        try {
            $ins = $pdo->prepare(
                'INSERT INTO leave_requests (user_id, leave_type, date_from, date_to, reason, status) VALUES (?, ?, ?, ?, ?, ?)'
            );
            $ins->execute([$user['id'], $leave_type, $date_from, $date_to, $reason, 'pending']);

            $summary = $user['full_name'] . ' requested ' . formatLeaveType($leave_type)
                . ' from ' . date('M j, Y', strtotime($date_from))
                . ' to ' . date('M j, Y', strtotime($date_to));

            notifyUsersByRole(
                $pdo,
                'supervisor',
                'Leave request pending endorsement',
                $summary,
                'request',
                'supervisor_leave_endorsement.php'
            );
            notifyUsersByRole(
                $pdo,
                'admin',
                'New leave request submitted',
                $summary . ' — awaiting supervisor endorsement.',
                'request',
                'admin_leave_approvals.php'
            );
            createNotification(
                $pdo,
                (int) $user['id'],
                'Leave request filed',
                'Your ' . formatLeaveType($leave_type) . ' request is pending supervisor endorsement.',
                'info',
                'employee_calendar.php'
            );

            header('Location: employee_calendar.php?leave=1');
            exit;
        } catch (PDOException $e) {
            error_log('[OHANA LEAVE SUBMIT] ' . $e->getMessage());
            $error_msg = 'Unable to save your leave request. Please try again.';
        }
    }
}

$calendar_tasks_stmt = $pdo->prepare("SELECT id, title, due_date, priority, status FROM tasks WHERE assigned_to = ? AND due_date IS NOT NULL ORDER BY due_date ASC");
$calendar_tasks_stmt->execute([$user['id']]);
$calendar_tasks = $calendar_tasks_stmt->fetchAll(PDO::FETCH_ASSOC);
$attendance_hist_stmt = $pdo->prepare("SELECT date, time_in, time_out FROM attendance WHERE user_id = ? AND date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) ORDER BY date DESC");
$attendance_hist_stmt->execute([$user['id']]);
$attendance_hist = $attendance_hist_stmt->fetchAll(PDO::FETCH_ASSOC);

$leaves_stmt = $pdo->prepare('SELECT * FROM leave_requests WHERE user_id = ? ORDER BY created_at DESC');
$leaves_stmt->execute([$user['id']]);
$my_leaves = $leaves_stmt->fetchAll();
$pending_leaves = array_values(array_filter($my_leaves, fn($r) => in_array($r['status'], ['pending', 'endorsed'], true)));
$approved_ytd = 0;
foreach ($my_leaves as $lr) {
    if ($lr['status'] === 'approved' && date('Y', strtotime($lr['date_from'])) === date('Y')) {
        $approved_ytd += (int) $lr['total_days'];
    }
}

$cal_year = (int) ($_GET['year'] ?? date('Y'));
$cal_month = (int) ($_GET['month'] ?? date('n'));
if ($cal_month < 1) {
    $cal_month = 12;
    $cal_year--;
} elseif ($cal_month > 12) {
    $cal_month = 1;
    $cal_year++;
}
$prev_month = $cal_month - 1;
$prev_year = $cal_year;
if ($prev_month < 1) {
    $prev_month = 12;
    $prev_year--;
}
$next_month = $cal_month + 1;
$next_year = $cal_year;
if ($next_month > 12) {
    $next_month = 1;
    $next_year++;
}
$first_dow = (int) date('w', strtotime(sprintf('%04d-%02d-01', $cal_year, $cal_month)));
$days_in_month = (int) date('t', strtotime(sprintf('%04d-%02d-01', $cal_year, $cal_month)));
$marked_days = [];
foreach ($my_leaves as $lr) {
    $from = new DateTime($lr['date_from']);
    $to = new DateTime($lr['date_to']);
    $to->modify('+1 day');
    foreach (new DatePeriod($from, new DateInterval('P1D'), $to) as $d) {
        if ((int) $d->format('n') === $cal_month && (int) $d->format('Y') === $cal_year) {
            $marked_days[(int) $d->format('j')] = $lr['status'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ohana Employee Portal - Calendar & Leave Management</title>
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
                    <a href="employee_calendar.php" class="flex items-center justify-between px-3 py-2 rounded-xl bg-[#1c482c] text-white font-semibold shadow-sm">
                        <div class="flex items-center gap-3">
                            <i class="fa-regular fa-calendar text-xs"></i> Calendar & Leaves
                        </div>
                        <span class="w-1.5 h-1.5 rounded-full bg-white"></span>
                    </a>
                    <a href="employee_notifications.php" class="flex items-center justify-between px-3 py-2 rounded-xl text-gray-600 hover:bg-gray-100/70 transition-colors">
                        <div class="flex items-center gap-3">
                            <i class="fa-regular fa-bell text-xs"></i> Notifications
                        </div>
                        <?php if ($unread_count > 0): ?>
                        <span class="bg-amber-100 text-amber-800 text-[10px] font-bold px-2 py-0.5 rounded-full"><?= (int) $unread_count ?></span>
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
                    <button class="w-7 h-7 rounded-full bg-[#f9f7f4] flex items-center justify-center text-gray-500 hover:bg-gray-200/60 transition-colors relative">
                        <i class="fa-regular fa-bell text-xs"></i>
                        <span class="absolute top-1 right-1 w-2 h-2 bg-amber-500 rounded-full"></span>
                    </button>
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

            <!-- DASHBOARD CONTAINER -->
            <main class="p-4 md:p-6 space-y-4 max-w-7xl w-full mx-auto">

                <!-- TOP HEADER & ACTIONS -->
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-3">
                    <div>
                        <div class="flex items-center gap-2 text-[11px] text-gray-500 font-medium">
                            <i class="fa-regular fa-calendar text-emerald-700"></i>
                            <span>TIME OFF & ATTENDANCE</span>
                            <span>•</span>
                            <span>Fiscal Year 2025</span>
                        </div>
                        <h1 class="text-xl md:text-2xl font-extrabold text-gray-900 tracking-tight">
                            Calendar & Leave Management
                        </h1>
                    </div>

                    <div class="flex items-center gap-2">
                        <a href="#" class="bg-white hover:bg-gray-50 text-gray-700 border border-gray-200 text-xs font-semibold py-1.5 px-3 rounded-xl flex items-center gap-1.5 shadow-sm transition-all">
                            <i class="fa-solid fa-book text-[10px] text-gray-400"></i> Leave Policy & Rules
                        </a>
                        <a href="#" class="bg-white hover:bg-gray-50 text-gray-700 border border-gray-200 text-xs font-semibold py-1.5 px-3 rounded-xl flex items-center gap-1.5 shadow-sm transition-all">
                            <i class="fa-solid fa-download text-[10px] text-gray-400"></i> Export Summary
                        </a>
                    </div>
                </div>

                <!-- 3 METRIC CARDS -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3.5">
                    <div class="bg-white p-4 rounded-2xl border border-gray-200/60 shadow-sm space-y-2">
                        <div class="flex justify-between items-start">
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">AVAILABLE LEAVE BALANCE</span>
                            <div class="w-6 h-6 rounded-lg bg-emerald-50 text-emerald-700 flex items-center justify-center text-xs">
                                <i class="fa-solid fa-umbrella-beach"></i>
                            </div>
                        </div>
                        <div class="flex items-baseline gap-2">
                            <span class="text-2xl font-extrabold text-gray-900"><?= count($my_leaves) ?></span>
                            <span class="text-xs text-gray-500 font-medium">Filed requests on record</span>
                        </div>
                        <div class="pt-2 border-t border-gray-100 flex items-center justify-between text-[11px] text-gray-600">
                            <div>Approved YTD: <strong class="text-gray-900"><?= (int) $approved_ytd ?> d</strong></div>
                            <div>Pending: <strong class="text-gray-900"><?= count($pending_leaves) ?></strong></div>
                        </div>
                    </div>

                    <div class="bg-white p-4 rounded-2xl border border-gray-200/60 shadow-sm space-y-2">
                        <div class="flex justify-between items-start">
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">PENDING MANAGEMENT REVIEW</span>
                            <div class="w-6 h-6 rounded-lg bg-amber-50 text-amber-700 flex items-center justify-center text-xs">
                                <i class="fa-solid fa-hourglass-half"></i>
                            </div>
                        </div>
                        <div class="flex items-baseline gap-2">
                            <span class="text-2xl font-extrabold text-gray-900"><?= count($pending_leaves) ?></span>
                            <span class="text-[11px] text-amber-700 font-semibold bg-amber-50 px-2 py-0.5 rounded-full">Awaiting review</span>
                        </div>
                        <div class="pt-2 border-t border-gray-100 flex items-center justify-between text-[11px]">
                            <?php if (!empty($pending_leaves)): ?>
                            <?php $latest_pending = $pending_leaves[0]; ?>
                            <span class="text-gray-600 capitalize"><?= htmlspecialchars($latest_pending['leave_type']) ?></span>
                            <span class="font-bold text-gray-900 bg-amber-50 px-2 py-0.5 rounded text-[10px]"><?= htmlspecialchars($latest_pending['date_from']) ?> – <?= htmlspecialchars($latest_pending['date_to']) ?></span>
                            <?php else: ?>
                            <span class="text-gray-500">No requests waiting on management.</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="bg-white p-4 rounded-2xl border border-gray-200/60 shadow-sm space-y-2">
                        <div class="flex justify-between items-start">
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">APPROVED YTD UTILIZATION</span>
                            <div class="w-6 h-6 rounded-lg bg-blue-50 text-blue-700 flex items-center justify-center text-xs">
                                <i class="fa-solid fa-circle-check"></i>
                            </div>
                        </div>
                        <div class="flex items-baseline gap-2">
                            <span class="text-2xl font-extrabold text-gray-900"><?= (int) $approved_ytd ?></span>
                            <span class="text-xs text-gray-500 font-medium">Approved days in <?= date('Y') ?></span>
                        </div>
                        <div class="pt-2 border-t border-gray-100 space-y-1">
                            <div class="flex justify-between text-[11px] text-gray-600">
                                <span>Utilization vs 20-day reference</span>
                                <span class="font-bold text-gray-900"><?= (int) $approved_ytd ?> / 20 Days</span>
                            </div>
                            <div class="w-full bg-gray-100 h-1.5 rounded-full overflow-hidden">
                                <div class="bg-emerald-600 h-full" style="width: <?= min(100, (int) round(($approved_ytd / 20) * 100)) ?>%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- MAIN GRID: CALENDAR & FORM -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-start">
                    
                    <!-- CALENDAR (COMPACT 5 ROWS FIXED) -->
                    <div class="lg:col-span-7 bg-white p-4 rounded-2xl border border-gray-200/60 shadow-sm space-y-3">
                        <div class="flex justify-between items-center">
                            <div class="flex items-center gap-2">
                                <h2 class="font-extrabold text-sm text-gray-900"><?= date('F Y', strtotime(sprintf('%04d-%02d-01', $cal_year, $cal_month))) ?></h2>
                            </div>

                            <div class="flex items-center gap-1.5">
                                <a href="employee_calendar.php?year=<?= $prev_year ?>&month=<?= $prev_month ?>" class="w-7 h-7 rounded-lg bg-[#f9f7f4] border border-gray-200 text-gray-600 flex items-center justify-center hover:bg-gray-100 transition-colors">
                                    <i class="fa-solid fa-chevron-left text-[10px]"></i>
                                </a>
                                <a href="employee_calendar.php" class="px-2.5 py-1 rounded-lg bg-[#f9f7f4] border border-gray-200 text-xs font-bold text-gray-700 hover:bg-gray-100 transition-colors">
                                    Today
                                </a>
                                <a href="employee_calendar.php?year=<?= $next_year ?>&month=<?= $next_month ?>" class="w-7 h-7 rounded-lg bg-[#f9f7f4] border border-gray-200 text-gray-600 flex items-center justify-center hover:bg-gray-100 transition-colors">
                                    <i class="fa-solid fa-chevron-right text-[10px]"></i>
                                </a>
                            </div>
                        </div>

                        <div class="space-y-1">
                            <div class="grid grid-cols-7 text-center text-[10px] font-bold text-gray-400 uppercase tracking-wider pb-1 border-b border-gray-100">
                                <div>Sun</div><div>Mon</div><div>Tue</div><div>Wed</div><div>Thu</div><div>Fri</div><div>Sat</div>
                            </div>

                            <div class="grid grid-cols-7 gap-y-1 text-center text-xs">
                                <?php for ($i = 0; $i < $first_dow; $i++): ?>
                                <div class="text-gray-300 py-1.5">&nbsp;</div>
                                <?php endfor; ?>
                                <?php for ($day = 1; $day <= $days_in_month; $day++):
                                    $is_today = ((int) date('j') === $day && (int) date('n') === $cal_month && (int) date('Y') === $cal_year);
                                    $mark = $marked_days[$day] ?? null;
                                ?>
                                <div class="py-1.5 font-medium <?= $is_today ? 'text-white bg-[#1c482c] rounded-xl' : 'text-gray-800' ?> flex flex-col items-center">
                                    <span><?= $day ?></span>
                                    <?php if ($is_today): ?>
                                    <span class="text-[8px] font-normal leading-none mt-0.5 text-emerald-200">Today</span>
                                    <?php elseif ($mark === 'approved'): ?>
                                    <span class="w-1 h-1 rounded-full bg-emerald-600 mt-0.5"></span>
                                    <?php elseif (in_array($mark, ['pending', 'endorsed'], true)): ?>
                                    <span class="w-1 h-1 rounded-full bg-amber-500 mt-0.5"></span>
                                    <?php endif; ?>
                                </div>
                                <?php endfor; ?>
                            </div>
                        </div>

                        <div class="pt-2.5 border-t border-gray-100 flex flex-wrap items-center justify-between gap-2 text-[11px] text-gray-600 font-medium">
                            <div class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-emerald-600 inline-block"></span> Approved Leave</div>
                            <div class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-amber-500 inline-block"></span> Pending Action</div>
                            <div class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-purple-500 inline-block"></span> Public Holiday</div>
                            <div class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-[#1c482c] inline-block"></span> Today</div>
                        </div>
                    </div>

                    <!-- LEAVE REQUEST FORM -->
                    <div class="lg:col-span-5 bg-white p-4 rounded-2xl border border-gray-200/60 shadow-sm space-y-3">
                        <?php if ($success_msg): ?>
                        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs rounded-xl p-2"><?= htmlspecialchars($success_msg) ?></div>
                        <?php endif; ?>
                        <?php if ($error_msg): ?>
                        <div class="bg-red-50 border border-red-200 text-red-700 text-xs rounded-xl p-2"><?= htmlspecialchars($error_msg) ?></div>
                        <?php endif; ?>
                        <div class="flex justify-between items-start">
                            <div>
                                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">NEW DIRECT SUBMISSION</span>
                                <h2 class="font-extrabold text-sm text-gray-900">File Leave Request</h2>
                            </div>
                            <div class="w-6 h-6 rounded-lg bg-gray-100 text-gray-500 flex items-center justify-center text-xs">
                                <i class="fa-regular fa-file-lines"></i>
                            </div>
                        </div>

                        <p class="text-[11px] text-gray-500 leading-tight">
                            Please ensure submission is completed 48h prior to scheduled dates.
                        </p>

                        <form method="POST" action="employee_calendar.php" class="space-y-2.5 text-xs">
                            <input type="hidden" name="action" value="submit_leave">
                            <div class="space-y-1">
                                <label class="font-bold text-gray-700 block text-[11px]">Leave Category *</label>
                                <div class="relative">
                                    <select name="leave_type" required class="w-full bg-[#f9f7f4] border border-gray-200 rounded-xl px-3 py-1.5 text-xs text-gray-800 appearance-none focus:outline-none focus:border-[#1c482c]">
                                        <option value="vacation">Vacation Leave</option>
                                        <option value="sick">Sick Leave</option>
                                        <option value="emergency">Emergency Leave</option>
                                        <option value="maternity">Maternity Leave</option>
                                        <option value="paternity">Paternity Leave</option>
                                        <option value="other">Other</option>
                                    </select>
                                    <i class="fa-solid fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-[10px] pointer-events-none"></i>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-2">
                                <div class="space-y-1">
                                    <label class="font-bold text-gray-700 block text-[11px]">Start Date *</label>
                                    <input type="date" name="date_from" required value="<?= date('Y-m-d') ?>" class="w-full bg-[#f9f7f4] border border-gray-200 rounded-xl px-3 py-1.5 text-xs text-gray-800 focus:outline-none focus:border-[#1c482c]">
                                </div>
                                <div class="space-y-1">
                                    <label class="font-bold text-gray-700 block text-[11px]">End Date *</label>
                                    <input type="date" name="date_to" required value="<?= date('Y-m-d') ?>" class="w-full bg-[#f9f7f4] border border-gray-200 rounded-xl px-3 py-1.5 text-xs text-gray-800 focus:outline-none focus:border-[#1c482c]">
                                </div>
                            </div>

                            <div class="space-y-1">
                                <label class="font-bold text-gray-700 block text-[11px]">Reason & Operational Purpose *</label>
                                <textarea name="reason" rows="2" required placeholder="Add any operational coverage notes or personal rationale here..." class="w-full bg-[#f9f7f4] border border-gray-200 rounded-xl p-2 text-xs text-gray-800 focus:outline-none focus:border-[#1c482c] resize-none"></textarea>
                            </div>

                            <div class="bg-amber-50/60 border border-amber-200/60 p-2 rounded-xl flex items-start gap-2 text-[11px] text-amber-900">
                                <i class="fa-solid fa-circle-exclamation text-amber-600 mt-0.5 shrink-0"></i>
                                <span>Requests are sent to your supervisor for endorsement, then to admin for final approval.</span>
                            </div>

                            <div class="grid grid-cols-2 gap-2 pt-0.5">
                                <button type="submit" class="bg-[#1c482c] hover:bg-[#153721] text-white font-semibold py-1.5 px-3 rounded-xl text-xs flex items-center justify-center gap-1.5 shadow-sm transition-all">
                                    <i class="fa-solid fa-paper-plane text-[10px]"></i> Submit Application
                                </button>
                                <a href="employee_calendar.php" class="bg-[#f9f7f4] hover:bg-gray-100 text-gray-700 border border-gray-200 font-semibold py-1.5 px-3 rounded-xl text-xs transition-all text-center">
                                    Reset
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- BOTTOM SECTIONS (COMPACT 2-COLUMN GRID) -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5 items-start">
                    
                    <!-- Upcoming Task Deadlines -->
                    <div class="bg-white p-4 rounded-2xl border border-gray-200/60 shadow-sm space-y-3">
                        <div class="flex justify-between items-center">
                            <h3 class="font-bold text-xs text-gray-900 uppercase">Upcoming Task Deadlines</h3>
                            <span class="text-[10px] font-bold text-gray-400 bg-gray-100 px-2 py-0.5 rounded-full"><?= count($calendar_tasks) ?> Tasks</span>
                        </div>

                        <div class="space-y-2">
                            <?php if (empty($calendar_tasks)): ?>
                                <p class="text-xs text-gray-500 italic">No upcoming task deadlines.</p>
                            <?php else: ?>
                                <?php foreach ($calendar_tasks as $task): ?>
                                <div class="bg-[#f9f7f4] p-2.5 rounded-xl border border-gray-200/50 space-y-1 text-xs">
                                    <div class="flex justify-between items-start">
                                        <div class="flex items-center gap-1.5 font-bold text-gray-900">
                                            <i class="fa-solid fa-list-check text-[#1c482c]"></i> <?= htmlspecialchars($task['title']) ?>
                                        </div>
                                        <span class="bg-emerald-100 text-emerald-800 text-[9px] font-bold px-1.5 py-0.5 rounded-full uppercase"><?= htmlspecialchars($task['priority']) ?></span>
                                    </div>
                                    <div class="text-[10px] text-gray-500">Due: <?= date('M d, Y', strtotime($task['due_date'])) ?> • <span class="italic text-gray-700 capitalize"><?= str_replace('_', ' ', $task['status']) ?></span></div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Attendance History -->
                    <div class="bg-white p-4 rounded-2xl border border-gray-200/60 shadow-sm space-y-3">
                        <div class="flex justify-between items-center">
                            <h3 class="font-bold text-xs text-gray-900 uppercase">Attendance History (30 Days)</h3>
                            <span class="text-[10px] font-bold text-gray-400 bg-gray-100 px-2 py-0.5 rounded-full"><?= count($attendance_hist) ?> Records</span>
                        </div>

                        <div class="space-y-2">
                            <?php if (empty($attendance_hist)): ?>
                                <p class="text-xs text-gray-500 italic">No attendance records in the past 30 days.</p>
                            <?php else: ?>
                                <?php foreach ($attendance_hist as $att): ?>
                                <div class="bg-[#f9f7f4] p-2.5 rounded-xl border border-gray-200/50 flex items-center justify-between text-xs">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-full bg-blue-100 text-blue-800 font-bold text-[9px] flex items-center justify-center shrink-0"><i class="fa-solid fa-clock"></i></div>
                                        <div>
                                            <strong class="text-gray-900 block leading-tight text-[11px]"><?= date('M d, Y', strtotime($att['date'])) ?></strong>
                                            <span class="text-[9px] text-gray-400">Time In: <?= htmlspecialchars(formatTimeValue($att['time_in'] ?? null)) ?></span>
                                        </div>
                                    </div>
                                    <?php if ($att['time_out']): ?>
                                    <span class="bg-gray-200 text-gray-700 font-bold px-1.5 py-0.5 rounded text-[9px]">Out: <?= date('h:i A', strtotime($att['time_out'])) ?></span>
                                    <?php else: ?>
                                    <span class="bg-amber-100 text-amber-800 font-bold px-1.5 py-0.5 rounded text-[9px]">No Time Out</span>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>

            </main>
        </div>
    </div>

</body>
</html>