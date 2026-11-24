<?php
require_once __DIR__ . '/auth.php';
$tituloPagina = $tituloPagina ?? 'FleteTonelada';
$rolActual = $_SESSION['usuario']['rol'] ?? '';
$paneles = [
    'cliente' => RUTA_BASE . '/cliente/panel.php',
    'transportista' => RUTA_BASE . '/transportista/panel.php',
    'admin' => RUTA_BASE . '/admin/panel.php',
];
$mensajesVista = obtenerMensajes();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="FleteTonelada conecta empresas con transportistas independientes para mover carga pesada.">
    <title><?= e($tituloPagina) ?></title>
    <link rel="stylesheet" href="<?= RUTA_BASE ?>/assets/css/base.css">
</head>
<body>
<a class="skip-link" href="#contenido">Saltar al contenido</a>
<header class="site-header">
    <div class="container header-inner">
        <a class="brand" href="<?= RUTA_BASE ?>/" aria-label="FleteTonelada, inicio">
            <span class="brand-mark">FT</span>
            <span class="brand-copy">Flete<span>Tonelada</span><small>TRANSPORTE DE CARGA</small></span>
        </a>
        <button class="menu-toggle" id="menu-toggle" type="button" aria-label="Abrir menú" aria-expanded="false" aria-controls="menu-principal">
            <span></span><span></span><span></span>
        </button>
        <nav class="main-nav" id="menu-principal" aria-label="Navegación principal">
            <a href="<?= RUTA_BASE ?>/">Inicio</a>
            <a href="<?= RUTA_BASE ?>/#como-funciona">Cómo funciona</a>
            <a href="<?= RUTA_BASE ?>/#cotizador">Cotizador</a>
            <?php if (usuarioAutenticado()): ?>
                <?php if (isset($paneles[$rolActual])): ?><a href="<?= e($paneles[$rolActual]) ?>">Mi panel</a><?php endif; ?>
                <?php if ($rolActual === 'cliente'): ?><a href="<?= RUTA_BASE ?>/cliente/mis-cargas.php">Mis cargas</a><?php elseif ($rolActual === 'transportista'): ?><a href="<?= RUTA_BASE ?>/transportista/disponibles.php">Cargas disponibles</a><?php elseif ($rolActual === 'admin'): ?><a href="<?= RUTA_BASE ?>/admin/usuarios.php">Usuarios</a><?php endif; ?>
                <a href="<?= RUTA_BASE ?>/perfil.php">Mi perfil</a>
                <form action="<?= RUTA_BASE ?>/auth/logout.php" method="POST" class="inline-form">
                    <input type="hidden" name="token_csrf" value="<?= e(obtenerTokenCsrf()) ?>">
                    <button class="nav-logout" type="submit">Cerrar sesión</button>
                </form>
            <?php else: ?>
                <a href="<?= RUTA_BASE ?>/auth/login.php">Iniciar sesión</a>
                <a class="button button-small" href="<?= RUTA_BASE ?>/auth/registro.php">Crear cuenta</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<?php foreach ($mensajesVista as $mensaje): ?>
    <div class="container flash flash-<?= e($mensaje['tipo']) ?>" role="status"><?= e($mensaje['texto']) ?></div>
<?php endforeach; ?>
