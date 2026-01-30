const numberHours = document.querySelector('.number-hours');
const barSeconds = document.querySelector('.bar-seconds');

const numberElement = [];
const barElement = [];

/* CREATE HOUR NUMBERS */
for (let i = 1; i <= 12; i++) {
  numberElement.push(
    `<span style="--index:${i};"><p>${i}</p></span>`
  );
}
numberHours.insertAdjacentHTML("afterbegin", numberElement.join(""));

/* CREATE SECOND BARS */
for (let i = 1; i <= 60; i++) {
  barElement.push(
    `<span style="--index:${i};"><p></p></span>`
  );
}
barSeconds.insertAdjacentHTML("afterbegin", barElement.join(""));

/* SELECT HANDS */
const handHours = document.querySelector('.hand.hours');
const handMinutes = document.querySelector('.hand.minutes');
const handSeconds = document.querySelector('.hand.seconds');

/* UPDATE TIME */
function getCurrentTime() {
  const date = new Date();

  const currentHours = date.getHours();
  const currentMinutes = date.getMinutes();
  const currentSeconds = date.getSeconds();

  handHours.style.transform =
    `rotate(${currentHours * 30 + currentMinutes / 2}deg)`;

  handMinutes.style.transform =
    `rotate(${currentMinutes * 6}deg)`;

  handSeconds.style.transform =
    `rotate(${currentSeconds * 6}deg)`;
}

getCurrentTime();
setInterval(getCurrentTime, 1000);
