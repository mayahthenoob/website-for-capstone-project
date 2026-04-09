<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'My Classes — Quiz Carnival';
require_once __DIR__ . '/includes/header-sidebar.php';

$pdo = getDB();
$uid = $user['id'];

if ($user['role'] === 'teacher') {
    $classes = $pdo->prepare("SELECT c.*, (SELECT COUNT(*) FROM enrollments WHERE class_id=c.id) as enrolled_count, (SELECT COUNT(*) FROM quizzes WHERE class_id=c.id) as quiz_count FROM classes c WHERE c.teacher_id=? ORDER BY c.created_at DESC");
    $classes->execute([$uid]);
} else {
    $classes = $pdo->prepare("SELECT c.*, u.name as teacher_name, (SELECT COUNT(*) FROM enrollments WHERE class_id=c.id) as enrolled_count, (SELECT COUNT(*) FROM quizzes WHERE class_id=c.id AND published=1) as quiz_count FROM classes c JOIN enrollments e ON e.class_id=c.id JOIN users u ON c.teacher_id=u.id WHERE e.student_id=? ORDER BY e.enrolled_at DESC");
    $classes->execute([$uid]);
}
$classes = $classes->fetchAll();
?>
<style>
.page-content { padding: 36px 40px; flex: 1; }
.page-hd { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 28px; flex-wrap: wrap; gap: 12px; }
.page-hd-left h1 { font-size: 24px; font-weight: 700; color: var(--text-main); }
.page-hd-left p  { font-size: 14px; color: var(--text-muted); margin-top: 4px; }
.btn-new { background: var(--primary); color: #fff; border: none; padding: 10px 22px; border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; font-family:'Inter',sans-serif; }
.btn-new:hover { background: #3310cc; }
.top-bar { display: flex; gap: 12px; margin-bottom: 24px; }
.search-box { flex: 1; max-width: 360px; position: relative; }
.search-box i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); font-size: 18px; color: var(--text-muted); }
.search-box input { width: 100%; padding: 10px 14px 10px 40px; border: 1.5px solid #e6e9ef; border-radius: 10px; font-size: 14px; font-family:'Inter',sans-serif; outline: none; }
.search-box input:focus { border-color: var(--primary); }
.classes-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 20px; }
.class-card { background: #fff; border-radius: 16px; overflow: hidden; box-shadow: var(--shadow); text-decoration: none; display: flex; flex-direction: column; transition: 0.2s; }
.class-card:hover { transform: translateY(-3px); box-shadow: 0 8px 30px rgba(0,0,0,0.1); }
.class-banner { height: 90px; display: flex; align-items: center; justify-content: center; font-size: 36px; color: rgba(255,255,255,0.8); }
.class-body { padding: 18px 20px; flex: 1; }
.class-name { font-size: 15px; font-weight: 700; color: var(--text-main); margin-bottom: 4px; }
.class-sub  { font-size: 13px; color: var(--text-muted); }
.class-footer { padding: 12px 20px; border-top: 1px solid #f4f6f9; display: flex; justify-content: space-between; align-items: center; }
.meta-pill { font-size: 12px; color: var(--text-muted); display: flex; align-items: center; gap: 4px; }
.btn-visit { padding: 6px 16px; border-radius: 20px; background: #ede9ff; color: var(--primary); font-size: 12px; font-weight: 700; text-decoration: none; }
.empty-state { text-align: center; padding: 80px 0; color: var(--text-muted); }
.empty-state i { font-size: 60px; display: block; margin-bottom: 16px; }
.empty-state h2 { font-size: 18px; font-weight: 700; color: var(--text-main); margin-bottom: 8px; }
</style>

<div class="page-content">
  <div class="page-hd">
    <div class="page-hd-left">
      <h1><?= $user['role']==='teacher' ? 'My Created Classes' : 'My Enrolled Classes' ?></h1>
      <p><?= $user['role']==='teacher' ? 'Classes you have created.' : 'Classes you are enrolled in.' ?></p>
    </div>
    <?php if ($user['role'] === 'teacher'): ?>
      <a href="<?= SITE_ROOT ?>create.php" class="btn-new"><i class='bx bx-plus'></i> New Class</a>
    <?php else: ?>
      <a href="<?= SITE_ROOT ?>classes.php" class="btn-new"><i class='bx bx-search'></i> Find Classes</a>
    <?php endif; ?>
  </div>

  <div class="top-bar">
    <div class="search-box">
      <i class='bx bx-search'></i>
      <input type="text" id="searchInput" placeholder="Search..." oninput="filterCards()">
    </div>
  </div>

  <?php if (empty($classes)): ?>
    <div class="empty-state">
      <i class='bx bx-book-open'></i>
      <h2>No classes yet</h2>
      <p><?= $user['role']==='teacher' ? 'Create your first class to get started.' : 'Enrol in a class to get started.' ?></p>
    </div>
  <?php else: ?>
    <div class="classes-grid" id="classesGrid">
      <?php foreach ($classes as $cls): ?>
        <div class="class-card-wrap" data-name="<?= h(strtolower($cls['name'])) ?>">
          <a href="<?= SITE_ROOT ?>class.php?id=<?= $cls['id'] ?>" class="class-card">
            <div class="class-banner" style="background:<?= h($cls['color']) ?>"><i class='bx bx-book'></i></div>
            <div class="class-body">
              <div class="class-name"><?= h($cls['name']) ?></div>
              <div class="class-sub"><?= $user['role']==='teacher' ? $cls['enrolled_count'].' students' : 'by '.h($cls['teacher_name']) ?></div>
            </div>
            <div class="class-footer">
              <span class="meta-pill"><i class='bx bx-notepad'></i><?= $cls['quiz_count'] ?> quiz<?= $cls['quiz_count']!=1?'zes':'' ?></span>
              <span class="btn-visit">Open</span>
            </div>
          </a>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<script>
function filterCards() {
    const q = document.getElementById("searchInput").value.toLowerCase().trim();
    document.querySelectorAll('.class-card-wrap').forEach(c => {
        c.style.display = c.dataset.name.includes(q) ? '' : 'none';
    });
}
function toggleDropdown() { document.getElementById("profileDropdown").classList.toggle("show"); }
window.addEventListener('click', (e) => { if (!e.target.closest('.profile-container')) document.getElementById("profileDropdown")?.classList.remove("show"); });
</script>
</div></body></html>
