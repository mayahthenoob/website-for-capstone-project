<?php
function h($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function jsonError($msg, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $msg]);
    exit;
}

function jsonSuccess($data = []) {
    echo json_encode(array_merge(['success' => true], $data));
    exit;
}

function formatDate($dateStr) {
    if (!$dateStr) return '';
    $d = new DateTime($dateStr);
    return $d->format('D, d M Y, g:i A');
}

function gradeColor($pct) {
    if ($pct >= 90) return '#27ae60';
    if ($pct >= 75) return '#2ecc71';
    if ($pct >= 60) return '#f39c12';
    if ($pct >= 50) return '#e67e22';
    return '#e74c3c';
}

function letterGrade($pct) {
    if ($pct >= 90) return 'A+';
    if ($pct >= 85) return 'A';
    if ($pct >= 80) return 'A-';
    if ($pct >= 75) return 'B+';
    if ($pct >= 70) return 'B';
    if ($pct >= 65) return 'B-';
    if ($pct >= 60) return 'C+';
    if ($pct >= 55) return 'C';
    if ($pct >= 50) return 'C-';
    return 'F';
}

function quizTypeIcon($type) {
    switch ($type) {
        case 'wordsearch':     return '<i class="bx bx-search-alt-2"></i>';
        case 'fillinblanks':   return '<i class="bx bx-color-fill"></i>';
        case 'hangman':        return '<i class="bx bx-male"></i>';
        case 'spellingbee':    return '<i class="bx bxs-invader"></i>';
        default:               return '<i class="bx bx-help-circle"></i>';
    }
}

function quizTypeLabel($type) {
    switch ($type) {
        case 'wordsearch':   return 'Word Search';
        case 'fillinblanks': return 'Fill in the Blanks';
        case 'hangman':      return 'Hangman';
        case 'spellingbee':  return 'Spelling Bee';
        default:             return $type;
    }
}

function classColors() {
    return ['#4318FF','#d81b60','#00897b','#e65100','#1565c0','#6a1b9a','#2e7d32','#c62828'];
}

function randomClassColor() {
    $colors = classColors();
    return $colors[array_rand($colors)];
}
