-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 26-07-2025 a las 22:51:07
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
-- Base de datos: `taller_motos`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `administradores`
--

CREATE TABLE `administradores` (
  `id_documento` bigint(11) NOT NULL,
  `nombre` text NOT NULL,
  `email` varchar(150) NOT NULL,
  `telefono` bigint(11) NOT NULL,
  `password` varchar(500) NOT NULL,
  `token_password` varchar(255) DEFAULT NULL,
  `token_expiracion` datetime DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `administradores`
--

INSERT INTO `administradores` (`id_documento`, `nombre`, `email`, `telefono`, `password`, `token_password`, `token_expiracion`, `fecha_creacion`) VALUES
(123456789, 'Instructor Cesar', 'nomelose@gmail.com', 1234567890, '$2y$10$2nCG3.w0WJs7Fng7TkmH6eYI91zH1jkozLsUszcmuOhjBHIrkxBUy', NULL, NULL, '2025-07-26 20:48:03'),
(1104941185, 'Adrian Camargo', 'adriancamargo68@gmail.com', 3108571293, '$2y$10$EzK1gET0YjVDrxLRxT37Cu1M2TT3KprDL9Z.vzslIFcHAXOB/Cf3.', NULL, NULL, '2025-07-18 15:33:10'),
(1104941188, 'Adrian Camargo Rodrigu', 'adriancamargo69@gmail.com', 3155278372, '$2y$10$2QNPHRYJf.JXUYdRkzN0QeOhmuoytFDMPuGQ0SoetW7fQo7hKgr/a', NULL, NULL, '2025-07-21 17:04:13');

--
-- Disparadores `administradores`
--
DELIMITER $$
CREATE TRIGGER `delete_administradores` AFTER DELETE ON `administradores` FOR EACH ROW BEGIN
    INSERT INTO auditoria (tabla_afectada, accion_realizada, descripcion, id_admin)
    VALUES ('Administradores', 'ELIMINACIÓN', CONCAT('Se eliminó al administrador: ', OLD.nombre, ' (Doc: ', OLD.id_documento, ')'), @current_admin_id);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `insert_administradores` AFTER INSERT ON `administradores` FOR EACH ROW BEGIN
    INSERT INTO auditoria (tabla_afectada, accion_realizada, descripcion, id_admin)
    VALUES ('Administradores', 'NUEVO REGISTRO', CONCAT('Se agregó al administrador: ', NEW.nombre, ' (Doc: ', NEW.id_documento, ')'), @current_admin_id);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `update_administradores` AFTER UPDATE ON `administradores` FOR EACH ROW BEGIN
    INSERT INTO auditoria (tabla_afectada, accion_realizada, descripcion, id_admin)
    VALUES ('Administradores', 'ACTUALIZACIÓN', CONCAT('Se actualizó al administrador: ', OLD.nombre, ' (Doc: ', OLD.id_documento, ')'), @current_admin_id);
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `auditoria`
--

CREATE TABLE `auditoria` (
  `id_auditoria` int(11) NOT NULL,
  `tabla_afectada` varchar(50) NOT NULL,
  `accion_realizada` varchar(50) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha_hora` timestamp NOT NULL DEFAULT current_timestamp(),
  `id_admin` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `auditoria`
--

INSERT INTO `auditoria` (`id_auditoria`, `tabla_afectada`, `accion_realizada`, `descripcion`, `fecha_hora`, `id_admin`) VALUES
(1, 'Clientes', 'NUEVO REGISTRO', 'Se agregó al cliente: Taira Sofia Rubio (Doc: 1109385178)', '2025-07-25 14:46:26', '1104941185'),
(2, 'Cilindrajes', 'NUEVO REGISTRO', 'Se agregó el cilindraje: 150cc', '2025-07-25 14:46:59', '1104941185'),
(3, 'Modelos (Años)', 'NUEVO REGISTRO', 'Se agregó el año: 2022', '2025-07-25 14:47:24', '1104941185'),
(4, 'Colores', 'NUEVO REGISTRO', 'Se agregó el color: \"Azul Champaña\"', '2025-07-25 14:47:57', '1104941185'),
(5, 'Motos', 'NUEVO REGISTRO', 'Se registró la moto con placa: GZM57D', '2025-07-25 14:48:12', '1104941185'),
(6, 'Tipos de Trabajo', 'ACTUALIZACIÓN', 'Se actualizó el trabajo (ID: 1) de \"Cambio de aceite\" a \"Cambio de aceite\"', '2025-07-25 14:48:37', '1104941185'),
(7, 'Mantenimientos', 'NUEVO REGISTRO', 'Se registró un nuevo mantenimiento (ID: 1) para la moto con placa: GZM57D), con un valor de: 30000', '2025-07-25 14:50:22', '1104941185'),
(8, 'Modelos (Años)', 'ACTUALIZACIÓN', 'Se actualizó el modelo (ID: 3) del año 2022 al año 2002', '2025-07-25 14:56:57', '1104941185'),
(9, 'Modelos (Años)', 'NUEVO REGISTRO', 'Se agregó el año: 2003', '2025-07-25 14:57:05', '1104941185'),
(10, 'Modelos (Años)', 'NUEVO REGISTRO', 'Se agregó el año: 2004', '2025-07-25 14:57:13', '1104941185'),
(11, 'Modelos (Años)', 'NUEVO REGISTRO', 'Se agregó el año: 2005', '2025-07-25 14:57:41', '1104941185'),
(12, 'Modelos (Años)', 'NUEVO REGISTRO', 'Se agregó el año: 2006', '2025-07-25 14:57:56', '1104941185'),
(13, 'Modelos (Años)', 'NUEVO REGISTRO', 'Se agregó el año: 2007', '2025-07-25 14:58:04', '1104941185'),
(14, 'Modelos (Años)', 'NUEVO REGISTRO', 'Se agregó el año: 2010', '2025-07-25 14:58:42', '1104941185'),
(15, 'Modelos (Años)', 'ACTUALIZACIÓN', 'Se actualizó el modelo (ID: 9) del año 2010 al año 2008', '2025-07-25 14:58:50', '1104941185'),
(16, 'Modelos (Años)', 'NUEVO REGISTRO', 'Se agregó el año: 2009', '2025-07-25 14:58:57', '1104941185'),
(17, 'Modelos (Años)', 'NUEVO REGISTRO', 'Se agregó el año: 2010', '2025-07-25 14:59:31', '1104941185'),
(18, 'Modelos (Años)', 'NUEVO REGISTRO', 'Se agregó el año: 2011', '2025-07-25 14:59:55', '1104941185'),
(19, 'Modelos (Años)', 'NUEVO REGISTRO', 'Se agregó el año: 2012', '2025-07-25 15:00:34', '1104941185'),
(20, 'Modelos (Años)', 'NUEVO REGISTRO', 'Se agregó el año: 2013', '2025-07-25 15:00:56', '1104941185'),
(21, 'Modelos (Años)', 'NUEVO REGISTRO', 'Se agregó el año: 2014', '2025-07-25 15:02:47', NULL),
(22, 'Modelos (Años)', 'NUEVO REGISTRO', 'Se agregó el año: 2015', '2025-07-25 15:02:47', NULL),
(23, 'Modelos (Años)', 'NUEVO REGISTRO', 'Se agregó el año: 2016', '2025-07-25 15:02:47', NULL),
(24, 'Modelos (Años)', 'NUEVO REGISTRO', 'Se agregó el año: 2017', '2025-07-25 15:02:47', NULL),
(25, 'Modelos (Años)', 'NUEVO REGISTRO', 'Se agregó el año: 2018', '2025-07-25 15:02:47', NULL),
(26, 'Modelos (Años)', 'NUEVO REGISTRO', 'Se agregó el año: 2019', '2025-07-25 15:02:47', NULL),
(27, 'Modelos (Años)', 'NUEVO REGISTRO', 'Se agregó el año: 2020', '2025-07-25 15:02:47', NULL),
(28, 'Modelos (Años)', 'NUEVO REGISTRO', 'Se agregó el año: 2021', '2025-07-25 15:02:47', NULL),
(29, 'Modelos (Años)', 'NUEVO REGISTRO', 'Se agregó el año: 2022', '2025-07-25 15:02:47', NULL),
(30, 'Modelos (Años)', 'NUEVO REGISTRO', 'Se agregó el año: 2023', '2025-07-25 15:02:47', NULL),
(31, 'Modelos (Años)', 'NUEVO REGISTRO', 'Se agregó el año: 2024', '2025-07-25 15:03:08', NULL),
(32, 'Modelos (Años)', 'NUEVO REGISTRO', 'Se agregó el año: 2025', '2025-07-25 15:03:08', NULL),
(33, 'Modelos (Años)', 'NUEVO REGISTRO', 'Se agregó el año: 2026', '2025-07-25 15:03:21', '1104941185'),
(34, 'Mantenimientos', 'ACTUALIZACIÓN', 'Se actualizó el mantenimiento (ID: 1) de la moto con placa: GZM57D. Valor anterior: 30000, nuevo valor: 150000.', '2025-07-25 16:44:07', '1104941185'),
(35, 'Administradores', 'ACTUALIZACIÓN', 'Se actualizó al administrador: Adrian Camargo (Doc: 1104941185)', '2025-07-25 17:26:25', NULL),
(36, 'Mantenimientos', 'NUEVO REGISTRO', 'Se registró un nuevo mantenimiento (ID: 2) para la moto con placa: GZM57D), con un valor de: 120000', '2025-07-25 19:49:34', '1104941185'),
(37, 'Mantenimientos', 'ACTUALIZACIÓN', 'Se actualizó el mantenimiento (ID: 1) de la moto con placa: GZM57D. Valor anterior: 150000, nuevo valor: 150000.', '2025-07-25 20:26:39', '1104941185'),
(38, 'Administradores', 'ACTUALIZACIÓN', 'Se actualizó al administrador: Adrian Camargo (Doc: 1104941185)', '2025-07-25 20:37:22', NULL),
(39, 'Tipos de Trabajo', 'ACTUALIZACIÓN', 'Se actualizó el trabajo (ID: 1) de \"Cambio de aceite\" a \"Cambio de aceite\"', '2025-07-25 21:12:29', '1104941185'),
(40, 'Tipos de Trabajo', 'ACTUALIZACIÓN', 'Se actualizó el trabajo (ID: 2) de \"Cambio de kit de arrastre\" a \"Cambio de kit de arrastre\"', '2025-07-25 21:12:37', '1104941185'),
(41, 'Cilindrajes', 'ACTUALIZACIÓN', 'Se actualizó el cilindraje (ID: 1) de 150cc a 1200cc', '2025-07-25 22:10:11', '1104941185'),
(42, 'Cilindrajes', 'NUEVO REGISTRO', 'Se agregó el cilindraje: 150cc', '2025-07-25 22:11:38', '1104941185'),
(43, 'Motos', 'ACTUALIZACIÓN', 'Se actualizó la moto con placa: GZM57D', '2025-07-25 22:12:05', '1104941185'),
(44, 'Tipos de Trabajo', 'ACTUALIZACIÓN', 'Se actualizó el trabajo (ID: 2) de \"Cambio de kit de arrastre\" a \"Cambio de kit de arrastre\"', '2025-07-25 22:12:19', '1104941185'),
(45, 'Motos', 'NUEVO REGISTRO', 'Se registró la moto con placa: GZM59D', '2025-07-25 22:12:54', '1104941185'),
(46, 'Mantenimientos', 'NUEVO REGISTRO', 'Se registró un nuevo mantenimiento (ID: 3) para la moto con placa: GZM59D), con un valor de: 120000', '2025-07-26 04:04:31', '1104941185'),
(47, 'Mantenimientos', 'ACTUALIZACIÓN', 'Se actualizó el mantenimiento (ID: 3) de la moto con placa: GZM59D. Valor anterior: 120000, nuevo valor: 960000.', '2025-07-26 04:44:36', '1104941185'),
(48, 'Mantenimientos', 'NUEVO REGISTRO', 'Se registró un nuevo mantenimiento (ID: 4) para la moto con placa: GZM57D), con un valor de: 90000', '2025-07-26 04:54:26', '1104941185'),
(49, 'Mantenimientos', 'NUEVO REGISTRO', 'Se registró un nuevo mantenimiento (ID: 5) para la moto con placa: GZM59D), con un valor de: 1200000', '2025-07-26 04:58:26', '1104941185'),
(50, 'Administradores', 'ACTUALIZACIÓN', 'Se actualizó al administrador: Adrian Camargo (Doc: 1104941185)', '2025-07-26 05:21:09', NULL),
(51, 'Administradores', 'ACTUALIZACIÓN', 'Se actualizó al administrador: Adrian Camargo (Doc: 1104941185)', '2025-07-26 05:21:17', NULL),
(52, 'Administradores', 'ACTUALIZACIÓN', 'Se actualizó al administrador: Adrian Camargo (Doc: 1104941185)', '2025-07-26 05:21:26', NULL),
(53, 'Administradores', 'ACTUALIZACIÓN', 'Se actualizó al administrador: Adrian Camargo (Doc: 1104941185)', '2025-07-26 05:22:09', NULL),
(54, 'Administradores', 'ACTUALIZACIÓN', 'Se actualizó al administrador: Adrian Camargo (Doc: 1104941185)', '2025-07-26 05:25:20', NULL),
(55, 'Administradores', 'ACTUALIZACIÓN', 'Se actualizó al administrador: Adrian Camargo (Doc: 1104941185)', '2025-07-26 05:30:33', NULL),
(56, 'Administradores', 'ACTUALIZACIÓN', 'Se actualizó al administrador: Adrian Camargo (Doc: 1104941185)', '2025-07-26 05:39:56', NULL),
(57, 'Administradores', 'ACTUALIZACIÓN', 'Se actualizó al administrador: Adrian Camargo (Doc: 1104941185)', '2025-07-26 05:48:57', NULL),
(58, 'Administradores', 'ACTUALIZACIÓN', 'Se actualizó al administrador: Adrian Camargo (Doc: 1104941185)', '2025-07-26 05:50:21', NULL),
(59, 'Administradores', 'ACTUALIZACIÓN', 'Se actualizó al administrador: Adrian Camargo (Doc: 1104941185)', '2025-07-26 05:54:42', NULL),
(60, 'Administradores', 'ACTUALIZACIÓN', 'Se actualizó al administrador: Adrian Camargo (Doc: 1104941185)', '2025-07-26 05:54:52', NULL),
(61, 'Administradores', 'ACTUALIZACIÓN', 'Se actualizó al administrador: Adrian Camargo (Doc: 1104941185)', '2025-07-26 05:57:28', NULL),
(62, 'Administradores', 'ACTUALIZACIÓN', 'Se actualizó al administrador: Adrian Camargo (Doc: 1104941185)', '2025-07-26 05:57:38', NULL),
(63, 'Administradores', 'ACTUALIZACIÓN', 'Se actualizó al administrador: Adrian Camargo (Doc: 1104941185)', '2025-07-26 05:58:11', NULL),
(64, 'Administradores', 'NUEVO REGISTRO', 'Se agregó al administrador: Instructor Cesar (Doc: 123456789)', '2025-07-26 20:48:03', '1104941185');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cilindraje`
--

CREATE TABLE `cilindraje` (
  `id_cc` int(11) NOT NULL,
  `cilindraje` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cilindraje`
--

INSERT INTO `cilindraje` (`id_cc`, `cilindraje`) VALUES
(1, 1200),
(2, 150);

--
-- Disparadores `cilindraje`
--
DELIMITER $$
CREATE TRIGGER `delete_cilindraje` AFTER DELETE ON `cilindraje` FOR EACH ROW BEGIN
    INSERT INTO auditoria (tabla_afectada, accion_realizada, descripcion, id_admin)
    VALUES ('Cilindrajes', 'ELIMINACIÓN', CONCAT('Se eliminó el cilindraje: ', OLD.cilindraje, 'cc (ID: ', OLD.id_cc, ')'), @current_admin_id);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `insert_cilindraje` AFTER INSERT ON `cilindraje` FOR EACH ROW BEGIN
    INSERT INTO auditoria (tabla_afectada, accion_realizada, descripcion, id_admin)
    VALUES ('Cilindrajes', 'NUEVO REGISTRO', CONCAT('Se agregó el cilindraje: ', NEW.cilindraje, 'cc'), @current_admin_id);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `update_cilindraje` AFTER UPDATE ON `cilindraje` FOR EACH ROW BEGIN
    INSERT INTO auditoria (tabla_afectada, accion_realizada, descripcion, id_admin)
    VALUES ('Cilindrajes', 'ACTUALIZACIÓN', CONCAT('Se actualizó el cilindraje (ID: ', OLD.id_cc, ') de ', OLD.cilindraje, 'cc a ', NEW.cilindraje, 'cc'), @current_admin_id);
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clientes`
--

CREATE TABLE `clientes` (
  `id_documento_cli` bigint(11) NOT NULL,
  `nombre` text NOT NULL,
  `telefono` bigint(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `direccion` varchar(100) NOT NULL,
  `fecha_ingreso` datetime NOT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `clientes`
--

INSERT INTO `clientes` (`id_documento_cli`, `nombre`, `telefono`, `email`, `direccion`, `fecha_ingreso`, `fecha_creacion`) VALUES
(1109385178, 'Taira Sofia Rubio', 3157622646, 'tairasofiarubiomedina@gmail.com', '', '2025-07-25 09:46:00', '2025-07-25 14:46:26');

--
-- Disparadores `clientes`
--
DELIMITER $$
CREATE TRIGGER `delete_clientes` AFTER DELETE ON `clientes` FOR EACH ROW BEGIN
    INSERT INTO auditoria (tabla_afectada, accion_realizada, descripcion, id_admin)
    VALUES ('Clientes', 'ELIMINACIÓN', CONCAT('Se eliminó al cliente: ', OLD.nombre, ' (Doc: ', OLD.id_documento_cli, ')'), @current_admin_id);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `insert_clientes` AFTER INSERT ON `clientes` FOR EACH ROW BEGIN
    INSERT INTO auditoria (tabla_afectada, accion_realizada, descripcion, id_admin)
    VALUES ('Clientes', 'NUEVO REGISTRO', CONCAT('Se agregó al cliente: ', NEW.nombre, ' (Doc: ', NEW.id_documento_cli, ')'), @current_admin_id);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `update_clientes` AFTER UPDATE ON `clientes` FOR EACH ROW BEGIN
    INSERT INTO auditoria (tabla_afectada, accion_realizada, descripcion, id_admin)
    VALUES ('Clientes', 'ACTUALIZACIÓN', CONCAT('Se actualizó el cliente con documento: ', OLD.id_documento_cli), @current_admin_id);
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `color`
--

CREATE TABLE `color` (
  `id_color` int(11) NOT NULL,
  `color` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `color`
--

INSERT INTO `color` (`id_color`, `color`) VALUES
(1, 'Azul'),
(2, 'Azul Champaña');

--
-- Disparadores `color`
--
DELIMITER $$
CREATE TRIGGER `delete_color` AFTER DELETE ON `color` FOR EACH ROW BEGIN
    INSERT INTO auditoria (tabla_afectada, accion_realizada, descripcion, id_admin)
    VALUES ('Colores', 'ELIMINACIÓN', CONCAT('Se eliminó el color: "', OLD.color, '" (ID: ', OLD.id_color, ')'), @current_admin_id);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `insert_color` AFTER INSERT ON `color` FOR EACH ROW BEGIN
    INSERT INTO auditoria (tabla_afectada, accion_realizada, descripcion, id_admin)
    VALUES ('Colores', 'NUEVO REGISTRO', CONCAT('Se agregó el color: "', NEW.color, '"'), @current_admin_id);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `update_color` AFTER UPDATE ON `color` FOR EACH ROW BEGIN
    INSERT INTO auditoria (tabla_afectada, accion_realizada, descripcion, id_admin)
    VALUES ('Colores', 'ACTUALIZACIÓN', CONCAT('Se actualizó el color (ID: ', OLD.id_color, ') de "', OLD.color, '" a "', NEW.color, '"'), @current_admin_id);
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_mantenimientos`
--

CREATE TABLE `detalle_mantenimientos` (
  `id_detalle` int(11) NOT NULL,
  `id_tipo_trabajo` int(11) NOT NULL,
  `cantidad` decimal(10,0) NOT NULL,
  `subtotal` decimal(10,0) NOT NULL,
  `id_mantenimiento` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `detalle_mantenimientos`
--

INSERT INTO `detalle_mantenimientos` (`id_detalle`, `id_tipo_trabajo`, `cantidad`, `subtotal`, `id_mantenimiento`) VALUES
(4, 2, 1, 120000, 2),
(5, 1, 1, 30000, 1),
(6, 2, 1, 120000, 1),
(8, 2, 1, 120000, 3),
(9, 2, 1, 120000, 3),
(10, 2, 6, 720000, 3),
(11, 1, 1, 30000, 4),
(12, 1, 2, 60000, 4),
(13, 2, 10, 1200000, 5);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mantenimientos`
--

CREATE TABLE `mantenimientos` (
  `id_mantenimientos` int(11) NOT NULL,
  `fecha_realizo` datetime NOT NULL,
  `kilometraje` bigint(7) NOT NULL,
  `total` decimal(10,0) NOT NULL,
  `observaciones_entrada` varchar(500) NOT NULL,
  `observaciones_salida` varchar(500) NOT NULL,
  `id_placa` varchar(7) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `mantenimientos`
--

INSERT INTO `mantenimientos` (`id_mantenimientos`, `fecha_realizo`, `kilometraje`, `total`, `observaciones_entrada`, `observaciones_salida`, `id_placa`) VALUES
(1, '2025-07-24 09:48:00', 49000, 150000, 'Se vio que la moto le hacia falta el cambio de aceite por que salio muy poco', 'Problema arreglado agregamos 1100 mm dos tarros de aceite', 'GZM57D'),
(2, '2025-07-25 14:49:00', 20202, 120000, 'dfsdfsd', 'qweqweqdq', 'GZM57D'),
(3, '2025-07-25 23:04:00', 2002, 960000, 'xzcxzcxz', 'scadsadsad', 'GZM59D'),
(4, '2025-07-25 23:53:00', 29210, 90000, 'asdasdas', 'sadsdas', 'GZM57D'),
(5, '2025-07-25 23:58:00', 12089, 1200000, 'sadajskdsaj', 'sadakjs', 'GZM59D');

--
-- Disparadores `mantenimientos`
--
DELIMITER $$
CREATE TRIGGER `delete_mantenimientos` AFTER DELETE ON `mantenimientos` FOR EACH ROW BEGIN
    INSERT INTO auditoria (tabla_afectada, accion_realizada, descripcion, id_admin)
    VALUES (
        'Mantenimientos', 
        'ELIMINACIÓN', 
        CONCAT(
            'Se eliminó el mantenimiento (ID: ', OLD.id_mantenimientos, 
            ') de la moto con placa: ', OLD.id_placa, 
            '. Valor total registrado: ', OLD.total, '.'
        ),
        @current_admin_id
    );
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `insert_mantenimientos` AFTER INSERT ON `mantenimientos` FOR EACH ROW BEGIN
    INSERT INTO auditoria (tabla_afectada, accion_realizada, descripcion, id_admin)
    VALUES (
        'Mantenimientos', 
        'NUEVO REGISTRO', 
        CONCAT('Se registró un nuevo mantenimiento (ID: ', NEW.id_mantenimientos, ') para la moto con placa: ', NEW.id_placa, '), con un valor de: ', NEW.total),
        @current_admin_id
    );
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `update_mantenimientos` AFTER UPDATE ON `mantenimientos` FOR EACH ROW BEGIN
    INSERT INTO auditoria (tabla_afectada, accion_realizada, descripcion, id_admin)
    VALUES (
        'Mantenimientos', 
        'ACTUALIZACIÓN', 
        CONCAT('Se actualizó el mantenimiento (ID: ', OLD.id_mantenimientos, ') de la moto con placa: ', OLD.id_placa, 
               '. Valor anterior: ', OLD.total, ', nuevo valor: ', NEW.total, '.'),
        @current_admin_id
    );
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `marcas`
--

CREATE TABLE `marcas` (
  `id_marca` int(11) NOT NULL,
  `marcas` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `marcas`
--

INSERT INTO `marcas` (`id_marca`, `marcas`) VALUES
(1, 'Yamaha'),
(2, 'Honda'),
(3, 'Suzuki'),
(4, 'KTM'),
(5, 'Kawasaki');

--
-- Disparadores `marcas`
--
DELIMITER $$
CREATE TRIGGER `insert_marcas` AFTER INSERT ON `marcas` FOR EACH ROW BEGIN
    INSERT INTO auditoria (tabla_afectada, accion_realizada, descripcion, id_admin)
    VALUES ('Marcas', 'NUEVO REGISTRO', CONCAT('Se agregó la marca: "', NEW.marcas, '"'), @current_admin_id);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_after_delete_marcas` AFTER DELETE ON `marcas` FOR EACH ROW BEGIN
    INSERT INTO auditoria (tabla_afectada, accion_realizada, descripcion, id_admin)
    VALUES ('Marcas', 'ELIMINACIÓN', CONCAT('Se eliminó la marca: "', OLD.marcas, '" (ID: ', OLD.id_marca, ')'), @current_admin_id);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `update_marcas` AFTER UPDATE ON `marcas` FOR EACH ROW BEGIN
    INSERT INTO auditoria (tabla_afectada, accion_realizada, descripcion, id_admin)
    VALUES ('Marcas', 'ACTUALIZACIÓN', CONCAT('Se actualizó la marca (ID: ', OLD.id_marca, ') de "', OLD.marcas, '" a "', NEW.marcas, '"'), @current_admin_id);
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `modelos`
--

CREATE TABLE `modelos` (
  `id_modelo` int(11) NOT NULL,
  `anio` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `modelos`
--

INSERT INTO `modelos` (`id_modelo`, `anio`) VALUES
(1, '2000'),
(2, '2001'),
(3, '2002'),
(4, '2003'),
(5, '2004'),
(6, '2005'),
(7, '2006'),
(8, '2007'),
(9, '2008'),
(10, '2009'),
(11, '2010'),
(12, '2011'),
(13, '2012'),
(14, '2013'),
(15, '2014'),
(16, '2015'),
(17, '2016'),
(18, '2017'),
(19, '2018'),
(20, '2019'),
(21, '2020'),
(22, '2021'),
(23, '2022'),
(24, '2023'),
(25, '2024'),
(26, '2025'),
(27, '2026');

--
-- Disparadores `modelos`
--
DELIMITER $$
CREATE TRIGGER `delete_modelos` AFTER DELETE ON `modelos` FOR EACH ROW BEGIN
    INSERT INTO auditoria (tabla_afectada, accion_realizada, descripcion, id_admin)
    VALUES ('Modelos (Años)', 'ELIMINACIÓN', CONCAT('Se eliminó el modelo (año): ', OLD.anio, ' (ID: ', OLD.id_modelo, ')'), @current_admin_id);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `insert_modelos` AFTER INSERT ON `modelos` FOR EACH ROW BEGIN
    INSERT INTO auditoria (tabla_afectada, accion_realizada, descripcion, id_admin)
    VALUES ('Modelos (Años)', 'NUEVO REGISTRO', CONCAT('Se agregó el año: ', NEW.anio), @current_admin_id);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `update_modelos` AFTER UPDATE ON `modelos` FOR EACH ROW BEGIN
    INSERT INTO auditoria (tabla_afectada, accion_realizada, descripcion, id_admin)
    VALUES ('Modelos (Años)', 'ACTUALIZACIÓN', CONCAT('Se actualizó el modelo (ID: ', OLD.id_modelo, ') del año ', OLD.anio, ' al año ', NEW.anio), @current_admin_id);
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `motos`
--

CREATE TABLE `motos` (
  `id_placa` varchar(6) NOT NULL,
  `id_cilindraje` int(11) NOT NULL,
  `id_referencia_marca` int(11) NOT NULL,
  `id_modelo` int(11) NOT NULL,
  `id_color` int(11) NOT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  `id_documento_cli` bigint(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `motos`
--

INSERT INTO `motos` (`id_placa`, `id_cilindraje`, `id_referencia_marca`, `id_modelo`, `id_color`, `fecha_registro`, `id_documento_cli`) VALUES
('GZM57D', 2, 1, 3, 2, '2025-07-25 14:48:12', 1109385178),
('GZM59D', 1, 1, 6, 1, '2025-07-25 22:12:54', 1109385178);

--
-- Disparadores `motos`
--
DELIMITER $$
CREATE TRIGGER `delete_motos` AFTER DELETE ON `motos` FOR EACH ROW BEGIN
    INSERT INTO auditoria (tabla_afectada, accion_realizada, descripcion, id_admin)
    VALUES ('Motos', 'ELIMINACIÓN', CONCAT('Se eliminó la moto con placa: ', OLD.id_placa), @current_admin_id);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `insert_motos` AFTER INSERT ON `motos` FOR EACH ROW BEGIN
    INSERT INTO auditoria (tabla_afectada, accion_realizada, descripcion, id_admin)
    VALUES ('Motos', 'NUEVO REGISTRO', CONCAT('Se registró la moto con placa: ', NEW.id_placa), @current_admin_id);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `update_motos` AFTER UPDATE ON `motos` FOR EACH ROW BEGIN
    INSERT INTO auditoria (tabla_afectada, accion_realizada, descripcion, id_admin)
    VALUES ('Motos', 'ACTUALIZACIÓN', CONCAT('Se actualizó la moto con placa: ', OLD.id_placa), @current_admin_id);
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `referencia_marca`
--

CREATE TABLE `referencia_marca` (
  `id_referencia` int(11) NOT NULL,
  `referencia_marca` text NOT NULL,
  `id_marcas` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `referencia_marca`
--

INSERT INTO `referencia_marca` (`id_referencia`, `referencia_marca`, `id_marcas`) VALUES
(1, 'Fz150', 1);

--
-- Disparadores `referencia_marca`
--
DELIMITER $$
CREATE TRIGGER `delete_referencia` AFTER DELETE ON `referencia_marca` FOR EACH ROW BEGIN
    INSERT INTO auditoria (tabla_afectada, accion_realizada, descripcion, id_admin)
    VALUES ('Referencias', 'ELIMINACIÓN', CONCAT('Se eliminó la referencia: "', OLD.referencia_marca, '" (ID: ', OLD.id_referencia, ')'), @current_admin_id);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `insert_referencia` AFTER INSERT ON `referencia_marca` FOR EACH ROW BEGIN
    INSERT INTO auditoria (tabla_afectada, accion_realizada, descripcion, id_admin)
    VALUES ('Referencias', 'NUEVO REGISTRO', CONCAT('Se agregó la referencia: "', NEW.referencia_marca, '"'), @current_admin_id);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `update_referencia` AFTER UPDATE ON `referencia_marca` FOR EACH ROW BEGIN
    INSERT INTO auditoria (tabla_afectada, accion_realizada, descripcion, id_admin)
    VALUES ('Referencias', 'ACTUALIZACIÓN', CONCAT('Se actualizó la referencia (ID: ', OLD.id_referencia, ') de "', OLD.referencia_marca, '" a "', NEW.referencia_marca, '"'), @current_admin_id);
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipo_trabajo`
--

CREATE TABLE `tipo_trabajo` (
  `id_tipo` int(11) NOT NULL,
  `cc_inicial` int(11) NOT NULL,
  `cc_final` int(11) NOT NULL,
  `detalle` text NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tipo_trabajo`
--

INSERT INTO `tipo_trabajo` (`id_tipo`, `cc_inicial`, `cc_final`, `detalle`, `precio_unitario`) VALUES
(1, 150, 150, 'Cambio de aceite', 30000.00),
(2, 1200, 1200, 'Cambio de kit de arrastre', 120000.00);

--
-- Disparadores `tipo_trabajo`
--
DELIMITER $$
CREATE TRIGGER `delete_tipo_trabajo` AFTER DELETE ON `tipo_trabajo` FOR EACH ROW BEGIN
    INSERT INTO auditoria (tabla_afectada, accion_realizada, descripcion, id_admin)
    VALUES ('Tipos de Trabajo', 'ELIMINACIÓN', CONCAT('Se eliminó el trabajo: "', OLD.detalle, '" (ID: ', OLD.id_tipo, ')'), @current_admin_id);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `insert_tipo_trabajo` AFTER INSERT ON `tipo_trabajo` FOR EACH ROW BEGIN
    INSERT INTO auditoria (tabla_afectada, accion_realizada, descripcion, id_admin)
    VALUES ('Tipos de Trabajo', 'NUEVO REGISTRO', CONCAT('Se agregó el trabajo: "', NEW.detalle, '" (Rango: ', NEW.cc_inicial, '-', NEW.cc_final, 'cc)'), @current_admin_id);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `update_tipo_trabajo` AFTER UPDATE ON `tipo_trabajo` FOR EACH ROW BEGIN
    INSERT INTO auditoria (tabla_afectada, accion_realizada, descripcion, id_admin)
    VALUES ('Tipos de Trabajo', 'ACTUALIZACIÓN', CONCAT('Se actualizó el trabajo (ID: ', OLD.id_tipo, ') de "', OLD.detalle, '" a "', NEW.detalle, '"'), @current_admin_id);
END
$$
DELIMITER ;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `administradores`
--
ALTER TABLE `administradores`
  ADD PRIMARY KEY (`id_documento`);

--
-- Indices de la tabla `auditoria`
--
ALTER TABLE `auditoria`
  ADD PRIMARY KEY (`id_auditoria`);

--
-- Indices de la tabla `cilindraje`
--
ALTER TABLE `cilindraje`
  ADD PRIMARY KEY (`id_cc`);

--
-- Indices de la tabla `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id_documento_cli`);

--
-- Indices de la tabla `color`
--
ALTER TABLE `color`
  ADD PRIMARY KEY (`id_color`);

--
-- Indices de la tabla `detalle_mantenimientos`
--
ALTER TABLE `detalle_mantenimientos`
  ADD PRIMARY KEY (`id_detalle`),
  ADD KEY `id_tipo_trabajo` (`id_tipo_trabajo`),
  ADD KEY `detalle_mantenimientos_ibfk_1` (`id_mantenimiento`);

--
-- Indices de la tabla `mantenimientos`
--
ALTER TABLE `mantenimientos`
  ADD PRIMARY KEY (`id_mantenimientos`),
  ADD KEY `mantenimientos_ibfk_1` (`id_placa`);

--
-- Indices de la tabla `marcas`
--
ALTER TABLE `marcas`
  ADD PRIMARY KEY (`id_marca`);

--
-- Indices de la tabla `modelos`
--
ALTER TABLE `modelos`
  ADD PRIMARY KEY (`id_modelo`);

--
-- Indices de la tabla `motos`
--
ALTER TABLE `motos`
  ADD PRIMARY KEY (`id_placa`),
  ADD KEY `id_color` (`id_color`),
  ADD KEY `id_modelo` (`id_modelo`),
  ADD KEY `id_referencia_marca` (`id_referencia_marca`),
  ADD KEY `motos_ibfk_1` (`id_documento_cli`),
  ADD KEY `motos_ibfk_2` (`id_cilindraje`);

--
-- Indices de la tabla `referencia_marca`
--
ALTER TABLE `referencia_marca`
  ADD PRIMARY KEY (`id_referencia`),
  ADD KEY `id_marcas` (`id_marcas`);

--
-- Indices de la tabla `tipo_trabajo`
--
ALTER TABLE `tipo_trabajo`
  ADD PRIMARY KEY (`id_tipo`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `auditoria`
--
ALTER TABLE `auditoria`
  MODIFY `id_auditoria` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=65;

--
-- AUTO_INCREMENT de la tabla `cilindraje`
--
ALTER TABLE `cilindraje`
  MODIFY `id_cc` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id_documento_cli` bigint(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1109385179;

--
-- AUTO_INCREMENT de la tabla `color`
--
ALTER TABLE `color`
  MODIFY `id_color` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `detalle_mantenimientos`
--
ALTER TABLE `detalle_mantenimientos`
  MODIFY `id_detalle` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de la tabla `mantenimientos`
--
ALTER TABLE `mantenimientos`
  MODIFY `id_mantenimientos` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `marcas`
--
ALTER TABLE `marcas`
  MODIFY `id_marca` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `modelos`
--
ALTER TABLE `modelos`
  MODIFY `id_modelo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT de la tabla `referencia_marca`
--
ALTER TABLE `referencia_marca`
  MODIFY `id_referencia` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `tipo_trabajo`
--
ALTER TABLE `tipo_trabajo`
  MODIFY `id_tipo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `detalle_mantenimientos`
--
ALTER TABLE `detalle_mantenimientos`
  ADD CONSTRAINT `detalle_mantenimientos_ibfk_1` FOREIGN KEY (`id_mantenimiento`) REFERENCES `mantenimientos` (`id_mantenimientos`) ON DELETE CASCADE,
  ADD CONSTRAINT `detalle_mantenimientos_ibfk_2` FOREIGN KEY (`id_tipo_trabajo`) REFERENCES `tipo_trabajo` (`id_tipo`);

--
-- Filtros para la tabla `mantenimientos`
--
ALTER TABLE `mantenimientos`
  ADD CONSTRAINT `mantenimientos_ibfk_1` FOREIGN KEY (`id_placa`) REFERENCES `motos` (`id_placa`) ON DELETE CASCADE;

--
-- Filtros para la tabla `motos`
--
ALTER TABLE `motos`
  ADD CONSTRAINT `motos_ibfk_1` FOREIGN KEY (`id_documento_cli`) REFERENCES `clientes` (`id_documento_cli`) ON DELETE CASCADE,
  ADD CONSTRAINT `motos_ibfk_2` FOREIGN KEY (`id_cilindraje`) REFERENCES `cilindraje` (`id_cc`),
  ADD CONSTRAINT `motos_ibfk_3` FOREIGN KEY (`id_color`) REFERENCES `color` (`id_color`),
  ADD CONSTRAINT `motos_ibfk_4` FOREIGN KEY (`id_modelo`) REFERENCES `modelos` (`id_modelo`),
  ADD CONSTRAINT `motos_ibfk_5` FOREIGN KEY (`id_referencia_marca`) REFERENCES `referencia_marca` (`id_referencia`);

--
-- Filtros para la tabla `referencia_marca`
--
ALTER TABLE `referencia_marca`
  ADD CONSTRAINT `referencia_marca_ibfk_1` FOREIGN KEY (`id_marcas`) REFERENCES `marcas` (`id_marca`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
