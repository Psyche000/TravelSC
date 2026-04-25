<?php
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/db.php';

// Already logged in? Go to user page
if (is_logged_in()) redirect('user.php');

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // --- Collect & sanitise inputs (htmlspecialchars used for display safety;
    //     PDO prepared statements handle DB safety) ---
    $username  = trim($_POST['username']  ?? '');
    $email     = trim($_POST['email']     ?? '');
    $password  = $_POST['password']       ?? '';
    $password2 = $_POST['password2']      ?? '';

    // --- Basic validation ---
    if ($username === '' || $email === '' || $password === '') {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== $password2) {
        $error = 'Passwords do not match.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username)) {
        $error = 'Username must be 3–50 characters: letters, numbers, underscore only.';
    } else {
        $db = get_db();

        // Check uniqueness — prepared statement, no SQL injection possible
        $stmt = $db->prepare('SELECT id FROM users WHERE email = ? OR username = ? LIMIT 1');
        $stmt->execute([$email, $username]);

        if ($stmt->fetch()) {
            $error = 'That email or username is already registered.';
        } else {
            // Hash the password with bcrypt (PHP default, cost 12)
            $hashed = password_hash($password, PASSWORD_DEFAULT, ['cost' => 12]);

            $stmt = $db->prepare(
                'INSERT INTO users (email, username, password) VALUES (?, ?, ?)'
            );
            $stmt->execute([$email, $username, $hashed]);

            // Log the new user in immediately (credentials step done)
            $new_id = (int)$db->lastInsertId();
            $_SESSION['user_id']          = $new_id;
            $_SESSION['username']         = $username;
            $_SESSION['email']            = $email;
            $_SESSION['is_admin']         = 0;
            $_SESSION['twofa_enabled']    = 0;
            $_SESSION['logged_in']        = true;   // No 2FA enabled yet, full login

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
<title>Register | TravelSc</title>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<script id="tailwind-config">
tailwind.config = {
  darkMode:"class",
  theme:{extend:{
    colors:{
      "primary":"#001e40","primary-container":"#003366","secondary":"#0058bc",
      "secondary-container":"#0070eb","tertiary-container":"#611b00",
      "on-primary":"#ffffff","on-primary-container":"#799dd6",
      "on-secondary":"#ffffff","on-tertiary-container":"#f47749",
      "surface":"#f7f9fb","surface-container-low":"#f2f4f6",
      "surface-container":"#eceef0","surface-container-high":"#e6e8ea",
      "surface-container-highest":"#e0e3e5","surface-container-lowest":"#ffffff",
      "on-surface":"#191c1e","on-surface-variant":"#43474f",
      "outline":"#737780","outline-variant":"#c3c6d1",
      "error":"#ba1a1a","error-container":"#ffdad6","on-error-container":"#93000a"
    },
    fontFamily:{"headline":["Manrope"],"body":["Inter"],"label":["Inter"]}
  }}
}
</script>
<style>
.material-symbols-outlined{font-variation-settings:'FILL' 0,'wght' 400,'GRAD' 0,'opsz' 24}
</style>
</head>
<body class="bg-surface font-body text-on-surface flex flex-col min-h-screen">
<main class="flex-grow grid md:grid-cols-2 min-h-screen">

  <!-- Left decorative panel -->
  <div class="hidden md:flex flex-col justify-between p-12 bg-primary relative overflow-hidden">
    <div class="absolute inset-0 opacity-40 bg-gradient-to-br from-primary via-primary-container to-secondary"></div>
    <img alt="Wing above clouds" class="absolute inset-0 w-full h-full object-cover mix-blend-overlay"
         src="https://lh3.googleusercontent.com/aida-public/AB6AXuCQScYXrYPYubcW1DNMjEckvpWY9x7xpVtHkPVY0RblWP-zvtewsk3EJx8kEH4ixlUAP4dMWvIYyDHKvzjoMjcRDnyhh93vWZc0WJacV_0B55xXFBf9vh6raUTXvGxhzirAC01yOvzOxBmjZ_xKBQk-xVLpHm54JpWKyMFQc9sDEh_1Ev-9J9JMMnbNk3vxVnfjcgSgJbL40RH7bjebDbJEZU48co2lqOQqfNlK42qgBBeDf9hCgIsIYZIQH0Dcb4lJ1W8GUfu1YDw"/>
    <div class="relative z-10">
      <h1 class="text-3xl font-headline font-extrabold tracking-tighter text-white mb-1">TravelSc</h1>
      <p class="text-on-primary-container font-headline text-lg italic opacity-90">The Precise Horizon</p>
    </div>
    <div class="relative z-10 max-w-md">
      <h2 class="text-4xl font-headline font-bold text-white tracking-tight mb-6">Your premium journey starts here.</h2>
      <div class="flex flex-col gap-6">
        <div class="flex items-start gap-4">
          <div class="mt-1 w-8 h-8 rounded-full bg-secondary-container flex items-center justify-center">
            <span class="material-symbols-outlined text-white text-sm">verified_user</span>
          </div>
          <div>
            <p class="text-white font-semibold">Priority Boarding</p>
            <p class="text-on-primary-container text-sm">Members get exclusive access to early bookings and premium cabin upgrades.</p>
          </div>
        </div>
        <div class="flex items-start gap-4">
          <div class="mt-1 w-8 h-8 rounded-full bg-secondary-container flex items-center justify-center">
            <span class="material-symbols-outlined text-white text-sm">loyalty</span>
          </div>
          <div>
            <p class="text-white font-semibold">Horizon Rewards</p>
            <p class="text-on-primary-container text-sm">Earn double miles on every international booking within the SkyTeam network.</p>
          </div>
        </div>
      </div>
    </div>
    <div class="relative z-10 flex items-center gap-2">
      <span class="text-xs font-label uppercase tracking-widest text-on-primary-container">Secure Cloud Gateway 256-bit Encrypted</span>
      <span class="material-symbols-outlined text-on-primary-container text-sm">lock</span>
    </div>
  </div>

  <!-- Right form panel -->
  <div class="flex items-center justify-center p-6 md:p-12 lg:p-24 bg-surface">
    <div class="w-full max-w-md">
      <div class="mb-10">
        <h2 class="text-3xl font-headline font-extrabold text-primary tracking-tight mb-2">Create Account</h2>
        <p class="text-outline font-medium">Experience the art of travel, refined.</p>
      </div>

      <?php if ($error): ?>
      <div class="mb-6 p-4 bg-error-container text-on-error-container rounded-xl text-sm font-medium flex items-center gap-3">
        <span class="material-symbols-outlined text-sm">error</span>
        <?= htmlspecialchars($error) ?>
      </div>
      <?php endif; ?>

      <form method="POST" action="register.php" class="space-y-5" novalidate>

        <div class="space-y-2">
          <label class="text-xs font-label font-bold text-primary uppercase tracking-wider px-1">Username</label>
          <div class="relative group">
            <span class="absolute left-4 top-1/2 -translate-y-1/2 material-symbols-outlined text-outline group-focus-within:text-secondary transition-colors">person</span>
            <input name="username" type="text" required autocomplete="username"
                   value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                   class="w-full pl-12 pr-4 py-4 bg-surface-container-high rounded-xl border-none focus:ring-2 focus:ring-secondary/30 focus:bg-surface-container-lowest transition-all duration-300 placeholder:text-outline/50"
                   placeholder="voyager_handle"/>
          </div>
        </div>

        <div class="space-y-2">
          <label class="text-xs font-label font-bold text-primary uppercase tracking-wider px-1">Email Address</label>
          <div class="relative group">
            <span class="absolute left-4 top-1/2 -translate-y-1/2 material-symbols-outlined text-outline group-focus-within:text-secondary transition-colors">mail</span>
            <input name="email" type="email" required autocomplete="email"
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                   class="w-full pl-12 pr-4 py-4 bg-surface-container-high rounded-xl border-none focus:ring-2 focus:ring-secondary/30 focus:bg-surface-container-lowest transition-all duration-300 placeholder:text-outline/50"
                   placeholder="you@horizon.com"/>
          </div>
        </div>

        <div class="space-y-2">
          <label class="text-xs font-label font-bold text-primary uppercase tracking-wider px-1">Password <span class="text-outline font-normal normal-case">(min 8 chars)</span></label>
          <div class="relative group">
            <span class="absolute left-4 top-1/2 -translate-y-1/2 material-symbols-outlined text-outline group-focus-within:text-secondary transition-colors">lock</span>
            <input name="password" type="password" required autocomplete="new-password"
                   class="w-full pl-12 pr-4 py-4 bg-surface-container-high rounded-xl border-none focus:ring-2 focus:ring-secondary/30 focus:bg-surface-container-lowest transition-all duration-300 placeholder:text-outline/50"
                   placeholder="••••••••••••"/>
          </div>
        </div>

        <div class="space-y-2">
          <label class="text-xs font-label font-bold text-primary uppercase tracking-wider px-1">Confirm Password</label>
          <div class="relative group">
            <span class="absolute left-4 top-1/2 -translate-y-1/2 material-symbols-outlined text-outline group-focus-within:text-secondary transition-colors">shield</span>
            <input name="password2" type="password" required autocomplete="new-password"
                   class="w-full pl-12 pr-4 py-4 bg-surface-container-high rounded-xl border-none focus:ring-2 focus:ring-secondary/30 focus:bg-surface-container-lowest transition-all duration-300 placeholder:text-outline/50"
                   placeholder="••••••••••••"/>
          </div>
        </div>

        <div class="pt-4">
          <button type="submit"
                  class="w-full bg-primary text-white font-headline font-bold py-4 px-8 rounded-full shadow-lg hover:-translate-y-1 transition-all duration-300 active:scale-95 flex items-center justify-center gap-2">
            Create Account
            <span class="material-symbols-outlined text-sm">east</span>
          </button>
        </div>
      </form>

      <!-- Divider -->
      <div class="relative my-6">
        <div class="absolute inset-0 flex items-center">
          <div class="w-full border-t border-outline-variant/30"></div>
        </div>
        <div class="relative flex justify-center">
          <span class="bg-surface px-4 text-xs text-outline uppercase tracking-widest font-semibold">or sign up with</span>
        </div>
      </div>

      <!-- Google Sign-Up -->
      <a href="google_auth.php?action=redirect"
         class="w-full flex items-center justify-center gap-3 border-2 border-outline-variant/40 rounded-full py-4 font-headline font-bold text-on-surface hover:border-secondary/40 hover:bg-surface-container-low hover:translate-y-[-2px] transition-all duration-300">
        <svg class="w-5 h-5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
          <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
          <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
          <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
          <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
        </svg>
        <span>Continue with Google</span>
      </a>

      <div class="mt-6 pt-6 border-t border-outline-variant/20 text-center">
        <p class="text-sm text-outline">Already a voyager? <a class="text-secondary font-bold hover:underline" href="login.php">Sign In</a></p>
      </div>
    </div>
  </div>
</main>
<footer class="w-full py-8 px-8 bg-primary">
  <div class="max-w-7xl mx-auto flex flex-col md:flex-row justify-between items-center gap-4">
    <p class="font-inter text-xs tracking-wide uppercase text-on-primary-container/70">© 2026 TravelSc. Member of SkyTeam.</p>
    <div class="flex gap-6">
      <a class="font-inter text-xs tracking-wide uppercase text-on-primary-container/70 hover:text-white transition-opacity" href="#">Privacy Policy</a>
      <a class="font-inter text-xs tracking-wide uppercase text-on-primary-container/70 hover:text-white transition-opacity" href="#">Terms of Service</a>
    </div>
  </div>
</footer>
</body>
</html>
