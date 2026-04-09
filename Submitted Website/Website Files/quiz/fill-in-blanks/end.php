<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
if (session_status() === PHP_SESSION_NONE) session_start();
requireLogin();
$pdo = getDB(); $uid = currentUser()['id'];
$quizId = (int)($_GET['quiz_id'] ?? 0);
$stmt = $pdo->prepare("SELECT q.*, c.id as class_id FROM quizzes q JOIN classes c ON q.class_id=c.id WHERE q.id=?");
$stmt->execute([$quizId]); $quiz = $stmt->fetch();
if (!$quiz) { header('Location: ' . SITE_ROOT . 'dashboard.php'); exit; }
$attempts = $pdo->prepare("SELECT * FROM quiz_attempts WHERE quiz_id=? AND student_id=? ORDER BY submitted_at ASC");
$attempts->execute([$quizId, $uid]); $attempts = $attempts->fetchAll();
$canRetry = count($attempts) < $quiz['attempts_allowed'];
$quizData = json_decode($quiz['quiz_data'] ?? '{}', true);
$totalSentences = count($quizData['sentences'] ?? []);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Details — <?= h($quiz['title']) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800;900&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Syne',sans-serif;background:radial-gradient(ellipse at top,#0d0630,#020008 70%);min-height:100vh;display:flex;flex-direction:column;align-items:center;padding:40px 20px;}
.page-wrap{width:100%;max-width:700px;z-index:1;}
h1{font-size:2rem;font-weight:800;color:#fff;margin-bottom:4px;}
.sub{font-size:0.9rem;color:rgba(255,255,255,0.4);margin-bottom:32px;letter-spacing:1px;}
.attempt-card{background:linear-gradient(160deg,#0f1f55,#060e25);border:1.5px solid rgba(48,129,208,0.3);border-radius:12px;margin-bottom:16px;overflow:hidden;animation:fadeIn 0.4s ease both;}
@keyframes fadeIn{from{opacity:0;transform:translateY(20px);}to{opacity:1;transform:translateY(0);}}
.attempt-header{padding:16px 20px;display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid rgba(48,129,208,0.15);}
.attempt-num-label{font-size:0.8rem;font-weight:700;color:rgba(255,255,255,0.5);text-transform:uppercase;letter-spacing:1px;}
.attempt-badge{padding:4px 12px;border-radius:20px;font-size:11px;font-weight:700;background:rgba(48,129,208,0.15);color:#00d2ff;}
.attempt-badge.best{background:rgba(241,196,15,0.15);color:#f1c40f;}
.attempt-body{padding:0 20px;}
table{width:100%;border-collapse:collapse;}
.td-key{padding:11px 0;font-size:0.82rem;color:rgba(255,255,255,0.4);text-transform:uppercase;letter-spacing:0.5px;width:45%;}
.td-val{padding:11px 0;font-size:0.88rem;color:rgba(255,255,255,0.85);font-weight:500;}
tr{border-bottom:1px solid rgba(48,129,208,0.08);}
tr:last-child{border-bottom:none;}
.score-bar-wrap{margin-top:6px;background:rgba(48,129,208,0.1);border-radius:99px;height:5px;overflow:hidden;}
.score-bar-fill{height:100%;background:linear-gradient(90deg,#5effa3,#00c896);border-radius:99px;}
.mini-scores{display:flex;justify-content:space-around;padding:16px 20px;border-top:1px solid rgba(48,129,208,0.08);}
.mini-score{text-align:center;}
.mini-score-val{font-size:1.3rem;font-weight:900;display:block;margin-bottom:2px;}
.mini-score-key{font-size:0.7rem;color:rgba(255,255,255,0.35);text-transform:uppercase;letter-spacing:1px;}
.ms-correct{color:#5effa3;}.ms-speed{color:#00d2ff;}.ms-streak{color:#f1c40f;}
.attempt-footer{padding:12px 20px;font-size:0.78rem;color:rgba(255,255,255,0.25);border-top:1px solid rgba(48,129,208,0.08);}
.nav-row{display:flex;gap:12px;margin-top:28px;}
.btn{padding:13px 28px;border:none;border-radius:12px;font-family:'Syne',sans-serif;font-weight:700;font-size:0.9rem;cursor:pointer;transition:0.2s;text-decoration:none;display:inline-flex;align-items:center;gap:8px;}
.btn-back{background:rgba(255,255,255,0.08);color:rgba(255,255,255,0.7);border:1px solid rgba(255,255,255,0.15);}
.btn-retry{background:linear-gradient(135deg,#f04e84,#7c3a91);color:#fff;box-shadow:0 4px 20px rgba(240,78,132,0.35);}
.btn-retry:hover{transform:translateY(-1px);}
</style>
</head>
<body>
<div class="page-wrap">
  <h1>Quiz Details</h1>
  <div class="sub"><?= h($quiz['title']) ?></div>
  <?php if (empty($attempts)): ?>
    <p style="color:rgba(255,255,255,0.4);text-align:center;padding:40px">No attempts yet.</p>
  <?php else:
    $bestIdx = 0;
    foreach ($attempts as $i => $a) { if ($a['score'] > $attempts[$bestIdx]['score']) $bestIdx = $i; }
    foreach ($attempts as $i => $a):
      $det = json_decode($a['attempt_data']??'{}',true)??[];
      $cPct = $det['correctPct'] ?? ($totalSentences>0?round($a['score']/$totalSentences*100):0);
      $tPct = $det['timePct']??0; $sPct = $det['streakPct']??0;
      $isBest = count($attempts)>1 && $i===$bestIdx;
      $dur = $a['time_taken']??0; $mins=floor($dur/60); $secs=$dur%60;
  ?>
    <div class="attempt-card" style="animation-delay:<?= $i*0.1 ?>s">
      <div class="attempt-header">
        <span class="attempt-num-label">Attempt <?= $i+1 ?></span>
        <span class="attempt-badge <?= $isBest?'best':'' ?>"><?= $isBest?'⭐ Best':'Completed' ?></span>
      </div>
      <div class="attempt-body">
        <table>
          <tr><td class="td-key">Status</td><td class="td-val">Finished</td></tr>
          <tr><td class="td-key">Duration</td><td class="td-val"><?= $mins ?>m <?= $secs ?>s</td></tr>
          <tr><td class="td-key">Blanks Correct</td><td class="td-val"><?= (int)$a['score'] ?> / <?= $totalSentences ?></td></tr>
          <tr><td class="td-key">Grade</td><td class="td-val"><?= (int)$a['score'] ?>.00 out of <?= $totalSentences ?>.00 <strong>(<?= $cPct ?>%)</strong>
            <div class="score-bar-wrap"><div class="score-bar-fill" style="width:<?= $cPct ?>%"></div></div>
          </td></tr>
        </table>
      </div>
      <div class="mini-scores">
        <div class="mini-score"><span class="mini-score-val ms-correct"><?= $cPct ?>%</span><span class="mini-score-key">Correct</span></div>
        <div class="mini-score"><span class="mini-score-val ms-speed"><?= $tPct ?>%</span><span class="mini-score-key">Speed</span></div>
        <div class="mini-score"><span class="mini-score-val ms-streak"><?= $sPct ?>%</span><span class="mini-score-key">Streak</span></div>
      </div>
      <div class="attempt-footer">Reviews are not permitted</div>
    </div>
  <?php endforeach; endif; ?>
  <div class="nav-row">
    <a href="<?= SITE_ROOT ?>class.php?id=<?= $quiz['class_id'] ?>" class="btn btn-back">← Back to Class</a>
    <?php if ($canRetry): ?><a href="<?= SITE_ROOT ?>quiz/fill-in-blanks/start.php?quiz_id=<?= $quizId ?>" class="btn btn-retry">Retry →</a><?php endif; ?>
  </div>
</div>
</body>
</html>
