// Nombre de la cookie y vista por defecto.
const NOMBRE_COOKIE = 'vista_listado';
const VISTA_POR_DEFECTO = 'cuadricula';

/**
 * Crea o sobrescribe una cookie que dura hasta el 31 de diciembre del año que viene.
 * @param {string} nombre
 * @param {string} valor
 */
function guardarCookie(nombre, valor) {
    const anioQueViene = new Date().getFullYear() + 1;
    const caducidad = '31 Dec ' + anioQueViene + ' 23:59:59 UTC';

    //con path=/ la cookie vale para toda la aplicacion
    document.cookie = nombre + '=' + encodeURIComponent(valor) +
        '; expires=' + caducidad + '; path=/';
}

/**
 * Busca una cookie por su nombre (algoritmo de los apuntes DWC 3.3).
 * @param {string} nombre
 * @returns {string|null} El valor de la cookie o null si no existe.
 */
function leerCookie(nombre) {
    //document.cookie devuelve todas las cookies en una cadena
    const pares = document.cookie.split('; ');
    for (let par of pares) {
        const [clave, valor] = par.split('=');
        if (clave === nombre) {
            return decodeURIComponent(valor);
        }
    }
    return null;
}

/**
 * Muestra el listado con la vista indicada y marca el botón que está activo.
 * @param {string} vista "lista" o "cuadricula".
 */
function aplicarVista(vista) {
    const listado = document.getElementById('listado-recetas');
    const botonCuadricula = document.getElementById('vista-cuadricula');
    const botonLista = document.getElementById('vista-lista');

    if (vista === 'lista') {
        listado.classList.add('vista-lista');
        botonLista.classList.add('active');
        botonCuadricula.classList.remove('active');
    } else {
        listado.classList.remove('vista-lista');
        botonCuadricula.classList.add('active');
        botonLista.classList.remove('active');
    }
}

/**
 * Cambia la vista al pulsar un botón y guarda la elección en la cookie.
 * @param {string} vista
 */
function cambiarVista(vista) {
    aplicarVista(vista);

    //window.navigator.cookieEnabled es false si el navegador bloquea las cookies
    if (window.navigator.cookieEnabled) {
        guardarCookie(NOMBRE_COOKIE, vista);
    }
}

const listadoRecetas = document.getElementById('listado-recetas');

//si no hay recetas no existe el listado y no se hace nada
if (listadoRecetas) {
    //recupera la preferencia guardada
    let vistaGuardada = leerCookie(NOMBRE_COOKIE);

    //solo se acepta los valores esperados cualquier otro se ignora
    if (vistaGuardada !== 'lista' && vistaGuardada !== 'cuadricula') {
        vistaGuardada = VISTA_POR_DEFECTO;
    }

    //aplicar la vista y mostrar los botones
    aplicarVista(vistaGuardada);
    document.getElementById('selector-vista').classList.remove('d-none');

    //reacciona a los clics de los dos botones
    document.getElementById('vista-cuadricula').addEventListener('click', () => {
        cambiarVista('cuadricula');
    });
    document.getElementById('vista-lista').addEventListener('click', () => {
        cambiarVista('lista');
    });
}
