<?php

declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../config/db.php';
requiereRol('admin');
$totalUsuarios = (int) $pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();
$pendientes = (int) $pdo->query("SELECT COUNT(*) FROM usuarios WHERE rol='transportista' AND verificado=0 AND activo=1")->fetchColumn();
$totalCargas = (int) $pdo->query('SELECT COUNT(*) FROM cargas')->fetchColumn();
$activas = (int) $pdo->query("SELECT COUNT(*) FROM cargas WHERE estado IN ('publicada','asignada','en_transito')")->fetchColumn();
$comisiones = (float) $pdo->query("SELECT COALESCE(SUM(comision),0) FROM cargas WHERE estado='entregada'")->fetchColumn();
$recientes = $pdo->query("SELECT c.id,c.estado,c.fecha_carga,co.nombre AS origen,cd.nombre AS destino,u.nombre AS cliente FROM cargas c INNER JOIN ciudades co ON co.id=c.origen_id INNER JOIN ciudades cd ON cd.id=c.destino_id INNER JOIN usuarios u ON u.id=c.cliente_id ORDER BY c.creado_en DESC LIMIT 6")->fetchAll();
$tituloPagina = 'Panel administrativo | FleteTonelada';
require_once __DIR__ . '/../includes/header.php';
?>
<main class="dashboard-page container" id="contenido"><div class="page-heading"><div><span class="eyebrow eyebrow-dark">ADMINISTRACIÓN</span><h1>Panel de control</h1><p>Supervisa usuarios, servicios y métricas generales de la plataforma.</p></div><a class="button button-primary" href="<?= RUTA_BASE ?>/admin/usuarios.php">Gestionar usuarios</a></div>
<div class="dashboard-grid"><article class="metric-card"><span>Usuarios registrados</span><strong><?= $totalUsuarios ?></strong></article><article class="metric-card"><span>Transportistas pendientes</span><strong><?= $pendientes ?></strong></article><article class="metric-card"><span>Cargas registradas</span><strong><?= $totalCargas ?></strong></article><article class="metric-card"><span>Cargas activas</span><strong><?= $activas ?></strong></article><article class="metric-card metric-wide"><span>Comisiones de servicios entregados (simuladas)</span><strong class="metric-money"><?= e(formatoDinero($comisiones)) ?></strong></article></div>
<section class="surface-card section-card"><div class="section-row"><h2>Servicios recientes</h2><a class="text-link" href="<?= RUTA_BASE ?>/admin/cargas.php">Ver todos</a></div><div class="table-wrap"><table><thead><tr><th>Servicio</th><th>Cliente</th><th>Ruta</th><th>Fecha</th><th>Estado</th></tr></thead><tbody><?php foreach ($recientes as $c): ?><tr><td>#<?= (int) $c['id'] ?></td><td><?= e($c['cliente']) ?></td><td><?= e($c['origen']) ?> → <?= e($c['destino']) ?></td><td><?= e($c['fecha_carga']) ?></td><td><span class="status status-<?= e($c['estado']) ?>"><?= e(estadoLegible($c['estado'])) ?></span></td></tr><?php endforeach; ?><?php if (!$recientes): ?><tr><td colspan="5">Todavía no hay servicios registrados.</td></tr><?php endif; ?></tbody></table></div></section>
<div class="quick-links"><a class="quick-link" href="<?= RUTA_BASE ?>/admin/usuarios.php"><b>Usuarios</b><span>Verificar transportistas y administrar cuentas →</span></a><a class="quick-link" href="<?= RUTA_BASE ?>/admin/cargas.php"><b>Cargas</b><span>Consultar todos los servicios y sus estados →</span></a></div></main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
