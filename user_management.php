<?php
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/db.php';

require_admin();

$db      = get_db();
$message = '';
$error   = '';

// --- Handle actions ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action  = $_POST['action']  ?? '';
    $target  = (int)($_POST['target_id'] ?? 0);

    // Don't let admins act on themselves
    if ($target === (int)$_SESSION['user_id']) {
        $error = 'You cannot perform this action on your own account.';
    } elseif ($action === 'delete' && $target > 0) {
        $stmt = $db->prepare('DELETE FROM users WHERE id = ?');
        $stmt->execute([$target]);
        $message = 'User deleted.';
    } elseif ($action === 'toggle_admin' && $target > 0) {
        // Fetch current isAdmin
        $stmt = $db->prepare('SELECT isAdmin FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$target]);
        $row = $stmt->fetch();
        if ($row) {
            $new_val = $row['isAdmin'] ? 0 : 1;
            $stmt = $db->prepare('UPDATE users SET isAdmin = ? WHERE id = ?');
            $stmt->execute([$new_val, $target]);
            $message = 'Admin status updated.';
        }
    }
}

// Fetch all users
$stmt = $db->prepare('SELECT id, username, email, isAdmin, twofa_enabled, created_at FROM users ORDER BY id ASC');
$stmt->execute();
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>User Management | TravelSc Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet"/>
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
body{font-family:'Inter',sans-serif}
h1,h2,h3{font-family:'Manrope',sans-serif}
</style>
</head>
<body class="bg-surface text-on-surface min-h-screen flex">

<!-- Side Nav -->
<aside class="fixed left-0 top-0 h-full flex flex-col py-8 bg-surface-container-low h-screen w-64 border-r border-outline-variant/20 font-medium text-sm z-40">
  <div class="px-6 mb-10">
    <h1 class="font-headline font-extrabold text-primary text-lg leading-none">TravelSc Admin</h1>
    <p class="text-[10px] uppercase tracking-[0.2em] text-outline mt-1">Global Fleet Control</p>
  </div>
  <nav class="flex-1 space-y-1 px-3">
    <a href="dashboard.php" class="flex items-center gap-3 px-4 py-3 text-on-surface-variant hover:bg-surface-container hover:pl-6 transition-all duration-300 rounded-lg">
      <span class="material-symbols-outlined">dashboard</span><span>Dashboard</span>
    </a>
    <a href="user_management.php" class="flex items-center gap-3 px-4 py-3 bg-white text-primary rounded-lg shadow-sm font-bold">
      <span class="material-symbols-outlined" style="font-variation-settings:'FILL' 1">group</span><span>User Management</span>
    </a>
  </nav>
  <div class="px-4 mt-auto space-y-1 border-t border-outline-variant/20 pt-6">
    <a href="user.php" class="flex items-center gap-3 px-4 py-2 text-on-surface-variant hover:text-primary transition-colors">
      <span class="material-symbols-outlined">person</span><span>My Profile</span>
    </a>
    <a href="logout.php" class="flex items-center gap-3 px-4 py-2 text-error hover:opacity-80 transition-opacity">
      <span class="material-symbols-outlined">logout</span><span>Sign Out</span>
    </a>
  </div>
</aside>

