<?php
require_once __DIR__ . '/config/auth.php';
requireEmployeeLogin();
$user = currentUser('employee');

// Handle task status update via POST
$status_feedback = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_task_status') {
    $task_id = (int)($_POST['task_id'] ?? 0);
    $new_status = $_POST['status'] ?? '';
    $allowed_statuses = ['pending', 'in_progress', 'for_review', 'completed', 'cancelled'];
    
    if ($task_id > 0 && in_array($new_status, $allowed_statuses)) {
        $completed_clause = ($new_status === 'completed') ? ", completed_at = NOW()" : ", completed_at = NULL";
        $update_stmt = $pdo->prepare("UPDATE tasks SET status = ?, updated_at = NOW() {$completed_clause} WHERE id = ? AND assigned_to = ?");
        $update_stmt->execute([$new_status, $task_id, $user['id']]);
        
        // Also if task is completed or in_progress and linked to service request, update service request status if needed
        $sr_task_stmt = $pdo->prepare("SELECT request_id FROM tasks WHERE id = ?");
        $sr_task_stmt->execute([$task_id]);
        $linked_req_id = $sr_task_stmt->fetchColumn();
        if ($linked_req_id) {
            if ($new_status === 'completed') {
                $pdo->prepare("UPDATE service_requests SET status = 'completed', completed_at = NOW() WHERE id = ?")->execute([$linked_req_id]);
            } elseif ($new_status === 'in_progress') {
                $pdo->prepare("UPDATE service_requests SET status = 'in_progress' WHERE id = ?")->execute([$linked_req_id]);
            }
        }
        
        $status_feedback = "Task status updated to: " . ucfirst(str_replace('_', ' ', $new_status));
    }
}

// Current filter tab
$tab = $_GET['tab'] ?? 'all';

// Query counts
$counts_stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
        SUM(CASE WHEN status = 'for_review' THEN 1 ELSE 0 END) as for_review,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending
    FROM tasks
    WHERE assigned_to = ?
");
$counts_stmt->execute([$user['id']]);
$task_counts = $counts_stmt->fetch(PDO::FETCH_ASSOC);

// Fetch tasks
$sql = "
    SELECT t.*, u.full_name as supervisor_name, sr.reference_no, sr.service_name, c.company_name as client_company
    FROM tasks t
    JOIN users u ON t.assigned_by = u.id
    LEFT JOIN service_requests sr ON t.request_id = sr.id
    LEFT JOIN users client_u ON sr.client_id = client_u.id
    LEFT JOIN clients c ON client_u.id = c.user_id
    WHERE t.assigned_to = ?
";
$params = [$user['id']];

if ($tab === 'in_progress') {
    $sql .= " AND t.status = 'in_progress'";
} elseif ($tab === 'for_review') {
    $sql .= " AND t.status = 'for_review'";
} elseif ($tab === 'completed') {
    $sql .= " AND t.status = 'completed'";
} elseif ($tab === 'pending') {
    $sql .= " AND t.status = 'pending'";
}

$sql .= " ORDER BY FIELD(t.priority, 'critical', 'high', 'normal', 'low'), t.due_date ASC, t.id DESC";

$tasks_stmt = $pdo->prepare($sql);
$tasks_stmt->execute($params);
$my_tasks = $tasks_stmt->fetchAll(PDO::FETCH_ASSOC);

