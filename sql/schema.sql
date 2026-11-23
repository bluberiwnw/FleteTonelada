CREATE DATABASE IF NOT EXISTS fletetonelada
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE fletetonelada;

SET NAMES utf8mb4;

-- ==========================================
-- 1. USUARIOS
-- ==========================================

CREATE TABLE usuarios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    telefono VARCHAR(20) DEFAULT NULL,
    empresa VARCHAR(150) DEFAULT NULL,
    rol ENUM('cliente', 'transportista', 'admin') NOT NULL DEFAULT 'cliente',
    verificado TINYINT(1) NOT NULL DEFAULT 0,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY uq_usuarios_email (email)
) ENGINE=InnoDB;

-- ==========================================
-- 2. TIPOS DE UNIDAD
-- ==========================================

CREATE TABLE tipos_unidad (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(80) NOT NULL,
    capacidad_max_ton DECIMAL(8,2) NOT NULL,
    tarifa_base DECIMAL(12,2) NOT NULL,
    costo_km DECIMAL(10,2) NOT NULL,

    UNIQUE KEY uq_tipos_unidad_nombre (nombre),

    CONSTRAINT chk_capacidad_positiva
        CHECK (capacidad_max_ton > 0),

    CONSTRAINT chk_tarifa_positiva
        CHECK (tarifa_base >= 0 AND costo_km >= 0)
) ENGINE=InnoDB;

-- ==========================================
-- 3. CIUDADES
-- ==========================================

CREATE TABLE ciudades (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(120) NOT NULL,
    estado VARCHAR(120) NOT NULL,
    lat DECIMAL(10,7) NOT NULL,
    lng DECIMAL(10,7) NOT NULL,

    UNIQUE KEY uq_ciudades_nombre_estado (nombre, estado),

    CONSTRAINT chk_ciudad_latitud
        CHECK (lat BETWEEN -90 AND 90),

    CONSTRAINT chk_ciudad_longitud
        CHECK (lng BETWEEN -180 AND 180)
) ENGINE=InnoDB;

-- ==========================================
-- 4. UNIDADES
-- ==========================================

CREATE TABLE unidades (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    transportista_id INT UNSIGNED NOT NULL,
    tipo_unidad_id INT UNSIGNED NOT NULL,
    placas VARCHAR(20) NOT NULL,
    anio SMALLINT UNSIGNED NOT NULL,
    activa TINYINT(1) NOT NULL DEFAULT 1,

    UNIQUE KEY uq_unidades_placas (placas),
    KEY idx_unidades_transportista_activa (transportista_id, activa),

    CONSTRAINT fk_unidades_transportista
        FOREIGN KEY (transportista_id)
        REFERENCES usuarios(id)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT,

    CONSTRAINT fk_unidades_tipo
        FOREIGN KEY (tipo_unidad_id)
        REFERENCES tipos_unidad(id)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT,

    CONSTRAINT chk_unidades_anio
        CHECK (anio BETWEEN 1980 AND 2100)
) ENGINE=InnoDB;

-- ==========================================
-- 5. CARGAS
-- ==========================================

CREATE TABLE cargas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT UNSIGNED NOT NULL,
    tipo_unidad_id INT UNSIGNED NOT NULL,
    origen_id INT UNSIGNED NOT NULL,
    destino_id INT UNSIGNED NOT NULL,
    dir_origen VARCHAR(255) NOT NULL,
    dir_destino VARCHAR(255) NOT NULL,
    descripcion TEXT NOT NULL,
    peso_ton DECIMAL(8,2) NOT NULL,
    fecha_carga DATE NOT NULL,
    distancia_km DECIMAL(10,2) NOT NULL,
    precio_total DECIMAL(12,2) NOT NULL,
    comision DECIMAL(12,2) NOT NULL,
    pago_transportista DECIMAL(12,2) NOT NULL,
    es_regreso TINYINT(1) NOT NULL DEFAULT 0,
    estado ENUM(
        'publicada',
        'asignada',
        'en_transito',
        'entregada',
        'cancelada'
    ) NOT NULL DEFAULT 'publicada',
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    KEY idx_cargas_cliente_estado (cliente_id, estado),
    KEY idx_cargas_estado_fecha (estado, fecha_carga),
    KEY idx_cargas_tipo_unidad (tipo_unidad_id),
    KEY idx_cargas_origen (origen_id),
    KEY idx_cargas_destino (destino_id),

    CONSTRAINT fk_cargas_cliente
        FOREIGN KEY (cliente_id)
        REFERENCES usuarios(id)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT,

    CONSTRAINT fk_cargas_tipo_unidad
        FOREIGN KEY (tipo_unidad_id)
        REFERENCES tipos_unidad(id)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT,

    CONSTRAINT fk_cargas_origen
        FOREIGN KEY (origen_id)
        REFERENCES ciudades(id)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT,

    CONSTRAINT fk_cargas_destino
        FOREIGN KEY (destino_id)
        REFERENCES ciudades(id)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT,

    CONSTRAINT chk_cargas_ciudades
        CHECK (origen_id <> destino_id),

    CONSTRAINT chk_cargas_peso
        CHECK (peso_ton > 0),

    CONSTRAINT chk_cargas_distancia
        CHECK (distancia_km > 0),

    CONSTRAINT chk_cargas_precios
        CHECK (
            precio_total >= 0
            AND comision >= 0
            AND pago_transportista >= 0
        ),

    CONSTRAINT chk_cargas_regreso
        CHECK (es_regreso IN (0, 1))
) ENGINE=InnoDB;

