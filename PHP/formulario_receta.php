<?php
session_start();

// Carga la conexión PDO.
require_once __DIR__ . '/conexion.php';

const UNIDADES = ['g', 'ml', 'ud', 'cucharada'];
const FILAS_INGREDIENTES = 5;

// Escapa los valores antes de mostrarlos en HTML para evitar que se interpreten como código.
function e(string $texto): string
{
    return htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
}

// Las categorías se usan para llenar el selector y validar la categoría enviada.
$stmt = $pdo->prepare('SELECT id_categoria, nombre FROM categorias ORDER BY nombre');
$stmt->execute();
$categorias = $stmt->fetchAll();
$idsCategorias = [];
foreach ($categorias as $cat) {
    $idsCategorias[] = $cat['id_categoria'];
}

// Estos valores comienzan vacíos y se rellenan con el POST si se envía el formulario.
$errores = [];
$titulo = '';
$instrucciones = '';
$idCategoria = '';
$ingredientes = [];

// GET solo muestra el formulario; POST también procesa y valida los datos recibidos.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recupera los campos principales y elimina espacios al principio y al final.
    $titulo        = trim($_POST['titulo'] ?? '');
    $instrucciones = trim($_POST['instrucciones'] ?? '');
    $idCategoria   = filter_input(INPUT_POST, 'id_categoria', FILTER_VALIDATE_INT);

    // Los ingredientes llegan como tres listas paralelas: nombre, cantidad y unidad.
    $nombres       = $_POST['ing_nombre'] ?? [];
    $cantidades    = $_POST['ing_cantidad'] ?? [];
    $unidades      = $_POST['ing_unidad'] ?? [];

    // Valida los campos obligatorios antes de intentar guardar.
    if ($titulo === '' || strlen($titulo) > 150) {
        $errores[] = 'El título es obligatorio y no puede pasar de 150 caracteres.';
    }
    if ($instrucciones === '') {
        $errores[] = 'Escribe la descripción y los pasos de la receta.';
    }
    if (!in_array($idCategoria, $idsCategorias)) {
        $errores[] = 'Elige una categoría de la lista.';
    }

    // Conserva los ingredientes enviados para volver a mostrarlos si hay errores.
    // La lista de ingredientes válidos se usa después para guardarlos.
    $ingredientesValidos = [];
    foreach ($nombres as $i => $nombre) {
        $ing = [
            'nombre'   => trim($nombre),
            'cantidad' => trim($cantidades[$i] ?? ''),
            'unidad'   => $unidades[$i] ?? '',
        ];
        $ingredientes[] = $ing;

        // Una fila sin nombre ni cantidad se considera vacía y no se valida ni se guarda.
        if ($ing['nombre'] === '' && $ing['cantidad'] === '') {
            continue;
        }

        // Usa el número de fila para que el usuario pueda localizar el error.
        $fila = $i + 1;
        if ($ing['nombre'] === '' || strlen($ing['nombre']) > 100) {
            $errores[] = "Ingrediente $fila: escribe un nombre de hasta 100 caracteres.";
        }
        if (!is_numeric($ing['cantidad']) || $ing['cantidad'] <= 0) {
            $errores[] = "Ingrediente $fila: la cantidad debe ser un número mayor que 0.";
        }
        if (!in_array($ing['unidad'], UNIDADES)) {
            $errores[] = "Ingrediente $fila: elige una unidad.";
        }
        $ingredientesValidos[] = $ing;
    }

    // La receta debe incluir al menos un ingrediente.
    if (count($ingredientesValidos) === 0) {
        $errores[] = 'Añade al menos un ingrediente.';
    }

    // Solo guarda si todas las validaciones anteriores han pasado.
    if (empty($errores)) {
        try {
            // La transacción hace que la receta y todos sus ingredientes se guarden juntos:
            // si una inserción falla, no debería quedar la receta guardada a medias.
            $pdo->beginTransaction();

            $stmt = $pdo->prepare('INSERT INTO recetas (titulo, instrucciones, id_categoria)
                                   VALUES (:titulo, :instrucciones, :id_categoria)');
            $stmt->execute([
                ':titulo'        => $titulo,
                ':instrucciones' => $instrucciones,
                ':id_categoria'  => $idCategoria,
            ]);
            $idReceta = $pdo->lastInsertId();

            // Este INSERT presupone que ingredientes tiene cantidad, unidad e id_receta.
            $stmt = $pdo->prepare('INSERT INTO ingredientes (nombre, cantidad, unidad, id_receta)
                                   VALUES (:nombre, :cantidad, :unidad, :id_receta)');
            foreach ($ingredientesValidos as $ing) {
                $stmt->execute([
                    ':nombre'    => $ing['nombre'],
                    ':cantidad'  => $ing['cantidad'],
                    ':unidad'    => $ing['unidad'],
                    ':id_receta' => $idReceta,
                ]);
            }

            // Confirma todos los cambios de la transacción.
            $pdo->commit();

            // Guarda un mensaje temporal y redirige para evitar repetir el envío al recargar.
            $_SESSION['mensaje'] = "La receta «{$titulo}» se ha guardado correctamente.";
            header('Location: formulario_receta.php');
            exit;
        } catch (PDOException $e) {
            // Si falla una consulta, deshace los cambios y registra el detalle en el log del servidor.
            $pdo->rollBack();
            error_log('Error INSERT receta: ' . $e->getMessage());
            $errores[] = 'No se ha podido guardar la receta. Inténtalo de nuevo.';
        }
    }
}

// Lee y borra el mensaje para que solo aparezca una vez.
$mensaje = $_SESSION['mensaje'] ?? '';
unset($_SESSION['mensaje']);

// Tras un POST se muestran las filas enviadas; en la primera visita se muestran cinco vacías.
$numFilas = count($ingredientes) ?: FILAS_INGREDIENTES;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nueva receta · Recetario</title>
</head>
<body>
<main>
    <h1>Crear una receta</h1>
    <p>Completa los datos, añade al menos un ingrediente y explica cómo preparar la receta.</p>

    <?php if ($mensaje): ?>
        <p class="aviso aviso-ok" role="status">✓ <?= e($mensaje) ?></p>
    <?php endif; ?>

    <?php if ($errores): ?>
        <ul class="aviso aviso-error" role="alert">
            <?php foreach ($errores as $error): ?>
                <li>✕ <?= e($error) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form action="formulario_receta.php" method="POST">
        <fieldset>
            <legend>1. Datos de la receta</legend>
            <p>Escribe un nombre y elige la categoría que mejor la describa.</p>

            <label for="titulo">Nombre de la receta *</label>
            <input type="text" id="titulo" name="titulo" maxlength="150" required
                   placeholder="Ej.: Tortilla de patatas" value="<?= e($titulo) ?>">

            <label for="id_categoria">Categoría *</label>
            <select id="id_categoria" name="id_categoria" required>
                <option value="">Selecciona una categoría</option>
                <?php foreach ($categorias as $cat): ?>
                    <option value="<?= $cat['id_categoria'] ?>" <?= $cat['id_categoria'] == $idCategoria ? 'selected' : '' ?>>
                        <?= e($cat['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </fieldset>

        <fieldset>
            <legend>2. Ingredientes</legend>
            <p>Añade al menos uno. En cada fila indica el ingrediente, la cantidad y la unidad.</p>
            <?php for ($i = 0; $i < $numFilas; $i++):
                // Si no llegó una fila para esta posición, se prepara una vacía.
                $ing = $ingredientes[$i] ?? ['nombre' => '', 'cantidad' => '', 'unidad' => '']; ?>
                <p>
                    <label for="ing_nombre_<?= $i ?>">Ingrediente <?= $i + 1 ?></label>
                    <input type="text" id="ing_nombre_<?= $i ?>" name="ing_nombre[]" maxlength="100"
                           placeholder="Ej.: Harina" value="<?= e($ing['nombre']) ?>">

                    <label for="ing_cantidad_<?= $i ?>">Cantidad</label>
                    <input type="text" id="ing_cantidad_<?= $i ?>" name="ing_cantidad[]"
                           inputmode="decimal" placeholder="Ej.: 200" value="<?= e($ing['cantidad']) ?>">

                    <label for="ing_unidad_<?= $i ?>">Unidad</label>
                    <select id="ing_unidad_<?= $i ?>" name="ing_unidad[]">
                        <option value="">Unidad</option>
                        <?php foreach (UNIDADES as $u): ?>
                            <option value="<?= $u ?>" <?= $u === $ing['unidad'] ? 'selected' : '' ?>><?= $u ?></option>
                        <?php endforeach; ?>
                    </select>
                </p>
            <?php endfor; ?>
        </fieldset>

        <fieldset>
            <legend>3. Preparación</legend>
            <p>Explica los pasos en el orden en que deben hacerse.</p>
            <label for="instrucciones">Pasos de preparación *</label>
            <textarea id="instrucciones" name="instrucciones" rows="8" required
                      placeholder="1. Prepara los ingredientes.&#10;2. Sigue con la elaboración..."><?= e($instrucciones) ?></textarea>
        </fieldset>

        <button type="submit">Guardar receta</button>
    </form>
</main>
</body>
</html>