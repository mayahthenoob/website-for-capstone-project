<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
if (session_status() === PHP_SESSION_NONE) session_start();
requireLogin();
if (currentUser()['role'] !== 'student') { header('Location: ' . SITE_ROOT . 'dashboard.php'); exit; }

$pdo    = getDB();
$uid    = currentUser()['id'];
$quizId = (int)($_GET['quiz_id'] ?? 0);

$stmt = $pdo->prepare("SELECT q.*, c.name as class_name, c.id as class_id FROM quizzes q JOIN classes c ON q.class_id=c.id JOIN enrollments e ON e.class_id=c.id WHERE q.id=? AND e.student_id=? AND q.published=1 AND q.type='fillinblanks'");
$stmt->execute([$quizId, $uid]); $quiz = $stmt->fetch();
if (!$quiz) { header('Location: ' . SITE_ROOT . 'dashboard.php'); exit; }

$attCount = $pdo->prepare("SELECT COUNT(*) FROM quiz_attempts WHERE quiz_id=? AND student_id=?");
$attCount->execute([$quizId, $uid]);
if ((int)$attCount->fetchColumn() >= $quiz['attempts_allowed']) { header('Location: ' . SITE_ROOT . 'quiz/fill-in-blanks/results.php?quiz_id='.$quizId); exit; }

