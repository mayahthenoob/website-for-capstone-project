<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json');
requireLogin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonError('Method not allowed', 405);
$data = json_decode(file_get_contents('php://input'), true);
$cid  = (int)($data['class_id'] ?? 0);
$uid  = currentUser()['id'];
if ($uid === null || $cid <= 0) jsonError('Invalid request');
if (currentUser()['role'] !== 'student') jsonError('Only students can enrol');
$pdo = getDB();
// Check class exists
$cls = $pdo->prepare("SELECT id FROM classes WHERE id=?"); $cls->execute([$cid]);
if (!$cls->fetch()) jsonError('Class not found', 404);
// Enrol
try {
    $pdo->prepare("INSERT IGNORE INTO enrollments (class_id, student_id) VALUES (?,?)")->execute([$cid, $uid]);
    jsonSuccess();
} catch (PDOException $e) { jsonError('Failed to enrol'); }
