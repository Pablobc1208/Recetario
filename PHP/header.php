<?php
// Cabecera común de todas las páginas.
// Antes de incluirla, cada página puede definir $titulo y $paginaActual.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$titulo = $titulo ?? 'Recetario';
$paginaActual = $paginaActual ?? '';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($titulo) ?> | Recetario</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Lora:wght@600;700&family=Open+Sans:wght@400;600&display=swap">
    <!-- Estilos del gestor de cookies (Vanilla Cookie Consent) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/vanilla-cookieconsent@3.1.0/dist/cookieconsent.css">
    <link rel="stylesheet" href="../CSS/style.css">
</head>

<body>
    <header class="cabecera">
        <?php include __DIR__ . '/nav.php'; ?>
    </header>
    <main class="container contenido">
        <?php include __DIR__ . '/mensajes.php'; ?>