<?php

declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../config/db.php';
requiereRol('admin');
$filtro = is_string($_GET['estado'] ?? null) ? $_GET['estado'] : '';
$estados = ['publicada','asignada','en_transito','entregada','cancelada'];
$sql = "SELECT c.id,c.estado,c.fecha_carga,c.peso_ton,c.precio_total,c.comision,co.nombre AS origen,cd.nombre AS destino,u.nombre AS cliente FROM cargas c INNER JOIN ciudades co ON co.id=c.origen_id INNER JOIN ciudades cd ON cd.id=c.destino_id INNER JOIN usuarios u ON u.id=c.cliente_id";
$params=[];
if (in_array($filtro,$estados,true)) { $sql .= ' WHERE c.estado=:estado'; $params[':estado']=$filtro; }
$sql .= ' ORDER BY c.creado_en DESC';
$q=$pdo->prepare($sql); $q->execute($params); $cargas=$q->fetchAll();
$tituloPagina='Todas las cargas | FleteTonelada';
require_once __DIR__ . '/../includes/header.php';
?>
<main class="dashboard-page container" id="contenido"><div class="page-heading"><div><span class="eyebrow eyebrow-dark">ADMINISTRACIÓN</span><h1>Todas las cargas</h1><p>Consulta el estado de los servicios registrados en la plataforma.</p></div><a class="button button-ghost" href="<?= RUTA_BASE ?>/admin/panel.php">Volver al panel</a></div><section class="surface-card form-card"><form method="GET" class="filter-form"><div class="form-field"><label for="estado">Estado del servicio</label><select id="estado" name="estado"><option value="">Todos</option><?php foreach ($estados as $estado): ?><option value="<?= e($estado) ?>" <?= $filtro===$estado?'selected':'' ?>><?= e(estadoLegible($estado)) ?></option><?php endforeach; ?></select></div><button class="button button-secondary" type="submit">Filtrar</button></form></section><section class="surface-card section-card"><div class="table-wrap"><table><thead><tr><th>ID</th><th>Cliente</th><th>Ruta</th><th>Peso</th><th>Fecha</th><th>Estado</th><th>Precio</th><th>Comisión</th></tr></thead><tbody><?php foreach ($cargas as $c): ?><tr><td>#<?= (int) $c['id'] ?></td><td><?= e($c['cliente']) ?></td><td><?= e($c['origen']) ?> → <?= e($c['destino']) ?></td><td><?= e((string) $c['peso_ton']) ?> t</td><td><?= e($c['fecha_carga']) ?></td><td><span class="status status-<?= e($c['estado']) ?>"><?= e(estadoLegible($c['estado'])) ?></span></td><td><?= e(formatoDinero($c['precio_total'])) ?></td><td><?= e(formatoDinero($c['comision'])) ?></td></tr><?php endforeach; ?><?php if (!$cargas): ?><tr><td colspan="8">No hay cargas registradas.</td></tr><?php endif; ?></tbody></table></div></section></main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
