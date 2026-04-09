<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Grades — Quiz Carnival';
require_once __DIR__ . '/includes/header-sidebar.php';

$pdo = getDB();
$uid = $user['id'];

if ($user['role'] === 'teacher') {
    // Teacher: see grades for a specific quiz or class
    $quizId  = (int)($_GET['quiz_id']  ?? 0);
    $classId = (int)($_GET['class_id'] ?? 0);

    // Get teacher's classes
    $classes = $pdo->prepare("SELECT c.id, c.name, c.color FROM classes c WHERE c.teacher_id=? ORDER BY c.name");
    $classes->execute([$uid]); $classes = $classes->fetchAll();

    $selectedClass = null; $quizzes = []; $selectedQuiz = null; $studentGrades = [];

    if ($classId) {
        // Verify teacher owns class
        foreach ($classes as $c) { if ($c['id'] == $classId) { $selectedClass = $c; break; } }
        if ($selectedClass) {
            $quizzes = $pdo->prepare("SELECT q.id, q.title, q.type FROM quizzes q WHERE q.class_id=? ORDER BY q.created_at DESC");
            $quizzes->execute([$classId]); $quizzes = $quizzes->fetchAll();
        }
    }

    if ($quizId && $selectedClass) {
        $selectedQuiz = $pdo->prepare("SELECT * FROM quizzes WHERE id=? AND class_id=?");
        $selectedQuiz->execute([$quizId, $classId]); $selectedQuiz = $selectedQuiz->fetch();
        if ($selectedQuiz) {
            // Get all students and their attempts + manual grades
            $stmt = $pdo->prepare("
                SELECT u.id, u.name, u.username, u.profile_pic,
                       MAX(qa.score) as best_score, MAX(qa.max_score) as max_score,
                       COUNT(qa.id) as attempt_count,
                       g.grade as manual_grade, g.note
                FROM enrollments e
                JOIN users u ON e.student_id=u.id
                LEFT JOIN quiz_attempts qa ON qa.quiz_id=? AND qa.student_id=u.id
                LEFT JOIN grades g ON g.quiz_id=? AND g.student_id=u.id
                WHERE e.class_id=?
                GROUP BY u.id, u.name, u.username, u.profile_pic, g.grade, g.note
                ORDER BY u.name
            ");
            $stmt->execute([$quizId, $quizId, $classId]); $studentGrades = $stmt->fetchAll();
        }
    }
} else {
    // Student: see their own grades per class
    $classId = (int)($_GET['class_id'] ?? 0);
    $classes = $pdo->prepare("SELECT c.id, c.name, c.color FROM classes c JOIN enrollments e ON e.class_id=c.id WHERE e.student_id=? ORDER BY c.name");
    $classes->execute([$uid]); $classes = $classes->fetchAll();
    $selectedClass = null; $myGrades = [];
    if (!$classId && !empty($classes)) $classId = $classes[0]['id'];
    if ($classId) {
        foreach ($classes as $c) { if ($c['id'] == $classId) { $selectedClass = $c; break; } }
        if ($selectedClass) {
            $stmt = $pdo->prepare("
                SELECT q.id, q.title, q.type, q.max_score as q_max,
                       MAX(qa.score) as best_score, MAX(qa.max_score) as max_score,
                       COUNT(qa.id) as attempt_count,
                       g.grade as manual_grade
                FROM quizzes q
                LEFT JOIN quiz_attempts qa ON qa.quiz_id=q.id AND qa.student_id=?
                LEFT JOIN grades g ON g.quiz_id=q.id AND g.student_id=?
                WHERE q.class_id=? AND q.published=1
                GROUP BY q.id, q.title, q.type, q.max_score, g.grade
                ORDER BY q.created_at
            ");
            $stmt->execute([$uid, $uid, $classId]); $myGrades = $stmt->fetchAll();
        }
    }
}
?>
<style>
.page-content { padding: 36px 40px; flex: 1; }
.page-hd { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 28px; flex-wrap: wrap; gap: 12px; }
.page-hd-left h1 { font-size: 24px; font-weight: 700; color: var(--text-main); }
.page-hd-left p  { font-size: 14px; color: var(--text-muted); margin-top: 4px; }
.filter-row { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 24px; align-items: center; }
.form-select { padding: 9px 14px; border: 1.5px solid #e6e9ef; border-radius: 10px; font-size: 14px; font-family:'Inter',sans-serif; outline: none; background: #fff; cursor: pointer; }
.form-select:focus { border-color: var(--primary); }
.stats-row { display: grid; grid-template-columns: repeat(3,1fr); gap: 16px; margin-bottom: 28px; }
.stat-card { background: #fff; border-radius: 14px; padding: 20px; box-shadow: var(--shadow); text-align: center; }
.stat-val { font-size: 30px; font-weight: 800; }
.stat-lbl { font-size: 12px; color: var(--text-muted); margin-top: 4px; }
.grade-table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 16px; box-shadow: var(--shadow); overflow: hidden; }
.grade-table th { text-align: left; padding: 14px 20px; font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; background: #f8f9fc; border-bottom: 1px solid #f0f0f0; }
.grade-table td { padding: 14px 20px; border-bottom: 1px solid #f8f9fc; font-size: 14px; vertical-align: middle; }
.grade-table tr:last-child td { border-bottom: none; }
.grade-table tr:hover td { background: #fafbff; }
.name-cell { display: flex; align-items: center; gap: 10px; }
.avatar-sm { width: 34px; height: 34px; border-radius: 50%; object-fit: cover; }
.badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; }
.badge-pending { background: #f4f6f9; color: var(--text-muted); }
.badge-done    { background: #ede9ff; color: var(--primary); }
.edit-btn { background: #f4f6f9; border: none; padding: 7px 14px; border-radius: 8px; font-size: 12px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 5px; font-family:'Inter',sans-serif; }
.edit-btn:hover { background: #ede9ff; color: var(--primary); }
.pct-display { font-weight: 700; font-size: 14px; }
.empty-state { text-align: center; padding: 60px 0; color: var(--text-muted); }
.empty-state i { font-size: 50px; display: block; margin-bottom: 12px; }
.select-prompt { background: #fff; border-radius: 16px; box-shadow: var(--shadow); padding: 60px; text-align: center; color: var(--text-muted); }
.select-prompt i { font-size: 60px; display: block; margin-bottom: 16px; color: #ddd; }

/* Edit modal */
.modal-backdrop { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; }
.modal-backdrop.show { display: flex; }
.modal { background: #fff; border-radius: 20px; padding: 32px; width: 100%; max-width: 400px; }
.modal-title { font-size: 18px; font-weight: 700; color: var(--text-main); margin-bottom: 6px; }
.modal-sub   { font-size: 13px; color: var(--text-muted); margin-bottom: 24px; }
.form-group  { margin-bottom: 18px; }
.form-label  { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: var(--text-main); }
.form-input  { width: 100%; padding: 11px 14px; border: 1.5px solid #e6e9ef; border-radius: 10px; font-size: 14px; font-family:'Inter',sans-serif; outline: none; }
.form-input:focus { border-color: var(--primary); }
.modal-btns  { display: flex; gap: 10px; }
.btn-cancel  { flex: 1; padding: 12px; border-radius: 10px; border: 1.5px solid #e6e9ef; background: #fff; font-weight: 600; cursor: pointer; font-family:'Inter',sans-serif; }
.btn-save    { flex: 1; padding: 12px; border-radius: 10px; border: none; background: var(--primary); color: #fff; font-weight: 700; cursor: pointer; font-family:'Inter',sans-serif; }
</style>

<div class="page-content">
  <div class="page-hd">
    <div class="page-hd-left">
      <h1>Grades</h1>
      <p><?= $user['role']==='teacher' ? 'View and edit student quiz grades.' : 'Your quiz grades across enrolled classes.' ?></p>
    </div>
  </div>

  <?php if ($user['role'] === 'teacher'): ?>
    <!-- Teacher view -->
    <div class="filter-row">
      <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:center">
        <select name="class_id" class="form-select" onchange="this.form.submit()">
          <option value="">-- Select Class --</option>
          <?php foreach ($classes as $c): ?>
            <option value="<?= $c['id'] ?>" <?= $classId==$c['id']?'selected':'' ?>><?= h($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
        <?php if ($selectedClass && !empty($quizzes)): ?>
        <select name="quiz_id" class="form-select" onchange="this.form.submit()">
          <option value="">-- Select Quiz --</option>
          <?php foreach ($quizzes as $q): ?>
            <option value="<?= $q['id'] ?>" <?= $quizId==$q['id']?'selected':'' ?>><?= h($q['title']) ?></option>
          <?php endforeach; ?>
        </select>
        <?php endif; ?>
        <?php if ($classId): ?><input type="hidden" name="class_id" value="<?= $classId ?>"> <?php endif; ?>
      </form>
    </div>

    <?php if ($selectedQuiz && !empty($studentGrades)): ?>
      <?php
        $done  = array_filter($studentGrades, fn($g) => $g['attempt_count'] > 0 || $g['manual_grade'] !== null);
        $total = count($studentGrades);
        $avgPct = 0;
        $graded = 0;
        foreach ($studentGrades as $g) {
            $grade = $g['manual_grade'] !== null ? $g['manual_grade'] : ($g['max_score'] > 0 ? $g['best_score'] : null);
            $max   = $g['max_score'] > 0 ? $g['max_score'] : 100;
            if ($grade !== null) { $avgPct += ($grade / $max * 100); $graded++; }
        }
        $avgPct = $graded > 0 ? round($avgPct / $graded) : 0;
      ?>
      <div class="stats-row">
        <div class="stat-card"><div class="stat-val"><?= $total ?></div><div class="stat-lbl">Students</div></div>
        <div class="stat-card"><div class="stat-val"><?= count($done) ?></div><div class="stat-lbl">Attempted</div></div>
        <div class="stat-card"><div class="stat-val" style="color:<?= gradeColor($avgPct) ?>"><?= $avgPct ?>%</div><div class="stat-lbl">Avg Grade</div></div>
      </div>

      <table class="grade-table">
        <thead><tr><th>Student</th><th>Status</th><th>Score</th><th>Grade</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($studentGrades as $g): ?>
            <?php
            $rawScore  = $g['best_score'];
            $rawMax    = $g['max_score'] ?: 100;
            $finalGrade = $g['manual_grade'] !== null ? $g['manual_grade'] : $rawScore;
            $pct        = $g['attempt_count'] > 0 ? round(($finalGrade / $rawMax) * 100) : null;
            $done       = $g['attempt_count'] > 0 || $g['manual_grade'] !== null;
            ?>
            <tr>
              <td><div class="name-cell">
                <img src="<?= SITE_ROOT . ($g['profile_pic'] ?: 'assets/default-avatar.jpg') ?>" alt="" class="avatar-sm" onerror="this.src='<?= SITE_ROOT ?>assets/default-avatar.jpg'">
                <div><div style="font-weight:600"><?= h($g['name']) ?></div><div style="font-size:12px;color:var(--text-muted)"><?= h($g['username']) ?></div></div>
              </div></td>
              <td><span class="badge <?= $done?'badge-done':'badge-pending' ?>"><?= $done ? ($g['manual_grade']!==null?'Graded':'Completed') : 'Pending' ?></span></td>
              <td><?= $g['attempt_count'] > 0 ? h($rawScore).' / '.h($rawMax) : '—' ?></td>
              <td>
                <?php if ($pct !== null): ?>
                  <span class="pct-display" style="color:<?= gradeColor($pct) ?>"><?= $pct ?>% <small>(<?= letterGrade($pct) ?>)</small></span>
                <?php else: ?>—<?php endif; ?>
              </td>
              <td><button class="edit-btn" onclick="openEdit(<?= $g['id'] ?>, '<?= h(addslashes($g['name'])) ?>', <?= $quizId ?>, <?= $finalGrade ?? 'null' ?>, '<?= h(addslashes($g['note']??'')) ?>')"><i class='bx bx-edit'></i> Edit</button></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

    <?php elseif ($selectedClass): ?>
      <div class="select-prompt"><i class='bx bx-notepad'></i>Select a quiz above to view student grades.</div>
    <?php else: ?>
      <div class="select-prompt"><i class='bx bx-book'></i>Select a class above to get started.</div>
    <?php endif; ?>

  <?php else: ?>
    <!-- Student view -->
    <div class="filter-row">
      <form method="GET" style="display:flex;gap:12px;align-items:center">
        <select name="class_id" class="form-select" onchange="this.form.submit()">
          <?php foreach ($classes as $c): ?>
            <option value="<?= $c['id'] ?>" <?= $classId==$c['id']?'selected':'' ?>><?= h($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </form>
    </div>

    <?php if (!empty($myGrades)): ?>
      <?php
        $totalPct = 0; $done = 0;
        foreach ($myGrades as $g) {
            $grade = $g['manual_grade'] !== null ? $g['manual_grade'] : $g['best_score'];
            $max   = $g['max_score'] ?: 100;
            if ($grade !== null && $g['attempt_count'] > 0) { $totalPct += ($grade / $max * 100); $done++; }
        }
        $avg = $done > 0 ? round($totalPct / $done) : 0;
      ?>
      <div class="stats-row">
        <div class="stat-card"><div class="stat-val"><?= count($myGrades) ?></div><div class="stat-lbl">Total Quizzes</div></div>
        <div class="stat-card"><div class="stat-val"><?= $done ?></div><div class="stat-lbl">Completed</div></div>
        <div class="stat-card"><div class="stat-val" style="color:<?= gradeColor($avg) ?>"><?= $avg ?>%</div><div class="stat-lbl">Your Average</div></div>
      </div>

      <table class="grade-table">
        <thead><tr><th>Quiz</th><th>Type</th><th>Status</th><th>Score</th><th>Grade</th></tr></thead>
        <tbody>
          <?php foreach ($myGrades as $g): ?>
            <?php
            $grade = $g['manual_grade'] !== null ? $g['manual_grade'] : $g['best_score'];
            $max   = $g['max_score'] ?: 100;
            $pct   = ($g['attempt_count'] > 0 && $grade !== null) ? round(($grade / $max) * 100) : null;
            ?>
            <tr>
              <td style="font-weight:600"><?= h($g['title']) ?></td>
              <td><?= quizTypeLabel($g['type']) ?></td>
              <td><span class="badge <?= $g['attempt_count']>0?'badge-done':'badge-pending' ?>"><?= $g['attempt_count']>0?'Completed':'Pending' ?></span></td>
              <td><?= $g['attempt_count']>0 ? h($grade).' / '.h($max) : '—' ?></td>
              <td>
                <?php if ($pct !== null): ?>
                  <span class="pct-display" style="color:<?= gradeColor($pct) ?>"><?= $pct ?>% (<?= letterGrade($pct) ?>)</span>
                <?php else: ?>—<?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <div class="empty-state"><i class='bx bx-notepad'></i>No quizzes found for this class.</div>
    <?php endif; ?>
  <?php endif; ?>
</div>

<!-- Edit Grade Modal (teacher only) -->
<?php if ($user['role']==='teacher'): ?>
<div class="modal-backdrop" id="editModal">
  <div class="modal">
    <div class="modal-title">Edit Grade</div>
    <div class="modal-sub" id="editModalSub"></div>
    <form id="editForm">
      <input type="hidden" id="editStudentId">
      <input type="hidden" id="editQuizId">
      <div class="form-group">
        <label class="form-label">Grade Score</label>
        <input type="number" class="form-input" id="editGrade" min="0" step="0.5" placeholder="e.g. 85">
      </div>
      <div class="form-group">
        <label class="form-label">Note (optional)</label>
        <textarea class="form-input" id="editNote" rows="2" placeholder="Add a note for the student..."></textarea>
      </div>
      <div class="modal-btns">
        <button type="button" class="btn-cancel" onclick="closeEditModal()">Cancel</button>
        <button type="submit" class="btn-save">Save Grade</button>
      </div>
    </form>
  </div>
</div>

<script>
function openEdit(studentId, studentName, quizId, currentGrade, currentNote) {
    document.getElementById('editStudentId').value = studentId;
    document.getElementById('editQuizId').value    = quizId;
    document.getElementById('editGrade').value     = currentGrade || '';
    document.getElementById('editNote').value      = currentNote || '';
    document.getElementById('editModalSub').textContent = 'Editing grade for ' + studentName;
    document.getElementById('editModal').classList.add('show');
}
function closeEditModal() { document.getElementById('editModal').classList.remove('show'); }

document.getElementById('editForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = this.querySelector('.btn-save'); btn.disabled=true; btn.textContent='Saving...';
    fetch('<?= SITE_ROOT ?>api/edit-grade.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({
            student_id: parseInt(document.getElementById('editStudentId').value),
            quiz_id:    parseInt(document.getElementById('editQuizId').value),
            grade:      parseFloat(document.getElementById('editGrade').value),
            note:       document.getElementById('editNote').value.trim(),
        })
    }).then(r=>r.json()).then(d => {
        if (d.success) location.reload();
        else { alert(d.error||'Failed'); btn.disabled=false; btn.textContent='Save Grade'; }
    });
});
document.getElementById('editModal').addEventListener('click', (e) => { if (e.target === e.currentTarget) closeEditModal(); });
</script>
<?php endif; ?>

<script>
function toggleDropdown() { document.getElementById("profileDropdown").classList.toggle("show"); }
window.addEventListener('click', (e) => { if (!e.target.closest('.profile-container')) document.getElementById("profileDropdown")?.classList.remove("show"); });
</script>
</div></body></html>
