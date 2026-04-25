<?php
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/db.php';

require_login();

$db      = get_db();
$user_id = $_SESSION['user_id'];

// Fetch fresh user data from DB
$stmt = $db->prepare('SELECT username, email, isAdmin, twofa_enabled FROM users WHERE id = ? LIMIT 1');
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    // User was deleted while logged in — kill session
    session_destroy();
    redirect('login.php');
}
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>User Profile | TravelSc</title>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet"/>
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
  "error":"#ba1a1a"
},fontFamily:{"headline":["Manrope"],"body":["Inter"]}}}}
</script>
<style>.material-symbols-outlined{font-variation-settings:'FILL' 0,'wght' 400,'GRAD' 0,'opsz' 24}</style>
</head>
<body class="bg-surface font-body text-on-surface overflow-x-hidden">

<!-- Top Nav -->
<nav class="fixed top-0 w-full z-50 bg-white/70 backdrop-blur-xl flex justify-between items-center px-8 h-20 border-b border-outline-variant/10">
  <span class="text-2xl font-headline font-black text-primary tracking-tighter">TravelSc</span>
  <div class="hidden md:flex gap-8">
    <a href="home.html" class="text-on-surface hover:text-secondary font-headline font-bold tracking-tight transition-all">Flights</a>
    <a href="#" class="text-on-surface hover:text-secondary font-headline font-bold tracking-tight transition-all">Stays</a>
  </div>
  <div class="flex items-center gap-4">
    <?php if ($user['isAdmin']): ?>
    <a href="dashboard.php" class="text-xs font-bold uppercase tracking-widest text-secondary hover:underline">Admin</a>
    <?php endif; ?>
    <a href="logout.php" class="flex items-center gap-2 text-error font-semibold hover:opacity-80 text-sm transition-opacity">
      <span class="material-symbols-outlined text-sm">logout</span> Sign Out
    </a>
  </div>
</nav>

