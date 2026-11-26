<?php
require_once __DIR__ . '/conexion.php';

$recetas = [];
$error = '';

try {
    // 1. Recuperar todas las recetas con su categoría y su autor, de la más nueva a la más antigua.
    $stmt = $pdo->prepare('SELECT r.id_receta, r.titulo, r.descripcion, r.tiempo_minutos, r.imagen,
                                  c.nombre AS categoria, u.nombre_usuario AS autor
                           FROM recetas r
                           JOIN categorias c ON c.id_categoria = r.id_categoria
                           JOIN usuarios u ON u.id_usuario = r.id_usuario
                           ORDER BY r.fecha_creacion DESC, r.id_receta DESC');
    $stmt->execute();
    // fetchAll devuelve un array con una fila (array asociativo) por receta.
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

<?php if ($error): ?>
    <p>
        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
    </p>
<?php elseif (count($recetas) === 0): ?>
    <!-- 2. Mensaje cuando no hay resultados -->
    <p>Todavía no hay recetas. <a href="formulario_receta.php">Crea la primera</a>.</p>
<?php else: ?>
    <p>
        <?= count($recetas) ?>
        <?= count($recetas) === 1 ? 'receta publicada' : 'recetas publicadas' ?>.
    </p>

    <!-- 3. Una tarjeta por receta, recorriendo el array con foreach -->
    <div class="row g-4">
        <?php foreach ($recetas as $receta): ?>
            <div class="col-12 col-md-6 col-lg-4">
                <article class="card h-100">
                    <?php if ($receta['imagen']): ?>
                        <img src="<?= htmlspecialchars($receta['imagen'], ENT_QUOTES, 'UTF-8') ?>" class="card-img-top"
                            alt="<?= htmlspecialchars($receta['titulo'], ENT_QUOTES, 'UTF-8') ?>">
                    <?php endif; ?>
                    <div class="card-body">
                        <h2 class="card-title h5">
                            <!-- 4. Enlace a la vista de detalle con el id por GET -->
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

<?php require_once __DIR__ . '/footer.php'; ?>