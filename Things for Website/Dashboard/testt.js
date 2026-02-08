const slides = document.querySelectorAll(".slide");
const slidesContainer = document.querySelector(".slides");
const nextBtn = document.querySelector(".next");
const prevBtn = document.querySelector(".prev");
const pagination = document.querySelector(".pagination");

let currentIndex = 0;
let interval;

function init() {
  createPagination();
  goToSlide(0);
  autoPlay();
}

function createPagination() {
  slides.forEach((_, index) => {
    const dot = document.createElement("div");
    dot.classList.add("dot");
    dot.addEventListener("click", () => goToSlide(index));
    pagination.appendChild(dot);
  });
}

function updatePagination() {
  document.querySelectorAll(".dot").forEach((dot, idx) => {
    dot.classList.toggle("active", idx === currentIndex);
  });
}

function goToSlide(index) {
  currentIndex = index;
  slidesContainer.style.transform = `translateX(-${index * 100}%)`;
  updatePagination();
  resetAutoPlay();
}

nextBtn.addEventListener("click", () => {
  goToSlide((currentIndex + 1) % slides.length);
});

prevBtn.addEventListener("click", () => {
  goToSlide((currentIndex - 1 + slides.length) % slides.length);
});

function autoPlay() {
  interval = setInterval(() => {
    goToSlide((currentIndex + 1) % slides.length);
  }, 4000);
}

function resetAutoPlay() {
  clearInterval(interval);
  autoPlay();
}

init();
