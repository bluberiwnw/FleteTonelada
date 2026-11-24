<?php

declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../config/db.php';
requiereRol('cliente');
$q = $pdo->prepare("SELECT c.id,c.estado,c.fecha_carga,c.peso_ton,c.precio_total,co.nombre AS origen,cd.nombre AS destino,tu.nombre AS unidad FROM cargas c INNER JOIN ciudades co ON co.id=c.origen_id INNER JOIN ciudades cd ON cd.id=c.destino_id INNER JOIN tipos_unidad tu ON tu.id=c.tipo_unidad_id WHERE c.cliente_id=:id ORDER BY c.creado_en DESC");
$q->execute([':id' => (int) $_SESSION['usuario']['id']]);
$cargas = $q->fetchAll();
$tituloPagina = 'Mis cargas | FleteTonelada';
require_once __DIR__ . '/../includes/header.php';
?>
<main class="dashboard-page container" id="contenido"><div class="page-heading"><div><span class="eyebrow eyebrow-dark">CLIENTE</span><h1>Mis cargas</h1><p>Consulta los servicios que has publicado.</p></div><a class="button button-primary" href="<?= RUTA_BASE ?>/cliente/publicar.php">+ Publicar carga</a></div>
<section class="surface-card section-card"><div class="section-row"><h2>Historial de publicaciones</h2><a class="text-link" href="<?= RUTA_BASE ?>/cliente/panel.php">Volver al panel</a></div>
<?php if (!$cargas): ?><div class="empty-state"><strong>No hay cargas registradas.</strong><p>Cuando publiques una carga aparecerá en esta sección.</p></div><?php else: ?><div class="table-wrap"><table><thead><tr><th>Ruta</th><th>Unidad</th><th>Peso</th><th>Fecha</th><th>Estado</th><th>Precio</th><th></th></tr></thead><tbody><?php foreach ($cargas as $c): ?><tr><td><?= e($c['origen']) ?> → <?= e($c['destino']) ?></td><td><?= e($c['unidad']) ?></td><td><?= e((string) $c['peso_ton']) ?> t</td><td><?= e($c['fecha_carga']) ?></td><td><span class="status status-<?= e($c['estado']) ?>"><?= e(estadoLegible($c['estado'])) ?></span></td><td><?= e(formatoDinero($c['precio_total'])) ?></td><td><a class="text-link" href="<?= RUTA_BASE ?>/cliente/carga.php?id=<?= (int) $c['id'] ?>">Detalle</a></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></section></main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
