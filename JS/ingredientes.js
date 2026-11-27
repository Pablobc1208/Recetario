document.addEventListener('DOMContentLoaded', () => {
    const lista = document.querySelector('#lista-ingredientes');
    const botonAnadir = document.querySelector('#btn-anadir');
 
    // Si la página no tiene la lista de ingredientes, no se hace nada
    if (!lista || !botonAnadir) {
        return;
    }
    // El máximo de filas viene en el atributo data-maximo como texto: Number lo convierte en número
    const maximo = Number(lista.getAttribute('data-maximo'));
 
    // Contador para dar un id distinto a cada campo nuevo 
    let contador = lista.querySelectorAll('.fila-ingrediente').length;
    const opcionesUnidad = lista.querySelector('select[name="ing_unidad[]"]').options;
 
    // Crea una etiqueta con su texto y el id del campo al que pertenece.
    function crearLabel(texto, idCampo) {
        const label = document.createElement('label');
        label.htmlFor = idCampo;
        label.textContent = texto;
        return label;
    }
 
    // Crea una fila completa: nombre, cantidad, unidad y botón Quitar
    function crearFila() {
        const fila = document.createElement('p');
        fila.classList.add('fila-ingrediente');
 
        // Nombre del ingrediente
        const idNombre = 'ing_nombre_' + contador;
        const labelNombre = crearLabel('Ingrediente', idNombre);
        labelNombre.classList.add('etiqueta-ingrediente');
        const inputNombre = document.createElement('input');
        inputNombre.type = 'text';
        inputNombre.id = idNombre;
        inputNombre.name = 'ing_nombre[]';
        inputNombre.maxLength = 100;
        inputNombre.placeholder = 'Ej.: Harina';
 
        // Cantidad
        const idCantidad = 'ing_cantidad_' + contador;
        const labelCantidad = crearLabel('Cantidad', idCantidad);
        const inputCantidad = document.createElement('input');
        inputCantidad.type = 'number';
        inputCantidad.id = idCantidad;
        inputCantidad.name = 'ing_cantidad[]';
        inputCantidad.inputMode = 'decimal';
        inputCantidad.min = '0.01';
        inputCantidad.max = '999999.99';
        inputCantidad.step = '0.01';
        inputCantidad.placeholder = 'Ej.: 200';
 
        // Unidad: se recorren las opciones del primer selector con un bucle for
        const idUnidad = 'ing_unidad_' + contador;
        const labelUnidad = crearLabel('Unidad', idUnidad);
        const selectUnidad = document.createElement('select');
        selectUnidad.id = idUnidad;
        selectUnidad.name = 'ing_unidad[]';
        for (let i = 0; i < opcionesUnidad.length; i++) {
            const opcion = document.createElement('option');
            opcion.value = opcionesUnidad[i].value;
            opcion.textContent = opcionesUnidad[i].textContent;
            selectUnidad.appendChild(opcion);
        }
 
        // Botón para quitar esta fila. type="button" para que no envíe el formulario
        const botonQuitar = document.createElement('button');
        botonQuitar.type = 'button';
        botonQuitar.classList.add('btn-quitar');
        botonQuitar.textContent = 'Quitar';
 
        // Se meten todos los elementos dentro de la fila, en orden
        const elementos = [labelNombre, inputNombre, labelCantidad, inputCantidad, labelUnidad, selectUnidad, botonQuitar];
        elementos.forEach((elemento) => {
            fila.appendChild(elemento);
        });
 
        contador++;
        return fila;
    }
 
    // Vuelve a numerar las etiquetas y desactiva el botón de añadir cuando se llega al máximo
    function actualizarFilas() {
        const etiquetas = lista.querySelectorAll('.etiqueta-ingrediente');
        etiquetas.forEach((etiqueta, i) => {
            etiqueta.textContent = 'Ingrediente ' + (i + 1);
        });
        botonAnadir.disabled = etiquetas.length >= maximo;
    }
 
    // Botón Añadir ingrediente
    botonAnadir.addEventListener('click', () => {
        const totalFilas = lista.querySelectorAll('.fila-ingrediente').length;
        if (totalFilas >= maximo) {
            return;
        }
        const nuevaFila = crearFila();
        lista.appendChild(nuevaFila);
        actualizarFilas();
        nuevaFila.querySelector('input').focus(); // el cursor va directo al nombre
    });
    // Boton Quitar
    lista.addEventListener('click', (event) => {
        if (!event.target.classList.contains('btn-quitar')) {
            return;
        }
        const fila = event.target.parentNode; // el <p class="fila-ingrediente"> que contiene el botón
        const totalFilas = lista.querySelectorAll('.fila-ingrediente').length;
 
        if (totalFilas > 1) {
            fila.remove();
        } else {
            // Siempre queda al menos una fila: si es la última, se vacía en lugar de borrarla
            fila.querySelectorAll('input').forEach((campo) => {
                campo.value = '';
            });
            fila.querySelector('select').value = '';
        }
        actualizarFilas();
    });
 
    actualizarFilas();
});