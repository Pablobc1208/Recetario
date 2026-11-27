<?php
// Menú de navegación. Marca como activa la página indicada en $paginaActual.
$enlaces = [
    'inicio' => ['url' => 'index.php', 'texto' => 'Inicio'],
    'recetas' => ['url' => 'listado_recetas.php', 'texto' => 'Recetas'],
    'nueva' => ['url' => 'formulario_receta.php', 'texto' => 'Nueva receta'],
];
?>
<nav class="navbar navbar-expand-md navegacion">
    <div class="container">
        <a class="navbar-brand logo" href="index.php">Recetario</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuPrincipal"
            aria-controls="menuPrincipal" aria-expanded="false" aria-label="Abrir menú">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="menuPrincipal">
            <ul class="navbar-nav ms-auto">
                <?php foreach ($enlaces as $clave => $enlace): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $clave === $paginaActual ? 'active' : '' ?>" <?= $clave === $paginaActual ? 'aria-current="page"' : '' ?> href="<?= $enlace['url'] ?>"><?= $enlace['texto'] ?></a>
                    </li>
                <?php endforeach; ?>
                <?php if (isset($_SESSION['usuario'])): ?>
                    <!-- «Mis recetas» solo aparece con sesión iniciada (#48) -->
                    <li class="nav-item">
                        <a class="nav-link <?= $paginaActual === 'mis_recetas' ? 'active' : '' ?>"
                            <?= $paginaActual === 'mis_recetas' ? 'aria-current="page"' : '' ?>
                            href="mis_recetas.php">Mis recetas</a>
                    </li>
                    <li class="nav-item"><span class="nav-link">Hola,
                            <?= htmlspecialchars($_SESSION['usuario'], ENT_QUOTES, 'UTF-8') ?></span></li>
                    <li class="nav-item"><a class="nav-link" href="logout.php">Cerrar sesión</a></li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $paginaActual === 'login' ? 'active' : '' ?>" <?= $paginaActual === 'login' ? 'aria-current="page"' : '' ?> href="login.php">Iniciar sesión</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $paginaActual === 'registro' ? 'active' : '' ?>"
                            <?= $paginaActual === 'registro' ? 'aria-current="page"' : '' ?>
                            href="registro.php">Registrarse</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
