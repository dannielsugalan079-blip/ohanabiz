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
  <title>Ohana System - Shift Notifications</title>
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

        <a href="supervisor_leave_endorsement.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-gray-600 hover:bg-black/5 text-sm font-medium transition">
          <i class="fa-regular fa-calendar-check text-base"></i>
          Leave Endorsement
        </a>

        <a href="supervisor_settings.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl bg-[#2D5A43] text-white text-sm font-medium shadow-sm">
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
        <button class="bg-[#B85D1B] hover:bg-[#a04f15] text-white px-5 py-2.5 rounded-xl text-sm font-medium flex items-center gap-2 shadow-sm transition">
          <i class="fa-solid fa-plus text-xs"></i>
          + New Dispatch
        </button>

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
            SUPERVISOR ACCESS CONTROL <span class="text-gray-300">/</span> SETTINGS & PROFILE
          </span>
          <p class="text-xs text-gray-500 max-w-xl">
            Manage supervisor operational credentials, assigned dispatch unit, duty notifications, and security access.
          </p>
        </div>

        <div class="flex items-center gap-3">
          <button class="px-4 py-2 rounded-xl text-xs font-semibold text-gray-600 bg-gray-200/60 hover:bg-gray-200 transition">
            Discard
          </button>
          <button class="bg-[#2D5A43] hover:bg-[#234734] text-white px-4 py-2 rounded-xl text-xs font-bold shadow-sm transition flex items-center gap-2">
            <i class="fa-solid fa-check text-xs"></i>
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
              <span class="bg-emerald-100/80 text-emerald-800 text-[9px] font-bold px-2 py-0.5 rounded uppercase tracking-wider">• Active Supervisor</span>
              <span class="bg-gray-100 text-gray-600 text-[10px] font-mono font-medium px-2 py-0.5 rounded">ID: SUP-<?= str_pad((string)$user['id'], 4, '0', STR_PAD_LEFT) ?></span>
            </div>
            <p class="text-xs font-medium text-gray-600"><?= htmlspecialchars($user['position'] ?? 'Operations Supervisor') ?></p>
            <div class="flex items-center gap-2 text-[11px] text-gray-500">
              <span><i class="fa-solid fa-location-dot mr-1 text-emerald-700"></i>Active Station • <?= htmlspecialchars($user['department'] ?? 'Operations') ?></span>
              <span>•</span>
              <span><i class="fa-regular fa-clock mr-1"></i>Standard Duty Shift</span>
            </div>
          </div>
        </div>

        <!-- Right Stats -->
        <div class="flex items-center gap-8 pr-4">
          <div class="text-right">
            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">COMPLETION RATE</span>
            <div class="flex items-center justify-end gap-1">
              <span class="text-2xl font-black text-gray-900 leading-none"><?= $sla_rate ?>%</span>
            </div>
            <span class="text-[10px] text-gray-400"><?= $completed_tasks_count ?> Completed</span>
          </div>

          <div class="text-right border-l border-gray-200 pl-8">
            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">TOTAL TASKS</span>
            <div class="text-2xl font-black text-gray-900 leading-none">
              <?= $total_tasks_count ?>
            </div>
            <span class="text-[10px] text-gray-400">In System</span>
          </div>
        </div>
      </div>

      <!-- TABS NAVIGATION -->
      <div class="flex items-center gap-2 border-b border-gray-200 pb-2">
        <a href="supervisor_settings.php" class="text-gray-600 hover:bg-black/5 px-4 py-2 rounded-xl text-xs font-medium transition">
          Profile Information
        </a>
        <a href="supervisor_dispatch.php" class="text-gray-600 hover:bg-black/5 px-4 py-2 rounded-xl text-xs font-medium transition">
          Field & Dispatch Settings
        </a>
        <a href="supervisor_security.php" class="text-gray-600 hover:bg-black/5 px-4 py-2 rounded-xl text-xs font-medium transition">
          Security & Access
        </a>
        <a href="supervisor_shift_notifications.php" class="bg-[#2D5A43] text-white px-4 py-2 rounded-xl text-xs font-semibold shadow-sm flex items-center gap-2">
          <i class="fa-regular fa-bell text-xs"></i>
          Shift Notifications
        </a>
      </div>

      <!-- GRID LAYOUT (Main Content + Right Sidebar) -->
      <div class="grid grid-cols-3 gap-6">

        <!-- LEFT 2 COLUMNS: Main Sections -->
        <div class="col-span-2 space-y-6">

          <!-- Section 1: Delivery Channels & Sound Profiles -->
          <div class="bg-white/90 border border-amber-900/10 rounded-2xl p-6 shadow-sm space-y-4">
            <div class="flex items-center justify-between border-b border-gray-100 pb-3">
              <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-800 flex items-center justify-center text-sm">
                  <i class="fa-solid fa-volume-high"></i>
                </div>
                <div>
                  <h4 class="text-sm font-bold text-gray-900 leading-none">Delivery Channels & Sound Profiles</h4>
                  <p class="text-[11px] text-gray-400 mt-0.5">Configure how critical operational dispatches reach your workstation and field handset.</p>
                </div>
              </div>
              <span class="bg-amber-100/70 text-amber-900 text-[10px] font-bold px-2.5 py-1 rounded-md">
                Active Shift Profile
              </span>
            </div>

            <!-- Item 1: Audio Alerts & Desktop Chimes -->
            <div class="bg-[#FAF7F2] p-4 rounded-xl border border-amber-900/5 flex items-center justify-between">
              <div class="flex items-start gap-3">
                <i class="fa-regular fa-bell text-gray-500 mt-1"></i>
                <div>
                  <div class="flex items-center gap-2">
                    <h5 class="text-xs font-bold text-gray-900">Audio Alerts & Desktop Chimes</h5>
                    <span class="bg-gray-200/80 text-gray-600 text-[9px] font-medium px-2 py-0.2 rounded">Default Siren</span>
                  </div>
                  <p class="text-[11px] text-gray-500 mt-0.5">
                    High-priority audible cue for urgent dispatch broadcasts and supervisor escalations.
                  </p>
                </div>
              </div>
              <!-- Toggle -->
              <label class="relative inline-flex items-center cursor-pointer ml-4">
                <input type="checkbox" checked class="sr-only peer">
                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#2D5A43]"></div>
              </label>
            </div>

            <!-- Item 2: Mobile Push Notifications -->
            <div class="bg-[#FAF7F2] p-4 rounded-xl border border-amber-900/5 flex items-center justify-between">
              <div class="flex items-start gap-3">
                <i class="fa-solid fa-mobile-screen-button text-gray-500 mt-1"></i>
                <div>
                  <div class="flex items-center gap-2">
                    <h5 class="text-xs font-bold text-gray-900">Mobile Push Notifications (Ohana App)</h5>
                    <span class="bg-gray-200/80 text-gray-600 text-[9px] font-medium px-2 py-0.2 rounded">Galaxy Tab S9 Active</span>
                  </div>
                  <p class="text-[11px] text-gray-500 mt-0.5">
                    Persistent push notification for field inspections, transit tracking, and urgent assignments.
                  </p>
                </div>
              </div>
              <!-- Toggle -->
              <label class="relative inline-flex items-center cursor-pointer ml-4">
                <input type="checkbox" checked class="sr-only peer">
                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#2D5A43]"></div>
              </label>
            </div>

            <!-- Item 3: Email Digest & Shift Audit Logs -->
            <div class="bg-[#FAF7F2] p-4 rounded-xl border border-amber-900/5 flex items-center justify-between">
              <div class="flex items-start gap-3">
                <i class="fa-regular fa-envelope text-gray-500 mt-1"></i>
                <div>
                  <div class="flex items-center gap-2">
                    <h5 class="text-xs font-bold text-gray-900">Email Digest & Shift Audit Logs</h5>
                    <span class="bg-gray-200/80 text-gray-600 text-[9px] font-medium px-2 py-0.2 rounded">maria.santos@ohana.ph</span>
                  </div>
                  <p class="text-[11px] text-gray-500 mt-0.5">
                    Batched export of incident logs, daily clearance manifests, and supervisor signatures.
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

          <!-- Section 2: Trigger Conditions & Escalation Thresholds -->
          <div class="bg-white/90 border border-amber-900/10 rounded-2xl p-6 shadow-sm space-y-4">
            <div class="flex items-center gap-2.5 border-b border-gray-100 pb-3">
              <i class="fa-solid fa-fire text-amber-700"></i>
              <div>
                <h4 class="text-sm font-bold text-gray-900 leading-none">Trigger Conditions & Escalation Thresholds</h4>
                <p class="text-[11px] text-gray-400 mt-0.5">Automated alerts executed by core telematics based on mission risk levels.</p>
              </div>
            </div>

            <!-- Item 1: Stalled Dispatch Alert -->
            <div class="bg-[#FAF7F2] p-4 rounded-xl border border-amber-900/5 flex items-center justify-between">
              <div class="flex items-start gap-3">
                <i class="fa-solid fa-route text-gray-500 mt-1"></i>
                <div>
                  <div class="flex items-center gap-2">
                    <h5 class="text-xs font-bold text-gray-900">Stalled Dispatch / No Transit Progress Alert</h5>
                    <span class="bg-orange-100 text-orange-800 text-[9px] font-bold px-2 py-0.2 rounded">Threshold: 30m</span>
                  </div>
                  <p class="text-[11px] text-gray-500 mt-0.5">
                    Notify after 30 minutes without GPS movement or milestone check-in along designated route corridor.
                  </p>
                </div>
              </div>
              <!-- Toggle -->
              <label class="relative inline-flex items-center cursor-pointer ml-4">
                <input type="checkbox" checked class="sr-only peer">
                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#2D5A43]"></div>
              </label>
            </div>

            <!-- Item 2: Urgent Government Filing Deadlines -->
            <div class="bg-[#FAF7F2] p-4 rounded-xl border border-amber-900/5 flex items-center justify-between">
              <div class="flex items-start gap-3">
                <i class="fa-solid fa-landmark text-gray-500 mt-1"></i>
                <div>
                  <div class="flex items-center gap-2">
                    <h5 class="text-xs font-bold text-gray-900">Urgent Government Filing Deadlines</h5>
                    <span class="bg-gray-200/80 text-gray-600 text-[9px] font-medium px-2 py-0.2 rounded">60m Window</span>
                  </div>
                  <p class="text-[11px] text-gray-500 mt-0.5">
                    Alert 60 minutes before BIR, DTI, SEC, or City Hall municipal submission cut-offs to reassign runners.
                  </p>
                </div>
              </div>
              <!-- Toggle -->
              <label class="relative inline-flex items-center cursor-pointer ml-4">
                <input type="checkbox" checked class="sr-only peer">
                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#2D5A43]"></div>
              </label>
            </div>

          </div>

          <!-- Section 3: Team Attendance Notifications -->
          <div class="bg-white/90 border border-amber-900/10 rounded-2xl p-6 shadow-sm space-y-4">
            <div class="flex items-center gap-2.5 border-b border-gray-100 pb-3">
              <i class="fa-solid fa-users-gear text-emerald-800"></i>
              <div>
                <h4 class="text-sm font-bold text-gray-900 leading-none">Team Attendance Notifications</h4>
                <p class="text-[11px] text-gray-400 mt-0.5">Supervisory awareness rules for Alpha Squad personnel check-ins and relief requests.</p>
              </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <!-- Box 1: Routine Clock-In / Out -->
              <div class="bg-[#FAF7F2] p-4 rounded-xl border border-amber-900/5 space-y-3">
                <div class="flex items-center justify-between">
                  <h5 class="text-xs font-bold text-gray-900">Routine Clock-In / Out</h5>
                  <!-- Toggle Off -->
                  <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" class="sr-only peer">
                    <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-[#2D5A43]"></div>
                  </label>
                </div>
                <p class="text-[11px] text-gray-500 leading-relaxed">
                  Muted during shift. Batched into end-of-day supervisor rollcall report.
                </p>
                <span class="inline-block bg-gray-200/60 text-gray-600 text-[10px] font-medium px-2 py-0.5 rounded">
                  Batched in Daily Summary
                </span>
              </div>

              <!-- Box 2: Emergency Sick Leave / Relief -->
              <div class="bg-[#FAF7F2] p-4 rounded-xl border border-amber-900/5 space-y-3">
                <div class="flex items-center justify-between">
                  <h5 class="text-xs font-bold text-gray-900">Emergency Sick Leave / Relief</h5>
                  <!-- Toggle On -->
                  <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" checked class="sr-only peer">
                    <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-[#2D5A43]"></div>
                  </label>
                </div>
                <p class="text-[11px] text-gray-500 leading-relaxed">
                  Priority request for same-day substitute driver or runner assignment.
                </p>
                <span class="inline-block bg-gray-200/60 text-gray-600 text-[10px] font-medium px-2 py-0.5 rounded">
                  Requires Endorsement Signature
                </span>
              </div>
            </div>

          </div>

        </div>

        <!-- RIGHT 1 COLUMN: Widgets (Quiet Hours & Recent Shift Alert Log) -->
        <div class="space-y-6">

          <!-- Quiet Hours & Handoff Widget -->
          <div class="bg-white/90 border border-amber-900/10 rounded-2xl p-5 shadow-sm space-y-4">
            <div class="flex items-center justify-between border-b border-gray-100 pb-3">
              <div class="flex items-center gap-2">
                <i class="fa-regular fa-moon text-gray-700"></i>
                <h4 class="text-xs font-bold text-gray-900">Quiet Hours & Handoff</h4>
              </div>
            </div>
            <p class="text-[11px] text-gray-400">Off-shift routing & secondary coverage.</p>

            <div class="bg-[#FAF7F2] p-3.5 rounded-xl border border-amber-900/5 space-y-3">
              <div class="flex items-center justify-between">
                <h5 class="text-xs font-bold text-gray-900">Shift Handoff Auto-Mute</h5>
                <!-- Toggle Switch -->
                <label class="relative inline-flex items-center cursor-pointer">
                  <input type="checkbox" checked class="sr-only peer">
                  <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-[#2D5A43]"></div>
                </label>
              </div>
              <p class="text-[10px] text-gray-500">Forward sirens to incoming supervisor</p>

              <div class="text-[11px] text-gray-600 space-y-1 pt-1 border-t border-gray-200/60">
                <div class="flex items-center justify-between">
                  <span class="text-gray-400"><i class="fa-regular fa-clock mr-1"></i>17:00 PM - 08:00 AM</span>
                  <span class="font-bold text-gray-800">Lead: R. Dalisay</span>
                </div>
                <div class="text-[10px] text-gray-400 flex items-center gap-1">
                  <span class="w-1.5 h-1.5 rounded-full bg-gray-800"></span>
                  <span>Standby Coverage: Active Mgr after 5m Auto-escalates to Hub</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Recent Shift Alert Log Widget -->
          <div class="bg-white/90 border border-amber-900/10 rounded-2xl p-5 shadow-sm space-y-4">
            <div class="flex items-center justify-between border-b border-gray-100 pb-3">
              <div class="flex items-center gap-2">
                <i class="fa-solid fa-rotate-left text-gray-600"></i>
                <h4 class="text-xs font-bold text-gray-900">Recent Shift Alert Log</h4>
              </div>
              <span class="text-[10px] text-gray-400 font-medium">Today</span>
            </div>

            <div class="space-y-3">
              <div class="text-center py-12 text-gray-400">
                <i class="fa-regular fa-bell text-4xl mb-3 block"></i>
                <h3 class="text-sm font-semibold text-gray-600 mb-1">No Shift Notifications</h3>
                <p class="text-xs">There are no pending shift notifications or alerts at this time.</p>
              </div>
            </div>
          </div>

        </div>

      </div>

    </div>
  </main>

</body>
</html>