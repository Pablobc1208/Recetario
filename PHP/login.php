<?php
session_start();

// Si ya hay sesión iniciada no tiene sentido volver a entrar.
if (isset($_SESSION['id_usuario'])) {
    header('Location: listado_recetas.php');
    exit;
}

require_once __DIR__ . '/conexion.php';
/** @var PDO $pdo */

$errores = [];
$nombreUsuario = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombreUsuario = trim($_POST['nombre_usuario'] ?? '');
    $contrasena = $_POST['contrasena'] ?? ''; // la contraseña no se recorta: los espacios cuentan

    if ($nombreUsuario === '' || $contrasena === '') {
        $errores[] = 'Escribe tu nombre de usuario y tu contraseña.';
    }

    if (empty($errores)) {
        // Busca al usuario con una consulta preparada.
        $stmt = $pdo->prepare('SELECT id_usuario, nombre_usuario, contrasena FROM usuarios WHERE nombre_usuario = ?');
        $stmt->execute([$nombreUsuario]);
        $usuario = $stmt->fetch();

        // password_verify compara la contraseña escrita con el hash guardado.
        if ($usuario !== false && password_verify($contrasena, $usuario['contrasena'])) {
            session_regenerate_id(true); // nuevo id de sesión al entrar
            $_SESSION['id_usuario'] = $usuario['id_usuario'];
            $_SESSION['usuario'] = $usuario['nombre_usuario'];
            $_SESSION['mensaje'] = ['tipo' => 'exito', 'texto' => 'Hola, ' . $usuario['nombre_usuario'] . '. Has iniciado sesión.'];
            header('Location: listado_recetas.php');
            exit;
        }

        // Mensaje genérico: no se dice si falla el usuario o la contraseña.
        $errores[] = 'Usuario o contraseña incorrectos.';
    }
}

// Título de la pestaña y enlace activo del menú.
$titulo = 'Iniciar sesión';
$paginaActual = 'login';
require_once __DIR__ . '/header.php';
?>
<h1>Iniciar sesión</h1>

<?php foreach ($errores as $error): ?>
    <p class="alert alert-danger" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
<?php endforeach; ?>

<form method="post" action="login.php" class="formulario-sesion">
    <div class="mb-3">
        <label for="nombre_usuario" class="form-label">Nombre de usuario</label>
        <input type="text" id="nombre_usuario" name="nombre_usuario" class="form-control" maxlength="50" required
            autocomplete="username" value="<?= htmlspecialchars($nombreUsuario, ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="mb-3">
        <label for="contrasena" class="form-label">Contraseña</label>
        <input type="password" id="contrasena" name="contrasena" class="form-control" required
            autocomplete="current-password">
    </div>
    <button type="submit" class="btn btn-danger">Entrar</button>
</form>

<p class="mt-3">¿No tienes cuenta? <a href="registro.php">Regístrate</a></p>

<?php require_once __DIR__ . '/footer.php'; ?>