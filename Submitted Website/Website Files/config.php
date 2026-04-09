<?php
/**
 * Site-wide config — include this at the top of every page.
 * SITE_ROOT is the base URL path (e.g. '/' or '/quiz-carnival/').
 * Adjust if installed in a sub-folder on InfinityFree.
 */
define('SITE_ROOT', '/');          // Change to e.g. '/quiz-carnival/' if in a subfolder
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('UPLOAD_URL', SITE_ROOT . 'uploads/');
define('MAX_UPLOAD_MB', 5);
