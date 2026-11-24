<?php

declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../config/db.php';
requiereRol('cliente');

$usuarioId = (int) $_SESSION['usuario']['id'];
$errores = [];
$ciudades = $pdo->query('SELECT id, nombre, estado FROM ciudades ORDER BY estado, nombre')->fetchAll();
$unidades = $pdo->query('SELECT id, nombre, capacidad_max_ton FROM tipos_unidad ORDER BY capacidad_max_ton')->fetchAll();
$datos = ['origen_id' => '', 'destino_id' => '', 'tipo_unidad_id' => '', 'peso_ton' => '', 'fecha_carga' => '', 'dir_origen' => '', 'dir_destino' => '', 'descripcion' => '', 'es_regreso' => '0'];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    validarTokenCsrf();
    foreach ($datos as $campo => $valor) {
        if ($campo === 'es_regreso') {
            $datos[$campo] = isset($_POST[$campo]) ? '1' : '0';
        } else {
            $datos[$campo] = is_string($_POST[$campo] ?? null) ? trim($_POST[$campo]) : '';
        }
    }

    $origenId = filter_var($datos['origen_id'], FILTER_VALIDATE_INT);
    $destinoId = filter_var($datos['destino_id'], FILTER_VALIDATE_INT);
    $unidadId = filter_var($datos['tipo_unidad_id'], FILTER_VALIDATE_INT);
    $peso = filter_var($datos['peso_ton'], FILTER_VALIDATE_FLOAT);
    $fecha = DateTime::createFromFormat('Y-m-d', $datos['fecha_carga']);

    if (!$origenId || !$destinoId || !$unidadId) $errores[] = 'Selecciona origen, destino y tipo de unidad.';
    if ($origenId && $destinoId && $origenId === $destinoId) $errores[] = 'El origen y el destino deben ser diferentes.';
    if ($peso === false || $peso <= 0 || $peso > 30) $errores[] = 'Ingresa un peso válido mayor que cero y máximo de 30 toneladas.';
    if (!$fecha || $fecha->format('Y-m-d') !== $datos['fecha_carga'] || $datos['fecha_carga'] < date('Y-m-d')) $errores[] = 'La fecha de carga debe ser hoy o una fecha futura.';
    if ($datos['dir_origen'] === '' || mb_strlen($datos['dir_origen']) > 255) $errores[] = 'Ingresa una dirección de origen de máximo 255 caracteres.';
    if ($datos['dir_destino'] === '' || mb_strlen($datos['dir_destino']) > 255) $errores[] = 'Ingresa una dirección de destino de máximo 255 caracteres.';
    if ($datos['descripcion'] === '' || mb_strlen($datos['descripcion']) > 5000) $errores[] = 'Describe la mercancía (máximo 5000 caracteres).';

    if (!$errores) {
        $qCiudad = $pdo->prepare('SELECT id, lat, lng FROM ciudades WHERE id=:id');
        $qCiudad->execute([':id' => $origenId]); $origen = $qCiudad->fetch();
        $qCiudad->execute([':id' => $destinoId]); $destino = $qCiudad->fetch();
        $qUnidad = $pdo->prepare('SELECT id, nombre, capacidad_max_ton, tarifa_base, costo_km FROM tipos_unidad WHERE id=:id');
        $qUnidad->execute([':id' => $unidadId]); $unidad = $qUnidad->fetch();
        if (!$origen || !$destino || !$unidad) {
            $errores[] = 'Los datos seleccionados no son válidos.';
        } else {
            try {
                $precio = calcularPrecio($origen, $destino, $unidad, (float) $peso, $datos['es_regreso'] === '1');
                $pdo->beginTransaction();
                $insertar = $pdo->prepare("INSERT INTO cargas (cliente_id, tipo_unidad_id, origen_id, destino_id, dir_origen, dir_destino, descripcion, peso_ton, fecha_carga, distancia_km, precio_total, comision, pago_transportista, es_regreso, estado) VALUES (:cliente,:unidad,:origen,:destino,:dir_origen,:dir_destino,:descripcion,:peso,:fecha,:distancia,:precio,:comision,:pago,:regreso,'publicada')");
                $insertar->execute([
                    ':cliente' => $usuarioId, ':unidad' => $unidadId, ':origen' => $origenId, ':destino' => $destinoId,
                    ':dir_origen' => $datos['dir_origen'], ':dir_destino' => $datos['dir_destino'], ':descripcion' => $datos['descripcion'],
                    ':peso' => $peso, ':fecha' => $datos['fecha_carga'], ':distancia' => $precio['distancia_km'],
                    ':precio' => $precio['precio_total'], ':comision' => $precio['comision'], ':pago' => $precio['pago_transportista'],
                    ':regreso' => $datos['es_regreso'] === '1' ? 1 : 0,
                ]);
                $cargaId = (int) $pdo->lastInsertId();
                $pago = $pdo->prepare("INSERT INTO pagos (carga_id, monto, estado) VALUES (:carga,:monto,'retenido')");
                $pago->execute([':carga' => $cargaId, ':monto' => $precio['precio_total']]);
                registrarHistorial($pdo, $cargaId, 'publicada', $usuarioId);
                $pdo->commit();
                establecerMensaje('success', 'La carga fue publicada. El depósito en garantía quedó simulado como retenido.');
                header('Location: ' . RUTA_BASE . '/cliente/carga.php?id=' . $cargaId);
                exit;
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                error_log('Publicación de carga: ' . $e->getMessage());
                $errores[] = $e instanceof InvalidArgumentException ? $e->getMessage() : 'No fue posible publicar la carga.';
            }
        }
    }
}

