function toggleMenu() {
    document.getElementById("menu").classList.toggle("open");
}

function toggleSubmenu(id) {
    const submenu = document.getElementById(id);
    const icon = document.getElementById(id + '-icon');
    
    if (submenu) {
        submenu.classList.toggle('expanded');
        if (icon) {
            icon.textContent = submenu.classList.contains('expanded') ? '▼' : '▶';
        }
    }
}

// Close menu when clicking outside
document.addEventListener('click', function(event) {
    const menu = document.getElementById('menu');
    const hamburger = document.querySelector('.hamburger');
    
    if (menu && menu.classList.contains('open')) {
        if (!menu.contains(event.target) && !hamburger.contains(event.target)) {
            menu.classList.remove('open');
        }
    }
});
