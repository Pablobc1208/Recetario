// Patrones (expresiones regulares) de cada campo. Se usan con .test(texto), que devuelve true o false.
const patronTitulo      = /^.{1,150}$/;              // entre 1 y 150 caracteres
const patronDescripcion = /^.{1,255}$/;              // entre 1 y 255 caracteres
const patronTiempo      = /^[1-9][0-9]*$/;           // entero mayor que 0: empieza por 1-9 y sigue con dígitos
const patronIngrediente = /^.{1,100}$/;              // entre 1 y 100 caracteres
const patronCantidad    = /^[0-9]{1,6}(\.[0-9]{1,2})?$/; // número con hasta 2 decimales, separados por punto

const formulario = document.querySelector('#form-receta');

// Los campos de cantidad solo admiten valores numéricos. Esta protección evita
// escribir letras o separadores no válidos antes de que se envíe el formulario.
const cantidadesNumericas = formulario.querySelectorAll('input[name="ing_cantidad[]"]');
cantidadesNumericas.forEach((cantidad) => {
    cantidad.addEventListener('beforeinput', function (event) {
        if (event.data !== null && /[^0-9.]/.test(event.data)) {
            event.preventDefault();
        }
    });

    cantidad.addEventListener('input', function () {
        cantidad.value = cantidad.value.replace(/[^0-9.]/g, '');
    });
});

// Crea un mensaje de error justo después del elemento indicado y lo marca como inválido.
function mostrarError(elemento, mensaje) {
    const error = document.createElement('span');
    error.classList.add('error-campo');
    error.textContent = mensaje;   // textContent: el mensaje se trata como texto, nunca como HTML
    elemento.classList.add('campo-invalido');
    elemento.insertAdjacentElement('afterend', error);
}

// Quita los errores de un envío anterior antes de volver a validar.
function limpiarErrores() {
    formulario.querySelectorAll('.error-campo').forEach(error => error.remove());
    formulario.querySelectorAll('.campo-invalido').forEach(campo => campo.classList.remove('campo-invalido'));
}

formulario.addEventListener('submit', function (event) {
    limpiarErrores();
    let hayErrores = false;

    // Datos de la receta
    const titulo = document.querySelector('#titulo');
    if (!patronTitulo.test(titulo.value.trim())) {
        mostrarError(titulo, 'Escribe el nombre de la receta (máximo 150 caracteres).');
        hayErrores = true;
    }

    const descripcion = document.querySelector('#descripcion');
    if (!patronDescripcion.test(descripcion.value.trim())) {
        mostrarError(descripcion, 'Escribe una descripción (máximo 255 caracteres).');
        hayErrores = true;
    }

    const tiempo = document.querySelector('#tiempo_minutos');
    if (!patronTiempo.test(tiempo.value.trim())) {
        mostrarError(tiempo, 'Indica los minutos con un número entero mayor que 0.');
        hayErrores = true;
    }

    const categoria = document.querySelector('#id_categoria');
    if (categoria.value === '') {
        mostrarError(categoria, 'Elige una categoría.');
        hayErrores = true;
    }

    // Ingredientes: se recorren las filas a la vez (nombre, cantidad y unidad de la misma fila)
    const nombres    = formulario.querySelectorAll('input[name="ing_nombre[]"]');
    const cantidades = formulario.querySelectorAll('input[name="ing_cantidad[]"]');
    const unidades   = formulario.querySelectorAll('select[name="ing_unidad[]"]');
    let filasRellenas = 0;

    nombres.forEach((nombre, i) => {
        const textoNombre   = nombre.value.trim();
        const textoCantidad = cantidades[i].value.trim();

        // Una fila sin nombre ni cantidad está vacía y no se valida (igual que en el servidor).
        if (textoNombre === '' && textoCantidad === '') {
            return;
        }
        filasRellenas++;

        if (!patronIngrediente.test(textoNombre)) {
            mostrarError(nombre, 'Escribe el ingrediente (máximo 100 caracteres).');
            hayErrores = true;
        }
        if (!patronCantidad.test(textoCantidad) || Number(textoCantidad) <= 0) {
            mostrarError(cantidades[i], 'Cantidad mayor que 0, con punto para los decimales (ej.: 0.5).');
            hayErrores = true;
        }
        if (unidades[i].value === '') {
            mostrarError(unidades[i], 'Elige una unidad.');
            hayErrores = true;
        }
    });

    if (filasRellenas === 0) {
        mostrarError(document.querySelector('#legend-ingredientes'), 'Añade al menos un ingrediente.');
        hayErrores = true;
    }

    // Preparación
    const instrucciones = document.querySelector('#instrucciones');
    if (instrucciones.value.trim() === '') {
        mostrarError(instrucciones, 'Escribe los pasos de preparación.');
        hayErrores = true;
    }

    // Si hay algún error, se cancela el envío y se lleva al usuario al primer campo con error.
    if (hayErrores) {
        event.preventDefault();
        const primero = formulario.querySelector('.campo-invalido');
        if (primero) {
            primero.focus();
        }
    }
});