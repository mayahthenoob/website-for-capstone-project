<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json');
requireTeacher();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonError('Method not allowed', 405);
$data      = json_decode(file_get_contents('php://input'), true);
$studentId = (int)($data['student_id'] ?? 0);
$quizId    = (int)($data['quiz_id']    ?? 0);
$grade     = isset($data['grade']) ? (float)$data['grade'] : null;
$note      = trim($data['note'] ?? '');
$uid       = currentUser()['id'];
$pdo       = getDB();
// Verify teacher owns the quiz's class
$stmt = $pdo->prepare("SELECT q.id FROM quizzes q JOIN classes c ON q.class_id=c.id WHERE q.id=? AND c.teacher_id=?");
$stmt->execute([$quizId, $uid]);
if (!$stmt->fetch()) jsonError('Access denied', 403);
$pdo->prepare("INSERT INTO grades (quiz_id, student_id, grade, note) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE grade=VALUES(grade), note=VALUES(note)")->execute([$quizId, $studentId, $grade, $note]);
jsonSuccess();
