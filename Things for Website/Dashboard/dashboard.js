const navItems = document.querySelectorAll('.nav-item');
const sidebar = document.querySelector('.sidebar');
const toggleBtn = document.getElementById('menu-toggle');

// Handle Active State switching
navItems.forEach(item => {
    item.addEventListener('click', () => {
        navItems.forEach(nav => nav.classList.remove('active'));
        item.classList.add('active');
    });
});

// Handle Sidebar Toggle
toggleBtn.addEventListener('click', () => {
    sidebar.classList.toggle('collapsed');
    
    // Switch between expanded and collapsed class if necessary
    if (sidebar.classList.contains('collapsed')) {
        sidebar.classList.remove('expanded');
    } else {
        sidebar.classList.add('expanded');
    }
});