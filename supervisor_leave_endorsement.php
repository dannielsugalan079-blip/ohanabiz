<?php
require_once __DIR__ . '/config/auth.php';
requireSupervisorLogin();
$user = currentUser('supervisor');
$unread_count = unreadNotificationCount($pdo, (int) $user['id']);
$flash = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $leave_id = (int) ($_POST['leave_id'] ?? 0);
    $remarks = trim($_POST['remarks'] ?? '');

    if ($leave_id > 0 && in_array($action, ['endorse', 'reject'], true)) {
        $row = $pdo->prepare('SELECT lr.*, u.full_name FROM leave_requests lr JOIN users u ON lr.user_id = u.id WHERE lr.id = ? LIMIT 1');
        $row->execute([$leave_id]);
        $leave = $row->fetch();

        if (!$leave || $leave['status'] !== 'pending') {
            $error_msg = 'That leave request is no longer pending endorsement.';
        } else {
            try {
                if ($action === 'endorse') {
                    $pdo->prepare('UPDATE leave_requests SET status = ?, endorsed_by = ?, remarks = ? WHERE id = ?')
                        ->execute(['endorsed', $user['id'], $remarks !== '' ? $remarks : 'Endorsed for admin approval', $leave_id]);
                    $msg = $leave['full_name'] . ' — ' . formatLeaveType($leave['leave_type'])
                        . ' (' . date('M j', strtotime($leave['date_from'])) . ' – ' . date('M j, Y', strtotime($leave['date_to'])) . ') endorsed for admin approval.';
                    notifyUsersByRole($pdo, 'admin', 'Leave endorsed — final approval needed', $msg, 'request', 'admin_leave_approvals.php');
                    createNotification($pdo, (int) $leave['user_id'], 'Leave endorsed', 'Your supervisor endorsed your leave request. Awaiting admin approval.', 'success', 'employee_calendar.php');
                    $flash = 'Leave request endorsed and sent to admin.';
                } else {
                    $pdo->prepare('UPDATE leave_requests SET status = ?, endorsed_by = ?, remarks = ? WHERE id = ?')
                        ->execute(['rejected', $user['id'], $remarks !== '' ? $remarks : 'Returned by supervisor', $leave_id]);
                    createNotification($pdo, (int) $leave['user_id'], 'Leave request returned', 'Your leave request was not endorsed. Check Calendar & Leaves for details.', 'warning', 'employee_calendar.php');
                    $flash = 'Leave request was returned to the employee.';
                }
                header('Location: supervisor_leave_endorsement.php?ok=1');
                exit;
            } catch (PDOException $e) {
                error_log('[OHANA LEAVE ENDORSE] ' . $e->getMessage());
                $error_msg = 'Unable to update the leave request.';
            }
        }
    }
}

if (isset($_GET['ok'])) {
    $flash = 'Leave workflow updated.';
}

$pending_leaves = fetchLeaveRequests($pdo, ['pending']);
$endorsed_leaves = fetchLeaveRequests($pdo, ['endorsed']);
$rejected_leaves = fetchLeaveRequests($pdo, ['rejected']);
$tab = $_GET['tab'] ?? 'pending';
if (!in_array($tab, ['pending', 'endorsed', 'rejected', 'all'], true)) {
    $tab = 'pending';
}
$visible = $pending_leaves;
if ($tab === 'endorsed') {
    $visible = $endorsed_leaves;
} elseif ($tab === 'rejected') {
    $visible = $rejected_leaves;
} elseif ($tab === 'all') {
    $visible = fetchLeaveRequests($pdo);
}

