<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

if (session_status() === PHP_SESSION_NONE) session_start();
requireLogin();
$user = currentUser();

$pdo = getDB();
$cid = (int)($_GET['id'] ?? 0);
if (!$cid) { header('Location: ' . SITE_ROOT . 'classes.php'); exit; }

$cls = $pdo->prepare("SELECT c.*, u.name as teacher_name, u.id as teacher_user_id FROM classes c JOIN users u ON c.teacher_id=u.id WHERE c.id=?");
$cls->execute([$cid]); $cls = $cls->fetch();
if (!$cls) { header('Location: ' . SITE_ROOT . 'classes.php'); exit; }

// Check access: teacher of class or enrolled student
$isOwner  = ($user['role'] === 'teacher' && $cls['teacher_user_id'] == $user['id']);
$enrolled = false;
if ($user['role'] === 'student') {
    $e = $pdo->prepare("SELECT id FROM enrollments WHERE class_id=? AND student_id=?");
    $e->execute([$cid, $user['id']]); $enrolled = (bool)$e->fetch();
}
$canAccess = $isOwner || ($user['role'] === 'teacher') || $enrolled;
// Teachers can visit any class; students need to be enrolled
if (!$canAccess) {
    header('Location: ' . SITE_ROOT . 'classes.php?msg=not_enrolled'); exit;
}

// Active tab
$tab = $_GET['tab'] ?? 'quizzes';

// Students in class
$students = $pdo->prepare("SELECT u.id, u.name, u.username, u.profile_pic FROM enrollments e JOIN users u ON e.student_id=u.id WHERE e.class_id=? ORDER BY u.name");
$students->execute([$cid]); $students = $students->fetchAll();

// Quizzes
if ($user['role'] === 'teacher') {
    $quizzes = $pdo->prepare("SELECT q.* FROM quizzes q WHERE q.class_id=? ORDER BY q.created_at DESC");
} else {
    $quizzes = $pdo->prepare("SELECT q.* FROM quizzes q WHERE q.class_id=? AND q.published=1 ORDER BY q.created_at DESC");
}
$quizzes->execute([$cid]); $quizzes = $quizzes->fetchAll();

// Attempts for current student
$attemptMap = [];
if ($user['role'] === 'student') {
    $aStmt = $pdo->prepare("SELECT quiz_id, MAX(score) as best_score, MAX(max_score) as max_score FROM quiz_attempts WHERE student_id=? GROUP BY quiz_id");
    $aStmt->execute([$user['id']]);
    foreach ($aStmt->fetchAll() as $a) $attemptMap[$a['quiz_id']] = $a;
}

