<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
if (session_status() === PHP_SESSION_NONE) session_start();
requireLogin();
if (currentUser()['role'] !== 'student') { header('Location: ' . SITE_ROOT . 'dashboard.php'); exit; }
$pdo = getDB(); $uid = currentUser()['id'];
$quizId = (int)($_GET['quiz_id'] ?? 0);
$stmt = $pdo->prepare("SELECT q.*, c.name as class_name, c.id as class_id FROM quizzes q JOIN classes c ON q.class_id=c.id JOIN enrollments e ON e.class_id=c.id WHERE q.id=? AND e.student_id=? AND q.published=1 AND q.type='hangman'");
$stmt->execute([$quizId, $uid]); $quiz = $stmt->fetch();
if (!$quiz) { header('Location: ' . SITE_ROOT . 'dashboard.php'); exit; }
$attCount = $pdo->prepare("SELECT COUNT(*) FROM quiz_attempts WHERE quiz_id=? AND student_id=?");
$attCount->execute([$quizId, $uid]);
if ((int)$attCount->fetchColumn() >= $quiz['attempts_allowed']) { header('Location: ' . SITE_ROOT . 'quiz/hangman/results.php?quiz_id='.$quizId); exit; }
$qd = json_decode($quiz['quiz_data'] ?? '{}', true);
$quizJson = json_encode(['quiz_id'=>$quiz['id'],'title'=>$quiz['title'],'words'=>$qd['words']??[],'category'=>$qd['category']??'General','guessesNum'=>(int)($qd['guessesNum']??6),'guessesMode'=>$qd['guessesMode']??'perWord','timeLimit'=>$quiz['time_limit']??20]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?= h($quiz['title']) ?> — Hangman</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
:root{--brand:#f04e84;--brand2:#7c3a91;--bg:#f7f4fb;--surface:#fff;--border:#e8e0f0;--text:#1a1025;--muted:#8b7da0;--correct:#00c896;--wrong:#ff4d6d;}
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'DM Sans',sans-serif;background:var(--bg);min-height:100vh;display:flex;flex-direction:column;align-items:center;}
header{background:linear-gradient(135deg,#1a0a2e,#2d1354);padding:0 32px;height:64px;width:100%;display:flex;align-items:center;gap:12px;box-shadow:0 2px 20px rgba(124,58,145,0.3);}
.logo-mark{width:36px;height:36px;background:linear-gradient(135deg,var(--brand),var(--brand2));border-radius:10px;display:flex;align-items:center;justify-content:center;font-family:'Syne',sans-serif;font-weight:800;font-size:16px;color:#fff;}
header span{font-family:'Syne',sans-serif;font-weight:700;font-size:1.1rem;color:#fff;}
.hdr-spacer{flex:1;}
.timer-bar{display:flex;align-items:center;gap:8px;background:rgba(255,255,255,0.1);border:1px solid rgba(255,255,255,0.15);border-radius:10px;padding:8px 16px;}
.t-seg{display:flex;flex-direction:column;align-items:center;}
.t-num{font-family:'Syne',sans-serif;font-size:1.3rem;font-weight:700;color:#fff;line-height:1;}
.t-lbl{font-size:0.6rem;color:rgba(255,255,255,0.5);text-transform:uppercase;}
.sep{color:rgba(255,255,255,0.4);font-size:1.1rem;padding-bottom:8px;}
.game-wrap{width:100%;max-width:720px;padding:28px 20px 60px;}
.quiz-title{font-family:'Syne',sans-serif;font-size:1.6rem;font-weight:800;text-align:center;margin-bottom:4px;}
.quiz-sub{text-align:center;color:var(--muted);font-size:0.85rem;margin-bottom:20px;}
.progress-row{display:flex;align-items:center;gap:12px;margin-bottom:20px;}
.progress-bar-wrap{flex:1;background:var(--border);border-radius:99px;height:7px;overflow:hidden;}
.progress-bar-fill{height:100%;background:linear-gradient(90deg,var(--brand),var(--brand2));border-radius:99px;transition:width 0.4s;width:0%;}
.progress-label{font-family:'Syne',sans-serif;font-weight:700;font-size:0.85rem;color:var(--brand2);white-space:nowrap;}
.game-card{background:var(--surface);border:1px solid var(--border);border-radius:20px;padding:28px 32px;box-shadow:0 4px 24px rgba(124,58,145,0.08);margin-bottom:20px;}
.card-top-row{display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;}
.word-counter{font-family:'Syne',sans-serif;font-size:0.8rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--brand);}
.guesses-badge{display:flex;align-items:center;gap:6px;background:var(--bg);border:1.5px solid var(--border);border-radius:20px;padding:5px 14px;font-family:'Syne',sans-serif;font-weight:700;font-size:0.82rem;color:var(--muted);}
.guesses-badge.danger{border-color:var(--wrong);color:var(--wrong);background:rgba(255,77,109,0.06);}
.guesses-badge .dot{width:8px;height:8px;border-radius:50%;background:var(--muted);}
.guesses-badge.danger .dot{background:var(--wrong);}
.hangman-wrap{display:flex;justify-content:center;margin-bottom:24px;background:var(--bg);border-radius:12px;padding:12px;}
.word-display{display:flex;flex-wrap:wrap;justify-content:center;gap:8px;margin-bottom:28px;}
.letter-box{width:44px;height:52px;border-bottom:3px solid var(--text);display:flex;align-items:center;justify-content:center;font-family:'Syne',sans-serif;font-size:1.5rem;font-weight:800;color:var(--brand2);transition:all 0.2s;}
.letter-box.revealed{animation:popIn 0.2s ease;}
@keyframes popIn{0%{transform:scale(1.3);}100%{transform:scale(1);}}
.cat-chip{display:inline-flex;align-items:center;gap:6px;background:rgba(124,58,145,0.08);border:1px solid rgba(124,58,145,0.2);border-radius:20px;padding:4px 12px;font-size:0.78rem;font-weight:600;color:var(--brand2);margin-bottom:20px;}
.keyboard{display:flex;flex-wrap:wrap;justify-content:center;gap:6px;margin-bottom:20px;}
.key-btn{width:42px;height:42px;background:var(--bg);border:1.5px solid var(--border);border-radius:8px;font-family:'Syne',sans-serif;font-weight:700;font-size:0.9rem;cursor:pointer;transition:all 0.15s;color:var(--text);}
.key-btn:hover:not(:disabled){border-color:var(--brand);color:var(--brand);transform:translateY(-1px);}
.key-btn.correct{background:rgba(0,200,150,0.12);border-color:var(--correct);color:var(--correct);}
.key-btn.wrong{background:rgba(255,77,109,0.1);border-color:var(--wrong);color:var(--wrong);}
.key-btn:disabled{opacity:0.5;cursor:not-allowed;transform:none;}
.word-guess-row{display:flex;gap:10px;align-items:center;margin-bottom:8px;}
.word-guess-row input{flex:1;padding:10px 14px;border:1.5px solid var(--border);border-radius:10px;font-family:'DM Sans',sans-serif;font-size:0.95rem;color:var(--text);background:var(--bg);outline:none;text-transform:uppercase;letter-spacing:2px;}
.word-guess-row input:focus{border-color:var(--brand);}
.guess-word-btn{padding:10px 20px;background:var(--brand2);color:#fff;border:none;border-radius:10px;font-family:'Syne',sans-serif;font-weight:700;font-size:0.88rem;cursor:pointer;}
.guess-word-btn:hover{background:var(--brand);}
.result-flash{text-align:center;font-family:'Syne',sans-serif;font-weight:700;font-size:1rem;min-height:24px;padding:4px 0;}
.result-flash.win{color:var(--correct);}
.result-flash.lose{color:var(--wrong);}
.nav-row{display:flex;align-items:center;gap:16px;margin-top:16px;}
.nav-btn{padding:10px 24px;border:1.5px solid var(--border);border-radius:10px;font-family:'Syne',sans-serif;font-weight:700;font-size:0.85rem;cursor:pointer;background:var(--surface);color:var(--text);transition:all 0.2s;}
.nav-btn:disabled{opacity:0.4;cursor:not-allowed;}
.nav-btn:hover:not(:disabled){border-color:var(--brand);color:var(--brand);}
.nav-info{flex:1;text-align:center;font-family:'Syne',sans-serif;font-weight:700;font-size:0.9rem;color:var(--muted);}
.submit-btn{display:block;width:100%;padding:15px;margin-top:16px;background:linear-gradient(135deg,var(--brand),var(--brand2));color:#fff;font-family:'Syne',sans-serif;font-weight:700;font-size:1rem;border:none;border-radius:14px;cursor:pointer;transition:all 0.2s;box-shadow:0 4px 20px rgba(240,78,132,0.35);}
.submit-btn:hover{transform:translateY(-1px);}
@media(max-width:500px){.key-btn{width:34px;height:34px;font-size:0.75rem;}.letter-box{width:34px;height:42px;font-size:1.2rem;}}
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
  <div class="quiz-title" id="quizTitle">Hangman</div>
  <div class="quiz-sub" id="quizSub">Guess each word before you run out of attempts</div>
  <div class="progress-row">
    <div class="progress-bar-wrap"><div class="progress-bar-fill" id="progressFill"></div></div>
    <div class="progress-label" id="progressLabel">0 / 0</div>
  </div>
  <div class="game-card">
    <div class="card-top-row">
      <div class="word-counter" id="wordCounter">Word 1 of 1</div>
      <div class="guesses-badge" id="guessesBadge"><div class="dot"></div><span id="guessesLeft">6 guesses left</span></div>
    </div>
    <div class="cat-chip" id="catChip">📂 General</div>
    <div class="hangman-wrap">
      <svg width="200" height="180" id="hangmanSvg">
        <line x1="20" y1="170" x2="100" y2="170" stroke="#c0b8d0" stroke-width="4"/>
        <line x1="60" y1="170" x2="60" y2="10" stroke="#c0b8d0" stroke-width="4"/>
        <line x1="60" y1="10" x2="140" y2="10" stroke="#c0b8d0" stroke-width="4"/>
        <line x1="140" y1="10" x2="140" y2="30" stroke="#c0b8d0" stroke-width="4"/>
        <circle id="hm-head" cx="140" cy="50" r="20" stroke="#f04e84" stroke-width="3" fill="transparent" style="display:none"/>
        <line id="hm-body" x1="140" y1="70" x2="140" y2="120" stroke="#f04e84" stroke-width="3" style="display:none"/>
        <line id="hm-larm" x1="140" y1="90" x2="110" y2="75" stroke="#f04e84" stroke-width="3" style="display:none"/>
        <line id="hm-rarm" x1="140" y1="90" x2="170" y2="75" stroke="#f04e84" stroke-width="3" style="display:none"/>
        <line id="hm-lleg" x1="140" y1="120" x2="118" y2="150" stroke="#f04e84" stroke-width="3" style="display:none"/>
        <line id="hm-rleg" x1="140" y1="120" x2="162" y2="150" stroke="#f04e84" stroke-width="3" style="display:none"/>
        <g id="hm-face" style="display:none">
          <line x1="132" y1="44" x2="137" y2="49" stroke="#f04e84" stroke-width="2"/>
          <line x1="137" y1="44" x2="132" y2="49" stroke="#f04e84" stroke-width="2"/>
          <line x1="143" y1="44" x2="148" y2="49" stroke="#f04e84" stroke-width="2"/>
          <line x1="148" y1="44" x2="143" y2="49" stroke="#f04e84" stroke-width="2"/>
          <path d="M132 60 Q140 68 148 60" stroke="#f04e84" stroke-width="2" fill="transparent"/>
        </g>
      </svg>
    </div>
    <div class="word-display" id="wordDisplay"></div>
    <div class="keyboard" id="keyboard"></div>
    <div class="word-guess-row">
      <input type="text" id="wordInput" placeholder="Type the full word…">
      <button class="guess-word-btn" onclick="guessFullWord()">Guess Word</button>
    </div>
    <div class="result-flash" id="resultFlash"></div>
  </div>
  <div class="nav-row">
    <button class="nav-btn" id="prevBtn" disabled onclick="goWord(-1)">← Prev</button>
    <div class="nav-info" id="navInfo">Word 1 of 1</div>
    <button class="nav-btn" id="nextBtn" onclick="goWord(1)">Next →</button>
  </div>
  <button class="submit-btn" onclick="handleSubmit()">Submit Quiz</button>
</div>

<script>
const QUIZ = <?= $quizJson ?>;
const SITE_ROOT = '<?= SITE_ROOT ?>';

document.getElementById("quizTitle").textContent = QUIZ.title;
document.getElementById("quizSub").textContent = `Guess ${QUIZ.words.length} word${QUIZ.words.length > 1 ? 's' : ''}`;
document.getElementById("catChip").textContent = `📂 ${QUIZ.category || 'General'}`;

const words = QUIZ.words;
const PARTS = ['hm-head','hm-body','hm-larm','hm-rarm','hm-lleg','hm-rleg','hm-face'];
const MAX_GUESSES = QUIZ.guessesNum || 6;
const MODE = QUIZ.guessesMode || "perWord";

let currentIdx = 0, startTime = Date.now();
let quizGuessesLeft = MAX_GUESSES;
let streak = 0, maxStreak = 0, lastWonTime = null;

const wordStates = words.map(() => ({ correct: new Set(), wrong: [], guessesLeft: MAX_GUESSES, done: false, won: false }));

function getGuessesLeft() { return MODE === "entireQuiz" ? quizGuessesLeft : wordStates[currentIdx].guessesLeft; }
function state() { return wordStates[currentIdx]; }

function initWord() {
  const word = words[currentIdx];
  const st = state();
  const wrongCount = st.wrong.length;
  PARTS.forEach((id, i) => { document.getElementById(id).style.display = i < wrongCount ? 'block' : 'none'; });
  const display = document.getElementById("wordDisplay");
  display.innerHTML = "";
  word.split("").forEach(letter => {
    const box = document.createElement("div");
    box.className = "letter-box";
    if (st.correct.has(letter)) { box.textContent = letter; box.classList.add("revealed"); }
    display.appendChild(box);
  });
  buildKeyboard();
  updateGuessesBadge();
  document.getElementById("wordCounter").textContent = `Word ${currentIdx + 1} of ${words.length}`;
  document.getElementById("navInfo").textContent = `Word ${currentIdx + 1} of ${words.length}`;
  document.getElementById("prevBtn").disabled = currentIdx === 0;
  document.getElementById("nextBtn").textContent = currentIdx === words.length - 1 ? "Finish" : "Next →";
  const flash = document.getElementById("resultFlash");
  if (st.done) { flash.textContent = st.won ? "✓ Correct!" : "✗ Word not guessed"; flash.className = "result-flash " + (st.won ? "win" : "lose"); }
  else { flash.textContent = ""; flash.className = "result-flash"; }
  document.getElementById("wordInput").value = "";
  updateProgress();
}

function buildKeyboard() {
  const kb = document.getElementById("keyboard");
  kb.innerHTML = "";
  const st = state();
  for (let i = 65; i <= 90; i++) {
    const letter = String.fromCharCode(i);
    const btn = document.createElement("button");
    btn.className = "key-btn"; btn.textContent = letter;
    if (st.correct.has(letter)) { btn.classList.add("correct"); btn.disabled = true; }
    else if (st.wrong.includes(letter)) { btn.classList.add("wrong"); btn.disabled = true; }
    else if (st.done || getGuessesLeft() <= 0) { btn.disabled = true; }
    else { btn.onclick = () => handleGuess(letter); }
    kb.appendChild(btn);
  }
}

function handleGuess(letter) {
  const st = state();
  if (st.done || getGuessesLeft() <= 0) return;
  const word = words[currentIdx];
  if (word.includes(letter)) {
    st.correct.add(letter);
    if (word.split("").every(l => st.correct.has(l))) {
      st.done = true; st.won = true;
      const now = Date.now();
      if (lastWonTime && (now - lastWonTime) < 8000) { streak++; } else { streak = 1; }
      maxStreak = Math.max(maxStreak, streak); lastWonTime = now;
    }
  } else {
    st.wrong.push(letter);
    if (MODE === "entireQuiz") quizGuessesLeft--; else st.guessesLeft--;
    const partIdx = st.wrong.length - 1;
    if (partIdx < PARTS.length) document.getElementById(PARTS[partIdx]).style.display = "block";
    if (getGuessesLeft() <= 0 || st.wrong.length >= MAX_GUESSES) {
      st.done = true; st.won = false; streak = 0;
      PARTS.forEach(id => document.getElementById(id).style.display = "block");
    }
  }
  initWord();
}

function guessFullWord() {
  const st = state();
  if (st.done) return;
  const word = words[currentIdx];
  const guess = document.getElementById("wordInput").value.toUpperCase().trim();
  if (!guess) return;
  if (guess === word) {
    word.split("").forEach(l => st.correct.add(l));
    st.done = true; st.won = true;
    const now = Date.now();
    if (lastWonTime && (now - lastWonTime) < 8000) { streak++; } else { streak = 1; }
    maxStreak = Math.max(maxStreak, streak); lastWonTime = now;
  } else {
    if (MODE === "entireQuiz") quizGuessesLeft = Math.max(0, quizGuessesLeft - 1);
    else st.guessesLeft = Math.max(0, st.guessesLeft - 1);
    const wrongCount = MAX_GUESSES - (MODE === "entireQuiz" ? quizGuessesLeft : st.guessesLeft);
    PARTS.slice(0, Math.min(wrongCount, PARTS.length)).forEach(id => document.getElementById(id).style.display = "block");
    if (getGuessesLeft() <= 0) { st.done = true; st.won = false; streak = 0; PARTS.forEach(id => document.getElementById(id).style.display = "block"); }
  }
  initWord();
}

function updateGuessesBadge() {
  const left = getGuessesLeft();
  const badge = document.getElementById("guessesBadge");
  document.getElementById("guessesLeft").textContent = `${left} guess${left !== 1 ? 'es' : ''} left`;
  badge.className = left <= 2 ? "guesses-badge danger" : "guesses-badge";
}

function updateProgress() {
  const won = wordStates.filter(s => s.won).length;
  const pct = (won / words.length) * 100;
  document.getElementById("progressFill").style.width = pct + "%";
  document.getElementById("progressLabel").textContent = `${won} / ${words.length}`;
}

function goWord(step) {
  const next = currentIdx + step;
  if (next < 0 || next >= words.length) return;
  currentIdx = next; initWord();
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

document.addEventListener("keydown", e => {
  if (e.target.tagName === "INPUT") return;
  if (/^[a-zA-Z]$/.test(e.key)) handleGuess(e.key.toUpperCase());
});

function handleSubmit() {
  clearInterval(timerInterval);
  const elapsed = Math.floor((Date.now() - startTime) / 1000);
  const won = wordStates.filter(s => s.won).length;
  const total = words.length;
  const correctPct = Math.round((won / total) * 100);
  const timePct = Math.max(0, Math.round(((totalSeconds - elapsed) / totalSeconds) * 100));
  const streakPct = Math.round((maxStreak / total) * 100);
  const attempt = { started: new Date(startTime).toISOString(), completed: new Date().toISOString(), durationSecs: elapsed, won, total, correctPct, timePct, streakPct };
  fetch(SITE_ROOT + 'api/submit-attempt.php', {
    method: 'POST', headers: {'Content-Type':'application/json'},
    body: JSON.stringify({ quiz_id: QUIZ.quiz_id, score: won, max_score: total, time_taken: elapsed, details: attempt })
  }).then(() => {
    window.location.href = SITE_ROOT + 'quiz/hangman/results.php?quiz_id=' + QUIZ.quiz_id;
  }).catch(() => {
    window.location.href = SITE_ROOT + 'quiz/hangman/results.php?quiz_id=' + QUIZ.quiz_id;
  });
}

initWord();
</script>
</body>
</html>
