<?php

declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../config/db.php';
requiereRol('transportista');
$idUsuario = (int) $_SESSION['usuario']['id'];
$errores = [];
$tipos = $pdo->query('SELECT id,nombre,capacidad_max_ton FROM tipos_unidad ORDER BY capacidad_max_ton')->fetchAll();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    validarTokenCsrf();
    $accion = is_string($_POST['accion'] ?? null) ? $_POST['accion'] : '';
    if ($accion === 'guardar') {
        $unidadId = filter_var($_POST['unidad_id'] ?? null, FILTER_VALIDATE_INT) ?: null;
        $tipoId = filter_var($_POST['tipo_unidad_id'] ?? null, FILTER_VALIDATE_INT);
        $placas = strtoupper(trim(is_string($_POST['placas'] ?? null) ? $_POST['placas'] : ''));
        $anio = filter_var($_POST['anio'] ?? null, FILTER_VALIDATE_INT);
        if (!$tipoId) $errores[] = 'Selecciona un tipo de unidad.';
        if ($placas === '' || strlen($placas) > 20 || !preg_match('/^[A-Z0-9 -]+$/', $placas)) $errores[] = 'Ingresa placas válidas (letras, números, espacios o guiones).';
        if (!$anio || $anio < 1980 || $anio > (int) date('Y') + 1) $errores[] = 'Ingresa un año válido.';
        if (!$errores) {
            try {
                $check = $pdo->prepare('SELECT id FROM tipos_unidad WHERE id=:id');
                $check->execute([':id' => $tipoId]);
                if (!$check->fetch()) throw new RuntimeException('Tipo de unidad no válido.');
                if ($unidadId) {
                    $upd = $pdo->prepare('UPDATE unidades SET tipo_unidad_id=:tipo,placas=:placas,anio=:anio WHERE id=:id AND transportista_id=:usuario');
                    $upd->execute([':tipo' => $tipoId, ':placas' => $placas, ':anio' => $anio, ':id' => $unidadId, ':usuario' => $idUsuario]);
                    if (!$upd->rowCount()) {
                        $verify = $pdo->prepare('SELECT id FROM unidades WHERE id=:id AND transportista_id=:usuario');
                        $verify->execute([':id' => $unidadId, ':usuario' => $idUsuario]);
                        if (!$verify->fetch()) throw new RuntimeException('No puedes modificar esa unidad.');
                    }
                    establecerMensaje('success', 'Unidad actualizada.');
                } else {
                    $ins = $pdo->prepare('INSERT INTO unidades (transportista_id,tipo_unidad_id,placas,anio) VALUES (:usuario,:tipo,:placas,:anio)');
                    $ins->execute([':usuario' => $idUsuario, ':tipo' => $tipoId, ':placas' => $placas, ':anio' => $anio]);
                    establecerMensaje('success', 'Unidad registrada.');
                }
                header('Location: ' . RUTA_BASE . '/transportista/unidades.php'); exit;
            } catch (Throwable $e) {
                error_log('Unidades: ' . $e->getMessage());
                $errores[] = $e instanceof RuntimeException ? $e->getMessage() : 'No se pudo guardar la unidad. Verifica que las placas no estén registradas.';
            }
        }
    } elseif ($accion === 'alternar') {
        $unidadId = filter_var($_POST['unidad_id'] ?? null, FILTER_VALIDATE_INT);
        if ($unidadId) {
            $upd = $pdo->prepare('UPDATE unidades SET activa = IF(activa=1,0,1) WHERE id=:id AND transportista_id=:usuario');
            $upd->execute([':id' => $unidadId, ':usuario' => $idUsuario]);
            establecerMensaje('success', 'Estado de la unidad actualizado.');
        }
        header('Location: ' . RUTA_BASE . '/transportista/unidades.php'); exit;
    }
}

