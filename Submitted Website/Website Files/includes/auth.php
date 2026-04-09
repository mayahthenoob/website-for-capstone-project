<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// SITE_ROOT may not be defined yet when auth.php is loaded standalone, so define a fallback
if (!defined('SITE_ROOT')) {
    define('SITE_ROOT', '/');
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . SITE_ROOT . 'login.php');
        exit;
    }
}

function requireTeacher() {
    requireLogin();
    if ($_SESSION['user_role'] !== 'teacher') {
        header('Location: ' . SITE_ROOT . 'dashboard.php');
        exit;
    }
}

function currentUser() {
    if (!isLoggedIn()) return null;
    return [
        'id'       => $_SESSION['user_id'],
        'name'     => $_SESSION['user_name'],
        'username' => $_SESSION['user_username'],
        'role'     => $_SESSION['user_role'],
        'avatar'   => $_SESSION['user_avatar'] ?? 'assets/default-avatar.jpg',
    ];
}

function isTeacher() {
    return isLoggedIn() && $_SESSION['user_role'] === 'teacher';
}

function refreshUserSession($pdo) {
    if (!isLoggedIn()) return;
    $stmt = $pdo->prepare("SELECT id, name, username, role, profile_pic FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $u = $stmt->fetch();
    if ($u) {
        $_SESSION['user_name']     = $u['name'];
        $_SESSION['user_username'] = $u['username'];
        $_SESSION['user_role']     = $u['role'];
        $_SESSION['user_avatar']   = $u['profile_pic'] ?: 'assets/default-avatar.jpg';
    }
}
