<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/funciones.php';
require_once __DIR__ . '/config/db.php';

$ciudades = $pdo->query('SELECT id, nombre, estado, lat, lng FROM ciudades ORDER BY estado, nombre')->fetchAll();
$tiposUnidad = $pdo->query('SELECT id, nombre, capacidad_max_ton, tarifa_base, costo_km FROM tipos_unidad ORDER BY capacidad_max_ton')->fetchAll();
$tituloPagina = 'FleteTonelada | Transporte de carga pesada';
require_once __DIR__ . '/includes/header.php';
?>
<main id="contenido">
    <section class="hero">
        <div class="container hero-grid">
            <div class="hero-copy">
                <span class="eyebrow">TRANSPORTE DE CARGA PESADA · 3.5 A 30 TONELADAS</span>
                <h1>Tu carga en movimiento, <span>sin complicaciones.</span></h1>
                <p>Conectamos empresas que necesitan transportar mercancía con transportistas independientes. Cotiza, publica y da seguimiento a tus servicios desde un solo lugar.</p>
                <div class="hero-actions"><a class="button button-primary" href="#cotizador">Cotizar un flete</a><a class="button button-outline" href="<?= RUTA_BASE ?>/auth/registro.php">Crear una cuenta</a></div>
                <div class="hero-points"><span>✓ Cotización estimada</span><span>✓ Seguimiento de cargas</span><span>✓ Entrega con PIN</span></div>
            </div>
            <div class="hero-art" aria-label="Vista ilustrativa de una ruta de transporte">
                <div class="route-card"><div class="route-card-top"><span class="live-dot"></span> SERVICIO DE TRANSPORTE</div><div class="route-stop"><i class="pin-origin"></i><div><small>ORIGEN</small><strong>Puebla, Puebla</strong></div></div><div class="route-dash"></div><div class="route-stop"><i class="pin-destination"></i><div><small>DESTINO</small><strong>Ciudad de México</strong></div></div><div class="route-card-bottom"><div><small>CAPACIDAD DE CARGA</small><strong>3.5 a 30 toneladas</strong></div><b class="truck-mark">FT</b></div></div>
                <div class="floating-chip"><b>FT</b><span><strong>FleteTonelada</strong><small>Conectando cargas y transportistas</small></span></div>
            </div>
        </div>
    </section>
    <section class="stats-strip"><div class="container stats-grid"><div><strong>3.5–30 t</strong><span>Capacidad de transporte</span></div><div><strong>5 tipos</strong><span>Unidades contempladas</span></div><div><strong>Un solo lugar</strong><span>Cotización y seguimiento</span></div></div></section>
    <section class="section" id="cotizador"><div class="container"><div class="section-heading"><span class="eyebrow eyebrow-dark">COTIZADOR</span><h2>Calcula el precio de tu flete</h2><p>Selecciona las ciudades, el tipo de unidad y el peso de tu mercancía para obtener un precio estimado.</p></div>
        <div class="calculator-layout"><section class="surface-card"><h3>Datos del transporte</h3><p class="muted">Completa los datos para calcular una cotización.</p>
            <form id="cotizador-form" action="<?= RUTA_BASE ?>/api/cotizar.php" method="POST">
                <div class="form-grid">
                    <div class="form-field"><label for="origen_id">Ciudad de origen</label><select id="origen_id" name="origen_id" required><option value="">Selecciona el origen</option><?php foreach ($ciudades as $ciudad): ?><option value="<?= (int) $ciudad['id'] ?>" data-lat="<?= e((string) $ciudad['lat']) ?>" data-lng="<?= e((string) $ciudad['lng']) ?>"><?= e($ciudad['nombre']) ?>, <?= e($ciudad['estado']) ?></option><?php endforeach; ?></select></div>
                    <div class="form-field"><label for="destino_id">Ciudad de destino</label><select id="destino_id" name="destino_id" required><option value="">Selecciona el destino</option><?php foreach ($ciudades as $ciudad): ?><option value="<?= (int) $ciudad['id'] ?>" data-lat="<?= e((string) $ciudad['lat']) ?>" data-lng="<?= e((string) $ciudad['lng']) ?>"><?= e($ciudad['nombre']) ?>, <?= e($ciudad['estado']) ?></option><?php endforeach; ?></select></div>
                    <div class="form-field"><label for="tipo_unidad_id">Tipo de unidad</label><select id="tipo_unidad_id" name="tipo_unidad_id" required><option value="">Selecciona una unidad</option><?php foreach ($tiposUnidad as $tipo): ?><option value="<?= (int) $tipo['id'] ?>" data-capacidad="<?= e((string) $tipo['capacidad_max_ton']) ?>" data-base="<?= e((string) $tipo['tarifa_base']) ?>" data-km="<?= e((string) $tipo['costo_km']) ?>"><?= e($tipo['nombre']) ?> (<?= e((string) $tipo['capacidad_max_ton']) ?> t)</option><?php endforeach; ?></select></div>
                    <div class="form-field"><label for="peso_ton">Peso de la carga (toneladas)</label><input type="number" id="peso_ton" name="peso_ton" min="0.1" max="30" step="0.1" placeholder="Ej. 10" required></div>
                </div>
                <label class="check-row"><input type="checkbox" name="es_regreso" value="1"><span>Considerar flete de regreso (descuento demostrativo del 20 %)</span></label>
                <button class="button button-primary button-block" id="cotizar-button" type="submit">Calcular cotización</button>
            </form>
        </section>
        <aside class="quote-card"><div class="quote-symbol">FT</div><span class="eyebrow">RESULTADO ESTIMADO</span><h3>Tu cotización</h3><p id="cotizador-mensaje" aria-live="polite">Completa los datos del transporte para calcular el precio.</p><div class="quote-lines"><div><span>Distancia estimada</span><strong id="resultado-distancia">— km</strong></div><div><span>Precio del flete</span><strong id="resultado-precio">—</strong></div><div><span>Comisión de plataforma</span><strong id="resultado-comision">—</strong></div><div class="quote-total"><span>Pago al transportista</span><strong id="resultado-transportista">—</strong></div></div><small class="quote-disclaimer">La cotización es estimada. El servidor volverá a calcular el precio al publicar la carga.</small><a class="button button-light button-block" href="<?= RUTA_BASE ?>/auth/registro.php">Comenzar ahora</a></aside></div>
    </div></section>
    <section class="section section-soft" id="como-funciona"><div class="container"><div class="section-heading"><span class="eyebrow eyebrow-dark">PROCESO</span><h2>¿Cómo funciona FleteTonelada?</h2><p>Una ruta sencilla desde la cotización hasta la confirmación de entrega.</p></div><div class="steps-grid"><article class="step-card"><b class="step-index">01</b><div class="step-icon">01</div><h3>Cotiza tu carga</h3><p>Selecciona origen, destino, peso y tipo de unidad.</p></article><article class="step-card"><b class="step-index">02</b><div class="step-icon">02</div><h3>Publica el servicio</h3><p>Registra la carga y el depósito simulado queda retenido.</p></article><article class="step-card"><b class="step-index">03</b><div class="step-icon">03</div><h3>Asigna transporte</h3><p>Un transportista verificado acepta una carga compatible.</p></article><article class="step-card"><b class="step-index">04</b><div class="step-icon">04</div><h3>Confirma la entrega</h3><p>El receptor comparte el PIN y se simula la liberación del pago.</p></article></div></div></section>
    <section class="section"><div class="container"><div class="section-heading"><span class="eyebrow eyebrow-dark">FLOTA</span><h2>Unidades para diferentes cargas</h2><p>El catálogo contempla distintas capacidades para adaptarse al servicio.</p></div><div class="units-grid"><?php foreach ($tiposUnidad as $tipo): ?><article class="unit-card"><span class="unit-symbol">FT</span><h3><?= e($tipo['nombre']) ?></h3><p>Capacidad máxima: <strong><?= e((string) $tipo['capacidad_max_ton']) ?> toneladas</strong></p></article><?php endforeach; ?></div></div></section>
    <section class="cta-section"><div class="container cta-inner"><div><span class="eyebrow">COMIENZA HOY</span><h2>Haz más sencillo el transporte de tus cargas.</h2><p>Crea una cuenta para publicar servicios o encontrar cargas disponibles.</p></div><a class="button button-light" href="<?= RUTA_BASE ?>/auth/registro.php">Crear cuenta</a></div></section>
</main>
<script src="<?= RUTA_BASE ?>/assets/js/cotizador.js" defer></script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
