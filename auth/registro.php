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

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    validarTokenCsrf();

    $nombre = trim(is_string($_POST['nombre'] ?? null) ? $_POST['nombre'] : '');
    $email = trim(is_string($_POST['email'] ?? null) ? $_POST['email'] : '');
    $telefono = trim(is_string($_POST['telefono'] ?? null) ? $_POST['telefono'] : '');
    $empresa = trim(is_string($_POST['empresa'] ?? null) ? $_POST['empresa'] : '');
    $rol = is_string($_POST['rol'] ?? null) ? $_POST['rol'] : '';
    $contrasena = is_string($_POST['contrasena'] ?? null) ? $_POST['contrasena'] : '';
    $confirmar = is_string($_POST['confirmar_contrasena'] ?? null) ? $_POST['confirmar_contrasena'] : '';

    if ($nombre === '' || mb_strlen($nombre) > 120) {
        $errores[] = 'Ingresa un nombre de máximo 120 caracteres.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) {
        $errores[] = 'Ingresa un correo electrónico válido.';
    }
    if (mb_strlen($telefono) > 20) {
        $errores[] = 'El teléfono no puede superar 20 caracteres.';
    }
    if (mb_strlen($empresa) > 150) {
        $errores[] = 'La empresa no puede superar 150 caracteres.';
    }
    if (!in_array($rol, ['cliente', 'transportista'], true)) {
        $errores[] = 'Selecciona un tipo de cuenta válido.';
    }
    if (strlen($contrasena) < 8 || strlen($contrasena) > 72) {
        $errores[] = 'La contraseña debe tener entre 8 y 72 bytes.';
    }
    if ($contrasena !== $confirmar) {
        $errores[] = 'Las contraseñas no coinciden.';
    }

    if (!$errores) {
        try {
            $consulta = $pdo->prepare('SELECT id FROM usuarios WHERE email = :email LIMIT 1');
            $consulta->execute([':email' => $email]);
            if ($consulta->fetch()) {
                $errores[] = 'Este correo ya está registrado.';
            } else {
                $hash = password_hash($contrasena, PASSWORD_DEFAULT);
                $verificado = $rol === 'cliente' ? 1 : 0;
                $insertar = $pdo->prepare(
                    'INSERT INTO usuarios (nombre, email, password_hash, telefono, empresa, rol, verificado) VALUES (:nombre, :email, :hash, :telefono, :empresa, :rol, :verificado)'
                );
                $insertar->execute([
                    ':nombre' => $nombre,
                    ':email' => mb_strtolower($email),
                    ':hash' => $hash,
                    ':telefono' => $telefono !== '' ? $telefono : null,
                    ':empresa' => $empresa !== '' ? $empresa : null,
                    ':rol' => $rol,
                    ':verificado' => $verificado,
                ]);
                establecerMensaje('success', 'Tu cuenta fue creada. Ya puedes iniciar sesión.');
                header('Location: ' . RUTA_BASE . '/auth/login.php');
                exit;
            }
        } catch (PDOException $e) {
            error_log('Registro FleteTonelada: ' . $e->getMessage());
            $errores[] = 'No fue posible completar el registro. Inténtalo nuevamente.';
        }
    }
}

$tituloPagina = 'Crear cuenta | FleteTonelada';
require_once __DIR__ . '/../includes/header.php';
?>
<main class="auth-layout" id="contenido">
    <section class="auth-card">
        <span class="eyebrow">ÚNETE A FLETETONELADA</span>
        <h1>Crea tu cuenta</h1>
        <p class="muted">Regístrate como cliente o transportista para comenzar.</p>
        <?php if ($errores): ?><div class="alert alert-error" role="alert"><ul><?php foreach ($errores as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
        <form method="POST" class="form-stack" data-validar-contrasena>
            <input type="hidden" name="token_csrf" value="<?= e(obtenerTokenCsrf()) ?>">
            <div class="form-field"><label for="nombre">Nombre completo *</label><input id="nombre" name="nombre" maxlength="120" autocomplete="name" required value="<?= e($nombre) ?>"></div>
            <div class="form-field"><label for="email">Correo electrónico *</label><input type="email" id="email" name="email" maxlength="190" autocomplete="email" required value="<?= e($email) ?>"></div>
            <div class="form-two">
                <div class="form-field"><label for="telefono">Teléfono</label><input type="tel" id="telefono" name="telefono" maxlength="20" autocomplete="tel" value="<?= e($telefono) ?>"></div>
                <div class="form-field"><label for="empresa">Empresa</label><input id="empresa" name="empresa" maxlength="150" value="<?= e($empresa) ?>"></div>
            </div>
            <div class="form-field"><label for="rol">Tipo de cuenta *</label><select id="rol" name="rol" required><option value="cliente" <?= $rol === 'cliente' ? 'selected' : '' ?>>Cliente</option><option value="transportista" <?= $rol === 'transportista' ? 'selected' : '' ?>>Transportista</option></select></div>
            <div class="form-two">
                <div class="form-field"><label for="contrasena">Contraseña *</label><input type="password" id="contrasena" name="contrasena" minlength="8" maxlength="72" autocomplete="new-password" required></div>
                <div class="form-field"><label for="confirmar_contrasena">Confirmar *</label><input type="password" id="confirmar_contrasena" name="confirmar_contrasena" minlength="8" maxlength="72" autocomplete="new-password" required></div>
            </div>
            <p class="form-help">Los transportistas nuevos necesitan verificación del administrador antes de aceptar cargas.</p>
            <button class="button button-primary button-block" type="submit">Crear cuenta</button>
        </form>
        <p class="auth-switch">¿Ya tienes cuenta? <a href="<?= RUTA_BASE ?>/auth/login.php">Inicia sesión</a></p>
    </section>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
