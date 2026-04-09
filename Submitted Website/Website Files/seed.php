<?php
/**
 * Quiz Carnival — Seed File
 * Creates default teacher and student accounts.
 * Delete this file after running.
 */
require_once __DIR__ . '/includes/db.php';
$pdo = getDB();

$accounts = [
    ['username' => 'teacher1',  'password' => 'teacher123', 'name' => 'Ms. Garcia',   'email' => 'teacher1@school.edu',  'role' => 'teacher'],
    ['username' => 'student1',  'password' => 'student123', 'name' => 'Alex Rivera',  'email' => 'student1@school.edu',  'role' => 'student'],
    ['username' => 'student2',  'password' => 'student123', 'name' => 'Jordan Lee',   'email' => 'student2@school.edu',  'role' => 'student'],
];

$stmt = $pdo->prepare("INSERT IGNORE INTO users (username, password, name, email, role) VALUES (?,?,?,?,?)");
foreach ($accounts as $a) {
    $stmt->execute([$a['username'], password_hash($a['password'], PASSWORD_BCRYPT), $a['name'], $a['email'], $a['role']]);
}

echo "<pre style='font-family:sans-serif;padding:40px'>";
echo "✔ Default accounts created:\n\n";
foreach ($accounts as $a) {
    echo "  Username: {$a['username']}  |  Password: {$a['password']}  |  Role: {$a['role']}\n";
}
echo "\n<strong>Delete this file now!</strong>";
echo "</pre>";
