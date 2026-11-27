<?php
session_start();

// Solo los usuarios que han iniciado sesión pueden crear recetas.
if (!isset($_SESSION['id_usuario'])) {
    header('Location: login.php');
    exit;
}

// Carga la conexión PDO.
require_once __DIR__ . '/conexion.php';

// Mismos valores que el ENUM de la columna receta_ingrediente.unidad.
$unidadesValidas = ['g', 'kg', 'ml', 'l', 'ud', 'cucharada', 'cucharadita'];
$filasIngredientes = 5;

// Estos valores comienzan vacíos y se rellenan con el POST si se envía el formulario.
$errores = [];

// Las categorías se usan para llenar el selector y validar la categoría enviada.
$categorias = [];
try {
    $stmt = $pdo->prepare('SELECT id_categoria, nombre FROM categorias ORDER BY nombre');
    $stmt->execute();
    $categorias = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log($e->getMessage()); // el detalle técnico solo va al log del servidor
    $errores[] = 'No se han podido cargar las categorías. Inténtalo más tarde.';
}
$idsCategorias = [];
foreach ($categorias as $cat) {
    $idsCategorias[] = $cat['id_categoria'];
}
$tituloReceta = '';
$descripcion = '';
$tiempoMinutos = '';
$instrucciones = '';
$idCategoria = '';
$ingredientes = [];