$urgent_pending = 0;
foreach ($pending_leaves as $lr) {
    if ($lr['leave_type'] === 'emergency') {
        $urgent_pending++;
    }
}
$days_month = (int) $pdo->query(
    "SELECT COALESCE(SUM(total_days), 0) FROM leave_requests
     WHERE status IN ('endorsed','approved')
       AND MONTH(date_from) = MONTH(CURDATE()) AND YEAR(date_from) = YEAR(CURDATE())"
)->fetchColumn();
$members_on_leave = (int) $pdo->query(
    "SELECT COUNT(DISTINCT user_id) FROM leave_requests
     WHERE status IN ('endorsed','approved')
       AND MONTH(date_from) = MONTH(CURDATE()) AND YEAR(date_from) = YEAR(CURDATE())"
)->fetchColumn();
$decided = (int) $pdo->query(
    "SELECT COUNT(*) FROM leave_requests WHERE status IN ('endorsed','approved','rejected')
     AND YEAR(created_at) = YEAR(CURDATE()) AND QUARTER(created_at) = QUARTER(CURDATE())"
)->fetchColumn();
$positive = (int) $pdo->query(
    "SELECT COUNT(*) FROM leave_requests WHERE status IN ('endorsed','approved')
     AND YEAR(created_at) = YEAR(CURDATE()) AND QUARTER(created_at) = QUARTER(CURDATE())"
)->fetchColumn();
$endorsement_rate = $decided > 0 ? number_format(($positive / $decided) * 100, 1) : '100.0';
$emp_count = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'employee' AND status = 'active'")->fetchColumn();
$capacity = $emp_count > 0 ? max(55, 100 - (int) min(40, $days_month * 2)) : 100;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Ohana System - Leave Endorsement</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <style>
    body {
      font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
    }
  </style>
