<?php
require_once __DIR__ . '/config/auth.php';
requireLogin('client');
$user = currentUser();

// Fetch client profile
$cl_stmt = $pdo->prepare("SELECT * FROM clients WHERE user_id = ?");
$cl_stmt->execute([$user['id']]);
$client_profile = $cl_stmt->fetch(PDO::FETCH_ASSOC);
$company_name = !empty($client_profile['company_name']) ? $client_profile['company_name'] : (!empty($user['full_name']) ? $user['full_name'] : 'Valued Client');

// Client's service requests
$my_requests_stmt = $pdo->prepare("
    SELECT sr.*, u.full_name as assigned_specialist 
    FROM service_requests sr 
    LEFT JOIN users u ON sr.assigned_to = u.id 
    WHERE sr.client_id = ? 
    ORDER BY sr.requested_at DESC LIMIT 10
");
$my_requests_stmt->execute([$user['id']]);
$my_requests = $my_requests_stmt->fetchAll(PDO::FETCH_ASSOC);

// Request counts by status
$req_counts_stmt = $pdo->prepare("SELECT status, COUNT(*) as c FROM service_requests WHERE client_id = ? GROUP BY status");
$req_counts_stmt->execute([$user['id']]);
$req_counts = [];
foreach ($req_counts_stmt->fetchAll(PDO::FETCH_ASSOC) as $r) { $req_counts[$r['status']] = $r['c']; }
$total_requests = array_sum($req_counts);
$pending_count = $req_counts['pending'] ?? 0;
$in_progress_count = $req_counts['in_progress'] ?? 0;
$completed_count = $req_counts['completed'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enterprise Client Portal - <?= htmlspecialchars($company_name) ?></title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background-color: #f7f5f0;
            color: #2d3748;
        }
    </style>
</head>
<body class="bg-[#f7f5f0] text-gray-800 antialiased">

    <div class="flex min-h-screen">
        
        <!-- SIDEBAR (NAKA-STICKY NA PARA SUMUSUNOD SA SCROLL) -->
        <aside class="w-64 bg-white border-r border-gray-200/65 flex flex-col justify-between shrink-0 sticky top-0 h-screen z-40 hidden lg:flex">
            <div>
                <!-- Top Brand Logo -->
                <a href="client_dashboard.php" class="p-4 border-b border-gray-100 flex items-center gap-2.5 group">
                    <div class="w-8 h-8 rounded-lg bg-white p-0.5 border border-gray-200 shadow-xs flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                        <img src="assets/images/ohana.png" alt="OHANA Logo" class="w-full h-full object-contain">
                    </div>
                    <div>
                        <span class="font-bold text-xs text-gray-900 tracking-tight block">Ohana Portal</span>
                        <span class="text-[9px] uppercase tracking-wider text-gray-400 font-semibold block">Client Workspace</span>
                    </div>
                </a>

                <!-- Client Workspace Selector -->
                <div class="p-4 mx-3 my-3 bg-[#f9f7f4] rounded-xl border border-gray-200/60">
                    <span class="text-[10px] uppercase font-bold text-gray-400 tracking-wider block mb-1">Client Workspace</span>
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-xs text-gray-900 truncate"><?= htmlspecialchars($company_name) ?></span>
                        <i class="fa-solid fa-arrows-rotate text-gray-400 text-xs"></i>
                    </div>
                </div>

                <!-- Navigation Links -->
                <nav class="px-3 space-y-1 text-xs font-medium">
                    <a href="client_dashboard.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl bg-[#1c482c] text-white font-semibold shadow-sm">
                        <i class="fa-solid fa-house text-xs"></i> My Dashboard
                    </a>
                    <a href="client_request_service.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-gray-600 hover:bg-gray-100/70 transition-colors">
                        <i class="fa-solid fa-inbox text-xs"></i> Inquiries & Requests
                    </a>
                    <a href="client_orders_documents.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-gray-600 hover:bg-gray-100/70 transition-colors">
                        <i class="fa-solid fa-file-lines text-xs"></i> Orders & Documents
                    </a>
                    <a href="client_profile_billing.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-gray-600 hover:bg-gray-100/70 transition-colors">
                        <i class="fa-solid fa-id-card text-xs"></i> Profile & Billing
                    </a>
                </nav>
            </div>

            <!-- Sidebar Footer -->
            <div class="p-4 border-t border-gray-100 space-y-3">
                <a href="logout.php" class="flex items-center gap-2 text-xs text-red-600 font-semibold px-3 py-2 hover:bg-red-50 rounded-lg transition-colors">
                    <i class="fa-solid fa-right-from-bracket"></i> Log Out
                </a>
                <div class="flex items-center gap-1.5 text-[10px] text-gray-400 px-3">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 inline-block"></span>
                    <span>Core Nodes Synchronized</span>
                </div>
            </div>
        </aside>

        <!-- MAIN CONTENT WRAPPER -->
        <div class="flex-1 flex flex-col min-w-0">
            
            <!-- TOP HEADER -->
            <header class="bg-white border-b border-gray-200/60 h-16 px-6 flex items-center justify-between sticky top-0 z-30">
                <div class="flex items-center gap-4 flex-1 max-w-md">
                    <div class="relative w-full">
                        <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                        <input type="text" placeholder="Search orders, documents, services..." class="w-full bg-[#f9f7f4] border border-gray-200 rounded-xl pl-9 pr-4 py-2 text-xs focus:outline-none focus:border-[#1c482c] transition-all">
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <button class="w-8 h-8 rounded-full bg-[#f9f7f4] flex items-center justify-center text-gray-500 hover:bg-gray-200/60 transition-colors relative">
                        <i class="fa-regular fa-bell text-xs"></i>
                        <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-emerald-600 rounded-full"></span>
                    </button>
                    <button class="w-8 h-8 rounded-full bg-[#f9f7f4] flex items-center justify-center text-gray-500 hover:bg-gray-200/60 transition-colors">
                        <i class="fa-regular fa-circle-question text-xs"></i>
                    </button>

                    <div class="h-6 w-[1px] bg-gray-200"></div>

                    <div class="flex items-center gap-3">
                        <div class="text-right hidden sm:block">
                            <span class="block text-xs font-bold text-gray-900"><?= htmlspecialchars($user['full_name']) ?></span>
                            <span class="block text-[10px] text-gray-400"><?= htmlspecialchars($company_name) ?></span>
                        </div>
                        <div class="w-9 h-9 rounded-full bg-emerald-800 text-white font-bold flex items-center justify-center text-xs shadow-xs">
                            <?= htmlspecialchars($user['initials']) ?>
                        </div>
                    </div>
                </div>
            </header>

            <!-- DASHBOARD CONTAINER -->
            <main class="p-6 md:p-8 space-y-8 max-w-7xl w-full mx-auto">

                <!-- BREADCRUMB & WELCOME BANNER -->
                <div class="bg-white p-6 rounded-3xl border border-gray-200/60 shadow-sm flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                    <div class="space-y-1.5">
                        <div class="flex items-center gap-2 text-xs text-gray-500">
                            <span class="font-semibold text-gray-700">Enterprise Client Portal</span>
                            <i class="fa-solid fa-chevron-right text-[9px] text-gray-300"></i>
                            <span>Client ID: #<?= htmlspecialchars(!empty($client_profile['account_number']) ? $client_profile['account_number'] : ('CLI-' . str_pad($user['id'], 4, '0', STR_PAD_LEFT) . '-BUL')) ?></span>
                        </div>
                        <h1 class="text-2xl md:text-3xl font-extrabold text-gray-900 tracking-tight">
                            Welcome back, <?= htmlspecialchars($company_name) ?>
                        </h1>
                        <p class="text-xs text-gray-500 leading-relaxed max-w-xl">
                            Manage ongoing filings, order on-demand business printing, and coordinate directly with your dedicated Malolos branch specialist.
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-2.5 shrink-0">
                        <a href="client_request_service.php" class="bg-[#1c482c] hover:bg-[#153721] text-white text-xs font-semibold py-2.5 px-4 rounded-xl shadow-sm flex items-center gap-2 transition-all">
                            <i class="fa-solid fa-plus text-[10px]"></i> Request Service
                        </a>
                        <a href="client_orders_documents.php" class="bg-[#f9f7f4] hover:bg-gray-100 text-gray-700 border border-gray-200 text-xs font-semibold py-2.5 px-4 rounded-xl flex items-center gap-2 transition-all">
                            <i class="fa-solid fa-upload text-[10px]"></i> Upload Files
                        </a>
                        <a href="client_orders_documents.php" class="bg-[#f9f7f4] hover:bg-gray-100 text-gray-700 border border-gray-200 text-xs font-semibold py-2.5 px-4 rounded-xl flex items-center gap-2 transition-all">
                            <i class="fa-solid fa-chart-line text-[10px]"></i> Track Status
                        </a>
                    </div>
                </div>

                <!-- METRICS GRID (3 CARDS) -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Card 1 -->
                    <div class="bg-white p-5 rounded-3xl border border-gray-200/60 shadow-sm relative overflow-hidden space-y-4">
                        <div class="flex justify-between items-start">
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">ACTIVE DIRECTIVES</span>
                            <div class="w-7 h-7 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center text-xs">
                                <i class="fa-solid fa-clock"></i>
                            </div>
                        </div>
                        <div class="flex items-baseline gap-3">
                            <span class="text-4xl font-extrabold text-gray-900"><?= str_pad($in_progress_count, 2, '0', STR_PAD_LEFT) ?></span>
                            <span class="text-xs font-medium text-gray-500">In Progress</span>
                        </div>
                        <div class="pt-2 border-t border-gray-100 flex justify-between items-center text-xs text-gray-500">
                            <span>Service Requests</span>
                            <span class="font-bold text-gray-800">Tracking</span>
                        </div>
                    </div>

                    <!-- Card 2 -->
                    <div class="bg-white p-5 rounded-3xl border border-gray-200/60 shadow-sm relative overflow-hidden space-y-4">
                        <div class="flex justify-between items-start">
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">COMPLETED FILINGS</span>
                            <div class="w-7 h-7 rounded-xl bg-blue-50 text-blue-700 flex items-center justify-center text-xs">
                                <i class="fa-solid fa-circle-check"></i>
                            </div>
                        </div>
                        <div class="flex items-baseline gap-3">
                            <span class="text-4xl font-extrabold text-gray-900"><?= str_pad($completed_count, 2, '0', STR_PAD_LEFT) ?></span>
                            <span class="text-xs font-medium text-gray-500">Completed</span>
                        </div>
                        <div class="pt-2 border-t border-gray-100 flex justify-between items-center text-xs text-gray-500">
                            <span>Total Served</span>
                            <span class="text-gray-400"><i class="fa-solid fa-shield-halved"></i></span>
                        </div>
                    </div>

                    <!-- Card 3 -->
                    <div class="bg-white p-5 rounded-3xl border border-gray-200/60 shadow-sm relative overflow-hidden space-y-4">
                        <div class="flex justify-between items-start">
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">PENDING / REQUIRED ACTION</span>
                            <div class="w-7 h-7 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center text-xs">
                                <i class="fa-solid fa-triangle-exclamation"></i>
                            </div>
                        </div>
                        <div class="flex items-baseline gap-3">
                            <span class="text-4xl font-extrabold text-gray-900"><?= str_pad($pending_count, 2, '0', STR_PAD_LEFT) ?></span>
                            <span class="text-xs font-medium text-amber-600 font-semibold">Pending</span>
                        </div>
                        <div class="pt-2 border-t border-gray-100 flex justify-between items-center text-xs">
                            <span class="text-gray-500 truncate pr-2">Awaiting Action</span>
                            <a href="client_orders_documents.php" class="font-bold text-[#1c482c] hover:underline shrink-0">View All</a>
                        </div>
                    </div>
                </div>

                <!-- SOLUTIONS & BUSINESS SERVICES (4 COLUMNS) -->
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <div class="flex items-center gap-2">
                            <i class="fa-solid fa-layer-group text-[#1c482c] text-sm"></i>
                            <h2 class="font-bold text-base text-gray-900">Solutions & Business Services</h2>
                        </div>
                        <a href="#" class="text-xs font-semibold text-[#1c482c] hover:underline">View Complete Rate Sheet</a>
                    </div>
                    <p class="text-xs text-gray-500 -mt-2">On-demand production, corporate compliance, and liaison coordination</p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                        <!-- Service 1 -->
                        <div class="bg-white p-6 rounded-3xl border border-gray-200/60 shadow-sm flex flex-col justify-between space-y-6 hover:shadow-md transition-all">
                            <div class="space-y-4">
                                <div class="flex justify-between items-center">
                                    <div class="w-10 h-10 rounded-2xl bg-emerald-50 text-[#1c482c] flex items-center justify-center text-sm">
                                        <i class="fa-solid fa-print"></i>
                                    </div>
                                    <span class="text-[10px] font-bold bg-emerald-50 text-emerald-800 px-2.5 py-1 rounded-full">Same-Day</span>
                                </div>
                                <div>
                                    <h3 class="font-bold text-sm text-gray-900">Printing Services</h3>
                                    <p class="text-[11px] text-gray-500 mt-1 leading-relaxed">Photocopy, blueprints, stickers, DTF heat transfers, and corporate uniforms.</p>
                                </div>
                            </div>
                            <div class="pt-4 border-t border-gray-100 flex justify-between items-center">
                                <span class="text-[11px] text-gray-500">From <strong class="text-gray-900">₱1.50/page</strong></span>
                                <a href="client_request_service.php" class="text-xs font-bold text-[#1c482c] flex items-center gap-1 hover:gap-1.5 transition-all">Order <i class="fa-solid fa-arrow-right text-[9px]"></i></a>
                            </div>
                        </div>

                        <!-- Service 2 -->
                        <div class="bg-white p-6 rounded-3xl border border-gray-200/60 shadow-sm flex flex-col justify-between space-y-6 hover:shadow-md transition-all">
                            <div class="space-y-4">
                                <div class="flex justify-between items-center">
                                    <div class="w-10 h-10 rounded-2xl bg-orange-50 text-orange-700 flex items-center justify-center text-sm">
                                        <i class="fa-solid fa-building-columns"></i>
                                    </div>
                                    <span class="text-[10px] font-bold bg-orange-50 text-orange-800 px-2.5 py-1 rounded-full">Expedited</span>
                                </div>
                                <div>
                                    <h3 class="font-bold text-sm text-gray-900">Gov't Liaison & Permits</h3>
                                    <p class="text-[11px] text-gray-500 mt-1 leading-relaxed">BIR, TIN, PhilHealth, SSS, Pag-IBIG filing, DFA passport & NBI clearances.</p>
                                </div>
                            </div>
                            <div class="pt-4 border-t border-gray-100 flex justify-between items-center">
                                <span class="text-[11px] text-gray-500">Direct desk liaison</span>
                                <a href="client_request_service.php" class="text-xs font-bold text-[#1c482c] flex items-center gap-1 hover:gap-1.5 transition-all">Inquire <i class="fa-solid fa-arrow-right text-[9px]"></i></a>
                            </div>
                        </div>

                        <!-- Service 3 -->
                        <div class="bg-white p-6 rounded-3xl border border-gray-200/60 shadow-sm flex flex-col justify-between space-y-6 hover:shadow-md transition-all">
                            <div class="space-y-4">
                                <div class="flex justify-between items-center">
                                    <div class="w-10 h-10 rounded-2xl bg-purple-50 text-purple-700 flex items-center justify-center text-sm">
                                        <i class="fa-solid fa-scale-balanced"></i>
                                    </div>
                                    <span class="text-[10px] font-bold bg-purple-50 text-purple-800 px-2.5 py-1 rounded-full">CPA/Legal</span>
                                </div>
                                <div>
                                    <h3 class="font-bold text-sm text-gray-900">Corporate Advisory</h3>
                                    <p class="text-[11px] text-gray-500 mt-1 leading-relaxed">Notary public, contracts, monthly bookkeeping, and annual business renewals.</p>
                                </div>
                            </div>
                            <div class="pt-4 border-t border-gray-100 flex justify-between items-center">
                                <span class="text-[11px] text-gray-500">Certified specialists</span>
                                <a href="client_request_service.php" class="text-xs font-bold text-[#1c482c] flex items-center gap-1 hover:gap-1.5 transition-all">Consult <i class="fa-solid fa-arrow-right text-[9px]"></i></a>
                            </div>
                        </div>

                        <!-- Service 4 -->
                        <div class="bg-white p-6 rounded-3xl border border-gray-200/60 shadow-sm flex flex-col justify-between space-y-6 hover:shadow-md transition-all">
                            <div class="space-y-4">
                                <div class="flex justify-between items-center">
                                    <div class="w-10 h-10 rounded-2xl bg-amber-50 text-amber-700 flex items-center justify-center text-sm">
                                        <i class="fa-solid fa-pen-nib"></i>
                                    </div>
                                    <span class="text-[10px] font-bold bg-amber-50 text-amber-800 px-2.5 py-1 rounded-full">Creative</span>
                                </div>
                                <div>
                                    <h3 class="font-bold text-sm text-gray-900">Creative & Technical</h3>
                                    <p class="text-[11px] text-gray-500 mt-1 leading-relaxed">Brand identity, vector conversions, high-speed transcription & office supplies.</p>
                                </div>
                            </div>
                            <div class="pt-4 border-t border-gray-100 flex justify-between items-center">
                                <span class="text-[11px] text-gray-500">Turnkey delivery</span>
                                <a href="client_request_service.php" class="text-xs font-bold text-[#1c482c] flex items-center gap-1 hover:gap-1.5 transition-all">Explore <i class="fa-solid fa-arrow-right text-[9px]"></i></a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- LOWER GRID: ACTIVE ORDERS & WIDGETS -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start" id="location">
                    
                    <!-- LEFT COLUMN: ACTIVE DIRECTIVES & ORDERS TABLE (7 Cols) -->
                    <div class="lg:col-span-7 bg-white p-6 rounded-3xl border border-gray-200/60 shadow-sm space-y-6">
                        <div class="flex justify-between items-center">
                            <div>
                                <h2 class="font-bold text-base text-gray-900">Active Client Directives & Orders</h2>
                                <p class="text-xs text-gray-500 mt-0.5">Live status of submissions handled by Ohana Business Specialists</p>
                            </div>
                            <a href="#" class="text-xs font-semibold text-gray-600 hover:text-gray-900 border border-gray-200 px-3 py-1.5 rounded-xl bg-[#f9f7f4]">Export Summary</a>
                        </div>

                        <!-- Table Area -->
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs">
                                <thead>
                                    <tr class="text-gray-400 font-bold text-[10px] uppercase border-b border-gray-100">
                                        <th class="pb-3 font-semibold">Directive / Order</th>
                                        <th class="pb-3 font-semibold">Progress Stage</th>
                                        <th class="pb-3 font-semibold">Account Lead</th>
                                        <th class="pb-3 font-semibold">Target SLA</th>
                                        <th class="pb-3 font-semibold text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 text-gray-700">
                                    <?php if (empty($my_requests)): ?>
                                    <tr>
                                        <td colspan="5" class="py-8 text-center text-gray-500 italic">
                                            No service requests submitted yet. <a href="client_request_service.php" class="text-[#1c482c] font-bold hover:underline">Submit your first request</a> to get started.
                                        </td>
                                    </tr>
                                    <?php else: ?>
                                        <?php foreach ($my_requests as $req): ?>
                                        <tr class="hover:bg-gray-50/55">
                                            <td class="py-4 pr-3">
                                                <span class="font-bold block text-gray-900"><?= htmlspecialchars($req['service_name']) ?></span>
                                                <span class="text-[10px] text-gray-400 block mt-0.5"><?= htmlspecialchars($req['reference_no']) ?></span>
                                            </td>
                                            <td class="py-4 pr-3">
                                                <span class="inline-block font-semibold capitalize mb-1
                                                    <?= $req['status'] === 'completed' ? 'text-emerald-700' : ($req['status'] === 'pending' ? 'text-purple-600' : 'text-amber-600') ?>
                                                ">
                                                    <?= htmlspecialchars(str_replace('_', ' ', $req['status'])) ?>
                                                </span>
                                            </td>
                                            <td class="py-4 pr-3">
                                                <div class="flex items-center gap-2">
                                                    <span><?= htmlspecialchars($req['assigned_specialist'] ?? 'Unassigned') ?></span>
                                                </div>
                                            </td>
                                            <td class="py-4 pr-3 font-medium text-gray-900">
                                                <?= date('M d, Y', strtotime($req['requested_at'])) ?>
                                            </td>
                                            <td class="py-4 text-right">
                                                <div class="flex items-center justify-end gap-2 text-gray-400">
                                                    <a href="client_orders_documents.php" class="hover:text-gray-700"><i class="fa-solid fa-file-lines text-xs"></i></a>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- RIGHT COLUMN: PHYSICAL HUB & UPCOMING WINDOW (5 Cols) -->
                    <div class="lg:col-span-5 space-y-6">
                        
                        <!-- Physical Hub Card -->
                        <div class="bg-white p-6 rounded-3xl border border-gray-200/60 shadow-sm space-y-4">
                            <div class="flex justify-between items-center">
                                <span class="font-bold text-sm text-gray-900">PHYSICAL HUB & CONCIERGE</span>
                                <span class="text-[10px] bg-emerald-100 text-emerald-800 px-2.5 py-1 rounded-full font-bold flex items-center gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-600 animate-pulse"></span> Open Now
                                </span>
                            </div>

                            <div class="space-y-3 text-xs text-gray-600">
                                <div class="flex items-start gap-2.5">
                                    <i class="fa-solid fa-location-dot text-[#1c482c] mt-0.5"></i>
                                    <div>
                                        <strong class="text-gray-900 block">Malolos Headquarters</strong>
                                        <span class="text-gray-500">GF Topico Bldg., MacArthur Hwy, Malolos City</span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2.5">
                                    <i class="fa-solid fa-phone text-[#1c482c]"></i>
                                    <span>0997 855 1913 / (044) 791-4402</span>
                                </div>
                                <div class="flex items-center gap-2.5">
                                    <i class="fa-regular fa-clock text-[#1c482c]"></i>
                                    <span>Mon - Sat: 8:00 AM to 6:30 PM</span>
                                </div>
                            </div>

                            <!-- Map Box Preview -->
                            <div class="w-full h-36 bg-stone-200 rounded-2xl overflow-hidden relative border border-gray-200 flex items-center justify-center">
                                <div class="absolute inset-0 opacity-40 bg-[radial-gradient(#cbd5e1_1px,transparent_1px)] [background-size:16px_16px]"></div>
                                <div class="bg-white/90 backdrop-blur-xs px-3 py-1.5 rounded-xl shadow-xs border border-gray-200 text-center relative z-10">
                                    <span class="font-bold text-[11px] text-gray-900 block">Topico Bldg. Liaison Center</span>
                                    <a href="#" class="text-[10px] text-[#1c482c] font-bold underline">Directions</a>
                                </div>
                            </div>
                        </div>

                        <!-- Upcoming Window Card -->
                        <div class="bg-white p-6 rounded-3xl border border-gray-200/60 shadow-sm space-y-4">
                            <div class="flex items-center gap-2 text-xs font-bold text-amber-700 uppercase">
                                <i class="fa-solid fa-calendar-days"></i> UPCOMING WINDOW
                            </div>

                            <div>
                                <h3 class="font-bold text-base text-gray-900">Mayor's Permit Renewal</h3>
                                <p class="text-xs text-gray-500 mt-1 leading-relaxed">
                                    Annual Malolos LGU business permit renewals commence Jan 2nd. Schedule an early liaison slot.
                                </p>
                            </div>

                            <a href="#" class="block w-full py-3 bg-[#f9f7f4] hover:bg-gray-100 text-center text-xs font-semibold text-gray-800 rounded-xl border border-gray-200/80 transition-colors">
                                Schedule Consultation
                            </a>
                        </div>

                        <!-- Urgent Courier Notice -->
                        <div class="bg-[#1c482c] p-5 rounded-3xl text-white flex items-center justify-between shadow-md">
                            <div class="space-y-1">
                                <span class="font-bold text-xs block">Need urgent courier dispatch?</span>
                                <span class="text-[11px] text-emerald-100/80 block">Same-day document delivery around Bulacan</span>
                            </div>
                            <a href="tel:09978551913" class="bg-white text-[#1c482c] font-bold text-xs px-4 py-2.5 rounded-xl shadow-xs hover:bg-emerald-50 transition-colors shrink-0">
                                Call Hub
                            </a>
                        </div>

                    </div>
                </div>

                <!-- CLIENT COMPLIANCE DIGITAL VAULT FOOTER BAR -->
                <div class="bg-white p-4 rounded-2xl border border-gray-200/60 shadow-sm flex flex-col sm:flex-row justify-between items-center gap-4">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-xl bg-gray-100 text-gray-600 flex items-center justify-center text-xs">
                            <i class="fa-solid fa-lock"></i>
                        </div>
                        <div>
                            <span class="font-bold text-xs text-gray-900 block">Client Compliance Digital Vault</span>
                            <span class="text-[11px] text-gray-500 block">24 notarized copies and receipts archived securely</span>
                        </div>
                    </div>
                    <a href="#" class="bg-[#f9f7f4] hover:bg-gray-100 text-gray-700 text-xs font-semibold px-4 py-2 rounded-xl border border-gray-200 transition-colors">
                        Open Vault
                    </a>
                </div>

            </main>
        </div>
    </div>

</body>
</html>