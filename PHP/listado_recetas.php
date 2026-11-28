<?php
require_once __DIR__ . '/conexion.php';

$recetas = [];
$categorias = [];
$sugerenciasTitulos = [];
$sugerenciasIngredientes = [];
$error = '';

// 1. Recuperar los filtros enviados por GET (si no se envían, quedan vacíos).
$idCategoria = filter_input(INPUT_GET, 'categoria', FILTER_VALIDATE_INT);
// is_string comprueba que llega texto: si alguien pone ?texto[]=x en la URL, se trata como vacío.
$ingrediente = is_string($_GET['ingrediente'] ?? null) ? trim($_GET['ingrediente']) : '';
$texto       = is_string($_GET['texto'] ?? null) ? trim($_GET['texto']) : '';

// Hay filtros si se ha elegido una categoría válida o se ha escrito algo en los campos de texto.
$hayFiltros = $idCategoria || $ingrediente !== '' || $texto !== '';

try {
    // Categorías para llenar el selector del formulario de filtros.
    $stmt = $pdo->prepare('SELECT id_categoria, nombre FROM categorias ORDER BY nombre');
    $stmt->execute();
    $categorias = $stmt->fetchAll();

    // Títulos de recetas y nombres de ingredientes: se sugieren al escribir en los filtros (<datalist>).
    $sugerenciasTitulos = [];
    $stmt = $pdo->prepare('SELECT titulo FROM recetas ORDER BY titulo');
    $stmt->execute();
    foreach ($stmt->fetchAll() as $fila) {
        $sugerenciasTitulos[] = $fila['titulo'];
    }

    $sugerenciasIngredientes = [];
    $stmt = $pdo->prepare('SELECT nombre FROM ingredientes ORDER BY nombre');
    $stmt->execute();
    foreach ($stmt->fetchAll() as $fila) {
        $sugerenciasIngredientes[] = $fila['nombre'];
    }

    // 2. Construir la consulta: se parte de la consulta de todas las recetas
    //    y se añade una condición por cada filtro que se haya rellenado.
    //    "WHERE 1 = 1" siempre se cumple; sirve para poder añadir cada filtro con "AND".
    $sql = 'SELECT r.id_receta, r.titulo, r.descripcion, r.tiempo_minutos, r.imagen,
                   c.nombre AS categoria, u.nombre_usuario AS autor
            FROM recetas r
            JOIN categorias c ON c.id_categoria = r.id_categoria
            JOIN usuarios u ON u.id_usuario = r.id_usuario
            WHERE 1 = 1';
    $parametros = [];

    if ($idCategoria) {
        $sql .= ' AND r.id_categoria = :categoria';
        $parametros[':categoria'] = $idCategoria;
    }
    if ($texto !== '') {
        // LIKE con % delante y detrás: el título contiene el texto en cualquier posición.
        $sql .= ' AND r.titulo LIKE :texto';
        $parametros[':texto'] = '%' . $texto . '%';
    }
    if ($ingrediente !== '') {
        // La receta debe tener algún ingrediente cuyo nombre contenga el texto buscado.
        $sql .= ' AND r.id_receta IN (SELECT receta_ingrediente.id_receta
                                      FROM receta_ingrediente
                                      JOIN ingredientes ON ingredientes.id_ingrediente = receta_ingrediente.id_ingrediente
                                      WHERE ingredientes.nombre LIKE :ingrediente)';
        $parametros[':ingrediente'] = '%' . $ingrediente . '%';
    }

    $sql .= ' ORDER BY r.fecha_creacion DESC, r.id_receta DESC';

    // Los datos del usuario nunca se concatenan en el SQL: van aparte, como parámetros.
    $stmt = $pdo->prepare($sql);
    $stmt->execute($parametros);
    $recetas = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log($e->getMessage());
    $error = 'No se han podido cargar las recetas. Inténtalo más tarde.';
}

// Variables que usan header.php (pestaña) y nav.php (enlace activo).
$titulo = 'Recetas';
$paginaActual = 'recetas';
require_once __DIR__ . '/header.php';
?>
<h1>Recetas</h1>

