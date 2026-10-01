<?php
require_once __DIR__ . '/config/auth.php';
requireLogin('client');
$user = currentUser();

// Fetch client profile
$client_stmt = $pdo->prepare("SELECT * FROM clients WHERE user_id = ?");
$client_stmt->execute([$user['id']]);
$client_profile = $client_stmt->fetch(PDO::FETCH_ASSOC);
$company_name = !empty($client_profile['company_name']) ? $client_profile['company_name'] : (!empty($user['full_name']) ? $user['full_name'] : 'Valued Client');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ohana Portal - Profile & Billing</title>
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
                    <a href="client_orders_documents.php" class="flex items-center gap-3 px-3 py-2 rounded-xl text-gray-600 hover:bg-gray-100/70 transition-colors">
                        <i class="fa-solid fa-folder-open text-xs"></i> Orders & Documents
                    </a>
                    <a href="client_profile_billing.php" class="flex items-center gap-3 px-3 py-2 rounded-xl bg-[#1c482c] text-white font-semibold shadow-sm">
                        <i class="fa-solid fa-receipt text-xs"></i> Profile & Billing
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
                        <input type="text" placeholder="Search profile settings, invoices, billing..." class="w-full bg-[#f9f7f4] border border-gray-200 rounded-xl pl-9 pr-4 py-1.5 text-xs focus:outline-none focus:border-[#1c482c] transition-all">
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
                    <span class="text-[11px] text-gray-500 font-medium">Client Workspace > <?= htmlspecialchars($company_name) ?> > Profile & Billing</span>
                    <h1 class="text-2xl md:text-3xl font-extrabold text-gray-900 tracking-tight">
                        Client Profile & Billing Management
                    </h1>
                    <p class="text-xs text-gray-600">
                        Manage corporate profile information, authorized representative credentials, secure payment methods, and historical billing statements through your Malolos Hub account.
                    </p>
                </div>

                <!-- TWO COLUMN GRID FOR PROFILE & BILLING DETAILS -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-start">
                    
                    <!-- LEFT COLUMN: CORPORATE & REPRESENTATIVE PROFILE -->
                    <div class="lg:col-span-7 space-y-4">
                        
                        <!-- CORPORATE PROFILE CARD -->
                        <div class="bg-white p-5 rounded-2xl border border-gray-200/60 shadow-sm space-y-4">
                            <div class="flex justify-between items-center pb-3 border-b border-gray-100">
                                <div class="flex items-center gap-2">
                                    <div class="w-7 h-7 rounded-lg bg-[#1c482c] text-white flex items-center justify-center text-xs">
                                        <i class="fa-solid fa-building"></i>
                                    </div>
                                    <h2 class="font-extrabold text-sm text-gray-900">Corporate Entity Profile</h2>
                                </div>
                                <span class="bg-emerald-100 text-emerald-800 text-[10px] font-bold px-2.5 py-0.5 rounded-full">Verified Active</span>
                            </div>

                            <form action="#" method="POST" class="space-y-3 text-xs">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <div class="space-y-1">
                                        <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider">Registered Business Name</label>
                                        <input type="text" value="<?= htmlspecialchars($company_name) ?>" class="w-full bg-[#f9f7f4] border border-gray-200 rounded-xl px-3 py-2 font-medium text-gray-800 focus:outline-none focus:border-[#1c482c]">
                                    </div>
                                    <div class="space-y-1">
                                        <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider">Industry</label>
                                        <input type="text" value="<?= htmlspecialchars($client_profile['industry'] ?? '') ?>" class="w-full bg-[#f9f7f4] border border-gray-200 rounded-xl px-3 py-2 font-medium text-gray-800 focus:outline-none focus:border-[#1c482c]">
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <div class="space-y-1">
                                        <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider">Authorized Representative</label>
                                        <input type="text" value="<?= htmlspecialchars($user['full_name']) ?>" class="w-full bg-[#f9f7f4] border border-gray-200 rounded-xl px-3 py-2 font-medium text-gray-800 focus:outline-none focus:border-[#1c482c]">
                                    </div>
                                    <div class="space-y-1">
                                        <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider">Official Corporate Email</label>
                                        <input type="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" class="w-full bg-[#f9f7f4] border border-gray-200 rounded-xl px-3 py-2 font-medium text-gray-800 focus:outline-none focus:border-[#1c482c]">
                                    </div>
                                </div>

                                <div class="space-y-1">
                                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider">Operating Address</label>
                                    <input type="text" value="<?= htmlspecialchars($client_profile['address'] ?? '') ?>" class="w-full bg-[#f9f7f4] border border-gray-200 rounded-xl px-3 py-2 font-medium text-gray-800 focus:outline-none focus:border-[#1c482c]">
                                </div>

                                <div class="pt-2 flex justify-end">
                                    <button type="submit" class="bg-[#1c482c] hover:bg-[#153721] text-white font-semibold py-2 px-4 rounded-xl text-xs transition-all shadow-sm">
                                        Save Profile Changes
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- BILLING HISTORY & INVOICES TABLE -->
                        <div class="bg-white p-5 rounded-2xl border border-gray-200/60 shadow-sm space-y-4">
                            <div class="flex justify-between items-center pb-3 border-b border-gray-100">
                                <div class="flex items-center gap-2">
                                    <div class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-800 flex items-center justify-center text-xs">
                                        <i class="fa-solid fa-file-invoice-dollar"></i>
                                    </div>
                                    <h2 class="font-extrabold text-sm text-gray-900">Billing History & Invoices</h2>
                                </div>
                                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Statement Ledger</span>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="w-full text-left text-xs">
                                    <thead class="bg-[#f9f7f4] text-gray-400 uppercase tracking-wider text-[10px] border-b border-gray-200/60">
                                        <tr>
                                            <th class="py-3 px-3 font-bold">Invoice Ref</th>
                                            <th class="py-3 px-3 font-bold">Particulars</th>
                                            <th class="py-3 px-3 font-bold">Amount</th>
                                            <th class="py-3 px-3 font-bold">Status</th>
                                            <th class="py-3 px-3 font-bold text-right">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 font-medium text-gray-700">
                                        <tr>
                                            <td colspan="5" class="py-8 text-center text-gray-500 italic">
                                                Invoice history will appear here once services are rendered.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div>

                    <!-- RIGHT COLUMN: PAYMENT METHODS & HUB SUPPORT -->
                    <div class="lg:col-span-5 space-y-4">
                        
                        <!-- PAYMENT METHODS CARD -->
                        <div class="bg-white p-5 rounded-2xl border border-gray-200/60 shadow-sm space-y-4">
                            <div class="flex justify-between items-center pb-3 border-b border-gray-100">
                                <div class="flex items-center gap-2">
                                    <div class="w-7 h-7 rounded-lg bg-amber-50 text-amber-800 flex items-center justify-center text-xs">
                                        <i class="fa-solid fa-credit-card"></i>
                                    </div>
                                    <h2 class="font-extrabold text-sm text-gray-900">Registered Payment Methods</h2>
                                </div>
                                <button class="text-[11px] font-bold text-[#1c482c] hover:underline">+ Add Method</button>
                            </div>

                            <div class="space-y-2 text-xs">
                                <div class="bg-[#f9f7f4] border border-gray-200 p-3 rounded-xl flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-blue-100 text-blue-800 flex items-center justify-center text-xs font-bold">
                                            <i class="fa-solid fa-wallet"></i>
                                        </div>
                                        <div>
                                            <strong class="block text-gray-900 text-xs">GCash Corporate (*913)</strong>
                                            <span class="text-[10px] text-gray-500">Default Auto-Debit Channel</span>
                                        </div>
                                    </div>
                                    <span class="bg-emerald-100 text-emerald-800 text-[9px] font-bold px-2 py-0.5 rounded-full">Active</span>
                                </div>

                                <div class="bg-[#f9f7f4] border border-gray-200/60 p-3 rounded-xl flex items-center justify-between opacity-75">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-gray-200 text-gray-700 flex items-center justify-center text-xs font-bold">
                                            <i class="fa-solid fa-building-columns"></i>
                                        </div>
                                        <div>
                                            <strong class="block text-gray-900 text-xs">BPI Corporate Account</strong>
                                            <span class="text-[10px] text-gray-500">Ending in •••• 4402</span>
                                        </div>
                                    </div>
                                    <button class="text-gray-500 text-[10px] font-semibold hover:underline">Set Primary</button>
                                </div>
                            </div>
                        </div>

                        <!-- MALOLOS HUB SUPPORT CARD -->
                        <div class="bg-white p-5 rounded-2xl border border-gray-200/60 shadow-sm space-y-3">
                            <div class="flex items-center gap-2">
                                <div class="w-6 h-6 rounded-lg bg-emerald-50 text-emerald-800 flex items-center justify-center text-xs">
                                    <i class="fa-solid fa-headset"></i>
                                </div>
                                <span class="text-[10px] font-bold text-gray-900 uppercase tracking-wider">Billing & Account Assistance</span>
                            </div>

                            <p class="text-[11px] text-gray-600 leading-relaxed">
                                For billing adjustments, official withholding tax certificates (BIR Form 2307), or custom municipal receipt routing, reach out to our Malolos desk:
                            </p>

                            <div class="space-y-1 text-xs font-medium text-gray-800">
                                <div class="flex items-center gap-2">
                                    <i class="fa-solid fa-phone text-gray-400 text-xs"></i> 0997 855 1913 / (044) 791-4402
                                </div>
                                <div class="flex items-center gap-2">
                                    <i class="fa-regular fa-envelope text-gray-400 text-xs"></i> billing@ohanaconsultancy.ph
                                </div>
                            </div>

                            <div class="pt-2 border-t border-gray-100 flex items-center justify-between text-[10px] text-gray-500">
                                <span>Malolos Hub Finance Officer</span>
                                <span class="font-bold text-emerald-700">VERIFIED DESK</span>
                            </div>
                        </div>

                    </div>

                </div>

                <!-- FOOTER -->
                <div class="bg-white px-5 py-3 rounded-2xl border border-gray-200/60 shadow-sm flex justify-between items-center text-[11px] text-gray-500">
                    <span>© 2025 OHANA Business Consultancy Inc. — Secure Profile & Billing Ledger</span>
                    <span class="font-bold text-emerald-800">MALOLOS, BULACAN HUB</span>
                </div>

            </main>
        </div>
    </div>

</body>
</html>