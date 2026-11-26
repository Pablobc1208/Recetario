<?php

require_once __DIR__ . '/conexion.php';
/** @var PDO $pdo */

$stmt = $pdo->prepare('SELECT id_categoria, nombre FROM categorias ORDER BY nombre'); //consulta ordenada alfabeticamente
$stmt->execute();

require_once __DIR__ . '/header.php';
?>
<h1>Categorías</h1>

<p><a href="categoria_nueva.php">Añadir categoría</a></p>

<?php if ($stmt->rowCount() === 0): //lista vacia 
?>
    <p>Todavía no hay categorías.</p>
<?php else: ?>
    <ul>
        <?php while ($fila = $stmt->fetch()): //una fila por vuelta y false al acabar 
        ?>
            <li>
                <a href="categoria_recetas.php?id=<?= htmlspecialchars($fila['id_categoria']) //pasa el id por GET 
                                                    ?>">
                    <?= htmlspecialchars($fila['nombre']) ?>
                </a>
            </li>
        <?php endwhile; ?>
    </ul>
<?php endif; ?>

<?php require_once __DIR__ . '/footer.php'; ?>