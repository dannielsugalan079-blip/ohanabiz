<?php
require_once __DIR__ . '/config/auth.php';
requireLogin('client');
$user = currentUser();

// Fetch client profile
$client_stmt = $pdo->prepare("SELECT * FROM clients WHERE user_id = ?");
$client_stmt->execute([$user['id']]);
$client_profile = $client_stmt->fetch(PDO::FETCH_ASSOC);
$company_name = !empty($client_profile['company_name']) ? $client_profile['company_name'] : (!empty($user['full_name']) ? $user['full_name'] : 'Valued Client');

// User initials
$initials = '';
$name_parts = explode(' ', trim($user['full_name']));
foreach (array_slice($name_parts, 0, 2) as $p) {
    if (!empty($p)) $initials .= strtoupper($p[0]);
}
if (empty($initials)) $initials = 'CL';

// Fetch client's service requests
$requests_stmt = $pdo->prepare("
    SELECT sr.*, s.category, u.full_name as assigned_specialist
    FROM service_requests sr
    LEFT JOIN services s ON sr.service_id = s.id
    LEFT JOIN users u ON sr.assigned_to = u.id
    WHERE sr.client_id = ?
    ORDER BY sr.requested_at DESC
");
$requests_stmt->execute([$user['id']]);
$client_requests = $requests_stmt->fetchAll(PDO::FETCH_ASSOC);

// Counts
$total_orders = count($client_requests);
$active_orders = 0;
$completed_orders = 0;
foreach ($client_requests as $cr) {
    if (in_array($cr['status'], ['pending', 'reviewing', 'in_progress'])) {
        $active_orders++;
    } elseif ($cr['status'] === 'completed') {
        $completed_orders++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ohana Portal - Orders & Documents</title>
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
                <a href="client_dashboard.php" class="p-4 border-b border-gray-100 flex items-center gap-2.5 group">
                    <div class="w-8 h-8 rounded-lg bg-white p-0.5 border border-gray-200 shadow-xs flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                        <img src="assets/images/ohana.png" alt="OHANA Logo" class="w-full h-full object-contain">
                    </div>
                    <div>
                        <span class="font-bold text-xs text-gray-900 tracking-tight block">Ohana Portal</span>
                        <span class="text-[9px] uppercase tracking-wider text-gray-400 font-semibold block">Client Workspace</span>
                    </div>
                </a>

                <div class="p-3 mx-3 my-2.5 bg-[#f9f7f4] rounded-xl border border-gray-200/60">
                    <span class="text-[9px] uppercase font-bold text-gray-400 tracking-wider block">CLIENT WORKSPACE</span>
                    <span class="text-xs font-extrabold text-gray-900 block truncate"><?= htmlspecialchars($company_name) ?></span>
                </div>

                <nav class="px-3 space-y-0.5 text-xs font-medium">
                    <a href="client_dashboard.php" class="flex items-center gap-3 px-3 py-2 rounded-xl text-gray-600 hover:bg-gray-100/70 transition-colors">
                        <i class="fa-solid fa-house text-xs"></i> My Dashboard
                    </a>
                    <a href="client_request_service.php" class="flex items-center gap-3 px-3 py-2 rounded-xl text-gray-600 hover:bg-gray-100/70 transition-colors">
                        <i class="fa-solid fa-file-lines text-xs"></i> Inquiries & Requests
                    </a>
                    <a href="client_orders_documents.php" class="flex items-center gap-3 px-3 py-2 rounded-xl bg-[#1c482c] text-white font-semibold shadow-sm">
                        <i class="fa-solid fa-folder-open text-xs"></i> Orders & Documents
                    </a>
                    <a href="client_profile_billing.php" class="flex items-center gap-3 px-3 py-2 rounded-xl text-gray-600 hover:bg-gray-100/70 transition-colors">
                        <i class="fa-solid fa-coins text-xs"></i> Pricing & Billing
                    </a>
                </nav>
            </div>

            <div class="p-3 border-t border-gray-100 space-y-2">
                <a href="logout.php" class="flex items-center gap-2 text-xs text-red-600 font-semibold px-3 py-1.5 hover:bg-red-50 rounded-lg transition-colors">
                    <i class="fa-solid fa-right-from-bracket"></i> Log Out
                </a>
                <span class="text-[10px] text-gray-400 px-3 block">© Core Protocol Synchronized</span>
            </div>
        </aside>

        <!-- MAIN CONTENT WRAPPER -->
        <div class="flex-1 flex flex-col min-w-0">
            
            <!-- TOP HEADER -->
            <header class="bg-white border-b border-gray-200/60 h-14 px-6 flex items-center justify-between sticky top-0 z-30">
                <div class="flex items-center gap-4 flex-1 max-w-md">
                    <div class="relative w-full">
                        <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                        <input type="text" placeholder="Search orders, documents, services..." class="w-full bg-[#f9f7f4] border border-gray-200 rounded-xl pl-9 pr-4 py-1.5 text-xs focus:outline-none focus:border-[#1c482c] transition-all">
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <div class="flex items-center gap-2.5">
                        <div class="w-7 h-7 rounded-full bg-emerald-800 text-white font-bold flex items-center justify-center text-xs">
                            <?= htmlspecialchars($initials) ?>
                        </div>
                        <div class="text-left hidden sm:block">
                            <span class="block text-xs font-bold text-gray-900 leading-tight"><?= htmlspecialchars($user['full_name']) ?></span>
                            <span class="block text-[10px] text-gray-400"><?= htmlspecialchars($company_name) ?></span>
                        </div>
                    </div>
                </div>
            </header>

            <!-- MAIN CONTAINER -->
            <main class="p-4 md:p-6 space-y-4 max-w-7xl w-full mx-auto">

                <!-- BREADCRUMB & TITLE -->
                <div class="space-y-1">
                    <span class="text-[11px] text-gray-500 font-medium">Client Workspace > <?= htmlspecialchars($company_name) ?> > Orders & Documents</span>
                    <h1 class="text-2xl md:text-3xl font-extrabold text-gray-900 tracking-tight">
                        Orders & Documents Vault
                    </h1>
                    <p class="text-xs text-gray-600">
                        Access verified regulatory records, approved municipal permits, architectural blue prints, and digital compliance certificates archived from Malolos Hub operations.
                    </p>
                </div>

                <!-- METRICS / STATS SUMMARY -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div class="bg-white p-4 rounded-2xl border border-gray-200/60 shadow-sm space-y-1">
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Total Orders</span>
                        <div class="flex items-baseline justify-between">
                            <span class="text-xl font-extrabold text-gray-900"><?= $total_orders ?></span>
                            <span class="text-[10px] bg-emerald-100 text-emerald-800 font-bold px-2 py-0.5 rounded-full">All Directives</span>
                        </div>
                    </div>
                    <div class="bg-white p-4 rounded-2xl border border-gray-200/60 shadow-sm space-y-1">
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Active Directives</span>
                        <div class="flex items-baseline justify-between">
                            <span class="text-xl font-extrabold text-amber-700"><?= $active_orders ?></span>
                            <span class="text-[10px] bg-amber-100 text-amber-800 font-bold px-2 py-0.5 rounded-full">In Progress</span>
                        </div>
                    </div>
                    <div class="bg-white p-4 rounded-2xl border border-gray-200/60 shadow-sm space-y-1">
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Completed / Archived</span>
                        <div class="flex items-baseline justify-between">
                            <span class="text-xl font-extrabold text-emerald-800"><?= $completed_orders ?></span>
                            <span class="text-[10px] bg-emerald-100 text-emerald-800 font-bold px-2 py-0.5 rounded-full">Secured Vault</span>
                        </div>
                    </div>
                </div>

                <!-- ORDERS & DOCUMENTS TABLE SECTION -->
                <div class="bg-white p-5 rounded-2xl border border-gray-200/60 shadow-sm space-y-4">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-3">
                        <div>
                            <h2 class="font-extrabold text-sm text-gray-900">Recent Service Records & Issued Files</h2>
                            <p class="text-[11px] text-gray-500">Showing active municipal submissions, clearance certificates, and digital scan archives.</p>
                        </div>
                        <div class="flex items-center gap-2 w-full md:w-auto">
                            <a href="client_request_service.php" class="bg-[#1c482c] hover:bg-[#153721] text-white font-semibold py-1.5 px-3 rounded-xl text-xs flex items-center gap-1.5 shadow-sm transition-all">
                                <i class="fa-solid fa-plus text-[10px]"></i> New Request
                            </a>
                        </div>
                    </div>

                    <!-- TABLE -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-[#f9f7f4] text-gray-400 uppercase tracking-wider text-[10px] border-b border-gray-200/60">
                                <tr>
                                    <th class="py-3 px-4 font-bold">Tracking Ref / Service Name</th>
                                    <th class="py-3 px-4 font-bold">Priority</th>
                                    <th class="py-3 px-4 font-bold">Assigned Specialist</th>
                                    <th class="py-3 px-4 font-bold">Total Amount</th>
                                    <th class="py-3 px-4 font-bold">Status</th>
                                    <th class="py-3 px-4 font-bold">Date Filed</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 font-medium text-gray-700">
                                <?php if (empty($client_requests)): ?>
                                    <tr>
                                        <td colspan="6" class="py-8 text-center text-gray-400 italic">
                                            No service requests found. <a href="client_request_service.php" class="text-[#1c482c] font-bold underline ml-1">Submit a request to get started</a>.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($client_requests as $req): 
                                        $badge = 'bg-gray-100 text-gray-700';
                                        if ($req['status'] === 'completed') $badge = 'bg-emerald-100 text-emerald-800';
                                        elseif ($req['status'] === 'in_progress') $badge = 'bg-amber-100 text-amber-800';
                                        elseif ($req['status'] === 'reviewing') $badge = 'bg-blue-100 text-blue-800';
                                        elseif ($req['status'] === 'pending') $badge = 'bg-purple-100 text-purple-800';
                                    ?>
                                    <tr class="hover:bg-gray-50/50 transition-colors">
                                        <td class="py-3.5 px-4">
                                            <strong class="block text-gray-900 text-xs"><?= htmlspecialchars($req['reference_no']) ?></strong>
                                            <span class="text-[11px] text-gray-500 font-normal"><?= htmlspecialchars($req['service_name']) ?></span>
                                        </td>
                                        <td class="py-3.5 px-4">
                                            <span class="uppercase text-[10px] font-bold px-2 py-0.5 rounded-full <?= $req['priority'] === 'rush' ? 'bg-red-100 text-red-800' : ($req['priority'] === 'urgent' ? 'bg-amber-100 text-amber-800' : 'bg-gray-100 text-gray-700') ?>">
                                                <?= htmlspecialchars($req['priority']) ?>
                                            </span>
                                        </td>
                                        <td class="py-3.5 px-4">
                                            <span class="block text-gray-900"><?= htmlspecialchars($req['assigned_specialist'] ?? 'Malolos Hub Desk') ?></span>
                                            <span class="text-[10px] text-gray-400">Operations</span>
                                        </td>
                                        <td class="py-3.5 px-4 text-gray-900 font-bold">
                                            ₱<?= number_format((float)($req['total_amount'] ?? 0), 2) ?>
                                        </td>
                                        <td class="py-3.5 px-4">
                                            <span class="<?= $badge ?> text-[10px] font-bold px-2.5 py-1 rounded-full capitalize">
                                                <?= str_replace('_', ' ', $req['status']) ?>
                                            </span>
                                        </td>
                                        <td class="py-3.5 px-4 text-gray-500">
                                            <?= date('M d, Y', strtotime($req['requested_at'])) ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </main>
        </div>
    </div>

</body>
</html>