-- ==========================================
-- 6. VIAJES
-- ==========================================

CREATE TABLE viajes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    carga_id INT UNSIGNED NOT NULL,
    transportista_id INT UNSIGNED NOT NULL,
    unidad_id INT UNSIGNED NOT NULL,
    pin_entrega CHAR(6) NOT NULL,
    intentos_pin TINYINT UNSIGNED NOT NULL DEFAULT 0,
    asignado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    recogido_en DATETIME DEFAULT NULL,
    entregado_en DATETIME DEFAULT NULL,

    UNIQUE KEY uq_viajes_carga (carga_id),
    KEY idx_viajes_transportista (transportista_id),
    KEY idx_viajes_unidad (unidad_id),

    CONSTRAINT fk_viajes_carga
        FOREIGN KEY (carga_id)
        REFERENCES cargas(id)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT,

    CONSTRAINT fk_viajes_transportista
        FOREIGN KEY (transportista_id)
        REFERENCES usuarios(id)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT,

    CONSTRAINT fk_viajes_unidad
        FOREIGN KEY (unidad_id)
        REFERENCES unidades(id)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT,

    CONSTRAINT chk_viajes_intentos
        CHECK (intentos_pin <= 5)
) ENGINE=InnoDB;

-- ==========================================
-- 7. PAGOS
-- ==========================================

CREATE TABLE pagos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    carga_id INT UNSIGNED NOT NULL,
    monto DECIMAL(12,2) NOT NULL,
    estado ENUM(
        'retenido',
        'liberado',
        'reembolsado'
    ) NOT NULL DEFAULT 'retenido',
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    liberado_en DATETIME DEFAULT NULL,

    UNIQUE KEY uq_pagos_carga (carga_id),

    CONSTRAINT fk_pagos_carga
        FOREIGN KEY (carga_id)
        REFERENCES cargas(id)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT,

    CONSTRAINT chk_pagos_monto
        CHECK (monto >= 0)
) ENGINE=InnoDB;

-- ==========================================
-- 8. CALIFICACIONES
-- ==========================================

CREATE TABLE calificaciones (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    carga_id INT UNSIGNED NOT NULL,
    autor_id INT UNSIGNED NOT NULL,
    evaluado_id INT UNSIGNED NOT NULL,
    estrellas TINYINT UNSIGNED NOT NULL,
    comentario TEXT DEFAULT NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY uq_calificacion_autor (carga_id, autor_id),
    KEY idx_calificaciones_evaluado (evaluado_id),

    CONSTRAINT fk_calificaciones_carga
        FOREIGN KEY (carga_id)
        REFERENCES cargas(id)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT,

    CONSTRAINT fk_calificaciones_autor
        FOREIGN KEY (autor_id)
        REFERENCES usuarios(id)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT,

    CONSTRAINT fk_calificaciones_evaluado
        FOREIGN KEY (evaluado_id)
        REFERENCES usuarios(id)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT,

    CONSTRAINT chk_calificaciones_estrellas
        CHECK (estrellas BETWEEN 1 AND 5),

    CONSTRAINT chk_calificaciones_usuarios
        CHECK (autor_id <> evaluado_id)
) ENGINE=InnoDB;

-- ==========================================
-- 9. HISTORIAL DE ESTADOS
-- ==========================================

CREATE TABLE historial_estados (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    carga_id INT UNSIGNED NOT NULL,
    estado ENUM(
        'publicada',
        'asignada',
        'recogida',
        'en_transito',
        'entregada',
        'cancelada'
    ) NOT NULL,
    usuario_id INT UNSIGNED NOT NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    KEY idx_historial_carga_fecha (carga_id, fecha),

    CONSTRAINT fk_historial_carga
        FOREIGN KEY (carga_id)
        REFERENCES cargas(id)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT,

    CONSTRAINT fk_historial_usuario
        FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT
) ENGINE=InnoDB;
