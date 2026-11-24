<?php

declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../config/db.php';
requiereRol('transportista');
$transportistaId = (int) $_SESSION['usuario']['id'];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    validarTokenCsrf();
    $viajeId = filter_var($_POST['viaje_id'] ?? null, FILTER_VALIDATE_INT);
    $accion = is_string($_POST['accion'] ?? null) ? $_POST['accion'] : '';
    try {
        if (!$viajeId) throw new RuntimeException('Viaje no válido.');
        $pdo->beginTransaction();
        $q = $pdo->prepare("SELECT v.id,v.carga_id,v.pin_entrega,v.intentos_pin,v.recogido_en,c.estado AS carga_estado FROM viajes v INNER JOIN cargas c ON c.id=v.carga_id WHERE v.id=:viaje AND v.transportista_id=:transportista FOR UPDATE");
        $q->execute([':viaje' => $viajeId, ':transportista' => $transportistaId]);
        $viaje = $q->fetch();
        if (!$viaje) throw new RuntimeException('No se encontró el viaje o no te pertenece.');

        if ($accion === 'recogida') {
            if ($viaje['carga_estado'] !== 'asignada' || $viaje['recogido_en'] !== null) throw new RuntimeException('El viaje no está listo para registrar la recogida.');
            $pdo->prepare('UPDATE viajes SET recogido_en=NOW() WHERE id=:id')->execute([':id' => $viajeId]);
            registrarHistorial($pdo, (int) $viaje['carga_id'], 'recogida', $transportistaId);
            establecerMensaje('success', 'La recogida de la mercancía quedó registrada.');
        } elseif ($accion === 'transito') {
            if ($viaje['carga_estado'] !== 'asignada' || $viaje['recogido_en'] === null) throw new RuntimeException('Primero debes registrar la recogida.');
            $pdo->prepare("UPDATE cargas SET estado='en_transito' WHERE id=:id AND estado='asignada'")->execute([':id' => $viaje['carga_id']]);
            registrarHistorial($pdo, (int) $viaje['carga_id'], 'en_transito', $transportistaId);
            establecerMensaje('success', 'El viaje ahora está en tránsito.');
        } elseif ($accion === 'entrega') {
            if ($viaje['carga_estado'] !== 'en_transito') throw new RuntimeException('El viaje debe estar en tránsito para registrar la entrega.');
            if ((int) $viaje['intentos_pin'] >= 5) throw new RuntimeException('Se agotaron los cinco intentos permitidos para el PIN.');
            $pin = is_string($_POST['pin'] ?? null) ? trim($_POST['pin']) : '';
            if (!preg_match('/^\d{6}$/', $pin) || !hash_equals((string) $viaje['pin_entrega'], $pin)) {
                $pdo->prepare('UPDATE viajes SET intentos_pin=intentos_pin+1 WHERE id=:id')->execute([':id' => $viajeId]);
                $pdo->commit();
                establecerMensaje('error', 'PIN incorrecto. Se registró un intento.');
                header('Location: ' . RUTA_BASE . '/transportista/viajes.php'); exit;
            }
            $pdo->prepare('UPDATE viajes SET entregado_en=NOW() WHERE id=:id')->execute([':id' => $viajeId]);
            $pdo->prepare("UPDATE cargas SET estado='entregada' WHERE id=:id AND estado='en_transito'")->execute([':id' => $viaje['carga_id']]);
            $pdo->prepare("UPDATE pagos SET estado='liberado',liberado_en=NOW() WHERE carga_id=:id AND estado='retenido'")->execute([':id' => $viaje['carga_id']]);
            registrarHistorial($pdo, (int) $viaje['carga_id'], 'entregada', $transportistaId);
            establecerMensaje('success', 'Entrega confirmada. El pago simulado fue liberado menos la comisión de plataforma.');
        } else {
            throw new RuntimeException('Acción no reconocida.');
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('Gestión de viaje: ' . $e->getMessage());
        establecerMensaje('error', $e instanceof RuntimeException ? $e->getMessage() : 'No fue posible actualizar el viaje.');
    }
    header('Location: ' . RUTA_BASE . '/transportista/viajes.php'); exit;
}

