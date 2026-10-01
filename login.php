<?php
/**
 * ============================================================
 *  CLIENT LOGIN — login.php
 *  Handles client authentication via PDO + bcrypt
 * ============================================================
 */
require_once __DIR__ . '/config/auth.php';
redirectIfLoggedIn('client');

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $error = 'Please enter your email and password.';
    } else {
        try {
            $stmt = $pdo->prepare(
                "SELECT id, full_name, email, password_hash, role, status, position, department, phone
                 FROM users
                 WHERE email = ? AND role = 'client'
                 LIMIT 1"
            );
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                if ($user['status'] !== 'active') {
                    $error = 'Your account is inactive or suspended. Please contact OHANA support.';
                } else {
                    // Update last login timestamp
                    $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")
                        ->execute([$user['id']]);

                    establishUserSession($user);
                    header('Location: ' . dashboardForRole('client'));
                    exit;
                }
            } else {
                $error = 'Invalid email or password. Please try again.';
            }
        } catch (PDOException $e) {
            error_log('[OHANA LOGIN ERROR] ' . $e->getMessage());
            $error = 'A server error occurred. Please try again later.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ohana Business Consultancy Inc. - Secure Client Portal</title>
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
                <span class="block text-[9px] text-emerald-300 uppercase tracking-widest font-medium">ENTERPRISE CLIENT GATEWAY</span>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 inline-block ring-4 ring-emerald-500/20"></span>
        </div>
    </header>

    <!-- MAIN HERO & LOGIN SECTION -->
    <main class="flex-1 flex flex-col items-center justify-center px-4 py-8 relative overflow-hidden">
        
        <!-- BACKGROUND GLOW EFFECTS -->
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[400px] bg-emerald-600/10 blur-[120px] rounded-full pointer-events-none"></div>

        <div class="w-full max-w-xl text-center space-y-3 mb-6 relative z-10">
            <div class="inline-flex items-center gap-2 bg-white/10 border border-white/10 text-emerald-200 text-[10px] font-bold px-3 py-1 rounded-full tracking-wider uppercase backdrop-blur-sm">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span> YOUR FAMILY IN DOING BUSINESS
            </div>
            
            <h1 class="text-3xl md:text-4xl font-extrabold text-white tracking-tight">
                SECURE CLIENT PORTAL
            </h1>
            
            <p class="text-xs md:text-sm text-emerald-100/70 max-w-md mx-auto leading-relaxed">
                Unified business services, active filings tracking, printing orders, and corporate advisory access for registered clients.
            </p>
        </div>

        <!-- LOGIN CARD CONTAINER -->
        <div class="w-full max-w-md bg-white rounded-3xl shadow-2xl border border-gray-100 p-6 md:p-8 relative z-10 space-y-6">
            
            <div class="flex items-center justify-between pb-4 border-b border-gray-100">
                <div class="flex items-center gap-2">
                    <div class="w-6 h-6 rounded-md bg-[#133321] text-white flex items-center justify-center text-[10px]">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>
                    <span class="text-[10px] font-bold text-gray-500 uppercase tracking-wider">OHANA CLIENT SUITE</span>
                </div>
            </div>

            <!-- ERROR / SUCCESS ALERTS -->
            <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 rounded-xl p-3 flex items-center gap-2 text-xs text-red-700">
                <i class="fa-solid fa-circle-exclamation text-red-500"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
            <?php endif; ?>

            <form action="login.php" method="POST" class="space-y-4">
                
                <!-- EMAIL ADDRESS -->
                <div class="space-y-1.5 text-left">
                    <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider">EMAIL ADDRESS</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400 text-xs">
                            <i class="fa-regular fa-envelope"></i>
                        </span>
                        <input
                            type="email"
                            name="email"
                            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                            placeholder="e.g. client@ohanabiz.com"
                            required
                            autocomplete="email"
                            class="w-full bg-[#f9f9f9] border border-gray-200 rounded-xl pl-10 pr-4 py-2.5 text-xs font-medium text-gray-800 focus:outline-none focus:border-[#133321] focus:bg-white transition-all"
                        >
                    </div>
                </div>

                <!-- PASSWORD -->
                <div class="space-y-1.5 text-left">
                    <div class="flex justify-between items-center">
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider">PASSWORD</label>
                        <a href="#" class="text-[11px] font-semibold text-emerald-800 hover:underline">Forgot Password?</a>
                    </div>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400 text-xs">
                            <i class="fa-solid fa-lock"></i>
                        </span>
                        <input
                            type="password"
                            name="password"
                            id="passwordField"
                            required
                            autocomplete="current-password"
                            placeholder="Enter your password"
                            class="w-full bg-[#f9f9f9] border border-gray-200 rounded-xl pl-10 pr-10 py-2.5 text-xs font-medium text-gray-800 focus:outline-none focus:border-[#133321] focus:bg-white transition-all"
                        >
                        <button type="button" onclick="togglePassword()" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-gray-400 hover:text-gray-600 text-xs">
                            <i class="fa-regular fa-eye" id="eyeIcon"></i>
                        </button>
                    </div>
                </div>

                <!-- REMEMBER ME -->
                <div class="flex items-center gap-2 text-left pt-1">
                    <input type="checkbox" id="remember" name="remember" class="w-4 h-4 rounded border-gray-300 text-[#133321] focus:ring-[#133321]">
                    <label for="remember" class="text-xs text-gray-600 font-medium select-none">Remember me</label>
                </div>

                <!-- SUBMIT BUTTON -->
                <button type="submit" class="w-full bg-[#133321] hover:bg-[#1a472f] text-white font-bold py-3 px-4 rounded-xl text-xs tracking-wide shadow-md transition-all flex items-center justify-center gap-2">
                    <i class="fa-solid fa-right-to-bracket text-xs"></i> Log In to Client Portal
                </button>

            </form>

            <!-- FOOTER LINKS INSIDE CARD -->
            <div class="pt-2 text-center space-y-3">
                <p class="text-xs text-gray-500">
                    Don't have a registered client account? <br>
                    <a href="client_create_account.php" class="font-bold text-[#133321] hover:underline inline-flex items-center gap-1 mt-1">
                        Create Account <i class="fa-solid fa-arrow-right text-[10px]"></i>
                    </a>
                </p>

                <div class="pt-3 border-t border-gray-100 flex items-center justify-center gap-1.5 text-[10px] text-gray-400 font-medium">
                    <i class="fa-solid fa-shield text-emerald-700"></i>
                    <span>Restricted portal for authorized Ohana clients and business partners. 256-bit TLS encrypted connection.</span>
                </div>
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
        function togglePassword() {
            const field = document.getElementById('passwordField');
            const icon  = document.getElementById('eyeIcon');
            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                field.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }
    </script>

</body>
</html>