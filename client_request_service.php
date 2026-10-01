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

// Fetch active services
$services_stmt = $pdo->query("SELECT id, category, name, description, base_price, estimated_days FROM services WHERE is_active = 1 ORDER BY category, name");
$all_services = $services_stmt->fetchAll(PDO::FETCH_ASSOC);

$success_msg = '';
$error_msg = '';
$desk = fetchPrimarySupervisor($pdo);
$new_ref_no = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $service_id       = (int)($_POST['service_id'] ?? 0);
    $directive_title  = trim($_POST['directive_title'] ?? '');
    $target_date      = !empty($_POST['target_date']) ? $_POST['target_date'] : null;
    $instructions     = trim($_POST['instructions'] ?? '');
    $priority         = in_array($_POST['priority'] ?? '', ['normal', 'urgent', 'rush']) ? $_POST['priority'] : 'normal';
    $delivery_pref    = trim($_POST['delivery_pref'] ?? 'Digital + Courier');
    $special_delivery = trim($_POST['special_delivery'] ?? '');

    if ($service_id <= 0) {
        $error_msg = 'Please select a service from the list.';
    } elseif (empty($directive_title) && empty($instructions)) {
        $error_msg = 'Please provide a title or details for your request.';
    } else {
        // Find selected service
        $selected_service = null;
        foreach ($all_services as $s) {
            if ((int)$s['id'] === $service_id) {
                $selected_service = $s;
                break;
            }
        }

        if (!$selected_service) {
            $error_msg = 'Invalid service selected.';
        } else {
            // Handle file upload
            $uploaded_filename = null;
            if (isset($_FILES['document']) && $_FILES['document']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = __DIR__ . '/uploads/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                $orig_filename = basename($_FILES['document']['name']);
                $ext = strtolower(pathinfo($orig_filename, PATHINFO_EXTENSION));
                $allowed = ['pdf', 'png', 'jpg', 'jpeg', 'docx', 'doc', 'zip'];

                if (in_array($ext, $allowed)) {
                    $clean_name = 'DOC_' . date('Ymd_His') . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
                    if (move_uploaded_file($_FILES['document']['tmp_name'], $upload_dir . $clean_name)) {
                        $uploaded_filename = $clean_name . ' (' . $orig_filename . ')';
                    }
                }
            }

            // Calculation
            $base_price = (float)$selected_service['base_price'];
            $surcharge = 0;
            if ($priority === 'urgent') {
                $surcharge = 500;
            } elseif ($priority === 'rush') {
                $surcharge = 1000;
            }
            $delivery_fee = (stripos($delivery_pref, 'Courier') !== false) ? 150 : 0;
            $total_amount = $base_price + $surcharge + $delivery_fee;

            // Generate Reference Number
            $new_ref_no = generateServiceReference($pdo);

            // Compose Details field
            $details_text = "Title: " . ($directive_title ?: $selected_service['name']) . "\n";
            if ($target_date) {
                $details_text .= "Target Date: " . $target_date . "\n";
            }
            if ($instructions) {
                $details_text .= "Instructions: " . $instructions . "\n";
            }
            $details_text .= "Handover Mode: " . $delivery_pref . "\n";
            if ($special_delivery) {
                $details_text .= "Special Notes: " . $special_delivery . "\n";
            }
            if ($uploaded_filename) {
                $details_text .= "Attached Document: " . $uploaded_filename . "\n";
            }

            try {
                $ins_stmt = $pdo->prepare("
                    INSERT INTO service_requests 
                    (reference_no, client_id, service_id, service_name, details, status, priority, total_amount, payment_status, requested_at)
                    VALUES (?, ?, ?, ?, ?, 'pending', ?, ?, 'unpaid', NOW())
                ");
                $ins_stmt->execute([
                    $new_ref_no,
                    $user['id'],
                    $selected_service['id'],
                    $selected_service['name'],
                    $details_text,
                    $priority,
                    $total_amount
                ]);

                if ($desk) {
                    createNotification(
                        $pdo,
                        (int) $desk['id'],
                        'New client service request',
                        $new_ref_no . ' — ' . $selected_service['name'],
                        'request',
                        'supervisor_dispatch.php'
                    );
                }

                $success_msg = "Your request has been submitted successfully! Reference Code: <strong>{$new_ref_no}</strong>";
            } catch (PDOException $e) {
                error_log('[OHANA SERVICE REQUEST] ' . $e->getMessage());
                $error_msg = 'A database error occurred while saving your request. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ohana Portal - Submit New Service Request</title>
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
                    <a href="client_request_service.php" class="flex items-center gap-3 px-3 py-2 rounded-xl bg-[#1c482c] text-white font-semibold shadow-sm">
                        <i class="fa-solid fa-file-lines text-xs"></i> Inquiries & Requests
                    </a>
                    <a href="client_orders_documents.php" class="flex items-center gap-3 px-3 py-2 rounded-xl text-gray-600 hover:bg-gray-100/70 transition-colors">
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
                    <span class="text-[11px] text-gray-500 font-medium">Client Workspace > <?= htmlspecialchars($company_name) ?> > Service Directive Filing</span>
                    <h1 class="text-2xl md:text-3xl font-extrabold text-gray-900 tracking-tight">
                        Submit New Service Request
                    </h1>
                    <p class="text-xs text-gray-600">
                        Request official regulatory licensing, statutory compliance, architectural printing, or specialized enterprise labor directly through your dedicated Malolos Hub personnel.
                    </p>
                </div>

                <!-- NOTIFICATIONS BANNER -->
                <?php if (!empty($success_msg)): ?>
                <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-2xl flex items-center justify-between text-xs text-emerald-900 shadow-xs">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-700 font-bold shrink-0">
                            <i class="fa-solid fa-circle-check text-sm"></i>
                        </div>
                        <div>
                            <span class="font-bold text-sm block">Success!</span>
                            <span><?= $success_msg ?></span>
                        </div>
                    </div>
                    <a href="client_orders_documents.php" class="bg-emerald-700 hover:bg-emerald-800 text-white font-bold px-3 py-1.5 rounded-xl transition text-xs shrink-0">
                        View Orders & Documents
                    </a>
                </div>
                <?php elseif (!empty($error_msg)): ?>
                <div class="p-4 bg-red-50 border border-red-200 rounded-2xl flex items-center gap-3 text-xs text-red-900 shadow-xs">
                    <div class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center text-red-700 font-bold shrink-0">
                        <i class="fa-solid fa-triangle-exclamation text-sm"></i>
                    </div>
                    <div>
                        <span class="font-bold text-sm block">Notice</span>
                        <span><?= htmlspecialchars($error_msg) ?></span>
                    </div>
                </div>
                <?php endif; ?>

                <!-- REAL SUBMISSION FORM -->
                <form method="POST" action="client_request_service.php" enctype="multipart/form-data" id="serviceRequestForm">

                    <!-- GRID LAYOUT -->
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-start">
                        
                        <!-- LEFT COLUMN: STEPS & FORMS -->
                        <div class="lg:col-span-8 space-y-4">
                            
                            <!-- STEP 01: SERVICE CLASSIFICATION -->
                            <div class="bg-white p-5 rounded-2xl border border-gray-200/60 shadow-sm space-y-4">
                                <div class="flex justify-between items-center">
                                    <div class="flex items-center gap-2">
                                        <span class="w-6 h-6 rounded-lg bg-[#1c482c] text-white text-xs font-bold flex items-center justify-center">01</span>
                                        <h2 class="font-extrabold text-sm text-gray-900">Service Selection & Urgency</h2>
                                    </div>
                                    <span class="text-[10px] font-bold text-amber-600 uppercase tracking-wider bg-amber-50 px-2 py-0.5 rounded">REQUIRED FIELD</span>
                                </div>

                                <div class="space-y-2">
                                    <label class="text-[10px] font-bold text-gray-500 uppercase tracking-wider block">Select Service</label>
                                    <div class="relative">
                                        <select name="service_id" id="serviceSelect" required class="w-full bg-[#f9f7f4] border border-gray-300 rounded-xl px-4 py-2.5 text-xs text-gray-900 font-semibold focus:outline-none focus:border-[#1c482c] focus:ring-1 focus:ring-[#1c482c]">
                                            <option value="">-- Select a Service --</option>
                                            <?php
                                            $cat_labels = [
                                                'primary_documentation' => "Gov't Liaison & Documentation",
                                                'business_corporate' => "Business & Corporate Advisory",
                                                'legal_notarial' => "Legal & Notarial Services",
                                                'printing_online' => "Printing, Scanning & Production"
                                            ];
                                            $grouped = [];
                                            foreach ($all_services as $s) {
                                                $grouped[$s['category']][] = $s;
                                            }
                                            foreach ($grouped as $cat_key => $svc_list):
                                                $cat_title = $cat_labels[$cat_key] ?? ucfirst(str_replace('_', ' ', $cat_key));
                                            ?>
                                                <optgroup label="<?= htmlspecialchars($cat_title) ?>">
                                                    <?php foreach ($svc_list as $svc): ?>
                                                        <option value="<?= $svc['id'] ?>" 
                                                                data-name="<?= htmlspecialchars($svc['name']) ?>"
                                                                data-category="<?= htmlspecialchars($cat_title) ?>"
                                                                data-price="<?= (float)$svc['base_price'] ?>"
                                                                data-days="<?= (int)$svc['estimated_days'] ?>">
                                                            <?= htmlspecialchars($svc['name']) ?> — ₱<?= number_format($svc['base_price'], 2) ?> (Est. <?= $svc['estimated_days'] ?> days)
                                                        </option>
                                                    <?php endforeach; ?>
                                                </optgroup>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <!-- URGENCY & PROCESSING PRIORITY -->
                                <div class="space-y-1.5 pt-2">
                                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Urgency & Processing Priority</label>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-2 text-xs">
                                        <label class="priority-label border-2 border-[#1c482c] bg-emerald-50/30 p-3 rounded-xl flex items-center justify-between cursor-pointer transition">
                                            <div>
                                                <strong class="block text-gray-900 text-[11px]">Standard</strong>
                                                <span class="text-[10px] text-gray-500">3-5 Business Days (₱0)</span>
                                            </div>
                                            <input type="radio" name="priority" value="normal" checked class="text-[#1c482c] focus:ring-[#1c482c] priority-radio">
                                        </label>
                                        <label class="priority-label border border-gray-200 bg-[#f9f7f4] p-3 rounded-xl flex items-center justify-between cursor-pointer transition">
                                            <div>
                                                <strong class="block text-gray-900 text-[11px]">Expedited (+₱500)</strong>
                                                <span class="text-[10px] text-gray-500">24-48 Hours Turnaround</span>
                                            </div>
                                            <input type="radio" name="priority" value="urgent" class="text-[#1c482c] focus:ring-[#1c482c] priority-radio">
                                        </label>
                                        <label class="priority-label border border-gray-200 bg-[#f9f7f4] p-3 rounded-xl flex items-center justify-between cursor-pointer transition">
                                            <div>
                                                <strong class="block text-gray-900 text-[11px]">Urgent Flash (+₱1,000)</strong>
                                                <span class="text-[10px] text-amber-700 font-semibold">Same Day / Priority Approval</span>
                                            </div>
                                            <input type="radio" name="priority" value="rush" class="text-[#1c482c] focus:ring-[#1c482c] priority-radio">
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- STEP 02: DIRECTIVES & SCOPE REQUIREMENTS -->
                            <div class="bg-white p-5 rounded-2xl border border-gray-200/60 shadow-sm space-y-4">
                                <div class="flex justify-between items-center">
                                    <div class="flex items-center gap-2">
                                        <span class="w-6 h-6 rounded-lg bg-[#1c482c] text-white text-xs font-bold flex items-center justify-center">02</span>
                                        <h2 class="font-extrabold text-sm text-gray-900">Directives & Scope Requirements</h2>
                                    </div>
                                    <span class="text-[10px] font-medium text-gray-500 flex items-center gap-1">
                                        <i class="fa-solid fa-lock text-[9px] text-emerald-700"></i> Encrypted Transmission
                                    </span>
                                </div>

                                <div class="space-y-3 text-xs">
                                    <div class="space-y-1">
                                        <label class="text-[10px] font-bold text-gray-500 uppercase tracking-wider block">Directive Title / Purpose <span class="text-red-500">*</span></label>
                                        <input type="text" name="directive_title" required placeholder="Ex: 2026 Mayor's Permit Annual Renewal & Barangay Clearance" 
                                               class="w-full bg-[#f9f7f4] border border-gray-300 rounded-xl px-3 py-2 text-xs text-gray-800 font-medium focus:outline-none focus:border-[#1c482c]">
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                        <div class="space-y-1">
                                            <label class="text-[10px] font-bold text-gray-500 uppercase tracking-wider block">Target Entity / Units</label>
                                            <input type="text" value="<?= htmlspecialchars($company_name) ?>" readonly 
                                                   class="w-full bg-gray-100 border border-gray-200 rounded-xl px-3 py-2 text-xs text-gray-600 font-medium cursor-not-allowed">
                                        </div>
                                        <div class="space-y-1">
                                            <label class="text-[10px] font-bold text-gray-500 uppercase tracking-wider block">Target Completion Date</label>
                                            <input type="date" name="target_date" value="<?= date('Y-m-d', strtotime('+3 days')) ?>" 
                                                   class="w-full bg-[#f9f7f4] border border-gray-300 rounded-xl px-3 py-2 text-xs text-gray-800 font-medium focus:outline-none focus:border-[#1c482c]">
                                        </div>
                                    </div>

                                    <div class="space-y-1">
                                        <label class="text-[10px] font-bold text-gray-500 uppercase tracking-wider block">Detailed Operating Instructions & Specific Notes</label>
                                        <textarea name="instructions" rows="3" placeholder="Add additional instructions for the Malolos Hub specialists..." 
                                                  class="w-full bg-[#f9f7f4] border border-gray-300 rounded-xl p-3 text-xs text-gray-800 font-medium focus:outline-none focus:border-[#1c482c]"></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- STEP 03: SUPPORTING DOCUMENTS & ASSET VAULT -->
                            <div class="bg-white p-5 rounded-2xl border border-gray-200/60 shadow-sm space-y-4">
                                <div class="flex justify-between items-center">
                                    <div class="flex items-center gap-2">
                                        <span class="w-6 h-6 rounded-lg bg-[#1c482c] text-white text-xs font-bold flex items-center justify-center">03</span>
                                        <h2 class="font-extrabold text-sm text-gray-900">Supporting Documents & Asset Vault</h2>
                                    </div>
                                    <span class="text-[10px] text-gray-400 font-medium">Max Size: 25MB</span>
                                </div>

                                <!-- DROPZONE / FILE INPUT -->
                                <div class="border-2 border-dashed border-gray-300 bg-[#f9f7f4] rounded-2xl p-6 text-center space-y-2 relative hover:border-[#1c482c] transition">
                                    <input type="file" name="document" id="fileUploadInput" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                                    <div class="w-10 h-10 rounded-full bg-white shadow-xs border border-gray-200 flex items-center justify-center mx-auto text-[#1c482c] text-sm">
                                        <i class="fa-solid fa-cloud-arrow-up"></i>
                                    </div>
                                    <div>
                                        <strong class="text-xs font-extrabold text-gray-900 block" id="uploadStatusText">Drop supporting records or Click to Browse</strong>
                                        <span class="text-[10px] text-gray-500 block mt-0.5">Accepted file formats: PDF, PNG, JPG, DOCX, ZIP. Secured in local server.</span>
                                    </div>
                                    <span class="inline-block bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 font-semibold text-xs py-1.5 px-4 rounded-xl shadow-xs transition-all pointer-events-none">
                                        Browse Local Files
                                    </span>
                                </div>
                            </div>

                            <!-- STEP 04: BRANCH LOGISTICS & FULFILLMENT -->
                            <div class="bg-white p-5 rounded-2xl border border-gray-200/60 shadow-sm space-y-4">
                                <div class="flex justify-between items-center">
                                    <div class="flex items-center gap-2">
                                        <span class="w-6 h-6 rounded-lg bg-[#1c482c] text-white text-xs font-bold flex items-center justify-center">04</span>
                                        <h2 class="font-extrabold text-sm text-gray-900">Branch Logistics & Fulfillment</h2>
                                    </div>
                                    <span class="bg-emerald-100 text-emerald-800 text-[10px] font-bold px-2 py-0.5 rounded-full">Malolos Center Active</span>
                                </div>

                                <div class="space-y-3 text-xs">
                                    <div class="space-y-1">
                                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Assigned Operational Branch</label>
                                        <div class="bg-[#f9f7f4] border border-gray-200 rounded-xl px-3 py-2 text-gray-800 font-medium flex items-center gap-2">
                                            <i class="fa-solid fa-building text-gray-400"></i> Malolos Headquarters (3rd Flr, Topico Bldg., MacArthur Hwy, Malolos, Bulacan)
                                        </div>
                                    </div>

                                    <div class="space-y-1">
                                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Document Dispatch & Handover Preference</label>
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                                            <label class="delivery-label border-2 border-[#1c482c] bg-emerald-50/30 p-3 rounded-xl flex items-start gap-2.5 cursor-pointer">
                                                <input type="radio" name="delivery_pref" value="Digital + Courier" checked class="mt-1 text-[#1c482c] delivery-radio">
                                                <div>
                                                    <strong class="block text-gray-900 text-[11px]">Digital + Courier</strong>
                                                    <span class="text-[10px] text-gray-500">Express Delivery (+₱150)</span>
                                                </div>
                                            </label>
                                            <label class="delivery-label border border-gray-200 bg-[#f9f7f4] p-3 rounded-xl flex items-start gap-2.5 cursor-pointer">
                                                <input type="radio" name="delivery_pref" value="Pick Up at Malolos" class="mt-1 text-[#1c482c] delivery-radio">
                                                <div>
                                                    <strong class="block text-gray-900 text-[11px]">Pick Up at Malolos</strong>
                                                    <span class="text-[10px] text-gray-500">Front Desk Counter (₱0)</span>
                                                </div>
                                            </label>
                                            <label class="delivery-label border border-gray-200 bg-[#f9f7f4] p-3 rounded-xl flex items-start gap-2.5 cursor-pointer">
                                                <input type="radio" name="delivery_pref" value="On-Site Liaison" class="mt-1 text-[#1c482c] delivery-radio">
                                                <div>
                                                    <strong class="block text-gray-900 text-[11px]">On-Site Liaison</strong>
                                                    <span class="text-[10px] text-gray-500">Store Premises Handover</span>
                                                </div>
                                            </label>
                                        </div>
                                    </div>

                                    <div class="space-y-1">
                                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Special Delivery or Gate Instructions</label>
                                        <input type="text" name="special_delivery" placeholder="Ex: Deliver to reception / look for Store Manager" 
                                               class="w-full bg-[#f9f7f4] border border-gray-200 rounded-xl px-3 py-2 text-gray-800 font-medium focus:outline-none focus:border-[#1c482c]">
                                    </div>
                                </div>
                            </div>

                        </div>

                        <!-- RIGHT COLUMN: SUMMARY & SUPPORT -->
                        <div class="lg:col-span-4 space-y-4">
                            
                            <!-- DIRECTIVE SUMMARY CARD -->
                            <div class="bg-white p-4 rounded-2xl border border-gray-200/60 shadow-sm space-y-3 sticky top-20">
                                <div class="flex justify-between items-center">
                                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Directive Summary</span>
                                    <span class="bg-emerald-100 text-emerald-800 text-[9px] font-bold px-2 py-0.5 rounded-full">Auto-Calculated</span>
                                </div>

                                <div class="space-y-2 text-xs divide-y divide-gray-100">
                                    <div class="flex justify-between pt-2">
                                        <span class="text-gray-500">Selected Service</span>
                                        <strong class="text-gray-900 truncate max-w-[170px]" id="summaryServiceName">None Selected</strong>
                                    </div>
                                    <div class="flex justify-between pt-2">
                                        <span class="text-gray-500">Est. Processing Window</span>
                                        <strong class="text-gray-900" id="summaryWindow">-- Days</strong>
                                    </div>
                                    <div class="flex justify-between pt-2">
                                        <span class="text-gray-500">Base Service Fee</span>
                                        <strong class="text-gray-900" id="summaryBasePrice">₱0.00</strong>
                                    </div>
                                    <div class="flex justify-between pt-2">
                                        <span class="text-gray-500">Priority Surcharge</span>
                                        <strong class="text-gray-900" id="summarySurcharge">₱0.00</strong>
                                    </div>
                                    <div class="flex justify-between pt-2">
                                        <span class="text-gray-500">Handover / Delivery Fee</span>
                                        <strong class="text-gray-900" id="summaryDelivery">₱150.00</strong>
                                    </div>
                                    <div class="flex justify-between pt-2 text-sm">
                                        <span class="font-bold text-gray-900">Total Est. Amount</span>
                                        <strong class="text-[#1c482c] font-black text-base" id="summaryTotal">₱150.00</strong>
                                    </div>
                                </div>

                                <div class="pt-2 border-t border-gray-200/60">
                                    <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wider block mb-1.5">ASSIGNED ACCOUNT DESK</span>
                                    <div class="flex items-center gap-2.5 bg-[#f9f7f4] p-2.5 rounded-xl border border-gray-200/60">
                                        <div class="w-7 h-7 rounded-full bg-emerald-800 text-white font-bold flex items-center justify-center text-xs">
                                            <?= htmlspecialchars($desk ? getUserInitials($desk['full_name']) : 'OB') ?>
                                        </div>
                                        <div class="text-xs">
                                            <strong class="block text-gray-900"><?= htmlspecialchars($desk['full_name'] ?? 'Operations Desk') ?></strong>
                                            <span class="text-[10px] text-gray-500"><?= htmlspecialchars($desk['position'] ?? 'Operations Supervisor Desk') ?></span>
                                        </div>
                                    </div>
                                </div>

                                <!-- FULFILLMENT PROTOCOL LIST -->
                                <div class="pt-2 space-y-2 text-xs">
                                    <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wider block">DIRECTIVE DISPATCH GUARANTEE</span>
                                    <ul class="space-y-1.5 text-[11px] text-gray-600">
                                        <li class="flex items-start gap-2">
                                            <i class="fa-solid fa-circle text-[6px] text-emerald-700 mt-1.5"></i>
                                            <span>Immediate sync with Malolos Operations Queue.</span>
                                        </li>
                                        <li class="flex items-start gap-2">
                                            <i class="fa-solid fa-circle text-[6px] text-emerald-700 mt-1.5"></i>
                                            <span>Assigned directly to dedicated documentation specialist.</span>
                                        </li>
                                    </ul>
                                </div>

                                <!-- BUTTON IN SIDEBAR TOO FOR QUICK ACCESS -->
                                <button type="submit" class="w-full bg-[#1c482c] hover:bg-[#153721] text-white font-bold py-2.5 px-4 rounded-xl text-xs flex items-center justify-center gap-2 shadow-sm transition-all mt-3">
                                    <i class="fa-solid fa-paper-plane text-xs"></i> Submit Directive & Dispatch
                                </button>
                            </div>

                        </div>

                    </div>

                    <!-- FOOTER ACTIONS -->
                    <div class="bg-white px-5 py-3 rounded-2xl border border-gray-200/60 shadow-sm flex flex-col md:flex-row justify-between items-center gap-3 mt-4">
                        <div class="flex items-center gap-2 text-[11px] text-gray-500">
                            <i class="fa-solid fa-shield text-emerald-600"></i>
                            <span>OHANABIZ Core Protocol Synchronized • Direct MySQL Recording</span>
                        </div>

                        <div class="flex items-center gap-2 w-full md:w-auto justify-end">
                            <a href="client_dashboard.php" class="bg-[#f9f7f4] hover:bg-gray-100 text-gray-700 border border-gray-200 font-semibold py-2 px-4 rounded-xl text-xs transition-all text-center">
                                Cancel & Return
                            </a>
                            <button type="submit" class="bg-[#1c482c] hover:bg-[#153721] text-white font-semibold py-2 px-5 rounded-xl text-xs flex items-center justify-center gap-1.5 shadow-sm transition-all">
                                Submit Directive & Dispatch <i class="fa-solid fa-arrow-right text-[10px]"></i>
                            </button>
                        </div>
                    </div>

                </form>

            </main>
        </div>
    </div>

    <!-- SCRIPT FOR DYNAMIC CALCULATIONS & FILE PREVIEW -->
    <script>
        const serviceSelect = document.getElementById('serviceSelect');
        const summaryServiceName = document.getElementById('summaryServiceName');
        const summaryWindow = document.getElementById('summaryWindow');
        const summaryBasePrice = document.getElementById('summaryBasePrice');
        const summarySurcharge = document.getElementById('summarySurcharge');
        const summaryDelivery = document.getElementById('summaryDelivery');
        const summaryTotal = document.getElementById('summaryTotal');
        const fileUploadInput = document.getElementById('fileUploadInput');
        const uploadStatusText = document.getElementById('uploadStatusText');

        function recalculate() {
            let basePrice = 0;
            let days = 3;
            let name = 'None Selected';

            if (serviceSelect.selectedIndex > 0) {
                const opt = serviceSelect.options[serviceSelect.selectedIndex];
                basePrice = parseFloat(opt.dataset.price) || 0;
                days = parseInt(opt.dataset.days) || 3;
                name = opt.dataset.name || opt.text;
            }

            // Priority
            let surcharge = 0;
            const selectedPriority = document.querySelector('input[name="priority"]:checked');
            if (selectedPriority) {
                if (selectedPriority.value === 'urgent') surcharge = 500;
                if (selectedPriority.value === 'rush') surcharge = 1000;
            }

            // Delivery
            let deliveryFee = 0;
            const selectedDelivery = document.querySelector('input[name="delivery_pref"]:checked');
            if (selectedDelivery && selectedDelivery.value.includes('Courier')) {
                deliveryFee = 150;
            }

            const total = basePrice + surcharge + deliveryFee;

            summaryServiceName.textContent = name;
            summaryWindow.textContent = days + (days === 1 ? ' Working Day' : ' Working Days');
            summaryBasePrice.textContent = '₱' + basePrice.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            summarySurcharge.textContent = '₱' + surcharge.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            summaryDelivery.textContent = '₱' + deliveryFee.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            summaryTotal.textContent = '₱' + total.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        }

        serviceSelect.addEventListener('change', recalculate);

        // Highlight radio cards
        document.querySelectorAll('.priority-radio').forEach(r => {
            r.addEventListener('change', () => {
                document.querySelectorAll('.priority-label').forEach(l => {
                    l.classList.remove('border-2', 'border-[#1c482c]', 'bg-emerald-50/30');
                    l.classList.add('border-gray-200', 'bg-[#f9f7f4]');
                });
                r.closest('.priority-label').classList.remove('border-gray-200', 'bg-[#f9f7f4]');
                r.closest('.priority-label').classList.add('border-2', 'border-[#1c482c]', 'bg-emerald-50/30');
                recalculate();
            });
        });

        document.querySelectorAll('.delivery-radio').forEach(r => {
            r.addEventListener('change', () => {
                document.querySelectorAll('.delivery-label').forEach(l => {
                    l.classList.remove('border-2', 'border-[#1c482c]', 'bg-emerald-50/30');
                    l.classList.add('border-gray-200', 'bg-[#f9f7f4]');
                });
                r.closest('.delivery-label').classList.remove('border-gray-200', 'bg-[#f9f7f4]');
                r.closest('.delivery-label').classList.add('border-2', 'border-[#1c482c]', 'bg-emerald-50/30');
                recalculate();
            });
        });

        fileUploadInput.addEventListener('change', () => {
            if (fileUploadInput.files.length > 0) {
                uploadStatusText.innerHTML = '<span class="text-emerald-700 font-bold"><i class="fa-solid fa-file-check mr-1"></i> ' + fileUploadInput.files[0].name + '</span> (Ready for upload)';
            }
        });

        // Initialize on load
        recalculate();
    </script>

</body>
</html>