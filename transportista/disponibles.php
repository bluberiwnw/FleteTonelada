<?php

declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../config/db.php';
requiereRol('transportista');
$id = (int) $_SESSION['usuario']['id'];
$ciudadFiltro = filter_var($_GET['origen_id'] ?? null, FILTER_VALIDATE_INT) ?: null;
$tipoFiltro = filter_var($_GET['tipo_unidad_id'] ?? null, FILTER_VALIDATE_INT) ?: null;
$ciudades = $pdo->query('SELECT id,nombre FROM ciudades ORDER BY nombre')->fetchAll();
$tipos = $pdo->query('SELECT id,nombre FROM tipos_unidad ORDER BY capacidad_max_ton')->fetchAll();
$sql = "SELECT c.id,c.peso_ton,c.fecha_carga,c.distancia_km,c.pago_transportista,c.es_regreso,co.nombre AS origen,co.estado AS estado_origen,cd.nombre AS destino,cd.estado AS estado_destino,tu.nombre AS unidad FROM cargas c INNER JOIN ciudades co ON co.id=c.origen_id INNER JOIN ciudades cd ON cd.id=c.destino_id INNER JOIN tipos_unidad tu ON tu.id=c.tipo_unidad_id WHERE c.estado='publicada' AND c.fecha_carga >= CURDATE() AND EXISTS (SELECT 1 FROM unidades un WHERE un.transportista_id=:transportista AND un.tipo_unidad_id=c.tipo_unidad_id AND un.activa=1)";
$params = [':transportista' => $id];
if ($ciudadFiltro) { $sql .= ' AND c.origen_id=:origen'; $params[':origen'] = $ciudadFiltro; }
if ($tipoFiltro) { $sql .= ' AND c.tipo_unidad_id=:tipo'; $params[':tipo'] = $tipoFiltro; }
$sql .= ' ORDER BY c.fecha_carga,c.creado_en';
$q = $pdo->prepare($sql); $q->execute($params); $cargas = $q->fetchAll();
$tituloPagina = 'Cargas disponibles | FleteTonelada';
require_once __DIR__ . '/../includes/header.php';
?>
<main class="dashboard-page container" id="contenido"><div class="page-heading"><div><span class="eyebrow eyebrow-dark">OPORTUNIDADES</span><h1>Cargas disponibles</h1><p>Se muestran las cargas que coinciden con tus unidades activas. No se muestran datos de contacto antes de asignar.</p></div><a class="button button-ghost" href="<?= RUTA_BASE ?>/transportista/panel.php">Volver al panel</a></div>
<?php if (!$_SESSION['usuario']['verificado']): ?><div class="notice notice-warning">Tu cuenta aún no está verificada. Puedes consultar las cargas, pero no aceptarlas hasta recibir aprobación del administrador.</div><?php elseif (!$cargas && !$ciudadFiltro && !$tipoFiltro): ?><div class="notice">No tienes unidades activas compatibles. Registra una unidad para consultar cargas.</div><?php endif; ?>
<section class="surface-card form-card"><form method="GET" class="filter-form"><div class="form-field"><label for="origen_id">Filtrar por origen</label><select name="origen_id" id="origen_id"><option value="">Todas las ciudades</option><?php foreach ($ciudades as $c): ?><option value="<?= (int) $c['id'] ?>" <?= $ciudadFiltro === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['nombre']) ?></option><?php endforeach; ?></select></div><div class="form-field"><label for="tipo_unidad_id">Tipo de unidad</label><select name="tipo_unidad_id" id="tipo_unidad_id"><option value="">Todas mis unidades</option><?php foreach ($tipos as $t): ?><option value="<?= (int) $t['id'] ?>" <?= $tipoFiltro === (int) $t['id'] ? 'selected' : '' ?>><?= e($t['nombre']) ?></option><?php endforeach; ?></select></div><button class="button button-secondary" type="submit">Filtrar</button><a class="text-link" href="<?= RUTA_BASE ?>/transportista/disponibles.php">Limpiar</a></form></section>
<?php if (!$cargas): ?><div class="empty-state surface-card">No hay cargas disponibles para los filtros seleccionados.</div><?php else: ?><div class="listing-grid"><?php foreach ($cargas as $c): ?><article class="surface-card load-card"><div class="load-card-head"><span class="status status-publicada">Publicada</span><small>#<?= (int) $c['id'] ?></small></div><h2><?= e($c['origen']) ?> <span>→</span> <?= e($c['destino']) ?></h2><p class="muted"><?= e($c['estado_origen']) ?> → <?= e($c['estado_destino']) ?></p><div class="load-facts"><span><small>Unidad</small><strong><?= e($c['unidad']) ?></strong></span><span><small>Peso</small><strong><?= e((string) $c['peso_ton']) ?> t</strong></span><span><small>Fecha</small><strong><?= e($c['fecha_carga']) ?></strong></span><span><small>Distancia</small><strong><?= e((string) $c['distancia_km']) ?> km</strong></span></div><div class="load-card-bottom"><div><small>Pago estimado</small><strong><?= e(formatoDinero($c['pago_transportista'])) ?></strong></div><a class="button button-primary button-small" href="<?= RUTA_BASE ?>/transportista/carga.php?id=<?= (int) $c['id'] ?>">Ver detalle</a></div></article><?php endforeach; ?></div><?php endif; ?></main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
