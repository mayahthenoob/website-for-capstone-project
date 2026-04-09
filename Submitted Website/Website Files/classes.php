<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Classes — Quiz Carnival';
require_once __DIR__ . '/includes/header-sidebar.php';

$pdo = getDB();
$uid = $user['id'];

// All classes (all teachers)
$classes = $pdo->query("SELECT c.*, u.name as teacher_name, (SELECT COUNT(*) FROM enrollments WHERE class_id=c.id) as enrolled_count FROM classes c JOIN users u ON c.teacher_id=u.id ORDER BY c.created_at DESC")->fetchAll();

// Which classes is current student enrolled in?
$enrolledIds = [];
if ($user['role'] === 'student') {
    $stmt = $pdo->prepare("SELECT class_id FROM enrollments WHERE student_id=?");
    $stmt->execute([$uid]);
    $enrolledIds = array_column($stmt->fetchAll(), 'class_id');
}
?>
<style>
.page-content { padding: 36px 40px; flex: 1; }
.page-hd { margin-bottom: 28px; }
.page-hd h1 { font-size: 24px; font-weight: 700; color: var(--text-main); }
.page-hd p  { font-size: 14px; color: var(--text-muted); margin-top: 4px; }
.top-bar { display: flex; gap: 12px; margin-bottom: 24px; align-items: center; }
.search-box { flex: 1; max-width: 380px; position: relative; }
.search-box i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); font-size: 18px; color: var(--text-muted); }
.search-box input { width: 100%; padding: 10px 14px 10px 40px; border: 1.5px solid #e6e9ef; border-radius: 10px; font-size: 14px; font-family: 'Inter',sans-serif; outline: none; transition: 0.2s; }
.search-box input:focus { border-color: var(--primary); }
.classes-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; }
.class-card { background: #fff; border-radius: 16px; overflow: hidden; box-shadow: var(--shadow); transition: 0.2s; text-decoration: none; display: flex; flex-direction: column; }
.class-card:hover { transform: translateY(-3px); box-shadow: 0 8px 30px rgba(0,0,0,0.1); }
.class-banner { height: 100px; display: flex; align-items: center; justify-content: center; font-size: 40px; color: rgba(255,255,255,0.8); }
.class-body { padding: 18px 20px; flex: 1; }
.class-name { font-size: 15px; font-weight: 700; color: var(--text-main); margin-bottom: 4px; }
.class-teacher { font-size: 13px; color: var(--text-muted); }
.class-footer { padding: 12px 20px; border-top: 1px solid #f4f6f9; display: flex; align-items: center; justify-content: space-between; }
.enroll-count { font-size: 12px; color: var(--text-muted); }
.btn-enroll { padding: 6px 16px; border-radius: 20px; border: none; font-size: 12px; font-weight: 700; cursor: pointer; transition: 0.2s; font-family: 'Inter',sans-serif; }
.btn-enroll.enrolled { background: #e0f7f4; color: #00897b; }
.btn-enroll.enroll   { background: var(--primary); color: #fff; }
.btn-enroll.enroll:hover { background: #3310cc; }
.btn-visit { padding: 6px 16px; border-radius: 20px; border: none; font-size: 12px; font-weight: 700; cursor: pointer; background: #ede9ff; color: var(--primary); font-family: 'Inter',sans-serif; text-decoration: none; display: inline-block; }
.no-results { text-align: center; color: var(--text-muted); padding: 60px 0; font-size: 15px; }
.no-results i { font-size: 50px; display: block; margin-bottom: 12px; }
</style>

<div class="page-content">
  <div class="page-hd">
    <h1>All Classes</h1>
    <p>Browse all available classes and enrol in the ones you want to join.</p>
  </div>

  <div class="top-bar">
    <div class="search-box">
      <i class='bx bx-search'></i>
      <input type="text" id="searchInput" placeholder="Search classes..." oninput="filterCards()">
    </div>
  </div>

  <div class="classes-grid" id="classesGrid">
    <?php if (empty($classes)): ?>
      <div class="no-results" style="grid-column:1/-1"><i class='bx bx-book-open'></i>No classes available yet.</div>
    <?php else: foreach ($classes as $cls): ?>
      <div class="class-card-wrap" data-name="<?= h(strtolower($cls['name'] . ' ' . $cls['teacher_name'])) ?>">
        <div class="class-card">
          <div class="class-banner" style="background:<?= h($cls['color']) ?>">
            <i class='bx bx-book'></i>
          </div>
          <div class="class-body">
            <div class="class-name"><?= h($cls['name']) ?></div>
            <div class="class-teacher">by <?= h($cls['teacher_name']) ?></div>
          </div>
          <div class="class-footer">
            <span class="enroll-count"><i class='bx bx-group'></i> <?= $cls['enrolled_count'] ?></span>
            <?php if ($user['role'] === 'student'): ?>
              <?php if (in_array($cls['id'], $enrolledIds)): ?>
                <a href="<?= SITE_ROOT ?>class.php?id=<?= $cls['id'] ?>" class="btn-visit">Visit</a>
              <?php else: ?>
                <button class="btn-enroll enroll" onclick="enrol(<?= $cls['id'] ?>, this)">Enrol</button>
              <?php endif; ?>
            <?php else: ?>
              <a href="<?= SITE_ROOT ?>class.php?id=<?= $cls['id'] ?>" class="btn-visit">Visit</a>
            <?php endif; ?>
          </div>
        </div>
      </div>
    <?php endforeach; endif; ?>
  </div>
  <div class="no-results" id="noResults" style="display:none"><i class='bx bx-search-alt'></i>No classes found matching your search.</div>
</div>

<script>
function filterCards() {
    const q = document.getElementById("searchInput").value.toLowerCase().trim();
    let visible = 0;
    document.querySelectorAll('.class-card-wrap').forEach(c => {
        const match = c.dataset.name.includes(q);
        c.style.display = match ? '' : 'none';
        if (match) visible++;
    });
    document.getElementById("noResults").style.display = visible === 0 ? 'block' : 'none';
}

function enrol(classId, btn) {
    btn.disabled = true; btn.textContent = '...';
    fetch('<?= SITE_ROOT ?>api/enroll.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({class_id: classId})
    }).then(r => r.json()).then(d => {
        if (d.success) {
            btn.className = 'btn-enroll enrolled';
            btn.textContent = 'Enrolled';
        } else {
            btn.disabled = false; btn.textContent = 'Enrol';
            alert(d.error || 'Failed to enrol.');
        }
    });
}

function toggleDropdown() { document.getElementById("profileDropdown").classList.toggle("show"); }
window.addEventListener('click', (e) => { if (!e.target.closest('.profile-container')) document.getElementById("profileDropdown")?.classList.remove("show"); });
</script>
</div></body></html>
