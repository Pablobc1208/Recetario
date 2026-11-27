# Registro de pruebas

Cada prueba indica los pasos, el resultado esperado y el obtenido. Las capturas están en [`docs/depuracionJS/`](depuracionJS/).

## JavaScript (depuración con DevTools, #20)

Detalle de cada sesión en [depuracion-js.md](depuracion-js.md).

| # | Prueba | Pasos | Resultado esperado | Resultado obtenido | Evidencia |
|---|---|---|---|---|---|
| JS-1 | Quitar fila de ingrediente | Nueva receta → «+ Añadir ingrediente» → «Quitar» | La fila desaparece | ❌ `TypeError: fila.remove is not a function` (`ingredientes.js`). Corregido: `fila = event.target.parentNode`. ✅ Tras el cambio, la fila se quita | [error](depuracionJS/depuracion_ingredientes.png), [breakpoint](depuracionJS/depuracion_ingredientes_breakpoint.png) |
| PHP-1 | Abrir «Nueva receta» | Entrar en `formulario_receta.php` | El formulario sin avisos | ❌ `Warning: Undefined variable $errores` (línea 213). Corregido: `$errores = [];` al inicio. ✅ Tras el cambio no sale | [aviso](depuracionJS/depuracion_ingredientes.png) |
| JS-2 | Quitar la última fila | Dejar una sola fila → «Quitar» | La fila se queda pero con los campos vacíos | ✅ Correcto | — |
| JS-3 | Calcular raciones | Receta → cambiar personas de 4 a 5 | Las cantidades se multiplican por 1,25 (200 g → 250 g) | ✅ Correcto | [raciones](depuracionJS/depuracion_raciones.png) |
| JS-4 | Raciones fuera de rango | Escribir 25 personas | Vuelve al valor anterior | ✅ Correcto | [raciones](depuracionJS/depuracion_raciones.png) |
| JS-5 | Formulario vacío | Nueva receta → «Guardar» sin rellenar | Mensajes de error y no se envía | ✅ Correcto | [validación 1](depuracionJS/depuracion-validacion1.png), [2](depuracionJS/depuracion-validacion2.png), [3](depuracionJS/depuracion-validacion3.png) |
| JS-6 | Cantidad con coma | Cantidad `0,5` | Error: usar punto para los decimales | ✅ Correcto | [validación 1](depuracionJS/depuracion-validacion1.png), [2](depuracionJS/depuracion-validacion2.png), [3](depuracionJS/depuracion-validacion3.png) |
| JS-7 | Guardar vista del listado | Aceptar cookies → pulsar «Lista» → recargar | Se mantiene la vista en lista y existe la cookie `vista_listado` | ✅ Correcto | — |
| JS-8 | Confirmar al eliminar | Mis recetas → «Eliminar» → «Cancelar» | La receta no se borra | ✅ Correcto | — |