</head>
<body class="bg-[#FAF7F2] text-gray-800 antialiased flex min-h-screen">

  <!-- SIDEBAR NAVIGATION -->
  <aside class="w-64 bg-[#FAF7F2] border-r border-amber-900/10 flex flex-col justify-between p-6 shrink-0 sticky top-0 h-screen overflow-y-auto">
    <div>
      <!-- Logo / Header -->
      <a href="supervisor_dashboard.php" class="flex items-center gap-3 mb-8 group">
        <div class="w-10 h-10 rounded-xl bg-white p-1 border border-amber-900/10 shadow-xs flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
          <img src="assets/images/ohana.png" alt="OHANA Logo" class="w-full h-full object-contain">
        </div>
        <div>
          <h1 class="font-bold text-gray-900 leading-none text-base">Ohana System</h1>
          <span class="text-[10px] tracking-wider text-gray-500 font-semibold uppercase">SUPERVISOR SUITE</span>
        </div>
      </a>

      <!-- Navigation Links -->
      <div class="space-y-1">
        <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider block mb-2 px-3">CORE MODULES</span>
        
        <a href="supervisor_dashboard.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-gray-600 hover:bg-black/5 text-sm font-medium transition">
          <i class="fa-solid fa-border-all text-base"></i>
          Operations Dashboard
        </a>
        
        <a href="supervisor_dispatch.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-gray-600 hover:bg-black/5 text-sm font-medium transition">
          <i class="fa-solid fa-list-check text-base"></i>
          Dispatch & Tasks
        </a>

        <a href="supervisor_team_attendance.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-gray-600 hover:bg-black/5 text-sm font-medium transition">
          <i class="fa-regular fa-address-book text-base"></i>
          Team Attendance
        </a>

        <a href="supervisor_leave_endorsement.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl bg-[#2D5A43] text-white text-sm font-medium shadow-sm">
          <i class="fa-regular fa-calendar-check text-base"></i>
          Leave Endorsement
        </a>

        <a href="supervisor_settings.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-gray-600 hover:bg-black/5 text-sm font-medium transition">
          <i class="fa-solid fa-user-gear text-base"></i>
          Settings & Profile
        </a>
      </div>
    </div>

    <!-- Sidebar Footer -->
    <div class="space-y-4">
      <a href="logout.php?portal=supervisor" class="flex items-center gap-3 px-3 py-2 text-gray-600 hover:text-gray-900 text-sm font-medium">
        <i class="fa-solid fa-arrow-right-from-bracket rotate-180"></i>
        Log Out
      </a>

      <div class="bg-amber-900/5 p-3 rounded-2xl border border-amber-900/10 flex items-center gap-2">
        <span class="w-2 h-2 rounded-full bg-emerald-600"></span>
        <span class="text-[11px] text-gray-600 font-medium">Core Nodes Synchronized</span>
      </div>
    </div>
  </aside>

  <!-- MAIN CONTENT AREA -->
  <main class="flex-1 flex flex-col min-w-0">
    
    <!-- TOP NAVBAR -->
    <header class="h-20 border-b border-amber-900/10 flex items-center justify-between px-8 bg-[#FAF7F2] sticky top-0 z-20">
      <!-- Search Bar -->
      <div class="relative w-96">
        <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
        <input type="text" placeholder="Search leave requests, endorsees, staff..." class="w-full bg-white/60 border border-gray-200/80 rounded-full pl-10 pr-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#2D5A43]/20 focus:border-[#2D5A43]">
      </div>

      <!-- Right Actions -->
      <div class="flex items-center gap-4">
        <a href="supervisor_shift_notifications.php" class="w-10 h-10 rounded-xl border border-gray-200 flex items-center justify-center text-gray-600 hover:bg-white/80 relative">
          <i class="fa-regular fa-bell text-base"></i>
          <?php if ($unread_count > 0): ?>
          <span class="absolute -top-1 -right-1 min-w-[16px] h-4 px-1 rounded-full bg-orange-500 text-white text-[9px] font-bold flex items-center justify-center"><?= (int) $unread_count ?></span>
          <?php endif; ?>
        </a>

        <!-- User Profile -->
        <div class="flex items-center gap-3 pl-2 border-l border-gray-200">
          <div class="w-9 h-9 rounded-full bg-[#2D5A43] text-white flex items-center justify-center font-bold text-xs shadow-xs">
            <?= htmlspecialchars($user['initials']) ?>
          </div>
          <div class="text-left">
            <h4 class="text-sm font-bold text-gray-900 leading-none"><?= htmlspecialchars($user['full_name']) ?></h4>
            <span class="text-xs text-gray-500"><?= htmlspecialchars($user['position'] ?? 'Operations Supervisor') ?></span>
          </div>
        </div>
      </div>
    </header>

    <!-- CONTENT BODY -->
    <div class="p-8 space-y-6 overflow-y-auto">
      <?php if ($flash && !isset($_GET['ok'])): ?>
      <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs rounded-xl px-4 py-2"><?= htmlspecialchars($flash) ?></div>
      <?php elseif (isset($_GET['ok'])): ?>
      <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs rounded-xl px-4 py-2">Leave workflow updated.</div>
      <?php endif; ?>
      <?php if ($error_msg): ?>
      <div class="bg-red-50 border border-red-200 text-red-700 text-xs rounded-xl px-4 py-2"><?= htmlspecialchars($error_msg) ?></div>
      <?php endif; ?>
      
      <!-- Header Title & Action -->
      <div class="flex items-start justify-between">
        <div>
          <span class="inline-flex items-center gap-1.5 text-gray-500 text-[10px] font-bold tracking-wider uppercase mb-1">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
            SUPERVISOR WORKFLOW • LEAVE ENDORSEMENT
          </span>
          <h2 class="text-2xl font-bold text-gray-900 leading-tight">Leave Endorsement</h2>
          <p class="text-xs text-gray-500 mt-0.5">Review, endorse, or escalate team member leave applications before HR and admin final clearance.</p>
        </div>

        <button class="bg-[#2D5A43] hover:bg-[#234734] text-white px-4 py-2 rounded-xl text-xs font-semibold flex items-center gap-2 shadow-sm transition">
          <i class="fa-solid fa-list-check text-xs"></i> Batch Review
        </button>
      </div>

      <!-- TOP METRIC CARDS -->
      <div class="grid grid-cols-4 gap-5">
        
        <!-- Card 1 -->
        <div class="bg-white/90 border border-amber-900/10 rounded-2xl p-5 shadow-sm space-y-3">
          <div class="flex items-center justify-between">
            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">PENDING SUPERVISOR ACTION</span>
            <div class="w-8 h-8 rounded-full bg-emerald-100/60 text-emerald-800 flex items-center justify-center text-xs">
              <i class="fa-regular fa-clipboard"></i>
            </div>
          </div>
          <div class="flex items-baseline gap-2">
            <span class="text-3xl font-black text-gray-900"><?= count($pending_leaves) ?></span>
          </div>
          <p class="text-[11px] text-gray-400 font-semibold flex items-center gap-1">
            <i class="fa-solid fa-bolt text-xs"></i> <?= (int) $urgent_pending ?> urgent emergency
          </p>
        </div>

        <!-- Card 2 -->
        <div class="bg-white/90 border border-amber-900/10 rounded-2xl p-5 shadow-sm space-y-3">
          <div class="flex items-center justify-between">
            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">UNIT CAPACITY IMPACT</span>
            <div class="w-8 h-8 rounded-full bg-amber-100/60 text-amber-800 flex items-center justify-center text-xs">
              <i class="fa-solid fa-chart-simple"></i>
            </div>
          </div>
          <div class="flex items-baseline gap-2">
            <span class="text-3xl font-black text-gray-900"><?= (int) $capacity ?>%</span>
          </div>
          <p class="text-[11px] text-gray-400 font-medium flex items-center gap-1">
            <i class="fa-regular fa-circle-check text-emerald-600"></i> <?= $emp_count ?> specialists on roster
          </p>
        </div>

        <!-- Card 3 -->
        <div class="bg-white/90 border border-amber-900/10 rounded-2xl p-5 shadow-sm space-y-3">
          <div class="flex items-center justify-between">
            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">LEAVES THIS MONTH</span>
            <div class="w-8 h-8 rounded-full bg-amber-100/60 text-amber-800 flex items-center justify-center text-xs">
              <i class="fa-regular fa-calendar font-semibold"></i>
            </div>
          </div>
          <div class="flex items-baseline gap-2">
            <span class="text-3xl font-black text-gray-900"><?= (int) $days_month ?> days</span>
          </div>
          <p class="text-[11px] text-gray-400 font-medium">Across <?= (int) $members_on_leave ?> team members</p>
        </div>

        <!-- Card 4 -->
        <div class="bg-white/90 border border-amber-900/10 rounded-2xl p-5 shadow-sm space-y-3">
          <div class="flex items-center justify-between">
            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">ENDORSEMENT RATE</span>
            <div class="w-8 h-8 rounded-full bg-emerald-100/60 text-emerald-800 flex items-center justify-center text-xs">
              <i class="fa-solid fa-shield"></i>
            </div>
          </div>
          <div class="flex items-baseline gap-2">
            <span class="text-3xl font-black text-gray-900"><?= htmlspecialchars($endorsement_rate) ?>%</span>
          </div>
          <p class="text-[11px] text-gray-400 font-medium">Projected this quarter</p>
        </div>

      </div>

      <!-- FILTER TABS & DROPDOWNS -->
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-2">
          <a href="supervisor_leave_endorsement.php?tab=pending" class="<?= $tab === 'pending' ? 'bg-[#2D5A43] text-white shadow-sm' : 'bg-white/60 hover:bg-white text-gray-600' ?> px-4 py-2 rounded-xl text-xs font-semibold flex items-center gap-2 transition">
            Pending Endorsement <span class="<?= $tab === 'pending' ? 'bg-white/20 text-white' : 'bg-gray-200 text-gray-600' ?> px-1.5 py-0.5 rounded-full text-[10px]"><?= count($pending_leaves) ?></span>
          </a>
          <a href="supervisor_leave_endorsement.php?tab=endorsed" class="<?= $tab === 'endorsed' ? 'bg-[#2D5A43] text-white shadow-sm' : 'bg-white/60 hover:bg-white text-gray-600' ?> px-4 py-2 rounded-xl text-xs font-semibold flex items-center gap-2 transition">
            Endorsed to Admin <span class="<?= $tab === 'endorsed' ? 'bg-white/20 text-white' : 'bg-gray-200 text-gray-600' ?> px-1.5 py-0.5 rounded-full text-[10px]"><?= count($endorsed_leaves) ?></span>
          </a>
          <a href="supervisor_leave_endorsement.php?tab=rejected" class="<?= $tab === 'rejected' ? 'bg-[#2D5A43] text-white shadow-sm' : 'bg-white/60 hover:bg-white text-gray-600' ?> px-4 py-2 rounded-xl text-xs font-semibold flex items-center gap-2 transition">
            Returned / Rejected <span class="<?= $tab === 'rejected' ? 'bg-white/20 text-white' : 'bg-gray-200 text-gray-600' ?> px-1.5 py-0.5 rounded-full text-[10px]"><?= count($rejected_leaves) ?></span>
          </a>
          <a href="supervisor_leave_endorsement.php?tab=all" class="text-xs text-gray-500 font-semibold ml-2 hover:underline">All Requests</a>
        </div>

        <div class="flex items-center gap-3">
          <div class="relative">
            <select class="bg-white/80 border border-gray-200 rounded-xl px-3.5 py-1.5 text-xs text-gray-700 appearance-none pr-8 focus:outline-none">
              <option>All Supervisor Units (4)</option>
            </select>
            <i class="fa-solid fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-[10px]"></i>
          </div>

          <div class="relative">
            <select class="bg-white/80 border border-gray-200 rounded-xl px-3.5 py-1.5 text-xs text-gray-700 appearance-none pr-8 focus:outline-none">
              <option>Newest First</option>
            </select>
            <i class="fa-solid fa-arrow-down-short-wide absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-[10px]"></i>
          </div>
        </div>
      </div>

      <!-- LEAVE REQUEST CARDS CONTAINER -->
      <div class="space-y-6">
        <?php if (!$visible): ?>
        <div class="text-center py-12 text-gray-400">
          <i class="fa-regular fa-calendar-check text-4xl mb-3 block"></i>
          <h3 class="text-sm font-semibold text-gray-600 mb-1">No Leave Requests</h3>
          <p class="text-xs">There are no leave records in this view at this time.</p>
        </div>
        <?php else: ?>
        <?php foreach ($visible as $lr): ?>
        <div class="bg-white/90 border border-amber-900/10 rounded-2xl p-5 shadow-sm space-y-4">
          <div class="flex items-start justify-between gap-4">
            <div>
              <h3 class="text-sm font-bold text-gray-900"><?= htmlspecialchars($lr['full_name']) ?></h3>
              <p class="text-[11px] text-gray-500"><?= htmlspecialchars($lr['position'] ?: 'Operations Specialist') ?> • <?= htmlspecialchars($lr['department'] ?: 'Field Unit') ?></p>
            </div>
            <span class="text-[10px] font-bold uppercase tracking-wide px-2.5 py-1 rounded-full <?= $lr['status'] === 'pending' ? 'bg-amber-100 text-amber-900' : ($lr['status'] === 'endorsed' ? 'bg-emerald-100 text-emerald-800' : ($lr['status'] === 'approved' ? 'bg-emerald-100 text-emerald-800' : 'bg-red-50 text-red-700')) ?>">
              <?= htmlspecialchars($lr['status']) ?>
            </span>
          </div>
          <div class="flex flex-wrap items-center gap-3 text-xs text-gray-600">
            <span class="font-semibold text-gray-800"><?= htmlspecialchars(formatLeaveType($lr['leave_type'])) ?></span>
            <span><?= date('M j, Y', strtotime($lr['date_from'])) ?> – <?= date('M j, Y', strtotime($lr['date_to'])) ?></span>
            <span class="text-gray-400"><?= (int) $lr['total_days'] ?> day(s)</span>
          </div>
          <p class="text-xs text-gray-600 bg-[#FAF7F2] rounded-xl p-3 border border-amber-900/5"><?= htmlspecialchars($lr['reason']) ?></p>
          <?php if ($lr['status'] === 'pending'): ?>
          <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-3 items-end">
            <input type="hidden" name="leave_id" value="<?= (int) $lr['id'] ?>">
            <div class="md:col-span-2">
              <label class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Remarks (optional)</label>
              <input type="text" name="remarks" placeholder="Coverage notes or reason for return" class="mt-1 w-full bg-[#FAF7F2] border border-gray-200 rounded-xl px-3 py-2 text-xs">
            </div>
            <button type="submit" name="action" value="reject" class="py-2 bg-gray-200/80 hover:bg-gray-300 text-gray-800 text-xs font-bold rounded-xl transition">Return / Reject</button>
            <button type="submit" name="action" value="endorse" class="py-2 bg-[#2D5A43] hover:bg-[#234734] text-white text-xs font-bold rounded-xl shadow-sm transition">Endorse to Admin</button>
          </form>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
      </div>

    </div>
  </main>

</body>
</html>