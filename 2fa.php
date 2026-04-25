<?php
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/totp.php';

// Must have a pending session from login.php
if (!isset($_SESSION['pending_user_id'])) {
    redirect('login.php');
}
if (is_logged_in()) redirect('user.php');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code   = trim($_POST['code'] ?? '');
    $secret = $_SESSION['pending_twofa_secret'] ?? '';

    if (strlen($code) !== 6 || !ctype_digit($code)) {
        $error = 'Please enter the 6-digit code from your authenticator app.';
    } elseif (!TOTP::verify($secret, $code)) {
        $error = 'Incorrect code. Please try again.';
    } else {
        // Code is valid — promote pending session to a full session
        $_SESSION['user_id']       = $_SESSION['pending_user_id'];
        $_SESSION['username']      = $_SESSION['pending_username'];
        $_SESSION['email']         = $_SESSION['pending_email'];
        $_SESSION['is_admin']      = $_SESSION['pending_is_admin'];
        $_SESSION['twofa_enabled'] = 1;
        $_SESSION['logged_in']     = true;

        // Clean up pending keys
        unset(
            $_SESSION['pending_user_id'],
            $_SESSION['pending_username'],
            $_SESSION['pending_email'],
            $_SESSION['pending_is_admin'],
            $_SESSION['pending_twofa_secret']
        );

        if ($_SESSION['is_admin']) {
            redirect('dashboard.php');
        } else {
            redirect('user.php');
        }
    }
}
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>TravelSc - Secure Verification</title>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<script id="tailwind-config">
tailwind.config={darkMode:"class",theme:{extend:{colors:{
  "primary":"#001e40","primary-container":"#003366","secondary":"#0058bc",
  "secondary-container":"#0070eb","tertiary-container":"#611b00",
  "on-primary":"#ffffff","on-tertiary-container":"#f47749",
  "surface":"#f7f9fb","surface-container-low":"#f2f4f6",
  "surface-container":"#eceef0","surface-container-high":"#e6e8ea",
  "surface-container-lowest":"#ffffff",
  "on-surface":"#191c1e","on-surface-variant":"#43474f",
  "outline":"#737780","outline-variant":"#c3c6d1",
  "error":"#ba1a1a","error-container":"#ffdad6","on-error-container":"#93000a"
},fontFamily:{"headline":["Manrope"],"body":["Inter"]}}}}
</script>
<style>
.material-symbols-outlined{font-variation-settings:'FILL' 0,'wght' 400,'GRAD' 0,'opsz' 24}
.otp-input:focus{background-color:#ffffff;border:1px solid rgba(0,30,64,0.3);outline:none}
</style>
</head>
<body class="bg-surface font-body text-on-surface min-h-screen flex flex-col">
<main class="flex-grow flex flex-col items-center justify-center px-4 relative overflow-hidden">
  <div class="absolute inset-0 z-0 pointer-events-none opacity-40">
    <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] bg-primary-container rounded-full blur-[120px]"></div>
    <div class="absolute bottom-[-10%] right-[-10%] w-[30%] h-[30%] bg-secondary-container rounded-full blur-[100px]"></div>
  </div>

  <div class="w-full max-w-md z-10">
    <div class="text-center mb-10">
      <h1 class="text-3xl font-headline font-extrabold tracking-tighter text-primary">TravelSc</h1>
      <p class="text-on-surface-variant mt-2 font-label text-sm tracking-wide uppercase">The Precise Horizon</p>
    </div>

    <div class="bg-surface-container-lowest rounded-xl p-8 md:p-12 shadow-[0_8px_24px_rgba(25,28,30,0.06)] border border-outline-variant/10">
      <div class="flex justify-center mb-8">
        <div class="w-16 h-16 bg-surface-container-low rounded-full flex items-center justify-center">
          <span class="material-symbols-outlined text-secondary text-3xl">shield_person</span>
        </div>
      </div>

      <div class="text-center mb-10">
        <h2 class="text-2xl font-headline font-bold text-primary mb-3">Verification Required</h2>
        <p class="text-on-surface-variant text-sm leading-relaxed">
          Enter the 6-digit code from your authenticator app to complete sign-in.
        </p>
      </div>

      <?php if ($error): ?>
      <div class="mb-6 p-4 bg-error-container text-on-error-container rounded-xl text-sm font-medium flex items-center gap-3">
        <span class="material-symbols-outlined text-sm">error</span>
        <?= htmlspecialchars($error) ?>
      </div>
      <?php endif; ?>

      <form method="POST" action="2fa.php" novalidate>
        <!-- Single text input for the 6-digit code -->
        <div class="mb-10">
          <label class="block text-xs font-label font-semibold text-primary tracking-wider uppercase text-center mb-4">Authenticator Code</label>
          <input name="code" type="text" inputmode="numeric" pattern="\d{6}" maxlength="6" required autocomplete="one-time-code"
                 class="otp-input w-full h-16 text-center text-3xl font-bold tracking-[0.5em] rounded-lg bg-surface-container-high border-none focus:ring-0 transition-all duration-200"
                 placeholder="000000"/>
        </div>

        <button type="submit"
                class="w-full bg-tertiary-container text-on-tertiary-container font-headline font-bold py-4 rounded-full shadow-sm hover:translate-y-[-4px] active:scale-95 transition-all duration-300">
          Verify Identity
        </button>
      </form>

      <div class="mt-8 text-center pt-6 border-t border-outline-variant/10">
        <a href="login.php"
           class="text-on-surface-variant font-label text-xs hover:text-primary transition-colors uppercase tracking-widest">
          Cancel &amp; Sign Out
        </a>
      </div>
    </div>

    <div class="mt-8 flex items-center justify-center gap-6 opacity-60 grayscale">
      <div class="flex items-center gap-1">
        <span class="material-symbols-outlined text-base" style="font-variation-settings:'FILL' 1">lock</span>
        <span class="text-[10px] uppercase font-bold tracking-tighter">AES-256 Encrypted</span>
      </div>
      <div class="flex items-center gap-1">
        <span class="material-symbols-outlined text-base" style="font-variation-settings:'FILL' 1">verified_user</span>
        <span class="text-[10px] uppercase font-bold tracking-tighter">SkyTeam Verified</span>
      </div>
    </div>
  </div>
</main>
<footer class="w-full py-8 px-8 bg-surface-container-low mt-auto">
  <div class="max-w-7xl mx-auto flex flex-col md:flex-row justify-between items-center gap-4">
    <p class="font-inter text-[10px] tracking-wide uppercase text-on-surface-variant">© 2026 TravelSc. Member of SkyTeam.</p>
    <div class="flex gap-6">
      <a class="font-inter text-[10px] tracking-wide uppercase text-on-surface-variant hover:text-primary transition-colors" href="#">Privacy Policy</a>
      <a class="font-inter text-[10px] tracking-wide uppercase text-on-surface-variant hover:text-primary transition-colors" href="#">Terms of Service</a>
    </div>
  </div>
</footer>
</body>
</html>
