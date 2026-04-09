<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Create — Quiz Carnival';
require_once __DIR__ . '/includes/header-sidebar.php';
requireTeacher();

$pdo = getDB();
$uid = $user['id'];
$cid = (int)($_GET['class_id'] ?? 0);

// Load teacher's classes
$myClasses = $pdo->prepare("SELECT c.*, (SELECT COUNT(*) FROM quizzes WHERE class_id=c.id) as quiz_count FROM classes c WHERE c.teacher_id=? ORDER BY c.created_at DESC");
$myClasses->execute([$uid]); $myClasses = $myClasses->fetchAll();

$colors = classColors();
?>
<style>
.page-content { padding: 36px 40px; flex: 1; }
.page-hd { margin-bottom: 32px; }
.page-hd h1 { font-size: 24px; font-weight: 700; color: var(--text-main); }
.page-hd p  { font-size: 14px; color: var(--text-muted); margin-top: 4px; }
.create-btn-big {
  display: flex; align-items: center; gap: 16px;
  background: #fff; border-radius: 16px; padding: 24px 28px;
  border: 2px dashed #e6e9ef; cursor: pointer; transition: 0.2s;
  margin-bottom: 32px; max-width: 480px;
}
.create-btn-big:hover { border-color: var(--primary); background: #fafbff; }
.create-btn-icon { width: 54px; height: 54px; border-radius: 14px; background: #ede9ff; color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 26px; flex-shrink: 0; }
.create-btn-text h3 { font-size: 16px; font-weight: 700; color: var(--text-main); margin-bottom: 2px; }
.create-btn-text p  { font-size: 13px; color: var(--text-muted); }
.section-title { font-size: 17px; font-weight: 700; color: var(--text-main); margin-bottom: 18px; }
.classes-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 18px; }
.class-card { background: #fff; border-radius: 14px; overflow: hidden; box-shadow: var(--shadow); text-decoration: none; transition: 0.2s; display: block; }
.class-card:hover { transform: translateY(-3px); box-shadow: 0 8px 28px rgba(0,0,0,0.1); }
.class-banner { height: 80px; display: flex; align-items: center; justify-content: center; font-size: 32px; color: rgba(255,255,255,0.8); }
.class-body { padding: 16px 20px; }
.class-name { font-size: 14px; font-weight: 700; color: var(--text-main); margin-bottom: 4px; }
.class-meta { font-size: 12px; color: var(--text-muted); display: flex; gap: 10px; }
.class-actions { padding: 12px 20px; border-top: 1px solid #f4f6f9; display: flex; gap: 8px; }
.btn-sm { padding: 6px 14px; border-radius: 8px; font-size: 12px; font-weight: 600; border: none; cursor: pointer; font-family:'Inter',sans-serif; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; }
.btn-add-quiz { background: var(--primary); color: #fff; }
.btn-add-quiz:hover { background: #3310cc; }
.btn-visit { background: #f4f6f9; color: var(--text-main); }
.btn-visit:hover { background: #e6e9ef; }
.empty-classes { text-align: center; padding: 60px 0; color: var(--text-muted); }

/* Modal */
.modal-backdrop { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; }
.modal-backdrop.show { display: flex; }
.modal { background: #fff; border-radius: 20px; padding: 32px; width: 100%; max-width: 520px; box-shadow: 0 20px 60px rgba(0,0,0,0.2); position: relative; }
.modal-close { position: absolute; top: 16px; right: 16px; background: #f4f6f9; border: none; border-radius: 8px; width: 32px; height: 32px; font-size: 18px; cursor: pointer; display: flex; align-items: center; justify-content: center; }
.modal-close:hover { background: #e6e9ef; }
.modal-title { font-size: 18px; font-weight: 700; color: var(--text-main); margin-bottom: 6px; }
.modal-sub { font-size: 13px; color: var(--text-muted); margin-bottom: 24px; }
.form-group { margin-bottom: 18px; }
.form-label { display: block; font-size: 13px; font-weight: 600; color: var(--text-main); margin-bottom: 6px; }
.form-input { width: 100%; padding: 11px 14px; border: 1.5px solid #e6e9ef; border-radius: 10px; font-size: 14px; font-family:'Inter',sans-serif; outline: none; transition: 0.2s; }
.form-input:focus { border-color: var(--primary); }
.color-row { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 6px; }
.color-dot { width: 32px; height: 32px; border-radius: 50%; cursor: pointer; border: 3px solid transparent; transition: 0.15s; }
.color-dot.selected { border-color: #fff; box-shadow: 0 0 0 3px rgba(0,0,0,0.3); }
.btn-submit { background: var(--primary); color: #fff; border: none; width: 100%; padding: 13px; border-radius: 10px; font-size: 15px; font-weight: 700; cursor: pointer; font-family:'Inter',sans-serif; transition: 0.2s; margin-top: 4px; }
.btn-submit:hover { background: #3310cc; }
.error-msg { background: #fee2e2; color: #b91c1c; border-radius: 8px; padding: 10px 14px; font-size: 13px; margin-bottom: 14px; display: none; }
.success-msg { background: #e0f7f4; color: #00897b; border-radius: 8px; padding: 10px 14px; font-size: 13px; margin-bottom: 14px; display: none; }

/* Quiz modal */
.quiz-type-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 8px; }
.quiz-type-card {
  border: 2px solid #e6e9ef; border-radius: 12px; padding: 16px; cursor: pointer;
  transition: 0.2s; text-align: center; position: relative;
}
.quiz-type-card:hover:not(.disabled) { border-color: var(--primary); background: #fafbff; }
.quiz-type-card.selected { border-color: var(--primary); background: #ede9ff; }
.quiz-type-card.disabled { opacity: 0.4; cursor: not-allowed; }
.quiz-type-card .qt-icon { font-size: 28px; margin-bottom: 6px; }
.quiz-type-card h4 { font-size: 13px; font-weight: 700; color: var(--text-main); margin-bottom: 2px; }
.quiz-type-card p { font-size: 11px; color: var(--text-muted); }
.soon-badge { position: absolute; top: 8px; right: 8px; background: #f4f6f9; color: var(--text-muted); font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 20px; }
</style>

<div class="page-content">
  <div class="page-hd">
    <h1>Create</h1>
    <p>Create new classes and quizzes for your students.</p>
  </div>

  <!-- Create class button -->
  <button class="create-btn-big" onclick="openCreateClass()">
    <div class="create-btn-icon"><i class='bx bx-plus'></i></div>
    <div class="create-btn-text">
      <h3>Create a New Class</h3>
      <p>Set up a class for your students to enrol in</p>
    </div>
  </button>

  <!-- My classes with quiz creation -->
  <?php if (!empty($myClasses)): ?>
  <div class="section-title">Your Classes — Add Quizzes</div>
  <div class="classes-grid">
    <?php foreach ($myClasses as $cls): ?>
    <div class="class-card">
      <div class="class-banner" style="background:<?= h($cls['color']) ?>"><i class='bx bx-book' style="color:rgba(255,255,255,0.7)"></i></div>
      <div class="class-body">
        <div class="class-name"><?= h($cls['name']) ?></div>
        <div class="class-meta">
          <span><i class='bx bx-notepad'></i> <?= $cls['quiz_count'] ?> quiz<?= $cls['quiz_count']!=1?'zes':'' ?></span>
        </div>
      </div>
      <div class="class-actions">
        <button class="btn-sm btn-add-quiz" onclick="openAddQuiz(<?= $cls['id'] ?>, '<?= h(addslashes($cls['name'])) ?>')"><i class='bx bx-plus'></i> Add Quiz</button>
        <a href="<?= SITE_ROOT ?>class.php?id=<?= $cls['id'] ?>" class="btn-sm btn-visit"><i class='bx bx-link-external'></i> Open</a>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php else: ?>
  <div class="empty-classes">
    <i class='bx bx-book-open' style="font-size:50px;display:block;margin-bottom:12px"></i>
    <p>No classes yet. Create your first class above.</p>
  </div>
  <?php endif; ?>
</div>

<!-- ─── Create Class Modal ─── -->
<div class="modal-backdrop" id="createClassModal">
  <div class="modal">
    <button class="modal-close" onclick="closeModal('createClassModal')"><i class='bx bx-x'></i></button>
    <div class="modal-title">Create a New Class</div>
    <div class="modal-sub">Fill in the details below to set up your class.</div>
    <div class="error-msg" id="classError"></div>
    <div class="success-msg" id="classSuccess"></div>
    <form id="createClassForm">
      <div class="form-group">
        <label class="form-label">Class Name *</label>
        <input type="text" class="form-input" name="name" placeholder="e.g. Mathematics Grade 10" required>
      </div>
      <div class="form-group">
        <label class="form-label">Description</label>
        <textarea class="form-input" name="description" rows="3" placeholder="What is this class about?" style="resize:vertical"></textarea>
      </div>
      <div class="form-group">
        <label class="form-label">Class Color</label>
        <div class="color-row" id="colorRow">
          <?php foreach ($colors as $i => $c): ?>
            <div class="color-dot <?= $i===0?'selected':'' ?>" style="background:<?= h($c) ?>" data-color="<?= h($c) ?>" onclick="selectColor(this)"></div>
          <?php endforeach; ?>
        </div>
        <input type="hidden" name="color" id="selectedColor" value="<?= h($colors[0]) ?>">
      </div>
      <button type="submit" class="btn-submit">Create Class</button>
    </form>
  </div>
</div>

<!-- ─── Add Quiz Modal ─── -->
<div class="modal-backdrop" id="addQuizModal">
  <div class="modal">
    <button class="modal-close" onclick="closeModal('addQuizModal')"><i class='bx bx-x'></i></button>
    <div class="modal-title">Add Quiz to <span id="quizModalClassName"></span></div>
    <div class="modal-sub">Choose a quiz type to open the creator in a new tab.</div>
    <input type="hidden" id="quizModalClassId">
    <div class="quiz-type-grid">
      <div class="quiz-type-card disabled" onclick="" title="Coming Soon">
        <div class="soon-badge">Soon</div>
        <div class="qt-icon"><i class='bx bxs-invader' style="color:#d81b60"></i></div>
        <h4>Spelling Bee</h4>
        <p>Listen and spell</p>
      </div>
      <div class="quiz-type-card" onclick="openQuizCreator('wordsearch')">
        <div class="qt-icon"><i class='bx bx-search-alt-2' style="color:var(--primary)"></i></div>
        <h4>Word Search</h4>
        <p>Find hidden words in a grid</p>
      </div>
      <div class="quiz-type-card" onclick="openQuizCreator('fillinblanks')">
        <div class="qt-icon"><i class='bx bx-color-fill' style="color:#00897b"></i></div>
        <h4>Fill in the Blanks</h4>
        <p>Complete the sentences</p>
      </div>
      <div class="quiz-type-card" onclick="openQuizCreator('hangman')">
        <div class="qt-icon"><i class='bx bx-male' style="color:#f59e0b"></i></div>
        <h4>Hangman</h4>
        <p>Guess the hidden word</p>
      </div>
    </div>
  </div>
</div>

<script>
function openCreateClass() { document.getElementById('createClassModal').classList.add('show'); }
function closeModal(id) { document.getElementById(id).classList.remove('show'); }

function selectColor(el) {
    document.querySelectorAll('.color-dot').forEach(d => d.classList.remove('selected'));
    el.classList.add('selected');
    document.getElementById('selectedColor').value = el.dataset.color;
}

let currentClassId = null;
function openAddQuiz(classId, className) {
    currentClassId = classId;
    document.getElementById('quizModalClassId').value = classId;
    document.getElementById('quizModalClassName').textContent = className;
    document.getElementById('addQuizModal').classList.add('show');
}

function openQuizCreator(type) {
    const classId = currentClassId;
    const urls = {
        'wordsearch':   '<?= SITE_ROOT ?>quiz/word-search/create.php',
        'fillinblanks': '<?= SITE_ROOT ?>quiz/fill-in-blanks/create.php',
        'hangman':      '<?= SITE_ROOT ?>quiz/hangman/create.php',
    };
    window.open(urls[type] + '?class_id=' + classId, '_blank');
    closeModal('addQuizModal');
}

// Close modals on backdrop click
document.querySelectorAll('.modal-backdrop').forEach(el => {
    el.addEventListener('click', (e) => { if (e.target === el) el.classList.remove('show'); });
});

// Create class form
document.getElementById('createClassForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const errEl = document.getElementById('classError');
    const sucEl = document.getElementById('classSuccess');
    errEl.style.display = 'none'; sucEl.style.display = 'none';
    const data = {
        name:        this.name.value.trim(),
        description: this.description.value.trim(),
        color:       document.getElementById('selectedColor').value,
    };
    if (!data.name) { errEl.textContent = 'Class name is required.'; errEl.style.display='block'; return; }
    const btn = this.querySelector('.btn-submit');
    btn.disabled = true; btn.textContent = 'Creating...';
    fetch('<?= SITE_ROOT ?>api/create-class.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify(data)
    }).then(r=>r.json()).then(d => {
        if (d.success) {
            sucEl.textContent = 'Class created! Redirecting...';
            sucEl.style.display='block';
            setTimeout(() => window.location.href = '<?= SITE_ROOT ?>class.php?id='+d.class_id, 1200);
        } else {
            errEl.textContent = d.error || 'Failed to create class.';
            errEl.style.display='block';
            btn.disabled=false; btn.textContent='Create Class';
        }
    });
});

function toggleDropdown() { document.getElementById("profileDropdown").classList.toggle("show"); }
window.addEventListener('click', (e) => { if (!e.target.closest('.profile-container')) document.getElementById("profileDropdown")?.classList.remove("show"); });
</script>
</div></body></html>
