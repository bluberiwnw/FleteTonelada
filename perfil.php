<?php

declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';
requiereLogin();
$id = (int) $_SESSION['usuario']['id'];
$q = $pdo->prepare('SELECT nombre,email,telefono,empresa,password_hash FROM usuarios WHERE id=:id AND activo=1');
$q->execute([':id' => $id]); $usuario = $q->fetch();
if (!$usuario) { $_SESSION = []; session_destroy(); header('Location: ' . RUTA_BASE . '/auth/login.php'); exit; }
$errores=[];
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    validarTokenCsrf();
    $nombre=trim(is_string($_POST['nombre'] ?? null)?$_POST['nombre']:'');
    $telefono=trim(is_string($_POST['telefono'] ?? null)?$_POST['telefono']:'');
    $empresa=trim(is_string($_POST['empresa'] ?? null)?$_POST['empresa']:'');
    $actual=is_string($_POST['contrasena_actual'] ?? null)?$_POST['contrasena_actual']:'';
    $nueva=is_string($_POST['contrasena_nueva'] ?? null)?$_POST['contrasena_nueva']:'';
    $confirmar=is_string($_POST['confirmar_contrasena'] ?? null)?$_POST['confirmar_contrasena']:'';
    if ($nombre==='' || mb_strlen($nombre)>120) $errores[]='El nombre es obligatorio y no debe superar 120 caracteres.';
    if (mb_strlen($telefono)>20) $errores[]='El teléfono no debe superar 20 caracteres.';
    if (mb_strlen($empresa)>150) $errores[]='La empresa no debe superar 150 caracteres.';
    if ($nueva!=='' && !password_verify($actual,$usuario['password_hash'])) $errores[]='La contraseña actual no es correcta.';
    if ($nueva!=='' && (strlen($nueva)<8 || strlen($nueva)>72)) $errores[]='La nueva contraseña debe tener entre 8 y 72 bytes.';
    if ($nueva!==$confirmar) $errores[]='La confirmación de contraseña no coincide.';
    if (!$errores) {
        if ($nueva!=='') {
            $hash=password_hash($nueva,PASSWORD_DEFAULT);
            $upd=$pdo->prepare('UPDATE usuarios SET nombre=:nombre,telefono=:telefono,empresa=:empresa,password_hash=:hash WHERE id=:id');
            $upd->execute([':nombre'=>$nombre,':telefono'=>$telefono!==''?$telefono:null,':empresa'=>$empresa!==''?$empresa:null,':hash'=>$hash,':id'=>$id]);
        } else {
            $upd=$pdo->prepare('UPDATE usuarios SET nombre=:nombre,telefono=:telefono,empresa=:empresa WHERE id=:id');
            $upd->execute([':nombre'=>$nombre,':telefono'=>$telefono!==''?$telefono:null,':empresa'=>$empresa!==''?$empresa:null,':id'=>$id]);
        }
        $_SESSION['usuario']['nombre']=$nombre;
        establecerMensaje('success','Tu perfil fue actualizado.');
        header('Location: '.RUTA_BASE.'/perfil.php'); exit;
    }
    $usuario['nombre']=$nombre; $usuario['telefono']=$telefono; $usuario['empresa']=$empresa;
}
$tituloPagina='Mi perfil | FleteTonelada';
require_once __DIR__.'/includes/header.php';
?>
<main class="dashboard-page container" id="contenido"><div class="page-heading"><div><span class="eyebrow eyebrow-dark">CUENTA</span><h1>Mi perfil</h1><p>Actualiza tus datos de contacto y, si lo necesitas, tu contraseña.</p></div><a class="button button-ghost" href="<?= RUTA_BASE ?>/">Volver al inicio</a></div><section class="surface-card form-card narrow-card"><?php if ($errores): ?><div class="alert alert-error"><ul><?php foreach($errores as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?><form method="POST" class="form-stack"><input type="hidden" name="token_csrf" value="<?= e(obtenerTokenCsrf()) ?>"><div class="form-field"><label for="nombre">Nombre</label><input id="nombre" name="nombre" maxlength="120" required value="<?= e($usuario['nombre']) ?>"></div><div class="form-field"><label>Correo electrónico</label><input value="<?= e($usuario['email']) ?>" disabled><small class="form-help">El correo no se puede cambiar en esta versión.</small></div><div class="form-field"><label for="telefono">Teléfono</label><input id="telefono" name="telefono" maxlength="20" value="<?= e($usuario['telefono']??'') ?>"></div><div class="form-field"><label for="empresa">Empresa</label><input id="empresa" name="empresa" maxlength="150" value="<?= e($usuario['empresa']??'') ?>"></div><hr class="soft-rule"><h2>Cambiar contraseña (opcional)</h2><div class="form-field"><label for="contrasena_actual">Contraseña actual</label><input type="password" id="contrasena_actual" name="contrasena_actual" autocomplete="current-password"></div><div class="form-field"><label for="contrasena_nueva">Nueva contraseña</label><input type="password" id="contrasena_nueva" name="contrasena_nueva" minlength="8" maxlength="72" autocomplete="new-password"></div><div class="form-field"><label for="confirmar_contrasena">Confirmar nueva contraseña</label><input type="password" id="confirmar_contrasena" name="confirmar_contrasena" minlength="8" maxlength="72" autocomplete="new-password"></div><button class="button button-primary" type="submit">Guardar cambios</button></form></section></main>
<?php require_once __DIR__.'/includes/footer.php'; ?>