$q = $pdo->prepare("SELECT v.id AS viaje_id,v.recogido_en,v.entregado_en,v.intentos_pin,c.id AS carga_id,c.estado,c.fecha_carga,c.peso_ton,c.pago_transportista,co.nombre AS origen,cd.nombre AS destino,cl.nombre AS cliente,cl.telefono AS telefono_cliente,c.dir_origen,c.dir_destino,un.placas,tu.nombre AS unidad FROM viajes v INNER JOIN cargas c ON c.id=v.carga_id INNER JOIN ciudades co ON co.id=c.origen_id INNER JOIN ciudades cd ON cd.id=c.destino_id INNER JOIN usuarios cl ON cl.id=c.cliente_id INNER JOIN unidades un ON un.id=v.unidad_id INNER JOIN tipos_unidad tu ON tu.id=un.tipo_unidad_id WHERE v.transportista_id=:id ORDER BY v.asignado_en DESC");
$q->execute([':id' => $transportistaId]); $viajes = $q->fetchAll();
$tituloPagina = 'Mis viajes | FleteTonelada';
require_once __DIR__ . '/../includes/header.php';
?>
<main class="dashboard-page container" id="contenido"><div class="page-heading"><div><span class="eyebrow eyebrow-dark">OPERACIÓN</span><h1>Mis viajes</h1><p>Actualiza el estado de los servicios que aceptaste. El PIN no se muestra en esta pantalla.</p></div><a class="button button-ghost" href="<?= RUTA_BASE ?>/transportista/panel.php">Volver al panel</a></div>
<?php if (!$viajes): ?><div class="empty-state surface-card">Todavía no tienes viajes asignados. <a class="text-link" href="<?= RUTA_BASE ?>/transportista/disponibles.php">Consultar cargas disponibles</a></div><?php else: ?><div class="listing-grid"><?php foreach ($viajes as $v): ?><article class="surface-card trip-card"><div class="load-card-head"><span class="status status-<?= e($v['estado']) ?>"><?= e(estadoLegible($v['estado'])) ?></span><small>Viaje #<?= (int) $v['viaje_id'] ?></small></div><h2><?= e($v['origen']) ?> → <?= e($v['destino']) ?></h2><p class="muted">Cliente: <?= e($v['cliente']) ?> · Teléfono: <?= e($v['telefono_cliente'] ?: 'No proporcionado') ?></p><div class="notice"><strong>Recogida:</strong> <?= e($v['dir_origen']) ?><br><strong>Entrega:</strong> <?= e($v['dir_destino']) ?><br><strong>Unidad:</strong> <?= e($v['unidad']) ?> · <?= e($v['placas']) ?></div><div class="trip-meta"><span><?= e($v['fecha_carga']) ?></span><strong><?= e(formatoDinero($v['pago_transportista'])) ?></strong></div>
<?php if ($v['estado'] === 'asignada' && !$v['recogido_en']): ?><form method="POST"><input type="hidden" name="token_csrf" value="<?= e(obtenerTokenCsrf()) ?>"><input type="hidden" name="viaje_id" value="<?= (int) $v['viaje_id'] ?>"><input type="hidden" name="accion" value="recogida"><button class="button button-primary button-block" type="submit">Registrar recogida</button></form>
<?php elseif ($v['estado'] === 'asignada' && $v['recogido_en']): ?><div class="notice notice-success">Recogida registrada: <?= e(fechaLegible($v['recogido_en'])) ?></div><form method="POST"><input type="hidden" name="token_csrf" value="<?= e(obtenerTokenCsrf()) ?>"><input type="hidden" name="viaje_id" value="<?= (int) $v['viaje_id'] ?>"><input type="hidden" name="accion" value="transito"><button class="button button-primary button-block" type="submit">Marcar en tránsito</button></form>
<?php elseif ($v['estado'] === 'en_transito'): ?><form method="POST" class="form-stack"><input type="hidden" name="token_csrf" value="<?= e(obtenerTokenCsrf()) ?>"><input type="hidden" name="viaje_id" value="<?= (int) $v['viaje_id'] ?>"><input type="hidden" name="accion" value="entrega"><div class="form-field"><label for="pin-<?= (int) $v['viaje_id'] ?>">PIN de seis dígitos proporcionado por el receptor</label><input id="pin-<?= (int) $v['viaje_id'] ?>" name="pin" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required></div><small class="form-help">Intentos utilizados: <?= (int) $v['intentos_pin'] ?> de 5.</small><button class="button button-primary button-block" type="submit" <?= (int) $v['intentos_pin'] >= 5 ? 'disabled' : '' ?>>Confirmar entrega</button></form>
<?php else: ?><div class="notice notice-success">Servicio finalizado. El pago simulado fue liberado.</div><a class="button button-secondary button-block" href="<?= RUTA_BASE ?>/transportista/calificar.php?id=<?= (int) $v['carga_id'] ?>">Calificar cliente</a><?php endif; ?></article><?php endforeach; ?></div><?php endif; ?></main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
