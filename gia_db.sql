-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 26-04-2026 a las 22:13:08
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `gia_db`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `consumibles`
--

CREATE TABLE `consumibles` (
  `id` int(11) NOT NULL,
  `tipo` varchar(100) DEFAULT NULL,
  `categoria` varchar(100) DEFAULT NULL,
  `longitud` varchar(50) DEFAULT NULL,
  `cantidad_stock` int(11) DEFAULT 0,
  `id_unidad` int(11) DEFAULT NULL,
  `estatus` enum('Nuevo','Usado') DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `modificado_por` varchar(15) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `empleados`
--

CREATE TABLE `empleados` (
  `id` int(11) NOT NULL,
  `matricula` varchar(15) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `categoria` varchar(100) DEFAULT NULL,
  `id_unidad` int(11) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `modificado_por` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `equipos_computo`
--

CREATE TABLE `equipos_computo` (
  `id` int(11) NOT NULL,
  `serie` varchar(50) NOT NULL,
  `tipo` enum('CPU','AIO','Laptop') NOT NULL,
  `fabricante` varchar(50) DEFAULT NULL,
  `modelo` varchar(100) DEFAULT NULL,
  `monitor` varchar(50) DEFAULT NULL,
  `tipo_alm` enum('HDD','SSD 2.5','NVME') DEFAULT NULL,
  `capacidad` varchar(20) DEFAULT NULL,
  `ram` varchar(20) DEFAULT NULL,
  `ip` varchar(15) DEFAULT NULL,
  `mac_net` varchar(17) DEFAULT NULL,
  `mac_wifi` varchar(17) DEFAULT NULL,
  `nodo` varchar(50) DEFAULT NULL,
  `p_router` varchar(20) DEFAULT NULL,
  `id_usuario` int(11) DEFAULT NULL,
  `id_unidad` int(11) DEFAULT NULL,
  `area` varchar(100) DEFAULT NULL,
  `departamento` varchar(100) DEFAULT NULL,
  `extension` varchar(20) DEFAULT NULL,
  `proyecto` varchar(50) DEFAULT NULL,
  `fecha_instalacion` date DEFAULT NULL,
  `fecha_retiro` date DEFAULT NULL,
  `estatus` varchar(50) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `modificado_por` varchar(15) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `impresoras`
--

CREATE TABLE `impresoras` (
  `id` int(11) NOT NULL,
  `tipo` varchar(50) DEFAULT NULL,
  `serie` varchar(50) DEFAULT NULL,
  `fabricante` varchar(50) DEFAULT NULL,
  `modelo` varchar(100) DEFAULT NULL,
  `id_unidad` int(11) DEFAULT NULL,
  `area` varchar(100) DEFAULT NULL,
  `departamento` varchar(100) DEFAULT NULL,
  `ip` varchar(15) DEFAULT NULL,
  `fecha_instalacion` date DEFAULT NULL,
  `fecha_retiro` date DEFAULT NULL,
  `estatus` varchar(50) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `modificado_por` varchar(15) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `redes`
--

CREATE TABLE `redes` (
  `id` int(11) NOT NULL,
  `tipo` varchar(50) DEFAULT NULL,
  `serie` varchar(50) DEFAULT NULL,
  `fabricante` varchar(50) DEFAULT NULL,
  `modelo` varchar(100) DEFAULT NULL,
  `num_puertos` int(11) DEFAULT NULL,
  `ip_gestion` varchar(15) DEFAULT NULL,
  `mac` varchar(17) DEFAULT NULL,
  `nodo_uplink` varchar(50) DEFAULT NULL,
  `id_unidad` int(11) DEFAULT NULL,
  `area` varchar(100) DEFAULT NULL,
  `estatus` varchar(50) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `modificado_por` varchar(15) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `telefonos`
--

CREATE TABLE `telefonos` (
  `id` int(11) NOT NULL,
  `serie` varchar(50) DEFAULT NULL,
  `fabricante` varchar(50) DEFAULT NULL,
  `modelo` varchar(100) DEFAULT NULL,
  `tipo` enum('analógico','ip') DEFAULT NULL,
  `ip` varchar(15) DEFAULT NULL,
  `nombre` varchar(100) DEFAULT NULL,
  `extension` varchar(20) DEFAULT NULL,
  `nodo` varchar(50) DEFAULT NULL,
  `p_router` varchar(20) DEFAULT NULL,
  `id_unidad` int(11) DEFAULT NULL,
  `area` varchar(100) DEFAULT NULL,
  `departamento` varchar(100) DEFAULT NULL,
  `fecha_instalacion` date DEFAULT NULL,
  `fecha_retiro` date DEFAULT NULL,
  `estatus` varchar(50) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `modificado_por` varchar(15) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `televisiones`
--

CREATE TABLE `televisiones` (
  `id` int(11) NOT NULL,
  `serie` varchar(50) DEFAULT NULL,
  `fabricante` varchar(50) DEFAULT NULL,
  `modelo` varchar(100) DEFAULT NULL,
  `id_unidad` int(11) DEFAULT NULL,
  `area` varchar(100) DEFAULT NULL,
  `departamento` varchar(100) DEFAULT NULL,
  `uso` varchar(100) DEFAULT NULL,
  `fecha_instalacion` date DEFAULT NULL,
  `fecha_retiro` date DEFAULT NULL,
  `estatus` varchar(50) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `modificado_por` varchar(15) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `unidades`
--

CREATE TABLE `unidades` (
  `id` int(11) NOT NULL,
  `clave` varchar(20) DEFAULT NULL,
  `unidad` varchar(100) NOT NULL,
  `zona` varchar(100) DEFAULT NULL,
  `modificado_por` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `unidades`
--

INSERT INTO `unidades` (`id`, `clave`, `unidad`, `zona`) VALUES
(1, '03010028', 'U.M.F. N°40', 'LA PAZ');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `consumibles`
--
ALTER TABLE `consumibles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tipo` (`tipo`),
  ADD KEY `id_unidad` (`id_unidad`),
  ADD KEY `estatus` (`estatus`);

--
-- Indices de la tabla `empleados`
--
ALTER TABLE `empleados`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `matricula` (`matricula`),
  ADD KEY `matricula_2` (`matricula`),
  ADD KEY `nombre` (`nombre`),
  ADD KEY `id_unidad` (`id_unidad`);

--
-- Indices de la tabla `equipos_computo`
--
ALTER TABLE `equipos_computo`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `serie` (`serie`),
  ADD KEY `id_usuario` (`id_usuario`),
  ADD KEY `serie_2` (`serie`),
  ADD KEY `ip` (`ip`),
  ADD KEY `fabricante` (`fabricante`),
  ADD KEY `modelo` (`modelo`),
  ADD KEY `id_unidad` (`id_unidad`),
  ADD KEY `estatus` (`estatus`);

--
-- Indices de la tabla `impresoras`
--
ALTER TABLE `impresoras`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `serie` (`serie`),
  ADD KEY `serie_2` (`serie`),
  ADD KEY `ip` (`ip`),
  ADD KEY `fabricante` (`fabricante`),
  ADD KEY `modelo` (`modelo`),
  ADD KEY `id_unidad` (`id_unidad`),
  ADD KEY `estatus` (`estatus`);

--
-- Indices de la tabla `redes`
--
ALTER TABLE `redes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `serie` (`serie`),
  ADD KEY `serie_2` (`serie`),
  ADD KEY `ip_gestion` (`ip_gestion`),
  ADD KEY `fabricante` (`fabricante`),
  ADD KEY `modelo` (`modelo`),
  ADD KEY `id_unidad` (`id_unidad`),
  ADD KEY `estatus` (`estatus`);

--
-- Indices de la tabla `telefonos`
--
ALTER TABLE `telefonos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `serie` (`serie`),
  ADD KEY `serie_2` (`serie`),
  ADD KEY `ip` (`ip`),
  ADD KEY `fabricante` (`fabricante`),
  ADD KEY `modelo` (`modelo`),
  ADD KEY `id_unidad` (`id_unidad`),
  ADD KEY `estatus` (`estatus`);

--
-- Indices de la tabla `televisiones`
--
ALTER TABLE `televisiones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `serie` (`serie`),
  ADD KEY `serie_2` (`serie`),
  ADD KEY `fabricante` (`fabricante`),
  ADD KEY `modelo` (`modelo`),
  ADD KEY `id_unidad` (`id_unidad`),
  ADD KEY `estatus` (`estatus`);

--
-- Indices de la tabla `unidades`
--
ALTER TABLE `unidades`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `clave` (`clave`),
  ADD KEY `clave_2` (`clave`),
  ADD KEY `unidad` (`unidad`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `consumibles`
--
ALTER TABLE `consumibles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `empleados`
--
ALTER TABLE `empleados`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `equipos_computo`
--
ALTER TABLE `equipos_computo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `impresoras`
--
ALTER TABLE `impresoras`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `redes`
--
ALTER TABLE `redes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `telefonos`
--
ALTER TABLE `telefonos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `televisiones`
--
ALTER TABLE `televisiones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `unidades`
--
ALTER TABLE `unidades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `consumibles`
--
ALTER TABLE `consumibles`
  ADD CONSTRAINT `consumibles_ibfk_1` FOREIGN KEY (`id_unidad`) REFERENCES `unidades` (`id`);

--
-- Filtros para la tabla `empleados`
--
ALTER TABLE `empleados`
  ADD CONSTRAINT `empleados_ibfk_1` FOREIGN KEY (`id_unidad`) REFERENCES `unidades` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `equipos_computo`
--
ALTER TABLE `equipos_computo`
  ADD CONSTRAINT `equipos_computo_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `empleados` (`id`),
  ADD CONSTRAINT `equipos_computo_ibfk_2` FOREIGN KEY (`id_unidad`) REFERENCES `unidades` (`id`);

--
-- Filtros para la tabla `impresoras`
--
ALTER TABLE `impresoras`
  ADD CONSTRAINT `impresoras_ibfk_1` FOREIGN KEY (`id_unidad`) REFERENCES `unidades` (`id`);

--
-- Filtros para la tabla `redes`
--
ALTER TABLE `redes`
  ADD CONSTRAINT `redes_ibfk_1` FOREIGN KEY (`id_unidad`) REFERENCES `unidades` (`id`);

--
-- Filtros para la tabla `telefonos`
--
ALTER TABLE `telefonos`
  ADD CONSTRAINT `telefonos_ibfk_1` FOREIGN KEY (`id_unidad`) REFERENCES `unidades` (`id`);

--
-- Filtros para la tabla `televisiones`
--
ALTER TABLE `televisiones`
  ADD CONSTRAINT `televisiones_ibfk_1` FOREIGN KEY (`id_unidad`) REFERENCES `unidades` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
