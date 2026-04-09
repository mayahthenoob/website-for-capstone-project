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

$stmt = $pdo->prepare("SELECT q.*, c.name as class_name, c.id as class_id FROM quizzes q JOIN classes c ON q.class_id=c.id JOIN enrollments e ON e.class_id=c.id WHERE q.id=? AND e.student_id=? AND q.published=1 AND q.type='wordsearch'");
$stmt->execute([$quizId, $uid]); $quiz = $stmt->fetch();
if (!$quiz) { header('Location: ' . SITE_ROOT . 'dashboard.php'); exit; }

$attCount = $pdo->prepare("SELECT COUNT(*) FROM quiz_attempts WHERE quiz_id=? AND student_id=?");
$attCount->execute([$quizId, $uid]);
if ((int)$attCount->fetchColumn() >= $quiz['attempts_allowed']) { header('Location: ' . SITE_ROOT . 'quiz/word-search/results.php?quiz_id='.$quizId); exit; }

$quizData = json_decode($quiz['quiz_data'] ?? '{}', true);
$words    = $quizData['words'] ?? [];

// Generate grid with words hidden inside
function makeWordSearchGrid(array $words, int $size = 10): array {
    $grid = array_fill(0, $size, array_fill(0, $size, ''));
    $letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $directions = [[0,1],[1,0],[1,1],[0,-1],[-1,0],[-1,-1],[1,-1],[-1,1]];
    foreach ($words as $word) {
        $placed = false;
        for ($attempt = 0; $attempt < 100 && !$placed; $attempt++) {
            $dir  = $directions[array_rand($directions)];
            $len  = strlen($word);
            $row  = rand(0, $size-1);
            $col  = rand(0, $size-1);
            $endR = $row + $dir[0] * ($len-1);
            $endC = $col + $dir[1] * ($len-1);
            if ($endR < 0 || $endR >= $size || $endC < 0 || $endC >= $size) continue;
            $ok = true;
            for ($i = 0; $i < $len; $i++) {
                $r = $row + $dir[0]*$i; $c = $col + $dir[1]*$i;
                if ($grid[$r][$c] !== '' && $grid[$r][$c] !== $word[$i]) { $ok=false; break; }
            }
            if ($ok) {
                for ($i = 0; $i < $len; $i++) $grid[$row + $dir[0]*$i][$col + $dir[1]*$i] = $word[$i];
                $placed = true;
            }
        }
    }
    // Fill blanks
    for ($r = 0; $r < $size; $r++)
        for ($c = 0; $c < $size; $c++)
            if ($grid[$r][$c] === '') $grid[$r][$c] = $letters[rand(0,25)];
    return $grid;
}

