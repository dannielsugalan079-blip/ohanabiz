<?php
require_once __DIR__ . '/config/auth.php';
requireSupervisorLogin();
$user = currentUser('supervisor');

// Fetch all active employees / specialists
$emp_stmt = $pdo->query("SELECT id, full_name, email, position, department FROM users WHERE role = 'employee' AND status = 'active' ORDER BY full_name ASC");
$employees = $emp_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch pending or reviewing service requests
$requests_stmt = $pdo->query("
    SELECT sr.id, sr.reference_no, sr.service_name, sr.priority, sr.details, sr.status, sr.requested_at, u.full_name as client_name 
    FROM service_requests sr
    LEFT JOIN users u ON sr.client_id = u.id
    WHERE sr.status IN ('pending', 'reviewing', 'in_progress')
    ORDER BY sr.requested_at DESC
");
$pending_requests = $requests_stmt->fetchAll(PDO::FETCH_ASSOC);

// Count unassigned orders
$unassigned_count = (int)$pdo->query("SELECT COUNT(*) FROM service_requests WHERE assigned_to IS NULL AND status = 'pending'")->fetchColumn();

$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id  = !empty($_POST['request_id']) ? (int)$_POST['request_id'] : null;
    $assigned_to = (int)($_POST['assigned_to'] ?? 0);
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $priority    = in_array($_POST['priority'] ?? '', ['low', 'normal', 'high', 'critical']) ? $_POST['priority'] : 'normal';
    $due_date    = !empty($_POST['due_date']) ? $_POST['due_date'] : null;

    if ($assigned_to <= 0) {
        $error_msg = 'Mangyaring pumili ng specialist na pagkakalooban ng gawain.';
    } elseif (empty($title)) {
        $error_msg = 'Mangyaring maglagay ng pamagat o pangalan ng gawain (Task Title).';
    } else {
        try {
            $insert_task = $pdo->prepare("
                INSERT INTO tasks 
                (request_id, title, description, assigned_to, assigned_by, priority, status, due_date, created_at)
                VALUES (?, ?, ?, ?, ?, ?, 'pending', ?, NOW())
            ");
            $insert_task->execute([
                $request_id,
                $title,
                $description,
                $assigned_to,
                $user['id'],
                $priority,
                $due_date
            ]);

            // If linked to a service request, update service request
            if ($request_id) {
                $update_sr = $pdo->prepare("UPDATE service_requests SET status = 'in_progress', assigned_to = ? WHERE id = ?");
                $update_sr->execute([$assigned_to, $request_id]);
            }

            $new_task_id = (int) $pdo->lastInsertId();
            createNotification(
                $pdo,
                $assigned_to,
                'New task assigned',
                'You have been assigned: ' . $title,
                'task',
                'employee_task_management.php'
            );

            $success_msg = "Task dispatched successfully (Task #{$new_task_id}).";
        } catch (PDOException $e) {
            error_log('[OHANA TASK ASSIGN] ' . $e->getMessage());
            $error_msg = 'Nagkaroon ng aberya sa database: ' . htmlspecialchars($e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Ohana System - Assign New Task</title>
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
  <aside class="w-64 bg-[#FAF7F2] border-r border-amber-900/10 flex flex-col justify-between p-6 shrink-0 sticky top-0 h-screen hidden lg:flex">
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
        
        <a href="supervisor_dispatch.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl bg-[#2D5A43] text-white text-sm font-medium shadow-sm">
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
      <a href="logout.php?portal=supervisor" class="flex items-center gap-3 px-3 py-2 text-gray-600 hover:text-red-700 text-sm font-medium transition">
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
    <header class="h-20 border-b border-amber-900/10 flex items-center justify-between px-8 bg-[#FAF7F2] sticky top-0 z-30">
      <!-- Search Bar -->
      <div class="relative w-96">
        <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
        <input type="text" placeholder="Search dispatch orders, tasks, or agents..." class="w-full bg-white/60 border border-gray-200/80 rounded-full pl-10 pr-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#2D5A43]/20 focus:border-[#2D5A43]">
      </div>

      <!-- Right Actions -->
      <div class="flex items-center gap-4">
        <a href="supervisor_dispatch.php" class="bg-[#2D5A43] hover:bg-[#234734] text-white px-4 py-2 rounded-xl text-xs font-semibold flex items-center gap-2 shadow-sm transition">
          <i class="fa-solid fa-list-check text-xs"></i> View All Tasks
        </a>

        <!-- User Profile -->
        <div class="flex items-center gap-3 pl-2 border-l border-gray-200">
          <div class="w-9 h-9 rounded-full bg-[#2D5A43] text-white flex items-center justify-center font-bold text-xs shadow-xs">
            <?= htmlspecialchars($user['initials'] ?? 'SV') ?>
          </div>
          <div class="text-left">
            <h4 class="text-sm font-bold text-gray-900 leading-none"><?= htmlspecialchars($user['full_name']) ?></h4>
            <span class="text-xs text-gray-500"><?= htmlspecialchars($user['position'] ?? 'Operations Supervisor') ?></span>
          </div>
        </div>
      </div>
    </header>

    <!-- CONTENT BODY -->
    <div class="p-8 space-y-6 overflow-y-auto max-w-7xl mx-auto w-full">
      
      <!-- PAGE HEADER -->
      <div class="flex items-start justify-between">
        <div>
          <div class="flex items-center gap-2 mb-1">
            <h2 class="text-2xl font-bold text-gray-900 leading-tight">Assign New Task</h2>
            <span class="bg-emerald-100 text-emerald-800 text-[10px] font-bold px-2.5 py-0.5 rounded-full uppercase tracking-wider">
              SUPERVISOR OPERATIONAL DISPATCH
            </span>
          </div>
          <p class="text-xs text-gray-500">Assign client service work orders, field directives, and operational requests directly to active specialists.</p>
        </div>

        <div class="flex items-center gap-3">
          <div class="bg-amber-100/60 border border-amber-200/80 px-3.5 py-2 rounded-xl flex items-center gap-2.5">
            <i class="fa-regular fa-folder-open text-amber-800 text-sm"></i>
            <div>
              <span class="text-[9px] font-bold text-amber-800/70 uppercase tracking-wider block leading-none">UNASSIGNED ORDERS</span>
              <span class="text-xs font-extrabold text-amber-900"><?= $unassigned_count ?> Pending</span>
            </div>
          </div>

          <div class="bg-amber-100/60 border border-amber-200/80 px-3.5 py-2 rounded-xl flex items-center gap-2.5">
            <i class="fa-solid fa-users text-amber-800 text-sm"></i>
            <div>
              <span class="text-[9px] font-bold text-amber-800/70 uppercase tracking-wider block leading-none">ACTIVE SPECIALISTS</span>
              <span class="text-xs font-extrabold text-amber-900"><?= count($employees) ?> Online</span>
            </div>
          </div>
        </div>
      </div>

      <!-- ALERTS -->
      <?php if (!empty($success_msg)): ?>
      <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-2xl flex items-center justify-between text-xs text-emerald-900 shadow-xs">
        <div class="flex items-center gap-3">
          <div class="w-8 h-8 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-700 font-bold shrink-0">
            <i class="fa-solid fa-circle-check text-sm"></i>
          </div>
          <div>
            <span class="font-bold text-sm block">Nai-dispatch ang Gawain!</span>
            <span><?= $success_msg ?></span>
          </div>
        </div>
        <a href="supervisor_dispatch.php" class="bg-[#2D5A43] hover:bg-[#234734] text-white font-bold px-3 py-1.5 rounded-xl transition text-xs shrink-0">
          Tingnan sa Dispatch Table
        </a>
      </div>
      <?php elseif (!empty($error_msg)): ?>
      <div class="p-4 bg-red-50 border border-red-200 rounded-2xl flex items-center gap-3 text-xs text-red-900 shadow-xs">
        <div class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center text-red-700 font-bold shrink-0">
          <i class="fa-solid fa-triangle-exclamation text-sm"></i>
        </div>
        <div>
          <span class="font-bold text-sm block">Pansin</span>
          <span><?= htmlspecialchars($error_msg) ?></span>
        </div>
      </div>
      <?php endif; ?>

      <!-- MAIN FORM -->
      <form method="POST" action="supervisor_assign_new_task.php" id="assignTaskForm" class="space-y-6">

        <!-- STEP 1: SERVICE CATEGORY & CLIENT ORDER -->
        <div class="bg-white/90 border border-amber-900/10 rounded-2xl p-6 shadow-sm space-y-4">
          <div class="flex items-center justify-between border-b border-gray-100 pb-3">
            <div class="flex items-center gap-3">
              <span class="w-6 h-6 rounded-full bg-[#2D5A43] text-white text-xs font-bold flex items-center justify-center">1</span>
              <h3 class="font-bold text-gray-900 text-sm">Linked Client Order / Service Request</h3>
            </div>
            <span class="bg-emerald-100 text-emerald-800 text-[10px] font-bold px-2 py-0.5 rounded">STEP 1 OF 4</span>
          </div>

          <p class="text-xs text-gray-500">Pumili ng aktibong kahilingan ng kliyente (Service Request) mula sa database para awtomatikong maikabit ang gawain.</p>

          <div class="bg-[#FAF7F2] p-4 rounded-xl border border-amber-900/10 space-y-2">
            <label class="text-xs font-bold text-gray-700 flex items-center gap-1.5">
              <i class="fa-solid fa-link text-gray-400"></i> Linked Client Order / Request Code:
            </label>
            <div class="relative">
              <select name="request_id" id="requestSelect" class="w-full bg-white border border-gray-200 rounded-lg px-3 py-2 text-xs text-gray-800 font-semibold appearance-none focus:outline-none focus:ring-1 focus:ring-[#2D5A43]">
                <option value="">-- Walang Nakakabit na Order (General / Direct Internal Task) --</option>
                <?php foreach ($pending_requests as $req): ?>
                  <option value="<?= $req['id'] ?>" 
                          data-title="[<?= htmlspecialchars($req['reference_no']) ?>] <?= htmlspecialchars($req['service_name']) ?>"
                          data-priority="<?= htmlspecialchars($req['priority']) ?>"
                          data-desc="<?= htmlspecialchars($req['details'] ?? '') ?>">
                    <?= htmlspecialchars($req['reference_no']) ?> | <?= htmlspecialchars($req['service_name']) ?> — Kliyente: <?= htmlspecialchars($req['client_name'] ?? 'Client') ?> (Status: <?= ucfirst($req['status']) ?>)
                  </option>
                <?php endforeach; ?>
              </select>
              <i class="fa-solid fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i>
            </div>
          </div>
        </div>

        <!-- STEP 2: ASSIGN SPECIALIST / ON-DUTY HANDLER -->
        <div class="bg-white/90 border border-amber-900/10 rounded-2xl p-6 shadow-sm space-y-5">
          <div class="flex items-center justify-between border-b border-gray-100 pb-3">
            <div class="flex items-center gap-3">
              <span class="w-6 h-6 rounded-full bg-[#2D5A43] text-white text-xs font-bold flex items-center justify-center">2</span>
              <h3 class="font-bold text-gray-900 text-sm">Assign Specialist / On-Duty Handler</h3>
            </div>
            <span class="bg-emerald-100 text-emerald-800 text-[10px] font-bold px-2 py-0.5 rounded">STEP 2 OF 4</span>
          </div>

          <p class="text-xs text-gray-500 -mt-2">Piliin ang empleyadong magsasagawa ng gawain mula sa database:</p>

          <!-- Specialists Cards -->
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4" id="specialistList">
            <?php 
            $default_first = true;
            foreach ($employees as $emp): 
              $is_selected = $default_first;
              $default_first = false;
              $emp_initials = '';
              $p_parts = explode(' ', trim($emp['full_name']));
              foreach (array_slice($p_parts, 0, 2) as $p) {
                if (!empty($p)) $emp_initials .= strtoupper($p[0]);
              }
            ?>
              <label class="emp-card border <?= $is_selected ? 'border-2 border-[#2D5A43] bg-emerald-50/20' : 'border-gray-200 bg-white' ?> hover:border-[#2D5A43] rounded-xl p-4 cursor-pointer relative space-y-3 shadow-xs transition">
                <input type="radio" name="assigned_to" value="<?= $emp['id'] ?>" <?= $is_selected ? 'checked' : '' ?> class="emp-radio sr-only">
                <span class="check-icon absolute top-3 right-3 text-[#2D5A43] <?= $is_selected ? '' : 'hidden' ?>">
                  <i class="fa-solid fa-circle-check text-base"></i>
                </span>
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded-full bg-[#2D5A43] text-white flex items-center justify-center font-bold text-xs shrink-0">
                    <?= htmlspecialchars($emp_initials ?: 'EM') ?>
                  </div>
                  <div class="min-w-0">
                    <h4 class="font-bold text-xs text-gray-900 leading-tight truncate"><?= htmlspecialchars($emp['full_name']) ?></h4>
                    <span class="text-[10px] text-gray-500 block truncate"><?= htmlspecialchars($emp['position'] ?? 'Operations Specialist') ?></span>
                    <span class="text-[9px] text-emerald-700 font-semibold block">• Active in <?= htmlspecialchars($emp['department'] ?? 'Operations') ?></span>
                  </div>
                </div>
                <div class="flex items-center justify-between pt-2 border-t border-gray-100 text-[10px]">
                  <span class="font-semibold text-gray-600"><?= htmlspecialchars($emp['email']) ?></span>
                  <span class="bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded font-bold">Available</span>
                </div>
              </label>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- STEP 3: EXECUTION PARAMETERS & SLA TIMELINE -->
        <div class="bg-white/90 border border-amber-900/10 rounded-2xl p-6 shadow-sm space-y-5">
          <div class="flex items-center justify-between border-b border-gray-100 pb-3">
            <div class="flex items-center gap-3">
              <span class="w-6 h-6 rounded-full bg-[#2D5A43] text-white text-xs font-bold flex items-center justify-center">3</span>
              <h3 class="font-bold text-gray-900 text-sm">Execution Parameters & SLA Timeline</h3>
            </div>
            <span class="bg-emerald-100 text-emerald-800 text-[10px] font-bold px-2 py-0.5 rounded">STEP 3 OF 4</span>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div class="space-y-1.5">
              <label class="text-xs font-bold text-gray-700 block">Target Completion / Due Date <span class="text-red-500">*</span></label>
              <input type="date" name="due_date" value="<?= date('Y-m-d', strtotime('+1 day')) ?>" required 
                     class="w-full bg-[#FAF7F2] border border-gray-200 rounded-xl px-3.5 py-2 text-xs text-gray-800 font-semibold focus:outline-none focus:ring-1 focus:ring-[#2D5A43]">
            </div>

            <div class="space-y-1.5">
              <label class="text-xs font-bold text-gray-700 block">SLA Commitment Mode</label>
              <div class="bg-[#FAF7F2] border border-gray-200 rounded-xl px-3.5 py-2 text-xs text-gray-800 font-semibold">
                Malolos Hub Standard Operational Dispatch (24H - 48H SLA)
              </div>
            </div>
          </div>

          <!-- Priority Radio Buttons -->
          <div class="space-y-2">
            <label class="text-xs font-bold text-gray-700">Urgency & Priority Classification</label>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-xs">
              <label class="priority-opt border border-gray-200 bg-white rounded-xl p-3 text-center cursor-pointer transition">
                <input type="radio" name="priority" value="low" class="sr-only">
                <span class="font-bold text-gray-700 block">● Low Priority</span>
              </label>
              <label class="priority-opt border-2 border-[#2D5A43] bg-emerald-50/30 text-[#2D5A43] rounded-xl p-3 text-center cursor-pointer transition font-bold">
                <input type="radio" name="priority" value="normal" checked class="sr-only">
                <span class="block">● Normal Priority</span>
              </label>
              <label class="priority-opt border border-gray-200 bg-white rounded-xl p-3 text-center cursor-pointer transition">
                <input type="radio" name="priority" value="high" class="sr-only">
                <span class="font-bold text-amber-700 block">⚡ High / Expedited</span>
              </label>
              <label class="priority-opt border border-gray-200 bg-white rounded-xl p-3 text-center cursor-pointer transition">
                <input type="radio" name="priority" value="critical" class="sr-only">
                <span class="font-bold text-red-700 block">🔥 Critical / Rush</span>
              </label>
            </div>
          </div>
        </div>

        <!-- STEP 4: JOB DIRECTIVES & DETAILS -->
        <div class="bg-white/90 border border-amber-900/10 rounded-2xl p-6 shadow-sm space-y-5">
          <div class="flex items-center justify-between border-b border-gray-100 pb-3">
            <div class="flex items-center gap-3">
              <span class="w-6 h-6 rounded-full bg-[#2D5A43] text-white text-xs font-bold flex items-center justify-center">4</span>
              <h3 class="font-bold text-gray-900 text-sm">Job Directives & Task Instructions</h3>
            </div>
            <span class="bg-emerald-100 text-emerald-800 text-[10px] font-bold px-2 py-0.5 rounded">STEP 4 OF 4</span>
          </div>

          <!-- Task Title Input -->
          <div class="space-y-1.5">
            <label class="text-xs font-bold text-gray-700 block">Task Title / Subject <span class="text-red-500">*</span></label>
            <input type="text" name="title" id="taskTitleInput" required 
                   placeholder="Hal: File LGU-Form 904 at Malolos City Hall BPLO Dept" 
                   class="w-full bg-[#FAF7F2] border border-gray-200 rounded-xl px-3.5 py-2.5 text-xs text-gray-800 font-semibold focus:outline-none focus:ring-1 focus:ring-[#2D5A43]">
          </div>

          <!-- Directives Box -->
          <div class="space-y-1.5">
            <label class="text-xs font-bold text-gray-700 block">Step-by-Step Directives & Compliance Instructions</label>
            <textarea name="description" id="taskDescInput" rows="4" 
                      placeholder="1. Verify document set with client...&#10;2. Submit to designated government agency desk...&#10;3. Secure official receipt slip and update task status."
                      class="w-full bg-[#FAF7F2] border border-gray-200 rounded-xl p-3.5 text-xs text-gray-800 font-medium focus:outline-none focus:ring-1 focus:ring-[#2D5A43]"></textarea>
          </div>
        </div>

        <!-- BOTTOM ACTIONS BAR -->
        <div class="flex items-center justify-between pt-2">
          <a href="supervisor_dispatch.php" class="text-xs font-semibold text-gray-500 hover:text-gray-800 transition">
            <i class="fa-solid fa-arrow-left mr-1"></i> Discard & Return to Dispatch
          </a>

          <div class="flex items-center gap-3">
            <button type="submit" class="bg-[#2D5A43] hover:bg-[#234734] text-white px-6 py-2.5 rounded-xl text-xs font-bold flex items-center gap-2 transition shadow-sm">
              <i class="fa-solid fa-paper-plane text-xs"></i> Confirm & Dispatch Service Order
            </button>
          </div>
        </div>

      </form>

    </div>
  </main>

  <script>
    // Autofill title and description when selecting a service request
    const requestSelect = document.getElementById('requestSelect');
    const taskTitleInput = document.getElementById('taskTitleInput');
    const taskDescInput = document.getElementById('taskDescInput');

    requestSelect.addEventListener('change', () => {
      const selected = requestSelect.options[requestSelect.selectedIndex];
      if (selected.value) {
        if (!taskTitleInput.value || taskTitleInput.value.startsWith('[')) {
          taskTitleInput.value = selected.dataset.title || '';
        }
        if (selected.dataset.desc && !taskDescInput.value) {
          taskDescInput.value = selected.dataset.desc;
        }
      }
    });

    // Employee radio card selection
    document.querySelectorAll('.emp-card').forEach(card => {
      card.addEventListener('click', () => {
        document.querySelectorAll('.emp-card').forEach(c => {
          c.classList.remove('border-2', 'border-[#2D5A43]', 'bg-emerald-50/20');
          c.classList.add('border-gray-200', 'bg-white');
          const icon = c.querySelector('.check-icon');
          if (icon) icon.classList.add('hidden');
        });
        card.classList.remove('border-gray-200', 'bg-white');
        card.classList.add('border-2', 'border-[#2D5A43]', 'bg-emerald-50/20');
        const icon = card.querySelector('.check-icon');
        if (icon) icon.classList.remove('hidden');
        const radio = card.querySelector('.emp-radio');
        if (radio) radio.checked = true;
      });
    });

    // Priority button selection
    document.querySelectorAll('.priority-opt').forEach(opt => {
      opt.addEventListener('click', () => {
        document.querySelectorAll('.priority-opt').forEach(o => {
          o.classList.remove('border-2', 'border-[#2D5A43]', 'bg-emerald-50/30', 'text-[#2D5A43]');
          o.classList.add('border-gray-200', 'bg-white');
        });
        opt.classList.remove('border-gray-200', 'bg-white');
        opt.classList.add('border-2', 'border-[#2D5A43]', 'bg-emerald-50/30', 'text-[#2D5A43]');
        const radio = opt.querySelector('input[type="radio"]');
        if (radio) radio.checked = true;
      });
    });
  </script>

</body>
</html>