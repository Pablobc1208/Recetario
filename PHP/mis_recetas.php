<?php
// Recetas del usuario con sesión iniciada (issue #48).
session_start();
require_once __DIR__ . '/conexion.php';

// 1. Solo con sesión iniciada: si no hay usuario, al login.
if (!isset($_SESSION['id_usuario'])) {
    header('Location: login.php');
    exit;
}

$recetas = [];
$error = '';
try {
    // 2. Recuperar solo las recetas del usuario, con su categoría y su tiempo.
    $stmt = $pdo->prepare('SELECT r.id_receta, r.titulo, r.descripcion, r.tiempo_minutos, r.imagen,
                                  c.nombre AS categoria
                           FROM recetas r
                           JOIN categorias c ON c.id_categoria = r.id_categoria
                           WHERE r.id_usuario = :id
                           ORDER BY r.fecha_creacion DESC, r.id_receta DESC');
    $stmt->bindValue(':id', $_SESSION['id_usuario'], PDO::PARAM_INT);
    $stmt->execute();
    $recetas = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log($e->getMessage()); // El error real solo se guarda en el log
    $error = 'No se han podido cargar tus recetas. Inténtalo más tarde.';
}
// Variables que usan header.php (pestaña) y nav.php (enlace activo).
$titulo = 'Mis recetas';
$paginaActual = 'mis_recetas';
require_once __DIR__ . '/header.php';
?>
<h1>Mis recetas</h1>

<?php if ($error): ?>
    <p><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
<?php elseif (count($recetas) === 0): ?>
    <!-- 4. Mensaje cuando el usuario todavía no tiene recetas -->
    <p>Todavía no has publicado ninguna receta.</p>
    <a href="formulario_receta.php" class="btn btn-primary">Crea la primera</a>
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
                    </ul>
                    <div class="card-body">
                        <!-- Editar va por GET (solo muestra el formulario); eliminar va por POST -->
                        <a href="editar_receta.php?id=<?= (int) $receta['id_receta'] ?>&amp;volver=mis_recetas" class="btn btn-primary">Editar</a>
                        <form class="form-eliminar" action="eliminar_receta.php" method="POST">
                            <input type="hidden" name="id_receta" value="<?= (int) $receta['id_receta'] ?>">
                            <!-- Indica a eliminar_receta.php que vuelva a esta página -->
                            <input type="hidden" name="volver" value="mis_recetas">
                            <button type="submit" class="btn btn-outline-primary">Eliminar</button>
                        </form>
                    </div>
                </article>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Confirmación antes de eliminar (JavaScript separado del PHP) -->
<script src="../JS/eliminar.js"></script>

<?php require_once __DIR__ . '/footer.php'; ?>