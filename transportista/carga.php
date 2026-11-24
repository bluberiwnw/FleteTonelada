<?php

declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../config/db.php';
requiereRol('transportista');
$transportistaId = (int) $_SESSION['usuario']['id'];
$cargaId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if (!$cargaId) { http_response_code(400); exit('Identificador no válido.'); }
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    validarTokenCsrf();
    if (!$_SESSION['usuario']['verificado']) {
        establecerMensaje('error', 'Tu cuenta debe ser verificada antes de aceptar cargas.');
        header('Location: ' . RUTA_BASE . '/transportista/carga.php?id=' . $cargaId); exit;
    }
    $unidadId = filter_var($_POST['unidad_id'] ?? null, FILTER_VALIDATE_INT);
    if (!$unidadId) {
        establecerMensaje('error', 'Selecciona una unidad válida.');
        header('Location: ' . RUTA_BASE . '/transportista/carga.php?id=' . $cargaId); exit;
    }
    try {
        $pdo->beginTransaction();
        $qCarga = $pdo->prepare('SELECT id,cliente_id,tipo_unidad_id,peso_ton,estado FROM cargas WHERE id=:id FOR UPDATE');
        $qCarga->execute([':id' => $cargaId]);
        $cargaBloqueada = $qCarga->fetch();
        if (!$cargaBloqueada || $cargaBloqueada['estado'] !== 'publicada') {
            throw new RuntimeException('Esta carga ya no está disponible. Otro transportista pudo aceptarla.');
        }
        $qUnidad = $pdo->prepare('SELECT un.id,un.tipo_unidad_id,un.activa,tu.capacidad_max_ton FROM unidades un INNER JOIN tipos_unidad tu ON tu.id=un.tipo_unidad_id WHERE un.id=:unidad AND un.transportista_id=:transportista FOR UPDATE');
        $qUnidad->execute([':unidad' => $unidadId, ':transportista' => $transportistaId]);
        $unidad = $qUnidad->fetch();
        if (!$unidad || !(int) $unidad['activa']) throw new RuntimeException('La unidad no existe, no te pertenece o está inactiva.');
        if ((int) $unidad['tipo_unidad_id'] !== (int) $cargaBloqueada['tipo_unidad_id'] || (float) $cargaBloqueada['peso_ton'] > (float) $unidad['capacidad_max_ton']) {
            throw new RuntimeException('La unidad seleccionada no es compatible con la carga.');
        }
        $actualizar = $pdo->prepare("UPDATE cargas SET estado='asignada' WHERE id=:id AND estado='publicada'");
        $actualizar->execute([':id' => $cargaId]);
        if ($actualizar->rowCount() !== 1) throw new RuntimeException('Otro transportista acaba de aceptar esta carga.');
        $pin = (string) random_int(100000, 999999);
        $insertar = $pdo->prepare('INSERT INTO viajes (carga_id,transportista_id,unidad_id,pin_entrega) VALUES (:carga,:transportista,:unidad,:pin)');
        $insertar->execute([':carga' => $cargaId, ':transportista' => $transportistaId, ':unidad' => $unidadId, ':pin' => $pin]);
        registrarHistorial($pdo, $cargaId, 'asignada', $transportistaId);
        $pdo->commit();
        establecerMensaje('success', 'Aceptaste la carga. Ahora puedes consultar los datos de contacto y las direcciones exactas desde Mis viajes.');
        header('Location: ' . RUTA_BASE . '/transportista/viajes.php'); exit;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('Aceptación de carga: ' . $e->getMessage());
        establecerMensaje('error', $e instanceof RuntimeException ? $e->getMessage() : 'No fue posible aceptar la carga.');
        header('Location: ' . RUTA_BASE . '/transportista/carga.php?id=' . $cargaId); exit;
    }
}

