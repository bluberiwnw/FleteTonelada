<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';

requiereRol('transportista');

$idUsuario = $_SESSION['usuario']['id'];

$consulta = $pdo->prepare(
    "SELECT COUNT(*) FROM unidades
     WHERE transportista_id = :id"
);

$consulta->execute([':id' => $idUsuario]);

$totalUnidades = (int) $consulta->fetchColumn();

$consulta = $pdo->prepare(
    "SELECT COUNT(*) FROM viajes
     WHERE transportista_id = :id"
);

$consulta->execute([':id' => $idUsuario]);

$totalViajes = (int) $consulta->fetchColumn();

$verificado = $_SESSION['usuario']['verificado'];

$tituloPagina = 'Panel del transportista | FleteTonelada';

require_once __DIR__ . '/../includes/header.php';

?>

<main class="dashboard-page container" id="contenido">

    <div class="dashboard-heading">

        <span class="eyebrow eyebrow-dark">PANEL DEL TRANSPORTISTA</span>

        <h1>
            Bienvenido, <?= e($_SESSION['usuario']['nombre']) ?>
        </h1>

        <p>Consulta tus unidades y administra tus servicios de transporte.</p>

    </div>

    <div class="dashboard-grid">

        <article class="dashboard-card">
            <span>Mis unidades</span>
            <strong><?= $totalUnidades ?></strong>
        </article>

        <article class="dashboard-card">
            <span>Viajes registrados</span>
            <strong><?= $totalViajes ?></strong>
        </article>

        <article class="dashboard-card">
            <span>Verificación</span>
            <strong><?= $verificado ? 'Aprobado' : 'Pendiente' ?></strong>
        </article>

    </div>

    <?php if (!$verificado): ?>

        <div class="dashboard-notice">
            Tu cuenta está pendiente de verificación por el administrador.
            Podrás aceptar cargas cuando tu cuenta sea aprobada.
        </div>

    <?php else: ?>

        <div class="dashboard-notice">
            Tu cuenta está verificada. Próximamente podrás consultar
            las cargas disponibles y administrar tus viajes.
        </div>

    <?php endif; ?>

</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
