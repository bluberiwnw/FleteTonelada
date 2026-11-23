<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';

if (usuarioAutenticado()) {
    redirigirSegunRol();
}

$errores = [];
$email = '';

$mensajeRegistro = ($_GET['registro'] ?? '') === 'correcto';
$mensajeSalida = ($_GET['salida'] ?? '') === 'correcta';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    validarTokenCsrf();

    $email = trim(
        is_string($_POST['email'] ?? null)
            ? $_POST['email']
            : ''
    );

    $contrasena = is_string($_POST['contrasena'] ?? null)
        ? $_POST['contrasena']
        : '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'Ingresa un correo electrónico válido.';
    }

    if ($contrasena === '') {
        $errores[] = 'Ingresa tu contraseña.';
    }

    if (empty($errores)) {

        try {

            $consulta = $pdo->prepare(
                "SELECT id, nombre, email, password_hash, rol, verificado, activo
                 FROM usuarios
                 WHERE email = :email
                 LIMIT 1"
            );

            $consulta->execute([
                ':email' => $email
            ]);

            $usuario = $consulta->fetch();

            if (
                !$usuario ||
                !(int) $usuario['activo'] ||
                !password_verify($contrasena, $usuario['password_hash'])
            ) {

                $errores[] = 'Correo electrónico o contraseña incorrectos.';

            } else {

                session_regenerate_id(true);

                $_SESSION['usuario'] = [
                    'id' => (int) $usuario['id'],
                    'nombre' => $usuario['nombre'],
                    'email' => $usuario['email'],
                    'rol' => $usuario['rol'],
                    'verificado' => (bool) $usuario['verificado']
                ];

                unset($_SESSION['token_csrf']);

                obtenerTokenCsrf();

                redirigirSegunRol();
            }

        } catch (PDOException $e) {

            error_log('Error en inicio de sesión: ' . $e->getMessage());

            $errores[] = 'No fue posible iniciar sesión. Inténtalo nuevamente.';
        }
    }
}

$tokenCsrf = obtenerTokenCsrf();

?>

<!DOCTYPE html>
<html lang="es">

<head>
<link rel="stylesheet" href="/fletetonelada/assets/css/base.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Iniciar sesión | FleteTonelada</title>
</head>

<body>

    <main class="auth-page">

        <h1>Iniciar sesión</h1>

        <p>Ingresa tus credenciales para acceder a FleteTonelada.</p>

        <?php if ($mensajeRegistro): ?>

            <p role="status">
                Tu registro se completó correctamente. Ahora puedes iniciar sesión.
            </p>

        <?php endif; ?>

        <?php if ($mensajeSalida): ?>

            <p role="status">
                Has cerrado sesión correctamente.
            </p>

        <?php endif; ?>

        <?php if (!empty($errores)): ?>

            <div role="alert">
                <ul>
                    <?php foreach ($errores as $error): ?>
                        <li><?= e($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>

        <?php endif; ?>

        <form method="POST">

            <input
                type="hidden"
                name="token_csrf"
                value="<?= e($tokenCsrf) ?>"
            >

            <div>

                <label for="email">Correo electrónico</label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    maxlength="190"
                    autocomplete="email"
                    required
                    value="<?= e($email) ?>"
                >

            </div>

            <div>

                <label for="contrasena">Contraseña</label>

                <input
                    type="password"
                    id="contrasena"
                    name="contrasena"
                    autocomplete="current-password"
                    required
                >

            </div>

            <button type="submit">Iniciar sesión</button>

        </form>

        <p>
            ¿Todavía no tienes una cuenta?
            <a href="<?= RUTA_BASE ?>/auth/registro.php">
                Regístrate aquí
            </a>
        </p>

        <a href="<?= RUTA_BASE ?>/">
            Volver al inicio
        </a>

    </main>

</body>

</html>