// GET solo muestra el formulario; POST también procesa y valida los datos recibidos.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //is_string comprueba que llega texto si se manda por ej un array se trata como vacio
    $tituloReceta = is_string($_POST['titulo'] ?? null) ? trim($_POST['titulo']) : '';
    $descripcion = is_string($_POST['descripcion'] ?? null) ? trim($_POST['descripcion']) : '';
    $tiempoMinutos = is_string($_POST['tiempo_minutos'] ?? null) ? trim($_POST['tiempo_minutos']) : '';   // texto, para volver a mostrarlo si hay errores
    $instrucciones = is_string($_POST['instrucciones'] ?? null) ? trim($_POST['instrucciones']) : '';
    $idCategoria = filter_input(INPUT_POST, 'id_categoria', FILTER_VALIDATE_INT);
    $tiempoValido = filter_input(INPUT_POST, 'tiempo_minutos', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

    // Los ingredientes llegan como tres listas paralelas: nombre, cantidad y unidad.
    $nombres = $_POST['ing_nombre'] ?? [];
    $cantidades = $_POST['ing_cantidad'] ?? [];
    $unidades = $_POST['ing_unidad'] ?? [];

    //estos tres sí tienen que ser arrays si no lo son se descartan y saltará "Añade al menos un ingrediente".
    if (!is_array($nombres) || !is_array($cantidades) || !is_array($unidades)) {
        $nombres = [];
        $cantidades = [];
        $unidades = [];
    }

    // 2. Validar los campos obligatorios antes de intentar guardar.
    if ($tituloReceta === '' || strlen($tituloReceta) > 150) {
        $errores[] = 'El nombre de la receta es obligatorio y no puede pasar de 150 caracteres.';
    }
    if ($descripcion === '' || strlen($descripcion) > 255) {
        $errores[] = 'La descripción es obligatoria y no puede pasar de 255 caracteres.';
    }
    if (!$tiempoValido) {
        $errores[] = 'El tiempo de preparación debe ser un número entero de minutos mayor que 0.';
    }
    if ($instrucciones === '') {
        $errores[] = 'Escribe los pasos de preparación.';
    }
    if (!in_array($idCategoria, $idsCategorias)) {
        $errores[] = 'Elige una categoría de la lista.';
    }

    // Conserva los ingredientes enviados para volver a mostrarlos si hay errores.
    // La lista de ingredientes válidos se usa después para guardarlos.
    $ingredientesValidos = [];
    foreach ($nombres as $i => $nombre) {
        //cada elemento tiene que ser texto si no lo es se trata como vacio
        $cantidad = $cantidades[$i] ?? '';
        $unidad = $unidades[$i] ?? '';
        $ing = [
            'nombre' => is_string($nombre) ? trim($nombre) : '',
            'cantidad' => is_string($cantidad) ? trim($cantidad) : '',
            'unidad' => is_string($unidad) ? $unidad : '',
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
        $cantidadValida = preg_match('/^[0-9]{1,6}(\.[0-9]{1,2})?$/', $ing['cantidad']) === 1
            && (float) $ing['cantidad'] > 0;
        if (!$cantidadValida) {
            $errores[] = "Ingrediente $fila: la cantidad debe ser mayor que 0, con hasta 6 cifras enteras y 2 decimales usando punto.";
        }
        if (!in_array($ing['unidad'], $unidadesValidas)) {
            $errores[] = "Ingrediente $fila: elige una unidad.";
        }
        $ing['fila'] = $fila;
        $ingredientesValidos[] = $ing;
    }

    // La receta debe incluir al menos un ingrediente.
    if (count($ingredientesValidos) === 0) {
        $errores[] = 'Añade al menos un ingrediente.';
    }

    // 3. Procesar: solo guarda si todas las validaciones anteriores han pasado.
    if (empty($errores)) {
        try {
            // La transacción hace que la receta y todos sus ingredientes se guarden juntos:
            // si algo falla, no queda la receta guardada a medias.
            $pdo->beginTransaction();

            $stmt = $pdo->prepare('INSERT INTO recetas (titulo, descripcion, instrucciones, tiempo_minutos, id_categoria, id_usuario)
                                   VALUES (:titulo, :descripcion, :instrucciones, :tiempo_minutos, :id_categoria, :id_usuario)');
            $stmt->execute([
                ':titulo' => $tituloReceta,
                ':descripcion' => $descripcion,
                ':instrucciones' => $instrucciones,
                ':tiempo_minutos' => $tiempoValido,
                ':id_categoria' => $idCategoria,
                ':id_usuario' => $_SESSION['id_usuario'],
            ]);
            $idReceta = $pdo->lastInsertId();

            // Se preparan una vez y se ejecutan para cada ingrediente.
            $buscarIngrediente = $pdo->prepare('SELECT id_ingrediente FROM ingredientes WHERE nombre = :nombre');
            $crearIngrediente = $pdo->prepare('INSERT INTO ingredientes (nombre) VALUES (:nombre)');
            $unirIngrediente = $pdo->prepare('INSERT INTO receta_ingrediente (id_receta, id_ingrediente, cantidad, unidad)
                                                VALUES (:id_receta, :id_ingrediente, :cantidad, :unidad)');

            $idsUsados = [];
            foreach ($ingredientesValidos as $ing) {
                // Si el ingrediente ya está en el catálogo se usa su id; si no, se crea.
                $buscarIngrediente->execute([':nombre' => $ing['nombre']]);
                $encontrado = $buscarIngrediente->fetch();
                if ($encontrado) {
                    $idIngrediente = $encontrado['id_ingrediente'];
                } else {
                    $crearIngrediente->execute([':nombre' => $ing['nombre']]);
                    $idIngrediente = $pdo->lastInsertId();
                }

                // MySQL no distingue mayúsculas al buscar, así que "Huevo" y "huevo" dan el mismo id.
                // Si el id ya se ha usado en esta receta, se deshace todo y se avisa.
                if (in_array($idIngrediente, $idsUsados)) {
                    $pdo->rollBack();
                    $errores[] = "Ingrediente {$ing['fila']}: «{$ing['nombre']}» ya está en la lista.";
                    break;
                }
                $idsUsados[] = $idIngrediente;

                // La cantidad y la unidad se guardan en la tabla intermedia.
                $unirIngrediente->execute([
                    ':id_receta' => $idReceta,
                    ':id_ingrediente' => $idIngrediente,
                    ':cantidad' => $ing['cantidad'],
                    ':unidad' => $ing['unidad'],
                ]);
            }

            if (empty($errores)) {
                // Confirma todos los cambios de la transacción.
                $pdo->commit();

                // 4. Feedback: guarda un mensaje temporal y redirige para no repetir el envío al recargar.
                $_SESSION['mensaje'] = [
                    'tipo' => 'exito',
                    'texto' => "La receta «{$tituloReceta}» se ha creado correctamente.",
                ];
                header('Location: formulario_receta.php');
                exit;
            }
        } catch (PDOException $e) {
            // Si falla una consulta, deshace los cambios y registra el detalle en el log del servidor.
            $pdo->rollBack();
            error_log('Error INSERT receta: ' . $e->getMessage());
            $errores[] = 'No se ha podido guardar la receta. Inténtalo de nuevo.';
        }
    }
}

// Tras un POST se muestran las filas enviadas; en la primera visita se muestran cinco vacías.
$numFilas = count($ingredientes);
if ($numFilas === 0) {
    $numFilas = $filasIngredientes;
}

// Datos para la plantilla común: título de la pestaña y página activa del menú.
$titulo = 'Nueva receta';
$paginaActual = 'nueva';
require_once __DIR__ . '/header.php';
?>
<h1>Crear una receta</h1>
<p>Completa los datos, añade al menos un ingrediente y explica cómo preparar la receta.</p>

<?php if ($errores): ?>
    <ul class="aviso aviso-error" role="alert">
        <?php foreach ($errores as $error): ?>
            <li>✕ <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<form action="formulario_receta.php" method="POST" id="form-receta">
    <fieldset>
        <legend>1. Datos de la receta</legend>
        <p>Escribe un nombre, una breve descripción, el tiempo y la categoría.</p>

        <label for="titulo">Nombre de la receta *</label>
        <input type="text" id="titulo" name="titulo" maxlength="150" required placeholder="Ej.: Tortilla de patatas"
            value="<?= htmlspecialchars($tituloReceta, ENT_QUOTES, 'UTF-8') ?>">

        <label for="descripcion">Descripción *</label>
        <input type="text" id="descripcion" name="descripcion" maxlength="255" required
            placeholder="Ej.: La clásica tortilla española, jugosa por dentro."
            value="<?= htmlspecialchars($descripcion, ENT_QUOTES, 'UTF-8') ?>">

        <label for="tiempo_minutos">Tiempo de preparación (minutos) *</label>
        <input type="number" id="tiempo_minutos" name="tiempo_minutos" min="1" step="1" required placeholder="Ej.: 40"
            value="<?= htmlspecialchars($tiempoMinutos, ENT_QUOTES, 'UTF-8') ?>">

        <label for="id_categoria">Categoría *</label>
        <select id="id_categoria" name="id_categoria" required>
            <option value="">Selecciona una categoría</option>
            <?php foreach ($categorias as $cat): ?>
                <option value="<?= $cat['id_categoria'] ?>" <?= $cat['id_categoria'] == $idCategoria ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cat['nombre'], ENT_QUOTES, 'UTF-8') ?>
                </option>
            <?php endforeach; ?>
        </select>
    </fieldset>

    <fieldset>
        <legend id="legend-ingredientes">2. Ingredientes</legend>
        <p>Añade al menos uno. En cada fila indica el ingrediente, la cantidad y la unidad.</p>
        <?php for ($i = 0; $i < $numFilas; $i++):
            // Si no llegó una fila para esta posición, se prepara una vacía.
            $ing = $ingredientes[$i] ?? ['nombre' => '', 'cantidad' => '', 'unidad' => '']; ?>
            <p>
                <label for="ing_nombre_<?= $i ?>">Ingrediente <?= $i + 1 ?></label>
                <input type="text" id="ing_nombre_<?= $i ?>" name="ing_nombre[]" maxlength="100" placeholder="Ej.: Harina"
                    value="<?= htmlspecialchars($ing['nombre'], ENT_QUOTES, 'UTF-8') ?>">

                <label for="ing_cantidad_<?= $i ?>">Cantidad</label>
                <input type="number" id="ing_cantidad_<?= $i ?>" name="ing_cantidad[]" inputmode="decimal" min="0.01"
                    max="999999.99" step="0.01" placeholder="Ej.: 200"
                    value="<?= htmlspecialchars($ing['cantidad'], ENT_QUOTES, 'UTF-8') ?>">

                <label for="ing_unidad_<?= $i ?>">Unidad</label>
                <select id="ing_unidad_<?= $i ?>" name="ing_unidad[]">
                    <option value="">Unidad</option>
                    <?php foreach ($unidadesValidas as $u): ?>
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
            placeholder="1. Prepara los ingredientes.&#10;2. Sigue con la elaboración..."><?= htmlspecialchars($instrucciones, ENT_QUOTES, 'UTF-8') ?></textarea>
    </fieldset>

    <button type="submit">Guardar receta</button>
</form>

<script defer src="../JS/validarformulario_receta.js"></script>
<?php require_once __DIR__ . '/footer.php'; ?>