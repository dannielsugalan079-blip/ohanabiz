<?php
require_once __DIR__ . '/config/auth.php';
requireLogin('admin');
if (empty($_SESSION['admin_logged_in']) || empty($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}
$user = currentUser('admin') ?: currentUser();
$unread_count = unreadNotificationCount($pdo, (int) $user['id']);

$view = $_GET['view'] ?? 'pending';
$search = trim($_GET['q'] ?? '');
$department = $_GET['department'] ?? 'all';
if (!in_array($view, ['pending', 'history'], true)) { $view = 'pending'; }

$statuses = $view === 'history' ? ['approved', 'rejected'] : ['pending', 'endorsed'];
$where = [];
$params = [];
if ($search !== '') {
    $where[] = '(u.full_name LIKE ? OR u.position LIKE ? OR u.department LIKE ? OR lr.reason LIKE ?)';
    $like = "%$search%";
    $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like;
}
if ($department !== 'all') {
    $where[] = 'u.department = ?';
    $params[] = $department;
}
$baseSql = "SELECT lr.*, u.full_name, u.position, u.department, u.email, eu.full_name AS endorsed_name, au.full_name AS approved_name FROM leave_requests lr JOIN users u ON lr.user_id = u.id LEFT JOIN users eu ON lr.endorsed_by = eu.id LEFT JOIN users au ON lr.approved_by = au.id";
if ($where) {
    $baseSql .= ' WHERE ' . implode(' AND ', $where) . ' AND lr.status IN (' . implode(',', array_fill(0, count($statuses), '?')) . ')';
    $params = array_merge($params, $statuses);
} else {
    $baseSql .= ' WHERE lr.status IN (' . implode(',', array_fill(0, count($statuses), '?')) . ')';
    $params = $statuses;
}
$baseSql .= ' ORDER BY lr.created_at DESC';

$visibleStmt = $pdo->prepare($baseSql);
$visibleStmt->execute($params);
$visible = $visibleStmt->fetchAll();

$departmentOptions = $pdo->query("SELECT DISTINCT department FROM users WHERE role IN ('employee','supervisor') AND department IS NOT NULL AND department <> '' ORDER BY department ASC")->fetchAll(PDO::FETCH_COLUMN);
$total_employees = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'employee' AND status = 'active'")->fetchColumn();
$queue = fetchLeaveRequests($pdo, ['pending', 'endorsed']);
$history = fetchLeaveRequests($pdo, ['approved', 'rejected']);
$on_leave_today = (int) $pdo->query("SELECT COUNT(*) FROM leave_requests WHERE status = 'approved' AND CURDATE() BETWEEN date_from AND date_to")->fetchColumn();
$present_today = (int) $pdo->query("SELECT COUNT(DISTINCT user_id) FROM attendance WHERE date = CURDATE() AND status IN ('present','late','half_day','wfh')")->fetchColumn();
$staff_preview = $pdo->query("SELECT u.id, u.full_name, u.position, CASE WHEN EXISTS (SELECT 1 FROM leave_requests lr WHERE lr.user_id = u.id AND lr.status = 'approved' AND CURDATE() BETWEEN lr.date_from AND lr.date_to) THEN 'On Leave' WHEN EXISTS (SELECT 1 FROM attendance a WHERE a.user_id = u.id AND a.date = CURDATE() AND a.status IN ('present','late','half_day','wfh')) THEN 'On Duty' ELSE 'Standby' END AS live_status FROM users u WHERE u.role = 'employee' AND u.status = 'active' ORDER BY u.full_name LIMIT 6")->fetchAll();
$standby = 0;
foreach ($staff_preview as $s) { if ($s['live_status'] === 'Standby') { $standby++; } }
$coverage = $total_employees > 0 ? (int) round((($total_employees - $on_leave_today) / $total_employees) * 100) : 100;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Ohana Admin - Leave Approvals</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
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
        <a href="admin_leave_approvals.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl bg-[#2D5A43] text-white text-sm font-medium shadow-sm"><i class="fa-regular fa-calendar-check text-base"></i>Leave Approvals</a>
        <a href="admin_settings.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-gray-600 hover:bg-black/5 text-sm font-medium transition"><i class="fa-solid fa-gear text-base"></i>Settings & Profile</a>
      </div>
    </div>
    <div class="space-y-4">
      <a href="admin_logout.php" class="flex items-center gap-3 px-3 py-2 text-gray-600 hover:text-gray-900 text-sm font-medium"><i class="fa-solid fa-arrow-right-from-bracket rotate-180"></i>Log Out</a>
      <div class="bg-amber-900/5 p-3.5 rounded-2xl border border-amber-900/10"><div class="flex items-center gap-2 text-xs font-semibold text-gray-700 mb-1"><i class="fa-regular fa-shield-check text-amber-800"></i>SYSTEM HEALTH</div><p class="text-[11px] text-gray-500 leading-tight">All services synchronized with central roster.</p></div>
    </div>
  </aside>
  <main class="flex-1 flex flex-col min-w-0 min-h-0 overflow-hidden">
    <header class="h-20 border-b border-amber-900/10 flex items-center justify-between px-8 bg-[#FAF7F2] shrink-0">
      <form method="GET" action="admin_leave_approvals.php" class="relative w-96">
        <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
        <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search leave requests..." class="w-full bg-white/60 border border-gray-200/80 rounded-full pl-10 pr-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#2D5A43]/20 focus:border-[#2D5A43]">
        <?php if ($view !== 'pending'): ?><input type="hidden" name="view" value="<?= htmlspecialchars($view) ?>"><?php endif; ?>
        <?php if ($department !== 'all'): ?><input type="hidden" name="department" value="<?= htmlspecialchars($department) ?>"><?php endif; ?>
      </form>
      <div class="flex items-center gap-4">
        <div class="w-10 h-10 rounded-xl border border-gray-200 flex items-center justify-center text-gray-600 hover:bg-white/80 relative">
          <i class="fa-regular fa-bell text-base"></i>
          <?php if ($unread_count > 0): ?><span class="w-4 h-4 rounded-full bg-orange-500 text-white text-[9px] font-bold absolute -top-1 -right-1 flex items-center justify-center"><?= (int) $unread_count ?></span><?php endif; ?>
        </div>
        <div class="flex items-center gap-3 pl-2 border-l border-gray-200">
          <div class="w-10 h-10 rounded-full bg-[#2D5A43] text-white flex items-center justify-center font-bold text-sm shadow-sm"><?= htmlspecialchars($user['initials'] ?? 'AD') ?></div>
          <div class="text-left"><h4 class="text-sm font-bold text-gray-900 leading-none"><?= htmlspecialchars($user['full_name'] ?? 'Administrator') ?></h4><span class="text-xs text-gray-500"><?= htmlspecialchars($user['position'] ?? 'System Administrator') ?></span></div>
        </div>
      </div>
    </header>
    <div class="p-8 space-y-6 overflow-y-auto flex-1 min-h-0">
      <div>
        <span class="inline-block px-2.5 py-0.5 rounded-md bg-gray-200/70 text-gray-600 text-[10px] font-bold tracking-wider uppercase mb-1">• LEAVE OPERATIONS</span>
        <h2 class="text-2xl font-bold text-gray-900">Leave Approvals</h2>
        <p class="text-sm text-gray-500 mt-0.5">Review, authorize, and balance organizational time-off and department coverage.</p>
      </div>
      <div class="flex items-center justify-between border-b border-gray-200/60 pb-3">
        <div class="flex items-center gap-3">
          <a href="admin_leave_approvals.php?view=pending<?= $search !== '' ? '&q=' . urlencode($search) : '' ?><?= $department !== 'all' ? '&department=' . urlencode($department) : '' ?>" class="<?= $view === 'pending' ? 'bg-[#2D5A43] text-white shadow-sm' : 'text-gray-600 hover:bg-black/5' ?> px-4 py-2 rounded-xl text-xs font-semibold flex items-center gap-2">Pending Approvals <span class="<?= $view === 'pending' ? 'bg-white/20 text-white' : 'bg-gray-200 text-gray-600' ?> text-[10px] px-1.5 py-0.2 rounded-full"><?= count($queue) ?></span></a>
          <a href="admin_leave_approvals.php?view=history<?= $search !== '' ? '&q=' . urlencode($search) : '' ?><?= $department !== 'all' ? '&department=' . urlencode($department) : '' ?>" class="<?= $view === 'history' ? 'bg-[#2D5A43] text-white shadow-sm' : 'text-gray-600 hover:bg-black/5' ?> px-4 py-2 rounded-xl text-xs font-medium transition flex items-center gap-2">Approval History <span class="<?= $view === 'history' ? 'bg-white/20 text-white' : 'bg-gray-200 text-gray-600' ?> text-[10px] px-1.5 py-0.2 rounded-full"><?= count($history) ?></span></a>
          <a href="admin_leave_approvals.php?view=pending" class="text-gray-600 hover:bg-black/5 px-4 py-2 rounded-xl text-xs font-medium transition flex items-center gap-2"><i class="fa-regular fa-calendar-days text-xs"></i>Leave Calendar</a>
        </div>
        <form method="GET" action="admin_leave_approvals.php" class="flex items-center gap-2">
          <input type="hidden" name="view" value="<?= htmlspecialchars($view) ?>">
          <input type="hidden" name="q" value="<?= htmlspecialchars($search) ?>">
          <select name="department" class="bg-white/80 border border-gray-200 rounded-xl px-3 py-1.5 text-xs text-gray-700 font-medium focus:outline-none">
            <option value="all" <?= $department === 'all' ? 'selected' : '' ?>>All Departments</option>
            <?php foreach ($departmentOptions as $dept): ?><option value="<?= htmlspecialchars($dept) ?>" <?= $department === $dept ? 'selected' : '' ?>><?= htmlspecialchars($dept) ?></option><?php endforeach; ?>
          </select>
          <button type="submit" class="p-2 bg-white/80 border border-gray-200 rounded-xl text-gray-600 hover:bg-gray-50"><i class="fa-solid fa-arrow-down-short-wide text-xs"></i></button>
        </form>
      </div>
      <div class="grid grid-cols-4 gap-6 items-start">
        <div class="col-span-3 space-y-4">
          <?php if (!$visible): ?>
            <div class="bg-white/90 border border-amber-900/10 rounded-2xl p-5 shadow-sm space-y-4"><div class="text-center py-16 text-gray-400"><i class="fa-regular fa-calendar-check text-4xl mb-3 block"></i><h3 class="text-sm font-semibold text-gray-600 mb-1"><?= $view === 'history' ? 'No Leave History Yet' : 'No Pending Leave Requests' ?></h3><p class="text-xs"><?= $view === 'history' ? 'Approved and declined requests will appear here.' : 'There are no employee leave requests awaiting administrative approval at this time.' ?></p></div></div>
          <?php else: foreach ($visible as $lr): ?>
            <div class="bg-white/90 border border-amber-900/10 rounded-2xl p-5 shadow-sm space-y-4">
              <div class="flex items-start justify-between">
                <div>
                  <h4 class="font-bold text-sm text-gray-900"><?= htmlspecialchars($lr['full_name']) ?></h4>
                  <p class="text-[11px] text-gray-500"><?= htmlspecialchars($lr['position'] ?: 'Staff') ?> • <?= htmlspecialchars($lr['department'] ?: 'Operations') ?></p>
                </div>
                <div class="text-right space-y-1">
                  <span class="inline-block bg-amber-100 text-amber-900 text-[10px] font-bold px-2.5 py-1 rounded-full"><?= htmlspecialchars(formatLeaveType($lr['leave_type'])) ?></span>
                  <div class="text-[10px] font-semibold uppercase text-gray-400"><?= htmlspecialchars($lr['status']) ?></div>
                </div>
              </div>
              <div class="flex items-center justify-between text-xs text-gray-600 font-medium bg-[#FAF7F2] p-2.5 rounded-xl border border-amber-900/5"><span><?= date('M j, Y', strtotime($lr['date_from'])) ?> – <?= date('M j, Y', strtotime($lr['date_to'])) ?></span><span><?= (int) $lr['total_days'] ?> days</span></div>
              <p class="text-xs text-gray-500"><?= htmlspecialchars($lr['reason']) ?></p>
              <?php if (!empty($lr['endorsed_name'])): ?><p class="text-[11px] text-emerald-800">Endorsed by <?= htmlspecialchars($lr['endorsed_name']) ?></p><?php endif; ?>
              <?php if (in_array($lr['status'], ['pending', 'endorsed'], true)): ?>
                <form method="POST" class="space-y-3">
                  <input type="hidden" name="leave_id" value="<?= (int) $lr['id'] ?>">
                  <input type="text" name="remarks" placeholder="Decision remarks (optional)" class="w-full bg-[#FAF7F2] border border-gray-200 rounded-xl px-3 py-2 text-xs">
                  <div class="grid grid-cols-2 gap-3">
                    <button type="submit" name="action" value="reject" class="w-full py-2 bg-gray-200/80 hover:bg-gray-300 text-gray-800 text-xs font-bold rounded-xl transition">Reject</button>
                    <button type="submit" name="action" value="approve" class="w-full py-2 bg-[#2d5a3f] hover:bg-[#234832] text-white text-xs font-bold rounded-xl shadow-sm transition">Approve</button>
                  </div>
                </form>
              <?php endif; ?>
            </div>
          <?php endforeach; endif; ?>
        </div>
        <div class="col-span-1 space-y-4">
          <div class="bg-white/90 border border-amber-900/10 rounded-2xl p-5 shadow-sm space-y-5">
            <div class="flex items-center justify-between"><h3 class="font-bold text-gray-900 text-sm flex items-center gap-2"><i class="fa-solid fa-users text-gray-600"></i> Live Staff Status</h3><span class="text-[10px] text-emerald-700 font-bold flex items-center gap-1"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span> Real-time</span></div>
            <div class="grid grid-cols-2 gap-2 text-xs">
              <div class="bg-[#FAF7F2] p-2.5 rounded-xl border border-amber-900/5"><div class="text-[10px] text-gray-400 font-medium">On Duty</div><div class="font-bold text-gray-900 text-sm"><span class="text-base"><?= (int) $present_today ?></span> Active</div></div>
              <div class="bg-[#FAF7F2] p-2.5 rounded-xl border border-amber-900/5"><div class="text-[10px] text-gray-400 font-medium">Standby</div><div class="font-bold text-gray-900 text-sm"><span class="text-base"><?= (int) $standby ?></span> Available</div></div>
              <div class="bg-[#FAF7F2] p-2.5 rounded-xl border border-amber-900/5"><div class="text-[10px] text-gray-400 font-medium">On Break</div><div class="font-bold text-gray-900 text-sm"><span class="text-base">0</span> Paused</div></div>
              <div class="bg-[#FAF7F2] p-2.5 rounded-xl border border-amber-900/5"><div class="text-[10px] text-gray-400 font-medium">On Leave</div><div class="font-bold text-gray-900 text-sm"><span class="text-base"><?= (int) $on_leave_today ?></span> Absent</div></div>
            </div>
            <div class="space-y-3 pt-2">
              <?php if (!$staff_preview): ?><div class="text-center py-4 text-gray-400 text-xs italic">Staff tracking data will appear here.</div><?php else: foreach ($staff_preview as $st): ?><div class="flex items-center justify-between text-xs"><span class="font-medium text-gray-800"><?= htmlspecialchars($st['full_name']) ?></span><span class="text-[10px] font-bold <?= $st['live_status'] === 'On Leave' ? 'text-amber-800' : ($st['live_status'] === 'On Duty' ? 'text-emerald-700' : 'text-gray-500') ?>"><?= htmlspecialchars($st['live_status']) ?></span></div><?php endforeach; endif; ?>
            </div>
            <div class="bg-[#FAF7F2] p-3 rounded-xl border border-amber-900/10 text-xs text-gray-600 space-y-1"><div class="flex items-center gap-1.5 font-bold text-gray-800 text-[11px]"><i class="fa-regular fa-chart-bar text-emerald-700"></i><?= (int) $coverage ?>% Workforce Active on Duty</div><p class="text-[10px] text-gray-500 leading-tight">Coverage requirements fulfilled across all shifts.</p></div>
          </div>
        </div>
      </div>
    </div>
  </main>
</body>
</html>