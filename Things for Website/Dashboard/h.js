// Initialize AOS animations
AOS.init({
    offset: 0
});

// Sticky header
window.addEventListener("scroll", () => {
    const header = document.querySelector("header");
    header.classList.toggle("sticky", window.scrollY > 0);
});

// Mobile menu toggle
const menuBtn = document.querySelector(".menu-btn");
const navigation = document.querySelector(".navigation");

if (menuBtn && navigation) {
    menuBtn.addEventListener("click", () => {
        menuBtn.classList.toggle("active");
        navigation.classList.toggle("active");
    });
}

// Close menu when clicking a link
const navigationItems = document.querySelectorAll(".navigation a");

navigationItems.forEach(item => {
    item.addEventListener("click", () => {
        menuBtn.classList.remove("active");
        navigation.classList.remove("active");
    });
});

// Scroll-to-top button (safe check)
const scrollBtn = document.querySelector(".scrollToTop-btn");

if (scrollBtn) {
    window.addEventListener("scroll", () => {
        scrollBtn.classList.toggle("active", window.scrollY > 500);
    });
}
