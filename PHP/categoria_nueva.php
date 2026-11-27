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

    //is_string comprueba que llega texto si se manda por ej un array se trata como vacio
    $nombre = is_string($_POST['nombre'] ?? null) ? trim($_POST['nombre']) : ''; //?? evita el error si falta y trim quita los espacios

    //validacion en el servidor
    if ($nombre === '') {
        $errores[] = 'El nombre es obligatorio.';
    } elseif (strlen($nombre) > 50) {
        $errores[] = 'El nombre no puede tener más de 50 caracteres.';
    }

    if (empty($errores)) {
        try {
            //evitar los duplicados
            $stmt = $pdo->prepare('SELECT id_categoria FROM categorias WHERE nombre = ?');
            $stmt->execute([$nombre]);
            if ($stmt->rowCount() > 0) {
                $errores[] = 'Esa categoría ya existe.';
            } else {
                //inserta, guarda el mensaje de confirmacion y vuelve al listado
                $stmt = $pdo->prepare('INSERT INTO categorias (nombre) VALUES (?)');
                $stmt->execute([$nombre]);
                $_SESSION['mensaje'] = ['tipo' => 'exito', 'texto' => "Categoría «{$nombre}» creada correctamente."];
                header('Location: categorias.php');
                exit;
            }
        } catch (PDOException $e) {
            error_log($e->getMessage()); //el detalle tecnico solo va al log del servidor
            $errores[] = 'No se ha podido guardar la categoría. Inténtalo de nuevo.';
        }
    }
}

//titulo de la pestaña y enlace activo del menu
$titulo = 'Nueva categoría';
$paginaActual = 'categorias';
require_once __DIR__ . '/header.php';
?>
<h1>Nueva categoría</h1>

<?php foreach ($errores as $error): ?>
    <p class="alert alert-danger" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
<?php endforeach; ?>

<form method="post" action="categoria_nueva.php">
    <label for="nombre">Nombre</label>
    <input type="text" id="nombre" name="nombre" maxlength="50" required value="<?= htmlspecialchars($nombre) ?>">
    <button type="submit">Guardar</button>
</form>

<p><a href="categorias.php">Volver al listado</a></p>

<?php require_once __DIR__ . '/footer.php'; ?>