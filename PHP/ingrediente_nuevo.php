<?php
require_once __DIR__ . '/conexion.php';
/** @var PDO $pdo */

//solo con sesion iniciada
session_start();
if (!isset($_SESSION['id_usuario'])) { //si no hay sesion iniciada redirecciona a login
    header('Location: login.php');
    exit;
}

$errores = []; //array para guardar errores
$nombre = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') { //solo al enviar el formulario
    $nombre = trim($_POST['nombre'] ?? ''); //trim quita los espacios y ?? evita el error si falta

    //validacion en el servidor
    if ($nombre === '') {
        $errores[] = 'El nombre es obligatorio.';
    } elseif (strlen($nombre) > 100) {
        $errores[] = 'El nombre no puede tener más de 100 caracteres.';
    }

    //evita los duplicados
    if (empty($errores)) {
        $stmt = $pdo->prepare('SELECT id_ingrediente FROM ingredientes WHERE nombre = ?');
        $stmt->execute([$nombre]);
        if ($stmt->rowCount() > 0) {
            $errores[] = 'Ese ingrediente ya existe.';
        }
    }

    //inserta y vuelve al listado
    if (empty($errores)) {
        $stmt = $pdo->prepare('INSERT INTO ingredientes (nombre) VALUES (?)');
        $stmt->execute([$nombre]);
        header('Location: ingredientes.php');
        exit;
    }
}

require_once __DIR__ . '/header.php';
?>
<h1>Nuevo ingrediente</h1>

<?php foreach ($errores as $error): ?>
    <p class="error"><?= htmlspecialchars($error) ?></p>
<?php endforeach; ?>

<form method="post" action="ingrediente_nuevo.php">
    <label for="nombre">Nombre</label>
    <input type="text" id="nombre" name="nombre" maxlength="100" required
        value="<?= htmlspecialchars($nombre) ?>">
    <button type="submit">Guardar</button>
</form>

<p><a href="ingredientes.php">Volver al listado</a></p>

<?php require_once __DIR__ . '/footer.php'; ?>