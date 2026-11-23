<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';

if (usuarioAutenticado()) {
    redirigirSegunRol();
}

$errores = [];

$nombre = '';
$email = '';
$telefono = '';
$empresa = '';
$rol = 'cliente';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    validarTokenCsrf();

    $nombre = trim(is_string($_POST['nombre'] ?? null) ? $_POST['nombre'] : '');
    $email = trim(is_string($_POST['email'] ?? null) ? $_POST['email'] : '');
    $telefono = trim(is_string($_POST['telefono'] ?? null) ? $_POST['telefono'] : '');
    $empresa = trim(is_string($_POST['empresa'] ?? null) ? $_POST['empresa'] : '');
    $rol = is_string($_POST['rol'] ?? null) ? $_POST['rol'] : '';

    $contrasena = is_string($_POST['contrasena'] ?? null)
        ? $_POST['contrasena']
        : '';

    $confirmarContrasena = is_string($_POST['confirmar_contrasena'] ?? null)
        ? $_POST['confirmar_contrasena']
        : '';

    // Validar nombre.
    if ($nombre === '' || mb_strlen($nombre) > 120) {
        $errores[] = 'Ingresa un nombre válido de máximo 120 caracteres.';
    }

    // Validar correo.
    if (
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        mb_strlen($email) > 190
    ) {
        $errores[] = 'Ingresa un correo electrónico válido.';
    }

    // Validar teléfono.
    if (mb_strlen($telefono) > 20) {
        $errores[] = 'El teléfono no puede superar los 20 caracteres.';
    }

    // Validar empresa.
    if (mb_strlen($empresa) > 150) {
        $errores[] = 'El nombre de empresa no puede superar los 150 caracteres.';
    }

    // Validar rol.
    if (!in_array($rol, ['cliente', 'transportista'], true)) {
        $errores[] = 'Selecciona un tipo de cuenta válido.';
    }

    // Validar contraseña.
    if (strlen($contrasena) < 8) {
        $errores[] = 'La contraseña debe tener al menos 8 caracteres.';
    }

    if (strlen($contrasena) > 72) {
        $errores[] = 'La contraseña no puede superar los 72 bytes.';
    }

    if ($contrasena !== $confirmarContrasena) {
        $errores[] = 'Las contraseñas no coinciden.';
    }

    // Comprobar si el correo ya está registrado.
    if (empty($errores)) {

        $consulta = $pdo->prepare(
            "SELECT id FROM usuarios WHERE email = :email LIMIT 1"
        );

        $consulta->execute([
            ':email' => $email
        ]);

        if ($consulta->fetch()) {
            $errores[] = 'Este correo electrónico ya está registrado.';
        }
    }

    // Registrar al usuario.
    if (empty($errores)) {

        $passwordHash = password_hash($contrasena, PASSWORD_DEFAULT);

        $verificado = $rol === 'cliente' ? 1 : 0;

        try {

            $consulta = $pdo->prepare(
                "INSERT INTO usuarios
                (nombre, email, password_hash, telefono, empresa, rol, verificado)
                VALUES
                (:nombre, :email, :password_hash, :telefono, :empresa, :rol, :verificado)"
            );

            $consulta->execute([
                ':nombre' => $nombre,
                ':email' => $email,
                ':password_hash' => $passwordHash,
                ':telefono' => $telefono !== '' ? $telefono : null,
                ':empresa' => $empresa !== '' ? $empresa : null,
                ':rol' => $rol,
                ':verificado' => $verificado
            ]);

            header('Location: ' . RUTA_BASE . '/auth/login.php?registro=correcto');
            exit;

        } catch (PDOException $e) {

            error_log('Error en registro: ' . $e->getMessage());

            $errores[] = 'No fue posible completar el registro. Inténtalo nuevamente.';
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
    <title>Crear cuenta | FleteTonelada</title>
</head>

<body>

    <main class="auth-page">

        <h1>Crear cuenta</h1>

        <p>Regístrate en FleteTonelada para comenzar a utilizar la plataforma.</p>

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
                <label for="nombre">Nombre completo</label>

                <input
                    type="text"
                    id="nombre"
                    name="nombre"
                    maxlength="120"
                    autocomplete="name"
                    required
                    value="<?= e($nombre) ?>"
                >
            </div>

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
                <label for="telefono">Teléfono</label>

                <input
                    type="tel"
                    id="telefono"
                    name="telefono"
                    maxlength="20"
                    autocomplete="tel"
                    value="<?= e($telefono) ?>"
                >
            </div>

            <div>
                <label for="empresa">Nombre de empresa</label>

                <input
                    type="text"
                    id="empresa"
                    name="empresa"
                    maxlength="150"
                    value="<?= e($empresa) ?>"
                >
            </div>

            <div>
                <label for="rol">Tipo de cuenta</label>

                <select id="rol" name="rol" required>

                    <option value="cliente" <?= $rol === 'cliente' ? 'selected' : '' ?>>
                        Cliente
                    </option>

                    <option value="transportista" <?= $rol === 'transportista' ? 'selected' : '' ?>>
                        Transportista
                    </option>

                </select>
            </div>

            <div>
                <label for="contrasena">Contraseña</label>

                <input
                    type="password"
                    id="contrasena"
                    name="contrasena"
                    minlength="8"
                    maxlength="72"
                    autocomplete="new-password"
                    required
                >
            </div>

            <div>
                <label for="confirmar_contrasena">Confirmar contraseña</label>

                <input
                    type="password"
                    id="confirmar_contrasena"
                    name="confirmar_contrasena"
                    minlength="8"
                    maxlength="72"
                    autocomplete="new-password"
                    required
                >
            </div>

            <button type="submit">Crear cuenta</button>

        </form>

        <p>
            ¿Ya tienes una cuenta?
            <a href="<?= RUTA_BASE ?>/auth/login.php">Inicia sesión</a>
        </p>

        <a href="<?= RUTA_BASE ?>/">Volver al inicio</a>

    </main>

</body>

</html>
