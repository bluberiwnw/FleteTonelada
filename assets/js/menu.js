const menuToggle = document.getElementById('menu-toggle');
const menuPrincipal = document.getElementById('menu-principal');

if (menuToggle && menuPrincipal) {
    menuToggle.addEventListener('click', () => {
        const abierto = menuPrincipal.classList.toggle('menu-open');
        menuToggle.setAttribute('aria-expanded', abierto ? 'true' : 'false');
        menuToggle.setAttribute('aria-label', abierto ? 'Cerrar menú' : 'Abrir menú');
    });

    menuPrincipal.querySelectorAll('a').forEach((enlace) => {
        enlace.addEventListener('click', () => {
            menuPrincipal.classList.remove('menu-open');
            menuToggle.setAttribute('aria-expanded', 'false');
        });
    });
}
