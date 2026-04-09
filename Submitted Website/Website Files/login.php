<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    header('Location: ' . SITE_ROOT . 'dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        $pdo  = getDB();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id']       = $user['id'];
            $_SESSION['user_name']     = $user['name'];
            $_SESSION['user_username'] = $user['username'];
            $_SESSION['user_role']     = $user['role'];
            $_SESSION['user_avatar']   = $user['profile_pic'] ?: 'assets/default-avatar.jpg';
            header('Location: ' . SITE_ROOT . 'dashboard.php');
            exit;
        } else {
            $error = 'Incorrect username or password.';
        }
    } else {
        $error = 'Please enter your username and password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login — Quiz Carnival</title>
<link rel="icon" href="<?= SITE_ROOT ?>assets/logo.png" type="image/png">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
  font-family: 'Inter', sans-serif;
  background: url('https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=1920&q=80') no-repeat center center fixed;
  background-size: cover;
  display: flex; justify-content: center; align-items: center; min-height: 100vh;
}
.card {
  background: rgba(255,255,255,0.93);
  backdrop-filter: blur(12px);
  padding: 44px 36px;
  border-radius: 20px;
  box-shadow: 0 20px 50px rgba(0,0,0,0.22);
  width: 100%; max-width: 380px;
  text-align: center;
}
.logo { margin-bottom: 28px; }
.logo img { height: 64px; border-radius: 12px; }
.logo p { margin-top: 10px; font-size: 1.3rem; font-weight: 800; color: #1b2559; }
.logo span { font-size: 0.85rem; color: #64748b; }
form { display: flex; flex-direction: column; gap: 14px; }
input[type=text], input[type=password] {
  padding: 14px 16px; border: 1.5px solid #e2e8f0; border-radius: 10px;
  font-size: 0.95rem; font-family: 'Inter',sans-serif; background: #f8fafc;
  transition: 0.2s; width: 100%;
}
input:focus { outline: none; border-color: #4318FF; box-shadow: 0 0 0 3px rgba(67,24,255,0.1); background: #fff; }
.btn-login {
  background: #1b2559; color: #fff; border: none; padding: 14px;
  border-radius: 10px; font-size: 1rem; font-weight: 700; cursor: pointer;
  font-family: 'Inter',sans-serif; transition: 0.2s; margin-top: 4px;
}
.btn-login:hover { background: #4318FF; }
.error {
  background: #fee2e2; color: #b91c1c; border-radius: 8px;
  padding: 10px 14px; font-size: 0.88rem; text-align: left; font-weight: 500;
}
.footer-note { margin-top: 20px; font-size: 0.85rem; color: #64748b; }
.footer-note a { color: #4318FF; text-decoration: none; font-weight: 600; }
</style>
</head>
<body>
<div class="card">
  <div class="logo">
    <img src="<?= SITE_ROOT ?>assets/logo.png" alt="Quiz Carnival">
    <p>Quiz Carnival</p>
    <span>Sign in to continue</span>
  </div>

  <?php if ($error): ?>
    <div class="error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="POST">
    <input type="text" name="username" placeholder="Username" required autocomplete="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
    <input type="password" name="password" placeholder="Password" required autocomplete="current-password">
    <button type="submit" class="btn-login">Log In</button>
  </form>

  <p class="footer-note">
    <a href="<?= SITE_ROOT ?>">← Back to homepage</a>
  </p>
</div>
</body>
</html>
