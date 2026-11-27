<?php
// Solo recibe datos por POST desde el botón «Eliminar receta» de receta.php y mis_recetas.php.
session_start();

require_once __DIR__ . '/conexion.php';

// 1. Solo con sesión iniciada (#15): si no hay usuario, al login.
if (!isset($_SESSION['id_usuario'])) {
    header('Location: login.php');
    exit;
}
// 2. Solo por POST: si alguien escribe la URL a mano (GET), no se borra nada.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: listado_recetas.php');
    exit;
}

// Página a la que volver: «Mis recetas» si se borró desde allí (#48); si no, el listado.
// Solo se aceptan estos dos valores para no redirigir a cualquier dirección.
$destino = ($_POST['volver'] ?? '') === 'mis_recetas' ? 'mis_recetas.php' : 'listado_recetas.php';

// 3. Recoger el id de la receta y validarlo.
$id = filter_input(INPUT_POST, 'id_receta', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    $_SESSION['mensaje'] = ['tipo' => 'error', 'texto' => 'No se ha indicado una receta válida.'];
    header('Location: ' . $destino);
    exit;
}
try {
    // 4. Borrar solo si la receta es del usuario conectado.
    // El WHERE comprueba a la vez el id de la receta y su autor.
    // Sus filas de receta_ingrediente se borran solas por el ON DELETE CASCADE.
    $stmt = $pdo->prepare('DELETE FROM recetas
                           WHERE id_receta = :id AND id_usuario = :id_usuario');
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->bindValue(':id_usuario', $_SESSION['id_usuario'], PDO::PARAM_INT);
    $stmt->execute();

    // 5. rowCount() dice cuántas filas se han borrado: 1 si ha ido bien, 0 si no.
    if ($stmt->rowCount() > 0) {
        $_SESSION['mensaje'] = ['tipo' => 'exito', 'texto' => 'La receta se ha eliminado correctamente.'];
    } else {
        $_SESSION['mensaje'] = ['tipo' => 'error', 'texto' => 'No se ha podido eliminar: la receta no existe o no eres su autor.'];
    }
} catch (PDOException $e) {
    error_log($e->getMessage()); // El error real solo se guarda en el log
    $_SESSION['mensaje'] = ['tipo' => 'error', 'texto' => 'No se ha podido eliminar la receta. Inténtalo más tarde.'];
}

// 6. Redirigir a la página de origen, donde mensajes.php muestra el aviso.
header('Location: ' . $destino);
exit;