$quizData = json_decode($quiz['quiz_data'] ?? '{}', true);
// Shuffle word bank
$sentences = $quizData['sentences'] ?? [];
$quizJson  = json_encode(['quiz_id' => $quiz['id'], 'title' => $quiz['title'], 'sentences' => $sentences, 'timeLimit' => $quiz['time_limit'] ?: 20]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?= h($quiz['title']) ?> — Fill in the Blanks</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
:root{--brand:#f04e84;--brand2:#7c3a91;--bg:#f7f4fb;--surface:#fff;--border:#e8e0f0;--text:#1a1025;--muted:#8b7da0;--accent:#00c896;--danger:#ff4d6d;}
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'DM Sans',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;display:flex;flex-direction:column;align-items:center;}
header{background:linear-gradient(135deg,#1a0a2e,#2d1354);padding:0 32px;height:64px;width:100%;display:flex;align-items:center;gap:12px;box-shadow:0 2px 20px rgba(124,58,145,0.3);}
.logo-mark{width:36px;height:36px;background:linear-gradient(135deg,var(--brand),var(--brand2));border-radius:10px;display:flex;align-items:center;justify-content:center;font-family:'Syne',sans-serif;font-weight:800;font-size:16px;color:#fff;}
header span{font-family:'Syne',sans-serif;font-weight:700;font-size:1.1rem;color:#fff;}
.hdr-spacer{flex:1;}
.timer-bar{display:flex;align-items:center;gap:8px;background:rgba(255,255,255,0.1);border:1px solid rgba(255,255,255,0.15);border-radius:10px;padding:8px 16px;}
.t-seg{display:flex;flex-direction:column;align-items:center;}
.t-num{font-family:'Syne',sans-serif;font-size:1.3rem;font-weight:700;color:#fff;line-height:1;}
.t-lbl{font-size:0.6rem;color:rgba(255,255,255,0.5);text-transform:uppercase;}
.sep{color:rgba(255,255,255,0.4);font-size:1.1rem;padding-bottom:8px;}
.game-wrap{width:100%;max-width:780px;padding:28px 20px 60px;}
.quiz-title{font-family:'Syne',sans-serif;font-size:1.6rem;font-weight:800;color:var(--text);text-align:center;margin-bottom:4px;}
.quiz-sub{text-align:center;color:var(--muted);font-size:0.85rem;margin-bottom:18px;}
.progress-row{display:flex;align-items:center;gap:12px;margin-bottom:24px;}
.progress-bar-wrap{flex:1;background:var(--border);border-radius:99px;height:7px;overflow:hidden;}
.progress-bar-fill{height:100%;background:linear-gradient(90deg,var(--brand),var(--brand2));border-radius:99px;transition:width 0.4s ease;width:0%;}
.progress-label{font-family:'Syne',sans-serif;font-weight:700;font-size:0.85rem;color:var(--brand2);white-space:nowrap;}
.page-card{background:var(--surface);border:1px solid var(--border);border-radius:20px;padding:28px 32px;box-shadow:0 4px 24px rgba(124,58,145,0.08);margin-bottom:24px;min-height:380px;}
.page-title-row{display:flex;justify-content:space-between;align-items:center;margin-bottom:22px;}
.page-heading{font-family:'Syne',sans-serif;font-size:0.8rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--brand);}
.page-badge{font-size:0.78rem;color:var(--muted);background:var(--bg);border:1px solid var(--border);border-radius:20px;padding:4px 12px;font-weight:600;}
.sentence-row{display:flex;align-items:center;flex-wrap:wrap;gap:8px;padding:12px 0;border-bottom:1px solid var(--bg);font-size:1rem;line-height:1.6;color:#333;}
.sentence-row:last-child{border-bottom:none;}
.sentence-num-badge{font-family:'Syne',sans-serif;font-weight:800;font-size:0.78rem;color:var(--brand2);background:rgba(124,58,145,0.08);padding:3px 8px;border-radius:6px;flex-shrink:0;}
.drop-zone{display:inline-flex;align-items:center;justify-content:center;min-width:100px;height:34px;border:none;border-bottom:2.5px solid var(--border);border-radius:0;vertical-align:middle;margin:0 4px;padding:0 8px;transition:all 0.2s;background:transparent;position:relative;}
.drop-zone.hovered{background:rgba(240,78,132,0.06);border-bottom-color:var(--brand);border-radius:8px 8px 0 0;}
.word-in-zone{color:var(--brand2);font-weight:700;font-size:0.95rem;}
.word-bank-card{background:var(--surface);border:1px solid var(--border);border-radius:16px;padding:20px 24px;margin-bottom:20px;box-shadow:0 2px 12px rgba(124,58,145,0.05);}
.word-bank-title{font-family:'Syne',sans-serif;font-size:0.78rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--muted);margin-bottom:14px;}
.word-bank{display:flex;flex-wrap:wrap;gap:8px;min-height:60px;}
.word-chip{display:inline-flex;align-items:center;padding:8px 16px;background:var(--bg);border:1.5px solid var(--border);border-radius:30px;font-weight:600;font-size:0.9rem;color:var(--text);cursor:grab;user-select:none;transition:all 0.15s;box-shadow:0 1px 4px rgba(0,0,0,0.06);}
.word-chip:hover{border-color:var(--brand);color:var(--brand);transform:translateY(-1px);}
.word-chip.placed{opacity:0.35;pointer-events:none;}
.nav-row{display:flex;align-items:center;gap:16px;margin-bottom:20px;}
.nav-btn{padding:10px 24px;border:1.5px solid var(--border);border-radius:10px;font-family:'Syne',sans-serif;font-weight:700;font-size:0.85rem;cursor:pointer;background:var(--surface);color:var(--text);transition:all 0.2s;}
.nav-btn:disabled{opacity:0.4;cursor:not-allowed;}
.nav-btn:hover:not(:disabled){border-color:var(--brand);color:var(--brand);}
.nav-info{flex:1;text-align:center;font-family:'Syne',sans-serif;font-weight:700;font-size:0.9rem;color:var(--muted);}
.submit-btn{display:block;width:100%;padding:15px;background:linear-gradient(135deg,var(--brand),var(--brand2));color:#fff;font-family:'Syne',sans-serif;font-weight:700;font-size:1rem;border:none;border-radius:14px;cursor:pointer;transition:all 0.2s;box-shadow:0 4px 20px rgba(240,78,132,0.35);}
.submit-btn:hover{transform:translateY(-1px);}
</style>
</head>
<body>
<header>
  <div class="logo-mark">QC</div>
  <span>Quiz Carnival</span>
  <div class="hdr-spacer"></div>
  <div class="timer-bar">
    <div class="t-seg"><span id="th" class="t-num">00</span><span class="t-lbl">hrs</span></div>
    <span class="sep">:</span>
    <div class="t-seg"><span id="tm" class="t-num">00</span><span class="t-lbl">min</span></div>
    <span class="sep">:</span>
    <div class="t-seg"><span id="ts" class="t-num">00</span><span class="t-lbl">sec</span></div>
  </div>
</header>

<div class="game-wrap">
  <div class="quiz-title" id="quizTitle">Fill In The Blanks</div>
  <div class="quiz-sub" id="quizSub">Drag words into the correct blanks</div>
  <div class="progress-row">
    <div class="progress-bar-wrap"><div class="progress-bar-fill" id="progressFill"></div></div>
    <div class="progress-label" id="progressLabel">0 / 0</div>
  </div>
  <div class="page-card">
    <div class="page-title-row"><div class="page-heading">Sentences</div><div class="page-badge" id="pageBadge">Page 1 of 1</div></div>
    <div id="sentenceContent"></div>
  </div>
  <div class="word-bank-card">
    <div class="word-bank-title">Word Bank — drag to place</div>
    <div class="word-bank" id="wordBank"></div>
  </div>
  <div class="nav-row">
    <button class="nav-btn" id="prevBtn" disabled onclick="changePage(-1)">← Previous</button>
    <div class="nav-info" id="pageInfo">Page 1</div>
    <button class="nav-btn" id="nextBtn" onclick="changePage(1)">Next →</button>
  </div>
  <button class="submit-btn" onclick="handleSubmit()">Submit Quiz</button>
</div>

<script>
const QUIZ = <?= $quizJson ?>;
const SITE_ROOT = '<?= SITE_ROOT ?>';

document.getElementById("quizTitle").textContent = QUIZ.title;
const sentences = QUIZ.sentences;
const PER_PAGE = 10;
const totalPages = Math.ceil(sentences.length / PER_PAGE);
let currentPage = 0;
let startTime = Date.now();
let streak = 0, maxStreak = 0;

const zones = new Array(sentences.length).fill(null);
const allWords = sentences.map(s => s.answer).sort(() => Math.random() - 0.5);

document.getElementById("quizSub").textContent = `Fill in ${sentences.length} blanks`;

const wordBankEl = document.getElementById("wordBank");

function buildWordBank() {
  wordBankEl.innerHTML = "";
  allWords.forEach((word, wi) => {
    const chip = document.createElement("div");
    chip.className = "word-chip";
    chip.id = "chip-" + wi;
    chip.textContent = word;
    chip.draggable = true;
    const usedIdx = zones.findIndex(z => z === word);
    if (usedIdx !== -1) chip.classList.add("placed");
    chip.addEventListener("dragstart", e => { e.dataTransfer.setData("text", JSON.stringify({ word, wi })); });
    wordBankEl.appendChild(chip);
  });
  wordBankEl.addEventListener("dragover", e => e.preventDefault());
  wordBankEl.addEventListener("drop", e => {
    e.preventDefault();
    try { const { word } = JSON.parse(e.dataTransfer.getData("text")); zones.forEach((z, i) => { if (z === word) zones[i] = null; }); renderPage(); updateProgress(); } catch {}
  });
}

function renderPage() {
  const content = document.getElementById("sentenceContent");
  content.innerHTML = "";
  const start = currentPage * PER_PAGE;
  const end = Math.min(start + PER_PAGE, sentences.length);
  for (let i = start; i < end; i++) {
    const item = sentences[i];
    const row = document.createElement("div");
    row.className = "sentence-row";
    const numBadge = document.createElement("span");
    numBadge.className = "sentence-num-badge";
    numBadge.textContent = i + 1;
    row.appendChild(numBadge);
    const parts = item.sentence.split("____");
    parts.forEach((part, pi) => {
      if (part) { const span = document.createElement("span"); span.textContent = part; row.appendChild(span); }
      if (pi < parts.length - 1) {
        const zone = document.createElement("div");
        zone.className = "drop-zone";
        zone.dataset.idx = i;
        if (zones[i]) zone.innerHTML = `<span class="word-in-zone">${zones[i]}</span>`;
        zone.addEventListener("dragover", e => { e.preventDefault(); zone.classList.add("hovered"); });
        zone.addEventListener("dragleave", () => zone.classList.remove("hovered"));
        zone.addEventListener("drop", e => {
          e.preventDefault();
          zone.classList.remove("hovered");
          try {
            const { word } = JSON.parse(e.dataTransfer.getData("text"));
            zones.forEach((z, zi) => { if (z === word) zones[zi] = null; });
            zones[i] = word;
            renderPage(); buildWordBank(); updateProgress();
          } catch {}
        });
        row.appendChild(zone);
      }
    });
    content.appendChild(row);
  }
  document.getElementById("pageBadge").textContent = `Page ${currentPage+1} of ${totalPages}`;
  document.getElementById("pageInfo").textContent = `Page ${currentPage+1} of ${totalPages}`;
  document.getElementById("prevBtn").disabled = currentPage === 0;
  document.getElementById("nextBtn").disabled = currentPage === totalPages - 1;
}

function changePage(step) { currentPage += step; renderPage(); }

function updateProgress() {
  const filled = zones.filter(z => z !== null).length;
  const pct = (filled / sentences.length) * 100;
  document.getElementById("progressFill").style.width = pct + "%";
  document.getElementById("progressLabel").textContent = `${filled} / ${sentences.length}`;
}

const totalSeconds = (QUIZ.timeLimit || 20) * 60;
const endTime = Date.now() + totalSeconds * 1000;
function updateTimer() {
  const left = Math.max(0, endTime - Date.now());
  const h = Math.floor(left / 3600000);
  const m = Math.floor((left % 3600000) / 60000);
  const s = Math.floor((left % 60000) / 1000);
  document.getElementById("th").textContent = h.toString().padStart(2,"0");
  document.getElementById("tm").textContent = m.toString().padStart(2,"0");
  document.getElementById("ts").textContent = s.toString().padStart(2,"0");
  if (left === 0) { clearInterval(timerInterval); handleSubmit(); }
}
const timerInterval = setInterval(updateTimer, 1000);
updateTimer();

function handleSubmit() {
  clearInterval(timerInterval);
  const elapsed = Math.floor((Date.now() - startTime) / 1000);
  let correct = 0, curStreak = 0, maxSt = 0;
  sentences.forEach((s, i) => {
    if (zones[i] && zones[i].toLowerCase().trim() === s.answer.toLowerCase().trim()) { correct++; curStreak++; maxSt = Math.max(maxSt, curStreak); }
    else curStreak = 0;
  });
  const total = sentences.length;
  const correctPct = Math.round((correct / total) * 100);
  const timePct = Math.max(0, Math.round(((totalSeconds - elapsed) / totalSeconds) * 100));
  const streakPct = Math.round((maxSt / total) * 100);
  const attempt = { started: new Date(startTime).toISOString(), completed: new Date().toISOString(), durationSecs: elapsed, correct, total, correctPct, timePct, streakPct };
  fetch(SITE_ROOT + 'api/submit-attempt.php', {
    method: 'POST', headers: {'Content-Type':'application/json'},
    body: JSON.stringify({ quiz_id: QUIZ.quiz_id, score: correct, max_score: total, time_taken: elapsed, details: attempt })
  }).then(() => {
    window.location.href = SITE_ROOT + 'quiz/fill-in-blanks/results.php?quiz_id=' + QUIZ.quiz_id;
  }).catch(() => {
    window.location.href = SITE_ROOT + 'quiz/fill-in-blanks/results.php?quiz_id=' + QUIZ.quiz_id;
  });
}

buildWordBank(); renderPage(); updateProgress();
</script>
</body>
</html>
