<?php
/**
 * ============================================================
 *  CLIENT REGISTRATION — client_create_account.php
 *  Handles new client account creation via PDO
 * ============================================================
 */
require_once __DIR__ . '/config/auth.php';
redirectIfLoggedIn('client');

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect and sanitize inputs
    $full_name    = trim($_POST['full_name']       ?? '');
    $email        = trim($_POST['email']           ?? '');
    $phone        = trim($_POST['phone']           ?? '');
    $password     = $_POST['password']             ?? '';
    $confirm_pw   = $_POST['confirm_password']     ?? '';

    // Validation
    if (empty($full_name) || empty($email) || empty($phone) || empty($password)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } elseif (!preg_match('/[0-9]/', $password)) {
        $error = 'Password must contain at least one number.';
    } elseif (!preg_match('/[^a-zA-Z0-9]/', $password)) {
        $error = 'Password must contain at least one special character.';
    } elseif ($password !== $confirm_pw) {
        $error = 'Passwords do not match. Please try again.';
    } else {
        try {
            // Check if email already exists
            $check = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            $check->execute([$email]);
            if ($check->fetch()) {
                $error = 'This email address is already registered. Please use a different email or log in.';
            } else {
                // Hash the password
                $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

                // Begin transaction
                $pdo->beginTransaction();

                // Insert into users table
                $stmt = $pdo->prepare(
                    "INSERT INTO users (full_name, email, password_hash, role, phone, status)
                     VALUES (?, ?, ?, 'client', ?, 'active')"
                );
                $stmt->execute([$full_name, $email, $hash, $phone]);
                $new_user_id = $pdo->lastInsertId();

                // Insert into clients table (extended profile)
                $client_stmt = $pdo->prepare(
                    "INSERT INTO clients (user_id, authorized_rep_name)
                     VALUES (?, ?)"
                );
                $client_stmt->execute([$new_user_id, $full_name]);

                $pdo->commit();

                establishUserSession([
                    'id'         => $new_user_id,
                    'full_name'  => $full_name,
                    'email'      => $email,
                    'role'       => 'client',
                    'phone'      => $phone,
                    'position'   => '',
                    'department' => '',
                ]);

                header('Location: ' . dashboardForRole('client'));
                exit;
            }
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('[OHANA CLIENT REGISTER ERROR] ' . $e->getMessage());
            $error = 'A server error occurred during registration. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ohana Business Consultancy Inc. - Create Client Account</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        }
    </style>
</head>
<body class="bg-[#133321] text-gray-800 antialiased min-h-screen flex flex-col justify-between selection:bg-emerald-800 selection:text-white">

    <!-- TOP HEADER -->
    <header class="w-full py-4 px-6 flex items-center justify-between border-b border-emerald-900/50 bg-[#133321]/80 backdrop-blur-md">
        <a href="index.php" class="inline-flex items-center gap-2 text-xs font-semibold text-emerald-100/80 hover:text-white transition-colors bg-white/5 hover:bg-white/10 px-3.5 py-2 rounded-xl border border-white/10">
            <i class="fa-solid fa-arrow-left text-[10px]"></i> Return to Ohana Landing Page
        </a>

        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-white p-1 border border-white/20 shadow-sm flex items-center justify-center shrink-0">
                <img src="assets/images/ohana.png" alt="OHANA Logo" class="w-full h-full object-contain">
            </div>
            <div class="text-right hidden sm:block">
                <span class="block font-bold text-xs text-white tracking-tight">OHANA BUSINESS CONSULTANCY INC.</span>
                <span class="block text-[9px] text-emerald-300 uppercase tracking-widest font-medium">ENTERPRISE CLIENT SUITE</span>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 inline-block ring-4 ring-emerald-500/20"></span>
        </div>
    </header>

    <!-- MAIN HERO & REGISTRATION SECTION -->
    <main class="flex-1 flex flex-col items-center justify-center px-4 py-8 relative overflow-hidden">
        
        <!-- BACKGROUND GLOW EFFECTS -->
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[400px] bg-emerald-600/10 blur-[120px] rounded-full pointer-events-none"></div>

        <div class="w-full max-w-2xl text-center space-y-3 mb-6 relative z-10">
            <div class="inline-flex items-center gap-2 bg-white/10 border border-white/10 text-emerald-200 text-[10px] font-bold px-3 py-1 rounded-full tracking-wider uppercase backdrop-blur-sm">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span> YOUR FAMILY IN DOING BUSINESS
            </div>
            
            <h1 class="text-3xl md:text-4xl font-extrabold text-white tracking-tight">
                SECURE CLIENT PORTAL
            </h1>
            
            <p class="text-xs md:text-sm text-emerald-100/70 max-w-lg mx-auto leading-relaxed">
                Register your business account to access official business services, monitor active compliance filings, and track printing directives.
            </p>
        </div>

        <!-- REGISTRATION CARD CONTAINER -->
        <div class="w-full max-w-xl bg-white rounded-3xl shadow-2xl border border-gray-100 p-6 md:p-8 relative z-10 space-y-6">
            
            <div class="flex items-center justify-between pb-4 border-b border-gray-100">
                <div class="flex items-center gap-2">
                    <div class="w-6 h-6 rounded-md bg-[#133321] text-white flex items-center justify-center text-[10px]">
                        <i class="fa-solid fa-user-plus"></i>
                    </div>
                    <span class="text-[10px] font-bold text-gray-500 uppercase tracking-wider">OHANA CLIENT ONBOARDING</span>
                </div>
                <span class="bg-amber-100 text-amber-900 text-[10px] font-bold px-2.5 py-0.5 rounded-full">New Client Account</span>
            </div>

            <div class="text-left space-y-1">
                <h2 class="text-base font-extrabold text-gray-900">Create Account</h2>
                <p class="text-[11px] text-gray-500">Register your corporate profile to synchronize with Ohana Enterprise Services and submit service requests.</p>
            </div>

            <!-- ERROR ALERT -->
            <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 rounded-xl p-3 flex items-center gap-2 text-xs text-red-700">
                <i class="fa-solid fa-circle-exclamation text-red-500 shrink-0"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
            <?php endif; ?>

            <form action="client_create_account.php" method="POST" class="space-y-4 text-left" id="registerForm">
                
                <!-- AUTHORIZED REPRESENTATIVE FULL NAME -->
                <div class="space-y-1.5">
                    <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider">Authorized Representative Full Name *</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400 text-xs">
                            <i class="fa-regular fa-user"></i>
                        </span>
                        <input
                            type="text"
                            name="full_name"
                            value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>"
                            placeholder="e.g. Maria Clara Santos"
                            required
                            class="w-full bg-[#f9f9f9] border border-gray-200 rounded-xl pl-10 pr-4 py-2.5 text-xs font-medium text-gray-800 focus:outline-none focus:border-[#133321] focus:bg-white transition-all"
                        >
                    </div>
                </div>

                <!-- EMAIL ADDRESS & CONTACT MOBILE -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div class="space-y-1.5">
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider">Email Address *</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400 text-xs">
                                <i class="fa-regular fa-envelope"></i>
                            </span>
                            <input
                                type="email"
                                name="email"
                                value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                                placeholder="e.g. client@yourcompany.ph"
                                required
                                autocomplete="email"
                                class="w-full bg-[#f9f9f9] border border-gray-200 rounded-xl pl-10 pr-4 py-2.5 text-xs font-medium text-gray-800 focus:outline-none focus:border-[#133321] focus:bg-white transition-all"
                            >
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider">Contact Mobile / Phone *</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400 text-xs">
                                <i class="fa-solid fa-phone"></i>
                            </span>
                            <input
                                type="text"
                                name="phone"
                                value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"
                                placeholder="e.g. 0997 855 1913"
                                required
                                class="w-full bg-[#f9f9f9] border border-gray-200 rounded-xl pl-10 pr-4 py-2.5 text-xs font-medium text-gray-800 focus:outline-none focus:border-[#133321] focus:bg-white transition-all"
                            >
                        </div>
                    </div>
                </div>

                <!-- PASSWORD & CONFIRM PASSWORD -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div class="space-y-1.5">
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider">Password *</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400 text-xs">
                                <i class="fa-solid fa-lock"></i>
                            </span>
                            <input
                                type="password"
                                name="password"
                                id="regPassword"
                                required
                                placeholder="Min 8 characters"
                                autocomplete="new-password"
                                class="w-full bg-[#f9f9f9] border border-gray-200 rounded-xl pl-10 pr-10 py-2.5 text-xs font-medium text-gray-800 focus:outline-none focus:border-[#133321] focus:bg-white transition-all"
                            >
                            <button type="button" onclick="togglePw('regPassword','regEye1')" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-gray-400 hover:text-gray-600 text-xs">
                                <i class="fa-regular fa-eye" id="regEye1"></i>
                            </button>
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider">Confirm Password *</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400 text-xs">
                                <i class="fa-solid fa-shield-check"></i>
                            </span>
                            <input
                                type="password"
                                name="confirm_password"
                                id="regConfirm"
                                required
                                placeholder="Re-enter password"
                                autocomplete="new-password"
                                class="w-full bg-[#f9f9f9] border border-gray-200 rounded-xl pl-10 pr-10 py-2.5 text-xs font-medium text-gray-800 focus:outline-none focus:border-[#133321] focus:bg-white transition-all"
                            >
                            <button type="button" onclick="togglePw('regConfirm','regEye2')" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-gray-400 hover:text-gray-600 text-xs">
                                <i class="fa-regular fa-eye" id="regEye2"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- PASSWORD REQUIREMENTS BADGES -->
                <div class="flex items-center gap-2 flex-wrap pt-1">
                    <span id="req_len"  class="bg-gray-100 text-gray-500 border border-gray-200/60 text-[10px] font-bold px-2.5 py-1 rounded-full flex items-center gap-1 transition-colors">
                        <i class="fa-solid fa-check text-[9px]"></i> 8+ Characters
                    </span>
                    <span id="req_num"  class="bg-gray-100 text-gray-500 border border-gray-200/60 text-[10px] font-bold px-2.5 py-1 rounded-full flex items-center gap-1 transition-colors">
                        <i class="fa-solid fa-check text-[9px]"></i> 1 Number
                    </span>
                    <span id="req_sym"  class="bg-gray-100 text-gray-500 border border-gray-200/60 text-[10px] font-bold px-2.5 py-1 rounded-full flex items-center gap-1 transition-colors">
                        <i class="fa-solid fa-check text-[9px]"></i> 1 Special Symbol
                    </span>
                    <span id="req_match" class="bg-gray-100 text-gray-500 border border-gray-200/60 text-[10px] font-bold px-2.5 py-1 rounded-full flex items-center gap-1 transition-colors">
                        <i class="fa-solid fa-check text-[9px]"></i> Passwords Match
                    </span>
                </div>

                <!-- TERMS & CONDITIONS CHECKBOX -->
                <div class="flex items-start gap-2 pt-1">
                    <input type="checkbox" id="terms" name="terms" required class="w-4 h-4 mt-0.5 rounded border-gray-300 text-[#133321] focus:ring-[#133321]">
                    <label for="terms" class="text-[11px] text-gray-600 font-medium select-none leading-relaxed">
                        I agree to the <a href="#" class="font-bold text-[#133321] underline">Client Terms of Service</a> and acknowledge strict compliance with the <strong class="text-gray-800">Philippine Data Privacy Act of 2012 (R.A. 10173)</strong>.
                    </label>
                </div>

                <!-- SUBMIT BUTTON -->
                <button type="submit" class="w-full bg-[#133321] hover:bg-[#1a472f] text-white font-bold py-3 px-4 rounded-xl text-xs tracking-wide shadow-md transition-all flex items-center justify-center gap-2 mt-2">
                    Submit Account Registration <i class="fa-solid fa-arrow-right text-[10px]"></i>
                </button>

            </form>

            <!-- FOOTER LINK INSIDE CARD -->
            <div class="pt-2 text-center">
                <p class="text-xs text-gray-500">
                    Already have a registered client account? <a href="login.php" class="font-bold text-[#133321] hover:underline inline-flex items-center gap-1">Log In Here <i class="fa-solid fa-arrow-right text-[10px]"></i></a>
                </p>
            </div>

        </div>

        <!-- BOTTOM BADGES / FEATURES -->
        <div class="w-full max-w-5xl grid grid-cols-1 md:grid-cols-3 gap-3 mt-10 relative z-10">
            <div class="bg-white/90 backdrop-blur-sm p-4 rounded-2xl border border-white/20 shadow-sm flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-800 flex items-center justify-center shrink-0 text-sm">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <div class="text-left">
                    <strong class="block text-xs font-extrabold text-gray-900">256-bit Enterprise SSL</strong>
                    <span class="block text-[10px] text-gray-500 font-medium">Encrypted Multi-Node Access</span>
                </div>
            </div>
            <div class="bg-white/90 backdrop-blur-sm p-4 rounded-2xl border border-white/20 shadow-sm flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-800 flex items-center justify-center shrink-0 text-sm">
                    <i class="fa-solid fa-file-shield"></i>
                </div>
                <div class="text-left">
                    <strong class="block text-xs font-extrabold text-gray-900">Data Privacy Act Compliant</strong>
                    <span class="block text-[10px] text-gray-500 font-medium">Philippine R.A. 10173 Standards</span>
                </div>
            </div>
            <div class="bg-white/90 backdrop-blur-sm p-4 rounded-2xl border border-white/20 shadow-sm flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-800 flex items-center justify-center shrink-0 text-sm">
                    <i class="fa-solid fa-building"></i>
                </div>
                <div class="text-left">
                    <strong class="block text-xs font-extrabold text-gray-900">Malolos, Bulacan Operational Hub</strong>
                    <span class="block text-[10px] text-gray-500 font-medium">Ground Floor Topico Building</span>
                </div>
            </div>
        </div>

    </main>

    <!-- FOOTER -->
    <footer class="w-full bg-[#133321] border-t border-emerald-900/60 py-4 px-6 text-center md:flex md:justify-between items-center text-[11px] text-emerald-100/60">
        <div class="flex items-center justify-center gap-2 mb-2 md:mb-0">
            <div class="w-5 h-5 rounded bg-white/10 flex items-center justify-center text-white text-[9px]">
                <i class="fa-solid fa-cube"></i>
            </div>
            <span>© <?= date('Y') ?> OHANA Business & Government Assistance Services • Bulacan Branch. All rights reserved.</span>
        </div>
        <div>
            <span class="font-semibold text-emerald-300">OHANA BUSINESS CONSULTANCY INC.</span> — "YOUR FAMILY IN DOING BUSINESS"
        </div>
    </footer>

    <script>
        // Toggle password visibility
        function togglePw(fieldId, iconId) {
            const f = document.getElementById(fieldId);
            const i = document.getElementById(iconId);
            f.type = f.type === 'password' ? 'text' : 'password';
            i.classList.toggle('fa-eye');
            i.classList.toggle('fa-eye-slash');
        }

        // Real-time password strength indicators
        const pwField  = document.getElementById('regPassword');
        const cfField  = document.getElementById('regConfirm');

        function checkReq(id, condition) {
            const el = document.getElementById(id);
            if (condition) {
                el.className = el.className.replace('bg-gray-100 text-gray-500 border-gray-200/60', 'bg-emerald-50 text-emerald-800 border-emerald-200/60');
            } else {
                el.className = el.className.replace('bg-emerald-50 text-emerald-800 border-emerald-200/60', 'bg-gray-100 text-gray-500 border-gray-200/60');
            }
        }

        pwField.addEventListener('input', () => {
            const v = pwField.value;
            checkReq('req_len',  v.length >= 8);
            checkReq('req_num',  /[0-9]/.test(v));
            checkReq('req_sym',  /[^a-zA-Z0-9]/.test(v));
            checkReq('req_match', v.length > 0 && v === cfField.value);
        });

        cfField.addEventListener('input', () => {
            checkReq('req_match', pwField.value.length > 0 && pwField.value === cfField.value);
        });
    </script>

</body>
</html>