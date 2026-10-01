<?php
require_once __DIR__ . '/config/auth.php';
requireSupervisorLogin();
$user = currentUser('supervisor');

// Team members count
$total_employees = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'employee' AND status = 'active'")->fetchColumn();

// Today's attendance records
$today_records = $pdo->query("
    SELECT a.*, u.full_name, u.position, u.department 
    FROM attendance a 
    JOIN users u ON a.user_id = u.id 
    WHERE a.date = CURDATE() 
    ORDER BY a.time_in DESC
")->fetchAll(PDO::FETCH_ASSOC);

$total_present = count($today_records);
$late_count = 0;
foreach ($today_records as $rec) {
    if (!empty($rec['status']) && $rec['status'] === 'late') {
        $late_count++;
    } elseif (!empty($rec['time_in']) && strtotime($rec['time_in']) > strtotime('09:00:00')) {
        $late_count++;
    }
}
$absent_count = max(0, $total_employees - $total_present);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Ohana System - Team Attendance</title>
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

        <a href="supervisor_team_attendance.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl bg-[#2D5A43] text-white text-sm font-medium shadow-sm">
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
        <input type="text" placeholder="Search attendance, staff, records..." class="w-full bg-white/60 border border-gray-200/80 rounded-full pl-10 pr-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#2D5A43]/20 focus:border-[#2D5A43]">
      </div>

      <!-- Right Actions -->
      <div class="flex items-center gap-4">
        <button class="bg-[#B85D1B] hover:bg-[#a04f15] text-white px-5 py-2.5 rounded-xl text-sm font-medium flex items-center gap-2 shadow-sm transition">
          <i class="fa-solid fa-plus-circle text-xs"></i>
          + New Dispatch
        </button>

        <button class="w-10 h-10 rounded-xl border border-gray-200 flex items-center justify-center text-gray-600 hover:bg-white/80 relative">
          <i class="fa-regular fa-bell text-base"></i>
          <span class="absolute top-2 right-2 w-2 h-2 rounded-full bg-orange-500"></span>
        </button>

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
      
      <!-- Welcome Header & Actions -->
      <div class="flex items-start justify-between">
        <div>
          <span class="inline-flex items-center gap-1.5 text-gray-500 text-[10px] font-bold tracking-wider uppercase mb-1">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
            SYSTEM SYNCHRONIZED
          </span>
          <h2 class="text-2xl font-bold text-gray-900 leading-tight">Team Attendance</h2>
          <p class="text-xs text-gray-500 mt-0.5">Review daily shift logs, clock-in records, duty stations, and attendance overrides.</p>
        </div>

        <!-- Buttons -->
        <div class="flex items-center gap-3">
          <button class="bg-white/80 border border-gray-200 text-gray-700 px-4 py-2 rounded-xl text-xs font-semibold flex items-center gap-2 shadow-sm hover:bg-white transition">
            <i class="fa-solid fa-download text-xs text-gray-500"></i> Export DTR
          </button>
          <button class="bg-[#2D5A43] hover:bg-[#234734] text-white px-4 py-2 rounded-xl text-xs font-semibold flex items-center gap-2 shadow-sm transition">
            <i class="fa-solid fa-plus text-xs"></i> + Manual Clock-In
          </button>
        </div>
      </div>

      <!-- TOP METRIC CARDS -->
      <div class="grid grid-cols-4 gap-5">
        
        <!-- Card 1: Total Present -->
        <div class="bg-white/90 border border-amber-900/10 rounded-2xl p-5 shadow-sm space-y-3">
          <div class="flex items-center justify-between">
            <span class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">TOTAL PRESENT</span>
            <div class="w-8 h-8 rounded-full bg-emerald-50 text-emerald-700 flex items-center justify-center text-xs">
              <i class="fa-solid fa-user-check"></i>
            </div>
          </div>
          <div class="flex items-baseline gap-2">
            <span class="text-3xl font-black text-gray-900"><?= $total_present ?></span>
            <span class="bg-emerald-100 text-emerald-800 text-[10px] font-bold px-2 py-0.5 rounded-full flex items-center gap-1">
              of <?= $total_employees ?> Active
            </span>
          </div>
          <div class="text-[11px] text-gray-400 font-medium flex justify-between">
            <span>Logged duty shifts today</span>
            <span class="text-gray-600 font-semibold"><?= $total_present ?> active</span>
          </div>
        </div>

        <!-- Card 2: Late / Delayed -->
        <div class="bg-white/90 border border-amber-900/10 rounded-2xl p-5 shadow-sm space-y-3">
          <div class="flex items-center justify-between">
            <span class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">LATE / DELAYED</span>
            <div class="w-8 h-8 rounded-full bg-amber-50 text-amber-800 flex items-center justify-center text-xs">
              <i class="fa-regular fa-clock"></i>
            </div>
          </div>
          <div class="flex items-baseline gap-2">
            <span class="text-3xl font-black text-gray-900"><?= $late_count ?></span>
            <span class="bg-amber-100 text-amber-800 text-[10px] font-bold px-2 py-0.5 rounded-full">
              Post-09:00 AM
            </span>
          </div>
          <p class="text-[11px] text-gray-400 font-medium">Logged after official call-time</p>
        </div>

        <!-- Card 3: On Leave Today -->
        <div class="bg-white/90 border border-amber-900/10 rounded-2xl p-5 shadow-sm space-y-3">
          <div class="flex items-center justify-between">
            <span class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">ON LEAVE TODAY</span>
            <div class="w-8 h-8 rounded-full bg-amber-50 text-amber-800 flex items-center justify-center text-xs">
              <i class="fa-regular fa-calendar-xmark"></i>
            </div>
          </div>
          <div class="flex items-baseline gap-2">
            <span class="text-3xl font-black text-gray-900">0</span>
            <span class="bg-gray-100 text-gray-700 text-[10px] font-bold px-2 py-0.5 rounded-full">
              Endorsed
            </span>
          </div>
          <p class="text-[11px] text-gray-400 font-medium">No approved leave today</p>
        </div>

        <!-- Card 4: Absent Today -->
        <div class="bg-white/90 border border-amber-900/10 rounded-2xl p-5 shadow-sm space-y-3">
          <div class="flex items-center justify-between">
            <span class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">UNACCOUNTED TODAY</span>
            <div class="w-8 h-8 rounded-full bg-red-50 text-red-700 flex items-center justify-center text-xs">
              <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
          </div>
          <div class="flex items-baseline gap-2">
            <span class="text-3xl font-black text-gray-900"><?= $absent_count ?></span>
            <span class="bg-red-100 text-red-800 text-[10px] font-bold px-2 py-0.5 rounded-full">
              Pending Log
            </span>
          </div>
          <div class="text-[11px] text-gray-400 font-medium flex justify-between">
            <span>Unscheduled / No time-in</span>
            <a href="#" class="text-red-700 font-bold hover:underline">Review Team →</a>
          </div>
        </div>

      </div>

      <!-- FILTER CONTROLS -->
      <div class="grid grid-cols-4 gap-4">
        <div class="relative col-span-1">
          <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
          <input type="text" placeholder="Filter by name, ID, duty station..." class="w-full bg-white/80 border border-gray-200 rounded-xl pl-9 pr-3 py-2 text-xs focus:outline-none focus:ring-1 focus:ring-[#2D5A43]">
        </div>

        <div class="relative">
          <select class="w-full bg-white/80 border border-gray-200 rounded-xl px-3.5 py-2 text-xs text-gray-700 appearance-none focus:outline-none focus:ring-1 focus:ring-[#2D5A43]">
            <option>Shift: Morning (06:00 - 14:00)</option>
            <option>Shift: Mid Shift (14:00 - 22:00)</option>
            <option>Shift: Night Shift (22:00 - 06:00)</option>
          </select>
          <i class="fa-solid fa-chevron-down absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-[10px] pointer-events-none"></i>
        </div>

        <div class="relative">
          <select class="w-full bg-white/80 border border-gray-200 rounded-xl px-3.5 py-2 text-xs text-gray-700 appearance-none focus:outline-none focus:ring-1 focus:ring-[#2D5A43]">
            <option>Status: All Logs</option>
            <option>Status: Active / On Duty</option>
            <option>Status: Late</option>
            <option>Status: On Leave</option>
          </select>
          <i class="fa-solid fa-chevron-down absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-[10px] pointer-events-none"></i>
        </div>

        <div class="bg-white/80 border border-gray-200 rounded-xl px-3.5 py-2 text-xs text-gray-700 flex items-center justify-between cursor-pointer">
          <div class="flex items-center gap-2">
            <i class="fa-regular fa-calendar text-gray-400"></i>
            <span>Today, Oct 24, 2025</span>
          </div>
        </div>
      </div>

      <!-- LIVE SHIFT ATTENDANCE LOGS TABLE -->
      <div class="bg-white/90 border border-amber-900/10 rounded-2xl p-6 shadow-sm space-y-4">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-2">
            <h3 class="text-sm font-bold text-gray-900">Live Shift Attendance Logs</h3>
            <span class="bg-emerald-100 text-emerald-800 text-[10px] font-bold px-2 py-0.5 rounded-full"><?= $total_present ?> / <?= $total_employees ?> Logged</span>
          </div>
          <div class="flex items-center gap-3 text-gray-400 text-xs">
            <button class="hover:text-gray-600"><i class="fa-solid fa-rotate"></i></button>
            <button class="hover:text-gray-600"><i class="fa-solid fa-table-columns"></i></button>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="text-[10px] font-bold text-gray-400 uppercase tracking-wider border-b border-gray-100">
                <th class="pb-3 font-semibold">EMPLOYEE</th>
                <th class="pb-3 font-semibold">SHIFT & DUTY STATION</th>
                <th class="pb-3 font-semibold">TIME IN</th>
                <th class="pb-3 font-semibold">TIME OUT</th>
                <th class="pb-3 font-semibold">STATUS</th>
                <th class="pb-3 font-semibold text-right">ACTIONS</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 text-xs">
              <?php if (empty($today_records)): ?>
                <tr>
                  <td colspan="6" class="py-12 text-center text-gray-400">
                    <i class="fa-regular fa-calendar-xmark text-3xl mb-2 block"></i>
                    <p class="font-semibold text-gray-600">No Attendance Records for Today</p>
                    <p class="text-[11px] mt-0.5">Personnel duty time-in logs will appear here in real-time as specialists check in.</p>
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($today_records as $rec): 
                  $is_late = (!empty($rec['status']) && $rec['status'] === 'late') || (!empty($rec['time_in']) && strtotime($rec['time_in']) > strtotime('09:00:00'));
                  $has_timed_out = !empty($rec['time_out']);
                ?>
                <tr class="hover:bg-[#FAF7F2]/50 transition">
                  <td class="py-4 pr-4">
                    <div class="flex items-center gap-3">
                      <div class="w-8 h-8 rounded-full bg-[#2D5A43] text-white flex items-center justify-center font-bold text-xs shrink-0">
                        <?= strtoupper(substr($rec['full_name'], 0, 1)) ?>
                      </div>
                      <div>
                        <h4 class="font-bold text-gray-900 leading-none"><?= htmlspecialchars($rec['full_name']) ?></h4>
                        <span class="text-[10px] text-gray-400">EMP-<?= str_pad((string)$rec['user_id'], 4, '0', STR_PAD_LEFT) ?> • <?= htmlspecialchars($rec['position'] ?? 'Operations Specialist') ?></span>
                      </div>
                    </div>
                  </td>
                  <td class="py-4 px-2">
                    <div>
                      <span class="font-bold text-gray-800 block leading-none">Regular Shift (08:00 - 17:00)</span>
                      <span class="text-[10px] text-gray-400"><?= htmlspecialchars($rec['department'] ?? 'Operations Hub') ?></span>
                    </div>
                  </td>
                  <td class="py-4 px-2">
                    <div>
                      <span class="font-bold <?= $is_late ? 'text-orange-600' : 'text-gray-900' ?> block leading-none">
                        <?= !empty($rec['time_in']) ? date('h:i A', strtotime($rec['time_in'])) : '—' ?>
                      </span>
                      <span class="text-[10px] text-gray-400 flex items-center gap-1">
                        <i class="fa-solid fa-clock text-[9px]"></i> <?= $is_late ? 'Late Log' : 'On-Time' ?>
                      </span>
                    </div>
                  </td>
                  <td class="py-4 px-2">
                    <?php if ($has_timed_out): ?>
                      <span class="font-bold text-gray-900"><?= date('h:i A', strtotime($rec['time_out'])) ?></span>
                    <?php else: ?>
                      <span class="text-gray-400 italic">-- In Progress</span>
                    <?php endif; ?>
                  </td>
                  <td class="py-4 px-2">
                    <?php if ($has_timed_out): ?>
                      <span class="bg-gray-100 text-gray-700 text-[10px] font-bold px-2.5 py-1 rounded-full inline-flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span> Shift Completed
                      </span>
                    <?php elseif ($is_late): ?>
                      <span class="bg-orange-100 text-orange-800 text-[10px] font-bold px-2.5 py-1 rounded-full inline-flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-orange-600"></span> Late Check-In
                      </span>
                    <?php else: ?>
                      <span class="bg-emerald-100 text-emerald-800 text-[10px] font-bold px-2.5 py-1 rounded-full inline-flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span> Active / On Duty
                      </span>
                    <?php endif; ?>
                  </td>
                  <td class="py-4 pl-2 text-right">
                    <div class="flex items-center justify-end gap-2">
                      <span class="text-xs text-gray-400"><?= date('M d', strtotime($rec['date'])) ?></span>
                    </div>
                  </td>
                </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div class="flex items-center justify-between pt-2 border-t border-gray-100">
          <span class="text-[11px] text-gray-400">Showing 1-5 of 48 logged shift personnel</span>
          <div class="flex items-center gap-1 text-xs font-semibold">
            <button class="w-6 h-6 rounded flex items-center justify-center text-gray-400 hover:bg-gray-100">&lt;</button>
            <button class="w-6 h-6 rounded bg-[#2D5A43] text-white flex items-center justify-center">1</button>
            <button class="w-6 h-6 rounded text-gray-600 hover:bg-gray-100 flex items-center justify-center">2</button>
            <button class="w-6 h-6 rounded text-gray-600 hover:bg-gray-100 flex items-center justify-center">3</button>
            <span class="px-1 text-gray-400">...</span>
            <button class="w-6 h-6 rounded text-gray-600 hover:bg-gray-100 flex items-center justify-center">10</button>
            <button class="w-6 h-6 rounded flex items-center justify-center text-gray-400 hover:bg-gray-100">&gt;</button>
          </div>
        </div>

      </div>

      <!-- ATTENDANCE OVERRIDES -->
      <div class="space-y-3">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-2">
            <div class="w-5 h-5 rounded-md bg-amber-100 text-amber-800 flex items-center justify-center text-[10px]">
              <i class="fa-solid fa-sliders"></i>
            </div>
            <h3 class="text-sm font-bold text-gray-900">Attendance Overrides</h3>
            <span class="bg-amber-100 text-amber-800 text-[10px] font-bold px-2 py-0.5 rounded-full">2 Pending</span>
          </div>
          <a href="#" class="text-xs font-semibold text-gray-500 hover:text-gray-800 transition">View Shift Attendance Audit Logs →</a>
        </div>

        <p class="text-[11px] text-gray-400 -mt-2">Supervisor clearance required for late arrivals and field clock-in anomalies prior to payroll cut-off.</p>

        <div class="grid grid-cols-2 gap-5">
          
          <!-- Card 1 -->
          <div class="bg-white/90 border border-amber-900/10 rounded-2xl p-5 shadow-sm space-y-3 border-l-4 border-l-amber-500">
            <div class="flex items-start justify-between">
              <div class="flex items-center gap-3">
                <img src="https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=100&auto=format&fit=crop&q=80" class="w-9 h-9 rounded-full object-cover">
                <div>
                  <h4 class="text-xs font-bold text-gray-900">Marcus Rodriguez</h4>
                  <span class="text-[10px] text-gray-400">Late Check-in Justification</span>
                </div>
              </div>
              <span class="text-[10px] text-gray-400">28m ago</span>
            </div>

            <div class="bg-[#FAF7F2] p-2.5 rounded-xl border border-amber-900/5 text-xs text-gray-600 flex items-center gap-2">
              <i class="fa-solid fa-triangle-exclamation text-amber-600 text-xs"></i>
              <span>Cause: <strong>Transit delay Sector 3 (+19m)</strong></span>
            </div>

            <div class="grid grid-cols-2 gap-3 pt-1">
              <button class="w-full py-2 rounded-xl text-xs font-semibold text-white bg-[#2D5A43] hover:bg-[#234734] transition">✓ Approve Override</button>
              <button class="w-full py-2 rounded-xl text-xs font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 transition">✕ Reject</button>
            </div>
          </div>

          <!-- Card 2 -->
          <div class="bg-white/90 border border-amber-900/10 rounded-2xl p-5 shadow-sm space-y-3 border-l-4 border-l-amber-500">
            <div class="flex items-start justify-between">
              <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-gray-200 text-gray-600 flex items-center justify-center font-bold text-xs">MC</div>
                <div>
                  <h4 class="text-xs font-bold text-gray-900">Michael Chang</h4>
                  <span class="text-[10px] text-gray-400">Manual Time-In Request</span>
                </div>
              </div>
              <span class="text-[10px] text-gray-400">1h ago</span>
            </div>

            <div class="bg-[#FAF7F2] p-2.5 rounded-xl border border-amber-900/5 text-xs text-gray-600 flex items-center gap-2">
              <i class="fa-solid fa-wifi text-amber-600 text-xs"></i>
              <span>Cause: <strong>Field dispatch offline zone (06:05 AM)</strong></span>
            </div>

            <div class="grid grid-cols-2 gap-3 pt-1">
              <button class="w-full py-2 rounded-xl text-xs font-semibold text-white bg-[#2D5A43] hover:bg-[#234734] transition">✓ Approve Override</button>
              <button class="w-full py-2 rounded-xl text-xs font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 transition">✕ Reject</button>
            </div>
          </div>

        </div>
      </div>

      <!-- ACCESS APPROVALS -->
      <div class="space-y-3">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-2">
            <div class="w-5 h-5 rounded-md bg-emerald-100 text-emerald-800 flex items-center justify-center text-[10px]">
              <i class="fa-solid fa-shield-halved"></i>
            </div>
            <h3 class="text-sm font-bold text-gray-900">Access Approvals</h3>
            <span class="bg-amber-100 text-amber-800 text-[10px] font-bold px-2 py-0.5 rounded-full">2 Pending</span>
          </div>
          <a href="#" class="text-xs font-semibold text-gray-500 hover:text-gray-800 transition">View All Access Audit Logs →</a>
        </div>

        <p class="text-[11px] text-gray-400 -mt-2">Roster elevates and terminal authorizations requiring supervisor clearance prior to shift dispatch.</p>

        <div class="grid grid-cols-2 gap-5">
          
          <!-- Access Card 1 -->
          <div class="bg-white/90 border border-amber-900/10 rounded-2xl p-5 shadow-sm space-y-3 border-l-4 border-l-[#2D5A43]">
            <div class="flex items-start justify-between">
              <div class="flex items-center gap-3">
                <img src="https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=100&auto=format&fit=crop&q=80" class="w-9 h-9 rounded-full object-cover">
                <div>
                  <h4 class="text-xs font-bold text-gray-900">Sarah Jenkins</h4>
                  <span class="text-[10px] text-gray-400">Senior Field Agent • EMP-8841</span>
                </div>
              </div>
              <span class="text-[10px] text-gray-400">2h ago</span>
            </div>

            <div class="bg-[#FAF7F2] p-2.5 rounded-xl border border-amber-900/5 text-xs text-gray-700 space-y-0.5">
              <div class="flex items-center gap-1.5 font-bold">
                <i class="fa-solid fa-arrow-up-right-dots text-[#2D5A43]"></i>
                <span>Elevate to Team Lead / Field Admin</span>
              </div>
              <p class="text-[10px] text-gray-400 pl-4">Req: Level 4 Regional Authority</p>
            </div>

            <div class="grid grid-cols-2 gap-3 pt-1">
              <button class="w-full py-2 rounded-xl text-xs font-semibold text-white bg-[#2D5A43] hover:bg-[#234734] transition">✓ Activate</button>
              <button class="w-full py-2 rounded-xl text-xs font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 transition">✕ Deny</button>
            </div>
          </div>

          <!-- Access Card 2 -->
          <div class="bg-white/90 border border-amber-900/10 rounded-2xl p-5 shadow-sm space-y-3 border-l-4 border-l-[#2D5A43]">
            <div class="flex items-start justify-between">
              <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-gray-200 text-gray-600 flex items-center justify-center font-bold text-xs">MC</div>
                <div>
                  <h4 class="text-xs font-bold text-gray-900">Michael Chang</h4>
                  <span class="text-[10px] text-gray-400">Logistics Dispatcher • EMP-3912</span>
                </div>
              </div>
              <span class="text-[10px] text-gray-400">5h ago</span>
            </div>

            <div class="bg-[#FAF7F2] p-2.5 rounded-xl border border-amber-900/5 text-xs text-gray-700 space-y-0.5">
              <div class="flex items-center gap-1.5 font-bold">
                <i class="fa-solid fa-key text-[#2D5A43]"></i>
                <span>Hardware YubiKey & Biometric Station Setup</span>
              </div>
              <p class="text-[10px] text-gray-400 pl-4">Req: Hardware Security Key Clearance</p>
            </div>

            <div class="grid grid-cols-2 gap-3 pt-1">
              <button class="w-full py-2 rounded-xl text-xs font-semibold text-white bg-[#2D5A43] hover:bg-[#234734] transition">✓ Activate</button>
              <button class="w-full py-2 rounded-xl text-xs font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 transition">✕ Deny</button>
            </div>
          </div>

        </div>
      </div>

    </div>
  </main>

</body>
</html>