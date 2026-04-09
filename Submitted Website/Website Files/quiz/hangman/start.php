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
$attCount->execute([$quizId, $uid]); $attCount = (int)$attCount->fetchColumn();
$attLeft = $quiz['attempts_allowed'] - $attCount;
$qd = json_decode($quiz['quiz_data'] ?? '{}', true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?= h($quiz['title']) ?> — Start</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
:root{--brand:#f04e84;--brand2:#7c3a91;--bg:#f7f4fb;--surface:#fff;--border:#e8e0f0;--text:#1a1025;--muted:#8b7da0;}
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'DM Sans',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;}
header{background:linear-gradient(135deg,#1a0a2e,#2d1354);padding:0 32px;height:64px;display:flex;align-items:center;gap:12px;}
.logo-mark{width:36px;height:36px;background:linear-gradient(135deg,var(--brand),var(--brand2));border-radius:10px;display:flex;align-items:center;justify-content:center;font-family:'Syne',sans-serif;font-weight:800;font-size:16px;color:#fff;}
header span{font-family:'Syne',sans-serif;font-weight:700;font-size:1.1rem;color:#fff;}
.container{max-width:720px;margin:48px auto;padding:0 20px 60px;}
h1{font-family:'Syne',sans-serif;font-size:2.4rem;font-weight:800;color:var(--text);margin-bottom:6px;}
.breadcrumb{font-size:0.85rem;color:var(--muted);margin-bottom:36px;}
.info-card{background:var(--surface);border:1px solid var(--border);border-radius:16px;padding:28px 32px;margin-bottom:16px;}
.info-card-title{font-family:'Syne',sans-serif;font-size:0.8rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--brand);margin-bottom:14px;}
.info-row{display:flex;align-items:baseline;gap:10px;padding:9px 0;border-bottom:1px solid var(--bg);}
.info-row:last-child{border-bottom:none;}
.info-key{color:var(--muted);font-size:0.88rem;min-width:160px;}
.info-val{color:var(--text);font-weight:500;font-size:0.95rem;}
.warning-box{background:rgba(240,78,132,0.06);border:1px solid rgba(240,78,132,0.2);border-radius:12px;padding:16px 20px;font-size:0.9rem;color:var(--brand2);margin-bottom:24px;line-height:1.6;}
.actions{display:flex;gap:12px;margin-top:28px;}
.btn{padding:13px 28px;border:none;border-radius:12px;font-family:'Syne',sans-serif;font-weight:700;font-size:0.9rem;cursor:pointer;transition:0.2s;text-decoration:none;display:inline-flex;align-items:center;gap:8px;}
.btn-secondary{background:var(--surface);border:1.5px solid var(--border);color:var(--muted);}
.btn-primary{background:linear-gradient(135deg,var(--brand),var(--brand2));color:#fff;box-shadow:0 4px 20px rgba(240,78,132,0.35);}
.btn-primary:hover{transform:translateY(-1px);}
.no-attempts{background:#fee2e2;color:#b91c1c;border-radius:12px;padding:20px;text-align:center;font-weight:600;}
</style>
</head>
<body>
<header><div class="logo-mark">QC</div><span>Quiz Carnival</span></header>
<div class="container">
  <div class="breadcrumb"><a href="<?= SITE_ROOT ?>class.php?id=<?= $quiz['class_id'] ?>" style="color:inherit"><?= h($quiz['class_name']) ?></a> → <strong><?= h($quiz['title']) ?></strong></div>
  <h1><?= h($quiz['title']) ?></h1>
  <div class="warning-box"><?= h($quiz['description'] ?: 'Guess each word one letter at a time before you run out of guesses!') ?></div>
  <div class="info-card">
    <div class="info-card-title">Availability</div>
    <div class="info-row"><span class="info-key">Opens</span><span class="info-val"><?= $quiz['opens_at'] ? formatDate($quiz['opens_at']) : 'Anytime' ?></span></div>
    <div class="info-row"><span class="info-key">Closes</span><span class="info-val"><?= $quiz['closes_at'] ? formatDate($quiz['closes_at']) : 'No deadline' ?></span></div>
  </div>
  <div class="info-card">
    <div class="info-card-title">Quiz Details</div>
    <div class="info-row"><span class="info-key">Type</span><span class="info-val">Hangman</span></div>
    <div class="info-row"><span class="info-key">Words</span><span class="info-val"><?= count($qd['words'] ?? []) ?></span></div>
    <div class="info-row"><span class="info-key">Category</span><span class="info-val"><?= h($qd['category'] ?? 'General') ?></span></div>
    <div class="info-row"><span class="info-key">Guesses per Word</span><span class="info-val"><?= $qd['guessesNum'] ?? 6 ?></span></div>
    <div class="info-row"><span class="info-key">Time Limit</span><span class="info-val"><?= $quiz['time_limit'] ? $quiz['time_limit'].' minutes' : 'No limit' ?></span></div>
    <div class="info-row"><span class="info-key">Attempts Left</span><span class="info-val"><?= $attLeft ?></span></div>
  </div>
  <?php if ($attLeft <= 0): ?>
    <div class="no-attempts">You have used all your attempts.</div>
    <div class="actions"><a href="<?= SITE_ROOT ?>quiz/hangman/results.php?quiz_id=<?= $quizId ?>" class="btn btn-primary">View Results</a></div>
  <?php else: ?>
    <div class="actions">
      <a href="<?= SITE_ROOT ?>class.php?id=<?= $quiz['class_id'] ?>" class="btn btn-secondary">← Back</a>
      <a href="<?= SITE_ROOT ?>quiz/hangman/play.php?quiz_id=<?= $quizId ?>" class="btn btn-primary">Start Quiz →</a>
    </div>
  <?php endif; ?>
</div>
</body>
</html>
