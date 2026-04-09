<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json');
requireTeacher();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonError('Method not allowed', 405);
$data      = json_decode(file_get_contents('php://input'), true);
$quizId    = (int)($data['quiz_id'] ?? 0);
$published = (int)(bool)($data['published'] ?? 0);
$uid       = currentUser()['id'];
$pdo       = getDB();
// Verify teacher owns class
$stmt = $pdo->prepare("SELECT q.id FROM quizzes q JOIN classes c ON q.class_id=c.id WHERE q.id=? AND c.teacher_id=?");
$stmt->execute([$quizId, $uid]);
if (!$stmt->fetch()) jsonError('Quiz not found or access denied', 403);
$pdo->prepare("UPDATE quizzes SET published=? WHERE id=?")->execute([$published, $quizId]);
jsonSuccess();
