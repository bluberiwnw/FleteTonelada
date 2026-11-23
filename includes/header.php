<?php

require_once __DIR__ . '/auth.php';

$tituloPagina = $tituloPagina ?? 'FleteTonelada';

$destinosPanel = [
    'cliente' => RUTA_BASE . '/cliente/panel.php',
    'transportista' => RUTA_BASE . '/transportista/panel.php',
    'admin' => RUTA_BASE . '/admin/panel.php'
];

$rolActual = $_SESSION['usuario']['rol'] ?? '';
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="FleteTonelada conecta empresas con transportistas independientes para facilitar el transporte de carga pesada.">

    <title><?= e($tituloPagina) ?></title>

    <link rel="stylesheet" href="<?= RUTA_BASE ?>/assets/css/base.css">
</head>

<body>

<a class="salto-contenido" href="#contenido">Saltar al contenido</a>

<header class="site-header">

    <div class="container header-inner">

        <a href="<?= RUTA_BASE ?>/" class="brand">
            <span class="brand-icon">FT</span>

            <span class="brand-name">
                Flete<span>Tonelada</span>
                <small>TRANSPORTE DE CARGA</small>
            </span>
        </a>

        <button
            class="menu-toggle"
            id="menu-toggle"
            type="button"
            aria-label="Abrir menú"
            aria-expanded="false"
            aria-controls="menu-principal"
        >
            <span></span>
            <span></span>
            <span></span>
        </button>

        <nav class="main-nav" id="menu-principal">

            <a href="<?= RUTA_BASE ?>/">Inicio</a>

            <a href="<?= RUTA_BASE ?>/#como-funciona">
                Cómo funciona
            </a>

            <a href="<?= RUTA_BASE ?>/#cotizador">
                Cotizar
            </a>

            <?php if (usuarioAutenticado()): ?>

                <?php if (isset($destinosPanel[$rolActual])): ?>

                    <a
                        href="<?= e($destinosPanel[$rolActual]) ?>"
                        class="nav-button"
                    >
                        Mi panel
                    </a>

                <?php endif; ?>

                <form
                    action="<?= RUTA_BASE ?>/auth/logout.php"
                    method="POST"
                    class="logout-form"
                >
                    <input
                        type="hidden"
                        name="token_csrf"
                        value="<?= e(obtenerTokenCsrf()) ?>"
                    >

                    <button type="submit" class="nav-logout">
                        Cerrar sesión
                    </button>
                </form>

            <?php else: ?>

                <a href="<?= RUTA_BASE ?>/auth/login.php">
                    Iniciar sesión
                </a>

                <a
                    href="<?= RUTA_BASE ?>/auth/registro.php"
                    class="nav-button"
                >
                    Crear cuenta
                </a>

            <?php endif; ?>

        </nav>

    </div>

</header>
