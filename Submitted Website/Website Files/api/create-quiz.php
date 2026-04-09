<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json');
requireTeacher();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonError('Method not allowed', 405);
$data    = json_decode(file_get_contents('php://input'), true);
$classId = (int)($data['class_id'] ?? 0);
$title   = trim($data['title'] ?? '');
$desc    = trim($data['description'] ?? '');
$type    = $data['type'] ?? '';
$quizData = $data['quiz_data'] ?? null;
$opensAt  = $data['opens_at'] ?? null;
$closesAt = $data['closes_at'] ?? null;
$timeLimit= isset($data['time_limit']) ? (int)$data['time_limit'] : null;
$attempts = isset($data['attempts_allowed']) ? (int)$data['attempts_allowed'] : 2;
$grading  = $data['grading_criteria'] ?? 'highestScore';
$quizId   = (int)($data['quiz_id'] ?? 0);

$allowedTypes = ['wordsearch','fillinblanks','hangman'];
if (!in_array($type, $allowedTypes)) jsonError('Invalid quiz type');
if (!$title) jsonError('Title is required');
$uid = currentUser()['id'];
$pdo = getDB();
// Verify teacher owns class
$cls = $pdo->prepare("SELECT id FROM classes WHERE id=? AND teacher_id=?"); $cls->execute([$classId, $uid]);
if (!$cls->fetch()) jsonError('Class not found or access denied', 403);

$jsonStr = $quizData ? json_encode($quizData) : null;

if ($quizId > 0) {
    // Update
    $q = $pdo->prepare("SELECT id FROM quizzes WHERE id=? AND class_id=?"); $q->execute([$quizId, $classId]);
    if (!$q->fetch()) jsonError('Quiz not found', 404);
    $pdo->prepare("UPDATE quizzes SET title=?,description=?,type=?,quiz_data=?,opens_at=?,closes_at=?,time_limit=?,attempts_allowed=?,grading_criteria=? WHERE id=?")
        ->execute([$title,$desc,$type,$jsonStr,$opensAt,$closesAt,$timeLimit,$attempts,$grading,$quizId]);
    jsonSuccess(['quiz_id' => $quizId]);
} else {
    // Create
    $pdo->prepare("INSERT INTO quizzes (class_id,title,description,type,quiz_data,opens_at,closes_at,time_limit,attempts_allowed,grading_criteria) VALUES (?,?,?,?,?,?,?,?,?,?)")
        ->execute([$classId,$title,$desc,$type,$jsonStr,$opensAt,$closesAt,$timeLimit,$attempts,$grading]);
    jsonSuccess(['quiz_id' => $pdo->lastInsertId()]);
}
