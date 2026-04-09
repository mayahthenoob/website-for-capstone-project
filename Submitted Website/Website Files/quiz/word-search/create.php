<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
if (session_status() === PHP_SESSION_NONE) session_start();
requireTeacher();

$pdo     = getDB();
$uid     = currentUser()['id'];
$classId = (int)($_GET['class_id'] ?? 0);
$quizId  = (int)($_GET['quiz_id']  ?? 0);

// Verify teacher owns class
if ($classId) {
    $cls = $pdo->prepare("SELECT * FROM classes WHERE id=? AND teacher_id=?"); $cls->execute([$classId, $uid]); $cls = $cls->fetch();
    if (!$cls) { echo "<p>Access denied.</p>"; exit; }
}

// Load existing quiz if editing
$quiz = null;
$quizDataParsed = null;
if ($quizId) {
    $stmt = $pdo->prepare("SELECT * FROM quizzes WHERE id=? AND type='wordsearch'"); $stmt->execute([$quizId]); $quiz = $stmt->fetch();
    if ($quiz) { $quizDataParsed = json_decode($quiz['quiz_data'] ?? '{}', true); $classId = $quiz['class_id']; }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Create Word Search — Quiz Carnival</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
<style>
:root { --brand:#4318FF; --brand2:#7c3a91; --bg:#f4f6f9; --surface:#fff; --border:#e8e0f0; --text:#1a1025; --muted:#8b7da0; --accent:#00c896; }
* { box-sizing:border-box; margin:0; padding:0; }
body { font-family:'DM Sans',sans-serif; background:var(--bg); color:var(--text); min-height:100vh; }
header { background:linear-gradient(135deg,#1a0a2e,#2d1354); padding:0 32px; height:64px; display:flex; align-items:center; gap:12px; position:sticky; top:0; z-index:100; box-shadow:0 2px 20px rgba(124,58,145,0.3); }
.logo-mark { width:36px;height:36px;background:linear-gradient(135deg,#f04e84,var(--brand2));border-radius:10px;display:flex;align-items:center;justify-content:center;font-family:'Syne',sans-serif;font-weight:800;font-size:16px;color:#fff; }
header span { font-family:'Syne',sans-serif;font-weight:700;font-size:1.1rem;color:#fff; }
.hdr-spacer { flex:1; }
.back-link { color:rgba(255,255,255,0.7);text-decoration:none;font-size:13px;display:flex;align-items:center;gap:6px; }
.back-link:hover { color:#fff; }
.page-wrap { max-width:860px;margin:40px auto;padding:0 20px 60px; }
.page-title { font-family:'Syne',sans-serif;font-size:2.2rem;font-weight:800;background:linear-gradient(135deg,#4318FF,var(--brand2));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;margin-bottom:8px; }
.page-sub { color:var(--muted);font-size:0.95rem;margin-bottom:36px; }
.card { background:var(--surface);border:1px solid var(--border);border-radius:16px;padding:32px;margin-bottom:20px;box-shadow:0 2px 16px rgba(67,24,255,0.06); }
.card-title { font-family:'Syne',sans-serif;font-weight:700;font-size:1rem;color:var(--brand);text-transform:uppercase;letter-spacing:1px;margin-bottom:20px;padding-bottom:12px;border-bottom:2px solid var(--bg); }
.field-grid { display:grid;grid-template-columns:1fr 1fr;gap:20px; }
.field { display:flex;flex-direction:column;gap:6px; }
.field.full { grid-column:span 2; }
label { font-size:0.82rem;font-weight:500;color:var(--muted);text-transform:uppercase;letter-spacing:0.5px; }
input[type=text],input[type=number],input[type=datetime-local],textarea { padding:11px 14px;border:1.5px solid var(--border);border-radius:10px;font-family:'DM Sans',sans-serif;font-size:0.95rem;color:var(--text);background:var(--bg);outline:none;transition:0.2s;width:100%; }
input:focus,textarea:focus { border-color:var(--brand);box-shadow:0 0 0 3px rgba(67,24,255,0.12); }
textarea { min-height:80px;resize:vertical; }
.hint-text { font-size:0.8rem;color:var(--muted);margin-top:4px; }
.radio-row { display:flex;flex-wrap:wrap;gap:10px;margin-top:6px; }
.radio-chip { display:flex;align-items:center;gap:7px;padding:8px 16px;border:1.5px solid var(--border);border-radius:30px;cursor:pointer;font-size:0.88rem;font-weight:500;transition:0.2s;background:var(--bg);user-select:none; }
.radio-chip:has(input:checked) { border-color:var(--brand);background:rgba(67,24,255,0.08);color:var(--brand); }
.radio-chip input,.check-chip input { display:none; }
.check-chip { display:flex;align-items:center;gap:7px;padding:8px 16px;border:1.5px solid var(--border);border-radius:30px;cursor:pointer;font-size:0.88rem;font-weight:500;transition:0.2s;background:var(--bg);user-select:none; }
.check-chip:has(input:checked) { border-color:var(--accent);background:rgba(0,200,150,0.08);color:#009a72; }
.formatted-date { font-size:0.82rem;color:var(--brand2);margin-top:5px;font-weight:500; }
#wordsContainer { display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-top:8px; }
#wordsContainer input { text-align:center;font-weight:700;font-size:0.9rem;letter-spacing:2px; }
.btn-row { display:flex;justify-content:flex-end;gap:12px;margin-top:32px; }
.btn { padding:13px 32px;border:none;border-radius:12px;font-family:'Syne',sans-serif;font-weight:700;font-size:0.95rem;cursor:pointer;transition:0.2s; }
.btn-secondary { background:var(--bg);border:1.5px solid var(--border);color:var(--muted); }
.btn-primary { background:linear-gradient(135deg,var(--brand),var(--brand2));color:#fff;box-shadow:0 4px 20px rgba(67,24,255,0.35); }
.btn-primary:hover { transform:translateY(-1px); }
.alert { padding:12px 16px;border-radius:10px;margin-bottom:16px;font-size:14px; }
.alert-error { background:#fee2e2;color:#b91c1c; }
.alert-success { background:#e0f7f4;color:#00897b; }
#statusMsg { display:none; }
</style>
</head>
<body>
<header>
  <div class="logo-mark"><i class='bx bx-search-alt-2' style="font-size:16px;color:#fff"></i></div>
  <span>Word Search Creator</span>
  <div class="hdr-spacer"></div>
  <?php if ($classId): ?>
  <a href="<?= SITE_ROOT ?>class.php?id=<?= $classId ?>" class="back-link"><i class='bx bx-arrow-back'></i> Back to Class</a>
  <?php endif; ?>
</header>

<div class="page-wrap">
  <div class="page-title">Create Word Search</div>
  <div class="page-sub"><?= $quiz ? 'Edit this Word Search quiz.' : 'Add words to hide in the grid and configure your quiz settings.' ?></div>

  <div id="statusMsg" class="alert"></div>

  <div class="card">
    <div class="card-title">Basic Info</div>
    <div class="field-grid">
      <div class="field full"><label>Title</label><input type="text" id="title" placeholder="e.g. Animals Word Search" value="<?= h($quiz['title']??'') ?>"></div>
      <div class="field full">
        <label>Description</label>
        <textarea id="description" placeholder="Describe the quiz..."><?= h($quiz['description']??'') ?></textarea>
        <div class="radio-row" style="margin-top:10px">
          <label class="radio-chip"><input type="radio" name="descMode" value="default"> Default</label>
          <label class="radio-chip"><input type="radio" name="descMode" value="custom" checked> Custom</label>
        </div>
      </div>
      <div class="field"><label>Category</label><input type="text" id="category" placeholder="e.g. Animals" value="<?= h($quizDataParsed['category']??'') ?>"></div>
      <div class="field"><label>Number of Words (1–12)</label><input type="number" id="amount" min="1" max="12" placeholder="e.g. 8" value="<?= h($quizDataParsed['wordCount']??'') ?>"></div>
    </div>
  </div>

  <div class="card">
    <div class="card-title">Words to Hide</div>
    <div class="hint-text" style="margin-bottom:12px">Only letters allowed — words are automatically uppercased</div>
    <div id="wordsContainer"><div class="hint-text" style="color:var(--muted)">Enter the number of words above to get started.</div></div>
  </div>

  <div class="card">
    <div class="card-title">Quiz Settings</div>
    <div class="field-grid">
      <div class="field full">
        <label>Attempts Allowed</label>
        <div class="radio-row">
          <label class="radio-chip"><input type="radio" name="reattempt" value="1"> 1</label>
          <label class="radio-chip"><input type="radio" name="reattempt" value="2" checked> 2</label>
          <label class="radio-chip"><input type="radio" name="reattempt" value="3"> 3</label>
        </div>
      </div>
      <div class="field full">
        <label>Grading Criteria</label>
        <div class="radio-row">
          <label class="check-chip"><input type="checkbox" name="grading" value="highestScore" checked> Highest Score</label>
          <label class="check-chip"><input type="checkbox" name="grading" value="fastestTime"> Fastest Time</label>
        </div>
      </div>
      <div class="field"><label>Opens</label><input type="datetime-local" id="opens" value="<?= h($quiz['opens_at']?str_replace(' ','T',substr($quiz['opens_at'],0,16)):'') ?>"><div id="opensFormatted" class="formatted-date"></div></div>
      <div class="field"><label>Closes</label><input type="datetime-local" id="closes" value="<?= h($quiz['closes_at']?str_replace(' ','T',substr($quiz['closes_at'],0,16)):'') ?>"><div id="closesFormatted" class="formatted-date"></div></div>
      <div class="field full"><label>Time Limit (minutes)</label><input type="number" id="timeLimit" min="0" placeholder="e.g. 20" value="<?= h($quiz['time_limit']??'') ?>"><div id="totalTime" class="formatted-date"></div></div>
    </div>
  </div>

  <div class="btn-row">
    <button class="btn btn-secondary" type="button" onclick="history.back()">Cancel</button>
    <button class="btn btn-primary" type="button" onclick="publish()">
      <?= $quiz ? 'Save Changes' : 'Save Quiz' ?> →
    </button>
  </div>
</div>

<script>
const CLASS_ID = <?= $classId ?>;
const QUIZ_ID  = <?= $quizId ?>;
const EXISTING_WORDS = <?= json_encode($quizDataParsed['words'] ?? []) ?>;
const SITE_ROOT = '<?= SITE_ROOT ?>';

// Load existing words if editing
window.addEventListener('DOMContentLoaded', () => {
  if (EXISTING_WORDS.length > 0) {
    const amt = document.getElementById('amount');
    amt.value = EXISTING_WORDS.length;
    generateWordInputs(EXISTING_WORDS.length, EXISTING_WORDS);
  }

  // Attempts
  const reattempt = <?= (int)($quiz['attempts_allowed']??2) ?>;
  document.querySelectorAll('input[name=reattempt]').forEach(r => {
    if (parseInt(r.value) === reattempt) r.checked = true;
  });
});

const amountInput = document.getElementById("amount");
const wordsContainer = document.getElementById("wordsContainer");

amountInput.addEventListener("input", function() {
  let n = parseInt(this.value);
  if (isNaN(n) || n < 1) { wordsContainer.innerHTML = '<div class="hint-text" style="color:var(--muted)">Enter a valid number.</div>'; return; }
  if (n > 12) { n = 12; this.value = 12; }
  generateWordInputs(n, []);
});

function generateWordInputs(n, existing) {
  wordsContainer.innerHTML = "";
  for (let i = 1; i <= n; i++) {
    const inp = document.createElement("input");
    inp.type = "text"; inp.placeholder = "Word " + i;
    inp.value = existing[i-1] || '';
    inp.addEventListener("input", () => { inp.value = inp.value.toUpperCase().replace(/[^A-Z]/g, ''); });
    wordsContainer.appendChild(inp);
  }
}

document.querySelectorAll('input[name="descMode"]').forEach(r => {
  r.addEventListener("change", () => {
    const d = document.getElementById("description");
    if (r.value === "default" && r.checked) { d.value = "This is a word search puzzle that should be completed in the allotted time"; d.readOnly = true; }
    else if (r.value === "custom" && r.checked) { d.value = ""; d.readOnly = false; }
  });
});

function fmtDate(v) { if (!v) return ""; return new Date(v).toLocaleString('en-GB', {weekday:'long',day:'numeric',month:'long',year:'numeric',hour:'numeric',minute:'2-digit',hour12:true}); }
document.getElementById("opens").addEventListener("change", e => document.getElementById("opensFormatted").textContent = fmtDate(e.target.value));
document.getElementById("closes").addEventListener("change", e => document.getElementById("closesFormatted").textContent = fmtDate(e.target.value));
document.getElementById("timeLimit").addEventListener("input", function() {
  const m = parseInt(this.value);
  if (isNaN(m) || m < 0) { document.getElementById("totalTime").textContent = ""; return; }
  const h = Math.floor(m / 60), rem = m % 60;
  document.getElementById("totalTime").textContent = h > 0 ? `${h} hr${h>1?'s':''} ${rem>0?rem+' min':''}`.trim() : `${m} minute${m!==1?'s':''}`;
});

function publish() {
  const title = document.getElementById("title").value.trim();
  if (!title) { showMsg('Title is required.', false); return; }
  const wordInputs = document.querySelectorAll('#wordsContainer input');
  const words = Array.from(wordInputs).map(i => i.value.trim().toUpperCase()).filter(w => w.length >= 2);
  if (words.length < 2) { showMsg('Add at least 2 words.', false); return; }

  const reattemptEl = document.querySelector('input[name=reattempt]:checked');
  const gradingEls  = document.querySelectorAll('input[name=grading]:checked');
  const grading     = Array.from(gradingEls).map(e => e.value).join(',') || 'highestScore';

  const payload = {
    class_id: CLASS_ID, quiz_id: QUIZ_ID,
    title, description: document.getElementById("description").value.trim(),
    type: 'wordsearch',
    quiz_data: { words, category: document.getElementById("category").value.trim(), wordCount: words.length },
    opens_at:  document.getElementById("opens").value  || null,
    closes_at: document.getElementById("closes").value || null,
    time_limit: parseInt(document.getElementById("timeLimit").value) || null,
    attempts_allowed: reattemptEl ? parseInt(reattemptEl.value) : 2,
    grading_criteria: grading,
  };

  fetch(SITE_ROOT + 'api/create-quiz.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(payload) })
    .then(r => r.json()).then(d => {
      if (d.success) {
        showMsg('Quiz saved! You can close this tab.', true);
        setTimeout(() => { if (CLASS_ID) window.location.href = SITE_ROOT + 'class.php?id=' + CLASS_ID; }, 1500);
      } else { showMsg(d.error || 'Failed to save.', false); }
    }).catch(() => showMsg('Network error.', false));
}

function showMsg(msg, ok) {
  const el = document.getElementById('statusMsg');
  el.className = 'alert ' + (ok ? 'alert-success' : 'alert-error');
  el.textContent = msg; el.style.display = 'block';
  el.scrollIntoView({behavior:'smooth', block:'center'});
}
</script>
</body>
</html>
