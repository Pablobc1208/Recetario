<?php

require_once __DIR__ . '/conexion.php';
/** @var PDO $pdo */

$stmt = $pdo->prepare('SELECT id_ingrediente, nombre FROM ingredientes ORDER BY nombre'); //consulta ordenada alfabeticamente
$stmt->execute();

require_once __DIR__ . '/header.php';
?>
<h1>Ingredientes</h1>

<p><a href="ingrediente_nuevo.php">Añadir ingrediente</a></p>

<?php if ($stmt->rowCount() === 0): //lista vacia 
?>
    <p>Todavía no hay ingredientes.</p>
<?php else: ?>
    <ul>
        <?php while ($fila = $stmt->fetch()): //una fila por vuelta y false al acabar 
        ?>
            <li><?= htmlspecialchars($fila['nombre']) ?></li>
        <?php endwhile; ?>
    </ul>
<?php endif; ?>

<?php require_once __DIR__ . '/footer.php'; ?>