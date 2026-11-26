<?php

require_once __DIR__ . '/conexion.php';
/** @var PDO $pdo */

$id = $_GET['id'] ?? ''; //ID de la categoria que llega por URL

//busca la categoría
$stmt = $pdo->prepare('SELECT nombre FROM categorias WHERE id_categoria = ?');
$stmt->execute([$id]);
$categoria = $stmt->fetch(); //false si la categoria no existe

//busca las recetas de esa categoria
if ($categoria) {
    $stmt = $pdo->prepare('SELECT id_receta, titulo FROM recetas WHERE id_categoria = ? ORDER BY titulo');
    $stmt->execute([$id]);
}

require_once __DIR__ . '/header.php';
?>
<?php if (!$categoria): ?>
    <h1>Categoría no encontrada</h1>
<?php else: ?>
    <h1>Recetas de <?= htmlspecialchars($categoria['nombre']) ?></h1>

    <?php if ($stmt->rowCount() === 0): //lista vacia 
    ?>
        <p>Esta categoría todavía no tiene recetas.</p>
    <?php else: ?>
        <ul>
            <?php while ($fila = $stmt->fetch()): //una fila por vuelta y false al acabar 
            ?>
                <li>
                    <a href="receta.php?id=<?= htmlspecialchars($fila['id_receta']) ?>">
                        <?= htmlspecialchars($fila['titulo']) ?>
                    </a>
                </li>
            <?php endwhile; ?>
        </ul>
    <?php endif; ?>
<?php endif; ?>

<p><a href="categorias.php">Volver a categorías</a></p>

<?php require_once __DIR__ . '/footer.php'; ?>