const sliderTabs = document.querySelectorAll(".slider-tab");
const sliderIndicator = document.querySelector(".slider-indicator");
const sliderControls = document.querySelector(".slider-controls");

const updateIndicator = (tab, index) => {
    sliderIndicator.style.transform = `translateX(${tab.offsetLeft}px)`;
    sliderIndicator.style.width = `${tab.offsetWidth}px`;

    const scrollLeft =
        tab.offsetLeft -
        sliderControls.offsetWidth / 2 +
        tab.offsetWidth / 2;

    sliderControls.scrollTo({
        left: scrollLeft,
        behavior: "smooth"
    });
};

const swiper = new Swiper(".slider-container", {
    effect: "fade",
    speed: 1300,
    autoplay: {
        delay: 4000,
        disableOnInteraction: false
    },
    navigation: {
        prevEl: "#slide-prev",
        nextEl: "#slide-next"
    },
    on: {
        slideChange: () => {
            updateIndicator(sliderTabs[swiper.activeIndex], swiper.activeIndex);
        }
    }
});

sliderTabs.forEach((tab, index) => {
    tab.addEventListener("click", () => {
        swiper.slideTo(index);
        updateIndicator(tab, index);
    });
});

updateIndicator(sliderTabs[0], 0);

window.addEventListener("resize", () => {
    updateIndicator(sliderTabs[swiper.activeIndex], swiper.activeIndex);
});
