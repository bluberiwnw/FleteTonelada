<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';

$tituloPagina = 'FleteTonelada | Transporte de carga pesada';

$consultaCiudades = $pdo->query(
    "SELECT id, nombre, estado
     FROM ciudades
     ORDER BY estado, nombre"
);

$ciudades = $consultaCiudades->fetchAll();

$consultaUnidades = $pdo->query(
    "SELECT id, nombre, capacidad_max_ton
     FROM tipos_unidad
     ORDER BY capacidad_max_ton"
);

$tiposUnidad = $consultaUnidades->fetchAll();

require_once __DIR__ . '/includes/header.php';

?>

<main id="contenido">

    <!-- HERO -->

    <section class="hero">

        <div class="container hero-grid">

            <div class="hero-content">

                <span class="eyebrow">
                    TRANSPORTE DE CARGA PESADA
                </span>

                <h1>
                    Tu carga en movimiento,
                    <span>sin complicaciones.</span>
                </h1>

                <p>
                    Conectamos empresas que necesitan transportar
                    mercancía con transportistas independientes.
                    Cotiza, publica y encuentra una unidad para tu carga.
                </p>

                <div class="hero-actions">

                    <a href="#cotizador" class="button button-primary">
                        Cotizar mi flete
                    </a>

                    <a href="<?= RUTA_BASE ?>/auth/registro.php"
                       class="button button-outline">
                        Crear una cuenta
                    </a>

                </div>

                <div class="hero-features">

                    <span>✓ Cotización sencilla</span>
                    <span>✓ Seguimiento de cargas</span>
                    <span>✓ Entrega con PIN</span>

                </div>

            </div>

            <div class="hero-visual">

                <div class="visual-card">

                    <div class="visual-header">
                        <span class="status-dot"></span>
                        SERVICIO DE TRANSPORTE
                    </div>

                    <div class="route-preview">

                        <div class="route-point">
                            <span class="route-marker"></span>

                            <div>
                                <small>ORIGEN</small>
                                <strong>Puebla, México</strong>
                            </div>
                        </div>

                        <div class="route-line"></div>

                        <div class="route-point">
                            <span class="route-marker destination"></span>

                            <div>
                                <small>DESTINO</small>
                                <strong>Ciudad de México</strong>
                            </div>
                        </div>

                    </div>

                    <div class="visual-divider"></div>

                    <div class="visual-bottom">

                        <div>
                            <small>CAPACIDAD</small>
                            <strong>3.5 - 30 toneladas</strong>
                        </div>

                        <span class="truck-icon">FT</span>

                    </div>

                </div>

                <div class="floating-note">
                    <span class="floating-icon">✓</span>
                    <span>
                        <strong>FleteTonelada</strong>
                        <small>Conectando cargas y transportistas</small>
                    </span>
                </div>

            </div>

        </div>

    </section>


    <!-- BENEFICIOS -->

    <section class="benefits-strip">

        <div class="container benefits-grid">

            <div>
                <strong>3.5 - 30 t</strong>
                <span>Capacidad de transporte</span>
            </div>

            <div>
                <strong>5 tipos</strong>
                <span>Unidades disponibles</span>
            </div>

            <div>
                <strong>Un solo lugar</strong>
                <span>Cotización y seguimiento</span>
            </div>

        </div>

    </section>


    <!-- COTIZADOR -->

    <section class="section" id="cotizador">

        <div class="container">

            <div class="section-heading">

                <span class="eyebrow eyebrow-dark">
                    COTIZADOR
                </span>

                <h2>
                    Calcula el precio de tu flete
                </h2>

                <p>
                    Selecciona las ciudades, el tipo de unidad y el
                    peso de tu mercancía para conocer un precio estimado.
                </p>

            </div>

            <div class="calculator-layout">

                <div class="calculator-card">

                    <h3>Datos del transporte</h3>

                    <p class="card-description">
                        Completa la información de tu carga.
                    </p>

                    <form
                        id="cotizador-form"
                        action="<?= RUTA_BASE ?>/api/cotizar.php"
                        method="POST"
                    >

                        <div class="form-grid">

                            <div class="form-group">

                                <label for="origen_id">
                                    Ciudad de origen
                                </label>

                                <select
                                    name="origen_id"
                                    id="origen_id"
                                    required
                                >

                                    <option value="">
                                        Selecciona el origen
                                    </option>

                                    <?php foreach ($ciudades as $ciudad): ?>

                                        <option value="<?= (int) $ciudad['id'] ?>">
                                            <?= e($ciudad['nombre']) ?>,
                                            <?= e($ciudad['estado']) ?>
                                        </option>

                                    <?php endforeach; ?>

                                </select>

                            </div>


                            <div class="form-group">

                                <label for="destino_id">
                                    Ciudad de destino
                                </label>

                                <select
                                    name="destino_id"
                                    id="destino_id"
                                    required
                                >

                                    <option value="">
                                        Selecciona el destino
                                    </option>

                                    <?php foreach ($ciudades as $ciudad): ?>

                                        <option value="<?= (int) $ciudad['id'] ?>">
                                            <?= e($ciudad['nombre']) ?>,
                                            <?= e($ciudad['estado']) ?>
                                        </option>

                                    <?php endforeach; ?>

                                </select>

                            </div>


                            <div class="form-group">

                                <label for="tipo_unidad_id">
                                    Tipo de unidad
                                </label>

                                <select
                                    name="tipo_unidad_id"
                                    id="tipo_unidad_id"
                                    required
                                >

                                    <option value="">
                                        Selecciona una unidad
                                    </option>

                                    <?php foreach ($tiposUnidad as $tipo): ?>

                                        <option value="<?= (int) $tipo['id'] ?>">
                                            <?= e($tipo['nombre']) ?>
                                            (<?= e((string) $tipo['capacidad_max_ton']) ?> t)
                                        </option>

                                    <?php endforeach; ?>

                                </select>

                            </div>


                            <div class="form-group">

                                <label for="peso_ton">
                                    Peso de la carga (toneladas)
                                </label>

                                <input
                                    type="number"
                                    name="peso_ton"
                                    id="peso_ton"
                                    min="0.1"
                                    step="0.1"
                                    placeholder="Ejemplo: 10"
                                    required
                                >

                            </div>

                        </div>


                        <label class="checkbox-container">

                            <input
                                type="checkbox"
                                name="es_regreso"
                                value="1"
                            >

                            <span>
                                Considerar flete de regreso
                            </span>

                        </label>


                        <button
                            type="submit"
                            class="button button-primary button-full"
                            id="cotizar-button"
                        >
                            Calcular cotización
                        </button>

                    </form>

                </div>


                <!-- RESULTADO -->

                <div class="quote-result-card">

                    <div class="result-icon">FT</div>

                    <span class="eyebrow">
                        RESULTADO ESTIMADO
                    </span>

                    <h3>Tu cotización</h3>

                    <p id="cotizador-mensaje" aria-live="polite">
                        Completa los datos del transporte
                        para calcular tu cotización.
                    </p>

                    <div class="result-details">

                        <div>
                            <span>Distancia estimada</span>
                            <strong id="resultado-distancia">-- km</strong>
                        </div>

                        <div>
                            <span>Precio del flete</span>
                            <strong id="resultado-precio">--</strong>
                        </div>

                        <div>
                            <span>Comisión estimada</span>
                            <strong id="resultado-comision">--</strong>
                        </div>

                        <div class="result-total">
                            <span>Pago al transportista</span>
                            <strong id="resultado-transportista">--</strong>
                        </div>

                    </div>

                    <p class="result-disclaimer">
                        Cotización aproximada para fines demostrativos.
                        El precio definitivo será calculado por el servidor
                        al publicar una carga.
                    </p>

                    <a
                        href="<?= RUTA_BASE ?>/auth/registro.php"
                        class="button button-light button-full"
                    >
                        Comenzar ahora
                    </a>

                </div>

            </div>

        </div>

    </section>


    <!-- COMO FUNCIONA -->

    <section class="section section-soft" id="como-funciona">

        <div class="container">

            <div class="section-heading">

                <span class="eyebrow eyebrow-dark">
                    PROCESO
                </span>

                <h2>¿Cómo funciona FleteTonelada?</h2>

                <p>
                    Facilitamos el proceso de transporte desde
                    la cotización hasta la confirmación de entrega.
                </p>

            </div>


            <div class="steps-grid">

                <article class="step-card">

                    <span class="step-number">01</span>

                    <div class="step-icon">01</div>

                    <h3>Cotiza tu carga</h3>

                    <p>
                        Selecciona el origen, destino, peso y
                        tipo de unidad que necesitas.
                    </p>

                </article>


                <article class="step-card">

                    <span class="step-number">02</span>

                    <div class="step-icon">02</div>

                    <h3>Publica tu servicio</h3>

                    <p>
                        Registra tu carga para que los
                        transportistas puedan consultarla.
                    </p>

                </article>


                <article class="step-card">

                    <span class="step-number">03</span>

                    <div class="step-icon">03</div>

                    <h3>Encuentra transporte</h3>

                    <p>
                        Un transportista con una unidad compatible
                        puede aceptar el servicio.
                    </p>

                </article>


                <article class="step-card">

                    <span class="step-number">04</span>

                    <div class="step-icon">04</div>

                    <h3>Confirma la entrega</h3>

                    <p>
                        La entrega se valida mediante un PIN
                        de seis dígitos.
                    </p>

                </article>

            </div>

        </div>

    </section>


    <!-- TIPOS DE UNIDADES -->

    <section class="section">

        <div class="container">

            <div class="section-heading">

                <span class="eyebrow eyebrow-dark">
                    TRANSPORTE
                </span>

                <h2>Unidades para diferentes cargas</h2>

                <p>
                    Contamos con diferentes capacidades para
                    adaptarnos a las necesidades de cada servicio.
                </p>

            </div>


            <div class="units-grid">

                <?php foreach ($tiposUnidad as $tipo): ?>

                    <article class="unit-card">

                        <span class="unit-symbol">FT</span>

                        <h3><?= e($tipo['nombre']) ?></h3>

                        <p>
                            Capacidad máxima de
                            <strong>
                                <?= e((string) $tipo['capacidad_max_ton']) ?>
                                toneladas
                            </strong>.
                        </p>

                    </article>

                <?php endforeach; ?>

            </div>

        </div>

    </section>


    <!-- LLAMADO A LA ACCIÓN -->

    <section class="cta-section">

        <div class="container cta-content">

            <div>

                <span class="eyebrow">
                    COMIENZA HOY
                </span>

                <h2>
                    Haz más sencillo el transporte de tus cargas.
                </h2>

                <p>
                    Crea tu cuenta y conoce una nueva forma
                    de conectar con servicios de transporte.
                </p>

            </div>

            <a
                href="<?= RUTA_BASE ?>/auth/registro.php"
                class="button button-light"
            >
                Crear cuenta gratis
            </a>

        </div>

    </section>

</main>

<script src="<?= RUTA_BASE ?>/assets/js/cotizador.js" defer></script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
