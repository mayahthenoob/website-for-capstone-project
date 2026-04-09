<?php
/**
 * Quiz Carnival — Database Installer
 * Visit this file once to set up all tables, then delete it.
 */
require_once __DIR__ . '/includes/db.php';
$pdo = getDB();

$sql = <<<SQL

CREATE TABLE IF NOT EXISTS `users` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `username`    VARCHAR(80)  NOT NULL UNIQUE,
  `password`    VARCHAR(255) NOT NULL,
  `name`        VARCHAR(150) NOT NULL,
  `email`       VARCHAR(150) DEFAULT NULL,
  `role`        ENUM('teacher','student') NOT NULL DEFAULT 'student',
  `profile_pic` VARCHAR(255) DEFAULT NULL,
  `bio`         TEXT DEFAULT NULL,
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `classes` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name`        VARCHAR(200) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `teacher_id`  INT UNSIGNED NOT NULL,
  `color`       VARCHAR(20)  NOT NULL DEFAULT '#4318FF',
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`teacher_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `enrollments` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `class_id`    INT UNSIGNED NOT NULL,
  `student_id`  INT UNSIGNED NOT NULL,
  `enrolled_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_enrollment` (`class_id`,`student_id`),
  FOREIGN KEY (`class_id`)   REFERENCES `classes`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`student_id`) REFERENCES `users`(`id`)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `quizzes` (
  `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `class_id`         INT UNSIGNED NOT NULL,
  `title`            VARCHAR(200) NOT NULL,
  `description`      TEXT DEFAULT NULL,
  `type`             ENUM('wordsearch','fillinblanks','hangman','spellingbee') NOT NULL,
  `quiz_data`        LONGTEXT DEFAULT NULL COMMENT 'JSON quiz content',
  `opens_at`         DATETIME DEFAULT NULL,
  `closes_at`        DATETIME DEFAULT NULL,
  `time_limit`       INT DEFAULT NULL COMMENT 'minutes',
  `attempts_allowed` TINYINT DEFAULT 2,
  `grading_criteria` VARCHAR(50) DEFAULT 'highestScore',
  `published`        TINYINT(1) DEFAULT 0,
  `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`class_id`) REFERENCES `classes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `quiz_attempts` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `quiz_id`      INT UNSIGNED NOT NULL,
  `student_id`   INT UNSIGNED NOT NULL,
  `score`        FLOAT DEFAULT 0,
  `max_score`    FLOAT DEFAULT 0,
  `time_taken`   INT DEFAULT 0 COMMENT 'seconds',
  `attempt_data` LONGTEXT DEFAULT NULL COMMENT 'JSON result details',
  `submitted_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`quiz_id`)     REFERENCES `quizzes`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`student_id`)  REFERENCES `users`(`id`)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `grades` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `quiz_id`    INT UNSIGNED NOT NULL,
  `student_id` INT UNSIGNED NOT NULL,
  `grade`      FLOAT DEFAULT NULL COMMENT 'manually overridden grade',
  `note`       TEXT DEFAULT NULL,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_grade` (`quiz_id`,`student_id`),
  FOREIGN KEY (`quiz_id`)     REFERENCES `quizzes`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`student_id`)  REFERENCES `users`(`id`)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `certificates` (
  `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`       INT UNSIGNED NOT NULL,
  `filename`      VARCHAR(255) NOT NULL,
  `original_name` VARCHAR(255) NOT NULL,
  `uploaded_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SQL;

try {
    $pdo->exec($sql);
    echo "<h2 style='font-family:sans-serif;color:green;padding:40px'>✔ Database tables created successfully!</h2>";
    echo "<p style='font-family:sans-serif;padding:0 40px'><strong>Important:</strong> Delete this file (<code>install.php</code>) from your server immediately.</p>";
    echo "<p style='font-family:sans-serif;padding:10px 40px'>Now <a href='seed.php'>run seed.php</a> to create default accounts, or add users manually via phpMyAdmin.</p>";
} catch (PDOException $e) {
    echo "<h2 style='color:red;font-family:sans-serif;padding:40px'>✘ Error: " . htmlspecialchars($e->getMessage()) . "</h2>";
}
