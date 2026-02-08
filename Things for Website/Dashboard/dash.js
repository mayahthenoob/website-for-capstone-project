const navItems = document.querySelectorAll('.nav-item');
const sidebar = document.querySelector('.sidebar');
const toggleBtn = document.getElementById('menu-toggle');

// Active state (visual only)
navItems.forEach(item => {
    item.addEventListener('click', () => {
        navItems.forEach(nav => nav.classList.remove('active'));
        item.classList.add('active');
    });
});

// Sidebar toggle
toggleBtn.addEventListener('click', () => {
    sidebar.classList.toggle('collapsed');
    sidebar.classList.toggle('expanded');
});
