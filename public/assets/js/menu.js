function toggleMenu() {
    document.getElementById("menu").classList.toggle("open");
}

function toggleSubmenu(id) {
    const submenu = document.getElementById(id);
    const icon = document.getElementById(id + '-icon');
    submenu.classList.toggle('expanded');
    if (icon) {
        icon.textContent = submenu.classList.contains('expanded') ? '▼' : '▶';
    }
}
