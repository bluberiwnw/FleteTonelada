<?php

declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../config/db.php';
requiereRol('admin');
$adminId = (int) $_SESSION['usuario']['id'];
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    validarTokenCsrf();
    $usuarioId = filter_var($_POST['usuario_id'] ?? null, FILTER_VALIDATE_INT);
    $accion = is_string($_POST['accion'] ?? null) ? $_POST['accion'] : '';
    if (!$usuarioId || $usuarioId === $adminId) {
        establecerMensaje('error', 'No puedes modificar tu propia cuenta desde esta sección.');
    } else {
        $q = $pdo->prepare("SELECT id,rol FROM usuarios WHERE id=:id AND rol <> 'admin'");
        $q->execute([':id' => $usuarioId]);
        $objetivo = $q->fetch();
        if (!$objetivo) {
            establecerMensaje('error', 'El usuario no existe o no puede administrarse desde esta sección.');
        } elseif ($accion === 'verificar' && $objetivo['rol'] === 'transportista') {
            $pdo->prepare('UPDATE usuarios SET verificado=IF(verificado=1,0,1) WHERE id=:id')->execute([':id' => $usuarioId]);
            establecerMensaje('success', 'Se actualizó la verificación del transportista.');
        } elseif ($accion === 'activar') {
            $pdo->prepare('UPDATE usuarios SET activo=IF(activo=1,0,1) WHERE id=:id')->execute([':id' => $usuarioId]);
            establecerMensaje('success', 'Se actualizó el estado de la cuenta.');
        } else {
            establecerMensaje('error', 'Acción no válida.');
        }
    }
    header('Location: ' . RUTA_BASE . '/admin/usuarios.php'); exit;
}
$filtro = is_string($_GET['rol'] ?? null) ? $_GET['rol'] : '';
$sql = 'SELECT id,nombre,email,telefono,empresa,rol,verificado,activo,creado_en FROM usuarios';
$params = [];
if (in_array($filtro, ['cliente','transportista','admin'], true)) { $sql .= ' WHERE rol=:rol'; $params[':rol'] = $filtro; }
$sql .= ' ORDER BY creado_en DESC';
$q = $pdo->prepare($sql); $q->execute($params); $usuarios = $q->fetchAll();
$tituloPagina = 'Usuarios | FleteTonelada';
require_once __DIR__ . '/../includes/header.php';
?>
<main class="dashboard-page container" id="contenido"><div class="page-heading"><div><span class="eyebrow eyebrow-dark">ADMINISTRACIÓN</span><h1>Usuarios</h1><p>Verifica transportistas y administra el estado de las cuentas.</p></div><a class="button button-ghost" href="<?= RUTA_BASE ?>/admin/panel.php">Volver al panel</a></div>
<section class="surface-card form-card"><form method="GET" class="filter-form"><div class="form-field"><label for="rol">Filtrar por rol</label><select id="rol" name="rol"><option value="">Todos</option><?php foreach (['cliente'=>'Cliente','transportista'=>'Transportista','admin'=>'Administrador'] as $key=>$label): ?><option value="<?= $key ?>" <?= $filtro === $key ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select></div><button class="button button-secondary" type="submit">Filtrar</button></form></section>
<section class="surface-card section-card"><div class="table-wrap"><table><thead><tr><th>Usuario</th><th>Rol</th><th>Verificación</th><th>Cuenta</th><th>Registro</th><th>Acciones</th></tr></thead><tbody><?php foreach ($usuarios as $u): ?><tr><td><strong><?= e($u['nombre']) ?></strong><small class="table-subline"><?= e($u['email']) ?><br><?= e($u['telefono'] ?? '') ?></small></td><td><?= e(ucfirst($u['rol'])) ?></td><td><?= $u['rol'] === 'transportista' ? ((int) $u['verificado'] ? 'Verificado' : 'Pendiente') : 'No aplica' ?></td><td><span class="status <?= $u['activo'] ? 'status-entregada' : 'status-cancelada' ?>"><?= $u['activo'] ? 'Activa' : 'Desactivada' ?></span></td><td><?= e(substr($u['creado_en'],0,10)) ?></td><td><?php if ($u['rol'] !== 'admin'): ?><div class="action-stack"><?php if ($u['rol'] === 'transportista'): ?><form method="POST"><input type="hidden" name="token_csrf" value="<?= e(obtenerTokenCsrf()) ?>"><input type="hidden" name="usuario_id" value="<?= (int) $u['id'] ?>"><input type="hidden" name="accion" value="verificar"><button class="text-button" type="submit"><?= $u['verificado'] ? 'Quitar verificación' : 'Verificar' ?></button></form><?php endif; ?><form method="POST"><input type="hidden" name="token_csrf" value="<?= e(obtenerTokenCsrf()) ?>"><input type="hidden" name="usuario_id" value="<?= (int) $u['id'] ?>"><input type="hidden" name="accion" value="activar"><button class="text-button" type="submit"><?= $u['activo'] ? 'Desactivar' : 'Activar' ?></button></form></div><?php else: ?>—<?php endif; ?></td></tr><?php endforeach; ?><?php if (!$usuarios): ?><tr><td colspan="6">No hay usuarios para este filtro.</td></tr><?php endif; ?></tbody></table></div></section></main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
