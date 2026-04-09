<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json');
requireLogin();
if (currentUser()['role'] !== 'student') jsonError('Only students can submit attempts');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonError('Method not allowed', 405);
$data      = json_decode(file_get_contents('php://input'), true);
$quizId    = (int)($data['quiz_id']    ?? 0);
$score     = (float)($data['score']    ?? 0);
$maxScore  = (float)($data['max_score'] ?? 0);
$timeTaken = (int)($data['time_taken'] ?? 0);
$details   = $data['details'] ?? null;
$uid       = currentUser()['id'];
$pdo       = getDB();

// Verify quiz is published and student is enrolled
$stmt = $pdo->prepare("SELECT q.* FROM quizzes q JOIN classes c ON q.class_id=c.id JOIN enrollments e ON e.class_id=c.id WHERE q.id=? AND e.student_id=? AND q.published=1");
$stmt->execute([$quizId, $uid]);
$quiz = $stmt->fetch();
if (!$quiz) jsonError('Quiz not found or not accessible', 403);

// Check attempt limit
$cnt = $pdo->prepare("SELECT COUNT(*) FROM quiz_attempts WHERE quiz_id=? AND student_id=?");
$cnt->execute([$quizId, $uid]);
if ((int)$cnt->fetchColumn() >= $quiz['attempts_allowed']) jsonError('Attempt limit reached');

$detailsJson = $details ? json_encode($details) : null;
$pdo->prepare("INSERT INTO quiz_attempts (quiz_id, student_id, score, max_score, time_taken, attempt_data) VALUES (?,?,?,?,?,?)")
    ->execute([$quizId, $uid, $score, $maxScore, $timeTaken, $detailsJson]);
jsonSuccess(['attempt_id' => $pdo->lastInsertId()]);
