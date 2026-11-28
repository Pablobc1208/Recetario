const RADIO = 83;
const CIRCUNFERENCIA = 2 * Math.PI * RADIO;

/**
 * Escribe unos segundos como minutos:segundos (125 → "2:05").
 * @param {number} segundos
 * @returns {string}
 */
const formatearTiempo = (segundos) => {
    const minutos = Math.floor(segundos / 60);
    const resto = segundos % 60;
    return minutos + ':' + (resto < 10 ? '0' + resto : resto);
};

function iniciarTemporizador() {
    const contenedor = document.querySelector('.temporizador');
    if (!contenedor) {
        return;
    }

    const boton = document.querySelector('#temporizador-boton');
    const texto = document.querySelector('#temporizador-texto');
    const reloj = document.querySelector('#temporizador-tiempo');
    const progreso = document.querySelector('#temporizador-progreso');
    const reiniciar = document.querySelector('#temporizador-reiniciar');

    const total = Number(document.querySelector('#temporizador-minutos').value) * 60;
    let restante = total;
    let intervalo = null; // id que devuelve setInterval (null = parado)

    //el trazo del anillo mide lo mismo que la circunferencia
    progreso.style.strokeDasharray = CIRCUNFERENCIA;

    //actualiza el texto del reloj y lo que se ve del anillo
    const pintar = () => {
        reloj.textContent = formatearTiempo(restante);
        //0 = anillo lleno                CIRCUNFERENCIA = anillo vacio
        progreso.style.strokeDashoffset = CIRCUNFERENCIA * (1 - restante / total);
    };

    const parar = () => {
        clearInterval(intervalo);
        intervalo = null;
        contenedor.classList.remove('en-marcha');
    };

    const terminar = () => {
        parar();
        contenedor.classList.add('terminado'); //el CSS hace vibrar el dibujo
        texto.textContent = '¡Listo!';
    };

    //se ejecuta cada segundo mientras el temporizador esta en marcha
    const avanzar = () => {
        restante--;
        pintar();
        if (restante === 0) {
            terminar();
        }
    };

    //empezar, pausar o seguir cuando se hace clck
    boton.addEventListener('click', () => {
        if (restante === 0) {
            return; // ya ha terminado: hay que pulsar «Reiniciar»
        }

        if (intervalo === null) {
            intervalo = setInterval(avanzar, 1000);
            contenedor.classList.add('en-marcha'); //el CSS cambia play por pausa
            texto.textContent = 'Pausar';
        } else {
            parar();
            texto.textContent = 'Seguir';
        }
    });

    reiniciar.addEventListener('click', () => {
        parar();
        restante = total;
        contenedor.classList.remove('terminado');
        texto.textContent = 'Empezar';
        pintar();
    });

    pintar();
}

iniciarTemporizador();
