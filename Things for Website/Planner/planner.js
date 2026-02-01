const daysEl = document.getElementById("days");
const monthYearEl = document.getElementById("month-year");
const eventListEl = document.getElementById("event-list");
const modal = document.getElementById("event-modal");

const titleInput = document.getElementById("event-title");
const dateInput = document.getElementById("event-date-input");
const startInput = document.getElementById("event-start-time");
const endInput = document.getElementById("event-end-time");
const colorPicker = document.getElementById("event-color-picker");
const colorText = document.getElementById("event-color-text");

let currentDate = new Date();
let selectedDate = null;
const events = {};

colorPicker.oninput = () => colorText.value = colorPicker.value;
colorText.oninput = () => colorPicker.value = colorText.value;

function renderCalendar(){
  const year=currentDate.getFullYear(),month=currentDate.getMonth();
  const lastDay=new Date(year,month+1,0).getDate();
  monthYearEl.textContent=currentDate.toLocaleString("default",{month:"long",year:"numeric"});
  daysEl.innerHTML="";
  for(let i=1;i<=lastDay;i++){
    const key=`${year}-${month+1}-${i}`;
    const div=document.createElement("div");
    div.className="day"+(events[key]?" has-events":"");
    div.textContent=i;
    div.onclick=()=>openModal(key);
    daysEl.appendChild(div);
  }
}

function openModal(dateStr){
  modal.classList.add("active");
  dateInput.value=dateStr;
  selectedDate=dateStr;
}

document.getElementById("cancel-event").onclick=()=>modal.classList.remove("active");

document.getElementById("save-event").onclick=()=>{
  if(!titleInput.value) return;
  if(!events[selectedDate]) events[selectedDate]=[];
  events[selectedDate].push({
    text:titleInput.value,
    time:`${startInput.value}-${endInput.value}`,
    color:colorText.value
  });
  modal.classList.remove("active");
  renderCalendar();
  showEvents(selectedDate);
};

function showEvents(dateStr){
  eventListEl.innerHTML="";
  (events[dateStr]||[]).forEach((e,i)=>{
    eventListEl.innerHTML+=`
      <div class="event-item">
        <div class="event-color" style="background:${e.color}"></div>
        <div>${e.text}</div>
        <div class="event-actions">
          <i class="fas fa-trash" onclick="events['${dateStr}'].splice(${i},1);renderCalendar();showEvents('${dateStr}')"></i>
        </div>
      </div>`;
  });
}

renderCalendar();
