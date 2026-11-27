// Pide confirmación antes de eliminar una receta
// Se usa en receta.php (un formulario) y en mis_recetas.php (uno por receta)
// Espera a que el HTML esté cargado para poder encontrar los formularios
document.addEventListener('DOMContentLoaded', () => {
    // Todos los formularios de eliminar de la página
    const formsEliminar = document.querySelectorAll('.form-eliminar');

    formsEliminar.forEach((form) => {
        form.addEventListener('submit', (e) => {
            // Si el usuario pulsa Cancelar, preventDefault() evita que se envíe el formulario
            if (!confirm('¿Seguro que quieres eliminar esta receta? No se puede deshacer.')) {
                e.preventDefault();
            }
        });
    });
});