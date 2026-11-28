<?php
 
require_once __DIR__ . '/conexion.php';
/** @var PDO $pdo */
 
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT); //ID de la categoria que llega por URL; false o null si no es un número
 
$categoria = false;
$recetas = [];
$error = '';
 
if (!$id || $id < 1) {
    //el id no es un numero entero positivo
    http_response_code(400);
    $error = 'El enlace no es válido.';
} else {
    try {
        //busca la categoria
        $stmt = $pdo->prepare('SELECT nombre FROM categorias WHERE id_categoria = ?');
        $stmt->execute([$id]);
        $categoria = $stmt->fetch(); //false si la categoria no existe
 
        if ($categoria === false) {
            http_response_code(404);
            $error = 'Esta categoría no existe.';
        } else {
            //busca las recetas de esa categoria
            $stmt = $pdo->prepare('SELECT id_receta, titulo FROM recetas WHERE id_categoria = ? ORDER BY titulo');
            $stmt->execute([$id]);
            $recetas = $stmt->fetchAll();
        }
    } catch (PDOException $e) {
        error_log($e->getMessage()); //el detalle tecnico solo va al log del servidor
        $categoria = false;
        $error = 'No se ha podido cargar la categoría. Inténtalo más tarde.';
    }
}
 
//titulo de la pestaña y enlace activo del menu
$titulo = $categoria ? 'Recetas de ' . $categoria['nombre'] : 'Categoría no encontrada';
$paginaActual = 'categorias';
require_once __DIR__ . '/header.php';
?>
<?php if ($error): ?>
    <h1>Categoría no encontrada</h1>
    <p class="alert alert-danger" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
<?php else: ?>
    <h1>Recetas de <?= htmlspecialchars($categoria['nombre'], ENT_QUOTES, 'UTF-8') ?></h1>
 
    <?php if (count($recetas) === 0): //lista vacia 
    ?>
        <p>Esta categoría todavía no tiene recetas.</p>
    <?php else: ?>
        <ul>
            <?php foreach ($recetas as $fila): ?>
                <li>
                    <a href="receta.php?id=<?= (int) $fila['id_receta'] ?>">
                        <?= htmlspecialchars($fila['titulo'], ENT_QUOTES, 'UTF-8') ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
<?php endif; ?>
 
<p><a href="categorias.php" class="btn btn-outline-primary">Volver a categorías</a></p>
 
<?php require_once __DIR__ . '/footer.php'; ?>