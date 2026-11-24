<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';

if (usuarioAutenticado()) {
    redirigirSegunRol();
}

$errores = [];
$email = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    validarTokenCsrf();
    $email = trim(is_string($_POST['email'] ?? null) ? $_POST['email'] : '');
    $contrasena = is_string($_POST['contrasena'] ?? null) ? $_POST['contrasena'] : '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'Ingresa un correo electrónico válido.';
    }
    if ($contrasena === '') {
        $errores[] = 'Ingresa tu contraseña.';
    }

    if (!$errores) {
        try {
            $consulta = $pdo->prepare(
                'SELECT id, nombre, email, password_hash, rol, verificado, activo FROM usuarios WHERE email = :email LIMIT 1'
            );
            $consulta->execute([':email' => $email]);
            $usuario = $consulta->fetch();

            if (!$usuario || !(int) $usuario['activo'] || !password_verify($contrasena, $usuario['password_hash'])) {
                $errores[] = 'Correo electrónico o contraseña incorrectos.';
            } else {
                if (password_needs_rehash($usuario['password_hash'], PASSWORD_DEFAULT)) {
                    $nuevoHash = password_hash($contrasena, PASSWORD_DEFAULT);
                    $actualizar = $pdo->prepare('UPDATE usuarios SET password_hash = :hash WHERE id = :id');
                    $actualizar->execute([':hash' => $nuevoHash, ':id' => $usuario['id']]);
                }

                session_regenerate_id(true);
                $_SESSION['usuario'] = [
                    'id' => (int) $usuario['id'],
                    'nombre' => $usuario['nombre'],
                    'email' => $usuario['email'],
                    'rol' => $usuario['rol'],
                    'verificado' => (bool) $usuario['verificado'],
                ];
                unset($_SESSION['token_csrf']);
                obtenerTokenCsrf();
                redirigirSegunRol();
            }
        } catch (PDOException $e) {
            error_log('Login FleteTonelada: ' . $e->getMessage());
            $errores[] = 'No fue posible iniciar sesión. Inténtalo nuevamente.';
        }
    }
}

$tituloPagina = 'Iniciar sesión | FleteTonelada';
require_once __DIR__ . '/../includes/header.php';
?>
<main class="auth-layout" id="contenido">
    <section class="auth-card auth-card-narrow">
        <span class="eyebrow">BIENVENIDO DE NUEVO</span>
        <h1>Iniciar sesión</h1>
        <p class="muted">Ingresa tus credenciales para continuar.</p>
        <?php if ($errores): ?><div class="alert alert-error" role="alert"><ul><?php foreach ($errores as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
        <form method="POST" class="form-stack">
            <input type="hidden" name="token_csrf" value="<?= e(obtenerTokenCsrf()) ?>">
            <div class="form-field"><label for="email">Correo electrónico</label><input type="email" id="email" name="email" maxlength="190" autocomplete="username" required value="<?= e($email) ?>"></div>
            <div class="form-field"><label for="contrasena">Contraseña</label><input type="password" id="contrasena" name="contrasena" autocomplete="current-password" required></div>
            <button class="button button-primary button-block" type="submit">Entrar a mi cuenta</button>
        </form>
        <p class="auth-switch">¿No tienes cuenta? <a href="<?= RUTA_BASE ?>/auth/registro.php">Regístrate</a></p>
    </section>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
