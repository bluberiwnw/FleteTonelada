<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';

requiereRol('admin');

$totalUsuarios = (int) $pdo->query(
    "SELECT COUNT(*) FROM usuarios"
)->fetchColumn();

$totalCargas = (int) $pdo->query(
    "SELECT COUNT(*) FROM cargas"
)->fetchColumn();

$transportistasPendientes = (int) $pdo->query(
    "SELECT COUNT(*) FROM usuarios
     WHERE rol = 'transportista' AND verificado = 0 AND activo = 1"
)->fetchColumn();

$tituloPagina = 'Panel administrativo | FleteTonelada';

require_once __DIR__ . '/../includes/header.php';

?>

<main class="dashboard-page container" id="contenido">

    <div class="dashboard-heading">

        <span class="eyebrow eyebrow-dark">ADMINISTRACIÓN</span>

        <h1>
            Bienvenido, <?= e($_SESSION['usuario']['nombre']) ?>
        </h1>

        <p>Consulta el estado general de FleteTonelada.</p>

    </div>

    <div class="dashboard-grid">

        <article class="dashboard-card">
            <span>Usuarios registrados</span>
            <strong><?= $totalUsuarios ?></strong>
        </article>

        <article class="dashboard-card">
            <span>Cargas registradas</span>
            <strong><?= $totalCargas ?></strong>
        </article>

        <article class="dashboard-card">
            <span>Transportistas pendientes</span>
            <strong><?= $transportistasPendientes ?></strong>
        </article>

    </div>

    <div class="dashboard-notice">
        El panel administrativo permitirá verificar transportistas,
        gestionar usuarios y consultar las métricas de la plataforma.
    </div>

</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
