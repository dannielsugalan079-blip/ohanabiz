<?php
require_once __DIR__ . '/config/auth.php';
requireSupervisorLogin();
$user = currentUser('supervisor');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Ohana System - Settings & Profile</title>
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
      
      <!-- Subtitle Tag & Header Actions -->
      <div class="flex items-start justify-between">
        <div>
          <span class="inline-block px-2.5 py-0.5 rounded-md bg-gray-200/70 text-gray-600 text-[10px] font-bold tracking-wider uppercase mb-1">
            • SUPERVISOR ACCESS CONTROL
          </span>
          <h2 class="text-2xl font-bold text-gray-900">Settings & Profile</h2>
          <p class="text-xs text-gray-500 mt-0.5">Manage supervisor operational credentials, assigned dispatch unit, duty notifications, and security access.</p>
        </div>

        <div class="flex items-center gap-3">
          <button class="px-4 py-2 rounded-xl text-xs font-semibold text-gray-600 bg-gray-200/60 hover:bg-gray-200 transition">
            Discard
          </button>
          <button class="bg-[#2D5A43] hover:bg-[#234734] text-white px-4 py-2 rounded-xl text-xs font-bold shadow-sm transition flex items-center gap-2">
            <i class="fa-regular fa-floppy-disk text-xs"></i>
            Save Changes
          </button>
        </div>
      </div>

      <!-- SUPERVISOR BANNER CARD -->
      <div class="bg-white/90 border border-amber-900/10 rounded-2xl p-6 shadow-sm flex items-center justify-between">
        <div class="flex items-center gap-5">
          <div class="relative">
            <img src="https://i.pravatar.cc/100?img=32" class="w-20 h-20 rounded-2xl object-cover border-2 border-white shadow">
            <span class="w-4 h-4 rounded-full bg-emerald-600 border-2 border-white absolute -bottom-1 -right-1"></span>
          </div>
          <div class="space-y-1.5">
            <div class="flex items-center gap-2">
              <h3 class="text-xl font-bold text-gray-900"><?= htmlspecialchars($user['full_name']) ?></h3>
              <span class="bg-emerald-100 text-emerald-800 text-[10px] font-bold px-2 py-0.5 rounded-md">Active Supervisor</span>
            </div>
            <p class="text-xs font-medium text-gray-600"><?= htmlspecialchars($user['position'] ?? 'Field Operations & Dispatch Lead') ?></p>
            <div class="flex items-center gap-3 text-xs text-gray-400">
              <span class="bg-gray-100 px-2 py-0.5 rounded text-gray-600 text-[11px] font-mono">SUP - 2025 - 0842</span>
              <span>•</span>
              <span class="text-gray-700 font-medium">■ Active Duty Shift • Malolos Hub</span>
            </div>
          </div>
        </div>

        <div class="bg-amber-50/60 border border-amber-900/10 p-3.5 rounded-xl text-right">
          <div class="flex items-center gap-2 text-xs font-bold text-gray-700 justify-end">
            <i class="fa-solid fa-arrows-rotate text-gray-500"></i>
            Activity & Shift Log
          </div>
          <span class="text-[11px] text-gray-500">Shift rotation ends in 3h 45m</span>
        </div>
      </div>

      <!-- TABS NAVIGATION -->
      <div class="flex items-center gap-2 border-b border-gray-200 pb-2">
        <a href="supervisor_settings.php" class="bg-[#2D5A43] text-white px-4 py-2 rounded-xl text-xs font-semibold shadow-sm inline-block">
          Profile Information
        </a>
        <a href="supervisor_dispatch.php" class="text-gray-600 hover:bg-black/5 px-4 py-2 rounded-xl text-xs font-medium transition inline-block">
          Field & Dispatch Settings
        </a>
        <a href="supervisor_security.php" class="text-gray-600 hover:bg-black/5 px-4 py-2 rounded-xl text-xs font-medium transition inline-block">
          Security & Access
        </a>
        <a href="supervisor_shift_notifications.php" class="text-gray-600 hover:bg-black/5 px-4 py-2 rounded-xl text-xs font-medium transition inline-block">
          Shift Notifications
        </a>
      </div>

      <!-- SECTION 1: Personal & Operational Information -->
      <div class="bg-white/90 border border-amber-900/10 rounded-2xl p-6 shadow-sm space-y-5">
        <div class="flex items-center justify-between border-b border-gray-100 pb-3">
          <div class="flex items-center gap-2.5">
            <i class="fa-regular fa-id-card text-gray-500"></i>
            <div>
              <h4 class="text-sm font-bold text-gray-900 leading-none">Personal & Operational Information</h4>
              <p class="text-[11px] text-gray-400 mt-0.5">Update your field supervisor profile and operational dispatch contacts.</p>
            </div>
          </div>
          <span class="bg-gray-100 text-gray-600 text-[10px] font-bold px-2.5 py-1 rounded-md">Primary Record</span>
        </div>

        <div class="grid grid-cols-2 gap-5">
          <!-- First Name -->
          <div class="space-y-1">
            <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider">FIRST NAME</label>
            <input type="text" value="Maria" class="w-full bg-white border border-gray-200 rounded-xl px-3.5 py-2 text-xs text-gray-800 font-medium focus:outline-none focus:border-[#2D5A43]">
          </div>

          <!-- Last Name -->
          <div class="space-y-1">
            <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider">LAST NAME</label>
            <input type="text" value="Santos" class="w-full bg-white border border-gray-200 rounded-xl px-3.5 py-2 text-xs text-gray-800 font-medium focus:outline-none focus:border-[#2D5A43]">
          </div>

          <!-- Corporate Email -->
          <div class="space-y-1">
            <div class="flex items-center justify-between">
              <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider">CORPORATE EMAIL</label>
              <span class="text-[10px] font-bold text-emerald-700 flex items-center gap-1">
                <i class="fa-solid fa-shield-check"></i> Verified Entity
              </span>
            </div>
            <div class="relative">
              <i class="fa-regular fa-envelope absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
              <input type="email" value="supervisor@ohanabusiness.com" class="w-full bg-gray-50/50 border border-gray-200 rounded-xl pl-10 pr-3.5 py-2 text-xs text-gray-800 font-medium focus:outline-none">
            </div>
          </div>

          <!-- Contact / Radio Mobile -->
          <div class="space-y-1">
            <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider">CONTACT / RADIO MOBILE</label>
            <div class="relative">
              <i class="fa-solid fa-phone absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
              <input type="text" value="+63 997 855 1913" class="w-full bg-white border border-gray-200 rounded-xl pl-10 pr-3.5 py-2 text-xs text-gray-800 font-medium focus:outline-none focus:border-[#2D5A43]">
            </div>
          </div>

          <!-- Designated Role -->
          <div class="space-y-1">
            <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider">DESIGNATED ROLE</label>
            <input type="text" value="Field Operations Supervisor & Duty Lead" class="w-full bg-white border border-gray-200 rounded-xl px-3.5 py-2 text-xs text-gray-800 font-medium focus:outline-none focus:border-[#2D5A43]">
          </div>

          <!-- Assigned Branch / Hub -->
          <div class="space-y-1">
            <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider">ASSIGNED BRANCH / HUB</label>
            <div class="relative">
              <i class="fa-solid fa-hubspot absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
              <input type="text" value="Malolos Main Operational Hub — Bulacan Branch" class="w-full bg-white border border-gray-200 rounded-xl pl-10 pr-3.5 py-2 text-xs text-gray-800 font-medium focus:outline-none focus:border-[#2D5A43]">
            </div>
          </div>
        </div>
      </div>

      <!-- SECTION 2: Supervisor Credentials -->
      <div class="bg-white/90 border border-amber-900/10 rounded-2xl p-6 shadow-sm space-y-5">
        <div class="flex items-center justify-between border-b border-gray-100 pb-3">
          <div class="flex items-center gap-2.5">
            <i class="fa-solid fa-shield-halved text-gray-500"></i>
            <div>
              <h4 class="text-sm font-bold text-gray-900 leading-none">Supervisor Credentials</h4>
              <p class="text-[11px] text-gray-400 mt-0.5">Official operational accreditation, duty call sign, and station badge validation.</p>
            </div>
          </div>
          <span class="bg-emerald-100 text-emerald-800 text-[10px] font-bold px-2.5 py-1 rounded-md">✓ Verified Accreditation</span>
        </div>

        <div class="grid grid-cols-5 gap-3">
          <div class="bg-[#FAF7F2] p-3 rounded-xl border border-amber-900/5">
            <span class="text-[9px] text-gray-400 font-bold uppercase block mb-1">SUPERVISOR BADGE ID</span>
            <span class="text-xs font-bold text-gray-800 font-mono">SUP-2025-0842</span>
          </div>
          <div class="bg-[#FAF7F2] p-3 rounded-xl border border-amber-900/5">
            <span class="text-[9px] text-gray-400 font-bold uppercase block mb-1">RADIO CALL SIGN</span>
            <span class="text-xs font-bold text-gray-900 uppercase">ALPHA-LEAD-1</span>
          </div>
          <div class="bg-[#FAF7F2] p-3 rounded-xl border border-amber-900/5">
            <span class="text-[9px] text-gray-400 font-bold uppercase block mb-1">ACCREDITATION</span>
            <span class="text-xs font-bold text-gray-800">PRC Registered Lead</span>
          </div>
          <div class="bg-[#FAF7F2] p-3 rounded-xl border border-amber-900/5">
            <span class="text-[9px] text-gray-400 font-bold uppercase block mb-1">ISSUED ON</span>
            <span class="text-xs font-bold text-gray-800">Oct 12, 2023</span>
          </div>
          <div class="bg-[#FAF7F2] p-3 rounded-xl border border-amber-900/5">
            <span class="text-[9px] text-gray-400 font-bold uppercase block mb-1">STATION ASSIGNMENT</span>
            <span class="text-xs font-bold text-gray-800">Malolos Hub</span>
          </div>
        </div>

        <div class="bg-amber-50/50 border border-amber-900/10 p-3 rounded-xl flex items-center gap-2 text-[11px] text-gray-600">
          <i class="fa-solid fa-circle-info text-amber-700"></i>
          <span>For credential renewals, radio call sign changes, or ID reprints, coordinate with HR & Dispatch Security.</span>
        </div>
      </div>

      <!-- SECTION 3: Shift & Dispatch Preferences -->
      <div class="bg-white/90 border border-amber-900/10 rounded-2xl p-6 shadow-sm space-y-5">
        <div class="flex items-center justify-between border-b border-gray-100 pb-3">
          <div class="flex items-center gap-2.5">
            <i class="fa-solid fa-[#2D5A43] text-gray-500"></i>
            <div>
              <h4 class="text-sm font-bold text-gray-900 leading-none">Shift & Dispatch Preferences</h4>
              <p class="text-[11px] text-gray-400 mt-0.5">Direct unit parameters, roster alignment, and operational handoff settings.</p>
            </div>
          </div>
          <span class="bg-orange-100 text-orange-800 text-[10px] font-bold px-2.5 py-1 rounded-md">Field Protocol</span>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div class="bg-[#FAF7F2] p-4 rounded-xl border border-amber-900/5 flex items-center justify-between">
            <div>
              <span class="text-[9px] text-gray-400 font-bold uppercase block mb-1">ASSIGNED TEAM UNIT</span>
              <h5 class="text-sm font-bold text-gray-900">Alpha Field Team</h5>
              <p class="text-[11px] text-gray-500">8 Specialists Active on Roster</p>
            </div>
            <i class="fa-solid fa-users text-gray-400 text-xl"></i>
          </div>

          <div class="bg-[#FAF7F2] p-4 rounded-xl border border-amber-900/5 flex items-center justify-between">
            <div>
              <span class="text-[9px] text-gray-400 font-bold uppercase block mb-1">DEFAULT SHIFT WINDOW</span>
              <h5 class="text-sm font-bold text-gray-900">Morning Field Shift</h5>
              <p class="text-[11px] text-gray-500">08:00 AM – 05:00 PM PHT</p>
            </div>
            <i class="fa-regular fa-clock text-gray-400 text-xl"></i>
          </div>
        </div>

        <div class="bg-gray-50 p-4 rounded-xl border border-gray-200/80 flex items-center justify-between">
          <div class="flex items-center gap-3">
            <i class="fa-solid fa-sliders text-gray-500"></i>
            <div>
              <h5 class="text-xs font-bold text-gray-800">Dispatch Rules & Automation Protocols</h5>
              <p class="text-[11px] text-gray-500">Task escalations, broadcast triggers, and route parameters are managed under field dispatch settings.</p>
            </div>
          </div>
          <a href="#" class="text-xs font-bold text-gray-800 hover:underline flex items-center gap-1">
            Go to Field & Dispatch <i class="fa-solid fa-arrow-right text-[10px]"></i>
          </a>
        </div>
      </div>

      <!-- SECTION 4: Account Safeguards & Operational Hand-off -->
      <div class="bg-white/90 border border-amber-900/10 rounded-2xl p-6 shadow-sm space-y-5">
        <div class="flex items-center justify-between border-b border-gray-100 pb-3">
          <div class="flex items-center gap-2.5">
            <i class="fa-solid fa-user-shield text-gray-500"></i>
            <div>
              <h4 class="text-sm font-bold text-gray-900 leading-none">Account Safeguards & Operational Hand-off</h4>
              <p class="text-[11px] text-gray-400 mt-0.5">Duty delegation, shift relief routing, and credential status management.</p>
            </div>
          </div>
          <span class="bg-gray-100 text-gray-600 text-[10px] font-bold px-2.5 py-1 rounded-md">OHANA PROTOCOL v4.19</span>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <!-- Request Shift Relief Box -->
          <div class="bg-[#FAF7F2] p-4 rounded-xl border border-amber-900/10 space-y-3">
            <div class="flex items-center justify-between">
              <h5 class="text-xs font-bold text-gray-800">Request Shift Relief</h5>
              <span class="bg-amber-100 text-amber-800 text-[9px] font-bold px-2 py-0.5 rounded">Hand-off Protocol</span>
            </div>
            <p class="text-[11px] text-gray-500 leading-relaxed">
              Temporarily handover dispatch authority and pending field escalations to designated standby supervisor (Mark Ramos).
            </p>
            <button class="w-full bg-gray-200/80 hover:bg-gray-300 text-gray-700 text-xs font-bold py-2 rounded-xl transition">
              Pause Duty Privileges / Deactivate Duty Lead
            </button>
          </div>

          <!-- Delete / Revoke Account Box -->
          <div class="bg-red-50/40 p-4 rounded-xl border border-red-200/60 space-y-3">
            <div class="flex items-center justify-between">
              <h5 class="text-xs font-bold text-red-900">Delete / Revoke Account</h5>
              <span class="bg-red-100 text-red-800 text-[9px] font-bold px-2 py-0.5 rounded uppercase">ADMIN ACTION</span>
            </div>
            <p class="text-[11px] text-red-700/80 leading-relaxed">
              Permanently revoke supervisor credentials, radio call sign assignment, and system access. Requires system admin confirmation.
            </p>
            <button class="w-full bg-red-100/80 hover:bg-red-200 text-red-800 text-xs font-bold py-2 rounded-xl transition">
              Deactivate Account / Revoke Access
            </button>
          </div>
        </div>
      </div>

    </div>
  </main>

</body>
</html>