<?php
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/totp.php';

require_login();

$db      = get_db();
$user_id = $_SESSION['user_id'];
$error   = '';
$success = '';

if (!isset($_SESSION['setup_2fa_secret'])) {
    $_SESSION['setup_2fa_secret'] = TOTP::generate_secret();
}
$secret = $_SESSION['setup_2fa_secret'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['code'] ?? '');

    if (strlen($code) !== 6 || !ctype_digit($code)) {
        $error = 'Please enter the 6-digit code.';
    } elseif (!TOTP::verify($secret, $code)) {
        $error = 'Code is incorrect or expired. Try again.';
    } else {
        
        $stmt = $db->prepare('UPDATE users SET twofa_enabled = 1, twofa_secret = ? WHERE id = ?');
        $stmt->execute([$secret, $user_id]);

        $_SESSION['twofa_enabled'] = 1;
        unset($_SESSION['setup_2fa_secret']);

        $success = '2FA enabled successfully!';
    }
}

// Build QR code URL via a free API (no library needed)
$uri    = TOTP::get_uri($secret, $_SESSION['email']);
$qr_url = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . rawurlencode($uri);
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>TravelSc - Enable 2FA</title>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<script id="tailwind-config">
tailwind.config={darkMode:"class",theme:{extend:{colors:{
  "primary":"#001e40","primary-container":"#003366","secondary":"#0058bc",
  "on-primary":"#ffffff","on-primary-container":"#799dd6",
  "surface":"#f7f9fb","surface-container-low":"#f2f4f6",
  "surface-container-high":"#e6e8ea","surface-container-highest":"#e0e3e5",
  "surface-container-lowest":"#ffffff",
  "on-surface":"#191c1e","on-surface-variant":"#43474f",
  "outline":"#737780","outline-variant":"#c3c6d1",
  "error":"#ba1a1a","error-container":"#ffdad6","on-error-container":"#93000a",
  "tertiary-container":"#611b00","on-tertiary-container":"#f47749"
},fontFamily:{"headline":["Manrope"],"body":["Inter"]}}}}
</script>
<style>.material-symbols-outlined{font-variation-settings:'FILL' 0,'wght' 400,'GRAD' 0,'opsz' 24}</style>
</head>
<body class="bg-surface font-body text-on-surface min-h-screen flex flex-col">
<nav class="fixed top-0 w-full z-50 bg-white/70 backdrop-blur-xl flex justify-between items-center px-8 h-20 border-b border-outline-variant/20">
  <span class="text-2xl font-headline font-extrabold tracking-tighter text-primary">TravelSc</span>
  <a href="user.php" class="flex items-center gap-2 text-secondary font-semibold hover:underline text-sm">
    <span class="material-symbols-outlined text-sm">arrow_back</span> Back to Profile
  </a>
</nav>
<main class="flex-grow flex items-center justify-center px-6 py-12 pt-28">
  <div class="w-full max-w-xl">

    <?php if ($success): ?>
    <div class="mb-8 p-6 bg-green-50 text-green-800 rounded-xl text-sm font-medium flex items-center gap-3 border border-green-200">
      <span class="material-symbols-outlined" style="font-variation-settings:'FILL' 1">check_circle</span>
      <?= htmlspecialchars($success) ?>
      <a href="user.php" class="ml-auto text-secondary font-bold hover:underline">Go to Profile →</a>
    </div>
    <?php else: ?>

    <div class="text-center mb-8">
      <div class="w-16 h-16 bg-primary rounded-2xl flex items-center justify-center text-white mx-auto mb-4">
        <span class="material-symbols-outlined text-3xl" style="font-variation-settings:'FILL' 1">shield_person</span>
      </div>
      <h1 class="text-3xl font-headline font-extrabold text-primary tracking-tight mb-2">Secure Your Account</h1>
      <p class="text-on-surface-variant">Scan the QR code with Google Authenticator or Authy, then enter the code to confirm.</p>
    </div>

    <?php if ($error): ?>
    <div class="mb-6 p-4 bg-error-container text-on-error-container rounded-xl text-sm font-medium flex items-center gap-3">
      <span class="material-symbols-outlined text-sm">error</span>
      <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <!-- QR Code -->
    <div class="bg-surface-container-lowest rounded-3xl p-8 shadow-sm border border-outline-variant/20 mb-6">
      <div class="flex flex-col items-center gap-4 mb-8">
        <div class="bg-white p-4 rounded-2xl border border-outline-variant/20 shadow-sm">
          <img src="<?= htmlspecialchars($qr_url) ?>" alt="2FA QR Code" width="200" height="200"/>
        </div>
        <span class="text-xs font-label uppercase tracking-widest text-outline">Scan with your authenticator app</span>
      </div>

      <!-- Manual entry key -->
      <div class="bg-surface-container-low p-4 rounded-xl mb-6">
        <p class="text-xs font-bold text-primary uppercase tracking-wider mb-2">Can't scan? Enter this key manually:</p>
        <div class="bg-surface-container-lowest border border-outline-variant/20 px-4 py-3 rounded-xl font-mono text-sm tracking-widest text-primary flex items-center justify-between">
          <span><?= htmlspecialchars($secret) ?></span>
          <button onclick="navigator.clipboard.writeText('<?= htmlspecialchars($secret) ?>')"
                  class="ml-2 text-secondary hover:text-primary transition-colors" title="Copy">
            <span class="material-symbols-outlined text-sm">content_copy</span>
          </button>
        </div>
      </div>

      <!-- Verification form -->
      <form method="POST" action="2fa_setup.php" novalidate>
        <label class="block text-xs font-label font-semibold text-primary tracking-wider uppercase mb-3">Enter the 6-digit code to confirm</label>
        <input name="code" type="text" inputmode="numeric" pattern="\d{6}" maxlength="6" required autocomplete="one-time-code"
               class="w-full h-14 text-center text-2xl font-bold tracking-[0.5em] bg-surface-container-high rounded-xl border-none focus:ring-2 focus:ring-primary/20 focus:bg-white transition-all mb-4"
               placeholder="000000"/>
        <button type="submit"
                class="w-full py-4 bg-primary text-on-primary font-headline font-bold rounded-full shadow-lg hover:translate-y-[-2px] transition-all duration-300 active:scale-[0.98]">
          Verify and Enable 2FA
        </button>
      </form>
    </div>

    <div class="text-center">
      <a href="user.php" class="text-sm font-semibold text-on-surface-variant hover:text-primary transition-colors">
        Cancel and return to profile
      </a>
    </div>

    <?php endif; ?>
  </div>
</main>
<footer class="w-full py-8 px-8 bg-surface-container-low mt-auto">
  <p class="text-center font-inter text-[10px] tracking-wide uppercase text-on-surface-variant">© 2026 TravelSc. Member of SkyTeam.</p>
</footer>
</body>
</html>
