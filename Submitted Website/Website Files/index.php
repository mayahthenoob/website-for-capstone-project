<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';

// If already logged in, go to dashboard
if (isLoggedIn()) {
    header('Location: ' . SITE_ROOT . 'dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="Quiz Carnival — The best quiz platform for students with four fun gamemodes.">
<title>Quiz Carnival — Fun Quizzes for Students</title>
<link rel="icon" href="<?= SITE_ROOT ?>assets/logo.png" type="image/png">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
<style>
:root {
  --bg-blue: #e8f4ff;
  --accent: #4318FF;
  --hot-pink: #d81b60;
  --yellow: #ffb400;
  --text: #1b2559;
  --muted: #64748b;
  --white: #fff;
  --dark: #111827;
}
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Inter', sans-serif; background: var(--bg-blue); color: var(--text); }

/* Navbar */
nav.navbar {
  background: var(--dark); height: 64px;
  display: flex; align-items: center; justify-content: space-between;
  padding: 0 32px; position: sticky; top: 0; z-index: 100;
}
.nav-logo { display: flex; align-items: center; gap: 10px; text-decoration: none; }
.nav-logo img { height: 38px; border-radius: 8px; }
.nav-logo span { color: #fff; font-size: 1.15rem; font-weight: 700; }
.nav-btn {
  background: var(--yellow); color: var(--dark); text-decoration: none;
  font-weight: 700; font-size: 0.85rem; padding: 10px 22px;
  border-radius: 25px; text-transform: uppercase; letter-spacing: 0.5px; transition: 0.2s;
}
.nav-btn:hover { background: #fff; color: var(--hot-pink); }

/* Hero */
.hero {
  max-width: 1100px; margin: 60px auto 50px;
  padding: 0 24px; display: flex; align-items: center; gap: 50px;
}
.hero-text { flex: 1; }
.hero-text h1 {
  font-size: clamp(2.4rem, 5vw, 3.8rem); color: var(--hot-pink);
  font-weight: 800; line-height: 1.1; margin-bottom: 16px;
}
.hero-text p { color: var(--muted); font-size: 1.05rem; line-height: 1.7; max-width: 440px; margin-bottom: 28px; }
.btn-primary {
  background: var(--accent); color: #fff; text-decoration: none;
  font-weight: 700; padding: 14px 36px; border-radius: 30px;
  display: inline-block; font-size: 0.95rem; transition: 0.2s;
  box-shadow: 0 6px 20px rgba(67,24,255,0.3);
}
.btn-primary:hover { background: #3310cc; transform: translateY(-2px); }
.hero-graphic {
  flex-shrink: 0; width: 340px; height: 280px;
  background: linear-gradient(135deg, #4318FF22, #d81b6022);
  border-radius: 24px; display: flex; align-items: center; justify-content: center;
}
.hero-graphic .big-icon { font-size: 120px; color: var(--accent); opacity: 0.5; }

/* Section heading */
.section-heading {
  text-align: center; font-size: 1.6rem; font-weight: 800;
  color: var(--text); margin-bottom: 32px;
}

/* Game mode grid */
.modes-grid {
  max-width: 1100px; margin: 0 auto 80px;
  padding: 0 24px;
  display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px;
}
@media (max-width: 900px) { .modes-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 500px) { .modes-grid { grid-template-columns: 1fr; } }

.mode-card {
  background: #fff; border-radius: 20px; padding: 32px 20px;
  text-align: center; box-shadow: 0 4px 24px rgba(0,0,0,0.06);
  transition: 0.2s; border: 2px solid transparent;
  display: flex; flex-direction: column; align-items: center; gap: 14px;
}
.mode-card:hover { transform: translateY(-4px); border-color: var(--accent); box-shadow: 0 10px 30px rgba(67,24,255,0.12); }
.mode-icon {
  width: 70px; height: 70px; border-radius: 16px;
  display: flex; align-items: center; justify-content: center; font-size: 34px;
}
.mode-card h3 { font-size: 1rem; font-weight: 700; color: var(--text); }
.mode-card p { font-size: 0.85rem; color: var(--muted); line-height: 1.5; }

.m1 .mode-icon { background: #fff0f6; color: var(--hot-pink); }
.m2 .mode-icon { background: #ede9ff; color: var(--accent); }
.m3 .mode-icon { background: #fff8e1; color: #f59e0b; }
.m4 .mode-icon { background: #e0f7f4; color: #00897b; }

/* Footer */
footer { text-align: center; padding: 30px; color: var(--muted); font-size: 0.88rem; border-top: 1px solid #dde6f0; }
</style>
</head>
<body>

<nav class="navbar">
  <a class="nav-logo" href="<?= SITE_ROOT ?>">
    <img src="<?= SITE_ROOT ?>assets/logo.png" alt="Quiz Carnival">
    <span>Quiz Carnival</span>
  </a>
  <a href="<?= SITE_ROOT ?>login.php" class="nav-btn">Login</a>
</nav>

<main>
  <section class="hero">
    <div class="hero-text">
      <h1>Quiz Carnival</h1>
      <p>The best quiz platform for students. Challenge yourself with four unique and exciting gamemodes designed to make learning fun and engaging.</p>
      <a href="<?= SITE_ROOT ?>login.php" class="btn-primary">Get Started</a>
    </div>
    <div class="hero-graphic">
      <i class='bx bxs-party hero-graphic big-icon'></i>
    </div>
  </section>

  <h2 class="section-heading">Game Modes</h2>
  <section class="modes-grid">
    <div class="mode-card m1">
      <div class="mode-icon"><i class='bx bxs-invader'></i></div>
      <h3>Spelling Bee</h3>
      <p>Listen and spell words correctly to prove your language skills.</p>
    </div>
    <div class="mode-card m2">
      <div class="mode-icon"><i class='bx bx-search-alt-2'></i></div>
      <h3>Word Search</h3>
      <p>Hunt through a grid of letters to find hidden words against the clock.</p>
    </div>
    <div class="mode-card m3">
      <div class="mode-icon"><i class='bx bx-color-fill'></i></div>
      <h3>Fill in the Blanks</h3>
      <p>Drag and drop words to complete the sentences correctly.</p>
    </div>
    <div class="mode-card m4">
      <div class="mode-icon"><i class='bx bx-male'></i></div>
      <h3>Hangman</h3>
      <p>Guess the hidden word one letter at a time before time runs out.</p>
    </div>
  </section>
</main>

<footer>&copy; <?= date('Y') ?> Quiz Carnival. All rights reserved.</footer>
</body>
</html>
