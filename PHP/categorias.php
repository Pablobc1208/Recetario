<?php

require_once __DIR__ . '/conexion.php';
/** @var PDO $pdo */

$categorias = [];
$error = '';

try {
    $stmt = $pdo->prepare('SELECT id_categoria, nombre FROM categorias ORDER BY nombre'); //consulta ordenada alfabeticamente
    $stmt->execute();
    $categorias = $stmt->fetchAll(); //todas las filas en un array
} catch (PDOException $e) {
    error_log($e->getMessage()); //el detalle tecnico solo va al log del servidor
    $error = 'No se han podido cargar las categorías. Inténtalo más tarde.';
}

//titulo de la pestaña y enlace activo del menu
$titulo = 'Categorías';
$paginaActual = 'categorias';
require_once __DIR__ . '/header.php';
?>
<h1>Categorías</h1>

<p><a href="categoria_nueva.php">Añadir categoría</a></p>

<?php if ($error): ?>
    <p class="alert alert-danger" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
<?php elseif (count($categorias) === 0): //lista vacia 
?>
    <p>Todavía no hay categorías.</p>
<?php else: ?>
    <ul>
        <?php foreach ($categorias as $fila): ?>
            <li>
                <a href="categoria_recetas.php?id=<?= (int) $fila['id_categoria'] //pasa el id por GET 
                                                    ?>">
                    <?= htmlspecialchars($fila['nombre'], ENT_QUOTES, 'UTF-8') ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<?php require_once __DIR__ . '/footer.php'; ?>