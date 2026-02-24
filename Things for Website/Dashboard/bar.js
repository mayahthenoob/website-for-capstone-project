const body = document.querySelector('body');
const sidebar = document.querySelector('.sidebar');
const toggle = document.querySelector(".toggle");
const btn = document.querySelector(".btn");
const icon = document.querySelector(".btn__icon");

// Sidebar toggle
toggle.addEventListener("click", () => {
    sidebar.classList.toggle("close");
});

// Dark mode toggle
btn.addEventListener("click", () => {
    body.classList.toggle("dark");

    if (body.classList.contains("dark")) {
        icon.classList.remove("bx-sun");
        icon.classList.add("bx-moon");
    } else {
        icon.classList.remove("bx-moon");
        icon.classList.add("bx-sun");
    }
});