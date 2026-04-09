<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Calendar — Quiz Carnival';
require_once __DIR__ . '/includes/header-sidebar.php';

$pdo = getDB();
$uid = $user['id'];

// Load quizzes with dates relevant to this user
if ($user['role'] === 'teacher') {
    $quizzes = $pdo->prepare("SELECT q.id, q.title, q.type, q.opens_at, q.closes_at, c.name as class_name, c.color FROM quizzes q JOIN classes c ON q.class_id=c.id WHERE c.teacher_id=? AND (q.opens_at IS NOT NULL OR q.closes_at IS NOT NULL)");
    $quizzes->execute([$uid]);
} else {
    $quizzes = $pdo->prepare("SELECT q.id, q.title, q.type, q.opens_at, q.closes_at, c.name as class_name, c.color FROM quizzes q JOIN classes c ON q.class_id=c.id JOIN enrollments e ON e.class_id=c.id WHERE e.student_id=? AND q.published=1 AND (q.opens_at IS NOT NULL OR q.closes_at IS NOT NULL)");
    $quizzes->execute([$uid]);
}
$quizzes = $quizzes->fetchAll();

// Build events JSON for JS
$events = [];
foreach ($quizzes as $q) {
    if ($q['opens_at']) {
        $events[] = ['date' => date('Y-m-d', strtotime($q['opens_at'])), 'title' => $q['title'], 'class' => $q['class_name'], 'type' => $q['type'], 'color' => $q['color'], 'when' => 'Opens'];
    }
    if ($q['closes_at']) {
        $events[] = ['date' => date('Y-m-d', strtotime($q['closes_at'])), 'title' => $q['title'], 'class' => $q['class_name'], 'type' => $q['type'], 'color' => $q['color'], 'when' => 'Closes'];
    }
}
$eventsJson = json_encode($events);
?>
<style>
.page-content { padding: 36px 40px; flex: 1; }
.page-hd { margin-bottom: 28px; }
.page-hd h1 { font-size: 24px; font-weight: 700; color: var(--text-main); }
.page-hd p  { font-size: 14px; color: var(--text-muted); margin-top: 4px; }
.cal-wrapper { display: flex; gap: 24px; flex-wrap: wrap; }
.cal-card { background: #fff; border-radius: 20px; box-shadow: var(--shadow); padding: 28px; flex: 1; min-width: 320px; max-width: 640px; }
.cal-controls { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.cal-nav { display: flex; align-items: center; gap: 12px; }
.cal-nav button { background: #f4f6f9; border: none; width: 36px; height: 36px; border-radius: 10px; font-size: 20px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: 0.15s; }
.cal-nav button:hover { background: #e6e9ef; }
.cal-month { font-size: 17px; font-weight: 700; color: var(--text-main); }
.today-btn { background: var(--primary); color: #fff; border: none; padding: 7px 16px; border-radius: 8px; font-size: 12px; font-weight: 700; cursor: pointer; font-family:'Inter',sans-serif; }
.today-btn:hover { background: #3310cc; }
.cal-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 4px; }
.cal-header { text-align: center; font-size: 11px; font-weight: 700; color: var(--text-muted); padding: 6px 0; }
.cal-day {
  min-height: 52px; background: #f8f9fc; border-radius: 10px; padding: 6px 4px 4px;
  cursor: pointer; transition: 0.15s; position: relative;
}
.cal-day:hover { background: #ede9ff; }
.cal-day.today { background: #ede9ff; }
.cal-day.selected { background: var(--primary); }
.cal-day.other-month { opacity: 0.3; }
.day-num { font-size: 12px; font-weight: 600; text-align: center; }
.cal-day.selected .day-num { color: #fff; }
.cal-day.today .day-num { color: var(--primary); }
.event-dot { width: 6px; height: 6px; border-radius: 50%; margin: 1px auto 0; }
.events-panel { background: #fff; border-radius: 20px; box-shadow: var(--shadow); padding: 28px; width: 300px; min-width: 260px; }
.events-panel h3 { font-size: 15px; font-weight: 700; color: var(--text-main); margin-bottom: 4px; }
.events-panel .ep-date { font-size: 13px; color: var(--text-muted); margin-bottom: 16px; }
.event-item { display: flex; gap: 10px; padding: 12px; border-radius: 12px; background: #f8f9fc; margin-bottom: 10px; border-left: 3px solid var(--primary); }
.event-item-info {}
.event-item-title { font-size: 13px; font-weight: 700; color: var(--text-main); }
.event-item-sub { font-size: 12px; color: var(--text-muted); margin-top: 2px; }
.no-events { text-align: center; padding: 30px 0; color: var(--text-muted); font-size: 13px; }
.no-events i { font-size: 36px; display: block; margin-bottom: 8px; }
</style>

<div class="page-content">
  <div class="page-hd">
    <h1>Calendar</h1>
    <p>View quiz open and close dates at a glance.</p>
  </div>

  <div class="cal-wrapper">
    <div class="cal-card">
      <div class="cal-controls">
        <div class="cal-nav">
          <button onclick="prevMonth()"><i class='bx bx-chevron-left'></i></button>
          <span class="cal-month" id="calMonth"></span>
          <button onclick="nextMonth()"><i class='bx bx-chevron-right'></i></button>
        </div>
        <button class="today-btn" onclick="goToday()">Today</button>
      </div>
      <div class="cal-grid" id="calGrid">
        <?php foreach (['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $d): ?>
          <div class="cal-header"><?= $d ?></div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="events-panel">
      <h3 id="eventsTitle">Events</h3>
      <div class="ep-date" id="eventsDate"></div>
      <div id="eventsList"><div class="no-events"><i class='bx bx-calendar'></i>Select a day to see quizzes.</div></div>
    </div>
  </div>
</div>

<script>
const EVENTS = <?= $eventsJson ?>;
const eventMap = {};
EVENTS.forEach(e => {
    if (!eventMap[e.date]) eventMap[e.date] = [];
    eventMap[e.date].push(e);
});

const DAYS   = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
const MONTHS = ['January','February','March','April','May','June','July','August','September','October','November','December'];

let today     = new Date();
let curYear   = today.getFullYear();
let curMonth  = today.getMonth();
let selected  = null;

function renderCalendar() {
    document.getElementById('calMonth').textContent = MONTHS[curMonth] + ' ' + curYear;
    const grid = document.getElementById('calGrid');
    // Remove day cells (keep headers)
    const headers = grid.querySelectorAll('.cal-header');
    grid.innerHTML = '';
    headers.forEach(h => grid.appendChild(h));

    const first = new Date(curYear, curMonth, 1).getDay();
    const days  = new Date(curYear, curMonth + 1, 0).getDate();
    const prev  = new Date(curYear, curMonth, 0).getDate();

    // Prev month filler
    for (let i = first - 1; i >= 0; i--) {
        const d = document.createElement('div');
        d.className = 'cal-day other-month';
        d.innerHTML = `<div class="day-num">${prev - i}</div>`;
        grid.appendChild(d);
    }

    for (let d = 1; d <= days; d++) {
        const dateStr = `${curYear}-${String(curMonth+1).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
        const isToday = (d === today.getDate() && curMonth === today.getMonth() && curYear === today.getFullYear());
        const isSel   = selected === dateStr;
        const cell    = document.createElement('div');
        cell.className = 'cal-day' + (isToday ? ' today' : '') + (isSel ? ' selected' : '');
        cell.dataset.date = dateStr;
        cell.innerHTML = `<div class="day-num">${d}</div>`;
        if (eventMap[dateStr]) {
            eventMap[dateStr].slice(0, 3).forEach(ev => {
                const dot = document.createElement('div');
                dot.className = 'event-dot';
                dot.style.background = ev.color || '#4318FF';
                cell.appendChild(dot);
            });
        }
        cell.onclick = () => selectDay(dateStr);
        grid.appendChild(cell);
    }
    // Next month filler
    const totalCells = first + days;
    const remaining  = 42 - totalCells;
    for (let i = 1; i <= remaining; i++) {
        const d = document.createElement('div');
        d.className = 'cal-day other-month';
        d.innerHTML = `<div class="day-num">${i}</div>`;
        grid.appendChild(d);
    }
}

function selectDay(dateStr) {
    selected = dateStr;
    renderCalendar();
    const [y, m, d] = dateStr.split('-').map(Number);
    const dt = new Date(y, m - 1, d);
    document.getElementById('eventsTitle').textContent = DAYS[dt.getDay()];
    document.getElementById('eventsDate').textContent  = `${d} ${MONTHS[m-1]} ${y}`;
    const evs = eventMap[dateStr] || [];
    const list = document.getElementById('eventsList');
    if (evs.length === 0) {
        list.innerHTML = '<div class="no-events"><i class="bx bx-calendar-x"></i>No quizzes on this day.</div>';
    } else {
        list.innerHTML = evs.map(e => `
            <div class="event-item" style="border-color:${e.color||'#4318FF'}">
                <div class="event-item-info">
                    <div class="event-item-title">${e.title}</div>
                    <div class="event-item-sub">${e.when} &middot; ${e.class}</div>
                </div>
            </div>
        `).join('');
    }
}

function prevMonth() { curMonth--; if (curMonth < 0) { curMonth = 11; curYear--; } renderCalendar(); }
function nextMonth() { curMonth++; if (curMonth > 11) { curMonth = 0; curYear++; } renderCalendar(); }
function goToday()   { curYear = today.getFullYear(); curMonth = today.getMonth(); renderCalendar(); selectDay(`${curYear}-${String(curMonth+1).padStart(2,'0')}-${String(today.getDate()).padStart(2,'0')}`); }

renderCalendar();
// Auto-select today
goToday();

function toggleDropdown() { document.getElementById("profileDropdown").classList.toggle("show"); }
window.addEventListener('click', (e) => { if (!e.target.closest('.profile-container')) document.getElementById("profileDropdown")?.classList.remove("show"); });
</script>
</div></body></html>
