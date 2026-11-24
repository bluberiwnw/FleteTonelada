USE fletetonelada;
SET NAMES utf8mb4;

INSERT IGNORE INTO tipos_unidad (nombre, capacidad_max_ton, tarifa_base, costo_km) VALUES
('Camioneta 3.5 t',3.50,1200.00,18.00),
('Rabón',8.00,2000.00,24.00),
('Tortón',15.00,2800.00,30.00),
('Plataforma',25.00,3500.00,36.00),
('Caja seca 53 pies',30.00,4000.00,40.00);

INSERT IGNORE INTO ciudades (nombre,estado,lat,lng) VALUES
('Puebla','Puebla',19.0414000,-98.2063000),
('Ciudad de México','Ciudad de México',19.4326000,-99.1332000),
('Toluca','Estado de México',19.2826000,-99.6557000),
('Santiago de Querétaro','Querétaro',20.5888000,-100.3899000),
('León','Guanajuato',21.1250000,-101.6860000),
('Guanajuato','Guanajuato',21.0190000,-101.2574000),
('Morelia','Michoacán',19.7008000,-101.1844000),
('Guadalajara','Jalisco',20.6597000,-103.3496000),
('Tepic','Nayarit',21.5042000,-104.8946000),
('Aguascalientes','Aguascalientes',21.8853000,-102.2916000),
('San Luis Potosí','San Luis Potosí',22.1565000,-100.9855000),
('Zacatecas','Zacatecas',22.7709000,-102.5832000),
('Durango','Durango',24.0277000,-104.6532000),
('Monterrey','Nuevo León',25.6866000,-100.3161000),
('Saltillo','Coahuila',25.4232000,-101.0053000),
('Torreón','Coahuila',25.5428000,-103.4068000),
('Chihuahua','Chihuahua',28.6353000,-106.0889000),
('Ciudad Juárez','Chihuahua',31.6904000,-106.4245000),
('Hermosillo','Sonora',29.0729000,-110.9559000),
('Culiacán','Sinaloa',24.8091000,-107.3940000),
('Mazatlán','Sinaloa',23.2494000,-106.4111000),
('Tijuana','Baja California',32.5149000,-117.0382000),
('Mexicali','Baja California',32.6245000,-115.4523000),
('La Paz','Baja California Sur',24.1426000,-110.3128000),
('Oaxaca de Juárez','Oaxaca',17.0732000,-96.7266000),
('Tuxtla Gutiérrez','Chiapas',16.7530000,-93.1150000),
('Villahermosa','Tabasco',17.9895000,-92.9475000),
('San Francisco de Campeche','Campeche',19.8301000,-90.5349000),
('Mérida','Yucatán',20.9674000,-89.5926000),
('Cancún','Quintana Roo',21.1619000,-86.8515000);

-- Contraseña demostrativa para las tres cuentas: FleteDemo2026!
-- Cambia estas credenciales antes de utilizar el sistema con datos reales.
INSERT IGNORE INTO usuarios (nombre,email,password_hash,telefono,empresa,rol,verificado,activo) VALUES
('Cliente de prueba','cliente@fletetonelada.test','$2y$12$qujHEabZvLLSv1drQJouIORvitLXZgj3oPsMtAIIgntdiuq9FvSNW','2220000001','Empresa Demo','cliente',1,1),
('Transportista de prueba','transportista@fletetonelada.test','$2y$12$qujHEabZvLLSv1drQJouIORvitLXZgj3oPsMtAIIgntdiuq9FvSNW','2220000002','Transportes Demo','transportista',1,1),
('Administrador FleteTonelada','admin@fletetonelada.test','$2y$12$qujHEabZvLLSv1drQJouIORvitLXZgj3oPsMtAIIgntdiuq9FvSNW','2220000003','FleteTonelada','admin',1,1);
