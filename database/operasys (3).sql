-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 15-10-2025 a las 02:22:42
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `operasys`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `auditoria`
--

CREATE TABLE `auditoria` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `accion` varchar(100) NOT NULL COMMENT 'login, logout, registro, crear_reporte, etc.',
  `detalle` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `auditoria`
--

INSERT INTO `auditoria` (`id`, `usuario_id`, `accion`, `detalle`, `ip_address`, `user_agent`, `fecha`) VALUES
(26, 1, 'login', 'Inicio de sesión exitoso', NULL, NULL, '2025-10-13 19:38:53'),
(27, 1, 'login', 'Inicio de sesión exitoso', NULL, NULL, '2025-10-13 19:38:53'),
(28, 1, 'firma_capturada', 'Firma digital guardada correctamente', NULL, NULL, '2025-10-13 19:39:14'),
(29, 1, 'login', 'Inicio de sesión exitoso', NULL, NULL, '2025-10-14 01:13:48'),
(30, 1, 'login', 'Inicio de sesión exitoso', NULL, NULL, '2025-10-14 01:13:48'),
(31, 1, 'editar_equipo', 'Equipo actualizado: RET001', NULL, NULL, '2025-10-14 01:30:43'),
(32, 1, 'editar_equipo', 'Equipo actualizado: RET001', NULL, NULL, '2025-10-14 01:30:51'),
(33, 1, 'eliminar_equipo', 'Equipo ID: 1', NULL, NULL, '2025-10-14 01:31:39'),
(34, 1, 'crear_equipo', 'Equipo creado: EX001', NULL, NULL, '2025-10-14 01:33:01'),
(35, 1, 'login', 'Inicio de sesión exitoso', NULL, NULL, '2025-10-14 07:47:29'),
(36, 1, 'login', 'Inicio de sesión exitoso', NULL, NULL, '2025-10-14 07:47:29'),
(37, 4, 'login', 'Inicio de sesión exitoso', NULL, NULL, '2025-10-14 08:54:58'),
(38, 4, 'login', 'Inicio de sesión exitoso', NULL, NULL, '2025-10-14 08:54:58'),
(39, 4, 'crear_reporte', 'Reporte ID: 1 creado', NULL, NULL, '2025-10-14 09:35:03'),
(40, 4, 'crear_reporte', 'Reporte ID: 2 creado', NULL, NULL, '2025-10-14 09:42:40'),
(41, 4, 'finalizar_reporte', 'Reporte ID: 1 finalizado', NULL, NULL, '2025-10-14 09:45:44'),
(42, 3, 'login', 'Inicio de sesión exitoso', NULL, NULL, '2025-10-14 09:54:52'),
(43, 3, 'login', 'Inicio de sesión exitoso', NULL, NULL, '2025-10-14 09:54:52'),
(44, 3, 'firma_capturada', 'Firma digital guardada correctamente', NULL, NULL, '2025-10-14 09:55:02'),
(45, 4, 'eliminar_reporte', 'Reporte ID: 2 eliminado (sin actividades)', NULL, NULL, '2025-10-14 10:25:29'),
(46, 4, 'crear_reporte', 'Reporte ID: 3 creado', NULL, NULL, '2025-10-14 10:25:58'),
(47, 4, 'eliminar_reporte', 'Reporte ID: 3 eliminado (sin actividades)', NULL, NULL, '2025-10-14 10:26:30'),
(48, 1, 'editar_tipo_trabajo', 'Tipo de trabajo actualizado: Acarreo de material', NULL, NULL, '2025-10-14 10:42:50'),
(49, 1, 'editar_tipo_trabajo', 'Tipo de trabajo actualizado: Acarreo de material', NULL, NULL, '2025-10-14 10:42:59'),
(50, 1, 'eliminar_fase_costo', 'Fase de costo ID: 9', NULL, NULL, '2025-10-14 10:43:11'),
(51, 1, 'editar_fase_costo', 'Fase de costo actualizada: FC060', NULL, NULL, '2025-10-14 10:43:21'),
(52, 1, 'editar_fase_costo', 'Fase de costo actualizada: FC060', NULL, NULL, '2025-10-14 10:43:28'),
(53, 3, 'logout', 'Cierre de sesión', NULL, NULL, '2025-10-14 10:44:50'),
(54, 1, 'login', 'Inicio de sesión exitoso', NULL, NULL, '2025-10-14 12:10:00'),
(55, 1, 'login', 'Inicio de sesión exitoso', NULL, NULL, '2025-10-14 12:10:00'),
(56, 1, 'login', 'Inicio de sesión exitoso', NULL, NULL, '2025-10-14 12:11:22'),
(57, 1, 'login', 'Inicio de sesión exitoso', NULL, NULL, '2025-10-14 12:11:22'),
(58, 1, 'login', 'Inicio de sesión exitoso', NULL, NULL, '2025-10-14 12:20:16'),
(59, 1, 'login', 'Inicio de sesión exitoso', NULL, NULL, '2025-10-14 12:20:16'),
(60, 4, 'login', 'Inicio de sesión exitoso', NULL, NULL, '2025-10-14 14:14:17'),
(61, 4, 'login', 'Inicio de sesión exitoso', NULL, NULL, '2025-10-14 14:14:17'),
(62, 1, 'logout', 'Cierre de sesión', NULL, NULL, '2025-10-14 14:25:21'),
(63, 1, 'login', 'Inicio de sesión exitoso', NULL, NULL, '2025-10-14 14:27:39'),
(64, 1, 'login', 'Inicio de sesión exitoso', NULL, NULL, '2025-10-14 14:27:39'),
(65, 1, 'login', 'Inicio de sesión exitoso', NULL, NULL, '2025-10-14 14:41:12'),
(66, 1, 'login', 'Inicio de sesión exitoso', NULL, NULL, '2025-10-14 14:41:12'),
(67, 4, 'login', 'Inicio de sesión exitoso', NULL, NULL, '2025-10-14 15:07:34'),
(68, 4, 'login', 'Inicio de sesión exitoso', NULL, NULL, '2025-10-14 15:07:34'),
(69, 4, 'login', 'Inicio de sesión exitoso', NULL, NULL, '2025-10-14 15:14:20'),
(70, 4, 'login', 'Inicio de sesión exitoso', NULL, NULL, '2025-10-14 15:14:20'),
(71, 4, 'login', 'Inicio de sesión exitoso', NULL, NULL, '2025-10-14 15:15:54'),
(72, 4, 'login', 'Inicio de sesión exitoso', NULL, NULL, '2025-10-14 15:15:54'),
(73, 4, 'login', 'Inicio de sesión exitoso', NULL, NULL, '2025-10-14 15:18:19'),
(74, 4, 'login', 'Inicio de sesión exitoso', NULL, NULL, '2025-10-14 15:18:19'),
(75, 4, 'login', 'Inicio de sesión exitoso', NULL, NULL, '2025-10-14 16:02:07'),
(76, 4, 'login', 'Inicio de sesión exitoso', NULL, NULL, '2025-10-14 16:02:07'),
(77, 3, 'login', 'Inicio de sesión exitoso', NULL, NULL, '2025-10-14 16:06:23'),
(78, 3, 'login', 'Inicio de sesión exitoso', NULL, NULL, '2025-10-14 16:06:23'),
(79, 4, 'logout', 'Cierre de sesión', NULL, NULL, '2025-10-14 16:22:57'),
(80, 4, 'login', 'Inicio de sesión exitoso', NULL, NULL, '2025-10-14 16:23:03'),
(81, 4, 'login', 'Inicio de sesión exitoso', NULL, NULL, '2025-10-14 16:23:03'),
(82, 4, 'logout', 'Cierre de sesión', NULL, NULL, '2025-10-14 16:25:06'),
(83, 4, 'login', 'Inicio de sesión exitoso', NULL, NULL, '2025-10-14 16:25:11'),
(84, 4, 'login', 'Inicio de sesión exitoso', NULL, NULL, '2025-10-14 16:25:11'),
(85, 1, 'cambiar_estado_usuario', 'Usuario ID 4 desactivado', NULL, NULL, '2025-10-14 16:44:45'),
(86, 4, 'logout', 'Cierre de sesión', NULL, NULL, '2025-10-14 16:44:54'),
(87, 1, 'cambiar_estado_usuario', 'Usuario ID 4 activado', NULL, NULL, '2025-10-14 16:45:18'),
(88, 4, 'login', 'Inicio de sesión exitoso', NULL, NULL, '2025-10-14 16:45:27'),
(89, 4, 'login', 'Inicio de sesión exitoso', NULL, NULL, '2025-10-14 16:45:27'),
(90, 3, 'login', 'Inicio de sesión exitoso', NULL, NULL, '2025-10-14 18:38:22'),
(91, 3, 'login', 'Inicio de sesión exitoso', NULL, NULL, '2025-10-14 18:38:22'),
(92, 3, 'crear_reporte', 'Reporte ID: 4 creado', NULL, NULL, '2025-10-14 18:38:29'),
(93, 4, 'login', 'Inicio de sesión exitoso', NULL, NULL, '2025-10-14 18:40:09'),
(94, 4, 'login', 'Inicio de sesión exitoso', NULL, NULL, '2025-10-14 18:40:09'),
(95, 3, 'finalizar_reporte', 'Reporte ID: 4 finalizado', NULL, NULL, '2025-10-14 18:42:09'),
(96, 1, 'login', 'Inicio de sesión exitoso', NULL, NULL, '2025-10-14 23:42:42'),
(97, 1, 'login', 'Inicio de sesión exitoso', NULL, NULL, '2025-10-14 23:42:42');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `configuracion_empresa`
--