$editar = null;
if (isset($_GET['editar'])) {
    $editarId = filter_var($_GET['editar'], FILTER_VALIDATE_INT);
    $q = $pdo->prepare('SELECT * FROM unidades WHERE id=:id AND transportista_id=:usuario');
    $q->execute([':id' => $editarId, ':usuario' => $idUsuario]);
    $editar = $q->fetch() ?: null;
}
$q = $pdo->prepare('SELECT un.*,tu.nombre AS tipo,tu.capacidad_max_ton FROM unidades un INNER JOIN tipos_unidad tu ON tu.id=un.tipo_unidad_id WHERE un.transportista_id=:usuario ORDER BY un.id DESC');
$q->execute([':usuario' => $idUsuario]); $misUnidades = $q->fetchAll();
$tituloPagina = 'Mis unidades | FleteTonelada';
require_once __DIR__ . '/../includes/header.php';
?>
<main class="dashboard-page container" id="contenido"><div class="page-heading"><div><span class="eyebrow eyebrow-dark">FLOTA</span><h1>Mis unidades</h1><p>Registra los vehículos con los que puedes realizar servicios.</p></div><a class="button button-ghost" href="<?= RUTA_BASE ?>/transportista/panel.php">Volver al panel</a></div>
<section class="surface-card form-card"><h2><?= $editar ? 'Editar unidad' : 'Registrar unidad' ?></h2><?php if ($errores): ?><div class="alert alert-error"><ul><?php foreach ($errores as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?><form method="POST" class="form-grid"><input type="hidden" name="token_csrf" value="<?= e(obtenerTokenCsrf()) ?>"><input type="hidden" name="accion" value="guardar"><input type="hidden" name="unidad_id" value="<?= (int) ($editar['id'] ?? 0) ?>"><div class="form-field"><label for="tipo_unidad_id">Tipo de unidad</label><select id="tipo_unidad_id" name="tipo_unidad_id" required><option value="">Selecciona</option><?php foreach ($tipos as $tipo): ?><option value="<?= (int) $tipo['id'] ?>" <?= (int) ($editar['tipo_unidad_id'] ?? 0) === (int) $tipo['id'] ? 'selected' : '' ?>><?= e($tipo['nombre']) ?> · <?= e((string) $tipo['capacidad_max_ton']) ?> t</option><?php endforeach; ?></select></div><div class="form-field"><label for="placas">Placas</label><input id="placas" name="placas" maxlength="20" required value="<?= e($editar['placas'] ?? '') ?>"></div><div class="form-field"><label for="anio">Año</label><input type="number" id="anio" name="anio" min="1980" max="<?= (int) date('Y') + 1 ?>" required value="<?= e((string) ($editar['anio'] ?? date('Y'))) ?>"></div><div class="form-field form-field-end"><button class="button button-primary" type="submit"><?= $editar ? 'Guardar cambios' : 'Registrar unidad' ?></button><?php if ($editar): ?><a class="text-link" href="<?= RUTA_BASE ?>/transportista/unidades.php">Cancelar edición</a><?php endif; ?></div></form></section>
<section class="surface-card section-card"><h2>Unidades registradas</h2><?php if (!$misUnidades): ?><div class="empty-state">Todavía no has registrado unidades.</div><?php else: ?><div class="table-wrap"><table><thead><tr><th>Unidad</th><th>Placas</th><th>Año</th><th>Capacidad</th><th>Estado</th><th>Acciones</th></tr></thead><tbody><?php foreach ($misUnidades as $u): ?><tr><td><?= e($u['tipo']) ?></td><td><?= e($u['placas']) ?></td><td><?= (int) $u['anio'] ?></td><td><?= e((string) $u['capacidad_max_ton']) ?> t</td><td><span class="status <?= $u['activa'] ? 'status-entregada' : 'status-cancelada' ?>"><?= $u['activa'] ? 'Activa' : 'Inactiva' ?></span></td><td class="actions-cell"><a class="text-link" href="?editar=<?= (int) $u['id'] ?>">Editar</a><form method="POST"><input type="hidden" name="token_csrf" value="<?= e(obtenerTokenCsrf()) ?>"><input type="hidden" name="accion" value="alternar"><input type="hidden" name="unidad_id" value="<?= (int) $u['id'] ?>"><button class="text-button" type="submit"><?= $u['activa'] ? 'Desactivar' : 'Activar' ?></button></form></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></section></main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
