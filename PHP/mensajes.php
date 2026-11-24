<?php
// Zona común de mensajes de confirmación y error.
// Para mostrar un mensaje en la siguiente página, antes de redirigir haz:
//   $_SESSION['mensaje'] = ['tipo' => 'exito', 'texto' => 'Receta guardada'];
//   header('Location: recetas.php'); exit;
// Tipos: 'exito' o 'error'.
if (!empty($_SESSION['mensaje'])):
    $mensaje = $_SESSION['mensaje'];
    unset($_SESSION['mensaje']); // se muestra una sola vez
    $clase = $mensaje['tipo'] === 'error' ? 'alert-danger' : 'alert-success';
?>
    <div class="alert <?= $clase ?> alert-dismissible fade show mensaje" role="alert">
        <?= htmlspecialchars($mensaje['texto']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
    </div>
<?php endif; ?>
