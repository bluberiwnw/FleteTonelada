<?php

declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../config/db.php';
requiereRol('cliente');
$usuarioId = (int) $_SESSION['usuario']['id'];
$consulta = $pdo->prepare("SELECT COUNT(*) AS total, SUM(estado IN ('publicada','asignada','en_transito')) AS activas, SUM(estado = 'entregada') AS entregadas FROM cargas WHERE cliente_id = :id");
$consulta->execute([':id' => $usuarioId]);
$estadisticas = $consulta->fetch();
$recientes = $pdo->prepare("SELECT c.id, c.estado, c.fecha_carga, c.precio_total, co.nombre AS origen, cd.nombre AS destino FROM cargas c INNER JOIN ciudades co ON co.id=c.origen_id INNER JOIN ciudades cd ON cd.id=c.destino_id WHERE c.cliente_id=:id ORDER BY c.creado_en DESC LIMIT 5");
$recientes->execute([':id' => $usuarioId]);
$cargas = $recientes->fetchAll();
$tituloPagina = 'Panel del cliente | FleteTonelada';
require_once __DIR__ . '/../includes/header.php';
?>
<main class="dashboard-page container" id="contenido">
    <div class="page-heading"><div><span class="eyebrow eyebrow-dark">PANEL DEL CLIENTE</span><h1>Hola, <?= e($_SESSION['usuario']['nombre']) ?></h1><p>Administra tus servicios de transporte y consulta su avance.</p></div><a class="button button-primary" href="<?= RUTA_BASE ?>/cliente/publicar.php">+ Publicar una carga</a></div>
    <div class="dashboard-grid"><article class="metric-card"><span>Cargas registradas</span><strong><?= (int) ($estadisticas['total'] ?? 0) ?></strong></article><article class="metric-card"><span>Cargas activas</span><strong><?= (int) ($estadisticas['activas'] ?? 0) ?></strong></article><article class="metric-card"><span>Entregadas</span><strong><?= (int) ($estadisticas['entregadas'] ?? 0) ?></strong></article></div>
    <section class="surface-card section-card"><div class="section-row"><div><h2>Mis cargas recientes</h2><p class="muted">Consulta el estado de tus publicaciones.</p></div><a class="text-link" href="<?= RUTA_BASE ?>/cliente/mis-cargas.php">Ver todas</a></div>
        <?php if (!$cargas): ?><div class="empty-state"><strong>Aún no tienes cargas publicadas.</strong><p>Publica tu primer servicio para comenzar.</p><a class="button button-primary" href="<?= RUTA_BASE ?>/cliente/publicar.php">Publicar carga</a></div><?php else: ?>
            <div class="table-wrap"><table><thead><tr><th>Ruta</th><th>Fecha</th><th>Estado</th><th>Precio</th><th></th></tr></thead><tbody><?php foreach ($cargas as $carga): ?><tr><td><?= e($carga['origen']) ?> → <?= e($carga['destino']) ?></td><td><?= e($carga['fecha_carga']) ?></td><td><span class="status status-<?= e($carga['estado']) ?>"><?= e(estadoLegible($carga['estado'])) ?></span></td><td><?= e(formatoDinero($carga['precio_total'])) ?></td><td><a class="text-link" href="<?= RUTA_BASE ?>/cliente/carga.php?id=<?= (int) $carga['id'] ?>">Ver</a></td></tr><?php endforeach; ?></tbody></table></div>
        <?php endif; ?>
    </section>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
