<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';

requiereRol('cliente');

$idUsuario = $_SESSION['usuario']['id'];

$consulta = $pdo->prepare(
    "SELECT
        COUNT(*) AS total,
        SUM(estado IN ('publicada', 'asignada', 'en_transito')) AS activas
     FROM cargas
     WHERE cliente_id = :id"
);

$consulta->execute([':id' => $idUsuario]);

$estadisticas = $consulta->fetch();

$tituloPagina = 'Panel del cliente | FleteTonelada';

require_once __DIR__ . '/../includes/header.php';

?>

<main class="dashboard-page container" id="contenido">

    <div class="dashboard-heading">

        <span class="eyebrow eyebrow-dark">PANEL DEL CLIENTE</span>

        <h1>
            Bienvenido, <?= e($_SESSION['usuario']['nombre']) ?>
        </h1>

        <p>Administra y consulta tus servicios de transporte.</p>

    </div>

    <div class="dashboard-grid">

        <article class="dashboard-card">
            <span>Cargas registradas</span>
            <strong><?= (int) ($estadisticas['total'] ?? 0) ?></strong>
        </article>

        <article class="dashboard-card">
            <span>Cargas activas</span>
            <strong><?= (int) ($estadisticas['activas'] ?? 0) ?></strong>
        </article>

        <article class="dashboard-card">
            <span>Estado de la cuenta</span>
            <strong>Activa</strong>
        </article>

    </div>

    <div class="dashboard-notice">
        Tu panel está preparado. En la siguiente etapa
        incorporaremos la publicación y el seguimiento de cargas.
    </div>

    <p style="margin-top:25px;">
        <a href="<?= RUTA_BASE ?>/#cotizador" class="button button-primary">
            Cotizar un flete
        </a>
    </p>

</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
