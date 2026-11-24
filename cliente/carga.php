<?php

declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../config/db.php';
requiereRol('cliente');
$usuarioId = (int) $_SESSION['usuario']['id'];
$cargaId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if (!$cargaId) { http_response_code(400); exit('Identificador de carga no válido.'); }
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    validarTokenCsrf();
    $accion = $_POST['accion'] ?? '';
    if ($accion === 'cancelar') {
        try {
            $pdo->beginTransaction();
            $bloqueo = $pdo->prepare('SELECT id, estado FROM cargas WHERE id=:id AND cliente_id=:cliente FOR UPDATE');
            $bloqueo->execute([':id' => $cargaId, ':cliente' => $usuarioId]);
            $c = $bloqueo->fetch();
            if (!$c || $c['estado'] !== 'publicada') {
                throw new RuntimeException('Solo puedes cancelar una carga que siga publicada y sin asignar.');
            }
            $pdo->prepare("UPDATE cargas SET estado='cancelada' WHERE id=:id")->execute([':id' => $cargaId]);
            $pdo->prepare("UPDATE pagos SET estado='reembolsado' WHERE carga_id=:id AND estado='retenido'")->execute([':id' => $cargaId]);
            registrarHistorial($pdo, $cargaId, 'cancelada', $usuarioId);
            $pdo->commit();
            establecerMensaje('success', 'Carga cancelada y reembolso simulado registrado.');
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            establecerMensaje('error', $e instanceof RuntimeException ? $e->getMessage() : 'No fue posible cancelar la carga.');
        }
        header('Location: ' . RUTA_BASE . '/cliente/carga.php?id=' . $cargaId); exit;
    }
}

$carga = obtenerCargaPropia($pdo, $cargaId, $usuarioId);
if (!$carga) { http_response_code(404); exit('No se encontró la carga solicitada.'); }
$qTrip = $pdo->prepare("SELECT v.id AS viaje_id,v.pin_entrega,v.recogido_en,v.entregado_en,u.nombre AS transportista,u.telefono AS telefono_transportista,u.email AS email_transportista,un.placas FROM viajes v INNER JOIN usuarios u ON u.id=v.transportista_id INNER JOIN unidades un ON un.id=v.unidad_id WHERE v.carga_id=:carga LIMIT 1");
$qTrip->execute([':carga' => $cargaId]);
$viaje = $qTrip->fetch();
$qPago = $pdo->prepare('SELECT monto, estado, liberado_en FROM pagos WHERE carga_id=:id LIMIT 1');
$qPago->execute([':id' => $cargaId]);
$pago = $qPago->fetch();
$qHist = $pdo->prepare('SELECT h.estado,h.fecha,u.nombre FROM historial_estados h INNER JOIN usuarios u ON u.id=h.usuario_id WHERE h.carga_id=:id ORDER BY h.fecha,h.id');
$qHist->execute([':id' => $cargaId]);
$historial = $qHist->fetchAll();
$tituloPagina = 'Detalle de carga #' . $cargaId . ' | FleteTonelada';
require_once __DIR__ . '/../includes/header.php';
?>
<main class="dashboard-page container" id="contenido"><div class="page-heading"><div><span class="eyebrow eyebrow-dark">SEGUIMIENTO</span><h1>Detalle de carga #<?= (int) $carga['id'] ?></h1><p>Consulta el estado y la información de tu servicio.</p></div><a class="button button-ghost" href="<?= RUTA_BASE ?>/cliente/mis-cargas.php">Volver a mis cargas</a></div>
<div class="details-grid"><section class="surface-card section-card"><div class="section-row"><h2>Información del servicio</h2><span class="status status-<?= e($carga['estado']) ?>"><?= e(estadoLegible($carga['estado'])) ?></span></div><div class="detail-route"><div><small>ORIGEN</small><strong><?= e($carga['origen_nombre']) ?>, <?= e($carga['origen_estado']) ?></strong><p><?= e($carga['dir_origen']) ?></p></div><div><small>DESTINO</small><strong><?= e($carga['destino_nombre']) ?>, <?= e($carga['destino_estado']) ?></strong><p><?= e($carga['dir_destino']) ?></p></div></div><dl class="detail-list"><div><dt>Mercancía</dt><dd><?= e($carga['descripcion']) ?></dd></div><div><dt>Unidad</dt><dd><?= e($carga['unidad_nombre']) ?></dd></div><div><dt>Peso</dt><dd><?= e((string) $carga['peso_ton']) ?> toneladas</dd></div><div><dt>Fecha de carga</dt><dd><?= e($carga['fecha_carga']) ?></dd></div><div><dt>Distancia estimada</dt><dd><?= e((string) $carga['distancia_km']) ?> km</dd></div><div><dt>Precio del servicio</dt><dd><?= e(formatoDinero($carga['precio_total'])) ?></dd></div></dl>
<?php if ($viaje): ?><div class="notice notice-success"><strong>Transportista asignado:</strong> <?= e($viaje['transportista']) ?><br>Teléfono: <?= e($viaje['telefono_transportista'] ?: 'No proporcionado') ?><br>Correo: <?= e($viaje['email_transportista']) ?><br>Placas: <?= e($viaje['placas']) ?></div><div class="pin-box"><small>PIN DE ENTREGA · COMPÁRTELO CON EL TRANSPORTISTA AL RECIBIR</small><strong><?= e($viaje['pin_entrega']) ?></strong><p>El PIN solo se muestra en esta vista del cliente.</p></div><?php else: ?><div class="notice">Los teléfonos y direcciones exactas solo se comparten con el transportista después de que acepte la carga.</div><?php endif; ?>
<?php if ($carga['estado'] === 'publicada'): ?><form method="POST" class="inline-action" onsubmit="return confirm('¿Deseas cancelar esta carga? El reembolso será simulado.');"><input type="hidden" name="token_csrf" value="<?= e(obtenerTokenCsrf()) ?>"><input type="hidden" name="accion" value="cancelar"><button class="button button-danger" type="submit">Cancelar carga</button></form><?php endif; ?>
<?php if ($carga['estado'] === 'entregada'): ?><a class="button button-primary" href="<?= RUTA_BASE ?>/cliente/calificar.php?id=<?= (int) $cargaId ?>">Calificar transportista</a><?php endif; ?></section>
<aside class="surface-card section-card"><h2>Pago en garantía</h2><p class="money-highlight"><?= e(formatoDinero($pago['monto'] ?? 0)) ?></p><span class="status status-<?= e($pago['estado'] ?? 'retenido') ?>"><?= e(ucfirst($pago['estado'] ?? 'retenido')) ?></span><p class="muted top-gap">Este movimiento es una simulación académica. No se procesa dinero real.</p><h2 class="top-gap">Historial del servicio</h2><ol class="timeline"><?php foreach ($historial as $evento): ?><li><strong><?= e(estadoLegible($evento['estado'])) ?></strong><small><?= e(fechaLegible($evento['fecha'])) ?> · <?= e($evento['nombre']) ?></small></li><?php endforeach; ?></ol></aside></div></main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