// User initials
$initials = '';
$name_parts = explode(' ', trim($user['full_name']));
foreach (array_slice($name_parts, 0, 2) as $p) {
    if (!empty($p)) $initials .= strtoupper($p[0]);
}
if (empty($initials)) $initials = 'EM';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ohana Employee Portal - My Tasks & Directives</title>
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
                    <span class="text-[10px] bg-emerald-100 text-emerald-800 font-bold px-2 py-0.5 rounded-full">Online</span>
                </div>

                <!-- Navigation Links -->
                <nav class="px-3 space-y-1 text-xs font-medium">
                    <a href="employee_dashboard.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-gray-600 hover:bg-gray-100/70 transition-colors">
                        <i class="fa-solid fa-house text-xs"></i> My Dashboard
                    </a>
                    <a href="employee_task_management.php" class="flex items-center justify-between px-3 py-2.5 rounded-xl bg-[#1c482c] text-white font-semibold shadow-sm">
                        <div class="flex items-center gap-3">
                            <i class="fa-solid fa-list-check text-xs"></i> My Tasks & Directives
                        </div>
                        <span class="w-1.5 h-1.5 rounded-full bg-white"></span>
                    </a>
                    <a href="employee_calendar.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-gray-600 hover:bg-gray-100/70 transition-colors">
                        <i class="fa-regular fa-calendar text-xs"></i> Calendar & Leaves
                    </a>
                    <a href="employee_notifications.php" class="flex items-center justify-between px-3 py-2.5 rounded-xl text-gray-600 hover:bg-gray-100/70 transition-colors">
                        <div class="flex items-center gap-3">
                            <i class="fa-regular fa-bell text-xs"></i> Notifications
                        </div>
                        <span class="bg-amber-100 text-amber-800 text-[10px] font-bold px-2 py-0.5 rounded-full">3</span>
                    </a>
                    <a href="employee_profile.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-gray-600 hover:bg-gray-100/70 transition-colors">
                        <i class="fa-solid fa-gear text-xs"></i> Profile & Settings
                    </a>
                </nav>
            </div>

            <!-- Sidebar Footer -->
            <div class="p-4 border-t border-gray-100">
                <a href="logout.php?portal=employee" class="flex items-center gap-2 text-xs text-red-600 font-semibold px-3 py-2 hover:bg-red-50 rounded-lg transition-colors">
                    <i class="fa-solid fa-right-from-bracket"></i> Log Out
                </a>
            </div>
        </aside>

        <!-- MAIN CONTENT WRAPPER -->
        <div class="flex-1 flex flex-col min-w-0">
            
            <!-- TOP HEADER -->
            <header class="bg-white border-b border-gray-200/60 h-16 px-6 flex items-center justify-between sticky top-0 z-30">
                <div class="flex items-center gap-4 flex-1 max-w-md">
                    <div class="relative w-full">
                        <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                        <input type="text" placeholder="Search directives, client orders, sub-tasks..." class="w-full bg-[#f9f7f4] border border-gray-200 rounded-xl pl-9 pr-4 py-2 text-xs focus:outline-none focus:border-[#1c482c] transition-all">
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <div class="hidden md:flex items-center gap-2 bg-[#f9f7f4] border border-gray-200 px-3 py-1.5 rounded-xl text-xs font-semibold text-gray-700">
                        <i class="fa-regular fa-calendar text-gray-400"></i>
                        <span><?= date('D, M d, Y') ?></span>
                    </div>

                    <div class="h-6 w-[1px] bg-gray-200"></div>

                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-emerald-800 text-white font-bold flex items-center justify-center text-xs shadow-xs">
                            <?= htmlspecialchars($initials) ?>
                        </div>
                        <div class="text-left hidden sm:block">
                            <span class="block text-xs font-bold text-gray-900"><?= htmlspecialchars($user['full_name']) ?></span>
                            <span class="block text-[10px] text-gray-400"><?= htmlspecialchars($user['position'] ?? 'Documentation Specialist') ?></span>
                        </div>
                    </div>
                </div>
            </header>

            <!-- DASHBOARD CONTAINER -->
            <main class="p-6 md:p-8 space-y-8 max-w-7xl w-full mx-auto">

                <!-- FEEDBACK ALERT -->
                <?php if (!empty($status_feedback)): ?>
                <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-2xl flex items-center gap-3 text-xs text-emerald-900 shadow-xs">
                    <i class="fa-solid fa-circle-check text-emerald-600 text-base"></i>
                    <span class="font-bold"><?= htmlspecialchars($status_feedback) ?></span>
                </div>
                <?php endif; ?>

                <!-- WORKSPACE BANNER & HEADER -->
                <div class="space-y-4">
                    <div class="flex items-center gap-2 text-xs">
                        <span class="bg-emerald-100 text-emerald-800 font-bold px-2.5 py-1 rounded-lg">SPECIALIST WORKSPACE</span>
                        <span class="text-gray-300">/</span>
                        <span class="bg-gray-200 text-gray-700 font-semibold px-2.5 py-1 rounded-lg"><?= htmlspecialchars($user['department'] ?? 'Operations') ?></span>
                    </div>

                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                        <div class="space-y-1">
                            <h1 class="text-2xl md:text-3xl font-extrabold text-gray-900 tracking-tight">
                                My Tasks & Directives
                            </h1>
                            <p class="text-xs text-gray-500 max-w-xl">
                                Manage, track, and execute assigned client service directives and operational tasks from supervisors.
                            </p>
                        </div>

                        <div class="flex items-center gap-2.5">
                            <a href="employee_dashboard.php" class="bg-[#1c482c] hover:bg-[#153721] text-white text-xs font-semibold py-2.5 px-4 rounded-xl shadow-sm flex items-center gap-2 transition-all">
                                <i class="fa-solid fa-gauge text-[10px]"></i> View Employee Dashboard
                            </a>
                        </div>
                    </div>
                </div>

                <!-- METRICS GRID (4 CARDS) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <!-- Card 1 -->
                    <div class="bg-white p-5 rounded-3xl border border-gray-200/60 shadow-sm space-y-4">
                        <div class="flex justify-between items-start">
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">TOTAL ASSIGNED</span>
                            <div class="w-7 h-7 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center text-xs">
                                <i class="fa-solid fa-list-check"></i>
                            </div>
                        </div>
                        <div class="flex items-baseline gap-2">
                            <span class="text-4xl font-extrabold text-gray-900"><?= (int)$task_counts['total'] ?></span>
                            <span class="text-xs font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full">All Tasks</span>
                        </div>
                        <p class="text-[11px] text-gray-500 pt-2 border-t border-gray-100">Across all operational queues</p>
                    </div>

                    <!-- Card 2 -->
                    <div class="bg-white p-5 rounded-3xl border border-gray-200/60 shadow-sm space-y-4">
                        <div class="flex justify-between items-start">
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">IN PROGRESS</span>
                            <div class="w-7 h-7 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center text-xs">
                                <i class="fa-solid fa-clock-rotate-left"></i>
                            </div>
                        </div>
                        <div class="flex items-baseline gap-2">
                            <span class="text-4xl font-extrabold text-amber-700"><?= (int)$task_counts['in_progress'] ?></span>
                            <span class="text-xs font-bold text-amber-800 bg-amber-50 px-2 py-0.5 rounded-full">Active</span>
                        </div>
                        <p class="text-[11px] text-gray-500 pt-2 border-t border-gray-100">Currently being handled</p>
                    </div>

                    <!-- Card 3 -->
                    <div class="bg-white p-5 rounded-3xl border border-gray-200/60 shadow-sm space-y-4">
                        <div class="flex justify-between items-start">
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">FOR REVIEW</span>
                            <div class="w-7 h-7 rounded-xl bg-blue-50 text-blue-700 flex items-center justify-center text-xs">
                                <i class="fa-solid fa-magnifying-glass"></i>
                            </div>
                        </div>
                        <div class="flex items-baseline gap-2">
                            <span class="text-4xl font-extrabold text-blue-700"><?= (int)$task_counts['for_review'] ?></span>
                            <span class="text-xs font-bold text-blue-800 bg-blue-50 px-2 py-0.5 rounded-full">Submitted</span>
                        </div>
                        <p class="text-[11px] text-gray-500 pt-2 border-t border-gray-100">Awaiting supervisor endorsement</p>
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
                            <span class="text-4xl font-extrabold text-emerald-800"><?= (int)$task_counts['completed'] ?></span>
                            <span class="text-xs font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full">Finished</span>
                        </div>
                        <p class="text-[11px] text-gray-500 pt-2 border-t border-gray-100">Successfully closed directives</p>
                    </div>
                </div>

                <!-- FILTER TABS & MAIN CONTENT GRID -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                    
                    <!-- LEFT COLUMN: TASKS TABLE & LIST -->
                    <div class="lg:col-span-8 space-y-6">
                        
                        <!-- Tabs & Filter Bar -->
                        <div class="flex flex-wrap items-center justify-between gap-4 bg-white p-3 rounded-2xl border border-gray-200/60 shadow-sm text-xs">
                            <div class="flex items-center gap-1 font-semibold overflow-x-auto">
                                <a href="employee_task_management.php?tab=all" class="px-3 py-1.5 rounded-xl <?= $tab === 'all' ? 'bg-[#1c482c] text-white' : 'text-gray-600 hover:bg-gray-100' ?>">
                                    All (<?= (int)$task_counts['total'] ?>)
                                </a>
                                <a href="employee_task_management.php?tab=pending" class="px-3 py-1.5 rounded-xl <?= $tab === 'pending' ? 'bg-[#1c482c] text-white' : 'text-gray-600 hover:bg-gray-100' ?>">
                                    Pending (<?= (int)$task_counts['pending'] ?>)
                                </a>
                                <a href="employee_task_management.php?tab=in_progress" class="px-3 py-1.5 rounded-xl <?= $tab === 'in_progress' ? 'bg-[#1c482c] text-white' : 'text-gray-600 hover:bg-gray-100' ?>">
                                    In Progress (<?= (int)$task_counts['in_progress'] ?>)
                                </a>
                                <a href="employee_task_management.php?tab=for_review" class="px-3 py-1.5 rounded-xl <?= $tab === 'for_review' ? 'bg-[#1c482c] text-white' : 'text-gray-600 hover:bg-gray-100' ?>">
                                    Pending Review (<?= (int)$task_counts['for_review'] ?>)
                                </a>
                                <a href="employee_task_management.php?tab=completed" class="px-3 py-1.5 rounded-xl <?= $tab === 'completed' ? 'bg-[#1c482c] text-white' : 'text-gray-600 hover:bg-gray-100' ?>">
                                    Completed (<?= (int)$task_counts['completed'] ?>)
                                </a>
                            </div>
                        </div>

                        <!-- TASKS LIST CONTAINER -->
                        <div class="space-y-4">
                            
                            <?php if (empty($my_tasks)): ?>
                                <div class="bg-white p-12 rounded-3xl border border-gray-200/60 text-center space-y-3">
                                    <div class="w-12 h-12 rounded-full bg-emerald-50 text-[#1c482c] flex items-center justify-center mx-auto text-xl">
                                        <i class="fa-solid fa-list-check"></i>
                                    </div>
                                    <h3 class="font-bold text-gray-900 text-sm">No Assigned Tasks</h3>
                                    <p class="text-xs text-gray-500 max-w-sm mx-auto">
                                        There are no tasks for the selected category yet. Tasks assigned by the Supervisor from the Malolos Hub will automatically appear here.
                                    </p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($my_tasks as $task): 
                                    // Status styling
                                    $status_badge = '';
                                    if ($task['status'] === 'completed') {
                                        $status_badge = '<span class="bg-emerald-100 text-emerald-800 text-[10px] font-bold px-2 py-0.5 rounded-full">Completed</span>';
                                    } elseif ($task['status'] === 'for_review') {
                                        $status_badge = '<span class="bg-blue-100 text-blue-800 text-[10px] font-bold px-2 py-0.5 rounded-full">For Review</span>';
                                    } elseif ($task['status'] === 'in_progress') {
                                        $status_badge = '<span class="bg-amber-100 text-amber-800 text-[10px] font-bold px-2 py-0.5 rounded-full">In Progress</span>';
                                    } else {
                                        $status_badge = '<span class="bg-gray-100 text-gray-700 text-[10px] font-bold px-2 py-0.5 rounded-full">Pending</span>';
                                    }

                                    // Priority styling
                                    $prio_color = 'bg-gray-100 text-gray-700';
                                    if ($task['priority'] === 'critical') $prio_color = 'bg-red-100 text-red-800';
                                    elseif ($task['priority'] === 'high') $prio_color = 'bg-orange-100 text-orange-800';
                                    elseif ($task['priority'] === 'normal') $prio_color = 'bg-blue-50 text-blue-700';
                                ?>
                                <div class="bg-white p-5 rounded-3xl border border-gray-200/60 shadow-sm space-y-4 hover:shadow-md transition">
                                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                                        <div class="space-y-1.5 flex-1">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="w-8 h-8 rounded-xl bg-emerald-50 text-[#1c482c] flex items-center justify-center text-xs shrink-0 font-bold">
                                                    #<?= $task['id'] ?>
                                                </span>
                                                <h3 class="font-bold text-sm text-gray-900"><?= htmlspecialchars($task['title']) ?></h3>
                                                <span class="<?= $prio_color ?> text-[10px] font-bold px-2 py-0.5 rounded-full uppercase">
                                                    <?= htmlspecialchars($task['priority']) ?>
                                                </span>
                                                <?= $status_badge ?>
                                            </div>
                                            <p class="text-xs text-gray-500 pl-10">
                                                <?php if (!empty($task['reference_no'])): ?>
                                                    Order: <strong class="text-gray-800"><?= htmlspecialchars($task['reference_no']) ?></strong> (<?= htmlspecialchars($task['service_name'] ?? 'Service') ?>) •
                                                <?php endif; ?>
                                                Due: <span class="text-gray-700 font-semibold"><?= !empty($task['due_date']) ? date('M d, Y', strtotime($task['due_date'])) : 'No due date' ?></span>
                                            </p>
                                        </div>

                                        <div class="flex items-center gap-3 w-full md:w-auto justify-between md:justify-end pl-10 md:pl-0">
                                            <div class="flex items-center gap-2 text-xs">
                                                <div class="w-6 h-6 rounded-full bg-emerald-100 text-[#1c482c] font-bold text-[10px] flex items-center justify-center">
                                                    SV
                                                </div>
                                                <span class="text-gray-700 font-medium"><?= htmlspecialchars($task['supervisor_name']) ?></span>
                                            </div>
                                        </div>
                                    </div>

                                    <?php if (!empty($task['description'])): ?>
                                    <div class="bg-[#f9f7f4] p-3 rounded-xl text-xs text-gray-700 leading-relaxed border border-gray-200/50">
                                        <strong class="text-[10px] uppercase tracking-wider text-gray-400 block mb-1">Directives:</strong>
                                        <?= nl2br(htmlspecialchars($task['description'])) ?>
                                    </div>
                                    <?php endif; ?>

                                    <!-- TASK ACTIONS BAR -->
                                    <div class="pt-3 border-t border-gray-100 flex flex-wrap items-center justify-between gap-3">
                                        <div class="text-[11px] text-gray-400">
                                            Created: <?= date('M d, Y h:i A', strtotime($task['created_at'])) ?>
                                        </div>

                                        <div class="flex items-center gap-2">
                                            <?php if ($task['status'] === 'pending'): ?>
                                                <form method="POST" action="employee_task_management.php">
                                                    <input type="hidden" name="action" value="update_task_status">
                                                    <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
                                                    <input type="hidden" name="status" value="in_progress">
                                                    <button type="submit" class="bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs px-3.5 py-1.5 rounded-xl shadow-xs transition">
                                                        <i class="fa-solid fa-play text-[10px] mr-1"></i> Start Task
                                                    </button>
                                                </form>
                                            <?php elseif ($task['status'] === 'in_progress'): ?>
                                                <form method="POST" action="employee_task_management.php">
                                                    <input type="hidden" name="action" value="update_task_status">
                                                    <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
                                                    <input type="hidden" name="status" value="for_review">
                                                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs px-3.5 py-1.5 rounded-xl shadow-xs transition">
                                                        <i class="fa-solid fa-paper-plane text-[10px] mr-1"></i> Submit for Review
                                                    </button>
                                                </form>
                                                <form method="POST" action="employee_task_management.php">
                                                    <input type="hidden" name="action" value="update_task_status">
                                                    <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
                                                    <input type="hidden" name="status" value="completed">
                                                    <button type="submit" class="bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-xs px-3.5 py-1.5 rounded-xl shadow-xs transition">
                                                        <i class="fa-solid fa-check text-[10px] mr-1"></i> Mark as Completed
                                                    </button>
                                                </form>
                                            <?php elseif ($task['status'] === 'for_review'): ?>
                                                <span class="text-xs text-blue-700 font-semibold flex items-center gap-1.5 bg-blue-50 px-3 py-1 rounded-xl">
                                                    <i class="fa-solid fa-spinner animate-spin text-[10px]"></i> Awaiting Supervisor Review
                                                </span>
                                            <?php elseif ($task['status'] === 'completed'): ?>
                                                <span class="text-xs text-emerald-700 font-bold flex items-center gap-1 bg-emerald-50 px-3 py-1 rounded-xl">
                                                    <i class="fa-solid fa-circle-check text-xs"></i> Completed on <?= date('M d, Y', strtotime($task['completed_at'] ?? 'now')) ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>

                        </div>

                    </div>

                    <!-- RIGHT COLUMN: TODAY'S PRIORITY QUEUE & OPERATIONAL DIRECTIVES -->
                    <div class="lg:col-span-4 space-y-6">
                        
                        <!-- Today's Priority Queue Card -->
                        <div class="bg-white p-6 rounded-3xl border border-gray-200/60 shadow-sm space-y-4">
                            <div class="flex justify-between items-center">
                                <div class="flex items-center gap-2 font-bold text-sm text-gray-900">
                                    <i class="fa-solid fa-clock text-[#1c482c]"></i> Today's Active Directives
                                </div>
                                <span class="text-[10px] bg-emerald-100 text-emerald-800 font-bold px-2.5 py-1 rounded-full">
                                    <?= count($my_tasks) ?> Loaded
                                </span>
                            </div>

                            <div class="space-y-3 pt-2 text-xs">
                                <?php 
                                $top_tasks = array_slice($my_tasks, 0, 3);
                                if (empty($top_tasks)): ?>
                                    <p class="text-gray-400 italic">No active queue for today.</p>
                                <?php else: ?>
                                    <?php foreach ($top_tasks as $tt): ?>
                                    <div class="pb-3 border-b border-gray-100 space-y-1">
                                        <div class="flex justify-between">
                                            <strong class="text-gray-900 truncate max-w-[180px]"><?= htmlspecialchars($tt['title']) ?></strong>
                                            <span class="text-[10px] text-amber-700 font-bold uppercase"><?= $tt['priority'] ?></span>
                                        </div>
                                        <span class="text-[11px] text-gray-500 block truncate">Due: <?= !empty($tt['due_date']) ? date('M d, Y', strtotime($tt['due_date'])) : 'Open' ?></span>
                                    </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Operational Directives Card -->
                        <div class="bg-white p-6 rounded-3xl border border-gray-200/60 shadow-sm space-y-4">
                            <div class="flex items-center gap-2 text-xs font-bold text-gray-900 uppercase">
                                <i class="fa-solid fa-triangle-exclamation text-amber-600"></i> Operational Directives
                            </div>

                            <p class="text-xs text-gray-600 leading-relaxed">
                                Ensure all requirements are complete before submitting for review to the Supervisor. Completed service orders will automatically update the client tracking records.
                            </p>

                            <div class="pt-3 border-t border-gray-100 flex justify-between items-center text-xs">
                                <span class="text-gray-400 font-medium">Notice Ref: #OHANA-MALOLOS-01</span>
                                <span class="font-bold text-[#1c482c]">Active SOP</span>
                            </div>
                        </div>

                    </div>
                </div>

            </main>
        </div>
    </div>

</body>
</html>