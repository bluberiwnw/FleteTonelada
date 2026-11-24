<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    exit('Método no permitido.');
}

requiereLogin();
validarTokenCsrf();
$_SESSION = [];
$params = session_get_cookie_params();
if (ini_get('session.use_cookies')) {
    setcookie(session_name(), '', [
        'expires' => time() - 42000,
        'path' => $params['path'],
        'domain' => $params['domain'],
        'secure' => $params['secure'],
        'httponly' => $params['httponly'],
        'samesite' => 'Lax',
    ]);
}
session_destroy();
header('Location: ' . RUTA_BASE . '/auth/login.php?salida=correcta');
exit;
