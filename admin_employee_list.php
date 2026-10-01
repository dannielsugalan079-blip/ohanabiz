<?php
require_once __DIR__ . '/config/auth.php';
requireLogin('admin');
if (empty($_SESSION['admin_logged_in']) || empty($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}
$user = currentUser('admin') ?: currentUser();

$q = trim($_GET['q'] ?? '');
$roleFilter = $_GET['role'] ?? 'all';
$statusFilter = $_GET['status'] ?? 'all';
$departmentFilter = $_GET['department'] ?? 'all';
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;

$where = ['role IN (\'employee\',\'supervisor\')'];
$params = [];
if ($q !== '') {
    $where[] = '(full_name LIKE ? OR email LIKE ? OR position LIKE ? OR department LIKE ?)';
    $like = "%$q%";
    $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like;
}
if ($roleFilter !== 'all') { $where[] = 'role = ?'; $params[] = $roleFilter; }
if ($statusFilter !== 'all') { $where[] = 'status = ?'; $params[] = $statusFilter; }
if ($departmentFilter !== 'all') { $where[] = 'department = ?'; $params[] = $departmentFilter; }
$whereSql = 'WHERE ' . implode(' AND ', $where);

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM users $whereSql");
$countStmt->execute($params);
$totalEmp = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalEmp / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$stmt = $pdo->prepare("SELECT id, full_name, email, role, position, department, status, last_login FROM users $whereSql ORDER BY role ASC, full_name ASC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

$departments = $pdo->query("SELECT DISTINCT department FROM users WHERE role IN ('employee','supervisor') AND department IS NOT NULL AND department <> '' ORDER BY department ASC")->fetchAll(PDO::FETCH_COLUMN);
$total_emp_count = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role IN ('employee','supervisor')")->fetchColumn();
$active_count = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role IN ('employee','supervisor') AND status = 'active'")->fetchColumn();
$unread_count = unreadNotificationCount($pdo, (int) $user['id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Ohana Admin - Employee Management</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
        <a href="admin_employee_list.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl bg-[#2D5A43] text-white text-sm font-medium shadow-sm"><i class="fa-regular fa-address-book text-base"></i>Employee Directory</a>
        <a href="admin_leave_approvals.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-gray-600 hover:bg-black/5 text-sm font-medium transition"><i class="fa-regular fa-calendar-check text-base"></i>Leave Approvals</a>
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
      <form method="GET" action="admin_employee_list.php" class="relative w-96">
        <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
        <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Search tasks, staff, records..." class="w-full bg-white/60 border border-gray-200/80 rounded-full pl-10 pr-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#2D5A43]/20 focus:border-[#2D5A43]">
        <?php if ($roleFilter !== 'all'): ?><input type="hidden" name="role" value="<?= htmlspecialchars($roleFilter) ?>"><?php endif; ?>
        <?php if ($statusFilter !== 'all'): ?><input type="hidden" name="status" value="<?= htmlspecialchars($statusFilter) ?>"><?php endif; ?>
        <?php if ($departmentFilter !== 'all'): ?><input type="hidden" name="department" value="<?= htmlspecialchars($departmentFilter) ?>"><?php endif; ?>
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
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-2xl font-bold text-gray-900">Employee Management</h2>
          <p class="text-sm text-gray-500 mt-1">Review staff roster, roles, permissions, and internal member directory.</p>
        </div>
      </div>

      <div class="grid grid-cols-4 gap-4">
        <div class="bg-white/80 p-5 rounded-2xl border border-amber-900/10 shadow-sm relative overflow-hidden">
          <div class="flex items-center justify-between text-gray-500 mb-2"><span class="text-[11px] font-bold uppercase tracking-wider">TOTAL PERSONNEL</span><div class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center text-gray-600"><i class="fa-solid fa-users text-sm"></i></div></div>
          <div class="flex items-baseline gap-2"><span class="text-3xl font-bold text-gray-900"><?= $total_emp_count ?></span><span class="text-xs bg-emerald-100 text-emerald-800 font-medium px-2 py-0.5 rounded-full"><?= $active_count ?> Active staff</span></div>
        </div>
        <div class="bg-white/80 p-5 rounded-2xl border border-amber-900/10 shadow-sm"><div class="flex items-center justify-between text-gray-500 mb-2"><span class="text-[11px] font-bold uppercase tracking-wider">ON LEAVE TODAY</span><div class="w-8 h-8 rounded-lg bg-orange-50 flex items-center justify-center text-orange-600"><i class="fa-regular fa-calendar text-sm"></i></div></div><div class="flex items-baseline gap-2"><span class="text-3xl font-bold text-gray-900"><?= (int) $pdo->query("SELECT COUNT(*) FROM leave_requests WHERE status = 'approved' AND CURDATE() BETWEEN date_from AND date_to")->fetchColumn() ?></span><span class="text-xs bg-orange-100 text-orange-800 font-medium px-2 py-0.5 rounded-full">Scheduled</span></div></div>
        <div class="bg-white/80 p-5 rounded-2xl border border-amber-900/10 shadow-sm"><div class="flex items-center justify-between text-gray-500 mb-2"><span class="text-[11px] font-bold uppercase tracking-wider">ABSENT TODAY</span><div class="w-8 h-8 rounded-lg bg-red-50 flex items-center justify-center text-red-500"><i class="fa-solid fa-user-xmark text-sm"></i></div></div><div class="flex items-baseline gap-2"><span class="text-3xl font-bold text-gray-900"><?= (int) $pdo->query("SELECT COUNT(*) FROM attendance WHERE date = CURDATE() AND status = 'absent'")->fetchColumn() ?></span><span class="text-xs bg-red-100 text-red-700 font-medium px-2 py-0.5 rounded-full">Unscheduled</span></div></div>
        <div class="bg-white/80 p-5 rounded-2xl border border-amber-900/10 shadow-sm"><div class="flex items-center justify-between text-gray-500 mb-2"><span class="text-[11px] font-bold uppercase tracking-wider">PENDING APPROVALS</span><div class="w-8 h-8 rounded-lg bg-amber-50 flex items-center justify-center text-amber-600"><i class="fa-solid fa-key text-sm"></i></div></div><div class="flex items-baseline gap-2"><span class="text-3xl font-bold text-gray-900"><?= (int) $pdo->query("SELECT COUNT(*) FROM leave_requests WHERE status IN ('pending','endorsed')")->fetchColumn() ?></span></div></div>
      </div>

      <div class="grid grid-cols-4 gap-6 items-start">
        <div class="col-span-3 space-y-4">
          <div class="flex items-center justify-between gap-4">
            <form method="GET" action="admin_employee_list.php" class="relative flex-1">
              <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
              <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Filter by name, email, role, or ID..." class="w-full bg-white/80 border border-gray-200 rounded-xl pl-10 pr-4 py-2 text-sm focus:outline-none">
              <?php if ($roleFilter !== 'all'): ?><input type="hidden" name="role" value="<?= htmlspecialchars($roleFilter) ?>"><?php endif; ?>
              <?php if ($statusFilter !== 'all'): ?><input type="hidden" name="status" value="<?= htmlspecialchars($statusFilter) ?>"><?php endif; ?>
              <?php if ($departmentFilter !== 'all'): ?><input type="hidden" name="department" value="<?= htmlspecialchars($departmentFilter) ?>"><?php endif; ?>
            </form>
            <div class="flex items-center gap-3">
              <form method="GET" action="admin_employee_list.php" class="flex items-center gap-3">
                <input type="hidden" name="q" value="<?= htmlspecialchars($q) ?>">
                <select name="role" class="bg-white/80 border border-gray-200 rounded-xl px-3 py-2 text-sm text-gray-700 focus:outline-none">
                  <option value="all" <?= $roleFilter === 'all' ? 'selected' : '' ?>>Roles: All Types</option>
                  <option value="employee" <?= $roleFilter === 'employee' ? 'selected' : '' ?>>Employee</option>
                  <option value="supervisor" <?= $roleFilter === 'supervisor' ? 'selected' : '' ?>>Supervisor</option>
                </select>
                <select name="status" class="bg-white/80 border border-gray-200 rounded-xl px-3 py-2 text-sm text-gray-700 focus:outline-none">
                  <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>Status: All Statuses</option>
                  <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active</option>
                  <option value="inactive" <?= $statusFilter === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                  <option value="suspended" <?= $statusFilter === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                </select>
                <select name="department" class="bg-white/80 border border-gray-200 rounded-xl px-3 py-2 text-sm text-gray-700 focus:outline-none">
                  <option value="all" <?= $departmentFilter === 'all' ? 'selected' : '' ?>>All departments</option>
                  <?php foreach ($departments as $dept): ?><option value="<?= htmlspecialchars($dept) ?>" <?= $departmentFilter === $dept ? 'selected' : '' ?>><?= htmlspecialchars($dept) ?></option><?php endforeach; ?>
                </select>
                <button type="submit" class="p-2.5 bg-white/80 border border-gray-200 rounded-xl text-gray-600 hover:bg-gray-50"><i class="fa-solid fa-sliders text-sm"></i></button>
              </form>
            </div>
          </div>

          <div class="bg-white/80 border border-amber-900/10 rounded-2xl overflow-hidden shadow-sm">
            <table class="w-full text-left border-collapse">
              <thead>
                <tr class="border-b border-gray-100 text-[11px] font-bold text-gray-400 uppercase tracking-wider bg-black/[0.01]">
                  <th class="py-3.5 px-4">EMPLOYEE</th>
                  <th class="py-3.5 px-4">POSITION</th>
                  <th class="py-3.5 px-4">DEPARTMENT</th>
                  <th class="py-3.5 px-4">ROLE</th>
                  <th class="py-3.5 px-4">STATUS</th>
                  <th class="py-3.5 px-4">LAST LOGIN</th>
                  <th class="py-3.5 px-4">ACTION</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 text-sm">
                <?php if (empty($employees)): ?>
                  <tr><td colspan="7" class="text-center py-10 text-gray-400">No employee records found.</td></tr>
                <?php else: foreach ($employees as $emp): ?>
                  <tr class="border-t border-gray-100 hover:bg-gray-50/50 transition">
                    <td class="py-3.5 px-5"><div class="flex items-center gap-3"><div class="w-9 h-9 rounded-full bg-[#2D5A43] text-white flex items-center justify-center text-sm font-bold shrink-0"><?= strtoupper(substr($emp['full_name'],0,1)) ?></div><div><p class="text-sm font-semibold text-gray-900"><?= htmlspecialchars($emp['full_name']) ?></p><p class="text-xs text-gray-400"><?= htmlspecialchars($emp['email']) ?></p></div></div></td>
                    <td class="py-3.5 px-5 text-sm text-gray-600"><?= htmlspecialchars($emp['position'] ?? '—') ?></td>
                    <td class="py-3.5 px-5 text-sm text-gray-600"><?= htmlspecialchars($emp['department'] ?? '—') ?></td>
                    <td class="py-3.5 px-5"><span class="inline-block px-2.5 py-0.5 rounded-full text-[11px] font-semibold <?= $emp['role'] === 'supervisor' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800' ?>"><?= ucfirst($emp['role']) ?></span></td>
                    <td class="py-3.5 px-5"><span class="inline-block px-2.5 py-0.5 rounded-full text-[11px] font-semibold <?= $emp['status'] === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800' ?>"><?= ucfirst($emp['status']) ?></span></td>
                    <td class="py-3.5 px-5 text-sm text-gray-500"><?= $emp['last_login'] ? date('M d, Y', strtotime($emp['last_login'])) : 'Never' ?></td>
                    <td class="py-3.5 px-5"><button type="button" data-open-profile="1" data-name="<?= htmlspecialchars($emp['full_name']) ?>" data-email="<?= htmlspecialchars($emp['email']) ?>" data-role="<?= htmlspecialchars(ucfirst($emp['role'])) ?>" data-position="<?= htmlspecialchars($emp['position'] ?? '—') ?>" data-department="<?= htmlspecialchars($emp['department'] ?? '—') ?>" data-status="<?= htmlspecialchars(ucfirst($emp['status'])) ?>" data-lastlogin="<?= htmlspecialchars($emp['last_login'] ? date('M d, Y', strtotime($emp['last_login'])) : 'Never') ?>" class="text-xs font-semibold text-[#2D5A43] hover:underline">View Profile</button></td>
                  </tr>
                <?php endforeach; endif; ?>
              </tbody>
            </table>
          </div>

          <div class="flex items-center justify-between text-xs text-gray-500 pt-2">
            <div>Showing <?= count($employees) ?> team members</div>
            <div class="flex items-center gap-1">
              <?php $queryString = http_build_query(['q' => $q, 'role' => $roleFilter, 'status' => $statusFilter, 'department' => $departmentFilter]); ?>
              <?php if ($page > 1): ?><a href="admin_employee_list.php?page=<?= max(1, $page - 1) ?>&<?= $queryString ?>" class="w-8 h-8 rounded-lg border border-gray-200 flex items-center justify-center text-gray-600 hover:bg-white">&lt;</a><?php else: ?><span class="w-8 h-8 rounded-lg border border-gray-200 flex items-center justify-center text-gray-400">&lt;</span><?php endif; ?>
              <?php for ($i = 1; $i <= $totalPages; $i++): ?><a href="admin_employee_list.php?page=<?= $i ?>&<?= $queryString ?>" class="w-8 h-8 rounded-lg <?= $i === $page ? 'bg-[#2D5A43] text-white font-medium' : 'border border-gray-200 text-gray-600 hover:bg-white' ?> flex items-center justify-center"><?= $i ?></a><?php endfor; ?>
              <?php if ($page < $totalPages): ?><a href="admin_employee_list.php?page=<?= min($totalPages, $page + 1) ?>&<?= $queryString ?>" class="w-8 h-8 rounded-lg border border-gray-200 flex items-center justify-center text-gray-600 hover:bg-white">&gt;</a><?php else: ?><span class="w-8 h-8 rounded-lg border border-gray-200 flex items-center justify-center text-gray-400">&gt;</span><?php endif; ?>
            </div>
          </div>
        </div>

        <div class="col-span-1 bg-white/80 border border-amber-900/10 rounded-2xl p-5 shadow-sm space-y-6">
          <div>
            <div class="flex items-center justify-between mb-1"><h3 class="font-bold text-gray-900 text-base flex items-center gap-2"><span>🔐</span> Access Approvals</h3><span class="bg-orange-100 text-orange-800 text-[10px] font-bold px-2 py-0.5 rounded-full"><?= (int) $pdo->query("SELECT COUNT(*) FROM leave_requests WHERE status IN ('pending','endorsed')")->fetchColumn() ?> Pending</span></div>
            <p class="text-xs text-gray-500 leading-relaxed">Roster elevates require administrator authorization prior to operational dispatch.</p>
          </div>
          <div class="space-y-4">
            <?php foreach ($pdo->query("SELECT lr.id, u.full_name, u.position, u.department, lr.status, lr.created_at FROM leave_requests lr JOIN users u ON u.id = lr.user_id WHERE lr.status IN ('pending','endorsed') ORDER BY lr.created_at DESC LIMIT 2")->fetchAll() as $approval): ?>
              <div class="bg-[#FAF7F2] border border-amber-900/10 rounded-xl p-4 space-y-3">
                <div class="flex items-center justify-between"><div class="flex items-center gap-2.5"><div class="w-9 h-9 rounded-full bg-[#2D5A43] text-white flex items-center justify-center text-xs font-bold"><?= htmlspecialchars(getUserInitials($approval['full_name'] ?? 'Employee')) ?></div><div><h4 class="font-bold text-xs text-gray-900"><?= htmlspecialchars($approval['full_name']) ?></h4><p class="text-[11px] text-gray-500"><?= htmlspecialchars($approval['position'] ?: 'Staff') ?></p></div></div><span class="text-[10px] text-gray-400"><?= date('M d', strtotime($approval['created_at'])) ?></span></div>
                <div class="bg-white p-2.5 rounded-lg border border-gray-200/80 text-xs text-gray-700 flex items-center gap-2"><i class="fa-solid fa-key text-amber-600"></i><div><span class="font-bold text-[11px] block"><?= htmlspecialchars($approval['status']) ?></span><span class="text-[10px] text-gray-500"><?= htmlspecialchars($approval['department'] ?: 'Operations') ?></span></div></div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>
  </main>

  <div id="profileModal" class="hidden fixed inset-0 bg-gray-900/40 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="w-full max-w-md bg-white rounded-2xl border border-gray-200 shadow-xl overflow-hidden">
      <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100"><div><p class="text-[10px] uppercase tracking-wider text-gray-500 font-bold">Profile</p><h3 id="modalProfileName" class="text-lg font-bold text-gray-900">Employee</h3></div><button type="button" id="closeProfileModal" class="text-gray-400 hover:text-gray-700"><i class="fa-solid fa-xmark"></i></button></div>
      <div class="p-5 space-y-3 text-sm text-gray-600">
        <div class="bg-[#FAF7F2] rounded-xl p-3"><div class="text-[10px] uppercase text-gray-500">Email</div><div id="modalProfileEmail" class="font-bold text-gray-900 mt-1">—</div></div>
        <div class="grid grid-cols-2 gap-3">
          <div class="bg-[#FAF7F2] rounded-xl p-3"><div class="text-[10px] uppercase text-gray-500">Role</div><div id="modalProfileRole" class="font-bold text-gray-900 mt-1">—</div></div>
          <div class="bg-[#FAF7F2] rounded-xl p-3"><div class="text-[10px] uppercase text-gray-500">Status</div><div id="modalProfileStatus" class="font-bold text-gray-900 mt-1">—</div></div>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div class="bg-[#FAF7F2] rounded-xl p-3"><div class="text-[10px] uppercase text-gray-500">Position</div><div id="modalProfilePosition" class="font-bold text-gray-900 mt-1">—</div></div>
          <div class="bg-[#FAF7F2] rounded-xl p-3"><div class="text-[10px] uppercase text-gray-500">Department</div><div id="modalProfileDepartment" class="font-bold text-gray-900 mt-1">—</div></div>
        </div>
        <div class="bg-[#FAF7F2] rounded-xl p-3"><div class="text-[10px] uppercase text-gray-500">Last Login</div><div id="modalProfileLastLogin" class="font-bold text-gray-900 mt-1">—</div></div>
      </div>
    </div>
  </div>

  <script>
    const profileModal = document.getElementById('profileModal');
    const closeProfileModal = document.getElementById('closeProfileModal');
    const modalProfileName = document.getElementById('modalProfileName');
    const modalProfileEmail = document.getElementById('modalProfileEmail');
    const modalProfileRole = document.getElementById('modalProfileRole');
    const modalProfileStatus = document.getElementById('modalProfileStatus');
    const modalProfilePosition = document.getElementById('modalProfilePosition');
    const modalProfileDepartment = document.getElementById('modalProfileDepartment');
    const modalProfileLastLogin = document.getElementById('modalProfileLastLogin');

    document.querySelectorAll('[data-open-profile]').forEach((btn) => {
      btn.addEventListener('click', () => {
        modalProfileName.textContent = btn.dataset.name || 'Employee';
        modalProfileEmail.textContent = btn.dataset.email || '—';
        modalProfileRole.textContent = btn.dataset.role || '—';
        modalProfileStatus.textContent = btn.dataset.status || '—';
        modalProfilePosition.textContent = btn.dataset.position || '—';
        modalProfileDepartment.textContent = btn.dataset.department || '—';
        modalProfileLastLogin.textContent = btn.dataset.lastlogin || '—';
        profileModal.classList.remove('hidden');
      });
    });

    closeProfileModal.addEventListener('click', () => profileModal.classList.add('hidden'));
    profileModal.addEventListener('click', (e) => {
      if (e.target === profileModal) profileModal.classList.add('hidden');
    });
  </script>
</body>
</html>