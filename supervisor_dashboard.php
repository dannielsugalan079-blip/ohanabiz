<?php
require_once __DIR__ . '/config/auth.php';
requireSupervisorLogin();
$user = currentUser('supervisor');

// Dashboard real metrics
$total_active_tasks = (int)$pdo->query("SELECT COUNT(*) FROM tasks WHERE status IN ('pending','in_progress','for_review')")->fetchColumn();
$pending_requests = (int)$pdo->query("SELECT COUNT(*) FROM service_requests WHERE status = 'pending'")->fetchColumn();
$completed_today = (int)$pdo->query("SELECT COUNT(*) FROM tasks WHERE status = 'completed' AND DATE(updated_at) = CURDATE()")->fetchColumn();
$total_employees = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'employee' AND status = 'active'")->fetchColumn();
$present_today = (int)$pdo->query("SELECT COUNT(DISTINCT user_id) FROM attendance WHERE date = CURDATE()")->fetchColumn();
$attendance_pct = $total_employees > 0 ? round(($present_today / $total_employees) * 100) : 0;
$pending_leaves = (int)$pdo->query("SELECT COUNT(*) FROM leave_requests WHERE status = 'pending'")->fetchColumn();
$approved_leaves_today = (int)$pdo->query("SELECT COUNT(*) FROM leave_requests WHERE status = 'approved' AND CURDATE() BETWEEN date_from AND date_to")->fetchColumn();
$unread_count = unreadNotificationCount($pdo, (int) $user['id']);
$pending_leave_rows = fetchLeaveRequests($pdo, ['pending']);
$pending_approvals_total = $pending_requests + $pending_leaves;