$q = $pdo->prepare("SELECT c.id,c.cliente_id,c.tipo_unidad_id,c.peso_ton,c.fecha_carga,c.distancia_km,c.pago_transportista,c.descripcion,c.estado,co.nombre AS origen,co.estado AS estado_origen,cd.nombre AS destino,cd.estado AS estado_destino,tu.nombre AS unidad FROM cargas c INNER JOIN ciudades co ON co.id=c.origen_id INNER JOIN ciudades cd ON cd.id=c.destino_id INNER JOIN tipos_unidad tu ON tu.id=c.tipo_unidad_id WHERE c.id=:id");
$q->execute([':id' => $cargaId]); $carga = $q->fetch();
if (!$carga) { http_response_code(404); exit('Carga no encontrada.'); }
$qTrip = $pdo->prepare('SELECT id FROM viajes WHERE carga_id=:carga AND transportista_id=:transportista LIMIT 1');
$qTrip->execute([':carga' => $cargaId, ':transportista' => $transportistaId]); $miViaje = $qTrip->fetch();
$contacto = null;
if ($miViaje) {
    $qContacto = $pdo->prepare('SELECT c.dir_origen,c.dir_destino,u.nombre AS cliente,u.telefono,u.email FROM cargas c INNER JOIN usuarios u ON u.id=c.cliente_id WHERE c.id=:id');
    $qContacto->execute([':id' => $cargaId]); $contacto = $qContacto->fetch();
}
$misUnidades = $pdo->prepare('SELECT un.id,un.placas,tu.nombre FROM unidades un INNER JOIN tipos_unidad tu ON tu.id=un.tipo_unidad_id WHERE un.transportista_id=:id AND un.activa=1 AND un.tipo_unidad_id=:tipo');
$misUnidades->execute([':id' => $transportistaId, ':tipo' => $carga['tipo_unidad_id']]); $unidades = $misUnidades->fetchAll();
$tituloPagina = 'Detalle de carga | FleteTonelada';
require_once __DIR__ . '/../includes/header.php';
?>
<main class="dashboard-page container" id="contenido"><div class="page-heading"><div><span class="eyebrow eyebrow-dark">DETALLE DE OPORTUNIDAD</span><h1>Carga #<?= (int) $carga['id'] ?></h1><p>Consulta la información disponible para decidir si aceptas el servicio.</p></div><a class="button button-ghost" href="<?= RUTA_BASE ?>/transportista/disponibles.php">Volver a cargas</a></div>
<div class="details-grid"><section class="surface-card section-card"><div class="section-row"><h2><?= e($carga['origen']) ?> → <?= e($carga['destino']) ?></h2><span class="status status-<?= e($carga['estado']) ?>"><?= e(estadoLegible($carga['estado'])) ?></span></div><p class="muted"><?= e($carga['estado_origen']) ?> → <?= e($carga['estado_destino']) ?></p><dl class="detail-list"><div><dt>Tipo de unidad</dt><dd><?= e($carga['unidad']) ?></dd></div><div><dt>Peso</dt><dd><?= e((string) $carga['peso_ton']) ?> toneladas</dd></div><div><dt>Fecha de carga</dt><dd><?= e($carga['fecha_carga']) ?></dd></div><div><dt>Distancia</dt><dd><?= e((string) $carga['distancia_km']) ?> km</dd></div><div><dt>Pago al transportista</dt><dd><?= e(formatoDinero($carga['pago_transportista'])) ?></dd></div><div><dt>Descripción</dt><dd><?= nl2br(e($carga['descripcion'])) ?></dd></div></dl>
<?php if ($miViaje && $contacto): ?><div class="notice notice-success"><strong>Servicio asignado a ti.</strong><br>Cliente: <?= e($contacto['cliente']) ?><br>Teléfono: <?= e($contacto['telefono'] ?: 'No proporcionado') ?><br>Correo: <?= e($contacto['email']) ?><br>Dirección de recogida: <?= e($contacto['dir_origen']) ?><br>Dirección de entrega: <?= e($contacto['dir_destino']) ?></div><?php elseif ($carga['estado'] === 'publicada'): ?><?php if (!$_SESSION['usuario']['verificado']): ?><div class="notice notice-warning">Tu cuenta aún no está verificada; no puedes aceptar cargas.</div><?php elseif (!$unidades): ?><div class="notice notice-warning">No tienes una unidad activa compatible. Registra una unidad del tipo solicitado.</div><?php else: ?><form method="POST" class="form-stack"><input type="hidden" name="token_csrf" value="<?= e(obtenerTokenCsrf()) ?>"><div class="form-field"><label for="unidad_id">Selecciona tu unidad compatible</label><select name="unidad_id" id="unidad_id" required><?php foreach ($unidades as $u): ?><option value="<?= (int) $u['id'] ?>"><?= e($u['nombre']) ?> · <?= e($u['placas']) ?></option><?php endforeach; ?></select></div><button class="button button-primary" type="submit">Aceptar carga</button></form><?php endif; ?><?php else: ?><div class="notice">Esta carga ya no está disponible para asignación.</div><?php endif; ?></section><aside class="surface-card section-card"><h2>Información de asignación</h2><p class="muted">Los datos de contacto y las direcciones exactas se revelan únicamente al transportista que obtiene la asignación.</p><div class="notice">El PIN de entrega no se muestra al transportista. El cliente lo comparte al recibir la mercancía.</div></aside></div></main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
