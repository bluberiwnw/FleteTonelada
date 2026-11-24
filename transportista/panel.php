<?php

declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../config/db.php';
requiereRol('transportista');
$id = (int) $_SESSION['usuario']['id'];
$q = $pdo->prepare('SELECT COUNT(*) FROM unidades WHERE transportista_id=:id AND activa=1');
$q->execute([':id' => $id]); $unidades = (int) $q->fetchColumn();
$q = $pdo->prepare("SELECT COUNT(*) FROM viajes v INNER JOIN cargas c ON c.id=v.carga_id WHERE v.transportista_id=:id AND c.estado IN ('asignada','en_transito')");
$q->execute([':id' => $id]); $activos = (int) $q->fetchColumn();
$q = $pdo->prepare("SELECT COALESCE(SUM(c.pago_transportista),0) FROM viajes v INNER JOIN cargas c ON c.id=v.carga_id WHERE v.transportista_id=:id AND c.estado='entregada'");
$q->execute([':id' => $id]); $ganancias = (float) $q->fetchColumn();
$tituloPagina = 'Panel del transportista | FleteTonelada';
require_once __DIR__ . '/../includes/header.php';
?>
<main class="dashboard-page container" id="contenido"><div class="page-heading"><div><span class="eyebrow eyebrow-dark">PANEL DEL TRANSPORTISTA</span><h1>Hola, <?= e($_SESSION['usuario']['nombre']) ?></h1><p>Administra tus unidades y encuentra servicios compatibles.</p></div><a class="button button-primary" href="<?= RUTA_BASE ?>/transportista/disponibles.php">Ver cargas disponibles</a></div>
<?php if (!$_SESSION['usuario']['verificado']): ?><div class="notice notice-warning"><strong>Verificación pendiente.</strong> Puedes registrar tus unidades y consultar tu panel, pero no podrás aceptar cargas hasta que el administrador apruebe tu cuenta.</div><?php endif; ?>
<div class="dashboard-grid"><article class="metric-card"><span>Unidades activas</span><strong><?= $unidades ?></strong></article><article class="metric-card"><span>Viajes en curso</span><strong><?= $activos ?></strong></article><article class="metric-card"><span>Ganancias liberadas (simuladas)</span><strong class="metric-money"><?= e(formatoDinero($ganancias)) ?></strong></article></div>
<div class="quick-links"><a class="quick-link" href="<?= RUTA_BASE ?>/transportista/unidades.php"><b>Administrar unidades</b><span>Registrar, editar o desactivar vehículos →</span></a><a class="quick-link" href="<?= RUTA_BASE ?>/transportista/viajes.php"><b>Mis viajes</b><span>Actualizar estados y confirmar entregas →</span></a></div></main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
