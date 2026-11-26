<?php
session_start();

// Vacía los datos de la sesión y la destruye.
$_SESSION = [];
session_destroy();

// Borra también la cookie de sesión del navegador.
setcookie(session_name(), '', time() - 3600, '/');

header('Location: login.php');
exit;