$tituloPagina = 'Publicar carga | FleteTonelada';
require_once __DIR__ . '/../includes/header.php';
?>
<main class="dashboard-page container" id="contenido">
    <div class="page-heading"><div><span class="eyebrow eyebrow-dark">NUEVO SERVICIO</span><h1>Publicar una carga</h1><p>El precio se calcula nuevamente en el servidor con las tarifas registradas.</p></div><a class="button button-ghost" href="<?= RUTA_BASE ?>/cliente/panel.php">Volver al panel</a></div>
    <section class="surface-card form-card">
        <?php if ($errores): ?><div class="alert alert-error" role="alert"><ul><?php foreach ($errores as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
        <form method="POST" class="form-stack">
            <input type="hidden" name="token_csrf" value="<?= e(obtenerTokenCsrf()) ?>">
            <div class="form-grid">
                <div class="form-field"><label for="origen_id">Ciudad de origen *</label><select id="origen_id" name="origen_id" required><option value="">Selecciona</option><?php foreach ($ciudades as $ciudad): ?><option value="<?= (int) $ciudad['id'] ?>" <?= (string) $ciudad['id'] === $datos['origen_id'] ? 'selected' : '' ?>><?= e($ciudad['nombre']) ?>, <?= e($ciudad['estado']) ?></option><?php endforeach; ?></select></div>
                <div class="form-field"><label for="destino_id">Ciudad de destino *</label><select id="destino_id" name="destino_id" required><option value="">Selecciona</option><?php foreach ($ciudades as $ciudad): ?><option value="<?= (int) $ciudad['id'] ?>" <?= (string) $ciudad['id'] === $datos['destino_id'] ? 'selected' : '' ?>><?= e($ciudad['nombre']) ?>, <?= e($ciudad['estado']) ?></option><?php endforeach; ?></select></div>
                <div class="form-field"><label for="dir_origen">Dirección exacta de recogida *</label><input id="dir_origen" name="dir_origen" maxlength="255" required value="<?= e($datos['dir_origen']) ?>"></div>
                <div class="form-field"><label for="dir_destino">Dirección exacta de entrega *</label><input id="dir_destino" name="dir_destino" maxlength="255" required value="<?= e($datos['dir_destino']) ?>"></div>
                <div class="form-field"><label for="tipo_unidad_id">Tipo de unidad *</label><select id="tipo_unidad_id" name="tipo_unidad_id" required><option value="">Selecciona</option><?php foreach ($unidades as $unidad): ?><option value="<?= (int) $unidad['id'] ?>" <?= (string) $unidad['id'] === $datos['tipo_unidad_id'] ? 'selected' : '' ?>><?= e($unidad['nombre']) ?> (máx. <?= e((string) $unidad['capacidad_max_ton']) ?> t)</option><?php endforeach; ?></select></div>
                <div class="form-field"><label for="peso_ton">Peso (toneladas) *</label><input type="number" id="peso_ton" name="peso_ton" min="0.1" max="30" step="0.1" required value="<?= e($datos['peso_ton']) ?>"></div>
                <div class="form-field"><label for="fecha_carga">Fecha de carga *</label><input type="date" id="fecha_carga" name="fecha_carga" min="<?= date('Y-m-d') ?>" required value="<?= e($datos['fecha_carga']) ?>"></div>
                <div class="form-field form-field-wide"><label for="descripcion">Descripción de la mercancía *</label><textarea id="descripcion" name="descripcion" rows="4" maxlength="5000" required><?= e($datos['descripcion']) ?></textarea></div>
            </div>
            <label class="check-row"><input type="checkbox" name="es_regreso" value="1" <?= $datos['es_regreso'] === '1' ? 'checked' : '' ?>><span>La carga contempla un posible regreso (descuento del 20 % en esta simulación).</span></label>
            <div class="notice">El depósito en garantía y su liberación son simulados. Esta aplicación no procesa pagos reales.</div>
            <button class="button button-primary" type="submit">Calcular y publicar carga</button>
        </form>
    </section>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
