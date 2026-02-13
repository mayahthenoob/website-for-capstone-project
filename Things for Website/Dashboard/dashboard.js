const navItems = document.querySelectorAll('.nav-item');
const sidebar = document.querySelector('.sidebar');
const toggleBtn = document.getElementById('menu-toggle');

navItems.forEach(item => {
    item.addEventListener('click', () => {
        navItems.forEach(nav => nav.classList.remove('active'));
        item.classList.add('active');
    });
});

toggleBtn.addEventListener('click', () => {
    sidebar.classList.toggle('collapsed');
    sidebar.classList.toggle('expanded');
});
