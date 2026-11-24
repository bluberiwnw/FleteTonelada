// Mejora de experiencia en formularios; PHP conserva la validación definitiva.
document.querySelectorAll('form[data-validar-contrasena]').forEach((formulario) => {
    formulario.addEventListener('submit', (evento) => {
        const contrasena = formulario.querySelector('[name="contrasena"]');
        const confirmar = formulario.querySelector('[name="confirmar_contrasena"]');
        if (contrasena && confirmar && contrasena.value !== confirmar.value) {
            confirmar.setCustomValidity('Las contraseñas no coinciden.');
            confirmar.reportValidity();
            evento.preventDefault();
        } else if (confirmar) {
            confirmar.setCustomValidity('');
        }
    });
});