CREATE TABLE `configuracion_empresa` (
  `id` int(11) NOT NULL,
  `nombre_empresa` varchar(150) DEFAULT NULL,
  `ruc_nit` varchar(50) DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `telefono` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `logo` text DEFAULT NULL,
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `configuracion_empresa`
--

INSERT INTO `configuracion_empresa` (`id`, `nombre_empresa`, `ruc_nit`, `direccion`, `telefono`, `email`, `logo`, `fecha_actualizacion`) VALUES
(1, 'OperaSys S.A.C', '2015486635', 'San Isidro 725 - Lima - Perú', '982226835', 'nilson.jhonny@gmail.com', 'data:image/png;base64,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', '2025-10-14 13:12:36');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `equipos`
--

CREATE TABLE `equipos` (
  `id` int(11) NOT NULL,
  `categoria` varchar(50) NOT NULL COMMENT 'Excavadora, Volquete, Tractor, etc.',
  `codigo` varchar(20) NOT NULL COMMENT 'Código único del equipo',
  `descripcion` varchar(255) DEFAULT NULL,
  `estado` tinyint(1) DEFAULT 1 COMMENT '1=activo, 0=inactivo',
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `equipos`
--

INSERT INTO `equipos` (`id`, `categoria`, `codigo`, `descripcion`, `estado`, `fecha_creacion`) VALUES
(2, 'Excavadora', 'EX002', 'Excavadora Komatsu PC200', 1, '2025-10-12 22:43:53'),
(3, 'Volquete', 'VOL001', 'Volquete Volvo FMX 8x4', 1, '2025-10-12 22:43:53'),
(4, 'Volquete', 'VOL002', 'Volquete Mercedes-Benz Actros', 1, '2025-10-12 22:43:53'),
(5, 'Tractor', 'TRA001', 'Tractor John Deere 6155M', 1, '2025-10-12 22:43:53'),
(6, 'Motoniveladora', 'MOT001', 'Motoniveladora Caterpillar 140M', 1, '2025-10-12 22:43:53'),
(7, 'Cargador', 'CAR001', 'Cargador frontal Caterpillar 950M', 1, '2025-10-12 22:43:53'),
(8, 'Retroexcavadora', 'RET001', 'Retroexcavadora JCB 3CX', 1, '2025-10-12 22:43:53'),
(9, 'Excavadora', 'EX001', 'Excavadora CAT 320', 1, '2025-10-14 01:33:01');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `fases_costo`
--

CREATE TABLE `fases_costo` (
  `id` int(11) NOT NULL,
  `codigo` varchar(20) NOT NULL COMMENT 'FC001, FC025, etc.',
  `descripcion` varchar(255) NOT NULL,
  `proyecto` varchar(100) DEFAULT NULL COMMENT 'Proyecto asociado (opcional)',
  `estado` tinyint(1) DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `fases_costo`
--

INSERT INTO `fases_costo` (`id`, `codigo`, `descripcion`, `proyecto`, `estado`, `fecha_creacion`) VALUES
(1, 'FC001', 'Movimiento de tierra - Fase 1', 'Proyecto Norte', 1, '2025-10-14 08:11:58'),
(2, 'FC002', 'Movimiento de tierra - Fase 2', 'Proyecto Norte', 1, '2025-10-14 08:11:58'),
(3, 'FC025', 'Acarreo de material - Zona A', 'Proyecto Norte', 1, '2025-10-14 08:11:58'),
(4, 'FC026', 'Acarreo de material - Zona B', 'Proyecto Norte', 1, '2025-10-14 08:11:58'),
(5, 'FC030', 'Excavación principal', 'Proyecto Sur', 1, '2025-10-14 08:11:58'),
(6, 'FC040', 'Nivelación preliminar', 'Proyecto Centro', 1, '2025-10-14 08:11:58'),
(7, 'FC050', 'Nivelación final', 'Proyecto Centro', 1, '2025-10-14 08:11:58'),
(8, 'FC060', 'Compactación', 'Proyecto Este', 1, '2025-10-14 08:11:58');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reportes`
--

CREATE TABLE `reportes` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `equipo_id` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `estado` enum('borrador','finalizado') DEFAULT 'borrador',
  `observaciones_generales` text DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_finalizacion` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `reportes`
--

INSERT INTO `reportes` (`id`, `usuario_id`, `equipo_id`, `fecha`, `estado`, `observaciones_generales`, `fecha_creacion`, `fecha_finalizacion`) VALUES
(1, 4, 9, '2025-10-14', 'finalizado', '', '2025-10-14 09:35:03', '2025-10-14 09:45:44'),
(4, 3, 7, '2025-10-14', 'finalizado', '', '2025-10-14 18:38:29', '2025-10-14 18:42:09');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reportes_combustible`
--

CREATE TABLE `reportes_combustible` (
  `id` int(11) NOT NULL,
  `reporte_id` int(11) NOT NULL,
  `horometro` decimal(10,1) NOT NULL,
  `galones` decimal(8,2) NOT NULL,
  `observaciones` varchar(255) DEFAULT NULL,
  `fecha_hora` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `reportes_combustible`
--

INSERT INTO `reportes_combustible` (`id`, `reporte_id`, `horometro`, `galones`, `observaciones`, `fecha_hora`) VALUES
(1, 1, 1588.5, 60.00, '', '2025-10-14 09:40:55'),
(2, 4, 1020.0, 50.00, 'tanqueado', '2025-10-14 18:39:03');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reportes_detalle`
--

CREATE TABLE `reportes_detalle` (
  `id` int(11) NOT NULL,
  `reporte_id` int(11) NOT NULL,
  `tipo_trabajo_id` int(11) NOT NULL,
  `fase_costo_id` int(11) NOT NULL,
  `horometro_inicial` decimal(10,1) NOT NULL,
  `horometro_final` decimal(10,1) NOT NULL,
  `horas_trabajadas` decimal(5,2) GENERATED ALWAYS AS (`horometro_final` - `horometro_inicial`) STORED,
  `observaciones` text DEFAULT NULL,
  `orden` int(11) NOT NULL DEFAULT 1 COMMENT 'Orden de las actividades',
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `reportes_detalle`
--

INSERT INTO `reportes_detalle` (`id`, `reporte_id`, `tipo_trabajo_id`, `fase_costo_id`, `horometro_inicial`, `horometro_final`, `observaciones`, `orden`, `fecha_creacion`) VALUES
(1, 1, 8, 1, 1586.1, 1587.5, '', 1, '2025-10-14 09:43:37'),
(2, 1, 2, 5, 1588.5, 1590.5, '', 2, '2025-10-14 09:44:42'),
(3, 4, 4, 1, 1020.0, 1025.5, 'pruebas', 1, '2025-10-14 18:39:38');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipos_trabajo`
--

CREATE TABLE `tipos_trabajo` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `estado` tinyint(1) DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `tipos_trabajo`
--

INSERT INTO `tipos_trabajo` (`id`, `nombre`, `descripcion`, `estado`, `fecha_creacion`) VALUES
(1, 'Acarreo de material', 'Transporte de tierra, piedra u otros materiales', 1, '2025-10-14 08:11:58'),
(2, 'Excavación', 'Remoción de tierra o rocas', 1, '2025-10-14 08:11:58'),
(3, 'Nivelación', 'Aplanado y nivelado de terreno', 1, '2025-10-14 08:11:58'),
(4, 'Carga', 'Carga de material en volquetes', 1, '2025-10-14 08:11:58'),
(5, 'Descarga', 'Descarga de material', 1, '2025-10-14 08:11:58'),
(6, 'Compactación', 'Compactado de suelo', 1, '2025-10-14 08:11:58'),
(7, 'Demolición', 'Derribo de estructuras', 1, '2025-10-14 08:11:58'),
(8, 'Limpieza de terreno', 'Remoción de vegetación y escombros', 1, '2025-10-14 08:11:58'),
(9, 'Mantenimiento', 'Mantenimiento preventivo del equipo', 1, '2025-10-14 08:11:58'),
(10, 'Espera', 'Tiempo de espera por indicaciones', 1, '2025-10-14 08:11:58');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nombre_completo` varchar(150) NOT NULL,
  `dni` varchar(20) NOT NULL,
  `cargo` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `firma` text DEFAULT NULL COMMENT 'Firma digital en base64',
  `rol` enum('operador','supervisor','admin') DEFAULT 'operador',
  `estado` tinyint(1) DEFAULT 1 COMMENT '1=activo, 0=inactivo',
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre_completo`, `dni`, `cargo`, `password`, `firma`, `rol`, `estado`, `fecha_creacion`) VALUES
(1, 'Administrador Sistema', '12345678', 'Administrador', '$2y$10$C7B38/Tur8jJVXZeA.HpY.Vcx0qONRIYlf1MV/IAKG8LCmlMU1nWu', 'data:image/png;base64,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', 'admin', 1, '2025-10-12 22:43:53'),
(2, 'Juan Pérez García', '45678901', 'Operador', '$2y$10$C7B38/Tur8jJVXZeA.HpY.Vcx0qONRIYlf1MV/IAKG8LCmlMU1nWu', NULL, 'operador', 1, '2025-10-12 22:43:53'),
(3, 'María López Ruiz', '34567890', 'Supervisor', '$2y$10$C7B38/Tur8jJVXZeA.HpY.Vcx0qONRIYlf1MV/IAKG8LCmlMU1nWu', 'data:image/png;base64,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', 'supervisor', 1, '2025-10-12 22:43:53'),
(4, 'Nilson Jhonny Cayao Fernandez', '47469940', 'Operador de Excavadora', '$2y$10$5Mlq/GaBwd0vqo0H1q.53.KXTUclit52zqsAQpdIJEuP3.cXm1k0q', 'data:image/png;base64,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', 'operador', 1, '2025-10-13 00:34:43'),
(5, 'Pedro Pablo Cuchisqui', '47458810', 'Operador de Volquete', '$2y$10$.D3eG5oFsWKT1Wv6l2ejoOm3WUhyML0p2WryDcn78usKZQ5ZIDxru', NULL, 'operador', 1, '2025-10-13 18:55:56');

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_reportes_completos`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vista_reportes_completos` (
);

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_reportes_completos`
--
DROP TABLE IF EXISTS `vista_reportes_completos`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_reportes_completos`  AS SELECT `r`.`id` AS `id`, `r`.`fecha` AS `fecha`, `r`.`hora_inicio` AS `hora_inicio`, `r`.`hora_fin` AS `hora_fin`, `r`.`horas_trabajadas` AS `horas_trabajadas`, `r`.`actividad` AS `actividad`, `r`.`observaciones` AS `observaciones`, `r`.`estado_sinc` AS `estado_sinc`, `u`.`nombre_completo` AS `operador`, `u`.`dni` AS `operador_dni`, `u`.`cargo` AS `operador_cargo`, `e`.`categoria` AS `equipo_categoria`, `e`.`codigo` AS `equipo_codigo`, `e`.`descripcion` AS `equipo_descripcion` FROM ((`reportes` `r` join `usuarios` `u` on(`r`.`usuario_id` = `u`.`id`)) join `equipos` `e` on(`r`.`equipo_id` = `e`.`id`)) ORDER BY `r`.`fecha` DESC, `r`.`hora_inicio` DESC ;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `auditoria`
--
ALTER TABLE `auditoria`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_usuario` (`usuario_id`),
  ADD KEY `idx_accion` (`accion`),
  ADD KEY `idx_fecha` (`fecha`);

--
-- Indices de la tabla `configuracion_empresa`
--
ALTER TABLE `configuracion_empresa`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `equipos`
--
ALTER TABLE `equipos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`),
  ADD KEY `idx_categoria` (`categoria`),
  ADD KEY `idx_codigo` (`codigo`),
  ADD KEY `idx_estado` (`estado`);

--
-- Indices de la tabla `fases_costo`
--
ALTER TABLE `fases_costo`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`);

--
-- Indices de la tabla `reportes`
--
ALTER TABLE `reportes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `equipo_id` (`equipo_id`),
  ADD KEY `idx_fecha` (`fecha`),
  ADD KEY `idx_estado` (`estado`),
  ADD KEY `idx_usuario` (`usuario_id`);

--
-- Indices de la tabla `reportes_combustible`
--
ALTER TABLE `reportes_combustible`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_reporte` (`reporte_id`);

--
-- Indices de la tabla `reportes_detalle`
--
ALTER TABLE `reportes_detalle`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tipo_trabajo_id` (`tipo_trabajo_id`),
  ADD KEY `fase_costo_id` (`fase_costo_id`),
  ADD KEY `idx_reporte` (`reporte_id`),
  ADD KEY `idx_orden` (`orden`);

--
-- Indices de la tabla `tipos_trabajo`
--
ALTER TABLE `tipos_trabajo`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `dni` (`dni`),
  ADD KEY `idx_dni` (`dni`),
  ADD KEY `idx_rol` (`rol`),
  ADD KEY `idx_estado` (`estado`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `auditoria`
--
ALTER TABLE `auditoria`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=98;

--
-- AUTO_INCREMENT de la tabla `configuracion_empresa`
--
ALTER TABLE `configuracion_empresa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `equipos`
--
ALTER TABLE `equipos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `fases_costo`
--
ALTER TABLE `fases_costo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `reportes`
--
ALTER TABLE `reportes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `reportes_combustible`
--
ALTER TABLE `reportes_combustible`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `reportes_detalle`
--
ALTER TABLE `reportes_detalle`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `tipos_trabajo`
--
ALTER TABLE `tipos_trabajo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `auditoria`
--
ALTER TABLE `auditoria`
  ADD CONSTRAINT `auditoria_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `reportes`
--
ALTER TABLE `reportes`
  ADD CONSTRAINT `reportes_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `reportes_ibfk_2` FOREIGN KEY (`equipo_id`) REFERENCES `equipos` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `reportes_combustible`
--
ALTER TABLE `reportes_combustible`
  ADD CONSTRAINT `reportes_combustible_ibfk_1` FOREIGN KEY (`reporte_id`) REFERENCES `reportes` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `reportes_detalle`
--
ALTER TABLE `reportes_detalle`
  ADD CONSTRAINT `reportes_detalle_ibfk_1` FOREIGN KEY (`reporte_id`) REFERENCES `reportes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reportes_detalle_ibfk_2` FOREIGN KEY (`tipo_trabajo_id`) REFERENCES `tipos_trabajo` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `reportes_detalle_ibfk_3` FOREIGN KEY (`fase_costo_id`) REFERENCES `fases_costo` (`id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
