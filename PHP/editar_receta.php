<?php
 
require_once __DIR__ . '/conexion.php';
/** @var PDO $pdo */
 
session_start();
 
//inicia sesion
if (!isset($_SESSION['id_usuario'])) {
    header('Location: login.php');
    exit;
}
 
$volverMisRecetas = (($_SERVER['REQUEST_METHOD'] === 'POST'
    ? ($_POST['volver'] ?? '')
    : ($_GET['volver'] ?? '')) === 'mis_recetas');
 
$unidadesValidas = ['g', 'kg', 'ml', 'l', 'ud', 'cucharada', 'cucharadita'];
$maxIngredientes = 20; //máximo de filas de ingredientes; las filas nuevas se añaden con JS/ingredientes.js (#17)
 
//recoge el id de la receta
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idReceta = filter_input(INPUT_POST, 'id_receta', FILTER_VALIDATE_INT);
} else {
    $idReceta = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
}
 
if (!$idReceta) {
    $_SESSION['mensaje'] = ['tipo' => 'error', 'texto' => 'El enlace no es válido.'];
    header('Location: ' . ($volverMisRecetas ? 'mis_recetas.php' : 'listado_recetas.php'));
    exit;
}
 
$urlRetorno = $volverMisRecetas ? 'mis_recetas.php' : 'receta.php?id=' . (int) $idReceta;
 
