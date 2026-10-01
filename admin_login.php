<?php
/**
 * ============================================================
 *  ADMIN LOGIN — admin_login.php
 *  Handles administrator authentication via PDO
 * ============================================================
 */
require_once __DIR__ . '/config/auth.php';
if (!isset($_GET['logged_out'])) {
    redirectIfLoggedIn('admin');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $error = 'Please enter your administrator email and password.';
    } else {
        try {
            $stmt = $pdo->prepare(
                "SELECT id, full_name, email, password_hash, role, status, position, department, phone
                 FROM users
                 WHERE email = ? AND role = 'admin'
                 LIMIT 1"
            );
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                if ($user['status'] !== 'active') {
                    $error = 'This administrator account is inactive. Contact the system owner.';
                } else {
                    // Update last login
                    $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")
                        ->execute([$user['id']]);

                    $_SESSION['admin_logged_in'] = true;
                    establishUserSession($user);
                    header('Location: ' . dashboardForRole('admin'));
                    exit;
                }
            } else {
                $error = 'Invalid administrator credentials. Access denied.';
            }
        } catch (PDOException $e) {
            error_log('[OHANA ADMIN LOGIN ERROR] ' . $e->getMessage());
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
  <title>Secure Administrative Portal - OHANA Business Consultancy Inc.</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <style>
    body {
      font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
    }
  </style>
</head>
<body class="bg-[#FAF7F2] text-gray-800 antialiased min-h-screen flex flex-col justify-between">

  <!-- MAIN TOP WRAPPER WITH DARK GREEN BACKGROUND -->
  <div class="bg-gradient-to-b from-[#1C3A2B] via-[#234A37] to-[#1C3A2B] text-white pt-6 pb-48 px-8 relative overflow-hidden">
    
    <!-- TOP NAVIGATION BAR -->
    <header class="max-w-7xl mx-auto flex items-center justify-between">
      <a href="index.php" class="inline-flex items-center gap-2 bg-white/10 hover:bg-white/20 text-xs font-semibold px-4 py-2 rounded-full backdrop-blur-md border border-white/10 transition">
        <i class="fa-solid fa-arrow-left text-[10px]"></i>
        Return to Ohana landing Page
      </a>

      <!-- Brand Center -->
      <div class="flex items-center gap-3">
        <div class="w-9 h-9 rounded-xl bg-white p-1 border border-white/20 shadow-sm flex items-center justify-center shrink-0">
          <img src="assets/images/ohana.png" alt="OHANA Logo" class="w-full h-full object-contain">
        </div>
        <div class="text-left">
          <h1 class="text-xs font-extrabold tracking-wider leading-none uppercase">OHANA BUSINESS CONSULTANCY INC.</h1>
          <span class="text-[9px] text-emerald-300 font-bold tracking-widest uppercase">ENTERPRISE TRACKING SYSTEM</span>
        </div>
      </div>

      <!-- Live Status Indicator -->
      <div class="flex items-center gap-2">
        <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 shadow-[0_0_8px_rgba(52,211,153,0.8)]"></span>
      </div>
    </header>

    <!-- HERO HEADER CONTENT -->
    <div class="max-w-3xl mx-auto text-center mt-12 space-y-3">
      <div class="inline-flex items-center gap-2 bg-white/10 px-3.5 py-1 rounded-full border border-white/10 text-[10px] font-bold tracking-widest uppercase text-emerald-200">
        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
        YOUR FAMILY IN DOING BUSINESS
      </div>

      <h2 class="text-3xl font-black tracking-tight text-white uppercase">
        SECURE ADMINISTRATIVE PORTAL
      </h2>

      <p class="text-xs text-emerald-100/70 max-w-xl mx-auto leading-relaxed">
        Unified authentication and client management suite for authorized staff, account managers, and executive liaison officers.
      </p>
    </div>
  </div>

  <!-- FLOATING LOGIN CARD CONTAINER -->
  <div class="max-w-md mx-auto -mt-36 px-4 relative z-10 w-full">
    <div class="bg-white rounded-3xl p-8 shadow-2xl border border-gray-100 space-y-6">
      
      <!-- Sub-header Badge -->
      <div class="flex justify-center">
        <span class="inline-flex items-center gap-1.5 bg-[#FAF7F2] text-gray-600 text-[10px] font-bold px-3 py-1 rounded-full uppercase tracking-wider border border-amber-900/5">
          <i class="fa-solid fa-shield text-amber-800 text-[10px]"></i>
          OHANA ADMIN SUITE
        </span>
      </div>

      <!-- ERROR ALERT -->
      <?php if ($error): ?>
      <div class="bg-red-50 border border-red-200 rounded-xl p-3 flex items-center gap-2 text-xs text-red-700">
        <i class="fa-solid fa-triangle-exclamation text-red-500"></i>
        <span><?= htmlspecialchars($error) ?></span>
      </div>
      <?php endif; ?>

      <!-- LOGGED OUT SUCCESS ALERT -->
      <?php if (isset($_GET['logged_out'])): ?>
      <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-3 flex items-center gap-2 text-xs text-emerald-800">
        <i class="fa-solid fa-circle-check text-emerald-600"></i>
        <span>You have been safely logged out of the Admin Portal.</span>
      </div>
      <?php endif; ?>

      <!-- Form Inputs -->
      <form action="admin_login.php" method="POST" class="space-y-4">
        
        <!-- Email Input -->
        <div class="space-y-1.5">
          <label class="text-[10px] font-bold text-gray-500 uppercase tracking-wider block">EMAIL ADDRESS</label>
          <div class="relative">
            <i class="fa-regular fa-envelope absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
            <input
              type="email"
              name="email"
              value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
              placeholder="admin@ohanabusiness.com"
              required
              autocomplete="email"
              class="w-full bg-[#FAF7F2] border border-gray-200/80 rounded-2xl pl-11 pr-4 py-3 text-xs text-gray-700 font-medium focus:outline-none focus:ring-2 focus:ring-[#2D5A43]/20 focus:border-[#2D5A43]"
            >
          </div>
        </div>

        <!-- Password Input -->
        <div class="space-y-1.5">
          <div class="flex items-center justify-between">
            <label class="text-[10px] font-bold text-gray-500 uppercase tracking-wider block">PASSWORD</label>
            <a href="#" class="text-[11px] text-amber-900/80 font-semibold hover:underline">Forgot Password?</a>
          </div>
          <div class="relative">
            <i class="fa-solid fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
            <input
              type="password"
              name="password"
              id="adminPassword"
              required
              autocomplete="current-password"
              placeholder="••••••••••••"
              class="w-full bg-[#FAF7F2] border border-gray-200/80 rounded-2xl pl-11 pr-10 py-3 text-xs text-gray-700 font-medium focus:outline-none focus:ring-2 focus:ring-[#2D5A43]/20 focus:border-[#2D5A43]"
            >
            <button type="button" onclick="toggleAdminPw()" class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 text-xs cursor-pointer hover:text-gray-600">
              <i class="fa-regular fa-eye" id="adminEyeIcon"></i>
            </button>
          </div>
        </div>

        <!-- Remember Me Checkbox -->
        <div class="flex items-center gap-2 pt-1">
          <input type="checkbox" id="remember" name="remember" class="w-4 h-4 rounded text-[#2D5A43] focus:ring-[#2D5A43] border-gray-300">
          <label for="remember" class="text-xs text-gray-600 font-medium cursor-pointer">Remember me</label>
        </div>

        <!-- Submit Button -->
        <button
          type="submit"
          class="w-full bg-[#2D5A43] hover:bg-[#234734] text-white font-bold py-3.5 rounded-2xl text-xs transition shadow-lg shadow-[#2D5A43]/20 mt-2 flex items-center justify-center gap-2"
        >
          <i class="fa-solid fa-right-to-bracket"></i> Login
        </button>
      </form>

      <!-- Request Staff Access Box -->
      <div class="bg-[#FAF7F2] p-4 rounded-2xl border border-gray-200/70 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="w-9 h-9 rounded-xl bg-orange-100 text-orange-700 flex items-center justify-center text-xs shrink-0">
            <i class="fa-solid fa-id-badge"></i>
          </div>
          <div>
            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">NEW PERSONNEL?</span>
            <span class="text-xs font-bold text-gray-800">Request credential issuance</span>
          </div>
        </div>
        <a href="employee_login.php" class="bg-white hover:bg-gray-50 text-gray-800 border border-gray-200 text-xs font-bold py-2 px-3 rounded-xl shadow-xs transition-all flex items-center gap-1 shrink-0">
          Staff Access <i class="fa-solid fa-arrow-up-right-from-square text-[9px]"></i>
        </a>
      </div>

      <!-- Restricted Access Notice -->
      <div class="pt-2 text-center border-t border-gray-100">
        <p class="text-[10px] text-gray-400 leading-relaxed max-w-xs mx-auto">
          <i class="fa-solid fa-shield-halved text-[10px] mr-1"></i>
          Restricted portal for authorized Ohana staff and executive consultants. IP addresses and terminal telemetry are logged for continuous audit.
        </p>
      </div>

    </div>
  </div>

  <!-- BOTTOM THREE FEATURE CARDS -->
  <div class="max-w-5xl mx-auto px-6 mt-12 mb-8 w-full">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      
      <!-- Card 1 -->
      <div class="bg-white border border-amber-900/10 rounded-2xl p-4 shadow-sm flex items-center gap-3">
        <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-900 flex items-center justify-center shrink-0">
          <i class="fa-solid fa-shield-check text-base"></i>
        </div>
        <div>
          <h4 class="text-xs font-bold text-gray-900 leading-tight">256-bit Enterprise SSL</h4>
          <span class="text-[10px] text-gray-400">Encrypted Multi-Node Access</span>
        </div>
      </div>

      <!-- Card 2 -->
      <div class="bg-white border border-amber-900/10 rounded-2xl p-4 shadow-sm flex items-center gap-3">
        <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-900 flex items-center justify-center shrink-0">
          <i class="fa-solid fa-file-contract text-base"></i>
        </div>
        <div>
          <h4 class="text-xs font-bold text-gray-900 leading-tight">Data Privacy Act Compliant</h4>
          <span class="text-[10px] text-gray-400">Philippine R.A. 10173 Standards</span>
        </div>
      </div>

      <!-- Card 3 -->
      <div class="bg-white border border-amber-900/10 rounded-2xl p-4 shadow-sm flex items-center gap-3">
        <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-900 flex items-center justify-center shrink-0">
          <i class="fa-solid fa-building text-base"></i>
        </div>
        <div>
          <h4 class="text-xs font-bold text-gray-900 leading-tight">Malolos, Bulacan Operational Hub</h4>
          <span class="text-[10px] text-gray-400">Ground Floor Topico Building</span>
        </div>
      </div>

    </div>
  </div>

  <!-- FOOTER -->
  <footer class="w-full">
    <div class="max-w-7xl mx-auto px-8 py-4 flex items-center justify-between text-[11px] text-gray-400 border-t border-gray-200/60">
      <div class="flex items-center gap-2">
        <div class="w-5 h-5 rounded bg-[#2D5A43] text-white flex items-center justify-center text-[10px]">
          <i class="fa-solid fa-shield-halved"></i>
        </div>
        <span>© <?= date('Y') ?> OHANA Business & Government Assistance Services • Bulacan Branch. All rights reserved.</span>
      </div>

      <span class="w-2 h-2 rounded-full bg-orange-500"></span>
    </div>

    <!-- BOTTOM BLACK BANNER -->
    <div class="bg-[#111827] text-gray-400 text-center text-[10px] py-2 font-bold tracking-widest uppercase border-t border-gray-800">
      OHANA BUSINESS CONSULTANCY INC. — <span class="text-gray-300">"YOUR FAMILY IN DOING BUSINESS"</span>
    </div>
  </footer>

  <script>
    function toggleAdminPw() {
      const field = document.getElementById('adminPassword');
      const icon  = document.getElementById('adminEyeIcon');
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