<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/funciones.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function responderJson(int $codigo, array $datos): never
{
    http_response_code($codigo);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    responderJson(405, ['ok' => false, 'mensaje' => 'Método no permitido.']);
}

$origenId = filter_var($_POST['origen_id'] ?? null, FILTER_VALIDATE_INT);
$destinoId = filter_var($_POST['destino_id'] ?? null, FILTER_VALIDATE_INT);
$unidadId = filter_var($_POST['tipo_unidad_id'] ?? null, FILTER_VALIDATE_INT);
$pesoEntrada = $_POST['peso_ton'] ?? null;
$esRegreso = ($_POST['es_regreso'] ?? '0') === '1';

if (!$origenId || !$destinoId || !$unidadId || !is_numeric($pesoEntrada) || (float) $pesoEntrada <= 0 || (float) $pesoEntrada > 30) {
    responderJson(422, ['ok' => false, 'mensaje' => 'Completa correctamente todos los datos de la cotización.']);
}
if ($origenId === $destinoId) {
    responderJson(422, ['ok' => false, 'mensaje' => 'El origen y el destino deben ser diferentes.']);
}

try {
    $consultaCiudad = $pdo->prepare('SELECT id, lat, lng FROM ciudades WHERE id = :id LIMIT 1');
    $consultaCiudad->execute([':id' => $origenId]);
    $origen = $consultaCiudad->fetch();
    $consultaCiudad->execute([':id' => $destinoId]);
    $destino = $consultaCiudad->fetch();

    $consultaUnidad = $pdo->prepare('SELECT id, nombre, capacidad_max_ton, tarifa_base, costo_km FROM tipos_unidad WHERE id = :id LIMIT 1');
    $consultaUnidad->execute([':id' => $unidadId]);
    $unidad = $consultaUnidad->fetch();

    if (!$origen || !$destino || !$unidad) {
        responderJson(422, ['ok' => false, 'mensaje' => 'Selecciona ciudades y una unidad válidas.']);
    }

    $resultado = calcularPrecio($origen, $destino, $unidad, (float) $pesoEntrada, $esRegreso);
    responderJson(200, ['ok' => true, 'unidad' => $unidad['nombre']] + $resultado);
} catch (InvalidArgumentException $e) {
    responderJson(422, ['ok' => false, 'mensaje' => $e->getMessage()]);
} catch (PDOException $e) {
    error_log('Cotizador FleteTonelada: ' . $e->getMessage());
    responderJson(500, ['ok' => false, 'mensaje' => 'No fue posible calcular la cotización.']);
}
