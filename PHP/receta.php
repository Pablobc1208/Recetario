<?php
// La sesión se abre antes de cualquier salida para saber si el usuario es el autor.
session_start();

require_once __DIR__ . '/conexion.php';

$receta = false;
$ingredientes = [];
$error = '';

// 1. Recibir el id por GET y validarlo.
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    http_response_code(400);
    $error = 'El enlace no es válido.';
} else {
    try {
        // 2. Buscar la receta con su categoría y su autor.
        $stmt = $pdo->prepare('SELECT r.id_receta, r.titulo, r.descripcion, r.instrucciones, r.tiempo_minutos, r.imagen,
                                      r.id_usuario, c.nombre AS categoria, u.nombre_usuario AS autor
                               FROM recetas r
                               JOIN categorias c ON c.id_categoria = r.id_categoria
                               JOIN usuarios u ON u.id_usuario = r.id_usuario
                               WHERE r.id_receta = :id');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $receta = $stmt->fetch();

        if ($receta === false) {
            // La receta no existe.
            http_response_code(404);
            $error = 'Esta receta no existe.';
        } else {
            // 3. Buscar sus ingredientes a través de la tabla intermedia.
            $stmt = $pdo->prepare('SELECT i.nombre, ri.cantidad, ri.unidad
                                   FROM receta_ingrediente ri
                                   JOIN ingredientes i ON i.id_ingrediente = ri.id_ingrediente
                                   WHERE ri.id_receta = :id
                                   ORDER BY i.nombre');
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $ingredientes = $stmt->fetchAll();
        }
    } catch (PDOException $e) {
        error_log($e->getMessage());
        $error = 'No se ha podido cargar la receta. Inténtalo más tarde.';
    }
}

// Solo el autor de la receta ve los botones de editar y eliminar.
$esAutor = $receta && isset($_SESSION['id_usuario']) && $_SESSION['id_usuario'] == $receta['id_usuario'];

// Variables que usan header.php (pestaña) y nav.php (enlace activo).
$titulo = $receta ? $receta['titulo'] : 'Receta no disponible';
$paginaActual = 'recetas';
require_once __DIR__ . '/header.php';
?>
<?php if ($error): ?>
    <h1>Receta no disponible</h1>
    <p><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
<?php else: ?>
    <!-- 4. Mostrar la receta -->
    <h1><?= htmlspecialchars($receta['titulo'], ENT_QUOTES, 'UTF-8') ?></h1>

    <!-- la receta a la izquierda y el temporizador a la derecha pero en movil las columnas se apilan y el temporizador queda debajo -->
    <div class="row">
        <div class="col-md-8 receta-detalle">
            <p><?= htmlspecialchars($receta['descripcion'], ENT_QUOTES, 'UTF-8') ?></p>
            <p>Categoría: <?= htmlspecialchars($receta['categoria'], ENT_QUOTES, 'UTF-8') ?></p>
            <p>Autor: <?= htmlspecialchars($receta['autor'], ENT_QUOTES, 'UTF-8') ?></p>
            <p>Tiempo: <?= (int) $receta['tiempo_minutos'] ?> minutos</p>

            <?php if ($receta['imagen']): ?>
                <img src="<?= htmlspecialchars($receta['imagen'], ENT_QUOTES, 'UTF-8') ?>"
                    alt="<?= htmlspecialchars($receta['titulo'], ENT_QUOTES, 'UTF-8') ?>">
            <?php endif; ?>

            <?php if ($esAutor): ?>
                <!-- Editar va por GET (solo muestra el formulario); eliminar va por POST (#11). -->
                <!-- La confirmación antes de eliminar está en JS/eliminar.js -->
                <p>
                    <a href="editar_receta.php?id=<?= (int) $receta['id_receta'] ?>" class="btn btn-primary">Editar receta</a>
                </p>
                <form class="form-eliminar" action="eliminar_receta.php" method="POST">
                    <input type="hidden" name="id_receta" value="<?= (int) $receta['id_receta'] ?>">
                    <button type="submit" class="btn btn-outline-primary">Eliminar receta</button>
                </form>
            <?php endif; ?>

            <h2>Ingredientes</h2>

            <!-- Calculadora de raciones (#18): JS/raciones.js recalcula las cantidades. -->
            <p>
                <label for="raciones">Cantidades para</label>
                <input type="number" id="raciones" min="1" max="20" value="4">
                personas
            </p>

            <ul>
                <?php foreach ($ingredientes as $ingrediente): ?>
                    <li>
                        <span class="cantidad"><?= (float) $ingrediente['cantidad'] ?></span>
                        <?= htmlspecialchars($ingrediente['unidad'], ENT_QUOTES, 'UTF-8') ?>
                        de <?= htmlspecialchars($ingrediente['nombre'], ENT_QUOTES, 'UTF-8') ?>
                    </li>
                <?php endforeach; ?>
            </ul>

            <h2>Elaboración</h2>
            <p><?= nl2br(htmlspecialchars($receta['instrucciones'], ENT_QUOTES, 'UTF-8')) ?></p>
        </div>

        <aside class="col-md-4">
            <!-- temporizador de cocina -->
            <section class="temporizador">
                <h2>Temporizador</h2>
                <input type="hidden" id="temporizador-minutos" value="<?= (int) $receta['tiempo_minutos'] ?>">
                <button type="button" id="temporizador-boton" class="btn temporizador-boton">
                    <svg class="temporizador-svg" viewBox="0 0 240 240" aria-hidden="true">
                        <rect class="pulsador" x="102" width="36" height="18" rx="6" />
                        <circle class="cuerpo" cx="120" cy="124" r="108" />
                        <circle class="pista" cx="120" cy="124" r="83" />
                        <circle id="temporizador-progreso" class="progreso" cx="120" cy="124" r="83"
                            transform="rotate(-90 120 124)" />
                        <g class="marcas">
                            <rect x="118" y="52" width="4" height="12" rx="2" />
                            <rect x="180" y="122" width="12" height="4" rx="2" />
                            <rect x="118" y="184" width="4" height="12" rx="2" />
                            <rect x="48" y="122" width="12" height="4" rx="2" />
                        </g>
                        <circle class="boton-centro" cx="120" cy="124" r="36" />
                        <path class="icono-play" d="M139 124L116.5 136.99V111.01L139 124Z" />
                        <g class="icono-pausa">
                            <rect x="108" y="110" width="8" height="28" rx="2" />
                            <rect x="124" y="110" width="8" height="28" rx="2" />
                        </g>
                    </svg>
                    <span id="temporizador-texto">Empezar</span>
                </button>
                <p id="temporizador-tiempo" class="temporizador-tiempo"><?= (int) $receta['tiempo_minutos'] ?>:00</p>
                <button type="button" id="temporizador-reiniciar" class="btn btn-outline-primary">Reiniciar</button>
            </section>
        </aside>
    </div>
<?php endif; ?>

<p><a href="listado_recetas.php" class="btn btn-outline-primary">Volver al listado</a></p>

<script src="../JS/raciones.js"></script>
<script src="../JS/eliminar.js"></script>
<script src="../JS/temporizador.js"></script>

<?php require_once __DIR__ . '/footer.php'; ?>