<?php
session_start();

// Un usuario con sesión iniciada no necesita registrarse.
if (isset($_SESSION['id_usuario'])) {
    header('Location: listado_recetas.php');
    exit;
}

require_once __DIR__ . '/conexion.php';
/** @var PDO $pdo */

$errores = [];
$nombreUsuario = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Recoger los datos del formulario.
    $nombreUsuario = trim($_POST['nombre_usuario'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $emailValido = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $contrasena = $_POST['contrasena'] ?? '';
    $repetir = $_POST['repetir_contrasena'] ?? '';

    // 2. Validar en el servidor (aunque el HTML ya tenga required).
    if (strlen($nombreUsuario) < 3 || strlen($nombreUsuario) > 50) {
        $errores[] = 'El nombre de usuario debe tener entre 3 y 50 caracteres.';
    }
    if (!$emailValido || strlen($email) > 100) {
        $errores[] = 'Escribe un email válido (máximo 100 caracteres).';
    }
    if (strlen($contrasena) < 8) {
        $errores[] = 'La contraseña debe tener al menos 8 caracteres.';
    }
    if ($contrasena !== $repetir) {
        $errores[] = 'Las dos contraseñas no coinciden.';
    }

    // 3. Comprobar que el nombre de usuario y el email no están ya registrados.
    if (empty($errores)) {
        $stmt = $pdo->prepare('SELECT nombre_usuario, email FROM usuarios WHERE nombre_usuario = ? OR email = ?');
        $stmt->execute([$nombreUsuario, $emailValido]);
        foreach ($stmt->fetchAll() as $existente) {
            if ($existente['nombre_usuario'] === $nombreUsuario) {
                $errores[] = 'Ese nombre de usuario ya está en uso.';
            }
            if ($existente['email'] === $emailValido) {
                $errores[] = 'Ya hay una cuenta con ese email.';
            }
        }
    }

    // 4. Guardar el usuario. La contraseña se guarda cifrada con password_hash, nunca en claro.
    if (empty($errores)) {
        try {
            $stmt = $pdo->prepare('INSERT INTO usuarios (nombre_usuario, email, contrasena) VALUES (:nombre, :email, :contrasena)');
            $stmt->execute([
                ':nombre' => $nombreUsuario,
                ':email' => $emailValido,
                ':contrasena' => password_hash($contrasena, PASSWORD_DEFAULT),
            ]);
            $_SESSION['mensaje'] = ['tipo' => 'exito', 'texto' => 'Cuenta creada. Ya puedes iniciar sesión.'];
            header('Location: login.php');
            exit;
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $errores[] = 'No se ha podido crear la cuenta. Inténtalo de nuevo.';
        }
    }
}

// Título de la pestaña y enlace activo del menú.
$titulo = 'Registrarse';
$paginaActual = 'registro';
require_once __DIR__ . '/header.php';
?>
<h1>Crear una cuenta</h1>

<?php if (!empty($errores)): ?>
    <ul class="alert alert-danger" role="alert">
        <?php foreach ($errores as $error): ?>
            <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<form method="post" action="registro.php" class="formulario-sesion">
    <div class="mb-3">
        <label for="nombre_usuario" class="form-label">Nombre de usuario</label>
        <input type="text" id="nombre_usuario" name="nombre_usuario" class="form-control" minlength="3" maxlength="50"
            required autocomplete="username" value="<?= htmlspecialchars($nombreUsuario, ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="mb-3">
        <label for="email" class="form-label">Email</label>
        <input type="email" id="email" name="email" class="form-control" maxlength="100" required autocomplete="email"
            value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="mb-3">
        <label for="contrasena" class="form-label">Contraseña (mínimo 8 caracteres)</label>
        <input type="password" id="contrasena" name="contrasena" class="form-control" minlength="8" required
            autocomplete="new-password">
    </div>
    <div class="mb-3">
        <label for="repetir_contrasena" class="form-label">Repite la contraseña</label>
        <input type="password" id="repetir_contrasena" name="repetir_contrasena" class="form-control" minlength="8"
            required autocomplete="new-password">
    </div>
    <button type="submit" class="btn btn-danger">Registrarme</button>
</form>

<p class="mt-3">¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a></p>

<?php require_once __DIR__ . '/footer.php'; ?>