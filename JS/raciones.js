/**
 *
 * Calculadora de raciones para la página de una receta (PHP/receta.php).
 * Al cambiar el número de personas se recalculan las cantidades de los ingredientes.
 *
 * Patrón usado: clase con constructor. El objeto CalculadoraRaciones guarda los datos
 * (array de cantidades y raciones) y los métodos que trabajan con ellos.
 */

// Las recetas de la base de datos están pensadas para 4 personas.
const RACIONES_BASE = 4;

/**
 * Redondea un número a dos decimales y lo escribe con coma (1.5 → "1,5").
 * @param {number} numero
 * @returns {string}
 */
const formatear = (numero) => String(Math.round(numero * 100) / 100).replace('.', ',');

class CalculadoraRaciones {
    /**
     * @param {number[]} cantidades Cantidades originales de los ingredientes.
     */
    constructor(cantidades) {
        this.cantidades = cantidades;
        this.raciones = RACIONES_BASE;
    }

    /**
     * Calcula una cantidad para el número de personas actual (regla de tres).
     * @param {number} cantidad Cantidad original para RACIONES_BASE personas.
     * @returns {number}
     */
    calcular(cantidad) {
        return cantidad * this.raciones / RACIONES_BASE;
    }

    /**
     * Devuelve un array con todas las cantidades recalculadas.
     * @returns {number[]}
     */
    cantidadesNuevas() {
        return this.cantidades.map((cantidad) => this.calcular(cantidad));
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const inputRaciones = document.querySelector('#raciones');
    const spans = document.querySelectorAll('.cantidad');

    // Si la página no tiene la calculadora (por ejemplo, receta no encontrada), no se hace nada.
    if (!inputRaciones || spans.length === 0) {
        return;
    }

    // Guardar en un array las cantidades originales que ha escrito PHP.
    const cantidades = [];
    spans.forEach((span) => {
        cantidades.push(Number(span.textContent));
    });

    const calculadora = new CalculadoraRaciones(cantidades);

    // Cada vez que cambia el número de personas, se pintan las cantidades nuevas.
    inputRaciones.addEventListener('change', () => {
        const raciones = Number(inputRaciones.value);

        // Si el valor no es válido, se vuelve al número anterior.
        if (raciones < 1 || raciones > 20) {
            inputRaciones.value = calculadora.raciones;
            return;
        }

        calculadora.raciones = raciones;
        const nuevas = calculadora.cantidadesNuevas();
        spans.forEach((span, i) => {
            span.textContent = formatear(nuevas[i]);
        });
    });
});
