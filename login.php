<?php
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/db.php';

if (is_logged_in()) redirect('user.php');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = $_POST['password']      ?? '';

    if ($email === '' || $password === '') {
        $error = 'Please fill in both fields.';
    } else {
        $db = get_db();

        // Fetch user by email — prepared statement prevents SQL injection
        $stmt = $db->prepare('SELECT id, username, password, isAdmin, twofa_enabled, twofa_secret FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // password_verify compares against the bcrypt hash stored in the DB
        if (!$user || !password_verify($password, $user['password'])) {
            $error = 'Invalid email or password.';
        } else {
            // Credentials are correct
            if ($user['twofa_enabled']) {
                // Store a pending session — NOT fully logged in yet
                $_SESSION['pending_user_id']     = (int)$user['id'];
                $_SESSION['pending_username']    = $user['username'];
                $_SESSION['pending_email']       = $email;
                $_SESSION['pending_is_admin']    = (int)$user['isAdmin'];
                $_SESSION['pending_twofa_secret']= $user['twofa_secret'];
                redirect('2fa.php');
            } else {
                // No 2FA — fully log in
                $_SESSION['user_id']       = (int)$user['id'];
                $_SESSION['username']      = $user['username'];
                $_SESSION['email']         = $email;
                $_SESSION['is_admin']      = (int)$user['isAdmin'];
                $_SESSION['twofa_enabled'] = 0;
                $_SESSION['logged_in']     = true;

                if ($user['isAdmin']) {
                    redirect('dashboard.php');
                } else {
                    redirect('user.php');
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Sign In - TravelSc</title>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<script id="tailwind-config">
tailwind.config={darkMode:"class",theme:{extend:{colors:{
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
},fontFamily:{"headline":["Manrope"],"body":["Inter"]}}}}
</script>
<style>
.material-symbols-outlined{font-variation-settings:'FILL' 0,'wght' 400,'GRAD' 0,'opsz' 24}
.bg-glass{background-color:rgba(255,255,255,0.7);backdrop-filter:blur(24px)}
</style>
</head>
<body class="bg-surface font-body text-on-surface min-h-screen flex flex-col">
<main class="flex-grow flex items-center justify-center relative overflow-hidden px-6 py-12">
  <div class="absolute inset-0 z-0">
    <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,_var(--tw-gradient-stops))] from-blue-100/30 via-surface to-surface"></div>
    <div class="absolute top-[-10%] right-[-10%] w-[40%] h-[40%] bg-primary/5 rounded-full blur-[120px]"></div>
    <div class="absolute bottom-[-5%] left-[-5%] w-[30%] h-[30%] bg-secondary/5 rounded-full blur-[100px]"></div>
  </div>

  <div class="relative z-10 w-full max-w-lg">
    <div class="text-center mb-10">
      <h1 class="text-3xl font-headline font-extrabold tracking-tighter text-primary mb-2">TravelSc</h1>
      <p class="text-on-surface-variant font-body text-sm tracking-wide uppercase">The Precise Horizon</p>
    </div>

    <div class="bg-surface-container-lowest rounded-xl p-8 md:p-12 shadow-[0_8px_32px_rgba(25,28,30,0.06)] border border-outline-variant/20">
      <div class="mb-8">
        <h2 class="text-2xl font-headline font-bold text-primary mb-2">Welcome Back</h2>
        <p class="text-on-surface-variant text-sm">Access your global travel dashboard.</p>
      </div>

      <?php if ($error): ?>
      <div class="mb-6 p-4 bg-error-container text-on-error-container rounded-xl text-sm font-medium flex items-center gap-3">
        <span class="material-symbols-outlined text-sm">error</span>
        <?= htmlspecialchars($error) ?>
      </div>
      <?php endif; ?>

      <form method="POST" action="login.php" class="space-y-6" novalidate>
        <div class="space-y-2">
          <label class="block text-xs font-label font-semibold text-primary tracking-wider uppercase ml-1" for="email">Email Address</label>
          <input id="email" name="email" type="email" required autocomplete="email"
                 value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                 class="w-full h-14 px-5 bg-surface-container-high rounded-xl border-none ring-0 focus:ring-2 focus:ring-primary/20 focus:bg-surface-container-lowest transition-all duration-300 text-on-surface placeholder:text-outline"
                 placeholder="name@voyager.com"/>
        </div>

        <div class="space-y-2">
          <div class="flex justify-between items-center px-1">
            <label class="block text-xs font-label font-semibold text-primary tracking-wider uppercase" for="password">Password</label>
          </div>
          <input id="password" name="password" type="password" required autocomplete="current-password"
                 class="w-full h-14 px-5 bg-surface-container-high rounded-xl border-none ring-0 focus:ring-2 focus:ring-primary/20 focus:bg-surface-container-lowest transition-all duration-300 text-on-surface placeholder:text-outline"
                 placeholder="••••••••"/>
        </div>

        <button type="submit"
                class="w-full h-14 bg-gradient-to-br from-primary-container to-primary text-on-primary rounded-full font-headline font-bold text-base hover:translate-y-[-2px] hover:shadow-lg hover:shadow-primary/20 active:scale-95 transition-all duration-300">
          Sign In
        </button>
      </form>

      <!-- Divider -->
      <div class="relative my-8">
        <div class="absolute inset-0 flex items-center">
          <div class="w-full border-t border-outline-variant/30"></div>
        </div>
        <div class="relative flex justify-center">
          <span class="bg-surface-container-lowest px-4 text-xs text-outline uppercase tracking-widest font-semibold">or continue with</span>
        </div>
      </div>

      <!-- Google Sign-In -->
      <a href="google_auth.php?action=redirect"
         class="w-full h-14 flex items-center justify-center gap-3 border-2 border-outline-variant/40 rounded-full font-headline font-bold text-on-surface hover:border-secondary/40 hover:bg-surface-container-low hover:translate-y-[-2px] transition-all duration-300 group">
        <svg class="w-5 h-5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
          <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
          <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
          <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
          <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
        </svg>
        <span>Sign in with Google</span>
      </a>

      <div class="mt-8 text-center">
        <p class="text-sm text-on-surface-variant font-medium">
          New to the Voyager experience?
          <a class="text-secondary font-bold hover:underline ml-1" href="register.php">Register</a>
        </p>
      </div>
    </div>

    <div class="mt-8 text-center">
      <p class="text-[10px] font-label font-semibold text-outline tracking-[0.2em] uppercase">© 2026 TravelSc. Member of SkyTeam.</p>
    </div>
  </div>
</main>
<footer class="w-full py-8 px-8 bg-blue-950">
  <div class="max-w-7xl mx-auto flex flex-col md:flex-row justify-between items-center gap-4">
    <p class="font-inter text-xs tracking-wide uppercase text-blue-300/50">© 2026 TravelSc. Member of SkyTeam.</p>
    <div class="flex gap-6">
      <a class="font-inter text-xs tracking-wide uppercase text-blue-300/70 hover:text-white transition-opacity" href="#">Privacy Policy</a>
      <a class="font-inter text-xs tracking-wide uppercase text-blue-300/70 hover:text-white transition-opacity" href="#">Terms of Service</a>
    </div>
  </div>
</footer>
</body>
</html>
