<?php
require_once __DIR__ . '/conexion.php';
/** @var PDO $pdo */

session_start(); //arranca la sesion para leer $_SESSION
if (!isset($_SESSION['id_usuario'])) {
    header('Location: login.php');
    exit;
}

$errores = [];
$nombre = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') { //solo al enviar el formulario
    $nombre = trim($_POST['nombre'] ?? ''); //trim quita los espacios y ?? evita el error si falta

    //validacion en el servidor
    if ($nombre === '') {
        $errores[] = 'El nombre es obligatorio.';
    } elseif (strlen($nombre) > 50) {
        $errores[] = 'El nombre no puede tener más de 50 caracteres.';
    }

    //evitar los duplicados
    if (empty($errores)) {
        $stmt = $pdo->prepare('SELECT id_categoria FROM categorias WHERE nombre = ?');
        $stmt->execute([$nombre]);
        if ($stmt->rowCount() > 0) {
            $errores[] = 'Esa categoría ya existe.';
        }
    }

    //inserta y vuelve al listado
    if (empty($errores)) {
        $stmt = $pdo->prepare('INSERT INTO categorias (nombre) VALUES (?)');
        $stmt->execute([$nombre]);
        header('Location: categorias.php');
        exit;
    }
}

require_once __DIR__ . '/header.php';
?>
<h1>Nueva categoría</h1>

<?php foreach ($errores as $error): ?>
    <p class="error"><?= htmlspecialchars($error) ?></p>
<?php endforeach; ?>

<form method="post" action="categoria_nueva.php">
    <label for="nombre">Nombre</label>
    <input type="text" id="nombre" name="nombre" maxlength="50" required
        value="<?= htmlspecialchars($nombre) ?>">
    <button type="submit">Guardar</button>
</form>

<p><a href="categorias.php">Volver al listado</a></p>

<?php require_once __DIR__ . '/footer.php'; ?>