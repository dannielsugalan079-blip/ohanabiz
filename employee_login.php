<?php
/**
 * ============================================================
 *  EMPLOYEE LOGIN — employee_login.php
 *  Handles employee & supervisor authentication via PDO
 * ============================================================
 */
require_once __DIR__ . '/config/auth.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $error = 'Please enter your corporate email and password.';
    } else {
        try {
            $stmt = $pdo->prepare(
                "SELECT id, full_name, email, password_hash, role, position, department, phone, status
                 FROM users
                 WHERE email = ? AND role IN ('employee', 'supervisor')
                 LIMIT 1"
            );
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                if ($user['status'] !== 'active') {
                    $error = 'Your account is inactive or suspended. Contact HR or your supervisor.';
                } else {
                    // Update last login timestamp
                    $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")
                        ->execute([$user['id']]);

                    establishUserSession($user);
                    header('Location: ' . dashboardForRole($user['role']));
                    exit;
                }
            } else {
                $error = 'Invalid email or password. Please try again.';
            }
        } catch (PDOException $e) {
            error_log('[OHANA EMPLOYEE LOGIN ERROR] ' . $e->getMessage());
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
  <title>Ohana Business Consultancy Inc. - Secure Employee Portal</title>
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
        Return to Ohana Landing Page
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
        SECURE EMPLOYEE PORTAL
      </h2>

      <p class="text-xs text-emerald-100/70 max-w-xl mx-auto leading-relaxed">
        Unified task execution and specialist workstation portal for authorized field personnel, liaison officers, and operations specialists.
      </p>
    </div>
  </div>

  <!-- FLOATING LOGIN CARD CONTAINER -->
  <div class="max-w-md mx-auto -mt-36 px-4 relative z-10 w-full">
    <div class="bg-white rounded-3xl p-8 shadow-2xl border border-gray-100 space-y-6">
      
      <!-- Sub-header Badge -->
      <div class="flex justify-center">
        <span class="inline-flex items-center gap-1.5 bg-[#FAF7F2] text-gray-600 text-[10px] font-bold px-3 py-1 rounded-full uppercase tracking-wider border border-amber-900/5">
          <i class="fa-solid fa-location-dot text-amber-800 text-[10px]"></i>
          OHANA SPECIALIST SUITE
        </span>
      </div>

      <!-- ACTIVE SESSION NOTICE -->
      <?php
        $employeeSession   = portalUser('employee');
        $supervisorSession = portalUser('supervisor');
      ?>
      <?php if ($employeeSession): ?>
      <div class="bg-emerald-50 border border-emerald-200 rounded-2xl p-4 flex items-center justify-between text-xs text-emerald-900 shadow-sm">
        <div class="space-y-0.5">
          <p class="font-bold flex items-center gap-1.5 text-emerald-800">
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
            Employee signed in: <?= htmlspecialchars($employeeSession['full_name']) ?>
          </p>
          <p class="text-[11px] text-emerald-700">This employee session stays active if you also sign in as a supervisor in another tab.</p>
        </div>
        <a href="employee_dashboard.php" class="bg-[#2D5A43] hover:bg-[#234734] text-white px-3 py-1.5 rounded-xl font-bold text-[11px] transition shadow-xs shrink-0 ml-3">
          Dashboard →
        </a>
      </div>
      <?php endif; ?>
      <?php if ($supervisorSession): ?>
      <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4 flex items-center justify-between text-xs text-amber-900 shadow-sm">
        <div class="space-y-0.5">
          <p class="font-bold flex items-center gap-1.5 text-amber-800">
            <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
            Supervisor signed in: <?= htmlspecialchars($supervisorSession['full_name']) ?>
          </p>
          <p class="text-[11px] text-amber-800">This supervisor session stays active if you also sign in as an employee in another tab.</p>
        </div>
        <a href="supervisor_dashboard.php" class="bg-[#2D5A43] hover:bg-[#234734] text-white px-3 py-1.5 rounded-xl font-bold text-[11px] transition shadow-xs shrink-0 ml-3">
          Dashboard →
        </a>
      </div>
      <?php endif; ?>

      <!-- ERROR ALERT -->
      <?php if ($error): ?>
      <div class="bg-red-50 border border-red-200 rounded-xl p-3 flex items-center gap-2 text-xs text-red-700">
        <i class="fa-solid fa-circle-exclamation text-red-500"></i>
        <span><?= htmlspecialchars($error) ?></span>
      </div>
      <?php endif; ?>

      <!-- Form Inputs -->
      <form action="employee_login.php" method="POST" class="space-y-4">
        
        <!-- Email Input -->
        <div class="space-y-1.5">
          <label class="text-[10px] font-bold text-gray-500 uppercase tracking-wider block">CORPORATE EMAIL ADDRESS</label>
          <div class="relative">
            <i class="fa-regular fa-envelope absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
            <input
              type="email"
              name="email"
              value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
              placeholder="yourname@ohanabiz.com"
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
              id="empPasswordField"
              required
              autocomplete="current-password"
              placeholder="Enter your password"
              class="w-full bg-[#FAF7F2] border border-gray-200/80 rounded-2xl pl-11 pr-10 py-3 text-xs text-gray-700 font-medium focus:outline-none focus:ring-2 focus:ring-[#2D5A43]/20 focus:border-[#2D5A43]"
            >
            <button type="button" onclick="toggleEmpPassword()" class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 text-xs cursor-pointer hover:text-gray-600">
              <i class="fa-regular fa-eye" id="empEyeIcon"></i>
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
          <i class="fa-solid fa-right-to-bracket"></i> Login to Employee Portal
        </button>
      </form>

      <!-- Restricted Access Notice -->
      <div class="pt-2 text-center border-t border-gray-100">
        <p class="text-[10px] text-gray-400 leading-relaxed max-w-xs mx-auto">
          <i class="fa-solid fa-shield-halved text-[10px] mr-1"></i>
          Restricted portal for authorized Ohana specialists and operations staff. Access is logged for continuous audit.
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
    function toggleEmpPassword() {
      const field = document.getElementById('empPasswordField');
      const icon  = document.getElementById('empEyeIcon');
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