//buscar la receta para saber si existe y quien es el autor
try {
    $stmt = $pdo->prepare('SELECT id_receta, titulo, descripcion, instrucciones, tiempo_minutos, id_categoria, id_usuario
                           FROM recetas WHERE id_receta = :id');
    $stmt->execute([':id' => $idReceta]);
    $receta = $stmt->fetch();
} catch (PDOException $e) {
    error_log($e->getMessage()); //el detalle tecnico solo va al log del servidor
    $_SESSION['mensaje'] = ['tipo' => 'error', 'texto' => 'No se ha podido cargar la receta. Inténtalo más tarde.'];
    header('Location: listado_recetas.php');
    exit;
}
 
if (!$receta) {
    $_SESSION['mensaje'] = ['tipo' => 'error', 'texto' => 'Esa receta no existe.'];
    header('Location: ' . ($volverMisRecetas ? 'mis_recetas.php' : 'listado_recetas.php'));
    exit;
}
 
//solo el autor la edita
if ($receta['id_usuario'] != $_SESSION['id_usuario']) {
    $_SESSION['mensaje'] = ['tipo' => 'error', 'texto' => 'Solo el autor puede editar esta receta.'];
    header('Location: ' . $urlRetorno);
    exit;
}
 
$errores = [];
 
//categorias para el selector y para validar la que se envia
$categorias = [];
try {
    $stmt = $pdo->prepare('SELECT id_categoria, nombre FROM categorias ORDER BY nombre');
    $stmt->execute();
    $categorias = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log($e->getMessage()); //el detalle tecnico solo va al log del servidor
    $errores[] = 'No se han podido cargar las categorías. Inténtalo más tarde.';
}
$idsCategorias = [];
foreach ($categorias as $cat) {
    $idsCategorias[] = $cat['id_categoria'];
}
 
//nombres de los ingredientes ya guardados: se sugieren mientras se escribe (<datalist>)
$nombresIngredientes = [];
try {
    $stmt = $pdo->prepare('SELECT nombre FROM ingredientes ORDER BY nombre');
    $stmt->execute();
    foreach ($stmt->fetchAll() as $fila) {
        $nombresIngredientes[] = $fila['nombre'];
    }
} catch (PDOException $e) {
    error_log($e->getMessage()); //sin sugerencias el formulario sigue funcionando
}
 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //los valores del formulario son los que ha escrito el usuario
    //is_string comprueba que llega texto: si alguien manda un array (campo[]=...), se trata como vacio
    $tituloReceta  = is_string($_POST['titulo'] ?? null) ? trim($_POST['titulo']) : '';
    $descripcion   = is_string($_POST['descripcion'] ?? null) ? trim($_POST['descripcion']) : '';
    $tiempoMinutos = is_string($_POST['tiempo_minutos'] ?? null) ? trim($_POST['tiempo_minutos']) : '';
    $instrucciones = is_string($_POST['instrucciones'] ?? null) ? trim($_POST['instrucciones']) : '';
    $idCategoria   = filter_input(INPUT_POST, 'id_categoria', FILTER_VALIDATE_INT);
    $tiempoValido  = filter_input(INPUT_POST, 'tiempo_minutos', FILTER_VALIDATE_INT);
 
    $nombres    = $_POST['ing_nombre'] ?? [];
    $cantidades = $_POST['ing_cantidad'] ?? [];
    $unidades   = $_POST['ing_unidad'] ?? [];
    //estos tres si tienen que ser arrays; si no lo son se descartan y salta "al menos un ingrediente"
    if (!is_array($nombres) || !is_array($cantidades) || !is_array($unidades)) {
        $nombres = [];
        $cantidades = [];
        $unidades = [];
    }
 
    //validacion en el servidor
    if ($tituloReceta === '' || strlen($tituloReceta) > 150) {
        $errores[] = 'El nombre de la receta es obligatorio y no puede pasar de 150 caracteres.';
    }
    if ($descripcion === '' || strlen($descripcion) > 255) {
        $errores[] = 'La descripción es obligatoria y no puede pasar de 255 caracteres.';
    }
    if (!$tiempoValido || $tiempoValido < 1) {
        $errores[] = 'El tiempo de preparación debe ser un número entero de minutos mayor que 0.';
    }
    if ($instrucciones === '') {
        $errores[] = 'Escribe los pasos de preparación.';
    }
    if (!in_array($idCategoria, $idsCategorias)) {
        $errores[] = 'Elige una categoría de la lista.';
    }
    //el limite de filas tambien se comprueba en el servidor, por si se salta el JavaScript
    if (count($nombres) > $maxIngredientes) {
        $errores[] = "Como máximo puedes añadir $maxIngredientes ingredientes.";
    }
 
    $ingredientes = [];
    $ingredientesValidos = [];
    foreach ($nombres as $i => $nombre) {
        //cada elemento tiene que ser texto; si no lo es se trata como vacio
        $cantidad = $cantidades[$i] ?? '';
        $unidad   = $unidades[$i] ?? '';
        $ing = [
            'nombre'   => is_string($nombre) ? trim($nombre) : '',
            'cantidad' => is_string($cantidad) ? trim($cantidad) : '',
            'unidad'   => is_string($unidad) ? $unidad : '',
        ];
        $ingredientes[] = $ing;
 
        //fila vacia se ignora
        if ($ing['nombre'] === '' && $ing['cantidad'] === '') {
            continue;
        }
 
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
 
    if (count($ingredientesValidos) === 0) {
        $errores[] = 'La receta debe tener al menos un ingrediente.';
    }
 
    //guarda si no hay errores
    if (empty($errores)) {
        try {
            //la receta y sus ingredientes se actualizan juntos o no se actualiza nada
            $pdo->beginTransaction();
 
            //UPDATE con WHEREpara que solo se cambie esa receta y añade id_usuario al WHERE para que cambie solo si eres el autor
            $stmt = $pdo->prepare('UPDATE recetas
                                   SET titulo = :titulo, descripcion = :descripcion, instrucciones = :instrucciones,
                                       tiempo_minutos = :tiempo_minutos, id_categoria = :id_categoria
                                   WHERE id_receta = :id_receta AND id_usuario = :id_usuario');
            $stmt->execute([
                ':titulo'         => $tituloReceta,
                ':descripcion'    => $descripcion,
                ':instrucciones'  => $instrucciones,
                ':tiempo_minutos' => $tiempoValido,
                ':id_categoria'   => $idCategoria,
                ':id_receta'      => $idReceta,
                ':id_usuario'     => $_SESSION['id_usuario'],
            ]);
 
            //se borran los ingredientes de la receta en la tabla intermedia y se insertan los del formulario
            $stmt = $pdo->prepare('DELETE FROM receta_ingrediente WHERE id_receta = :id_receta');
            $stmt->execute([':id_receta' => $idReceta]);
 
            $buscarIngrediente = $pdo->prepare('SELECT id_ingrediente FROM ingredientes WHERE nombre = :nombre');
            $crearIngrediente  = $pdo->prepare('INSERT INTO ingredientes (nombre) VALUES (:nombre)');
            $unirIngrediente   = $pdo->prepare('INSERT INTO receta_ingrediente (id_receta, id_ingrediente, cantidad, unidad)
                                                VALUES (:id_receta, :id_ingrediente, :cantidad, :unidad)');
 
            $idsUsados = [];
            foreach ($ingredientesValidos as $ing) {
                //si ya esta en el catalogo se reutiliza su id y si no se crea
                $buscarIngrediente->execute([':nombre' => $ing['nombre']]);
                $encontrado = $buscarIngrediente->fetch();
                if ($encontrado) {
                    $idIngrediente = $encontrado['id_ingrediente'];
                } else {
                    $crearIngrediente->execute([':nombre' => $ing['nombre']]);
                    $idIngrediente = $pdo->lastInsertId();
                }
 
                //el mismo ingrediente dos veces chocaria con la PK
                if (in_array($idIngrediente, $idsUsados)) {
                    $pdo->rollBack();
                    $errores[] = "Ingrediente {$ing['fila']}: «{$ing['nombre']}» ya está en la lista.";
                    break;
                }
                $idsUsados[] = $idIngrediente;
 
                $unirIngrediente->execute([
                    ':id_receta'      => $idReceta,
                    ':id_ingrediente' => $idIngrediente,
                    ':cantidad'       => $ing['cantidad'],
                    ':unidad'         => $ing['unidad'],
                ]);
            }
 
            if (empty($errores)) {
                $pdo->commit();
 
                //al recargar la pagina no se vuelve a enviar el formulario
                $_SESSION['mensaje'] = ['tipo' => 'exito', 'texto' => "La receta «{$tituloReceta}» se ha actualizado."];
                header('Location: ' . $urlRetorno);
                exit;
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log('Error UPDATE receta: ' . $e->getMessage());
            $_SESSION['mensaje'] = ['tipo' => 'error', 'texto' => 'No se ha podido actualizar la receta. Inténtalo de nuevo.'];
            header('Location: ' . $urlRetorno);
            exit;
        }
    }
} else {
    //el formulario se precarga con los datos que hay ahora en la base de datos
    $tituloReceta  = $receta['titulo'];
    $descripcion   = $receta['descripcion'];
    $tiempoMinutos = $receta['tiempo_minutos'];
    $instrucciones = $receta['instrucciones'];
    $idCategoria   = $receta['id_categoria'];
 
    //ingredientes actuales a traves de la tabla intermedia
    $ingredientes = [];
    try {
        $stmt = $pdo->prepare('SELECT i.nombre, ri.cantidad, ri.unidad
                               FROM receta_ingrediente ri
                               JOIN ingredientes i ON i.id_ingrediente = ri.id_ingrediente
                               WHERE ri.id_receta = :id
                               ORDER BY i.nombre');
        $stmt->execute([':id' => $idReceta]);
        $ingredientes = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log($e->getMessage()); //el detalle tecnico solo va al log del servidor
        $errores[] = 'No se han podido cargar los ingredientes de la receta. Inténtalo más tarde.';
    }
}
 
//se pintan los ingredientes que ya tiene la receta; si no tiene ninguno, una fila vacia
$numFilas = count($ingredientes);
if ($numFilas === 0) {
    $numFilas = 1;
}
 
$titulo = 'Editar receta';
$paginaActual = 'recetas';
require_once __DIR__ . '/header.php';
?>
<h1>Editar «<?= htmlspecialchars($receta['titulo'], ENT_QUOTES, 'UTF-8') ?>»</h1>
<p>Cambia lo que necesites. Para quitar un ingrediente, pulsa «Quitar»; para añadir otro, «Añadir ingrediente».</p>
 
<?php if ($errores): ?>
    <ul class="aviso aviso-error" role="alert">
        <?php foreach ($errores as $error): ?>
            <li>✕ <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
 
<form action="editar_receta.php" method="POST" id="form-receta">
    <!-- El id viaja oculto para saber qué receta se actualiza. Se vuelve a comprobar el autor en el servidor. -->
    <input type="hidden" name="id_receta" value="<?= (int) $idReceta ?>">
    <input type="hidden" name="volver" value="<?= $volverMisRecetas ? 'mis_recetas' : '' ?>">
 
    <fieldset>
        <legend>1. Datos de la receta</legend>
 
        <label for="titulo">Nombre de la receta *</label>
        <input type="text" id="titulo" name="titulo" maxlength="150" required
            value="<?= htmlspecialchars($tituloReceta, ENT_QUOTES, 'UTF-8') ?>">
 
        <label for="descripcion">Descripción *</label>
        <input type="text" id="descripcion" name="descripcion" maxlength="255" required
            value="<?= htmlspecialchars($descripcion, ENT_QUOTES, 'UTF-8') ?>">
 
        <label for="tiempo_minutos">Tiempo de preparación (minutos) *</label>
        <input type="number" id="tiempo_minutos" name="tiempo_minutos" min="1" step="1" required
            value="<?= htmlspecialchars($tiempoMinutos, ENT_QUOTES, 'UTF-8') ?>">
 
        <label for="id_categoria">Categoría *</label>
        <select id="id_categoria" name="id_categoria" required>
            <option value="">Selecciona una categoría</option>
            <?php foreach ($categorias as $cat): ?>
                <option value="<?= (int) $cat['id_categoria'] ?>" <?= $cat['id_categoria'] == $idCategoria ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cat['nombre'], ENT_QUOTES, 'UTF-8') ?>
                </option>
            <?php endforeach; ?>
        </select>
    </fieldset>
 
    <fieldset>
        <legend id="legend-ingredientes">2. Ingredientes</legend>
 
        <!-- PHP pinta los ingredientes que ya tiene la receta; JS/ingredientes.js añade y quita filas (#17). -->
        <div id="lista-ingredientes" data-maximo="<?= $maxIngredientes ?>">
            <?php for ($i = 0; $i < $numFilas; $i++):
                $ing = $ingredientes[$i] ?? ['nombre' => '', 'cantidad' => '', 'unidad' => '']; ?>
                <p class="fila-ingrediente">
                    <label for="ing_nombre_<?= $i ?>" class="etiqueta-ingrediente">Ingrediente <?= $i + 1 ?></label>
                    <input type="text" id="ing_nombre_<?= $i ?>" name="ing_nombre[]" maxlength="100"
                        list="sugerencias-ingredientes" autocomplete="off"
                        value="<?= htmlspecialchars($ing['nombre'], ENT_QUOTES, 'UTF-8') ?>">
 
                    <label for="ing_cantidad_<?= $i ?>">Cantidad</label>
                    <input type="number" id="ing_cantidad_<?= $i ?>" name="ing_cantidad[]"
                        inputmode="decimal" min="0.01" max="999999.99" step="0.01"
                        value="<?= htmlspecialchars($ing['cantidad'], ENT_QUOTES, 'UTF-8') ?>">
 
                    <label for="ing_unidad_<?= $i ?>">Unidad</label>
                    <select id="ing_unidad_<?= $i ?>" name="ing_unidad[]">
                        <option value="">Unidad</option>
                        <?php foreach ($unidadesValidas as $u): ?>
                            <option value="<?= $u ?>" <?= $u === $ing['unidad'] ? 'selected' : '' ?>><?= $u ?></option>
                        <?php endforeach; ?>
                    </select>
 
                    <button type="button" class="btn-quitar">Quitar</button>
                </p>
            <?php endfor; ?>
        </div>
 
        <!-- Sugerencias al escribir el nombre de un ingrediente: el navegador las filtra solo -->
        <datalist id="sugerencias-ingredientes">
            <?php foreach ($nombresIngredientes as $nombreIngrediente): ?>
                <option value="<?= htmlspecialchars($nombreIngrediente, ENT_QUOTES, 'UTF-8') ?>"></option>
            <?php endforeach; ?>
        </datalist>
 
        <button type="button" id="btn-anadir">+ Añadir ingrediente</button>
    </fieldset>
 
    <fieldset>
        <legend>3. Preparación</legend>
        <label for="instrucciones">Pasos de preparación *</label>
        <textarea id="instrucciones" name="instrucciones" rows="8" required><?= htmlspecialchars($instrucciones, ENT_QUOTES, 'UTF-8') ?></textarea>
    </fieldset>
 
    <!-- Botones del final: guardar y, a su derecha, cancelar (vuelve a la página de origen). -->
    <div class="acciones-formulario">
        <button type="submit">Guardar cambios</button>
        <a href="<?= htmlspecialchars($urlRetorno, ENT_QUOTES, 'UTF-8') ?>" class="btn-cancelar">Cancelar</a>
    </div>
</form>
<script defer src="../JS/validarformulario_receta.js"></script>
<script defer src="../JS/ingredientes.js"></script>
<?php require_once __DIR__ . '/footer.php'; ?>