// Urgent/high priority tasks
$urgent_tasks = $pdo->query("
    SELECT t.*, u.full_name as assignee_name 
    FROM tasks t 
    LEFT JOIN users u ON t.assigned_to = u.id 
    WHERE t.status IN ('pending','in_progress') AND t.priority IN ('high','critical') 
    ORDER BY t.created_at DESC LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Ohana System - Operations Dashboard</title>
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
        
        <a href="supervisor_dashboard.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl bg-[#2D5A43] text-white text-sm font-medium shadow-sm">
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

        <a href="supervisor_leave_endorsement.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-gray-600 hover:bg-black/5 text-sm font-medium transition">
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
        <input type="text" placeholder="Search personnel, tasks, or manifests..." class="w-full bg-white/60 border border-gray-200/80 rounded-full pl-10 pr-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#2D5A43]/20 focus:border-[#2D5A43]">
      </div>

      <!-- Right Actions -->
      <div class="flex items-center gap-4">
        <a href="supervisor_assign_new_task.php" class="bg-[#B85D1B] hover:bg-[#a04f15] text-white px-5 py-2.5 rounded-xl text-sm font-medium flex items-center gap-2 shadow-sm transition">
          <i class="fa-solid fa-plus-circle text-xs"></i>
          + New Dispatch
        </a>

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
      
      <!-- Welcome Header & Filter -->
      <div class="flex items-start justify-between">
        <div>
          <span class="inline-flex items-center gap-1.5 text-gray-500 text-[10px] font-bold tracking-wider uppercase mb-1">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
            SYSTEM SYNCHRONIZED
          </span>
          <h2 class="text-2xl font-bold text-gray-900 leading-tight">Welcome back, <?= htmlspecialchars($user["full_name"]) ?></h2>
        </div>

        <!-- Filter Dropdown -->
        <div class="flex items-center gap-2">
          <div class="bg-white/80 border border-gray-200 rounded-xl px-3 py-1.5 text-xs text-gray-600 font-medium flex items-center gap-3 shadow-sm cursor-pointer">
            <span>Department: <strong class="text-gray-900">All</strong></span>
            <i class="fa-solid fa-sliders text-gray-400 text-xs"></i>
          </div>
        </div>
      </div>

      <!-- TOP METRIC CARDS -->
      <div class="grid grid-cols-4 gap-5">
        
        <!-- Card 1: Total Active Tasks -->
        <div class="bg-white/90 border border-amber-900/10 rounded-2xl p-5 shadow-sm space-y-3">
          <div class="flex items-center justify-between">
            <span class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">TOTAL ACTIVE TASKS</span>
            <div class="w-7 h-7 rounded-lg bg-gray-100 flex items-center justify-center text-gray-600 text-xs">
              <i class="fa-regular fa-square-check"></i>
            </div>
          </div>
          <div class="flex items-baseline gap-2">
            <span class="text-3xl font-black text-gray-900"><?= $total_active_tasks ?></span>
            <span class="bg-amber-100 text-amber-900 text-[10px] font-bold px-2 py-0.5 rounded-full flex items-center gap-1">
              <i class="fa-solid fa-arrow-trend-up text-[8px]"></i>
              +12%
            </span>
          </div>
          <p class="text-[11px] text-gray-400 font-medium">Across 6 tactical workflows this week</p>
        </div>

        <!-- Card 2: Pending Approvals -->
        <div class="bg-white/90 border border-amber-900/10 rounded-2xl p-5 shadow-sm space-y-3">
          <div class="flex items-center justify-between">
            <span class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">PENDING APPROVALS</span>
            <div class="w-7 h-7 rounded-lg bg-amber-100/70 text-amber-800 flex items-center justify-center text-xs">
              <i class="fa-regular fa-bell"></i>
            </div>
          </div>
          <div class="flex items-baseline gap-2">
            <span class="text-3xl font-black text-gray-900"><?= $pending_approvals_total ?></span>
            <span class="bg-amber-100 text-amber-800 text-[10px] font-bold px-2 py-0.5 rounded-full">
              Immediate Review
            </span>
          </div>
          <p class="text-[11px] text-gray-400 font-medium"><?= (int) $pending_leaves ?> leave requests • <?= (int) $pending_requests ?> service requests</p>
        </div>

        <!-- Card 3: Completed Today -->
        <div class="bg-white/90 border border-amber-900/10 rounded-2xl p-5 shadow-sm space-y-3">
          <div class="flex items-center justify-between">
            <span class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">COMPLETED TODAY</span>
            <div class="w-7 h-7 rounded-lg bg-emerald-100 text-emerald-800 flex items-center justify-center text-xs">
              <i class="fa-regular fa-circle-check"></i>
            </div>
          </div>
          <div class="flex items-baseline gap-2">
            <span class="text-3xl font-black text-gray-900"><?= $completed_today ?></span>
            <span class="bg-emerald-100 text-emerald-800 text-[10px] font-bold px-2 py-0.5 rounded-full">
              88% Target
            </span>
          </div>
          <p class="text-[11px] text-gray-400 font-medium">+4 tasks resolved in the last hour</p>
        </div>

        <!-- Card 4: Staff Attendance -->
        <div class="bg-white/90 border border-amber-900/10 rounded-2xl p-5 shadow-sm space-y-3">
          <div class="flex items-center justify-between">
            <span class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">STAFF ATTENDANCE</span>
            <div class="w-7 h-7 rounded-lg bg-gray-100 flex items-center justify-center text-gray-600 text-xs">
              <i class="fa-solid fa-users"></i>
            </div>
          </div>
          <div class="flex items-baseline gap-2">
            <span class="text-3xl font-black text-gray-900"><?= $attendance_pct ?>%</span>
            <span class="text-xs text-gray-500 font-medium"><?= $present_today ?> / <?= $total_employees ?> present</span>
          </div>
          <p class="text-[11px] text-gray-400 font-medium"><?= (int) $approved_leaves_today ?> planned leaves • roster live</p>
        </div>

      </div>

      <!-- URGENT TASKS & ESCALATIONS -->
      <div class="bg-white/90 border border-amber-900/10 rounded-2xl p-6 shadow-sm space-y-4">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-3">
            <span class="w-2 h-2 rounded-full bg-red-500"></span>
            <h3 class="text-sm font-bold text-gray-900">Urgent Tasks & Escalations</h3>
            <span class="bg-orange-100 text-orange-800 text-[10px] font-bold px-2.5 py-0.5 rounded-full">High Priority</span>
          </div>
          <a href="#" class="text-xs font-semibold text-gray-500 hover:text-gray-800 transition">View All Tasks (<?= $total_active_tasks ?>)</a>
        </div>

        <div class="space-y-3">
<?php if (empty($urgent_tasks)): ?>
  <div class="text-center py-8 text-gray-400">
    <i class="fa-regular fa-circle-check text-2xl mb-2 block"></i>
    <p class="text-sm font-medium">No urgent tasks at this time. All workflows are on track.</p>
  </div>
<?php else: ?>
  <?php foreach ($urgent_tasks as $task): ?>
    <div class="bg-[#FAF7F2] p-4 rounded-xl border border-amber-900/5 flex items-center justify-between">
      <div class="flex items-start gap-3.5">
        <div class="w-8 h-8 rounded-lg bg-amber-100/80 text-amber-800 flex items-center justify-center text-xs mt-0.5 shrink-0">
          <i class="fa-regular fa-file-lines"></i>
        </div>
        <div class="space-y-1">
          <div class="flex items-center gap-2">
            <h4 class="text-xs font-bold text-gray-900"><?= htmlspecialchars($task['title']) ?></h4>
            <?php if ($task['priority'] === 'critical'): ?>
              <span class="bg-red-100 text-red-800 text-[9px] font-bold px-2 py-0.5 rounded">Critical</span>
            <?php else: ?>
              <span class="bg-orange-100 text-orange-800 text-[9px] font-bold px-2 py-0.5 rounded">High Priority</span>
            <?php endif; ?>
          </div>
          <p class="text-[11px] text-gray-500"><?= htmlspecialchars(mb_substr($task['description'] ?? 'No description provided.', 0, 120)) ?></p>
          <div class="flex items-center gap-2 text-[11px] text-gray-600 pt-1">
            <div class="w-4 h-4 rounded-full bg-[#2D5A43] text-white flex items-center justify-center text-[9px] font-bold"><?= strtoupper(substr($task['assignee_name'] ?? 'U', 0, 1)) ?></div>
            <span class="font-bold text-gray-800"><?= htmlspecialchars($task['assignee_name'] ?? 'Unassigned') ?></span>
            <?php if (!empty($task['due_date'])): ?>
              <span class="text-gray-300">•</span>
              <span class="text-gray-500">Due: <?= date('M d, Y', strtotime($task['due_date'])) ?></span>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <div class="flex items-center gap-2 shrink-0 ml-4">
        <a href="supervisor_assign_new_task.php" class="px-3.5 py-1.5 rounded-lg text-xs font-semibold text-gray-600 bg-gray-200/60 hover:bg-gray-200 transition">Reassign</a>
        <a href="supervisor_dispatch.php" class="px-3.5 py-1.5 rounded-lg text-xs font-semibold text-white bg-[#2D5A43] hover:bg-[#234734] transition">Review</a>
      </div>
    </div>
  <?php endforeach; ?>
<?php endif; ?>
        </div>
      </div>

      <!-- QUICK ACTIONS -->
      <div class="space-y-3">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-2">
            <i class="fa-solid fa-bolt text-amber-700 text-xs"></i>
            <h3 class="text-sm font-bold text-gray-900">Quick Actions</h3>
          </div>
          <span class="text-xs text-gray-400">Operational Workflows</span>
        </div>

        <div class="grid grid-cols-3 gap-5">
          <!-- Action 1 -->
          <a href="supervisor_assign_new_task.php" class="bg-[#FAF7F2] border border-amber-900/10 hover:border-amber-900/20 p-4 rounded-2xl text-left flex items-center justify-between group transition shadow-sm bg-white/60">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-gray-200/80 text-gray-700 flex items-center justify-center text-sm">
                <i class="fa-solid fa-user-plus"></i>
              </div>
              <div>
                <h4 class="text-xs font-bold text-gray-900">Assign New Task</h4>
                <p class="text-[10px] text-gray-500 mt-0.5">Dispatch ticket to operational...</p>
              </div>
            </div>
            <i class="fa-solid fa-chevron-right text-gray-400 text-xs group-hover:translate-x-1 transition"></i>
          </a>

          <!-- Action 2 -->
          <a href="supervisor_dispatch.php" class="bg-[#FAF7F2] border border-amber-900/10 hover:border-amber-900/20 p-4 rounded-2xl text-left flex items-center justify-between group transition shadow-sm bg-white/60">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-900 flex items-center justify-center text-sm">
                <i class="fa-solid fa-file-invoice"></i>
              </div>
              <div>
                <h4 class="text-xs font-bold text-gray-900">Generate Weekly Report</h4>
                <p class="text-[10px] text-gray-500 mt-0.5">Compile metrics for executive...</p>
              </div>
            </div>
            <i class="fa-solid fa-chevron-right text-gray-400 text-xs group-hover:translate-x-1 transition"></i>
          </a>

          <!-- Action 3 -->
          <button class="bg-[#FAF7F2] border border-amber-900/10 hover:border-amber-900/20 p-4 rounded-2xl text-left flex items-center justify-between group transition shadow-sm bg-white/60">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-amber-800/10 text-amber-900 flex items-center justify-center text-sm">
                <i class="fa-solid fa-bullhorn"></i>
              </div>
              <div>
                <h4 class="text-xs font-bold text-gray-900">Broadcast Announcement</h4>
                <p class="text-[10px] text-gray-500 mt-0.5">Push bulletin to all 30 field...</p>
              </div>
            </div>
            <i class="fa-solid fa-chevron-right text-gray-400 text-xs group-hover:translate-x-1 transition"></i>
          </button>
        </div>
      </div>

      <!-- PENDING LEAVE APPROVALS -->
      <div class="space-y-3">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-2">
            <i class="fa-regular fa-calendar-minus text-amber-900 text-xs"></i>
            <h3 class="text-sm font-bold text-gray-900">Pending Leave Approvals</h3>
            <span class="w-5 h-5 rounded-full bg-amber-500 text-white text-[10px] font-bold flex items-center justify-center"><?= (int) $pending_leaves ?></span>
          </div>
          <a href="supervisor_leave_endorsement.php" class="text-xs font-semibold text-gray-500 hover:text-gray-800 transition">Open Leave Management Console →</a>
        </div>

        <p class="text-[11px] text-gray-400 -mt-2">Time-off requests submitted by team members requiring supervisor endorsement.</p>

        <?php if (!$pending_leave_rows): ?>
        <div class="bg-white/90 border border-amber-900/10 rounded-2xl p-8 shadow-sm text-center space-y-2">
          <i class="fa-regular fa-calendar-check text-3xl text-gray-300 block mb-1"></i>
          <h4 class="text-sm font-semibold text-gray-700">No Pending Leave Requests</h4>
          <p class="text-xs text-gray-400 max-w-sm mx-auto">All staff time-off requests have been reviewed and endorsed. No action needed.</p>
          <div class="pt-2">
            <a href="supervisor_leave_endorsement.php" class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#2D5A43] hover:underline">
              <span>Access Leave Endorsement Console</span>
              <i class="fa-solid fa-arrow-right text-[10px]"></i>
            </a>
          </div>
        </div>
        <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <?php foreach (array_slice($pending_leave_rows, 0, 4) as $lr): ?>
          <div class="bg-white/90 border border-amber-900/10 rounded-2xl p-5 shadow-sm space-y-2">
            <div class="flex items-start justify-between">
              <div>
                <h4 class="text-xs font-bold text-gray-900"><?= htmlspecialchars($lr['full_name']) ?></h4>
                <p class="text-[11px] text-gray-500"><?= htmlspecialchars(formatLeaveType($lr['leave_type'])) ?></p>
              </div>
              <span class="text-[10px] font-bold text-amber-800 bg-amber-100 px-2 py-0.5 rounded-full">Pending</span>
            </div>
            <p class="text-[11px] text-gray-500"><?= date('M j', strtotime($lr['date_from'])) ?> – <?= date('M j, Y', strtotime($lr['date_to'])) ?></p>
            <a href="supervisor_leave_endorsement.php" class="text-[11px] font-semibold text-[#2D5A43]">Review →</a>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>

    </div>
  </main>

</body>
</html>