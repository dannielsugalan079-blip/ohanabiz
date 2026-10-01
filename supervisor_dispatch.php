<?php
require_once __DIR__ . '/config/auth.php';
requireSupervisorLogin();
$user = currentUser('supervisor');

// Real metrics from DB
$completed_tasks_count = (int)$pdo->query("SELECT COUNT(*) FROM tasks WHERE status = 'completed'")->fetchColumn();
$total_tasks_count = (int)$pdo->query("SELECT COUNT(*) FROM tasks")->fetchColumn();
$sla_rate = $total_tasks_count > 0 ? round(($completed_tasks_count / $total_tasks_count) * 100, 1) : 100.0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Ohana System - Field & Dispatch Settings</title>
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
          <i class="fa-solid fa-plus text-xs"></i>
          + New Dispatch
        </a>

        <button class="w-10 h-10 rounded-xl border border-gray-200 flex items-center justify-center text-gray-600 hover:bg-white/80 relative">
          <i class="fa-regular fa-bell text-base"></i>
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
      
      <!-- Breadcrumb & Header Actions -->
      <div class="flex items-start justify-between">
        <div>
          <span class="inline-block text-gray-500 text-[10px] font-bold tracking-wider uppercase mb-1">
            <i class="fa-regular fa-user mr-1"></i> SUPERVISOR ACCESS CONTROL
          </span>
          <h2 class="text-2xl font-bold text-gray-900 leading-tight">Settings & Profile</h2>
          <p class="text-xs text-gray-500 max-w-xl mt-1">
            Manage supervisor operational credentials, assigned dispatch unit, duty notifications, and security access.
          </p>
        </div>

        <div class="flex items-center gap-3">
          <button class="px-4 py-2 rounded-xl text-xs font-semibold text-gray-600 bg-gray-200/60 hover:bg-gray-200 transition">
            Discard
          </button>
          <button class="bg-[#2D5A43] hover:bg-[#234734] text-white px-4 py-2 rounded-xl text-xs font-bold shadow-sm transition flex items-center gap-2">
            <i class="fa-solid fa-floppy-disk text-xs"></i>
            Save Changes
          </button>
        </div>
      </div>

      <!-- SUPERVISOR BANNER CARD -->
      <div class="bg-white/90 border border-amber-900/10 rounded-2xl p-5 shadow-sm flex items-center justify-between">
        <div class="flex items-center gap-4">
          <div class="w-14 h-14 rounded-2xl bg-[#2D5A43] text-white flex items-center justify-center font-black text-xl border border-amber-900/10 shadow-sm shrink-0">
            <?= htmlspecialchars($user['initials']) ?>
          </div>
          <div class="space-y-1">
            <div class="flex items-center gap-2">
              <h3 class="text-lg font-bold text-gray-900 leading-none"><?= htmlspecialchars($user['full_name']) ?></h3>
              <span class="bg-emerald-100/80 text-emerald-800 text-[9px] font-bold px-2 py-0.5 rounded uppercase tracking-wider">Active Supervisor</span>
            </div>
            <p class="text-xs font-medium text-gray-600"><?= htmlspecialchars($user['position'] ?? 'Field Operations & Dispatch Lead') ?></p>
            <div class="flex items-center gap-2 text-[11px] text-gray-500">
              <span class="bg-gray-100 text-gray-600 text-[10px] font-mono font-medium px-2 py-0.5 rounded"><i class="fa-solid fa-id-card mr-1 text-gray-400"></i>SUP-<?= str_pad((string)$user['id'], 4, '0', STR_PAD_LEFT) ?></span>
              <span>•</span>
              <span><i class="fa-solid fa-location-dot mr-1 text-emerald-700"></i>Active Station • <?= htmlspecialchars($user['department'] ?? 'Operations') ?></span>
            </div>
          </div>
        </div>

        <!-- Right Stats -->
        <div class="flex items-center gap-8 pr-4">
          <div class="text-center">
            <span class="text-2xl font-black text-gray-900 leading-none block"><?= $sla_rate ?>%</span>
            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mt-1 block">COMPLETION RATE</span>
          </div>

          <div class="text-center border-l border-gray-200 pl-8">
            <span class="text-2xl font-black text-gray-900 leading-none block"><?= $completed_tasks_count ?></span>
            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mt-1 block">TASKS COMPLETED</span>
            <span class="text-[10px] text-gray-400 block mt-0.5"><?= $total_tasks_count ?> Total in System</span>
          </div>
        </div>
      </div>

      <!-- TABS NAVIGATION -->
      <div class="flex items-center gap-2 border-b border-gray-200 pb-2">
        <a href="supervisor_settings.php" class="text-gray-600 hover:bg-black/5 px-4 py-2 rounded-xl text-xs font-medium transition">
          Profile Information
        </a>
        <a href="supervisor_dispatch.php" class="bg-[#2D5A43] text-white px-4 py-2 rounded-xl text-xs font-semibold shadow-sm flex items-center gap-2">
          Field & Dispatch Settings
        </a>
        <a href="supervisor_security.php" class="text-gray-600 hover:bg-black/5 px-4 py-2 rounded-xl text-xs font-medium transition">
          Security & Access
        </a>
        <a href="supervisor_shift_notifications.php" class="text-gray-600 hover:bg-black/5 px-4 py-2 rounded-xl text-xs font-medium transition flex items-center gap-2">
          Shift Notifications
        </a>
      </div>

      <!-- GRID LAYOUT (Main Content + Right Sidebar) -->
      <div class="grid grid-cols-3 gap-6">

        <!-- LEFT 2 COLUMNS: Settings Sections -->
        <div class="col-span-2 space-y-6">

          <!-- Section 1: Assigned Operational Roster & Duty Hub -->
          <div class="bg-white/90 border border-amber-900/10 rounded-2xl p-6 shadow-sm space-y-5">
            <div class="flex items-center justify-between border-b border-gray-100 pb-3">
              <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-gray-100 text-gray-700 flex items-center justify-center text-sm">
                  <i class="fa-solid fa-building"></i>
                </div>
                <div>
                  <h4 class="text-sm font-bold text-gray-900 leading-none">Assigned Operational Roster & Duty Hub</h4>
                  <p class="text-[11px] text-gray-400 mt-0.5">Core territorial stationing and supervisory delegation hierarchy</p>
                </div>
              </div>
              <span class="bg-gray-100 text-gray-600 text-[10px] font-mono font-medium px-2.5 py-1 rounded-md border border-gray-200">
                Roster Rev: 2025.4B
              </span>
            </div>

            <!-- Grid Form Controls -->
            <div class="grid grid-cols-2 gap-4">
              <!-- Primary Operating Hub -->
              <div class="space-y-1.5">
                <label class="text-[11px] font-medium text-gray-500">Primary Operating Hub</label>
                <div class="relative">
                  <div class="w-full bg-[#FAF7F2] border border-amber-900/10 rounded-xl px-3.5 py-2.5 text-xs text-gray-800 font-medium flex items-center justify-between cursor-pointer">
                    <span class="flex items-center gap-2">
                      <i class="fa-solid fa-store text-gray-400"></i>
                      Malolos Main Hub - Bulacan Branch
                    </span>
                    <i class="fa-solid fa-chevron-down text-gray-400 text-[10px]"></i>
                  </div>
                </div>
              </div>

              <!-- Assigned Squad / Dispatch Team -->
              <div class="space-y-1.5">
                <label class="text-[11px] font-medium text-gray-500">Assigned Squad / Dispatch Team</label>
                <div class="w-full bg-[#FAF7F2] border border-amber-900/10 rounded-xl px-3.5 py-2.5 text-xs text-gray-800 font-medium flex items-center justify-between">
                  <span class="flex items-center gap-2">
                    <i class="fa-solid fa-user-group text-gray-400"></i>
                    Alpha Field Team - 8 Field Specialists
                  </span>
                </div>
              </div>

              <!-- Shift Schedule Window -->
              <div class="space-y-1.5">
                <label class="text-[11px] font-medium text-gray-500">Shift Schedule Window</label>
                <div class="w-full bg-[#FAF7F2] border border-amber-900/10 rounded-xl px-3.5 py-2.5 text-xs text-gray-800 font-medium flex items-center justify-between">
                  <span class="flex items-center gap-2">
                    <i class="fa-regular fa-clock text-gray-400"></i>
                    Morning Shift: 08:00 AM - 05:00 PM PHT
                  </span>
                </div>
              </div>

              <!-- Backup / Relief Supervisor -->
              <div class="space-y-1.5">
                <label class="text-[11px] font-medium text-gray-500">Backup / Relief Supervisor</label>
                <div class="relative">
                  <div class="w-full bg-[#FAF7F2] border border-amber-900/10 rounded-xl px-3.5 py-2.5 text-xs text-gray-800 font-medium flex items-center justify-between cursor-pointer">
                    <span class="flex items-center gap-2">
                      <i class="fa-solid fa-user-shield text-gray-400"></i>
                      Mark Ramos (SUP-2025-0114)
                    </span>
                    <i class="fa-solid fa-chevron-down text-gray-400 text-[10px]"></i>
                  </div>
                </div>
              </div>
            </div>

            <!-- Bottom Toggle: Enforce Geo-fenced Clock-in -->
            <div class="bg-[#FAF7F2] p-4 rounded-xl border border-amber-900/5 flex items-center justify-between mt-2">
              <div class="flex items-start gap-3">
                <i class="fa-solid fa-location-dot text-gray-500 mt-1"></i>
                <div>
                  <h5 class="text-xs font-bold text-gray-900">Enforce Geo-fenced Clock-in for Field Specialists</h5>
                  <p class="text-[11px] text-gray-500 mt-0.5">
                    Restricts time logging to within 500m radius of authorized Hub or designated municipal client site.
                  </p>
                </div>
              </div>
              <!-- Toggle On -->
              <label class="relative inline-flex items-center cursor-pointer ml-4">
                <input type="checkbox" checked class="sr-only peer">
                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#2D5A43]"></div>
              </label>
            </div>

          </div>

          <!-- Section 2: SLA Monitoring & Stalled Task Escalations -->
          <div class="bg-white/90 border border-amber-900/10 rounded-2xl p-6 shadow-sm space-y-4">
            <div class="flex items-center justify-between border-b border-gray-100 pb-3">
              <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-orange-100 text-orange-800 flex items-center justify-center text-sm">
                  <i class="fa-regular fa-hourglass-half"></i>
                </div>
                <div>
                  <h4 class="text-sm font-bold text-gray-900 leading-none">SLA Monitoring & Stalled Task Escalations</h4>
                  <p class="text-[11px] text-gray-400 mt-0.5">Early detection mechanisms for field delays and transit interruptions</p>
                </div>
              </div>
              <span class="bg-amber-100/80 text-amber-900 text-[10px] font-bold px-2.5 py-1 rounded-md">
                Tier 1 SLA Policy
              </span>
            </div>

            <!-- Item 1: Urgent Dispatch Broadcast -->
            <div class="bg-[#FAF7F2] p-4 rounded-xl border border-amber-900/5 flex items-center justify-between">
              <div class="flex items-start gap-3">
                <i class="fa-solid fa-bullhorn text-gray-500 mt-1"></i>
                <div>
                  <h5 class="text-xs font-bold text-gray-900">Urgent Dispatch Broadcast</h5>
                  <p class="text-[11px] text-gray-500 mt-0.5">
                    Send push alerts & SMS to all on-duty specialists when emergency task is lodged.
                  </p>
                </div>
              </div>
              <!-- Toggle On -->
              <label class="relative inline-flex items-center cursor-pointer ml-4">
                <input type="checkbox" checked class="sr-only peer">
                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#2D5A43]"></div>
              </label>
            </div>

            <!-- Item 2: Automatic Task Re-assignment -->
            <div class="bg-[#FAF7F2] p-4 rounded-xl border border-amber-900/5 flex items-center justify-between">
              <div class="flex items-start gap-3">
                <i class="fa-solid fa-right-left text-gray-500 mt-1"></i>
                <div>
                  <h5 class="text-xs font-bold text-gray-900">Automatic Task Re-assignment</h5>
                  <p class="text-[11px] text-gray-500 mt-0.5">
                    Automatically reassign task if specialist is unresponsive for 90 mins (Supervisor intervention recommended).
                  </p>
                </div>
              </div>
              <!-- Toggle Off -->
              <label class="relative inline-flex items-center cursor-pointer ml-4">
                <input type="checkbox" class="sr-only peer">
                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#2D5A43]"></div>
              </label>
            </div>

          </div>

          <!-- Section 3: Field Compliance & Proof of Completion -->
          <div class="bg-white/90 border border-amber-900/10 rounded-2xl p-6 shadow-sm space-y-4">
            <div class="flex items-center justify-between border-b border-gray-100 pb-3">
              <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-800 flex items-center justify-center text-sm">
                  <i class="fa-solid fa-file-contract"></i>
                </div>
                <div>
                  <h4 class="text-sm font-bold text-gray-900 leading-none">Field Compliance & Proof of Completion</h4>
                  <p class="text-[11px] text-gray-400 mt-0.5">Mandatory verification checks required before marking dispatches finalized</p>
                </div>
              </div>
              <span class="bg-emerald-100/80 text-emerald-800 text-[10px] font-bold px-2.5 py-1 rounded-md">
                Strict Audit Mode
              </span>
            </div>

            <!-- 2x2 Grid of Compliance Toggles -->
            <div class="grid grid-cols-2 gap-4">
              <!-- Item 1: Client Digital Signature -->
              <div class="bg-[#FAF7F2] p-4 rounded-xl border border-amber-900/5 flex items-center justify-between">
                <div class="flex items-start gap-3">
                  <i class="fa-solid fa-pen-nib text-gray-500 mt-1"></i>
                  <div>
                    <h5 class="text-xs font-bold text-gray-900">Client Digital Signature</h5>
                    <p class="text-[10px] text-gray-500 mt-0.5">
                      Require digital signature from recipient upon handover.
                    </p>
                  </div>
                </div>
                <!-- Toggle On -->
                <label class="relative inline-flex items-center cursor-pointer ml-2">
                  <input type="checkbox" checked class="sr-only peer">
                  <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-[#2D5A43]"></div>
                </label>
              </div>

              <!-- Item 2: Mandatory Document Photo -->
              <div class="bg-[#FAF7F2] p-4 rounded-xl border border-amber-900/5 flex items-center justify-between">
                <div class="flex items-start gap-3">
                  <i class="fa-solid fa-camera text-gray-500 mt-1"></i>
                  <div>
                    <h5 class="text-xs font-bold text-gray-900">Mandatory Document Photo</h5>
                    <p class="text-[10px] text-gray-500 mt-0.5">
                      Photo proof of stamped submission (BIR, DTI, Hall).
                    </p>
                  </div>
                </div>
                <!-- Toggle On -->
                <label class="relative inline-flex items-center cursor-pointer ml-2">
                  <input type="checkbox" checked class="sr-only peer">
                  <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-[#2D5A43]"></div>
                </label>
              </div>

              <!-- Item 3: GPS Location Tagging -->
              <div class="bg-[#FAF7F2] p-4 rounded-xl border border-amber-900/5 flex items-center justify-between">
                <div class="flex items-start gap-3">
                  <i class="fa-solid fa-location-crosshairs text-gray-500 mt-1"></i>
                  <div>
                    <h5 class="text-xs font-bold text-gray-900">GPS Location Tagging</h5>
                    <p class="text-[10px] text-gray-500 mt-0.5">
                      Hardware coordinates tagged on completion stamp.
                    </p>
                  </div>
                </div>
                <!-- Toggle On -->
                <label class="relative inline-flex items-center cursor-pointer ml-2">
                  <input type="checkbox" checked class="sr-only peer">
                  <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-[#2D5A43]"></div>
                </label>
              </div>

              <!-- Item 4: Offline Task Caching -->
              <div class="bg-[#FAF7F2] p-4 rounded-xl border border-amber-900/5 flex items-center justify-between">
                <div class="flex items-start gap-3">
                  <i class="fa-solid fa-[#2D5A43] text-gray-500 mt-1"></i>
                  <div>
                    <h5 class="text-xs font-bold text-gray-900">Offline Task Caching</h5>
                    <p class="text-[10px] text-gray-500 mt-0.5">
                      Store proofs locally for remote Bulacan barangays.
                    </p>
                  </div>
                </div>
                <!-- Toggle On -->
                <label class="relative inline-flex items-center cursor-pointer ml-2">
                  <input type="checkbox" checked class="sr-only peer">
                  <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-[#2D5A43]"></div>
                </label>
              </div>
            </div>

          </div>

        </div>

        <!-- RIGHT 1 COLUMN: Widgets -->
        <div class="space-y-6">

          <!-- Quick Dispatch Status Widget -->
          <div class="bg-white/90 border border-amber-900/10 rounded-2xl p-5 shadow-sm space-y-4">
            <div class="flex items-center justify-between border-b border-gray-100 pb-3">
              <h4 class="text-xs font-bold text-gray-900">Quick Dispatch Status</h4>
              <span class="bg-emerald-100/80 text-emerald-800 text-[9px] font-bold px-2 py-0.5 rounded flex items-center gap-1">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                All Systems Normal
              </span>
            </div>

            <div class="space-y-3">
              <!-- Item 1: On-Duty Specialists -->
              <div class="bg-[#FAF7F2] p-3.5 rounded-xl border border-amber-900/5 flex items-center justify-between">
                <div class="flex items-center gap-3">
                  <div class="w-8 h-8 rounded-lg bg-gray-200/60 flex items-center justify-center text-gray-600 text-xs">
                    <i class="fa-solid fa-user-check"></i>
                  </div>
                  <div>
                    <h5 class="text-xs font-bold text-gray-900">On-Duty Specialists</h5>
                    <p class="text-[10px] text-gray-400">Malolos Core Squad</p>
                  </div>
                </div>
                <div class="text-right">
                  <span class="text-base font-black text-gray-900 leading-none">8</span>
                  <span class="text-[10px] font-bold text-gray-800 block">Online</span>
                </div>
              </div>

              <!-- Item 2: Active Trips / In-Field -->
              <div class="bg-[#FAF7F2] p-3.5 rounded-xl border border-amber-900/5 flex items-center justify-between">
                <div class="flex items-center gap-3">
                  <div class="w-8 h-8 rounded-lg bg-amber-100 text-amber-800 flex items-center justify-center text-xs">
                    <i class="fa-solid fa-truck-fast"></i>
                  </div>
                  <div>
                    <h5 class="text-xs font-bold text-gray-900">Active Trips / In-Field</h5>
                    <p class="text-[10px] text-gray-400">Live in Transit</p>
                  </div>
                </div>
                <div class="text-right">
                  <span class="text-base font-black text-gray-900 leading-none">5</span>
                  <span class="text-[10px] font-bold text-amber-900 block">In-Progress</span>
                </div>
              </div>

              <!-- Item 3: Completed Today -->
              <div class="bg-[#FAF7F2] p-3.5 rounded-xl border border-amber-900/5 flex items-center justify-between">
                <div class="flex items-center gap-3">
                  <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-800 flex items-center justify-center text-xs">
                    <i class="fa-solid fa-circle-check"></i>
                  </div>
                  <div>
                    <h5 class="text-xs font-bold text-gray-900">Completed Today</h5>
                    <p class="text-[10px] text-gray-400">Verified & Cleared</p>
                  </div>
                </div>
                <div class="text-right">
                  <span class="text-base font-black text-gray-900 leading-none">14</span>
                  <span class="text-[10px] font-bold text-gray-800 block">Tasks</span>
                </div>
              </div>
            </div>

            <!-- Fleet Capacity Bar -->
            <div class="pt-2 border-t border-gray-100 space-y-1.5">
              <div class="flex items-center justify-between text-[10px] font-bold">
                <span class="text-gray-400 uppercase">FLEET CAPACITY USED</span>
                <span class="text-gray-900">62.5%</span>
              </div>
              <div class="w-full bg-gray-200 h-2 rounded-full overflow-hidden">
                <div class="bg-[#2D5A43] h-full w-[62.5%] rounded-full"></div>
              </div>
              <div class="flex items-center justify-between text-[10px] text-gray-400 font-medium">
                <span>5 Active</span>
                <span>3 Available for Dispatch</span>
              </div>
            </div>
          </div>

          <!-- Automated Dispatch & Routing Rules Widget -->
          <div class="bg-white/90 border border-amber-900/10 rounded-2xl p-5 shadow-sm space-y-4">
            <div class="flex items-center justify-between border-b border-gray-100 pb-3">
              <div class="flex items-center gap-2">
                <i class="fa-solid fa-diagram-project text-amber-700"></i>
                <h4 class="text-xs font-bold text-gray-900">Automated Dispatch & Routing Rules</h4>
              </div>
              <span class="bg-orange-100 text-orange-800 text-[9px] font-bold px-2 py-0.5 rounded flex items-center gap-1">
                <i class="fa-solid fa-bolt text-[8px]"></i>
                Engine Active
              </span>
            </div>
            <p class="text-[10px] text-gray-400">Algorithmic load-balancing across Bulacan routes</p>

            <div class="bg-[#FAF7F2] p-3.5 rounded-xl border border-amber-900/5 space-y-3">
              <div class="flex items-center justify-between">
                <h5 class="text-xs font-bold text-gray-900">Auto-Assign Incoming Requests</h5>
                <!-- Toggle Switch -->
                <label class="relative inline-flex items-center cursor-pointer">
                  <input type="checkbox" checked class="sr-only peer">
                  <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-[#2D5A43]"></div>
                </label>
              </div>
              <p class="text-[10px] text-gray-500 leading-relaxed">
                Automatically route client requests to nearest available specialist by workload and transit delta.
              </p>
            </div>
          </div>

          <!-- Supervisor Protocol Note Widget -->
          <div class="bg-[#FAF7F2] border border-amber-900/10 rounded-2xl p-4 flex items-start gap-3">
            <i class="fa-regular fa-lightbulb text-gray-500 mt-0.5 text-xs"></i>
            <div>
              <h5 class="text-xs font-bold text-gray-900">Supervisor Protocol Note</h5>
              <p class="text-[10px] text-gray-500 leading-relaxed mt-1">
                Any changes made to Geo-fencing or Mandatory Photo proofs will automatically reflect on specialist mobile handhelds upon their next dispatch sync.
              </p>
            </div>
          </div>

        </div>

      </div>

    </div>
  </main>

</body>
</html>