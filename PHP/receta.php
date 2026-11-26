<?php
require_once __DIR__ . '/conexion.php';

$receta = false;
$ingredientes = [];
$error = '';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    $error = 'El enlace no es válido.';
} else {
    try {
        // 2. Buscar la receta con su categoría
        $stmt = $pdo->prepare('SELECT r.titulo, r.descripcion, r.instrucciones, r.tiempo_minutos, r.imagen,
                                      c.nombre AS categoria
                               FROM recetas r
                               JOIN categorias c ON c.id_categoria = r.id_categoria
                               WHERE r.id_receta = :id');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $receta = $stmt->fetch();

        if (!$receta) {
            //La receta no existe
            http_response_code(404);
            $error = 'Esta receta no existe.';
        } else {
            //Buscar sus ingredientes a través de la tabla intermedia
            $stmt = $pdo->prepare('SELECT i.nombre, ri.cantidad, ri.unidad
                                   FROM receta_ingrediente ri
                                   JOIN ingredientes i ON i.id_ingrediente = ri.id_ingrediente
                                   WHERE ri.id_receta = :id');
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $ingredientes = $stmt->fetchAll();
        }
    } catch (PDOException $e) {
        error_log($e->getMessage());
        $error = 'No se ha podido cargar la receta. Inténtalo más tarde.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recetario</title>
    <link rel="stylesheet" href="../CSS/style.css">
</head>
<body>
    <main>
        <?php if ($error): ?>
            <h1>Receta no disponible</h1>
            <p><?= $error ?></p>
        <?php else: ?>
            <!-- 3. Mostrar la receta -->
            <h1><?= htmlspecialchars($receta['titulo']) ?></h1>
            <p><?= htmlspecialchars($receta['descripcion']) ?></p>
            <p>Categoría: <?= htmlspecialchars($receta['categoria']) ?></p>
            <p>Tiempo: <?= (int) $receta['tiempo_minutos'] ?> minutos</p>

            <?php if ($receta['imagen']): ?>
                <img src="<?= htmlspecialchars($receta['imagen']) ?>" alt="<?= htmlspecialchars($receta['titulo']) ?>">
            <?php endif; ?>

            <h2>Ingredientes</h2>
            <ul>
                <?php foreach ($ingredientes as $ingrediente): ?>
                    <li>
                        <?= (float) $ingrediente['cantidad'] ?>
                        <?= htmlspecialchars($ingrediente['unidad']) ?>
                        de <?= htmlspecialchars($ingrediente['nombre']) ?>
                    </li>
                <?php endforeach; ?>
            </ul>

            <h2>Elaboración</h2>
            <p><?= nl2br(htmlspecialchars($receta['instrucciones'])) ?></p>
        <?php endif; ?>

        <a href="index.php">Volver al listado</a>
    </main>
</body>
</html>