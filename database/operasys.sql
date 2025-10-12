-- ============================================
-- OperaSys - Base de Datos Completa
-- Versión: 1.0
-- Fecha: Octubre 2025
-- ============================================

-- Crear base de datos
CREATE DATABASE IF NOT EXISTS operasys 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE operasys;

-- ============================================
-- TABLA: usuarios
-- Descripción: Almacena operadores, supervisores y administradores
-- ============================================
CREATE TABLE IF NOT EXISTS usuarios (
  id INT PRIMARY KEY AUTO_INCREMENT,
  nombre_completo VARCHAR(150) NOT NULL,
  dni VARCHAR(20) UNIQUE NOT NULL,
  cargo VARCHAR(50) NOT NULL,
  password VARCHAR(255) NOT NULL,
  firma TEXT NULL COMMENT 'Firma digital en base64',
  rol ENUM('operador', 'supervisor', 'admin') DEFAULT 'operador',
  estado TINYINT(1) DEFAULT 1 COMMENT '1=activo, 0=inactivo',
  fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_dni (dni),
  INDEX idx_rol (rol),
  INDEX idx_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: equipos
-- Descripción: Catálogo de maquinaria pesada
-- ============================================
CREATE TABLE IF NOT EXISTS equipos (
  id INT PRIMARY KEY AUTO_INCREMENT,
  categoria VARCHAR(50) NOT NULL COMMENT 'Excavadora, Volquete, Tractor, etc.',
  codigo VARCHAR(20) UNIQUE NOT NULL COMMENT 'Código único del equipo',
  descripcion VARCHAR(255) NULL,
  estado TINYINT(1) DEFAULT 1 COMMENT '1=activo, 0=inactivo',
  fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_categoria (categoria),
  INDEX idx_codigo (codigo),
  INDEX idx_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: reportes
-- Descripción: Reportes diarios de operación
-- ============================================
CREATE TABLE IF NOT EXISTS reportes (
  id INT PRIMARY KEY AUTO_INCREMENT,
  usuario_id INT NOT NULL,
  equipo_id INT NOT NULL,
  fecha DATE NOT NULL,
  hora_inicio TIME NOT NULL,
  hora_fin TIME NULL,
  horas_trabajadas DECIMAL(5,2) NULL COMMENT 'Calculado automáticamente',
  actividad TEXT NOT NULL COMMENT 'Descripción del trabajo realizado',
  observaciones TEXT NULL,
  ubicacion VARCHAR(255) NULL COMMENT 'Coordenadas GPS opcionales',
  estado_sinc ENUM('pendiente', 'sincronizado') DEFAULT 'sincronizado',
  fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE RESTRICT ON UPDATE CASCADE,
  FOREIGN KEY (equipo_id) REFERENCES equipos(id) ON DELETE RESTRICT ON UPDATE CASCADE,
  INDEX idx_fecha (fecha),
  INDEX idx_usuario (usuario_id),
  INDEX idx_equipo (equipo_id),
  INDEX idx_estado_sinc (estado_sinc)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: auditoria
-- Descripción: Registro de acciones del sistema
-- ============================================
CREATE TABLE IF NOT EXISTS auditoria (
  id INT PRIMARY KEY AUTO_INCREMENT,
  usuario_id INT NOT NULL,
  accion VARCHAR(100) NOT NULL COMMENT 'login, logout, registro, crear_reporte, etc.',
  detalle TEXT NULL,
  ip_address VARCHAR(45) NULL,
  user_agent VARCHAR(255) NULL,
  fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE ON UPDATE CASCADE,
  INDEX idx_usuario (usuario_id),
  INDEX idx_accion (accion),
  INDEX idx_fecha (fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- DATOS DE PRUEBA
-- ============================================

-- Usuario Administrador
-- Contraseña: 12345678
INSERT INTO usuarios (nombre_completo, dni, cargo, password, rol, firma) 
VALUES (
  'Administrador Sistema', 
  '12345678', 
  'Administrador', 
  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 
  'admin',
  NULL
);

-- Operador de prueba
-- Contraseña: operador123
INSERT INTO usuarios (nombre_completo, dni, cargo, password, rol, firma) 
VALUES (
  'Juan Pérez García', 
  '45678901', 
  'Operador', 
  '$2y$10$CwTycUXWue0Thq9StjUM0uJ8L2Z8WzjzLpqhF2d5O.RlrZkKq/Dwu', 
  'operador',
  NULL
);

-- Supervisor de prueba
-- Contraseña: supervisor123
INSERT INTO usuarios (nombre_completo, dni, cargo, password, rol, firma) 
VALUES (
  'María López Ruiz', 
  '34567890', 
  'Supervisor', 
  '$2y$10$XnZZy8b8kpQrYdYz5vEWOuCkLhN6Y7aZMZmJqHxQqFpGKfXqGqKBi', 
  'supervisor',
  NULL
);

-- Equipos de prueba
INSERT INTO equipos (categoria, codigo, descripcion) VALUES
('Excavadora', 'EX001', 'Excavadora Caterpillar 320D'),
('Excavadora', 'EX002', 'Excavadora Komatsu PC200'),
('Volquete', 'VOL001', 'Volquete Volvo FMX 8x4'),
('Volquete', 'VOL002', 'Volquete Mercedes-Benz Actros'),
('Tractor', 'TRA001', 'Tractor John Deere 6155M'),
('Motoniveladora', 'MOT001', 'Motoniveladora Caterpillar 140M'),
('Cargador', 'CAR001', 'Cargador frontal Caterpillar 950M'),
('Retroexcavadora', 'RET001', 'Retroexcavadora JCB 3CX');

-- ============================================
-- VISTAS ÚTILES (OPCIONAL)
-- ============================================

-- Vista: Reportes con información completa
CREATE OR REPLACE VIEW vista_reportes_completos AS
SELECT 
  r.id,
  r.fecha,
  r.hora_inicio,
  r.hora_fin,
  r.horas_trabajadas,
  r.actividad,
  r.observaciones,
  r.estado_sinc,
  u.nombre_completo AS operador,
  u.dni AS operador_dni,
  u.cargo AS operador_cargo,
  e.categoria AS equipo_categoria,
  e.codigo AS equipo_codigo,
  e.descripcion AS equipo_descripcion
FROM reportes r
INNER JOIN usuarios u ON r.usuario_id = u.id
INNER JOIN equipos e ON r.equipo_id = e.id
ORDER BY r.fecha DESC, r.hora_inicio DESC;

-- Vista: Estadísticas por operador
CREATE OR REPLACE VIEW vista_estadisticas_operador AS
SELECT 
  u.id,
  u.nombre_completo,
  u.dni,
  u.cargo,
  COUNT(r.id) AS total_reportes,
  SUM(r.horas_trabajadas) AS total_horas_trabajadas,
  MIN(r.fecha) AS primer_reporte,
  MAX(r.fecha) AS ultimo_reporte
FROM usuarios u
LEFT JOIN reportes r ON u.usuario_id = r.id
WHERE u.rol = 'operador' AND u.estado = 1
GROUP BY u.id, u.nombre_completo, u.dni, u.cargo;

-- ============================================
-- PROCEDIMIENTOS ALMACENADOS (OPCIONAL)
-- ============================================

-- Procedimiento: Calcular horas trabajadas
DELIMITER $$
CREATE PROCEDURE calcular_horas_trabajadas(IN reporte_id INT)
BEGIN
  UPDATE reportes 
  SET horas_trabajadas = TIMESTAMPDIFF(MINUTE, 
    CONCAT(fecha, ' ', hora_inicio),
    CONCAT(fecha, ' ', hora_fin)
  ) / 60
  WHERE id = reporte_id AND hora_fin IS NOT NULL;
END$$
DELIMITER ;

-- ============================================
-- TRIGGERS (OPCIONAL)
-- ============================================

-- Trigger: Calcular horas trabajadas automáticamente
DELIMITER $$
CREATE TRIGGER before_update_reporte
BEFORE UPDATE ON reportes
FOR EACH ROW
BEGIN
  IF NEW.hora_fin IS NOT NULL AND NEW.hora_inicio IS NOT NULL THEN
    SET NEW.horas_trabajadas = TIMESTAMPDIFF(MINUTE, 
      CONCAT(NEW.fecha, ' ', NEW.hora_inicio),
      CONCAT(NEW.fecha, ' ', NEW.hora_fin)
    ) / 60;
  END IF;
END$$
DELIMITER ;

-- ============================================
-- INFORMACIÓN DE LA BASE DE DATOS
-- ============================================

-- Mostrar resumen
SELECT 'Base de datos OperaSys creada correctamente' AS mensaje;
SELECT 'Total de tablas:', COUNT(*) FROM information_schema.tables WHERE table_schema = 'operasys';
SELECT 'Usuarios creados:', COUNT(*) FROM usuarios;
SELECT 'Equipos registrados:', COUNT(*) FROM equipos;

-- ============================================
-- CREDENCIALES DE PRUEBA
-- ============================================
-- 
-- ADMINISTRADOR:
--   DNI: 12345678
--   Contraseña: 12345678
-- 
-- OPERADOR:
--   DNI: 45678901
--   Contraseña: operador123
-- 
-- SUPERVISOR:
--   DNI: 34567890
--   Contraseña: supervisor123
-- 
-- ============================================
