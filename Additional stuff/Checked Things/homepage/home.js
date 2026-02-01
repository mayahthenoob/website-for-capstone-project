const slides = document.querySelectorAll(".slider-item");
const tabs = document.querySelectorAll(".slider-tab");
const indicator = document.querySelector(".slider-indicator");
const sliderControls = document.querySelector(".slider-controls");
const prevBtn = document.getElementById("slide-prev");
const nextBtn = document.getElementById("slide-next");

let currentIndex = 0;
let slideInterval = setInterval(nextSlide, 4000);

function showSlide(index) {
    slides.forEach((slide, i) => {
        slide.classList.toggle("active", i === index);
    });
    tabs.forEach((tab, i) => {
        tab.classList.toggle("active", i === index);
    });

    // Update indicator
    const tab = tabs[index];
    indicator.style.width = tab.offsetWidth + "px";
    indicator.style.transform = `translateX(${tab.offsetLeft}px)`;

    // Center the tab
    const scrollLeft = tab.offsetLeft - sliderControls.offsetWidth / 2 + tab.offsetWidth / 2;
    sliderControls.scrollTo({ left: scrollLeft, behavior: "smooth" });
}

function nextSlide() {
    currentIndex = (currentIndex + 1) % slides.length;
    showSlide(currentIndex);
}

function prevSlide() {
    currentIndex = (currentIndex - 1 + slides.length) % slides.length;
    showSlide(currentIndex);
}

// Tab click
tabs.forEach((tab, index) => {
    tab.addEventListener("click", () => {
        currentIndex = index;
        showSlide(index);
        resetInterval();
    });
});

// Navigation buttons
nextBtn.addEventListener("click", () => { nextSlide(); resetInterval(); });
prevBtn.addEventListener("click", () => { prevSlide(); resetInterval(); });

// Auto slide reset
function resetInterval() {
    clearInterval(slideInterval);
    slideInterval = setInterval(nextSlide, 4000);
}

// Initial setup
showSlide(currentIndex);

window.addEventListener("resize", () => {
    showSlide(currentIndex);
});