<!-- 3. Formulario de filtros por GET: los valores elegidos se mantienen al mostrar los resultados -->
<form action="listado_recetas.php" method="GET" class="row g-3 align-items-end mb-4">
    <div class="col-12 col-md-4">
        <label for="texto" class="form-label">Buscar en el título</label>
        <input type="text" id="texto" name="texto" class="form-control" placeholder="Ej.: tortilla"
            list="sugerencias-titulos" autocomplete="off"
            value="<?= htmlspecialchars($texto, ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="col-12 col-md-3">
        <label for="categoria" class="form-label">Categoría</label>
        <select id="categoria" name="categoria" class="form-select">
            <option value="">Todas las categorías</option>
            <?php foreach ($categorias as $cat): ?>
                <option value="<?= (int) $cat['id_categoria'] ?>" <?= $cat['id_categoria'] == $idCategoria ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cat['nombre'], ENT_QUOTES, 'UTF-8') ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-12 col-md-3">
        <label for="ingrediente" class="form-label">Ingrediente</label>
        <input type="text" id="ingrediente" name="ingrediente" class="form-control" placeholder="Ej.: huevo"
            list="sugerencias-filtro-ingredientes" autocomplete="off"
            value="<?= htmlspecialchars($ingrediente, ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="col-12 col-md-2">
        <button type="submit" class="btn btn-primary w-100">Buscar</button>
        <?php if ($hayFiltros): ?>
            <!-- Enlace con aspecto de botón (clases de Bootstrap) -->
            <a href="listado_recetas.php" class="btn btn-outline-secondary w-100 mt-2">Quitar filtros</a>
        <?php endif; ?>
    </div>

    <!-- Sugerencias que el navegador va mostrando mientras se escribe en los filtros -->
    <datalist id="sugerencias-titulos">
        <?php foreach ($sugerenciasTitulos as $tituloSugerido): ?>
            <option value="<?= htmlspecialchars($tituloSugerido, ENT_QUOTES, 'UTF-8') ?>"></option>
        <?php endforeach; ?>
    </datalist>
    <datalist id="sugerencias-filtro-ingredientes">
        <?php foreach ($sugerenciasIngredientes as $ingredienteSugerido): ?>
            <option value="<?= htmlspecialchars($ingredienteSugerido, ENT_QUOTES, 'UTF-8') ?>"></option>
        <?php endforeach; ?>
    </datalist>
</form>

<?php if ($error): ?>
    <p>
        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
    </p>
<?php elseif (count($recetas) === 0 && $hayFiltros): ?>
    <!-- 4. Mensaje cuando los filtros no encuentran ninguna receta -->
    <p>No hay recetas que coincidan con tu búsqueda.</p>
    <a href="listado_recetas.php" class="btn btn-outline-primary">Ver todas las recetas</a>
<?php elseif (count($recetas) === 0): ?>
    <p>Todavía no hay recetas. <a href="formulario_receta.php">Crea la primera</a>.</p>
<?php else: ?>
    <!-- botones para elegir la vista empiezan ocultos y los muestra JS/cookies.js -->
    <div id="selector-vista" class="d-none mb-3">
        <span>Ver como:</span>
        <button type="button" id="vista-cuadricula" class="btn btn-outline-secondary btn-sm">Cuadrícula</button>
        <button type="button" id="vista-lista" class="btn btn-outline-secondary btn-sm">Lista</button>
    </div>

    <p>
        <?= count($recetas) ?>
        <?php if ($hayFiltros): ?>
            <?= count($recetas) === 1 ? 'receta encontrada' : 'recetas encontradas' ?>.
        <?php else: ?>
            <?= count($recetas) === 1 ? 'receta publicada' : 'recetas publicadas' ?>.
        <?php endif; ?>
    </p>

    <!-- Una tarjeta por receta, recorriendo el array con foreach.
         El id lo usa JS/cookies.js para añadir o quitar la clase "vista-lista". -->
    <div id="listado-recetas" class="row g-4">
        <?php foreach ($recetas as $receta): ?>
            <div class="col-12 col-md-6 col-lg-4">
                <article class="card h-100">
                    <?php if ($receta['imagen']): ?>
                        <img src="<?= htmlspecialchars($receta['imagen'], ENT_QUOTES, 'UTF-8') ?>" class="card-img-top"
                            alt="<?= htmlspecialchars($receta['titulo'], ENT_QUOTES, 'UTF-8') ?>">
                    <?php endif; ?>
                    <div class="card-body">
                        <h2 class="card-title h5">
                            <!-- Enlace a la vista de detalle con el id por GET -->
                            <a href="receta.php?id=<?= (int) $receta['id_receta'] ?>">
                                <?= htmlspecialchars($receta['titulo'], ENT_QUOTES, 'UTF-8') ?>
                            </a>
                        </h2>
                        <p class="card-text">
                            <?= htmlspecialchars($receta['descripcion'], ENT_QUOTES, 'UTF-8') ?>
                        </p>
                    </div>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item">Categoría:
                            <?= htmlspecialchars($receta['categoria'], ENT_QUOTES, 'UTF-8') ?>
                        </li>
                        <li class="list-group-item">Tiempo:
                            <?= (int) $receta['tiempo_minutos'] ?> minutos
                        </li>
                        <li class="list-group-item">Autor:
                            <?= htmlspecialchars($receta['autor'], ENT_QUOTES, 'UTF-8') ?>
                        </li>
                    </ul>
                </article>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- guarda en una cookie la vista elegida y la aplica al cargar. -->
<script src="../JS/cookies.js"></script>

<?php require_once __DIR__ . '/footer.php'; ?>