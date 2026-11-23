<?php

declare(strict_types=1);

const RUTA_BASE = '/fletetonelada';

ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');

if (session_status() === PHP_SESSION_NONE) {
    $esHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => RUTA_BASE . '/',
        'secure' => $esHttps,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    session_start();
}

/**
 * Escapa texto para mostrarlo de forma segura en HTML.
 */
function e(?string $valor): string
{
    return htmlspecialchars(
        $valor ?? '',
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

/**
 * Genera un token CSRF para proteger formularios.
 */
function obtenerTokenCsrf(): string
{
    if (empty($_SESSION['token_csrf'])) {
        $_SESSION['token_csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['token_csrf'];
}

/**
 * Comprueba el token CSRF recibido mediante POST.
 */
function validarTokenCsrf(): void
{
    $token = $_POST['token_csrf'] ?? '';

    if (
        $_SERVER['REQUEST_METHOD'] !== 'POST' ||
        !is_string($token) ||
        empty($_SESSION['token_csrf']) ||
        !hash_equals($_SESSION['token_csrf'], $token)
    ) {
        http_response_code(403);
        exit('Solicitud no válida. Actualiza la página e inténtalo nuevamente.');
    }
}

/**
 * Comprueba si existe una sesión iniciada.
 */
function usuarioAutenticado(): bool
{
    return isset(
        $_SESSION['usuario']['id'],
        $_SESSION['usuario']['rol']
    );
}

/**
 * Exige que el usuario haya iniciado sesión.
 */
function requiereLogin(): void
{
    if (!usuarioAutenticado()) {
        header('Location: ' . RUTA_BASE . '/auth/login.php');
        exit;
    }
}

/**
 * Restringe el acceso a un rol específico.
 */
function requiereRol(string $rol): void
{
    requiereLogin();

    if ($_SESSION['usuario']['rol'] !== $rol) {
        http_response_code(403);
        exit('No tienes permisos para acceder a esta sección.');
    }
}

/**
 * Redirige al usuario según su rol.
 */
function redirigirSegunRol(): void
{
    if (!usuarioAutenticado()) {
        header('Location: ' . RUTA_BASE . '/auth/login.php');
        exit;
    }

    $destinos = [
        'cliente' => RUTA_BASE . '/cliente/panel.php',
        'transportista' => RUTA_BASE . '/transportista/panel.php',
        'admin' => RUTA_BASE . '/admin/panel.php'
    ];

    $rol = $_SESSION['usuario']['rol'];

    if (!isset($destinos[$rol])) {
        http_response_code(403);
        exit('Rol de usuario no válido.');
    }

    header('Location: ' . $destinos[$rol]);
    exit;
}
