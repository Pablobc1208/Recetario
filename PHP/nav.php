<?php
// Menú de navegación. Marca como activa la página indicada en $paginaActual.
$enlaces = [
    'inicio'  => ['url' => 'index.php',        'texto' => 'Inicio'],
    'recetas' => ['url' => 'recetas.php',      'texto' => 'Recetas'],
    'nueva'   => ['url' => 'nueva_receta.php', 'texto' => 'Nueva receta'],
];
?>
<nav class="navbar navbar-expand-md navegacion">
    <div class="container">
        <a class="navbar-brand logo" href="index.php">Recetario</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#menuPrincipal" aria-controls="menuPrincipal"
                aria-expanded="false" aria-label="Abrir menú">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="menuPrincipal">
            <ul class="navbar-nav ms-auto">
                <?php foreach ($enlaces as $clave => $enlace): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $clave === $paginaActual ? 'active' : '' ?>"
                           <?= $clave === $paginaActual ? 'aria-current="page"' : '' ?>
                           href="<?= $enlace['url'] ?>"><?= $enlace['texto'] ?></a>
                    </li>
                <?php endforeach; ?>
                <?php if (isset($_SESSION['usuario'])): ?>
                    <li class="nav-item"><a class="nav-link" href="logout.php">Cerrar sesión</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="login.php">Iniciar sesión</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
