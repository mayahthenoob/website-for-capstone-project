<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';
requireLogin();
$user = currentUser();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= h($pageTitle ?? 'Quiz Carnival') ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
:root {
  --primary: #4318FF;
  --bg-body: #f4f6f9;
  --text-main: #1b2559;
  --text-muted: #a3aed0;
  --white: #ffffff;
  --shadow: 0 4px 20px rgba(0,0,0,0.05);
  --danger: #ee5d50;
  --sidebar-w: 220px;
}
body { display: flex; background: var(--bg-body); min-height: 100vh; }

/* ─── Sidebar ─── */
.sidebar {
  width: var(--sidebar-w);
  background-color: #111827;
  color: #9ca3af;
  display: flex;
  flex-direction: column;
  align-items: center;
  position: fixed;
  height: 100vh;
  top: 0;
  left: 0;
  z-index: 200;
}
.sidebar-top {
  display: flex;
  align-items: center;
  width: 85%;
  padding: 20px 0;
  border-bottom: 1px solid #1f2937;
}
.logo-img { width: 40px; height: 40px; border-radius: 8px; margin-right: 10px; overflow: hidden; flex-shrink: 0; }
.logo-img img { width: 100%; height: 100%; object-fit: cover; }
.brand-text { display: flex; flex-direction: column; }
.brand { font-weight: 600; color: #fff; font-size: 14px; }
.tagline { font-size: 10px; color: #6b7280; }
.sidebar-center { width: 85%; margin-top: 24px; flex: 1; }
.nav-section { margin-bottom: 4px; }
.nav-label { font-size: 10px; font-weight: 700; color: #4b5563; text-transform: uppercase; letter-spacing: 1px; padding: 8px 10px 4px; }
.list { list-style: none; }
.list-item { margin-bottom: 4px; }
.list-item a {
  display: flex; align-items: center; gap: 10px; text-decoration: none;
  color: #9ca3af; padding: 10px; border-radius: 10px; transition: 0.2s;
  font-size: 14px; font-weight: 500;
}
.list-item a i { font-size: 18px; min-width: 20px; }
.list-item a:hover { background-color: #1f2937; color: #fff; }
.list-item a.active { background-color: #374151; color: #fff; }
.sidebar-bottom { width: 85%; padding: 20px 0; border-top: 1px solid #1f2937; }
.sidebar-bottom a {
  display: flex; align-items: center; gap: 10px; text-decoration: none;
  color: #9ca3af; padding: 10px; border-radius: 10px; transition: 0.2s; font-size: 14px;
}
.sidebar-bottom a:hover { background: #1f2937; color: #ef4444; }

/* ─── Main ─── */
.main { flex: 1; display: flex; flex-direction: column; margin-left: var(--sidebar-w); min-height: 100vh; }

/* ─── Header ─── */
.header {
  height: 72px;
  background: #fff;
  display: flex;
  align-items: center;
  justify-content: flex-end;
  padding: 0 30px;
  border-bottom: 1px solid #e6e9ef;
  position: sticky;
  top: 0;
  z-index: 100;
  gap: 8px;
}
.header-right { display: flex; align-items: center; gap: 20px; }
.header-icon-btn {
  width: 40px; height: 40px; border-radius: 10px; border: none;
  background: #f4f6f9; color: #555; font-size: 20px;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer; transition: 0.2s; text-decoration: none;
}
.header-icon-btn:hover { background: #e6e9ef; color: var(--primary); }
.profile-container { position: relative; }
.profile {
  display: flex; align-items: center; gap: 10px; cursor: pointer;
  padding: 6px 12px; border-radius: 10px; background: #f4f6f9; transition: 0.2s;
}
.profile:hover { background: #e6e9ef; }
.profile img { width: 36px; height: 36px; border-radius: 50%; object-fit: cover; }
.profile span { font-size: 14px; font-weight: 600; color: var(--text-main); }
.profile i { color: #aaa; font-size: 16px; }
.dropdown-menu {
  position: absolute; top: calc(100% + 8px); right: 0; width: 210px;
  background: #fff; border-radius: 12px;
  box-shadow: 0 10px 30px rgba(0,0,0,0.12); border: 1px solid #eee;
  display: none; flex-direction: column; padding: 6px 0; z-index: 300;
}
.dropdown-menu.show { display: flex; }
.dropdown-item {
  display: flex; align-items: center; gap: 10px; padding: 10px 18px;
  text-decoration: none; color: #444; font-size: 14px; transition: 0.15s;
}
.dropdown-item i { min-width: 20px; text-align: center; font-size: 17px; }
.dropdown-item:hover { background: #f8f9fa; color: var(--primary); }
.dropdown-divider { border: 0; border-top: 1px solid #f0f0f0; margin: 4px 0; }
.dropdown-item.logout:hover { color: var(--danger); }

/* ─── Role badge ─── */
.role-badge {
  font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 20px;
  text-transform: uppercase; letter-spacing: 0.5px;
}
.role-teacher { background: #ede9ff; color: var(--primary); }
.role-student { background: #e0f7f4; color: #00897b; }
</style>
</head>
<body>

<!-- ══ Sidebar ══ -->
<aside class="sidebar">
  <div class="sidebar-top">
    <div class="logo-img">
      <img src="<?= h(SITE_ROOT) ?>assets/logo.png" alt="Quiz Carnival">
    </div>
    <div class="brand-text">
      <span class="brand">Quiz Carnival</span>
      <span class="tagline">Design &amp; Play Quizzes</span>
    </div>
  </div>

  <nav class="sidebar-center">
    <div class="nav-section">
      <ul class="list">
        <li class="list-item"><a href="<?= h(SITE_ROOT) ?>dashboard.php" class="<?= $currentPage==='dashboard'?'active':'' ?>"><i class='bx bxs-dashboard'></i><span>Dashboard</span></a></li>
        <li class="list-item"><a href="<?= h(SITE_ROOT) ?>myclasses.php" class="<?= $currentPage==='myclasses'?'active':'' ?>"><i class='bx bx-book'></i><span>My Classes</span></a></li>
        <li class="list-item"><a href="<?= h(SITE_ROOT) ?>classes.php" class="<?= $currentPage==='classes'?'active':'' ?>"><i class='bx bx-library'></i><span>Classes</span></a></li>
        <li class="list-item"><a href="<?= h(SITE_ROOT) ?>calendar.php" class="<?= $currentPage==='calendar'?'active':'' ?>"><i class='bx bx-calendar'></i><span>Calendar</span></a></li>
        <li class="list-item"><a href="<?= h(SITE_ROOT) ?>grades.php" class="<?= $currentPage==='grades'?'active':'' ?>"><i class='bx bx-line-chart'></i><span>Grades</span></a></li>
      </ul>
    </div>

    <?php if (isTeacher()): ?>
    <div class="nav-section" style="margin-top:16px;">
      <div class="nav-label">Teacher</div>
      <ul class="list">
        <li class="list-item"><a href="<?= h(SITE_ROOT) ?>create.php" class="<?= $currentPage==='create'?'active':'' ?>"><i class='bx bx-plus-circle'></i><span>Create</span></a></li>
      </ul>
    </div>
    <?php endif; ?>
  </nav>

  <div class="sidebar-bottom">
    <a href="<?= h(SITE_ROOT) ?>logout.php"><i class='bx bx-log-out'></i><span>Logout</span></a>
  </div>
</aside>

<!-- ══ Main wrapper ══ -->
<div class="main">

  <!-- ── Header ── -->
  <div class="header">
    <div class="header-right">
      <a href="<?= h(SITE_ROOT) ?>calendar.php" class="header-icon-btn" title="Calendar"><i class='bx bx-calendar'></i></a>

      <div class="profile-container">
        <div class="profile" onclick="toggleDropdown()">
          <img src="<?= h(SITE_ROOT . ($user['avatar'] ?: 'assets/default-avatar.jpg')) ?>" alt="Profile" onerror="this.src='<?= h(SITE_ROOT) ?>assets/default-avatar.jpg'">
          <span><?= h($user['name']) ?></span>
          <i class='bx bx-chevron-down'></i>
        </div>
        <div class="dropdown-menu" id="profileDropdown">
          <a href="<?= h(SITE_ROOT) ?>profile.php" class="dropdown-item"><i class='bx bxs-user-circle'></i> My Profile</a>
          <a href="<?= h(SITE_ROOT) ?>grades.php" class="dropdown-item"><i class='bx bx-line-chart'></i> Grades</a>
          <a href="<?= h(SITE_ROOT) ?>calendar.php" class="dropdown-item"><i class='bx bx-calendar'></i> Calendar</a>
          <span class="dropdown-item" style="cursor:default;">
            <i class='bx bx-shield-alt-2'></i>
            <span class="role-badge <?= $user['role']==='teacher'?'role-teacher':'role-student' ?>"><?= h(ucfirst($user['role'])) ?></span>
          </span>
          <hr class="dropdown-divider">
          <a href="<?= h(SITE_ROOT) ?>logout.php" class="dropdown-item logout"><i class='bx bx-log-out'></i> Logout</a>
        </div>
      </div>
    </div>
  </div>
  <!-- ── Page content starts below ── -->
