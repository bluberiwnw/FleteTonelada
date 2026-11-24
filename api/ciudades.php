<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=300');

try {
    $ciudades = $pdo->query('SELECT id, nombre, estado FROM ciudades ORDER BY estado, nombre')->fetchAll();
    echo json_encode(['ok' => true, 'ciudades' => $ciudades], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
} catch (PDOException $e) {
    error_log('API ciudades: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => 'No fue posible consultar las ciudades.']);
}
