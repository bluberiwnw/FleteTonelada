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
        'samesite' => 'Lax',
    ]);
    session_start();
}

function e(?string $valor): string
{
    return htmlspecialchars($valor ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function obtenerTokenCsrf(): string
{
    if (empty($_SESSION['token_csrf'])) {
        $_SESSION['token_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['token_csrf'];
}

function validarTokenCsrf(): void
{
    $token = $_POST['token_csrf'] ?? '';
    if (
        ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST' ||
        !is_string($token) ||
        empty($_SESSION['token_csrf']) ||
        !hash_equals($_SESSION['token_csrf'], $token)
    ) {
        http_response_code(403);
        exit('Solicitud no válida. Actualiza la página e inténtalo nuevamente.');
    }
}

function usuarioAutenticado(): bool
{
    return isset($_SESSION['usuario']['id'], $_SESSION['usuario']['rol']);
}

function comprobarCuentaActual(): void
{
    global $pdo;
    if (!usuarioAutenticado() || !isset($pdo) || !$pdo instanceof PDO) {
        return;
    }

    $consulta = $pdo->prepare('SELECT id,nombre,email,rol,verificado,activo FROM usuarios WHERE id=:id LIMIT 1');
    $consulta->execute([':id' => (int) $_SESSION['usuario']['id']]);
    $actual = $consulta->fetch();

    if (!$actual || !(int) $actual['activo']) {
        $_SESSION = [];
        session_regenerate_id(true);
        establecerMensaje('error', 'Tu cuenta está desactivada o ya no está disponible.');
        header('Location: ' . RUTA_BASE . '/auth/login.php');
        exit;
    }

    $_SESSION['usuario'] = [
        'id' => (int) $actual['id'],
        'nombre' => $actual['nombre'],
        'email' => $actual['email'],
        'rol' => $actual['rol'],
        'verificado' => (bool) $actual['verificado'],
    ];
}

function requiereLogin(): void
{
    if (!usuarioAutenticado()) {
        header('Location: ' . RUTA_BASE . '/auth/login.php');
        exit;
    }
    comprobarCuentaActual();
}

function requiereRol(string $rol): void
{
    requiereLogin();
    if (($_SESSION['usuario']['rol'] ?? '') !== $rol) {
        http_response_code(403);
        exit('No tienes permisos para acceder a esta sección.');
    }
}

function requiereCualquierRol(array $roles): void
{
    requiereLogin();
    if (!in_array($_SESSION['usuario']['rol'] ?? '', $roles, true)) {
        http_response_code(403);
        exit('No tienes permisos para acceder a esta sección.');
    }
}

function redirigirSegunRol(): never
{
    if (!usuarioAutenticado()) {
        header('Location: ' . RUTA_BASE . '/auth/login.php');
        exit;
    }
    comprobarCuentaActual();

    $destinos = [
        'cliente' => RUTA_BASE . '/cliente/panel.php',
        'transportista' => RUTA_BASE . '/transportista/panel.php',
        'admin' => RUTA_BASE . '/admin/panel.php',
    ];

    $rol = $_SESSION['usuario']['rol'];
    if (!isset($destinos[$rol])) {
        http_response_code(403);
        exit('Rol de usuario no válido.');
    }

    header('Location: ' . $destinos[$rol]);
    exit;
}

function establecerMensaje(string $tipo, string $mensaje): void
{
    $_SESSION['mensajes'][] = ['tipo' => $tipo, 'texto' => $mensaje];
}

function obtenerMensajes(): array
{
    $mensajes = $_SESSION['mensajes'] ?? [];
    unset($_SESSION['mensajes']);
    return $mensajes;
}