<div class="flex pt-20 min-h-screen max-w-[1440px] mx-auto">
  <!-- Side Nav -->
  <aside class="fixed left-0 top-0 h-full flex-col py-8 w-72 bg-surface-container-low border-r border-outline-variant/20 pt-28 hidden md:flex">
    <div class="px-8 mb-10">
      <h2 class="font-headline font-extrabold text-primary text-xl">Account</h2>
      <p class="text-on-surface-variant text-xs font-medium tracking-wide">Manage your journey</p>
    </div>
    <nav class="flex-1 space-y-1">
      <a href="user.php" class="flex items-center gap-4 px-8 py-4 bg-white text-primary rounded-r-full shadow-sm font-medium tracking-wide">
        <span class="material-symbols-outlined">person</span><span>Profile</span>
      </a>
      <a href="2fa_setup.php" class="flex items-center gap-4 px-8 py-4 text-on-surface-variant hover:bg-surface-container hover:translate-x-2 transition-transform duration-200">
        <span class="material-symbols-outlined">security</span><span>Security / 2FA</span>
      </a>
    </nav>
    <div class="mt-auto px-4 space-y-1 border-t border-outline-variant/20 pt-6">
      <a href="logout.php" class="flex items-center gap-4 px-8 py-3 text-error hover:bg-error/5 hover:translate-x-2 transition-transform duration-200 rounded-lg">
        <span class="material-symbols-outlined">logout</span><span>Sign Out</span>
      </a>
    </div>
  </aside>

  <!-- Main Content -->
  <main class="flex-1 md:ml-72 p-8 lg:p-16">
    <div class="max-w-4xl mx-auto">

      <!-- Profile Header -->
      <section class="mb-12">
        <div class="bg-surface-container-low p-10 rounded-[2rem] flex flex-col md:flex-row items-center gap-8 relative overflow-hidden">
          <div class="absolute top-0 right-0 w-64 h-64 bg-primary/5 rounded-full -mr-20 -mt-20 blur-3xl"></div>
          <!-- Avatar placeholder -->
          <div class="w-28 h-28 rounded-full bg-primary-container flex items-center justify-center text-on-primary-container text-5xl font-headline font-black shrink-0">
            <?= strtoupper(substr($user['username'], 0, 1)) ?>
          </div>
          <div class="text-center md:text-left z-10">
            <h1 class="text-4xl font-headline font-extrabold tracking-tighter text-primary mb-2">
              <?= htmlspecialchars($user['username']) ?>
            </h1>
            <p class="text-on-surface-variant font-medium flex items-center justify-center md:justify-start gap-2">
              <span class="material-symbols-outlined text-secondary text-lg">verified</span>
              <?= $user['isAdmin'] ? 'Administrator' : 'Voyager Member' ?>
            </p>
          </div>
          <?php if ($user['isAdmin']): ?>
          <div class="md:ml-auto">
            <a href="dashboard.php"
               class="flex items-center gap-2 px-6 py-3 bg-primary text-white rounded-full font-bold hover:translate-y-[-2px] transition-all shadow-lg">
              <span class="material-symbols-outlined text-sm">dashboard</span> Admin Dashboard
            </a>
          </div>
          <?php endif; ?>
        </div>
      </section>

      <!-- Info Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

        <!-- Account Details -->
        <section class="bg-surface-container-lowest p-8 rounded-[1.5rem] shadow-sm space-y-6">
          <h3 class="text-xl font-headline font-bold text-primary">Account Details</h3>
          <div class="space-y-2">
            <label class="text-[10px] font-black uppercase tracking-widest text-outline ml-1">Username</label>
            <div class="bg-surface-container-high px-5 py-4 rounded-xl text-on-surface font-medium">
              <?= htmlspecialchars($user['username']) ?>
            </div>
          </div>
          <div class="space-y-2">
            <label class="text-[10px] font-black uppercase tracking-widest text-outline ml-1">Email Address</label>
            <div class="bg-surface-container-high px-5 py-4 rounded-xl text-on-surface font-medium">
              <?= htmlspecialchars($user['email']) ?>
            </div>
          </div>
          <div class="space-y-2">
            <label class="text-[10px] font-black uppercase tracking-widest text-outline ml-1">Account Role</label>
            <div class="bg-surface-container-high px-5 py-4 rounded-xl text-on-surface font-medium">
              <?= $user['isAdmin'] ? 'Administrator' : 'Customer' ?>
            </div>
          </div>
        </section>

        <!-- Security -->
        <section class="bg-primary text-white p-8 rounded-[1.5rem] shadow-xl relative overflow-hidden">
          <div class="relative z-10">
            <div class="flex items-center gap-4 mb-6">
              <div class="w-12 h-12 bg-white/10 rounded-full flex items-center justify-center backdrop-blur-md">
                <span class="material-symbols-outlined text-white" style="font-variation-settings:'FILL' 1">shield</span>
              </div>
              <div>
                <p class="text-sm font-bold">Account Security</p>
                <p class="text-xs text-white/60">Status: <?= $user['twofa_enabled'] ? 'Protected' : 'Basic' ?></p>
              </div>
            </div>

            <div class="space-y-4">
              <div class="flex items-center justify-between p-4 bg-white/5 rounded-2xl border border-white/10">
                <div>
                  <p class="text-sm font-bold">Two-Factor Auth</p>
                  <p class="text-[10px] text-white/50">
                    <?= $user['twofa_enabled'] ? 'Enabled — your account is protected' : 'Not enabled — recommended' ?>
                  </p>
                </div>
                <!-- Toggle indicator -->
                <div class="w-12 h-6 rounded-full relative flex items-center px-1 <?= $user['twofa_enabled'] ? 'bg-secondary' : 'bg-white/20' ?>">
                  <div class="w-4 h-4 bg-white rounded-full <?= $user['twofa_enabled'] ? 'ml-auto' : '' ?>"></div>
                </div>
              </div>

              <?php if (!$user['twofa_enabled']): ?>
              <a href="2fa_setup.php"
                 class="w-full py-4 bg-on-tertiary-container text-white font-headline font-extrabold rounded-full hover:scale-105 active:scale-95 transition-all shadow-lg flex items-center justify-center gap-2">
                <span class="material-symbols-outlined text-sm">security</span>
                Enable 2FA
              </a>
              <?php else: ?>
              <div class="w-full py-4 bg-white/10 text-white/60 font-headline font-extrabold rounded-full flex items-center justify-center gap-2 cursor-default">
                <span class="material-symbols-outlined text-sm">check_circle</span>
                2FA Active
              </div>
              <?php endif; ?>
            </div>
          </div>
          <div class="absolute bottom-0 right-0 w-32 h-32 bg-secondary/20 rounded-full -mb-10 -mr-10 blur-2xl"></div>
        </section>
      </div>

    </div>
  </main>
</div>

<!-- Mobile bottom nav -->
<nav class="md:hidden fixed bottom-0 left-0 w-full bg-white/90 backdrop-blur-xl border-t border-outline-variant/10 flex justify-around items-center h-20 px-4 z-50">
  <a href="user.php" class="flex flex-col items-center gap-1 text-secondary">
    <span class="material-symbols-outlined" style="font-variation-settings:'FILL' 1">person</span>
    <span class="text-[10px] font-bold">Profile</span>
  </a>
  <a href="2fa_setup.php" class="flex flex-col items-center gap-1 text-outline hover:text-secondary">
    <span class="material-symbols-outlined">security</span>
    <span class="text-[10px] font-bold">Security</span>
  </a>
  <a href="logout.php" class="flex flex-col items-center gap-1 text-error">
    <span class="material-symbols-outlined">logout</span>
    <span class="text-[10px] font-bold">Sign Out</span>
  </a>
</nav>
</body>
</html>