$pageTitle = h($cls['name']) . ' — Quiz Carnival';
require_once __DIR__ . '/includes/header-sidebar.php';
?>
<style>
.page-content { padding: 0; flex: 1; display: flex; flex-direction: column; }
.class-hero { background: <?= h($cls['color']) ?>; color: #fff; padding: 32px 40px; }
.class-hero h1 { font-size: 28px; font-weight: 800; margin-bottom: 4px; }
.class-hero p  { font-size: 14px; opacity: 0.85; }
.class-hero-meta { margin-top: 16px; display: flex; gap: 20px; flex-wrap: wrap; }
.meta-chip { background: rgba(255,255,255,0.2); padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: 600; display: flex; align-items: center; gap: 6px; }

.tabs-bar { background: #fff; border-bottom: 1px solid #e6e9ef; padding: 0 40px; display: flex; gap: 0; }
.tab-btn { padding: 16px 0; margin-right: 28px; border: none; background: none; font-family: 'Inter',sans-serif; font-size: 14px; font-weight: 600; color: var(--text-muted); cursor: pointer; border-bottom: 3px solid transparent; transition: 0.2s; }
.tab-btn.active { color: var(--primary); border-color: var(--primary); }

.tab-content { padding: 32px 40px; flex: 1; }

/* Quiz cards */
.quiz-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 18px; }
.quiz-card { background: #fff; border-radius: 14px; padding: 20px; box-shadow: var(--shadow); border: 1.5px solid #f0f0f0; }
.quiz-card-head { display: flex; align-items: flex-start; gap: 12px; margin-bottom: 12px; }
.quiz-icon { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0; background: #ede9ff; color: var(--primary); }
.quiz-title { font-size: 15px; font-weight: 700; color: var(--text-main); }
.quiz-type-label { font-size: 12px; color: var(--text-muted); margin-top: 2px; }
.quiz-meta { font-size: 12px; color: var(--text-muted); display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 14px; }
.quiz-meta span { display: flex; align-items: center; gap: 4px; }
.quiz-actions { display: flex; gap: 8px; flex-wrap: wrap; }
.btn-sm { padding: 7px 16px; border-radius: 20px; font-size: 12px; font-weight: 700; border: none; cursor: pointer; font-family: 'Inter',sans-serif; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; transition: 0.2s; }
.btn-play  { background: var(--primary); color: #fff; }
.btn-play:hover { background: #3310cc; }
.btn-edit  { background: #f4f6f9; color: var(--text-main); }
.btn-edit:hover { background: #e6e9ef; }
.btn-pub   { background: #e0f7f4; color: #00897b; }
.btn-pub:hover { background: #c3f0ea; }
.btn-unpub { background: #fff0f4; color: #d81b60; }
.badge-pub { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; background: #e0f7f4; color: #00897b; }
.badge-draft { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; background: #f4f6f9; color: var(--text-muted); }
.badge-done { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; background: #ede9ff; color: var(--primary); }
.no-quizzes { text-align: center; padding: 60px 0; color: var(--text-muted); }
.no-quizzes i { font-size: 50px; display: block; margin-bottom: 12px; }

/* Students table */
.student-table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 14px; overflow: hidden; box-shadow: var(--shadow); }
.student-table th { text-align: left; padding: 14px 20px; font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #f0f0f0; background: #f8f9fc; }
.student-table td { padding: 14px 20px; border-bottom: 1px solid #f8f9fc; font-size: 14px; }
.student-table tr:last-child td { border-bottom: none; }
.avatar-sm { width: 34px; height: 34px; border-radius: 50%; object-fit: cover; }
.name-cell { display: flex; align-items: center; gap: 10px; }
.section-hd { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.section-title { font-size: 17px; font-weight: 700; color: var(--text-main); }
.btn-create-quiz { background: var(--primary); color: #fff; border: none; padding: 10px 20px; border-radius: 10px; font-size: 13px; font-weight: 600; cursor: pointer; font-family:'Inter',sans-serif; display: flex; align-items: center; gap: 6px; text-decoration: none; }
.btn-create-quiz:hover { background: #3310cc; }

/* Grade summary */
.grade-info { display: inline-flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 700; }
</style>

<div class="page-content">
  <div class="class-hero">
    <h1><?= h($cls['name']) ?></h1>
    <p><?= h($cls['description'] ?: 'No description provided.') ?></p>
    <div class="class-hero-meta">
      <span class="meta-chip"><i class='bx bx-user'></i> <?= h($cls['teacher_name']) ?></span>
      <span class="meta-chip"><i class='bx bx-group'></i> <?= count($students) ?> student<?= count($students)!=1?'s':'' ?></span>
      <span class="meta-chip"><i class='bx bx-notepad'></i> <?= count($quizzes) ?> quiz<?= count($quizzes)!=1?'zes':'' ?></span>
    </div>
  </div>

  <div class="tabs-bar">
    <button class="tab-btn <?= $tab==='quizzes'?'active':'' ?>" onclick="switchTab('quizzes')">
      <i class='bx bx-notepad'></i> Quizzes
    </button>
    <button class="tab-btn <?= $tab==='students'?'active':'' ?>" onclick="switchTab('students')">
      <i class='bx bx-group'></i> Students
    </button>
    <?php if ($user['role']==='teacher'): ?>
    <button class="tab-btn <?= $tab==='grades'?'active':'' ?>" onclick="switchTab('grades')">
      <i class='bx bx-line-chart'></i> Grades
    </button>
    <?php endif; ?>
  </div>

  <!-- ─── Quizzes tab ─── -->
  <div class="tab-content" id="tab-quizzes" style="<?= $tab!=='quizzes'?'display:none':'' ?>">
    <div class="section-hd">
      <span class="section-title">Quizzes</span>
      <?php if ($isOwner): ?>
        <a href="<?= SITE_ROOT ?>create.php?class_id=<?= $cid ?>" class="btn-create-quiz"><i class='bx bx-plus'></i> Add Quiz</a>
      <?php endif; ?>
    </div>

    <?php if (empty($quizzes)): ?>
      <div class="no-quizzes"><i class='bx bx-notepad'></i><?= $user['role']==='teacher'?'No quizzes yet. Add one above.':'No quizzes published yet.' ?></div>
    <?php else: ?>
    <div class="quiz-grid">
      <?php foreach ($quizzes as $q): ?>
        <?php
        $attempt   = $attemptMap[$q['id']] ?? null;
        $pct       = ($attempt && $attempt['max_score']>0) ? round($attempt['best_score']/$attempt['max_score']*100) : null;
        $canPlay   = $user['role']==='student' && $q['published'];
        $isOpen    = !$q['opens_at'] || strtotime($q['opens_at']) <= time();
        $notClosed = !$q['closes_at'] || strtotime($q['closes_at']) > time();
        $playable  = $canPlay && $isOpen && $notClosed;
        ?>
        <div class="quiz-card">
          <div class="quiz-card-head">
            <div class="quiz-icon"><?= quizTypeIcon($q['type']) ?></div>
            <div>
              <div class="quiz-title"><?= h($q['title']) ?></div>
              <div class="quiz-type-label"><?= quizTypeLabel($q['type']) ?></div>
            </div>
            <div style="margin-left:auto">
              <?php if ($user['role']==='teacher'): ?>
                <span class="<?= $q['published']?'badge-pub':'badge-draft' ?>"><?= $q['published']?'Published':'Draft' ?></span>
              <?php elseif ($pct!==null): ?>
                <span class="badge-done"><?= $pct ?>%</span>
              <?php endif; ?>
            </div>
          </div>
          <div class="quiz-meta">
            <?php if ($q['opens_at']): ?><span><i class='bx bx-calendar'></i> <?= formatDate($q['opens_at']) ?></span><?php endif; ?>
            <?php if ($q['time_limit']): ?><span><i class='bx bx-time'></i> <?= $q['time_limit'] ?> min</span><?php endif; ?>
            <span><i class='bx bx-refresh'></i> <?= $q['attempts_allowed'] ?> attempt<?= $q['attempts_allowed']!=1?'s':'' ?></span>
          </div>
          <div class="quiz-actions">
            <?php if ($user['role'] === 'teacher'): ?>
              <a href="<?= SITE_ROOT ?>quiz/<?= quizTypePath($q['type']) ?>/create.php?quiz_id=<?= $q['id'] ?>&class_id=<?= $cid ?>" class="btn-sm btn-edit" target="_blank"><i class='bx bx-edit'></i> Edit</a>
              <?php if ($q['published']): ?>
                <button class="btn-sm btn-unpub" onclick="togglePublish(<?= $q['id'] ?>, 0, this)"><i class='bx bx-hide'></i> Unpublish</button>
              <?php else: ?>
                <button class="btn-sm btn-pub" onclick="togglePublish(<?= $q['id'] ?>, 1, this)"><i class='bx bx-show'></i> Publish</button>
              <?php endif; ?>
              <a href="<?= SITE_ROOT ?>grades.php?quiz_id=<?= $q['id'] ?>" class="btn-sm btn-edit"><i class='bx bx-line-chart'></i> Grades</a>
            <?php elseif ($playable): ?>
              <a href="<?= SITE_ROOT ?>quiz/<?= quizTypePath($q['type']) ?>/start.php?quiz_id=<?= $q['id'] ?>" class="btn-sm btn-play"><i class='bx bx-play'></i> <?= $attempt?'Retry':'Start' ?></a>
            <?php elseif ($canPlay && !$isOpen): ?>
              <span style="font-size:12px;color:var(--text-muted)">Opens <?= formatDate($q['opens_at']) ?></span>
            <?php elseif ($canPlay && !$notClosed): ?>
              <span style="font-size:12px;color:var(--danger)">Closed</span>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

  <!-- ─── Students tab ─── -->
  <div class="tab-content" id="tab-students" style="<?= $tab!=='students'?'display:none':'' ?>">
    <div class="section-hd">
      <span class="section-title">Students (<?= count($students) ?>)</span>
    </div>
    <?php if (empty($students)): ?>
      <p style="color:var(--text-muted)">No students enrolled yet.</p>
    <?php else: ?>
    <table class="student-table">
      <thead><tr><th>Name</th><th>Username</th></tr></thead>
      <tbody>
        <?php foreach ($students as $s): ?>
        <tr>
          <td><div class="name-cell">
            <img src="<?= SITE_ROOT . ($s['profile_pic'] ?: 'assets/default-avatar.jpg') ?>" alt="" class="avatar-sm" onerror="this.src='<?= SITE_ROOT ?>assets/default-avatar.jpg'">
            <?= h($s['name']) ?>
          </div></td>
          <td><?= h($s['username']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>

  <!-- ─── Grades tab (teacher only) ─── -->
  <?php if ($user['role']==='teacher'): ?>
  <div class="tab-content" id="tab-grades" style="<?= $tab!=='grades'?'display:none':'' ?>">
    <div class="section-hd">
      <span class="section-title">Grade Overview</span>
      <a href="<?= SITE_ROOT ?>grades.php?class_id=<?= $cid ?>" class="btn-create-quiz"><i class='bx bx-line-chart'></i> Full Grades</a>
    </div>
    <p style="color:var(--text-muted);font-size:14px">See the full grades page to view and edit individual student scores.</p>
  </div>
  <?php endif; ?>
</div>

<?php
function quizTypePath($type) {
    switch($type) {
        case 'wordsearch':   return 'word-search';
        case 'fillinblanks': return 'fill-in-blanks';
        case 'hangman':      return 'hangman';
        default:             return $type;
    }
}
?>
<script>
function switchTab(tab) {
    document.querySelectorAll('.tab-content').forEach(t => t.style.display='none');
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-'+tab).style.display='block';
    event.target.classList.add('active');
}

function togglePublish(quizId, publish, btn) {
    btn.disabled = true;
    fetch('<?= SITE_ROOT ?>api/publish-quiz.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({quiz_id: quizId, published: publish})
    }).then(r=>r.json()).then(d => {
        if (d.success) location.reload();
        else { alert(d.error||'Failed'); btn.disabled=false; }
    });
}

function toggleDropdown() { document.getElementById("profileDropdown").classList.toggle("show"); }
window.addEventListener('click', (e) => { if (!e.target.closest('.profile-container')) document.getElementById("profileDropdown")?.classList.remove("show"); });
</script>
</div></body></html>
