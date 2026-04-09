<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();
$uid    = currentUser()['id'];
$certId = (int)($_GET['id'] ?? 0);
$pdo    = getDB();
$cert   = $pdo->prepare("SELECT * FROM certificates WHERE id=? AND user_id=?");
$cert->execute([$certId, $uid]); $cert = $cert->fetch();
if (!$cert) { header('Location: ' . SITE_ROOT . 'profile.php?tab=certs&err=not_found'); exit; }
// Delete file
$fp = UPLOAD_DIR . $cert['filename'];
if (file_exists($fp)) unlink($fp);
$pdo->prepare("DELETE FROM certificates WHERE id=?")->execute([$certId]);
header('Location: ' . SITE_ROOT . 'profile.php?tab=certs'); exit;
