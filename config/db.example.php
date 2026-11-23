<?php

declare(strict_types=1);

$host = 'localhost';
$database = 'fletetonelada';
$username = 'fletetonelada_app';
$password = 'REEMPLAZAR_CON_LA_CONTRASEÑA';
$charset = 'utf8mb4';

$dsn = "mysql:host={$host};dbname={$database};charset={$charset}";

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    error_log('FleteTonelada: error de conexión: ' . $e->getMessage());

    http_response_code(500);
    exit('No fue posible conectar con la base de datos.');
}
