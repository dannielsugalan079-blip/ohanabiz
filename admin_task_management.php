<?php
require_once __DIR__ . '/config/auth.php';
requireLogin('admin');
if (empty($_SESSION['admin_logged_in']) || empty($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}
$user = currentUser('admin') ?: currentUser();

$status = $_GET['status'] ?? 'all';
$q = trim($_GET['q'] ?? '');
$department = $_GET['department'] ?? 'all';
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 8;

$where = [];
$params = [];
if ($status !== 'all') { $where[] = 't.status = ?'; $params[] = $status; }
if ($q !== '') { $where[] = '(t.title LIKE ? OR t.description LIKE ? OR u.full_name LIKE ? OR sr.reference_no LIKE ?)'; $like = "%$q%"; $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like; }
if ($department !== 'all') { $where[] = 'u.department = ?'; $params[] = $department; }
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM tasks t LEFT JOIN users u ON t.assigned_to = u.id LEFT JOIN service_requests sr ON t.request_id = sr.id $whereSql");
$countStmt->execute($params);
$totalTasks = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalTasks / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$stmt = $pdo->prepare("SELECT t.*, u.full_name AS assignee_name, u.department AS assignee_department, sr.reference_no, sr.service_name FROM tasks t LEFT JOIN users u ON t.assigned_to = u.id LEFT JOIN service_requests sr ON t.request_id = sr.id $whereSql ORDER BY t.created_at DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

$task_counts = [
    'all' => (int) $pdo->query("SELECT COUNT(*) FROM tasks")->fetchColumn(),
    'pending' => (int) $pdo->query("SELECT COUNT(*) FROM tasks WHERE status = 'pending'")->fetchColumn(),
    'in_progress' => (int) $pdo->query("SELECT COUNT(*) FROM tasks WHERE status = 'in_progress'")->fetchColumn(),
    'for_review' => (int) $pdo->query("SELECT COUNT(*) FROM tasks WHERE status = 'for_review'")->fetchColumn(),
    'completed' => (int) $pdo->query("SELECT COUNT(*) FROM tasks WHERE status = 'completed'")->fetchColumn(),
];

$departments = $pdo->query("SELECT DISTINCT department FROM users WHERE role IN ('employee','supervisor') AND department IS NOT NULL AND department <> '' ORDER BY department ASC")->fetchAll(PDO::FETCH_COLUMN);
$unread_count = unreadNotificationCount($pdo, (int) $user['id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ohana Admin Enterprise Portal - Task Management</title>
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
                <a href="admin_task_management.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl bg-[#2D5A43] text-white text-sm font-medium shadow-sm"><i class="fa-solid fa-list-check text-base"></i>Task Management</a>
                <a href="admin_employee_list.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-gray-600 hover:bg-black/5 text-sm font-medium transition"><i class="fa-regular fa-address-book text-base"></i>Employee Directory</a>
                <a href="admin_leave_approvals.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-gray-600 hover:bg-black/5 text-sm font-medium transition"><i class="fa-regular fa-calendar-check text-base"></i>Leave Approvals</a>
                <a href="admin_settings.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-gray-600 hover:bg-black/5 text-sm font-medium transition"><i class="fa-solid fa-gear text-base"></i>Settings & Profile</a>
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
            <form method="GET" action="admin_task_management.php" class="relative w-96">
                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Search tasks, staff, records..." class="w-full bg-white/60 border border-gray-200/80 rounded-full pl-10 pr-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#2D5A43]/20 focus:border-[#2D5A43]">
                <?php if ($status !== 'all'): ?><input type="hidden" name="status" value="<?= htmlspecialchars($status) ?>"><?php endif; ?>
                <?php if ($department !== 'all'): ?><input type="hidden" name="department" value="<?= htmlspecialchars($department) ?>"><?php endif; ?>
            </form>
            <div class="flex items-center gap-4">
                <button class="w-10 h-10 rounded-xl border border-gray-200 flex items-center justify-center text-gray-600 hover:bg-white/80 relative">
                    <i class="fa-regular fa-bell text-base"></i>
                    <?php if ($unread_count > 0): ?><span class="w-2 h-2 rounded-full bg-orange-500 absolute top-2.5 right-2.5"></span><?php endif; ?>
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
            <div class="flex items-start justify-between">
                <div>
                    <div class="flex items-center space-x-2 text-[10px] font-bold text-gray-500 tracking-wider uppercase mb-1"><i class="fa-solid fa-sliders text-xs"></i><span>OPERATIONS HUB &bull;</span></div>
                    <h2 class="text-2xl font-extrabold text-gray-900">Task Management</h2>
                    <p class="text-xs text-gray-500 mt-0.5">Monitor active assignments and operational performance across departments.</p>
                </div>
                <div class="flex items-center space-x-3">
                    <div class="bg-amber-100/60 border border-amber-200/60 px-4 py-2 rounded-2xl flex items-center space-x-3"><i class="fa-regular fa-folder-open text-amber-800 text-base"></i><div><p class="text-[9px] font-bold text-gray-600 uppercase tracking-wider">Active Load</p><p class="text-xs font-extrabold text-gray-900"><?= $task_counts['all'] ?> Open</p></div></div>
                    <div class="bg-red-100/60 border border-red-200/60 px-4 py-2 rounded-2xl flex items-center space-x-3"><i class="fa-regular fa-bell text-red-800 text-base"></i><div><p class="text-[9px] font-bold text-gray-600 uppercase tracking-wider">Urgent Items</p><p class="text-xs font-extrabold text-gray-900"><?= $task_counts['pending'] + $task_counts['in_progress'] ?> Live</p></div></div>
                </div>
            </div>

            <div class="flex items-center justify-between gap-4">
                <div class="flex items-center space-x-2 bg-gray-200/60 p-1 rounded-xl">
                    <a href="admin_task_management.php?status=all<?= $q !== '' ? '&q=' . urlencode($q) : '' ?><?= $department !== 'all' ? '&department=' . urlencode($department) : '' ?>" class="<?= $status === 'all' ? 'bg-[#2d5a3f] text-white' : 'text-gray-600 hover:text-gray-900' ?> text-xs font-bold px-3 py-1.5 rounded-lg flex items-center gap-1.5 shadow-sm"><span>All Tasks</span><span class="<?= $status === 'all' ? 'bg-[#234832]' : 'bg-white/60' ?> text-white text-[10px] px-1.5 py-0.2 rounded-md"><?= $task_counts['all'] ?></span></a>
                    <a href="admin_task_management.php?status=pending<?= $q !== '' ? '&q=' . urlencode($q) : '' ?><?= $department !== 'all' ? '&department=' . urlencode($department) : '' ?>" class="<?= $status === 'pending' ? 'bg-[#2d5a3f] text-white' : 'text-gray-600 hover:text-gray-900' ?> text-xs font-medium px-3 py-1.5 rounded-lg flex items-center gap-1.5"><span>Pending</span><span class="<?= $status === 'pending' ? 'bg-[#234832] text-white' : 'text-gray-500' ?> text-[10px]"><?= $task_counts['pending'] ?></span></a>
                    <a href="admin_task_management.php?status=in_progress<?= $q !== '' ? '&q=' . urlencode($q) : '' ?><?= $department !== 'all' ? '&department=' . urlencode($department) : '' ?>" class="<?= $status === 'in_progress' ? 'bg-[#2d5a3f] text-white' : 'text-gray-600 hover:text-gray-900' ?> text-xs font-medium px-3 py-1.5 rounded-lg flex items-center gap-1.5"><span>In Progress</span><span class="<?= $status === 'in_progress' ? 'bg-[#234832] text-white' : 'text-gray-500' ?> text-[10px]"><?= $task_counts['in_progress'] ?></span></a>
                    <a href="admin_task_management.php?status=for_review<?= $q !== '' ? '&q=' . urlencode($q) : '' ?><?= $department !== 'all' ? '&department=' . urlencode($department) : '' ?>" class="<?= $status === 'for_review' ? 'bg-[#2d5a3f] text-white' : 'text-gray-600 hover:text-gray-900' ?> text-xs font-medium px-3 py-1.5 rounded-lg flex items-center gap-1.5"><span>For Review</span><span class="<?= $status === 'for_review' ? 'bg-[#234832] text-white' : 'text-gray-500' ?> text-[10px]"><?= $task_counts['for_review'] ?></span></a>
                    <a href="admin_task_management.php?status=completed<?= $q !== '' ? '&q=' . urlencode($q) : '' ?><?= $department !== 'all' ? '&department=' . urlencode($department) : '' ?>" class="<?= $status === 'completed' ? 'bg-[#2d5a3f] text-white' : 'text-gray-600 hover:text-gray-900' ?> text-xs font-medium px-3 py-1.5 rounded-lg flex items-center gap-1.5"><span>Completed</span><span class="<?= $status === 'completed' ? 'bg-[#234832] text-white' : 'text-gray-500' ?> text-[10px]"><?= $task_counts['completed'] ?></span></a>
                </div>
                <form method="GET" action="admin_task_management.php" class="relative max-w-xs w-full">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                    <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Filter task or assignee..." class="w-full bg-white border border-gray-200/80 rounded-xl pl-9 pr-4 py-1.5 text-xs text-gray-700 placeholder-gray-400 focus:outline-none focus:border-gray-400 transition shadow-sm">
                    <?php if ($status !== 'all'): ?><input type="hidden" name="status" value="<?= htmlspecialchars($status) ?>"><?php endif; ?>
                    <?php if ($department !== 'all'): ?><input type="hidden" name="department" value="<?= htmlspecialchars($department) ?>"><?php endif; ?>
                </form>
            </div>

            <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm overflow-hidden">
                <div class="px-6 py-4 flex items-center justify-between border-b border-gray-100">
                    <div class="flex items-center space-x-2"><h3 class="font-bold text-xs text-gray-900">Data Streaming</h3><span class="bg-emerald-100 text-emerald-800 text-[10px] font-bold px-2 py-0.5 rounded-md"><?= count($tasks) ?> Displayed</span></div>
                    <form method="GET" action="admin_task_management.php" class="flex items-center space-x-2 text-[11px] text-gray-700">
                        <input type="hidden" name="q" value="<?= htmlspecialchars($q) ?>">
                        <input type="hidden" name="status" value="<?= htmlspecialchars($status) ?>">
                        <select name="department" class="bg-white border border-gray-200 rounded-xl px-3 py-1.5 text-xs text-gray-700 focus:outline-none">
                            <option value="all" <?= $department === 'all' ? 'selected' : '' ?>>All departments</option>
                            <?php foreach ($departments as $dept): ?><option value="<?= htmlspecialchars($dept) ?>" <?= $department === $dept ? 'selected' : '' ?>><?= htmlspecialchars($dept) ?></option><?php endforeach; ?>
                        </select>
                        <button type="submit" class="px-2 py-1.5 border border-gray-200 rounded-xl hover:bg-gray-50">Apply</button>
                    </form>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-[#f7f3ed]/60 text-[10px] font-bold text-gray-500 uppercase tracking-wider border-b border-gray-200/60">
                                <th class="py-3 px-6">TITLE</th>
                                <th class="py-3 px-6">ASSIGNEE</th>
                                <th class="py-3 px-6">PRIORITY</th>
                                <th class="py-3 px-6">STATUS</th>
                                <th class="py-3 px-6">DUE DATE</th>
                                <th class="py-3 px-6">CREATED AT</th>
                                <th class="py-3 px-6">ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-xs">
                            <?php if (empty($tasks)): ?>
                                <tr><td colspan="7" class="text-center py-10 text-gray-400">No tasks match the current filters.</td></tr>
                            <?php else: foreach ($tasks as $task):
                                $priority_classes = ['critical' => 'bg-red-100 text-red-800', 'high' => 'bg-orange-100 text-orange-800', 'normal' => 'bg-blue-100 text-blue-800', 'low' => 'bg-gray-100 text-gray-600'];
                                $status_classes = ['completed' => 'bg-emerald-100 text-emerald-800', 'in_progress' => 'bg-blue-100 text-blue-800', 'pending' => 'bg-amber-100 text-amber-800', 'for_review' => 'bg-purple-100 text-purple-800'];
                                $pclass = $priority_classes[$task['priority']] ?? 'bg-gray-100 text-gray-600';
                                $sclass = $status_classes[$task['status']] ?? 'bg-gray-100 text-gray-600';
                            ?>
                                <tr class="border-t border-gray-100 hover:bg-gray-50/50 transition">
                                    <td class="py-3.5 px-5 text-sm font-semibold text-gray-900"><?= htmlspecialchars($task['title']) ?></td>
                                    <td class="py-3.5 px-5 text-sm text-gray-600"><?= htmlspecialchars($task['assignee_name'] ?? '—') ?></td>
                                    <td class="py-3.5 px-5"><span class="inline-block px-2.5 py-0.5 rounded-full text-[11px] font-semibold <?= $pclass ?>"><?= ucfirst($task['priority']) ?></span></td>
                                    <td class="py-3.5 px-5"><span class="inline-block px-2.5 py-0.5 rounded-full text-[11px] font-semibold <?= $sclass ?>"><?= ucwords(str_replace('_', ' ', $task['status'])) ?></span></td>
                                    <td class="py-3.5 px-5 text-sm text-gray-500"><?= $task['due_date'] ? date('M d, Y', strtotime($task['due_date'])) : '—' ?></td>
                                    <td class="py-3.5 px-5 text-sm text-gray-400"><?= date('M d, Y', strtotime($task['created_at'])) ?></td>
                                    <td class="py-3.5 px-5"><button type="button" data-open-task="1" data-title="<?= htmlspecialchars($task['title']) ?>" data-assignee="<?= htmlspecialchars($task['assignee_name'] ?? 'Unassigned') ?>" data-priority="<?= htmlspecialchars(ucfirst($task['priority'])) ?>" data-status="<?= htmlspecialchars(ucwords(str_replace('_', ' ', $task['status']))) ?>" data-description="<?= htmlspecialchars($task['description'] ?: 'No description provided.') ?>" data-department="<?= htmlspecialchars($task['assignee_department'] ?: '—') ?>" data-duedate="<?= htmlspecialchars($task['due_date'] ? date('M d, Y', strtotime($task['due_date'])) : 'Not set') ?>" class="text-xs font-semibold text-[#2D5A43] hover:underline">View</button></td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="px-6 py-3 bg-gray-50/50 border-t border-gray-100 flex items-center justify-between text-xs text-gray-500">
                    <span>Showing <?= count($tasks) ?> of <?= $totalTasks ?> active assignments</span>
                    <div class="flex items-center space-x-1">
                        <?php $queryString = http_build_query(['q' => $q, 'status' => $status, 'department' => $department]); ?>
                        <?php if ($page > 1): ?><a href="admin_task_management.php?page=<?= max(1, $page - 1) ?>&<?= $queryString ?>" class="w-7 h-7 flex items-center justify-center rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-100">&lt;</a><?php else: ?><span class="w-7 h-7 flex items-center justify-center rounded-lg border border-gray-200 text-gray-400">&lt;</span><?php endif; ?>
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <a href="admin_task_management.php?page=<?= $i ?>&<?= $queryString ?>" class="w-7 h-7 flex items-center justify-center rounded-lg <?= $i === $page ? 'bg-[#2d5a3f] text-white font-bold' : 'border border-gray-200 text-gray-600 hover:bg-gray-100' ?>"><?= $i ?></a>
                        <?php endfor; ?>
                        <?php if ($page < $totalPages): ?><a href="admin_task_management.php?page=<?= min($totalPages, $page + 1) ?>&<?= $queryString ?>" class="w-7 h-7 flex items-center justify-center rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-100">&gt;</a><?php else: ?><span class="w-7 h-7 flex items-center justify-center rounded-lg border border-gray-200 text-gray-400">&gt;</span><?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <div id="taskModal" class="hidden fixed inset-0 bg-gray-900/40 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="w-full max-w-lg bg-white rounded-2xl border border-gray-200 shadow-xl overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                <div>
                    <p class="text-[10px] uppercase tracking-wider text-gray-500 font-bold">Task Overview</p>
                    <h3 id="modalTaskTitle" class="text-lg font-bold text-gray-900">Task</h3>
                </div>
                <button type="button" id="closeTaskModal" class="text-gray-400 hover:text-gray-700"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="p-5 space-y-4 text-sm text-gray-600">
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-[#FAF7F2] rounded-xl p-3"><div class="text-[10px] uppercase text-gray-500">Assignee</div><div id="modalTaskAssignee" class="font-bold text-gray-900 mt-1">—</div></div>
                    <div class="bg-[#FAF7F2] rounded-xl p-3"><div class="text-[10px] uppercase text-gray-500">Priority</div><div id="modalTaskPriority" class="font-bold text-gray-900 mt-1">—</div></div>
                    <div class="bg-[#FAF7F2] rounded-xl p-3"><div class="text-[10px] uppercase text-gray-500">Status</div><div id="modalTaskStatus" class="font-bold text-gray-900 mt-1">—</div></div>
                    <div class="bg-[#FAF7F2] rounded-xl p-3"><div class="text-[10px] uppercase text-gray-500">Due Date</div><div id="modalTaskDueDate" class="font-bold text-gray-900 mt-1">—</div></div>
                </div>
                <div class="bg-[#FAF7F2] rounded-xl p-3"><div class="text-[10px] uppercase text-gray-500">Department</div><div id="modalTaskDepartment" class="font-bold text-gray-900 mt-1">—</div></div>
                <div class="bg-[#FAF7F2] rounded-xl p-3"><div class="text-[10px] uppercase text-gray-500">Description</div><p id="modalTaskDescription" class="mt-1 leading-relaxed">—</p></div>
            </div>
        </div>
    </div>

    <script>
        const modal = document.getElementById('taskModal');
        const closeBtn = document.getElementById('closeTaskModal');
        const taskTitle = document.getElementById('modalTaskTitle');
        const taskAssignee = document.getElementById('modalTaskAssignee');
        const taskPriority = document.getElementById('modalTaskPriority');
        const taskStatus = document.getElementById('modalTaskStatus');
        const taskDueDate = document.getElementById('modalTaskDueDate');
        const taskDepartment = document.getElementById('modalTaskDepartment');
        const taskDescription = document.getElementById('modalTaskDescription');

        document.querySelectorAll('[data-open-task]').forEach((btn) => {
            btn.addEventListener('click', () => {
                taskTitle.textContent = btn.dataset.title || 'Task';
                taskAssignee.textContent = btn.dataset.assignee || 'Unassigned';
                taskPriority.textContent = btn.dataset.priority || 'Normal';
                taskStatus.textContent = btn.dataset.status || 'Pending';
                taskDueDate.textContent = btn.dataset.duedate || 'Not set';
                taskDepartment.textContent = btn.dataset.department || 'Operations';
                taskDescription.textContent = btn.dataset.description || 'No description provided.';
                modal.classList.remove('hidden');
            });
        });

        closeBtn.addEventListener('click', () => modal.classList.add('hidden'));
        modal.addEventListener('click', (e) => {
            if (e.target === modal) modal.classList.add('hidden');
        });
    </script>
</body>
</html>