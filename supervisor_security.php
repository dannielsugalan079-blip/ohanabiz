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
  <title>Ohana System - Supervisor Security & Access</title>
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
        
        <a href="#" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-gray-600 hover:bg-black/5 text-sm font-medium transition">
          <i class="fa-solid fa-border-all text-base"></i>
          Operations Dashboard
        </a>
        
        <a href="#" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-gray-600 hover:bg-black/5 text-sm font-medium transition">
          <i class="fa-solid fa-list-check text-base"></i>
          Dispatch & Tasks
        </a>

        <a href="#" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-gray-600 hover:bg-black/5 text-sm font-medium transition">
          <i class="fa-regular fa-address-book text-base"></i>
          Team Attendance
        </a>

        <a href="#" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-gray-600 hover:bg-black/5 text-sm font-medium transition">
          <i class="fa-regular fa-calendar-check text-base"></i>
          Leave Endorsement
        </a>

        <a href="#" class="flex items-center gap-3 px-3 py-2.5 rounded-xl bg-[#2D5A43] text-white text-sm font-medium shadow-sm">
          <i class="fa-solid fa-user-gear text-base"></i>
          Settings & Profile
        </a>
      </div>
    </div>

    <!-- Sidebar Footer -->
    <div class="space-y-4">
      <a href="#" class="flex items-center gap-3 px-3 py-2 text-gray-600 hover:text-gray-900 text-sm font-medium">
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
          <h2 class="text-2xl font-bold text-gray-900">Supervisor Security & Access</h2>
          <p class="text-xs text-gray-500 mt-0.5">Manage supervisor operational credentials, assigned dispatch unit, duty notifications, and security access.</p>
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
              <span class="bg-emerald-100/80 text-emerald-800 text-[9px] font-bold px-2 py-0.5 rounded uppercase tracking-wider">• ACTIVE SUPERVISOR</span>
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
        <a href="supervisor_settings.php" class="text-gray-600 hover:bg-black/5 px-4 py-2 rounded-xl text-xs font-medium transition block">
          Profile Information
        </a>
        <a href="supervisor_dispatch.php" class="text-gray-600 hover:bg-black/5 px-4 py-2 rounded-xl text-xs font-medium transition block">
          Field & Dispatch Settings
        </a>
        <a href="supervisor_security.php" class="bg-[#2D5A43] text-white px-4 py-2 rounded-xl text-xs font-semibold shadow-sm flex items-center gap-1.5 block">
          <i class="fa-solid fa-shield-halved text-xs"></i>
          Security & Access
        </a>
        <a href="supervisor_shift_notifications.php" class="text-gray-600 hover:bg-black/5 px-4 py-2 rounded-xl text-xs font-medium transition flex items-center gap-2 block">
          <i class="fa-regular fa-bell text-xs"></i>
          Shift Notifications
          <span class="w-2 h-2 rounded-full bg-amber-500"></span>
        </a>
      </div>

      <!-- GRID LAYOUT (Main Content + Right Sidebar) -->
      <div class="grid grid-cols-3 gap-6">

        <!-- LEFT 2 COLUMNS: Main Security Sections -->
        <div class="col-span-2 space-y-6">

          <!-- Authentication & Credentials Section -->
          <div class="bg-white/90 border border-amber-900/10 rounded-2xl p-6 shadow-sm space-y-5">
            <div class="flex items-center justify-between border-b border-gray-100 pb-3">
              <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-800 flex items-center justify-center text-sm">
                  <i class="fa-solid fa-lock"></i>
                </div>
                <div>
                  <h4 class="text-sm font-bold text-gray-900 leading-none">Authentication & Credentials</h4>
                  <p class="text-[11px] text-gray-400 mt-0.5">Manage password strength, enterprise multi-factor authentication, and biometrics.</p>
                </div>
              </div>
              <span class="bg-gray-100 text-gray-600 text-[10px] font-bold px-2.5 py-1 rounded-md flex items-center gap-1">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span> Secured Node
              </span>
            </div>

            <!-- Password Card -->
            <div class="bg-[#FAF7F2] p-4 rounded-xl border border-amber-900/5 space-y-3">
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <h5 class="text-xs font-bold text-gray-900">Supervisor Access Password</h5>
                  <span class="bg-emerald-800 text-white text-[9px] font-bold px-2 py-0.5 rounded">Strong</span>
                </div>
                <button class="bg-white hover:bg-gray-50 border border-gray-200 text-gray-700 px-3 py-1.5 rounded-lg text-xs font-bold shadow-sm transition">
                  Change Password
                </button>
              </div>
              <p class="text-[11px] text-gray-500">Last rotated 14 days ago • Policy requires rotation every 90 days</p>
              <div class="text-[11px] text-gray-500 flex items-center gap-1">
                <i class="fa-solid fa-rotate-left text-gray-400 text-[10px]"></i>
                <span>Last Audit Log: Password reset via SMS OTP Oct 12, 2024 at Malolos Station</span>
              </div>
            </div>

            <!-- Two-Factor Authentication (2FA / MFA) -->
            <div class="space-y-3">
              <div class="flex items-center justify-between">
                <div>
                  <h5 class="text-xs font-bold text-gray-900">Two-Factor Authentication (2FA / MFA)</h5>
                  <p class="text-[11px] text-gray-400">Mandatory verification challenge required for field operations and dispatch overrides.</p>
                </div>
                <!-- Toggle Switch -->
                <label class="relative inline-flex items-center cursor-pointer">
                  <input type="checkbox" checked class="sr-only peer">
                  <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#2D5A43]"></div>
                </label>
              </div>

              <div class="grid grid-cols-2 gap-3">
                <!-- Authenticator App -->
                <div class="bg-[#FAF7F2] p-3.5 rounded-xl border border-amber-900/5 flex items-start justify-between">
                  <div class="flex items-start gap-2.5">
                    <i class="fa-solid fa-mobile-screen-button text-gray-500 mt-0.5"></i>
                    <div>
                      <div class="flex items-center gap-1.5">
                        <span class="text-xs font-bold text-gray-900">Authenticator App (TOTP)</span>
                        <span class="bg-gray-200 text-gray-700 text-[9px] font-bold px-1.5 py-0.2 rounded">Primary</span>
                      </div>
                      <p class="text-[10px] text-gray-500">Google / Microsoft Authenticator</p>
                      <span class="text-[10px] font-bold text-emerald-700 block mt-1">Configured & Active</span>
                    </div>
                  </div>
                  <i class="fa-solid fa-sliders text-gray-400 text-xs"></i>
                </div>

                <!-- SMS Backup Channel -->
                <div class="bg-[#FAF7F2] p-3.5 rounded-xl border border-amber-900/5 flex items-start justify-between">
                  <div class="flex items-start gap-2.5">
                    <i class="fa-regular fa-comment-dots text-gray-500 mt-0.5"></i>
                    <div>
                      <div class="flex items-center gap-1.5">
                        <span class="text-xs font-bold text-gray-900">SMS Backup Channel</span>
                        <span class="bg-gray-200 text-gray-700 text-[9px] font-bold px-1.5 py-0.2 rounded">Backup</span>
                      </div>
                      <p class="text-[10px] text-gray-500">+63 997 855 1913</p>
                      <span class="text-[10px] font-bold text-emerald-700 block mt-1">Verified SIM Profile</span>
                    </div>
                  </div>
                  <i class="fa-solid fa-pen text-gray-400 text-xs"></i>
                </div>
              </div>
            </div>

            <!-- Emergency Backup Codes -->
            <div class="bg-[#FAF7F2] p-4 rounded-xl border border-amber-900/5 flex items-center justify-between">
              <div class="flex items-center gap-3">
                <i class="fa-solid fa-key text-gray-500"></i>
                <div>
                  <h5 class="text-xs font-bold text-gray-900">One-Time Emergency Backup Codes</h5>
                  <p class="text-[11px] text-gray-500">8 remaining of 10 generated offline safety codes</p>
                </div>
              </div>
              <button class="bg-white border border-gray-200 text-gray-700 px-3 py-1.5 rounded-lg text-xs font-bold shadow-sm hover:bg-gray-50 transition">
                Generate New Codes
              </button>
            </div>

            <!-- Biometric Device Unlock -->
            <div class="bg-[#FAF7F2] p-4 rounded-xl border border-amber-900/5 flex items-center justify-between">
              <div class="flex items-center gap-3">
                <i class="fa-solid fa-fingerprint text-gray-500 text-lg"></i>
                <div>
                  <div class="flex items-center gap-2">
                    <h5 class="text-xs font-bold text-gray-900">Biometric Device Unlock (WebAuthn / Passkey)</h5>
                    <span class="bg-emerald-100 text-emerald-800 text-[9px] font-bold px-1.5 py-0.2 rounded">Active</span>
                  </div>
                  <p class="text-[11px] text-gray-500">Allow Touch ID, Windows Hello, and hardware FIDO2 keys for instantaneous supervisor re-authentication during field maneuvers.</p>
                </div>
              </div>
              <!-- Toggle Switch -->
              <label class="relative inline-flex items-center cursor-pointer ml-4">
                <input type="checkbox" checked class="sr-only peer">
                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#2D5A43]"></div>
              </label>
            </div>
          </div>

          <!-- Authorized Devices & Current Sessions Section -->
          <div class="bg-white/90 border border-amber-900/10 rounded-2xl p-6 shadow-sm space-y-4">
            <div class="flex items-center justify-between border-b border-gray-100 pb-3">
              <div class="flex items-center gap-2.5">
                <i class="fa-solid fa-laptop-code text-gray-500"></i>
                <div>
                  <h4 class="text-sm font-bold text-gray-900 leading-none">Authorized Devices & Current Sessions</h4>
                  <p class="text-[11px] text-gray-400 mt-0.5">Real-time terminal connections linked to supervisor badge ID SUP-2025-0842.</p>
                </div>
              </div>
              <button class="bg-red-50 text-red-700 hover:bg-red-100 border border-red-200 px-3 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5">
                <i class="fa-solid fa-right-from-bracket text-xs"></i>
                Terminate Other Sessions
              </button>
            </div>

            <!-- Session 1: Workstation -->
            <div class="bg-[#FAF7F2] p-4 rounded-xl border border-amber-900/5 flex items-center justify-between">
              <div class="flex items-start gap-3">
                <i class="fa-solid fa-desktop text-gray-500 mt-1"></i>
                <div class="space-y-0.5">
                  <div class="flex items-center gap-2">
                    <h5 class="text-xs font-bold text-gray-900">Malolos Operational Hub Workstation</h5>
                    <span class="bg-emerald-800 text-white text-[9px] font-bold px-2 py-0.5 rounded">Current Active Session</span>
                  </div>
                  <p class="text-[11px] text-gray-500">Chrome 128.0 on macOS Sonoma 14.5 • Enterprise Local Gateway</p>
                  <div class="text-[10px] text-gray-400 font-mono">
                    IP: 120.29.74.18 (Static Facility Node) • Started 07:54 AM PHT
                  </div>
                </div>
              </div>
              <span class="text-[10px] font-bold text-gray-700 uppercase tracking-wider">ONLINE NOW</span>
            </div>

            <!-- Session 2: Field Tablet -->
            <div class="bg-[#FAF7F2] p-4 rounded-xl border border-amber-900/5 flex items-center justify-between">
              <div class="flex items-start gap-3">
                <i class="fa-solid fa-tablet-screen-button text-gray-500 mt-1"></i>
                <div class="space-y-0.5">
                  <div class="flex items-center gap-2">
                    <h5 class="text-xs font-bold text-gray-900">Supervisor Field Tablet</h5>
                    <span class="text-[11px] text-gray-500">Samsung Galaxy Tab Active4 Pro</span>
                  </div>
                  <p class="text-[11px] text-gray-500">Ohana Mobile Field Supervisor Edition v4.19 • Knox Secure Container</p>
                  <div class="text-[10px] text-gray-400 font-mono">
                    Last active 12 mins ago at Guiguinto Route Segment • Cellular 5G
                  </div>
                </div>
              </div>
              <button class="bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 px-3 py-1.5 rounded-lg text-xs font-bold shadow-sm transition">
                Sign Out Device
              </button>
            </div>
          </div>

        </div>

        <!-- RIGHT 1 COLUMN: Widgets (Security Posture & Recent Activity) -->
        <div class="space-y-6">

          <!-- Security Posture Widget -->
          <div class="bg-white/90 border border-amber-900/10 rounded-2xl p-5 shadow-sm space-y-4">
            <div class="flex items-center justify-between border-b border-gray-100 pb-3">
              <div class="flex items-center gap-2">
                <i class="fa-regular fa-shield-check text-gray-600"></i>
                <h4 class="text-xs font-bold text-gray-900 uppercase tracking-wider">Security Posture</h4>
              </div>
              <span class="bg-emerald-100 text-emerald-800 text-[9px] font-bold px-2 py-0.5 rounded">COMPLIANT</span>
            </div>

            <div class="space-y-3 text-xs">
              <div class="flex items-center justify-between text-gray-600">
                <span>2FA Enforcement</span>
                <span class="font-bold text-gray-900">Active (100%)</span>
              </div>
              <div class="flex items-center justify-between text-gray-600">
                <span>FIDO2 Passkeys</span>
                <span class="font-bold text-gray-900">2 Registered</span>
              </div>
              <div class="flex items-center justify-between text-gray-600">
                <span>Password Age</span>
                <span class="font-bold text-gray-900">14 / 90 days</span>
              </div>
              <div class="flex items-center justify-between text-gray-600">
                <span>Dispatch Clearance</span>
                <span class="font-bold text-gray-900">Tier 3 Supervisor</span>
              </div>
            </div>
          </div>

          <!-- Recent Activity Widget -->
          <div class="bg-white/90 border border-amber-900/10 rounded-2xl p-5 shadow-sm space-y-4">
            <div class="flex items-center justify-between border-b border-gray-100 pb-3">
              <div class="flex items-center gap-2">
                <i class="fa-solid fa-circle-notch text-gray-600"></i>
                <h4 class="text-xs font-bold text-gray-900 uppercase tracking-wider">Recent Activity</h4>
              </div>
              <span class="text-[9px] font-bold text-gray-400 tracking-wider">LIVE LOG</span>
            </div>

            <div class="space-y-4 relative pl-3 border-l-2 border-gray-100">
              <!-- Log Item 1 -->
              <div class="relative pl-3">
                <span class="w-2 h-2 rounded-full bg-emerald-600 absolute -left-[17px] top-1"></span>
                <h5 class="text-xs font-bold text-gray-800">Station Login Approved</h5>
                <p class="text-[10px] text-gray-400">07:54 AM • Malolos Hub Workstation</p>
              </div>

              <!-- Log Item 2 -->
              <div class="relative pl-3">
                <span class="w-2 h-2 rounded-full bg-emerald-600 absolute -left-[17px] top-1"></span>
                <h5 class="text-xs font-bold text-gray-800">Field Tablet Ping Verified</h5>
                <p class="text-[10px] text-gray-400">07:42 AM • Cellular Knox Gateway</p>
              </div>

              <!-- Log Item 3 -->
              <div class="relative pl-3">
                <span class="w-2 h-2 rounded-full bg-amber-600 absolute -left-[17px] top-1"></span>
                <h5 class="text-xs font-bold text-gray-800">Shift Handover Confirmed</h5>
                <p class="text-[10px] text-gray-400">Yesterday 05:00 PM • Badge Endorsed</p>
              </div>
            </div>
          </div>

        </div>

      </div>

    </div>
  </main>

</body>
</html>