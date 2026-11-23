<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function responder(int $codigo, array $datos): never
{
    http_response_code($codigo);

    echo json_encode(
        $datos,
        JSON_UNESCAPED_UNICODE
    );

    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder(405, [
        'ok' => false,
        'mensaje' => 'Método de solicitud no permitido.'
    ]);
}

$origenId = filter_var(
    $_POST['origen_id'] ?? null,
    FILTER_VALIDATE_INT
);

$destinoId = filter_var(
    $_POST['destino_id'] ?? null,
    FILTER_VALIDATE_INT
);

$tipoUnidadId = filter_var(
    $_POST['tipo_unidad_id'] ?? null,
    FILTER_VALIDATE_INT
);

$peso = $_POST['peso_ton'] ?? null;

$esRegreso = ($_POST['es_regreso'] ?? '0') === '1';

if (
    !$origenId ||
    !$destinoId ||
    !$tipoUnidadId ||
    !is_numeric($peso) ||
    (float) $peso <= 0 ||
    (float) $peso > 30
) {
    responder(422, [
        'ok' => false,
        'mensaje' => 'Completa correctamente los datos de la cotización.'
    ]);
}

if ($origenId === $destinoId) {
    responder(422, [
        'ok' => false,
        'mensaje' => 'El origen y el destino deben ser diferentes.'
    ]);
}

try {

    // Consultar origen.

    $consulta = $pdo->prepare(
        "SELECT id, lat, lng
         FROM ciudades
         WHERE id = :id
         LIMIT 1"
    );

    $consulta->execute([
        ':id' => $origenId
    ]);

    $origen = $consulta->fetch();

    // Consultar destino.

    $consulta->execute([
        ':id' => $destinoId
    ]);

    $destino = $consulta->fetch();

    if (!$origen || !$destino) {
        responder(422, [
            'ok' => false,
            'mensaje' => 'Selecciona ciudades válidas.'
        ]);
    }

    // Consultar tipo de unidad.

    $consultaUnidad = $pdo->prepare(
        "SELECT id, nombre, capacidad_max_ton, tarifa_base, costo_km
         FROM tipos_unidad
         WHERE id = :id
         LIMIT 1"
    );

    $consultaUnidad->execute([
        ':id' => $tipoUnidadId
    ]);

    $unidad = $consultaUnidad->fetch();

    if (!$unidad) {
        responder(422, [
            'ok' => false,
            'mensaje' => 'Selecciona un tipo de unidad válido.'
        ]);
    }

    // Validar capacidad.

    $peso = (float) $peso;

    if ($peso > (float) $unidad['capacidad_max_ton']) {
        responder(422, [
            'ok' => false,
            'mensaje' => 'El peso supera la capacidad de la unidad seleccionada.'
        ]);
    }

    // Calcular distancia mediante Haversine.

    $radioTierra = 6371;

    $latOrigen = deg2rad((float) $origen['lat']);
    $latDestino = deg2rad((float) $destino['lat']);

    $diferenciaLat = $latDestino - $latOrigen;

    $diferenciaLng = deg2rad(
        (float) $destino['lng'] - (float) $origen['lng']
    );

    $a =
        sin($diferenciaLat / 2) ** 2
        +
        cos($latOrigen)
        *
        cos($latDestino)
        *
        sin($diferenciaLng / 2) ** 2;

    $a = min(1, max(0, $a));

    $c = 2 * asin(sqrt($a));

    $distanciaLinea = $radioTierra * $c;

    // Factor para aproximar la distancia por carretera.

    $distanciaKm = $distanciaLinea * 1.25;

    // Calcular precio.

    $subtotal =
        (float) $unidad['tarifa_base']
        +
        ($distanciaKm * (float) $unidad['costo_km']);

    $factorPeso =
        1 + (0.20 * ($peso / (float) $unidad['capacidad_max_ton']));

    $factorRegreso = $esRegreso ? 0.80 : 1;

    $precioTotal = $subtotal * $factorPeso * $factorRegreso;

    $comision = $precioTotal * 0.10;

    $pagoTransportista = $precioTotal - $comision;

    responder(200, [
        'ok' => true,
        'distancia_km' => round($distanciaKm, 2),
        'precio_total' => round($precioTotal, 2),
        'comision' => round($comision, 2),
        'pago_transportista' => round($pagoTransportista, 2),
        'unidad' => $unidad['nombre']
    ]);

} catch (PDOException $e) {

    error_log('Error en cotizador: ' . $e->getMessage());

    responder(500, [
        'ok' => false,
        'mensaje' => 'No fue posible calcular la cotización.'
    ]);

}

