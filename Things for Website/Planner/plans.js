document.addEventListener('DOMContentLoaded', function () {
  const monthYearEl = document.getElementById('month-year');
  const daysEl = document.getElementById('days');
  const prevMonthBtn = document.getElementById('prev-month');
  const nextMonthBtn = document.getElementById('next-month');
  const todayBtn = document.getElementById('today-btn');
  const eventDateEl = document.getElementById('event-date');
  const eventListEl = document.getElementById('event-list');

  let currentDate = new Date();
  let selectedDate = null;

  /* ======================
     EVENTS STORAGE
  ====================== */
  const events = {};

  function addEvent(dateStr, time, text) {
    if (!events[dateStr]) events[dateStr] = [];
    events[dateStr].push({ time, text });
    renderCalendar();
    showEvents(dateStr);
  }

  /* ======================
     EDIT / DELETE
  ====================== */
  window.deleteEvent = function (dateStr, index) {
    if (!confirm("Delete this event?")) return;

    events[dateStr].splice(index, 1);
    if (events[dateStr].length === 0) delete events[dateStr];

    renderCalendar();
    showEvents(dateStr);
  };

  window.editEvent = function (dateStr, index) {
    const event = events[dateStr][index];

    const newText = prompt("Edit event title:", event.text);
    if (!newText) return;

    const newTime = prompt("Edit event time:", event.time) || event.time;

    event.text = newText;
    event.time = newTime;

    renderCalendar();
    showEvents(dateStr);
  };

  /* ======================
     RENDER CALENDAR
  ====================== */
  function renderCalendar() {
    const firstDay = new Date(currentDate.getFullYear(), currentDate.getMonth(), 1);
    const lastDay = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 0);
    const prevLastDay = new Date(currentDate.getFullYear(), currentDate.getMonth(), 0);

    const firstDayIndex = firstDay.getDay();
    const lastDayIndex = lastDay.getDay();
    const nextDays = 7 - lastDayIndex - 1;

    const months = [
      "January","February","March","April","May","June",
      "July","August","September","October","November","December"
    ];

    monthYearEl.textContent = `${months[currentDate.getMonth()]} ${currentDate.getFullYear()}`;
    daysEl.innerHTML = "";

    for (let x = firstDayIndex; x > 0; x--) {
      daysEl.innerHTML += `<div class="day other-month">${prevLastDay.getDate() - x + 1}</div>`;
    }

    for (let i = 1; i <= lastDay.getDate(); i++) {
      const dateKey = `${currentDate.getFullYear()}-${currentDate.getMonth() + 1}-${i}`;
      let classes = "day";

      const today = new Date();
      if (
        i === today.getDate() &&
        currentDate.getMonth() === today.getMonth() &&
        currentDate.getFullYear() === today.getFullYear()
      ) classes += " today";

      if (
        selectedDate &&
        i === selectedDate.getDate() &&
        currentDate.getMonth() === selectedDate.getMonth() &&
        currentDate.getFullYear() === selectedDate.getFullYear()
      ) classes += " selected";

      if (events[dateKey]) classes += " has-events";

      daysEl.innerHTML += `<div class="${classes}" data-date="${dateKey}">${i}</div>`;
    }

    for (let j = 1; j <= nextDays; j++) {
      daysEl.innerHTML += `<div class="day other-month">${j}</div>`;
    }

    attachDayClicks();
  }

  function attachDayClicks() {
    document.querySelectorAll('.day:not(.other-month)').forEach(day => {
      day.onclick = () => {
        const dateStr = day.dataset.date;
        const [y, m, d] = dateStr.split('-').map(Number);
        selectedDate = new Date(y, m - 1, d);

        const title = prompt("Event title:");
        if (title) {
          const time = prompt("Event time:", "All day") || "All day";
          addEvent(dateStr, time, title);
        }

        renderCalendar();
        showEvents(dateStr);
      };
    });
  }

  /* ======================
     SHOW EVENTS
  ====================== */
  function showEvents(dateStr) {
    const [y, m, d] = dateStr.split('-').map(Number);
    const dateObj = new Date(y, m - 1, d);

    const dayNames = ["Sunday","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"];
    const months = [
      "January","February","March","April","May","June",
      "July","August","September","October","November","December"
    ];

    eventDateEl.textContent =
      `${dayNames[dateObj.getDay()]}, ${months[m - 1]} ${d}, ${y}`;

    eventListEl.innerHTML = "";

    if (!events[dateStr]) {
      eventListEl.innerHTML = `<div class="no-events">No events scheduled</div>`;
      return;
    }

    events[dateStr].forEach((ev, index) => {
      eventListEl.innerHTML += `
        <div class="event-item">
          <div class="event-color"></div>

          <div class="event-content">
            <div class="event-time">${ev.time}</div>
            <div class="event-text">${ev.text}</div>
          </div>

          <div class="event-actions">
            <i class="fas fa-pen edit-btn" onclick="editEvent('${dateStr}', ${index})"></i>
            <i class="fas fa-trash delete-btn" onclick="deleteEvent('${dateStr}', ${index})"></i>
          </div>
        </div>
      `;
    });
  }

  prevMonthBtn.onclick = () => {
    currentDate.setMonth(currentDate.getMonth() - 1);
    selectedDate = null;
    renderCalendar();
  };

  nextMonthBtn.onclick = () => {
    currentDate.setMonth(currentDate.getMonth() + 1);
    selectedDate = null;
    renderCalendar();
  };

  todayBtn.onclick = () => {
    currentDate = new Date();
    selectedDate = new Date();
    renderCalendar();
    showEvents(`${currentDate.getFullYear()}-${currentDate.getMonth() + 1}-${currentDate.getDate()}`);
  };

  renderCalendar();
});
