<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
if (session_status() === PHP_SESSION_NONE) session_start();
requireLogin();

$pdo    = getDB();
$uid    = currentUser()['id'];
$quizId = (int)($_GET['quiz_id'] ?? 0);

$stmt = $pdo->prepare("SELECT q.*, c.name as class_name, c.id as class_id FROM quizzes q JOIN classes c ON q.class_id=c.id WHERE q.id=?");
$stmt->execute([$quizId]); $quiz = $stmt->fetch();
if (!$quiz) { header('Location: ' . SITE_ROOT . 'dashboard.php'); exit; }

// Get this student's attempts
$attempts = $pdo->prepare("SELECT * FROM quiz_attempts WHERE quiz_id=? AND student_id=? ORDER BY submitted_at ASC");
$attempts->execute([$quizId, $uid]); $attempts = $attempts->fetchAll();

$lastAttempt = !empty($attempts) ? $attempts[count($attempts)-1] : null;
$attemptsJson = json_encode(array_map(function($a) {
    $details = json_decode($a['attempt_data'] ?? '{}', true);
    return array_merge($details ?: [], ['score' => $a['score'], 'max_score' => $a['max_score'], 'time_taken' => $a['time_taken']]);
}, $attempts));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Results — <?= h($quiz['title']) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800;900&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
:root{--brand:#f04e84;--brand2:#7c3a91;--card-bg:linear-gradient(160deg,#0f1f55,#060e25);--glow:#3081d0;--cyan:#00d2ff;--green:#5effa3;--gold:#f1c40f;}
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Syne',sans-serif;background:radial-gradient(ellipse at top,#0d0630,#020008 70%);min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:40px 20px;overflow:hidden;}
body::before{content:'';position:fixed;inset:0;background-image:radial-gradient(1px 1px at 20% 30%,rgba(255,255,255,0.6) 0%,transparent 100%),radial-gradient(1px 1px at 60% 70%,rgba(255,255,255,0.4) 0%,transparent 100%),radial-gradient(2px 2px at 50% 40%,rgba(0,210,255,0.3) 0%,transparent 100%);pointer-events:none;z-index:0;}
.game-card{position:relative;width:100%;max-width:600px;background:var(--card-bg);border:3px solid var(--glow);border-radius:12px;padding:80px 32px 100px;box-shadow:0 0 60px rgba(0,210,255,0.2);z-index:1;animation:cardIn 0.6s cubic-bezier(0.34,1.56,0.64,1) both;}
@keyframes cardIn{from{opacity:0;transform:scale(0.85) translateY(30px);}to{opacity:1;transform:scale(1) translateY(0);}}
.header-tab{position:absolute;top:-22px;left:50%;transform:translateX(-50%);background:linear-gradient(180deg,#2ecc71,#1e8449);color:#fff;padding:10px 60px;font-weight:900;font-size:1.8rem;letter-spacing:4px;border:2px solid var(--green);clip-path:polygon(8% 0%,92% 0%,100% 100%,0% 100%);white-space:nowrap;}
.quiz-name{text-align:center;font-size:0.8rem;color:rgba(255,255,255,0.4);letter-spacing:2px;text-transform:uppercase;margin-bottom:28px;}
.inner-frame{border:1px solid rgba(48,129,208,0.3);border-radius:8px;padding:36px 20px 28px;}
.circles-row{display:flex;justify-content:space-around;gap:16px;flex-wrap:wrap;}
.circle-item{display:flex;flex-direction:column;align-items:center;gap:14px;}
.circle-svg-wrap{position:relative;width:130px;height:130px;}
.circle-svg-wrap svg{width:130px;height:130px;transform:rotate(-90deg);}
.track{fill:none;stroke:rgba(48,129,208,0.2);stroke-width:10;}
.fill{fill:none;stroke-width:10;stroke-linecap:round;stroke-dasharray:345;stroke-dashoffset:345;transition:stroke-dashoffset 1.2s cubic-bezier(0.4,0,0.2,1);}
.fill-correct{stroke:url(#grad-correct);}
.fill-speed{stroke:url(#grad-speed);}
.fill-streak{stroke:url(#grad-streak);}
.circle-inner{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;}
.circle-pct{font-size:1.6rem;font-weight:900;color:var(--cyan);line-height:1;}
.circle-correct .circle-pct{color:var(--green);}
.circle-streak .circle-pct{color:var(--gold);}
.circle-label{font-size:0.8rem;color:rgba(255,255,255,0.7);text-transform:uppercase;letter-spacing:2px;font-weight:700;}
.divider{border:none;border-top:1px solid rgba(48,129,208,0.2);margin:24px 0 20px;}
.stats-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
.stat-box{background:rgba(48,129,208,0.06);border:1px solid rgba(48,129,208,0.15);border-radius:8px;padding:16px 12px;text-align:center;}
.stat-val{font-size:1.4rem;font-weight:900;color:var(--cyan);display:block;margin-bottom:4px;}
.stat-key{font-size:0.7rem;color:rgba(255,255,255,0.4);text-transform:uppercase;letter-spacing:1px;}
.nav-buttons{position:absolute;bottom:0;left:0;right:0;display:flex;border-top:1px solid rgba(48,129,208,0.2);}
.btn{flex:1;padding:20px;background:none;border:none;color:rgba(255,255,255,0.7);font-family:'Syne',sans-serif;font-weight:700;font-size:0.88rem;cursor:pointer;transition:0.2s;letter-spacing:1px;text-decoration:none;display:flex;align-items:center;justify-content:center;}
.btn:first-child{border-right:1px solid rgba(48,129,208,0.2);}
.btn:hover{color:#fff;background:rgba(255,255,255,0.05);}
.attempt-num{font-size:0.75rem;color:rgba(255,255,255,0.35);text-align:center;margin-bottom:6px;}
</style>
</head>
<body>
<div class="game-card">
  <div class="header-tab">RESULTS</div>
  <svg width="0" height="0"><defs>
    <linearGradient id="grad-correct" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" stop-color="#5effa3"/><stop offset="100%" stop-color="#00c896"/></linearGradient>
    <linearGradient id="grad-speed" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" stop-color="#00d2ff"/><stop offset="100%" stop-color="#3081d0"/></linearGradient>
    <linearGradient id="grad-streak" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" stop-color="#f1c40f"/><stop offset="100%" stop-color="#e67e22"/></linearGradient>
  </defs></svg>

  <div class="attempt-num" id="attemptNum"></div>
  <div class="quiz-name"><?= h($quiz['title']) ?></div>

  <?php if ($lastAttempt): ?>
  <div class="inner-frame">
    <div class="circles-row">
      <div class="circle-item circle-correct">
        <div class="circle-svg-wrap"><svg viewBox="0 0 130 130"><circle class="track" cx="65" cy="65" r="55"/><circle class="fill fill-correct" id="fillCorrect" cx="65" cy="65" r="55"/></svg><div class="circle-inner"><span class="circle-pct" id="pctCorrect">0%</span></div></div>
        <div class="circle-label">Correct</div>
      </div>
      <div class="circle-item">
        <div class="circle-svg-wrap"><svg viewBox="0 0 130 130"><circle class="track" cx="65" cy="65" r="55"/><circle class="fill fill-speed" id="fillSpeed" cx="65" cy="65" r="55"/></svg><div class="circle-inner"><span class="circle-pct" id="pctSpeed">0%</span></div></div>
        <div class="circle-label">Speed</div>
      </div>
      <div class="circle-item circle-streak">
        <div class="circle-svg-wrap"><svg viewBox="0 0 130 130"><circle class="track" cx="65" cy="65" r="55"/><circle class="fill fill-streak" id="fillStreak" cx="65" cy="65" r="55"/></svg><div class="circle-inner"><span class="circle-pct" id="pctStreak">0%</span></div></div>
        <div class="circle-label">Streak</div>
      </div>
    </div>
    <hr class="divider">
    <div class="stats-grid">
      <div class="stat-box"><span class="stat-val" id="statWords">—</span><span class="stat-key">Words Found</span></div>
      <div class="stat-box"><span class="stat-val" id="statTime">—</span><span class="stat-key">Time Taken</span></div>
    </div>
  </div>
  <?php else: ?>
  <p style="color:rgba(255,255,255,0.6);text-align:center;padding:40px 0">No attempts yet.</p>
  <?php endif; ?>

  <div class="nav-buttons">
    <a href="<?= SITE_ROOT ?>class.php?id=<?= $quiz['class_id'] ?>" class="btn">← Back</a>
    <a href="<?= SITE_ROOT ?>quiz/word-search/end.php?quiz_id=<?= $quizId ?>" class="btn">Details →</a>
  </div>
</div>

<script>
const attempts = <?= $attemptsJson ?>;
const totalAttempts = attempts.length;
const attempt = attempts[totalAttempts - 1] || null;
if (attempt) {
  document.getElementById("attemptNum").textContent = `Attempt ${totalAttempts} of <?= $quiz['attempts_allowed'] ?>`;
  const { correctPct = 0, timePct = 0, streakPct = 0, found = 0, total = 0, durationSecs = 0 } = attempt;
  const mins = Math.floor(durationSecs / 60), secs = durationSecs % 60;
  document.getElementById("statWords").textContent = `${found || attempt.score || 0} / ${total || attempt.max_score || 0}`;
  document.getElementById("statTime").textContent = `${mins}m ${secs}s`;
  const C = 2 * Math.PI * 55;
  function animateCircle(fillId, pctEl, pct, delay) {
    setTimeout(() => {
      document.getElementById(fillId).style.strokeDashoffset = C - (pct / 100) * C;
      let cur = 0, step = pct / 60;
      const iv = setInterval(() => { cur = Math.min(cur + step, pct); pctEl.textContent = Math.round(cur) + "%"; if (cur >= pct) clearInterval(iv); }, 20);
    }, delay);
  }
  animateCircle("fillCorrect", document.getElementById("pctCorrect"), correctPct, 300);
  animateCircle("fillSpeed", document.getElementById("pctSpeed"), timePct || 0, 500);
  animateCircle("fillStreak", document.getElementById("pctStreak"), streakPct || 0, 700);
}
</script>
</body>
</html>
