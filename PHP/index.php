<?php
// Página de inicio: bienvenida, accesos rápidos y las últimas recetas publicadas.
require_once __DIR__ . '/conexion.php';

$ultimas = [];
$error = '';

try {
    // Las 3 recetas más recientes, con su categoría y su autor.
    $stmt = $pdo->prepare('SELECT r.id_receta, r.titulo, r.descripcion, r.tiempo_minutos, r.imagen,
                                  c.nombre AS categoria, u.nombre_usuario AS autor
                           FROM recetas r
                           JOIN categorias c ON c.id_categoria = r.id_categoria
                           JOIN usuarios u ON u.id_usuario = r.id_usuario
                           ORDER BY r.fecha_creacion DESC, r.id_receta DESC
                           LIMIT 3');
    $stmt->execute();
    $ultimas = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log($e->getMessage()); // El error real solo se guarda en el log
    $error = 'No se han podido cargar las últimas recetas. Inténtalo más tarde.';
}

// Variables que usan header.php (pestaña) y nav.php (enlace activo).
$titulo = 'Inicio';
$paginaActual = 'inicio';
require_once __DIR__ . '/header.php';
?>

<!-- 1. Bienvenida -->
<section class="mb-5">
    <h1>Bienvenido al Recetario</h1>
    <p class="lead">
        Un recetario colaborativo donde cada persona puede publicar sus recetas,
        buscar por categoría o ingrediente y ajustar las cantidades a las raciones que necesite.
    </p>

    <!-- 2. Accesos rápidos: si no hay sesión, se invita a registrarse -->
    <a href="listado_recetas.php" class="btn btn-primary me-2 mb-2">Ver recetas</a>
    <?php if (isset($_SESSION['id_usuario'])): ?>
        <a href="formulario_receta.php" class="btn btn-outline-primary mb-2">Nueva receta</a>
    <?php else: ?>
        <a href="registro.php" class="btn btn-outline-primary me-2 mb-2">Crear una cuenta</a>
        <a href="login.php" class="btn btn-outline-primary mb-2">Ya tengo cuenta</a>
    <?php endif; ?>
</section>

<!-- gif -->
<section class="mb-5">
    <h2>Cocinar paso a paso</h2>
    <figure class="animacion-pasos">
        <img src="../docs/img/pasos-receta.webp" class="img-fluid" width="480" height="320" loading="lazy"
            alt="Animación de los pasos de una receta: 1, preparar los ingredientes; 2, cortarlos; 3, cocinarlos en la sartén; 4, servir el plato.">
        <figcaption>Preparar, cortar, cocinar y servir: así de fácil es seguir una receta del Recetario.</figcaption>
    </figure>
</section>

<!-- 4. Últimas recetas publicadas -->
<section>
    <h2>Últimas recetas</h2>

    <?php if ($error): ?>
        <p><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php elseif (count($ultimas) === 0): ?>
        <p>Todavía no hay recetas. <a href="formulario_receta.php">Publica la primera</a>.</p>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($ultimas as $receta): ?>
                <div class="col-12 col-md-6 col-lg-4">
                    <article class="card h-100">
                        <?php if ($receta['imagen']): ?>
                            <img src="<?= htmlspecialchars($receta['imagen'], ENT_QUOTES, 'UTF-8') ?>" class="card-img-top"
                                alt="<?= htmlspecialchars($receta['titulo'], ENT_QUOTES, 'UTF-8') ?>">
                        <?php endif; ?>
                        <div class="card-body">
                            <h3 class="card-title h5">
                                <a href="receta.php?id=<?= (int) $receta['id_receta'] ?>">
                                    <?= htmlspecialchars($receta['titulo'], ENT_QUOTES, 'UTF-8') ?>
                                </a>
                            </h3>
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
</section>

<?php require_once __DIR__ . '/footer.php'; ?>