<!-- Main -->
<main class="flex-1 ml-64 p-8 bg-surface">
  <header class="flex justify-between items-end mb-10">
    <div>
      <h2 class="text-3xl font-headline font-black text-primary tracking-tighter mb-2">User Management</h2>
      <p class="text-on-surface-variant font-medium">Oversee global user access, roles, and account statuses.</p>
    </div>
    <div class="flex items-center gap-2 bg-surface-container-low px-4 py-2 rounded-xl border border-outline-variant/20">
      <span class="material-symbols-outlined text-secondary">group</span>
      <span class="text-sm font-bold text-primary"><?= count($users) ?> Total Users</span>
    </div>
  </header>

  <?php if ($message): ?>
  <div class="mb-6 p-4 bg-green-50 text-green-800 rounded-xl text-sm font-medium flex items-center gap-3 border border-green-200">
    <span class="material-symbols-outlined text-sm" style="font-variation-settings:'FILL' 1">check_circle</span>
    <?= htmlspecialchars($message) ?>
  </div>
  <?php endif; ?>
  <?php if ($error): ?>
  <div class="mb-6 p-4 bg-error-container text-on-error-container rounded-xl text-sm font-medium flex items-center gap-3">
    <span class="material-symbols-outlined text-sm">error</span>
    <?= htmlspecialchars($error) ?>
  </div>
  <?php endif; ?>

  <!-- Table -->
  <section class="bg-surface-container-lowest rounded-3xl overflow-hidden shadow-sm border border-outline-variant/10">
    <div class="overflow-x-auto">
      <table class="w-full text-left border-collapse">
        <thead>
          <tr class="bg-surface-container-low/50">
            <th class="px-8 py-5 text-[10px] font-black uppercase tracking-[0.15em] text-outline">User</th>
            <th class="px-8 py-5 text-[10px] font-black uppercase tracking-[0.15em] text-outline">Email</th>
            <th class="px-8 py-5 text-[10px] font-black uppercase tracking-[0.15em] text-outline">Role</th>
            <th class="px-8 py-5 text-[10px] font-black uppercase tracking-[0.15em] text-outline">2FA</th>
            <th class="px-8 py-5 text-[10px] font-black uppercase tracking-[0.15em] text-outline">Joined</th>
            <th class="px-8 py-5 text-[10px] font-black uppercase tracking-[0.15em] text-outline text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-outline-variant/10">
          <?php foreach ($users as $u):
              $is_self = ((int)$u['id'] === (int)$_SESSION['user_id']);
          ?>
          <tr class="hover:bg-surface-container-low/30 transition-colors group">
            <td class="px-8 py-5">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-primary-container flex items-center justify-center text-on-primary-container font-black text-sm shrink-0">
                  <?= strtoupper(substr($u['username'], 0, 1)) ?>
                </div>
                <div>
                  <div class="font-bold text-primary"><?= htmlspecialchars($u['username']) ?></div>
                  <div class="text-[10px] text-outline font-medium">#<?= $u['id'] ?></div>
                </div>
              </div>
            </td>
            <td class="px-8 py-5 text-sm text-on-surface-variant"><?= htmlspecialchars($u['email']) ?></td>
            <td class="px-8 py-5">
              <?php if ($u['isAdmin']): ?>
              <span class="px-3 py-1 bg-primary-container text-on-primary-container text-[10px] font-black uppercase tracking-wider rounded-full">Admin</span>
              <?php else: ?>
              <span class="px-3 py-1 bg-surface-container-high text-on-surface-variant text-[10px] font-black uppercase tracking-wider rounded-full">Customer</span>
              <?php endif; ?>
            </td>
            <td class="px-8 py-5">
              <?php if ($u['twofa_enabled']): ?>
              <div class="flex items-center gap-2 text-green-600">
                <span class="w-2 h-2 rounded-full bg-green-500"></span>
                <span class="text-xs font-bold">On</span>
              </div>
              <?php else: ?>
              <div class="flex items-center gap-2 text-outline">
                <span class="w-2 h-2 rounded-full bg-outline-variant"></span>
                <span class="text-xs font-bold">Off</span>
              </div>
              <?php endif; ?>
            </td>
            <td class="px-8 py-5 text-xs text-on-surface-variant"><?= htmlspecialchars($u['created_at']) ?></td>
            <td class="px-8 py-5 text-right">
              <?php if (!$is_self): ?>
              <div class="flex justify-end gap-2">
                <!-- Toggle Admin -->
                <form method="POST" action="user_management.php" onsubmit="return confirm('Toggle admin status for <?= htmlspecialchars(addslashes($u['username'])) ?>?')">
                  <input type="hidden" name="action" value="toggle_admin"/>
                  <input type="hidden" name="target_id" value="<?= $u['id'] ?>"/>
                  <button type="submit" title="Toggle Admin" class="p-2 hover:bg-surface-container-high rounded-lg text-secondary transition-colors">
                    <span class="material-symbols-outlined text-[20px]">admin_panel_settings</span>
                  </button>
                </form>
                <!-- Delete -->
                <form method="POST" action="user_management.php" onsubmit="return confirm('Delete user <?= htmlspecialchars(addslashes($u['username'])) ?>? This cannot be undone.')">
                  <input type="hidden" name="action" value="delete"/>
                  <input type="hidden" name="target_id" value="<?= $u['id'] ?>"/>
                  <button type="submit" title="Delete User" class="p-2 hover:bg-error-container rounded-lg text-error transition-colors">
                    <span class="material-symbols-outlined text-[20px]">delete</span>
                  </button>
                </form>
              </div>
              <?php else: ?>
              <span class="text-[10px] text-outline uppercase tracking-widest">You</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="px-8 py-4 bg-surface-container-low/30 border-t border-outline-variant/10">
      <p class="text-[10px] font-black uppercase tracking-widest text-outline">Showing <?= count($users) ?> users</p>
    </div>
  </section>
</main>
</body>
</html>
