<?php

declare(strict_types=1);

/** Calcula la distancia aproximada por carretera y la distribución del precio. */
function calcularPrecio(array $origen, array $destino, array $unidad, float $peso, bool $esRegreso = false): array
{
    $capacidad = (float) $unidad['capacidad_max_ton'];
    if ($peso <= 0 || $peso > $capacidad) {
        throw new InvalidArgumentException('El peso debe ser mayor que cero y no superar la capacidad de la unidad.');
    }

    $radioTierra = 6371.0;
    $latOrigen = deg2rad((float) $origen['lat']);
    $latDestino = deg2rad((float) $destino['lat']);
    $dLat = $latDestino - $latOrigen;
    $dLng = deg2rad((float) $destino['lng'] - (float) $origen['lng']);

    $a = sin($dLat / 2) ** 2
        + cos($latOrigen) * cos($latDestino) * sin($dLng / 2) ** 2;
    $a = min(1.0, max(0.0, $a));
    $distanciaKm = 2 * $radioTierra * asin(sqrt($a)) * 1.25;

    if ($distanciaKm <= 0) {
        throw new InvalidArgumentException('El origen y el destino deben ser diferentes.');
    }

    $subtotal = (float) $unidad['tarifa_base'] + $distanciaKm * (float) $unidad['costo_km'];
    $factorPeso = 1 + 0.20 * ($peso / $capacidad);
    $factorRegreso = $esRegreso ? 0.80 : 1.0;
    $precio = $subtotal * $factorPeso * $factorRegreso;
    $comision = $precio * 0.10;

    return [
        'distancia_km' => round($distanciaKm, 2),
        'precio_total' => round($precio, 2),
        'comision' => round($comision, 2),
        'pago_transportista' => round($precio - $comision, 2),
    ];
}

function formatoDinero(float|int|string|null $cantidad): string
{
    return '$' . number_format((float) ($cantidad ?? 0), 2, '.', ',') . ' MXN';
}

function estadoLegible(string $estado): string
{
    $estados = [
        'publicada' => 'Publicada',
        'asignada' => 'Asignada',
        'recogida' => 'Mercancía recogida',
        'en_transito' => 'En tránsito',
        'entregada' => 'Entregada',
        'cancelada' => 'Cancelada',
    ];
    return $estados[$estado] ?? ucfirst(str_replace('_', ' ', $estado));
}

function fechaLegible(?string $fecha): string
{
    if (!$fecha) {
        return '—';
    }
    $timestamp = strtotime($fecha);
    return $timestamp ? date('d/m/Y H:i', $timestamp) : $fecha;
}

function registrarHistorial(PDO $pdo, int $cargaId, string $estado, int $usuarioId): void
{
    $consulta = $pdo->prepare(
        'INSERT INTO historial_estados (carga_id, estado, usuario_id) VALUES (:carga, :estado, :usuario)'
    );
    $consulta->execute([
        ':carga' => $cargaId,
        ':estado' => $estado,
        ':usuario' => $usuarioId,
    ]);
}

function obtenerCargaPropia(PDO $pdo, int $cargaId, int $clienteId): array|false
{
    $consulta = $pdo->prepare(
        "SELECT c.*, co.nombre AS origen_nombre, co.estado AS origen_estado,
                cd.nombre AS destino_nombre, cd.estado AS destino_estado,
                tu.nombre AS unidad_nombre
         FROM cargas c
         INNER JOIN ciudades co ON co.id = c.origen_id
         INNER JOIN ciudades cd ON cd.id = c.destino_id
         INNER JOIN tipos_unidad tu ON tu.id = c.tipo_unidad_id
         WHERE c.id = :carga AND c.cliente_id = :cliente LIMIT 1"
    );
    $consulta->execute([':carga' => $cargaId, ':cliente' => $clienteId]);
    return $consulta->fetch();
}
