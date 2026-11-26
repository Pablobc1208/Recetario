<?php

require_once __DIR__ . '/conexion.php';
/** @var PDO $pdo */

$ingredientes = [];
$error = '';

try {
    $stmt = $pdo->prepare('SELECT id_ingrediente, nombre FROM ingredientes ORDER BY nombre'); //consulta ordenada alfabeticamente
    $stmt->execute();
    $ingredientes = $stmt->fetchAll(); //todas las filas en un array
} catch (PDOException $e) {
    error_log($e->getMessage()); //el detalle tecnico solo va al log del servidor
    $error = 'No se han podido cargar los ingredientes. Inténtalo más tarde.';
}

//titulo de la pestaña y enlace activo del menu
$titulo = 'Ingredientes';
$paginaActual = 'ingredientes';
require_once __DIR__ . '/header.php';
?>
<h1>Ingredientes</h1>

<p><a href="ingrediente_nuevo.php">Añadir ingrediente</a></p>

<?php if ($error): ?>
    <p class="alert alert-danger" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
<?php elseif (count($ingredientes) === 0): //lista vacia 
?>
    <p>Todavía no hay ingredientes.</p>
<?php else: ?>
    <ul>
        <?php foreach ($ingredientes as $fila): ?>
            <li><?= htmlspecialchars($fila['nombre'], ENT_QUOTES, 'UTF-8') ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<?php require_once __DIR__ . '/footer.php'; ?>