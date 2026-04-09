<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Dashboard — Quiz Carnival';
require_once __DIR__ . '/includes/header-sidebar.php';

$pdo  = getDB();
$uid  = $user['id'];
$role = $user['role'];

// Stats
if ($role === 'teacher') {
    $classCount  = $pdo->prepare("SELECT COUNT(*) FROM classes WHERE teacher_id=?"); $classCount->execute([$uid]); $classCount = (int)$classCount->fetchColumn();
    $quizCount   = $pdo->prepare("SELECT COUNT(*) FROM quizzes q JOIN classes c ON q.class_id=c.id WHERE c.teacher_id=?"); $quizCount->execute([$uid]); $quizCount = (int)$quizCount->fetchColumn();
    $studentCount = $pdo->prepare("SELECT COUNT(DISTINCT e.student_id) FROM enrollments e JOIN classes c ON e.class_id=c.id WHERE c.teacher_id=?"); $studentCount->execute([$uid]); $studentCount = (int)$studentCount->fetchColumn();
    $recentClasses = $pdo->prepare("SELECT * FROM classes WHERE teacher_id=? ORDER BY created_at DESC LIMIT 5"); $recentClasses->execute([$uid]); $recentClasses = $recentClasses->fetchAll();
    $recentQuizzes = $pdo->prepare("SELECT q.*,c.name as class_name FROM quizzes q JOIN classes c ON q.class_id=c.id WHERE c.teacher_id=? ORDER BY q.created_at DESC LIMIT 5"); $recentQuizzes->execute([$uid]); $recentQuizzes = $recentQuizzes->fetchAll();
} else {
    $classCount  = $pdo->prepare("SELECT COUNT(*) FROM enrollments WHERE student_id=?"); $classCount->execute([$uid]); $classCount = (int)$classCount->fetchColumn();
    $quizCount   = $pdo->prepare("SELECT COUNT(*) FROM quiz_attempts WHERE student_id=?"); $quizCount->execute([$uid]); $quizCount = (int)$quizCount->fetchColumn();
    $pending     = $pdo->prepare("SELECT COUNT(DISTINCT q.id) FROM quizzes q JOIN classes c ON q.class_id=c.id JOIN enrollments e ON e.class_id=c.id LEFT JOIN quiz_attempts qa ON qa.quiz_id=q.id AND qa.student_id=? WHERE e.student_id=? AND q.published=1 AND qa.id IS NULL AND (q.closes_at IS NULL OR q.closes_at > NOW())");
    $pending->execute([$uid, $uid]); $pending = (int)$pending->fetchColumn();
    $recentClasses = $pdo->prepare("SELECT c.*,u.name as teacher_name FROM classes c JOIN enrollments e ON e.class_id=c.id JOIN users u ON c.teacher_id=u.id WHERE e.student_id=? ORDER BY e.enrolled_at DESC LIMIT 5"); $recentClasses->execute([$uid]); $recentClasses = $recentClasses->fetchAll();
    $recentQuizzes = $pdo->prepare("SELECT q.*,c.name as class_name,qa.score,qa.max_score,qa.submitted_at as done_at FROM quizzes q JOIN classes c ON q.class_id=c.id JOIN enrollments e ON e.class_id=c.id LEFT JOIN quiz_attempts qa ON qa.quiz_id=q.id AND qa.student_id=? WHERE e.student_id=? AND q.published=1 ORDER BY q.created_at DESC LIMIT 5"); $recentQuizzes->execute([$uid,$uid]); $recentQuizzes = $recentQuizzes->fetchAll();
}
?>
<style>
.page-content { padding: 36px 40px; flex: 1; }
.greeting { font-size: 26px; font-weight: 700; color: var(--text-main); margin-bottom: 4px; }
.subgreeting { color: var(--text-muted); font-size: 14px; margin-bottom: 32px; }
.stats-row { display: grid; grid-template-columns: repeat(3,1fr); gap: 20px; margin-bottom: 32px; }
.stat-card { background: #fff; border-radius: 16px; padding: 24px; box-shadow: var(--shadow); display: flex; align-items: center; gap: 16px; }
.stat-icon { width: 54px; height: 54px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 26px; flex-shrink: 0; }
.si-blue  { background: #ede9ff; color: var(--primary); }
.si-pink  { background: #fff0f4; color: #d81b60; }
.si-green { background: #e0f7f4; color: #00897b; }
.stat-val { font-size: 28px; font-weight: 800; color: var(--text-main); }
.stat-lbl { font-size: 13px; color: var(--text-muted); }
.two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
.card { background: #fff; border-radius: 16px; padding: 24px; box-shadow: var(--shadow); }
.card-hd { display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; }
.card-title { font-size: 15px; font-weight: 700; color: var(--text-main); }
.card-link { font-size: 13px; color: var(--primary); text-decoration: none; font-weight: 600; }
.card-link:hover { text-decoration: underline; }
.item-row { display: flex; align-items: center; gap: 12px; padding: 10px 0; border-bottom: 1px solid #f4f6f9; }
.item-row:last-child { border-bottom: none; }
.item-icon { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 17px; flex-shrink: 0; }
.item-info { flex: 1; min-width: 0; }
.item-name { font-size: 14px; font-weight: 600; color: var(--text-main); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.item-sub  { font-size: 12px; color: var(--text-muted); }
.badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
.badge-pub { background: #e0f7f4; color: #00897b; }
.badge-draft { background: #f4f6f9; color: #a3aed0; }
.badge-done { background: #ede9ff; color: var(--primary); }
.empty-state { text-align: center; padding: 30px 0; color: var(--text-muted); font-size: 14px; }
.empty-state i { font-size: 40px; display: block; margin-bottom: 8px; }
</style>

<div class="page-content">
  <p class="greeting">Hello, <?= h(explode(' ', $user['name'])[0]) ?> 👋</p>
  <p class="subgreeting"><?= $role === 'teacher' ? 'Welcome back, teacher! Here\'s an overview of your classes.' : 'Welcome back! Here are your classes and quizzes.' ?></p>

  <div class="stats-row">
    <div class="stat-card">
      <div class="stat-icon si-blue"><i class='bx bx-book'></i></div>
      <div><div class="stat-val"><?= $classCount ?></div><div class="stat-lbl"><?= $role==='teacher'?'Classes Created':'Classes Enrolled' ?></div></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon si-pink"><i class='bx bx-notepad'></i></div>
      <div><div class="stat-val"><?= $quizCount ?></div><div class="stat-lbl"><?= $role==='teacher'?'Quizzes Created':'Quizzes Completed' ?></div></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon si-green"><i class='bx <?= $role==='teacher'?'bx-group':'bx-time' ?>'></i></div>
      <div><div class="stat-val"><?= $role==='teacher'?$studentCount:$pending ?></div><div class="stat-lbl"><?= $role==='teacher'?'Total Students':'Pending Quizzes' ?></div></div>
    </div>
  </div>

  <div class="two-col">
    <!-- Recent Classes -->
    <div class="card">
      <div class="card-hd">
        <span class="card-title"><i class='bx bx-book' style="color:var(--primary)"></i> Recent Classes</span>
        <a class="card-link" href="<?= SITE_ROOT ?><?= $role==='teacher'?'create':'myclasses' ?>.php">See all</a>
      </div>
      <?php if (empty($recentClasses)): ?>
        <div class="empty-state"><i class='bx bx-book-open'></i>No classes yet.</div>
      <?php else: foreach ($recentClasses as $cls): ?>
        <a href="<?= SITE_ROOT ?>class.php?id=<?= $cls['id'] ?>" style="text-decoration:none;">
        <div class="item-row">
          <div class="item-icon" style="background:<?= h($cls['color'] ?? '#4318FF') ?>22;color:<?= h($cls['color'] ?? '#4318FF') ?>"><i class='bx bx-book'></i></div>
          <div class="item-info">
            <div class="item-name"><?= h($cls['name']) ?></div>
            <div class="item-sub"><?= $role==='teacher' ? date('d M Y', strtotime($cls['created_at'])) : 'by '.h($cls['teacher_name']) ?></div>
          </div>
        </div>
        </a>
      <?php endforeach; endif; ?>
    </div>

    <!-- Recent Quizzes -->
    <div class="card">
      <div class="card-hd">
        <span class="card-title"><i class='bx bx-notepad' style="color:var(--primary)"></i> Recent Quizzes</span>
        <a class="card-link" href="<?= SITE_ROOT ?>grades.php">Grades</a>
      </div>
      <?php if (empty($recentQuizzes)): ?>
        <div class="empty-state"><i class='bx bx-notepad'></i>No quizzes yet.</div>
      <?php else: foreach ($recentQuizzes as $q): ?>
        <div class="item-row">
          <div class="item-icon" style="background:#f4f6f9;color:var(--primary)"><?= quizTypeIcon($q['type']) ?></div>
          <div class="item-info">
            <div class="item-name"><?= h($q['title']) ?></div>
            <div class="item-sub"><?= h($q['class_name']) ?> &middot; <?= quizTypeLabel($q['type']) ?></div>
          </div>
          <?php if ($role==='teacher'): ?>
            <span class="badge <?= $q['published']?'badge-pub':'badge-draft' ?>"><?= $q['published']?'Published':'Draft' ?></span>
          <?php else: ?>
            <?php if ($q['done_at']): $pct = $q['max_score']>0?round($q['score']/$q['max_score']*100):0; ?>
              <span class="badge badge-done"><?= $pct ?>%</span>
            <?php else: ?>
              <span class="badge badge-draft">Pending</span>
            <?php endif; ?>
          <?php endif; ?>
        </div>
      <?php endforeach; endif; ?>
    </div>
  </div>
</div>

<script>
function toggleDropdown() {
    document.getElementById("profileDropdown").classList.toggle("show");
}
window.addEventListener('click', (e) => {
    if (!e.target.closest('.profile-container')) {
        document.getElementById("profileDropdown")?.classList.remove("show");
    }
});
</script>
</div>
</body>
</html>
