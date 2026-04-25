<?php
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/db.php';

require_admin();   // Redirects to user.php if not admin

$db = get_db();

// Fetch all users
$stmt = $db->prepare('SELECT id, username, email, isAdmin, twofa_enabled, created_at FROM users ORDER BY created_at DESC');
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_users = count($users);

// PHP 7.3 compatible counts
$admin_count = count(array_filter($users, function($u) {
    return !empty($u['isAdmin']);
}));

$twofa_count = count(array_filter($users, function($u) {
    return !empty($u['twofa_enabled']);
}));
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>TravelSc - Admin Dashboard</title>

<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>

<script>
tailwind.config={darkMode:"class",theme:{extend:{colors:{
  "primary":"#001e40","primary-container":"#003366","secondary":"#0058bc",
  "secondary-container":"#0070eb","tertiary-container":"#611b00",
  "on-primary":"#ffffff","on-primary-container":"#799dd6",
  "on-secondary":"#ffffff","on-secondary-container":"#fefcff","on-tertiary-container":"#f47749",
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
body{font-family:'Inter',sans-serif}
h1,h2,h3{font-family:'Manrope',sans-serif}
</style>
</head>

<body class="bg-surface text-on-surface">

<!-- Side Nav -->
<aside class="h-screen w-64 fixed left-0 top-0 border-r border-slate-200/20 bg-slate-100 flex flex-col py-6 gap-2 z-50 text-sm font-medium text-blue-900">
  <div class="px-4 mb-8">
    <h1 class="text-lg font-black text-blue-950 uppercase tracking-widest">TravelSc Admin</h1>
    <p class="text-xs text-slate-500 mt-1">The Precise Horizon</p>
  </div>

  <nav class="flex-1 space-y-1 px-3">
    <a href="dashboard.php" class="flex items-center gap-3 px-4 py-3 bg-white text-blue-900 rounded-lg shadow-sm font-bold">
      <span class="material-symbols-outlined">dashboard</span><span>Dashboard</span>
    </a>
    <a href="user_management.php" class="flex items-center gap-3 px-4 py-3 text-slate-500 hover:bg-slate-200/50 hover:pl-6 transition-all duration-300 rounded-lg">
      <span class="material-symbols-outlined">group</span><span>User Management</span>
    </a>
  </nav>

  <div class="px-4 mt-auto space-y-2">
    <hr class="border-slate-200/50"/>
    <a href="user.php" class="flex items-center gap-3 px-4 py-2 text-slate-500 hover:text-blue-900 transition-colors">
      <span class="material-symbols-outlined">person</span><span>My Profile</span>
    </a>
    <a href="logout.php" class="flex items-center gap-3 px-4 py-2 text-error hover:opacity-80 transition-opacity">
      <span class="material-symbols-outlined">logout</span><span>Logout</span>
    </a>
  </div>
</aside>

<!-- Main -->
<main class="ml-64 min-h-screen">

<header class="h-24 flex items-center justify-between px-10 sticky top-0 bg-surface/80 backdrop-blur-xl border-b border-outline-variant/10">
  <div>
    <h2 class="text-3xl font-bold text-primary">Operational Intelligence</h2>
    <p class="text-on-surface-variant text-sm">
      Welcome, <?= htmlspecialchars($_SESSION['username'] ?? 'Guest') ?>
    </p>
  </div>
</header>

<!-- Stats -->
<section class="px-10 py-6 grid grid-cols-1 md:grid-cols-3 gap-6">

<div class="bg-white p-6 rounded-xl border">
  <p class="text-xs uppercase">Total Users</p>
  <h3 class="text-2xl font-bold"><?= $total_users ?></h3>
</div>

<div class="bg-white p-6 rounded-xl border">
  <p class="text-xs uppercase">Admins</p>
  <h3 class="text-2xl font-bold"><?= $admin_count ?></h3>
</div>

<div class="bg-white p-6 rounded-xl border">
  <p class="text-xs uppercase">2FA Enabled</p>
  <h3 class="text-2xl font-bold"><?= $twofa_count ?></h3>
</div>

</section>

<!-- Table -->
<section class="px-10 py-6">

<div class="bg-white rounded-xl border overflow-hidden">
<table class="w-full text-left">

<thead class="bg-gray-100 text-xs uppercase">
<tr>
<th class="px-4 py-3">ID</th>
<th class="px-4 py-3">Username</th>
<th class="px-4 py-3">Email</th>
<th class="px-4 py-3">Role</th>
<th class="px-4 py-3">2FA</th>
<th class="px-4 py-3">Joined</th>
</tr>
</thead>

<tbody>
<?php foreach ($users as $u): ?>
<tr class="border-t">

<td class="px-4 py-3">#<?= $u['id'] ?></td>

<td class="px-4 py-3">
<?= htmlspecialchars($u['username']) ?>
</td>

<td class="px-4 py-3">
<?= htmlspecialchars($u['email']) ?>
</td>

<td class="px-4 py-3">
<?= !empty($u['isAdmin']) ? 'Admin' : 'Customer' ?>
</td>

<td class="px-4 py-3">
<?= !empty($u['twofa_enabled']) ? 'Enabled' : 'Off' ?>
</td>

<td class="px-4 py-3">
<?= htmlspecialchars($u['created_at']) ?>
</td>

</tr>
<?php endforeach; ?>
</tbody>

</table>
</div>

</section>

</main>
</body>
</html>