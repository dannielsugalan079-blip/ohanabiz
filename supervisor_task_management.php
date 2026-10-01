<?php
require_once __DIR__ . '/config/auth.php';
requireSupervisorLogin();
$user = currentUser('supervisor');

// Tasks query
$tasks_stmt = $pdo->query("
    SELECT t.*, u.full_name as assignee_name, u.position as assignee_position 
    FROM tasks t 
    LEFT JOIN users u ON t.assigned_to = u.id 
    ORDER BY t.created_at DESC
");
$tasks = $tasks_stmt->fetchAll(PDO::FETCH_ASSOC);

$total_tasks = count($tasks);
$urgent_tasks = 0;
$in_progress_tasks = 0;
$pending_tasks = 0;
$completed_tasks = 0;

foreach ($tasks as $t) {
    if ($t['priority'] === 'critical' || $t['priority'] === 'high') $urgent_tasks++;
    if ($t['status'] === 'in_progress') $in_progress_tasks++;
    if ($t['status'] === 'pending') $pending_tasks++;
    if ($t['status'] === 'completed') $completed_tasks++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Ohana System - Task Management</title>
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
        
        <a href="supervisor_task_management.php" class="flex items-center justify-between px-3 py-2.5 rounded-xl bg-[#2D5A43] text-white text-sm font-medium shadow-sm">
          <div class="flex items-center gap-3">
            <i class="fa-solid fa-list-check text-base"></i>
            Dispatch & Tasks
          </div>
          <span class="w-1.5 h-3 bg-emerald-400 rounded-full"></span>
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
        <input type="text" placeholder="Search personnel, tasks, or manifest..." class="w-full bg-white/60 border border-gray-200/80 rounded-full pl-10 pr-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#2D5A43]/20 focus:border-[#2D5A43]">
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
      
      <!-- Page Title & Top Stats -->
      <div class="flex items-start justify-between">
        <div>
          <div class="flex items-center gap-2 mb-1">
            <span class="bg-[#2D5A43] text-white text-[9px] font-bold px-2 py-0.5 rounded flex items-center gap-1">
              <i class="fa-solid fa-[#2D5A43] text-[8px]"></i> OPERATIONS HUB
            </span>
            <span class="w-1.5 h-1.5 rounded-full bg-orange-500"></span>
          </div>
          <h2 class="text-2xl font-bold text-gray-900 leading-tight">Task Management</h2>
          <p class="text-xs text-gray-500 mt-0.5">Assign, monitor, and manage team operational tasks across branch hubs.</p>
        </div>

        <!-- Top Right Stats Badges -->
        <div class="flex items-center gap-4">
          <div class="bg-amber-900/5 px-4 py-2 rounded-xl border border-amber-900/10 flex items-center gap-3">
            <i class="fa-solid fa-list-check text-gray-500 text-base"></i>
            <div>
              <span class="text-[10px] text-gray-500 uppercase tracking-wider block font-semibold leading-none">Active Load</span>
              <span class="text-sm font-bold text-gray-900"><?= $in_progress_tasks + $pending_tasks ?> Open</span>
            </div>
          </div>

          <div class="bg-amber-900/5 px-4 py-2 rounded-xl border border-amber-900/10 flex items-center gap-3">
            <i class="fa-regular fa-bell text-amber-800 text-base"></i>
            <div>
              <span class="text-[10px] text-gray-500 uppercase tracking-wider block font-semibold leading-none">Urgent Items</span>
              <span class="text-sm font-bold text-gray-900"><?= $urgent_tasks ?> High Priority</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Filter Tabs & Task Search -->
      <div class="flex items-center justify-between gap-4">
        <div class="flex items-center gap-2">
          <button class="bg-[#2D5A43] text-white px-4 py-2 rounded-xl text-xs font-semibold flex items-center gap-2 shadow-sm">
            All Tasks <span class="bg-white/20 text-white px-1.5 py-0.5 rounded text-[10px]"><?= $total_tasks ?></span>
          </button>
          <button class="bg-white/60 hover:bg-white text-gray-600 px-4 py-2 rounded-xl text-xs font-semibold flex items-center gap-2 border border-gray-200/80 transition">
            In Progress <span class="bg-gray-200 text-gray-600 px-1.5 py-0.5 rounded text-[10px]"><?= $in_progress_tasks ?></span>
          </button>
          <button class="bg-white/60 hover:bg-white text-gray-600 px-4 py-2 rounded-xl text-xs font-semibold flex items-center gap-2 border border-gray-200/80 transition">
            Pending <span class="bg-gray-200 text-gray-600 px-1.5 py-0.5 rounded text-[10px]"><?= $pending_tasks ?></span>
          </button>
          <button class="bg-white/60 hover:bg-white text-gray-600 px-4 py-2 rounded-xl text-xs font-semibold flex items-center gap-2 border border-gray-200/80 transition">
            Completed <span class="bg-gray-200 text-gray-600 px-1.5 py-0.5 rounded text-[10px]"><?= $completed_tasks ?></span>
          </button>
        </div>

        <div class="relative w-64">
          <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
          <input type="text" placeholder="Filter task or assignee..." class="w-full bg-white/80 border border-gray-200 rounded-xl pl-9 pr-3 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-[#2D5A43]">
        </div>
      </div>

      <!-- DATA STREAMING TABLE -->
      <div class="bg-white/90 border border-amber-900/10 rounded-2xl p-6 shadow-sm space-y-4">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-2">
            <h3 class="text-sm font-bold text-gray-900">Operational Task List</h3>
            <span class="bg-emerald-100 text-emerald-800 text-[10px] font-bold px-2 py-0.5 rounded-full"><?= $total_tasks ?> Recorded</span>
          </div>
          <a href="supervisor_assign_new_task.php" class="text-xs font-semibold text-[#2D5A43] hover:underline flex items-center gap-1">
            <i class="fa-solid fa-plus text-[10px]"></i> Dispatch New
          </a>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="text-[10px] font-bold text-gray-400 uppercase tracking-wider border-b border-gray-100">
                <th class="pb-3 font-semibold">TASK DETAILS</th>
                <th class="pb-3 font-semibold">ASSIGNED PERSONNEL</th>
                <th class="pb-3 font-semibold">PRIORITY</th>
                <th class="pb-3 font-semibold">DUE DATE</th>
                <th class="pb-3 font-semibold">STATUS</th>
                <th class="pb-3 font-semibold text-right">ACTIONS</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 text-xs">
              <?php if (empty($tasks)): ?>
                <tr>
                  <td colspan="6" class="py-12 text-center text-gray-400">
                    <i class="fa-regular fa-folder-open text-3xl mb-2 block"></i>
                    <p class="font-semibold text-gray-600">No Tasks Recorded</p>
                    <p class="text-[11px] mt-0.5">Use the dispatch form below to assign your first operational directive.</p>
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($tasks as $task): 
                  $priority_badge = 'bg-gray-100 text-gray-700';
                  if ($task['priority'] === 'critical') $priority_badge = 'bg-red-100 text-red-800';
                  elseif ($task['priority'] === 'high') $priority_badge = 'bg-orange-100 text-orange-800';
                  elseif ($task['priority'] === 'normal') $priority_badge = 'bg-blue-100 text-blue-800';

                  $status_badge = 'bg-gray-100 text-gray-700';
                  if ($task['status'] === 'completed') $status_badge = 'bg-emerald-100 text-emerald-800';
                  elseif ($task['status'] === 'in_progress') $status_badge = 'bg-blue-100 text-blue-800';
                  elseif ($task['status'] === 'for_review') $status_badge = 'bg-purple-100 text-purple-800';
                  elseif ($task['status'] === 'pending') $status_badge = 'bg-amber-100 text-amber-800';
                ?>
                <tr class="hover:bg-[#FAF7F2]/50 transition">
                  <td class="py-4 pr-4">
                    <div class="flex items-start gap-2">
                      <span class="w-1.5 h-1.5 rounded-full <?= $task['status'] === 'completed' ? 'bg-emerald-500' : 'bg-orange-500' ?> mt-1.5 shrink-0"></span>
                      <div>
                        <h4 class="font-bold text-gray-900"><?= htmlspecialchars($task['title']) ?></h4>
                        <p class="text-[11px] text-gray-500 mt-0.5 line-clamp-1"><?= htmlspecialchars(mb_substr($task['description'] ?? 'No description provided.', 0, 70)) ?></p>
                      </div>
                    </div>
                  </td>
                  <td class="py-4 px-2">
                    <div class="flex items-center gap-2.5">
                      <div class="w-7 h-7 rounded-full bg-[#2D5A43] text-white flex items-center justify-center font-bold text-xs shrink-0">
                        <?= strtoupper(substr($task['assignee_name'] ?? 'U', 0, 1)) ?>
                      </div>
                      <div>
                        <h5 class="font-bold text-gray-900 leading-none"><?= htmlspecialchars($task['assignee_name'] ?? 'Unassigned') ?></h5>
                        <span class="text-[10px] text-gray-400"><?= htmlspecialchars($task['assignee_position'] ?? 'Operations Specialist') ?></span>
                      </div>
                    </div>
                  </td>
                  <td class="py-4 px-2">
                    <span class="<?= $priority_badge ?> text-[10px] font-bold px-2 py-0.5 rounded-full uppercase">
                      <?= htmlspecialchars($task['priority']) ?>
                    </span>
                  </td>
                  <td class="py-4 px-2">
                    <div>
                      <span class="font-semibold text-gray-800 block leading-none">
                        <?= !empty($task['due_date']) ? date('M d, Y', strtotime($task['due_date'])) : 'Open' ?>
                      </span>
                      <span class="text-[10px] text-gray-400 font-medium">Created <?= date('M d', strtotime($task['created_at'])) ?></span>
                    </div>
                  </td>
                  <td class="py-4 px-2">
                    <span class="<?= $status_badge ?> text-[10px] font-bold px-2.5 py-0.5 rounded-full capitalize">
                      <?= str_replace('_', ' ', htmlspecialchars($task['status'])) ?>
                    </span>
                  </td>
                  <td class="py-4 pl-2 text-right">
                    <div class="flex items-center justify-end gap-2 text-gray-400">
                      <a href="supervisor_assign_new_task.php" class="hover:text-[#2D5A43] p-1 text-xs font-semibold"><i class="fa-solid fa-pen text-[11px]"></i> Edit</a>
                    </div>
                  </td>
                </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div class="flex items-center justify-between pt-2">
          <span class="text-[11px] text-gray-400">Showing 4 of 24 active assignments</span>
          <div class="flex items-center gap-1 text-xs font-semibold">
            <button class="w-6 h-6 rounded flex items-center justify-center text-gray-400 hover:bg-gray-100">&lt;</button>
            <button class="w-6 h-6 rounded bg-[#2D5A43] text-white flex items-center justify-center">1</button>
            <button class="w-6 h-6 rounded text-gray-600 hover:bg-gray-100 flex items-center justify-center">2</button>
            <button class="w-6 h-6 rounded text-gray-600 hover:bg-gray-100 flex items-center justify-center">3</button>
            <button class="w-6 h-6 rounded flex items-center justify-center text-gray-400 hover:bg-gray-100">&gt;</button>
          </div>
        </div>

      </div>

      <!-- ASSIGN NEW TASK FORM -->
      <div class="bg-white/90 border border-amber-900/10 rounded-2xl p-6 shadow-sm space-y-6">
        <div class="flex items-center justify-between border-b border-gray-100 pb-4">
          <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-emerald-800 text-white flex items-center justify-center text-xs">
              <i class="fa-solid fa-user-plus"></i>
            </div>
            <div>
              <h3 class="text-sm font-bold text-gray-900">Assign New Task</h3>
              <p class="text-[11px] text-gray-400">Direct operational dispatch to team members.</p>
            </div>
          </div>
          <button class="text-gray-400 hover:text-gray-600"><i class="fa-solid fa-rotate-left text-xs"></i></button>
        </div>

        <form class="space-y-4">
          <!-- Row 1 -->
          <div class="grid grid-cols-2 gap-6">
            <div>
              <div class="flex justify-between items-center mb-1">
                <label class="text-xs font-bold text-gray-700">Task Title *</label>
                <span class="text-[10px] text-gray-400">Max 75 chars</span>
              </div>
              <input type="text" placeholder="e.g. Conduct Facility Security Assessment" class="w-full bg-[#FAF7F2] border border-gray-200 rounded-xl px-3.5 py-2 text-xs focus:outline-none focus:ring-1 focus:ring-[#2D5A43]">
            </div>

            <div>
              <label class="text-xs font-bold text-gray-700 block mb-1">Assignee Personnel *</label>
              <div class="relative">
                <select class="w-full bg-[#FAF7F2] border border-gray-200 rounded-xl px-3.5 py-2 text-xs text-gray-700 appearance-none focus:outline-none focus:ring-1 focus:ring-[#2D5A43]">
                  <option>Aga Michael Roxas (Admin)</option>
                  <option>Elena Woods (Operations Lead)</option>
                  <option>David Chen (Logistics Lead)</option>
                </select>
                <i class="fa-solid fa-chevron-down absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-[10px] pointer-events-none"></i>
              </div>
            </div>
          </div>

          <!-- Row 2 -->
          <div class="grid grid-cols-3 gap-6">
            <div>
              <label class="text-xs font-bold text-gray-700 block mb-1">Due Date *</label>
              <input type="text" value="11/04/2025" class="w-full bg-[#FAF7F2] border border-gray-200 rounded-xl px-3.5 py-2 text-xs text-gray-700 focus:outline-none focus:ring-1 focus:ring-[#2D5A43]">
            </div>

            <div>
              <label class="text-xs font-bold text-gray-700 block mb-1">Category</label>
              <div class="relative">
                <select class="w-full bg-[#FAF7F2] border border-gray-200 rounded-xl px-3.5 py-2 text-xs text-gray-700 appearance-none focus:outline-none focus:ring-1 focus:ring-[#2D5A43]">
                  <option>Logistics & Ops</option>
                  <option>Engineering</option>
                  <option>HR & Compliance</option>
                </select>
                <i class="fa-solid fa-chevron-down absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-[10px] pointer-events-none"></i>
              </div>
            </div>

            <div>
              <label class="text-xs font-bold text-gray-700 block mb-1">Urgency Level</label>
              <div class="grid grid-cols-3 gap-2">
                <button type="button" class="py-2 bg-[#FAF7F2] border border-gray-200 rounded-xl text-xs font-medium text-gray-600 hover:bg-gray-100 flex items-center justify-center gap-1">
                  <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Low
                </button>
                <button type="button" class="py-2 bg-[#FAF7F2] border border-gray-200 rounded-xl text-xs font-medium text-gray-600 hover:bg-gray-100 flex items-center justify-center gap-1">
                  <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span> Medium
                </button>
                <button type="button" class="py-2 bg-[#B85D1B] text-white rounded-xl text-xs font-bold flex items-center justify-center gap-1 shadow-sm">
                  <span class="w-1.5 h-1.5 rounded-full bg-white"></span> Urgent
                </button>
              </div>
            </div>
          </div>

          <!-- Row 3 -->
          <div class="grid grid-cols-2 gap-6">
            <div>
              <label class="text-xs font-bold text-gray-700 block mb-1">Task Instructions</label>
              <textarea rows="4" placeholder="Provide step-by-step guidance, deliverable parameters, and expected outcomes..." class="w-full bg-[#FAF7F2] border border-gray-200 rounded-xl p-3.5 text-xs focus:outline-none focus:ring-1 focus:ring-[#2D5A43] resize-none"></textarea>
            </div>

            <div>
              <div class="flex justify-between items-center mb-1">
                <label class="text-xs font-bold text-gray-700">Attachments & Briefs</label>
                <span class="text-[10px] text-gray-400">PDF, DOC, ZIP (up to 25MB)</span>
              </div>
              <div class="border-2 border-dashed border-gray-200 bg-[#FAF7F2] rounded-xl h-[104px] flex flex-col items-center justify-center text-center p-4 cursor-pointer hover:bg-gray-100/50 transition">
                <i class="fa-solid fa-cloud-arrow-up text-gray-400 text-lg mb-1"></i>
                <span class="text-xs font-bold text-gray-700">Click to upload or drag files</span>
                <span class="text-[10px] text-gray-400">Specifications, manifests or audit forms</span>
              </div>
            </div>
          </div>

          <!-- Buttons -->
          <div class="flex items-center justify-end gap-3 pt-2">
            <button type="button" class="px-5 py-2.5 rounded-xl border border-gray-200 text-xs font-bold text-gray-600 hover:bg-gray-50 transition">Cancel</button>
            <button type="submit" class="px-5 py-2.5 rounded-xl bg-[#2D5A43] hover:bg-[#234734] text-white text-xs font-bold flex items-center gap-2 shadow-sm transition">
              <i class="fa-solid fa-play text-[10px]"></i> Create Task
            </button>
          </div>
        </form>
      </div>

      <!-- WEEKLY THROUGHPUT -->
      <div class="bg-white/90 border border-amber-900/10 rounded-2xl p-5 shadow-sm flex items-center justify-between">
        <div class="space-y-1">
          <div class="flex items-center gap-2">
            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">WEEKLY THROUGHPUT</span>
            <span class="bg-emerald-100 text-emerald-800 text-[10px] font-bold px-2 py-0.5 rounded-full flex items-center gap-1">
              <i class="fa-solid fa-arrow-trend-up text-[8px]"></i> +18.4%
            </span>
          </div>
          <h3 class="text-2xl font-black text-gray-900">32 Tasks Closed</h3>
          <p class="text-xs text-gray-400">14 ahead of current weekly operational target benchmark</p>
        </div>

        <!-- Sparkline Curve Representation -->
        <div class="w-64 h-12">
          <svg viewBox="0 0 200 50" class="w-full h-full stroke-[#2D5A43] fill-none stroke-2">
            <path d="M 0 40 Q 40 45, 80 25 T 160 30 T 200 10" stroke-linecap="round" />
          </svg>
        </div>
      </div>

    </div>
  </main>

</body>
</html>