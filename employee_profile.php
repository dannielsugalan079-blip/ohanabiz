<?php
require_once __DIR__ . '/config/auth.php';
requireEmployeeLogin();
$user = currentUser('employee');

$profile_stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$profile_stmt->execute([$user['id']]);
$profile = $profile_stmt->fetch(PDO::FETCH_ASSOC);
$total_tasks_stmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE assigned_to = ?");
$total_tasks_stmt->execute([$user['id']]);
$total_tasks = (int)$total_tasks_stmt->fetchColumn();
$done_tasks_stmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE assigned_to = ? AND status = 'completed'");
$done_tasks_stmt->execute([$user['id']]);
$done_tasks = (int)$done_tasks_stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ohana Employee Portal - Profile & Settings</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background-color: #f8f6f0;
            color: #2d3748;
        }
    </style>
</head>
<body class="bg-[#f8f6f0] text-gray-800 antialiased">

    <div class="flex min-h-screen">
        
        <!-- SIDEBAR -->
        <aside class="w-64 bg-white border-r border-gray-200/70 flex flex-col justify-between shrink-0 sticky top-0 h-screen z-40 hidden lg:flex">
            <div>
                <!-- Top Brand Logo -->
                <a href="employee_dashboard.php" class="p-4 border-b border-gray-100 flex items-center gap-2.5 group">
                    <div class="w-8 h-8 rounded-lg bg-white p-0.5 border border-gray-200 shadow-xs flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                        <img src="assets/images/ohana.png" alt="OHANA Logo" class="w-full h-full object-contain">
                    </div>
                    <div>
                        <span class="font-bold text-xs text-gray-900 tracking-tight block">Ohana Specialist</span>
                        <span class="text-[9px] uppercase tracking-wider text-gray-400 font-semibold block">Enterprise Portal</span>
                    </div>
                </a>

                <div class="p-3 mx-3 my-2.5 bg-[#f9f7f4] rounded-xl border border-gray-200/60 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 inline-block"></span>
                        <span class="text-xs font-bold text-gray-900">Specialist Desk</span>
                    </div>
                    <span class="text-[10px] bg-emerald-100 text-emerald-800 font-bold px-2 py-0.5 rounded-full">Online</span>
                </div>

                <nav class="px-3 space-y-0.5 text-xs font-medium">
                    <a href="employee_dashboard.php" class="flex items-center gap-3 px-3 py-2 rounded-xl text-gray-600 hover:bg-gray-100/70 transition-colors">
                        <i class="fa-solid fa-house text-xs"></i> My Dashboard
                    </a>
                    <a href="employee_task_management.php" class="flex items-center gap-3 px-3 py-2 rounded-xl text-gray-600 hover:bg-gray-100/70 transition-colors">
                        <i class="fa-solid fa-list-check text-xs"></i> My Tasks & Directives
                    </a>
                    <a href="employee_calendar.php" class="flex items-center gap-3 px-3 py-2 rounded-xl text-gray-600 hover:bg-gray-100/70 transition-colors">
                        <i class="fa-regular fa-calendar text-xs"></i> Calendar & Leaves
                    </a>
                    <a href="employee_notifications.php" class="flex items-center justify-between px-3 py-2 rounded-xl text-gray-600 hover:bg-gray-100/70 transition-colors">
                        <div class="flex items-center gap-3">
                            <i class="fa-regular fa-bell text-xs"></i> Notifications
                        </div>
                        <span class="bg-amber-100 text-amber-800 text-[10px] font-bold px-2 py-0.5 rounded-full">3</span>
                    </a>
                    <a href="employee_profile.php" class="flex items-center gap-3 px-3 py-2 rounded-xl bg-[#1c482c] text-white font-semibold shadow-sm">
                        <i class="fa-solid fa-gear text-xs"></i> Profile & Settings
                    </a>
                </nav>
            </div>

            <div class="p-3 border-t border-gray-100">
                <a href="logout.php?portal=employee" class="flex items-center gap-2 text-xs text-red-600 font-semibold px-3 py-1.5 hover:bg-red-50 rounded-lg transition-colors">
                    <i class="fa-solid fa-right-from-bracket"></i> Log Out
                </a>
            </div>
        </aside>

        <!-- MAIN CONTENT WRAPPER -->
        <div class="flex-1 flex flex-col min-w-0">
            
            <!-- TOP HEADER -->
            <header class="bg-white border-b border-gray-200/60 h-14 px-6 flex items-center justify-between sticky top-0 z-30">
                <div class="flex items-center gap-4 flex-1 max-w-md">
                    <div class="relative w-full">
                        <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                        <input type="text" placeholder="Search tasks, operational directives, policies..." class="w-full bg-[#f9f7f4] border border-gray-200 rounded-xl pl-9 pr-4 py-1.5 text-xs focus:outline-none focus:border-[#1c482c] transition-all">
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <button class="w-7 h-7 rounded-full bg-[#f9f7f4] flex items-center justify-center text-gray-500 hover:bg-gray-200/60 transition-colors relative">
                        <i class="fa-regular fa-bell text-xs"></i>
                        <span class="absolute top-1 right-1 w-2 h-2 bg-amber-500 rounded-full"></span>
                    </button>
                    <button class="w-7 h-7 rounded-full bg-[#f9f7f4] flex items-center justify-center text-gray-500 hover:bg-gray-200/60 transition-colors">
                        <i class="fa-regular fa-circle-question text-xs"></i>
                    </button>
                    <div class="h-5 w-[1px] bg-gray-200"></div>
                    <div class="flex items-center gap-2.5">
                        <div class="w-7 h-7 rounded-full bg-emerald-800 text-white font-bold flex items-center justify-center text-xs">
                            <?= htmlspecialchars($user['initials']) ?>
                        </div>
                        <div class="text-left hidden sm:block">
                            <span class="block text-xs font-bold text-gray-900 leading-tight"><?= htmlspecialchars($profile['full_name'] ?? $user['full_name']) ?></span>
                            <span class="block text-[10px] text-gray-400"><?= htmlspecialchars($profile['position'] ?? $user['position'] ?? 'Specialist') ?></span>
                        </div>
                    </div>
                </div>
            </header>

            <!-- PROFILE CONTAINER -->
            <main class="p-4 md:p-6 space-y-4 max-w-7xl w-full mx-auto">

                <!-- PROFILE HERO BANNER -->
                <div class="bg-white p-5 rounded-2xl border border-gray-200/60 shadow-sm space-y-4">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                        <div class="flex items-center gap-4">
                            <div class="relative">
                                <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=200&auto=format&fit=crop&q=80" alt="<?= htmlspecialchars($profile['full_name'] ?? $user['full_name']) ?>" class="w-16 h-16 rounded-2xl object-cover border-2 border-white shadow-md">
                                <span class="absolute -bottom-1 -right-1 w-4 h-4 bg-emerald-500 border-2 border-white rounded-full"></span>
                            </div>
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">SPECIALIST WORKSPACE</span>
                                    <span class="text-gray-300">•</span>
                                    <span class="text-[10px] font-mono text-gray-500">EMP-<?= htmlspecialchars($profile['id']) ?></span>
                                </div>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h1 class="text-xl md:text-2xl font-extrabold text-gray-900 tracking-tight"><?= htmlspecialchars($profile['full_name'] ?? $user['full_name']) ?></h1>
                                    <span class="bg-emerald-50 text-emerald-700 border border-emerald-200/60 text-[10px] font-bold px-2 py-0.5 rounded-full flex items-center gap-1">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Active Full-time
                                    </span>
                                    <span class="bg-gray-100 text-gray-700 text-[10px] font-semibold px-2 py-0.5 rounded-full">
                                        Bulacan Main Hub
                                    </span>
                                </div>
                                <p class="text-xs text-gray-500 font-medium">
                                    <?= htmlspecialchars($profile['position'] ?? $user['position'] ?? 'Specialist') ?> <span class="text-gray-300">•</span> <?= htmlspecialchars($profile['department'] ?? $user['department'] ?? 'Operations') ?>
                                </p>
                            </div>
                        </div>

                        <!-- RIGHT META INFO IN BANNER -->
                        <div class="flex items-center gap-4 bg-[#f9f7f4] border border-gray-200/60 p-3 rounded-xl w-full md:w-auto justify-between md:justify-start">
                            <div>
                                <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wider block">ASSIGNED LEAD</span>
                                <span class="text-xs font-bold text-gray-900 block">System Assigned</span>
                                <span class="text-[10px] text-gray-500 block">Lead Officer</span>
                            </div>
                            <div class="h-8 w-[1px] bg-gray-200"></div>
                            <div>
                                <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wider block">SHIFT WINDOW</span>
                                <span class="text-xs font-bold text-gray-900 block">08:00 - 17:00</span>
                                <span class="text-[10px] text-gray-500 block">Philippine Standard Time</span>
                            </div>
                        </div>
                    </div>

                    <!-- TABS NAVIGATION -->
                    <div class="flex items-center gap-6 border-t border-gray-100 pt-3 text-xs font-semibold overflow-x-auto">
                        <a href="#" class="text-[#1c482c] border-b-2 border-[#1c482c] pb-2 flex items-center gap-2 shrink-0">
                            <i class="fa-regular fa-user"></i> Personal Information
                        </a>
                        <a href="#" class="text-gray-500 hover:text-gray-900 pb-2 flex items-center gap-2 shrink-0 transition-colors">
                            <i class="fa-solid fa-shield-halved"></i> Account & Security
                        </a>
                        <a href="#" class="text-gray-500 hover:text-gray-900 pb-2 flex items-center gap-2 shrink-0 transition-colors">
                            <i class="fa-solid fa-sliders"></i> Preferences & Notifications
                        </a>
                        <a href="#" class="text-gray-500 hover:text-gray-900 pb-2 flex items-center gap-2 shrink-0 transition-colors">
                            <i class="fa-solid fa-scale-balanced"></i> Support & Legal
                        </a>
                    </div>
                </div>

                <!-- MAIN DETAILS GRID -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-start">
                    
                    <!-- LEFT COLUMN: DOSSIER & CONTACT RECORDS -->
                    <div class="lg:col-span-8 bg-white p-5 rounded-2xl border border-gray-200/60 shadow-sm space-y-4">
                        <div class="flex justify-between items-center pb-2 border-b border-gray-100">
                            <div>
                                <h2 class="font-extrabold text-sm text-gray-900">Personnel Dossier & Contact Records</h2>
                                <p class="text-[11px] text-gray-500">Institutional records synchronized with Ohana Central HRIS.</p>
                            </div>
                            <span class="bg-emerald-50 text-emerald-800 border border-emerald-200/60 text-[10px] font-bold px-2.5 py-1 rounded-full flex items-center gap-1.5">
                                <i class="fa-solid fa-circle-check text-[10px] text-emerald-600"></i> Verified Personnel
                            </span>
                        </div>

                        <!-- 2-COLUMN FORM FIELDS -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs">
                            
                            <!-- FULL LEGAL NAME -->
                            <div class="space-y-1">
                                <label class="font-bold text-[10px] text-gray-400 uppercase tracking-wider block">Full Legal Name</label>
                                <div class="bg-[#f9f7f4] border border-gray-200/70 rounded-xl px-3 py-2 text-gray-800 font-medium flex items-center gap-2">
                                    <i class="fa-regular fa-user text-gray-400 text-xs"></i> <?= htmlspecialchars($profile['full_name'] ?? '') ?>
                                </div>
                            </div>

                            <!-- EMPLOYEE ID -->
                            <div class="space-y-1">
                                <label class="font-bold text-[10px] text-gray-400 uppercase tracking-wider block">Employee ID</label>
                                <div class="bg-[#f9f7f4] border border-gray-200/70 rounded-xl px-3 py-2 text-gray-800 font-mono font-medium flex items-center gap-2">
                                    <i class="fa-solid fa-id-badge text-gray-400 text-xs"></i> EMP-<?= htmlspecialchars($profile['id'] ?? '') ?>
                                </div>
                            </div>

                            <!-- ORGANIZATIONAL EMAIL -->
                            <div class="space-y-1">
                                <label class="font-bold text-[10px] text-gray-400 uppercase tracking-wider block">Organizational Email</label>
                                <div class="bg-[#f9f7f4] border border-gray-200/70 rounded-xl px-3 py-2 text-gray-800 font-medium flex items-center gap-2">
                                    <i class="fa-regular fa-envelope text-gray-400 text-xs"></i> <?= htmlspecialchars($profile['email'] ?? '') ?>
                                </div>
                            </div>

                            <!-- PRIMARY MOBILE DISPATCH -->
                            <div class="space-y-1">
                                <label class="font-bold text-[10px] text-gray-400 uppercase tracking-wider block">Primary Mobile Dispatch</label>
                                <div class="bg-[#f9f7f4] border border-gray-200/70 rounded-xl px-3 py-2 text-gray-800 font-medium flex items-center gap-2">
                                    <i class="fa-solid fa-phone text-gray-400 text-xs"></i> +63 900 000 0000
                                </div>
                            </div>

                            <!-- DESIGNATED TITLE -->
                            <div class="space-y-1">
                                <label class="font-bold text-[10px] text-gray-400 uppercase tracking-wider block">Designated Title</label>
                                <div class="bg-[#f9f7f4] border border-gray-200/70 rounded-xl px-3 py-2 text-gray-800 font-medium flex items-center gap-2">
                                    <i class="fa-solid fa-briefcase text-gray-400 text-xs"></i> <?= htmlspecialchars($profile['position'] ?? 'Specialist') ?>
                                </div>
                            </div>

                            <!-- DEPARTMENT DIVISION -->
                            <div class="space-y-1">
                                <label class="font-bold text-[10px] text-gray-400 uppercase tracking-wider block">Department Division</label>
                                <div class="bg-[#f9f7f4] border border-gray-200/70 rounded-xl px-3 py-2 text-gray-800 font-medium flex items-center gap-2">
                                    <i class="fa-solid fa-building text-gray-400 text-xs"></i> <?= htmlspecialchars($profile['department'] ?? 'Operations') ?>
                                </div>
                            </div>

                            <!-- DIRECT REPORTING OFFICER -->
                            <div class="space-y-1">
                                <label class="font-bold text-[10px] text-gray-400 uppercase tracking-wider block">Direct Reporting Officer</label>
                                <div class="bg-[#f9f7f4] border border-gray-200/70 rounded-xl px-3 py-2 text-gray-800 font-medium flex items-center gap-2">
                                    <i class="fa-solid fa-user-tie text-gray-400 text-xs"></i> Maria Santos (Operations Director)
                                </div>
                            </div>

                            <!-- ASSIGNED SHIFT CADENCE -->
                            <div class="space-y-1">
                                <label class="font-bold text-[10px] text-gray-400 uppercase tracking-wider block">Assigned Shift Cadence</label>
                                <div class="bg-[#f9f7f4] border border-gray-200/70 rounded-xl px-3 py-2 text-gray-800 font-medium flex items-center gap-2">
                                    <i class="fa-regular fa-clock text-gray-400 text-xs"></i> Regular Day Shift (08:00 AM - 05:00 PM)
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- RIGHT COLUMN: STATIONARY DEPLOYMENT & ACCREDITATIONS -->
                    <div class="lg:col-span-4 space-y-4">
                        
                        <!-- STATIONARY DEPLOYMENT CARD -->
                        <div class="bg-white p-4 rounded-2xl border border-gray-200/60 shadow-sm space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">STATIONARY DEPLOYMENT</span>
                                <span class="bg-amber-100 text-amber-900 text-[9px] font-bold px-2 py-0.5 rounded-full">On-Premise</span>
                            </div>

                            <div>
                                <h3 class="font-extrabold text-sm text-gray-900">Bulacan Central Hub</h3>
                                <p class="text-[11px] text-gray-500">2nd Floor, Corporate Tower, MacArthur Highway, City of Malolos, Bulacan.</p>
                            </div>

                            <div class="bg-[#f9f7f4] border border-gray-200/60 rounded-xl p-2.5 text-center space-y-2">
                                <div class="relative rounded-lg overflow-hidden border border-gray-200">
                                    <img src="https://images.unsplash.com/photo-1497366216548-37526070297c?w=400&auto=format&fit=crop&q=80" alt="Desk Station" class="w-full h-24 object-cover">
                                    <div class="absolute inset-0 bg-black/20 flex items-end p-2">
                                        <span class="bg-white/90 backdrop-blur-sm text-gray-900 text-[10px] font-bold px-2 py-0.5 rounded-md shadow-xs">
                                            <i class="fa-solid fa-desktop text-emerald-700 mr-1"></i> Desk Station: Pod #04
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- OPERATIONAL ACCREDITATIONS CARD -->
                        <div class="bg-white p-4 rounded-2xl border border-gray-200/60 shadow-sm space-y-3">
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">OPERATIONAL ACCREDITATIONS</span>

                            <div class="space-y-2 text-xs">
                                <div class="bg-[#f9f7f4] p-2.5 rounded-xl border border-gray-200/50 flex items-start gap-2.5">
                                    <div class="w-7 h-7 rounded-lg bg-emerald-100 text-emerald-800 flex items-center justify-center shrink-0 text-xs mt-0.5">
                                        <i class="fa-solid fa-certificate"></i>
                                    </div>
                                    <div>
                                        <strong class="text-gray-900 block text-[11px]">Legal Compliance Reviewer</strong>
                                        <span class="text-[10px] text-emerald-700 font-medium block">Valid until Oct 2026 • Certified</span>
                                    </div>
                                </div>

                                <div class="bg-[#f9f7f4] p-2.5 rounded-xl border border-gray-200/50 flex items-start gap-2.5">
                                    <div class="w-7 h-7 rounded-lg bg-amber-100 text-amber-800 flex items-center justify-center shrink-0 text-xs mt-0.5">
                                        <i class="fa-solid fa-shield-heart"></i>
                                    </div>
                                    <div>
                                        <strong class="text-gray-900 block text-[11px]">Data Privacy Level 2 Handler</strong>
                                        <span class="text-[10px] text-gray-500 font-medium block">National Privacy Commission Compliant</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                </div>

                <!-- FOOTER ACTIONS -->
                <div class="bg-white px-5 py-3 rounded-2xl border border-gray-200/60 shadow-sm flex flex-col md:flex-row justify-between items-center gap-3">
                    <div class="flex items-center gap-2 text-[11px] text-gray-500">
                        <i class="fa-solid fa-rotate text-emerald-600"></i>
                        <span>All changes are synchronized with Ohana Central HRIS</span>
                    </div>

                    <div class="flex items-center gap-2 w-full md:w-auto justify-end">
                        <button class="bg-[#f9f7f4] hover:bg-gray-100 text-gray-700 border border-gray-200 font-semibold py-2 px-4 rounded-xl text-xs transition-all">
                            Discard Changes
                        </button>
                        <button class="bg-[#1c482c] hover:bg-[#153721] text-white font-semibold py-2 px-4 rounded-xl text-xs flex items-center gap-1.5 shadow-sm transition-all">
                            <i class="fa-solid fa-check text-[10px]"></i> Save Changes
                        </button>
                    </div>
                </div>

            </main>
        </div>
    </div>

</body>
</html>