$grid = makeWordSearchGrid($words);
$quizJson = json_encode([
    'quiz_id'   => $quiz['id'],
    'title'     => $quiz['title'],
    'words'     => $words,
    'grid'      => $grid,
    'timeLimit' => $quiz['time_limit'] ?: 20,
    'category'  => $quizData['category'] ?? '',
]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?= h($quiz['title']) ?> — Word Search</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
:root { --brand:#f04e84; --brand2:#7c3a91; --bg:#f7f4fb; --surface:#fff; --border:#e8e0f0; --text:#1a1025; --muted:#8b7da0; --c1:#2092e9;--c2:#f04e84;--c3:#35556e;--c4:#06382f;--c5:#74070e;--c6:#614d70;--c7:#6c7d36;--c8:#ffa858;--c9:#3a4b41;--c10:#00c896;--c11:#FF6B6B;--c12:#e67e22; }
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'DM Sans',sans-serif;background:var(--bg);min-height:100vh;display:flex;flex-direction:column;align-items:center;user-select:none;}
header{background:linear-gradient(135deg,#1a0a2e,#2d1354);padding:0 32px;height:64px;width:100%;display:flex;align-items:center;gap:12px;box-shadow:0 2px 20px rgba(124,58,145,0.3);}
.logo-mark{width:36px;height:36px;background:linear-gradient(135deg,var(--brand),var(--brand2));border-radius:10px;display:flex;align-items:center;justify-content:center;font-family:'Syne',sans-serif;font-weight:800;font-size:16px;color:#fff;}
header span{font-family:'Syne',sans-serif;font-weight:700;font-size:1.1rem;color:#fff;}
header .spacer{flex:1;}
.timer-bar{display:flex;align-items:center;gap:8px;background:rgba(255,255,255,0.1);border:1px solid rgba(255,255,255,0.15);border-radius:10px;padding:8px 16px;}
.t-seg{display:flex;flex-direction:column;align-items:center;}
.t-num{font-family:'Syne',sans-serif;font-size:1.3rem;font-weight:700;color:#fff;line-height:1;}
.t-lbl{font-size:0.6rem;color:rgba(255,255,255,0.5);text-transform:uppercase;}
.sep{color:rgba(255,255,255,0.4);font-size:1.1rem;padding-bottom:8px;}
.game-wrap{width:100%;max-width:720px;padding:28px 20px 60px;}
.quiz-title{font-family:'Syne',sans-serif;font-size:1.6rem;font-weight:800;color:var(--text);text-align:center;margin-bottom:4px;}
.quiz-sub{text-align:center;color:var(--muted);font-size:0.85rem;margin-bottom:24px;}
.progress-bar-wrap{width:100%;background:var(--border);border-radius:99px;height:6px;margin-bottom:28px;overflow:hidden;}
.progress-bar-fill{height:100%;background:linear-gradient(90deg,var(--brand),var(--brand2));border-radius:99px;transition:width 0.4s ease;width:0%;}
.grid-card{background:var(--surface);border:1px solid var(--border);border-radius:20px;padding:24px;box-shadow:0 4px 24px rgba(124,58,145,0.08);margin-bottom:24px;display:flex;justify-content:center;}
.grid{display:grid;grid-template-columns:repeat(10,50px);grid-template-rows:repeat(10,50px);gap:0;cursor:crosshair;position:relative;}
.cell{position:relative;width:50px;height:50px;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:19px;z-index:2;color:var(--text);border-radius:4px;transition:background 0.15s;}
.pill-highlight,#temp-pill{position:absolute;height:42px;border-radius:21px;pointer-events:none;z-index:1;transform-origin:21px center;margin-top:-21px;}
.pill-highlight{border:2.5px solid var(--pill-color);background:color-mix(in srgb,var(--pill-color) 10%,transparent);}
#temp-pill{border:2.5px dashed var(--brand);display:none;background:rgba(240,78,132,0.05);}
.words-card{background:var(--surface);border:1px solid var(--border);border-radius:20px;padding:24px 28px;box-shadow:0 4px 24px rgba(124,58,145,0.08);margin-bottom:24px;}
.words-title{font-family:'Syne',sans-serif;font-size:0.8rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--muted);margin-bottom:16px;}
.word-list{list-style:none;display:flex;flex-wrap:wrap;gap:8px;}
.word-list li{font-family:'Syne',sans-serif;font-weight:700;font-size:0.9rem;padding:6px 14px;background:var(--bg);border:1.5px solid var(--border);border-radius:8px;color:var(--text);transition:all 0.3s;letter-spacing:0.5px;}
.word-list li.found{opacity:0.35;text-decoration:line-through;background:transparent;}
.submit-btn{display:block;width:100%;padding:15px;background:linear-gradient(135deg,var(--brand),var(--brand2));color:#fff;font-family:'Syne',sans-serif;font-weight:700;font-size:1rem;border:none;border-radius:14px;cursor:pointer;transition:all 0.2s;box-shadow:0 4px 20px rgba(240,78,132,0.35);}
.submit-btn:hover{transform:translateY(-1px);}
@media(max-width:580px){.grid{grid-template-columns:repeat(10,34px);grid-template-rows:repeat(10,34px);}.cell{width:34px;height:34px;font-size:13px;}.pill-highlight,#temp-pill{height:30px;border-radius:15px;margin-top:-15px;transform-origin:15px center;}}
</style>
</head>
<body>
<header>
  <div class="logo-mark">QC</div>
  <span>Quiz Carnival</span>
  <div class="spacer"></div>
  <div class="timer-bar">
    <div class="t-seg"><span id="th" class="t-num">00</span><span class="t-lbl">hrs</span></div>
    <span class="sep">:</span>
    <div class="t-seg"><span id="tm" class="t-num">00</span><span class="t-lbl">min</span></div>
    <span class="sep">:</span>
    <div class="t-seg"><span id="ts" class="t-num">00</span><span class="t-lbl">sec</span></div>
  </div>
</header>
<div class="game-wrap">
  <div class="quiz-title" id="quizTitle">Word Search</div>
  <div class="quiz-sub" id="quizSub">Find all the hidden words</div>
  <div class="progress-bar-wrap"><div class="progress-bar-fill" id="progressFill"></div></div>
  <div class="grid-card"><div class="grid" id="grid"><div id="temp-pill"></div></div></div>
  <div class="words-card"><div class="words-title">Words to Find</div><ul class="word-list" id="wordList"></ul></div>
  <button class="submit-btn" onclick="handleSubmit()">Submit Quiz</button>
</div>
<script>
const QUIZ = <?= $quizJson ?>;
const SITE_ROOT = '<?= SITE_ROOT ?>';

document.getElementById("quizTitle").textContent = QUIZ.title;
document.getElementById("quizSub").textContent = `Find ${QUIZ.words.length} hidden words`;

const GRID = 10;
const COLORS = ['--c1','--c2','--c3','--c4','--c5','--c6','--c7','--c8','--c9','--c10','--c11','--c12'];
const words = QUIZ.words;
const gridData = QUIZ.grid;

let colorIndex = 0, isSelecting = false, startCell = null;
let currentSelection = { cells: [], angle: 0, width: 0, left: 0, top: 0 };
let wordColors = {};
let cellsArr = [];
let startTime = Date.now();
let streak = 0, maxStreak = 0, lastFoundTime = null;

const gridEl = document.getElementById("grid");
const wordListEl = document.getElementById("wordList");
const tempPill = document.getElementById("temp-pill");

for (let r = 0; r < GRID; r++) {
  for (let c = 0; c < GRID; c++) {
    const cell = document.createElement("div");
    cell.className = "cell";
    cell.textContent = gridData[r] ? (gridData[r][c] || " ") : " ";
    cell.dataset.row = r; cell.dataset.col = c;
    cell.addEventListener("mousedown", e => { e.preventDefault(); handleStart(cell); });
    cell.addEventListener("mouseenter", () => handleDrag(cell));
    cell.addEventListener("touchstart", e => { e.preventDefault(); handleStart(cell); }, { passive: false });
    cell.addEventListener("touchmove", e => {
      e.preventDefault();
      const touch = e.touches[0];
      const el = document.elementFromPoint(touch.clientX, touch.clientY);
      if (el && el.classList.contains("cell")) handleDrag(el);
    }, { passive: false });
    gridEl.appendChild(cell);
    cellsArr.push(cell);
  }
}

words.forEach(w => {
  const li = document.createElement("li");
  li.textContent = w; li.id = "word-" + w;
  wordListEl.appendChild(li);
});

function getCellSize() { return cellsArr[0]?.getBoundingClientRect().width || 50; }
function handleStart(cell) { isSelecting = true; startCell = cell; tempPill.style.display = "block"; updateSelection(cell); }
function handleDrag(cell) { if (!isSelecting) return; updateSelection(cell); }
document.addEventListener("mouseup", () => { if (!isSelecting) return; isSelecting = false; checkSelection(); tempPill.style.display = "none"; });
document.addEventListener("touchend", () => { if (!isSelecting) return; isSelecting = false; checkSelection(); tempPill.style.display = "none"; });

function updateSelection(endCell) {
  const r1 = parseInt(startCell.dataset.row), c1 = parseInt(startCell.dataset.col);
  const r2 = parseInt(endCell.dataset.row), c2 = parseInt(endCell.dataset.col);
  const dr = r2 - r1, dc = c2 - c1;
  const absDr = Math.abs(dr), absDc = Math.abs(dc);
  if (!(dr === 0 || dc === 0 || absDr === absDc)) return;
  const cellSize = getCellSize();
  const steps = Math.max(absDr, absDc);
  const dist = Math.sqrt(dr*dr + dc*dc) * cellSize;
  const pillW = dist + cellSize * 0.84;
  const angle = Math.atan2(dr, dc) * (180 / Math.PI);
  const startX = c1 * cellSize + cellSize / 2;
  const startY = r1 * cellSize + cellSize / 2;
  const halfH = cellSize * 0.42;
  tempPill.style.width = `${pillW}px`;
  tempPill.style.left = `${startX - halfH}px`;
  tempPill.style.top = `${startY}px`;
  tempPill.style.transform = `rotate(${angle}deg)`;
  tempPill.style.height = `${halfH * 2}px`;
  tempPill.style.borderRadius = `${halfH}px`;
  tempPill.style.marginTop = `-${halfH}px`;
  tempPill.style.transformOrigin = `${halfH}px center`;
  currentSelection = { width: pillW, angle, left: `${startX - halfH}px`, top: `${startY}px`, cells: [], halfH };
  const rStep = dr === 0 ? 0 : dr / absDr, cStep = dc === 0 ? 0 : dc / absDc;
  for (let i = 0; i <= steps; i++) currentSelection.cells.push(cellsArr[(r1 + i*rStep) * GRID + (c1 + i*cStep)]);
}

function checkSelection() {
  const word = currentSelection.cells.map(c => c.textContent.trim()).join("");
  const rev = word.split("").reverse().join("");
  const match = words.find(w => (w === word || w === rev) && !wordColors[w]);
  if (match) {
    const colorVar = COLORS[colorIndex % COLORS.length];
    const color = getComputedStyle(document.documentElement).getPropertyValue(colorVar).trim();
    wordColors[match] = color; colorIndex++;
    const pill = document.createElement("div");
    pill.className = "pill-highlight";
    const hH = currentSelection.halfH || 21;
    pill.style.setProperty("--pill-color", color);
    pill.style.width = `${currentSelection.width}px`;
    pill.style.left = currentSelection.left;
    pill.style.top = currentSelection.top;
    pill.style.height = `${hH * 2}px`;
    pill.style.borderRadius = `${hH}px`;
    pill.style.marginTop = `-${hH}px`;
    pill.style.transformOrigin = `${hH}px center`;
    pill.style.transform = `rotate(${currentSelection.angle}deg)`;
    gridEl.appendChild(pill);
    const li = document.getElementById("word-" + match);
    if (li) { li.classList.add("found"); li.style.color = color; li.style.borderColor = color; }
    const now = Date.now();
    if (lastFoundTime && (now - lastFoundTime) < 5000) { streak++; } else { streak = 1; }
    maxStreak = Math.max(maxStreak, streak); lastFoundTime = now;
    updateProgress();
    if (Object.keys(wordColors).length === words.length) setTimeout(() => handleSubmit(true), 600);
  }
}

function updateProgress() {
  const pct = (Object.keys(wordColors).length / words.length) * 100;
  document.getElementById("progressFill").style.width = pct + "%";
}

const totalSeconds = (QUIZ.timeLimit || 20) * 60;
const endTime = Date.now() + totalSeconds * 1000;
function updateTimer() {
  const left = Math.max(0, endTime - Date.now());
  const h = Math.floor(left / 3600000);
  const m = Math.floor((left % 3600000) / 60000);
  const s = Math.floor((left % 60000) / 1000);
  document.getElementById("th").textContent = h.toString().padStart(2, "0");
  document.getElementById("tm").textContent = m.toString().padStart(2, "0");
  document.getElementById("ts").textContent = s.toString().padStart(2, "0");
  if (left === 0) { clearInterval(timerInterval); handleSubmit(false); }
}
const timerInterval = setInterval(updateTimer, 1000);
updateTimer();

function handleSubmit(allFound = false) {
  clearInterval(timerInterval);
  const elapsed = Math.floor((Date.now() - startTime) / 1000);
  const found = Object.keys(wordColors).length;
  const total = words.length;
  const correctPct = Math.round((found / total) * 100);
  const timePct = Math.max(0, Math.round(((totalSeconds - elapsed) / totalSeconds) * 100));
  const streakPct = Math.round((maxStreak / total) * 100);

  const attemptData = { started: new Date(startTime).toISOString(), completed: new Date().toISOString(), durationSecs: elapsed, found, total, correctPct, timePct, streakPct };

  fetch(SITE_ROOT + 'api/submit-attempt.php', {
    method: 'POST',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify({ quiz_id: QUIZ.quiz_id, score: found, max_score: total, time_taken: elapsed, details: attemptData })
  }).then(r => r.json()).then(d => {
    sessionStorage.setItem('wsLastAttempt', JSON.stringify(attemptData));
    sessionStorage.setItem('wsQuizTitle', QUIZ.title);
    sessionStorage.setItem('wsQuizId', QUIZ.quiz_id);
    window.location.href = SITE_ROOT + 'quiz/word-search/results.php?quiz_id=' + QUIZ.quiz_id;
  }).catch(() => {
    sessionStorage.setItem('wsLastAttempt', JSON.stringify(attemptData));
    sessionStorage.setItem('wsQuizTitle', QUIZ.title);
    sessionStorage.setItem('wsQuizId', QUIZ.quiz_id);
    window.location.href = SITE_ROOT + 'quiz/word-search/results.php?quiz_id=' + QUIZ.quiz_id;
  });
}
</script>
</body>
</html>
