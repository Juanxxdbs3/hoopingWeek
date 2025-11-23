-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 22-11-2025 a las 05:56:47
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
-- Base de datos: `hooping_week`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `championships`
--

CREATE TABLE `championships` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `organizer_id` int(11) NOT NULL,
  `sport` varchar(100) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'planning',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ;

--
-- Volcado de datos para la tabla `championships`
--

INSERT INTO `championships` (`id`, `name`, `organizer_id`, `sport`, `start_date`, `end_date`, `status`, `created_at`) VALUES
(1, 'Torneo Navideño 2025 - Edición Especial', 17, 'basketball', '2025-12-16', '2025-12-23', 'finished', '2025-11-13 00:26:13'),
(3, 'Torneo Navideño 2025', 12, 'futbol', '2025-12-16', '2025-12-22', 'planning', '2025-11-21 14:04:42');

--
-- Disparadores `championships`
--
DELIMITER $$
CREATE TRIGGER `trg_validate_championship_organizer` BEFORE INSERT ON `championships` FOR EACH ROW BEGIN
  -- organizer debe ser trainer (2) o super_admin (4)
  IF NOT EXISTS (SELECT 1 FROM users WHERE id = NEW.organizer_id AND role_id IN (2,4)) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'organizer_id debe tener rol trainer o super_admin';
  END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_validate_championship_organizer_upd` BEFORE UPDATE ON `championships` FOR EACH ROW BEGIN
  IF NEW.organizer_id <> OLD.organizer_id THEN
    IF NOT EXISTS (SELECT 1 FROM users WHERE id = NEW.organizer_id AND role_id IN (2,4)) THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'organizer_id (update) debe tener rol trainer o super_admin';
    END IF;
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `championship_teams`
--

CREATE TABLE `championship_teams` (
  `championship_id` int(11) NOT NULL,
  `team_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `championship_teams`
--

INSERT INTO `championship_teams` (`championship_id`, `team_id`) VALUES
(1, 1),
(3, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `fields`
--

CREATE TABLE `fields` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `location` varchar(255) NOT NULL,
  `width_meters` int(11) DEFAULT NULL,
  `length_meters` int(11) DEFAULT NULL,
  `surface_type` varchar(100) DEFAULT NULL,
  `allowed_sports` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`allowed_sports`)),
  `people_capacity` int(11) DEFAULT NULL,
  `state` varchar(50) NOT NULL DEFAULT 'active',
  `is_open_to_public` tinyint(1) NOT NULL DEFAULT 1,
  `owner_entity` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ;

--
-- Volcado de datos para la tabla `fields`
--

INSERT INTO `fields` (`id`, `name`, `location`, `width_meters`, `length_meters`, `surface_type`, `allowed_sports`, `people_capacity`, `state`, `is_open_to_public`, `owner_entity`, `notes`, `created_at`) VALUES
(3, 'Cancha de Ider', 'Plaza de toros', 70, 90, 'concrete', '[\"basketball\",\"volleyball\",\"football\"]', 200, 'active', 1, 'IDER', 'Cancha principal', '2025-11-11 23:03:29'),
(4, 'Patinodromo', 'Plaza de toros', 50, 75, 'slab', '[\"skating\"]', 80, 'active', 0, 'ACOL', 'Pista principal', '2025-11-12 09:07:39'),
(5, 'Pista de atletismo', 'Cartagena, Plaza de toros', 70, 80, 'runite', '[\"athletism\",\"jabaline\",\"fence\"]', 600, 'inactive', 1, 'IDERBOL', 'Pista principal', '2025-11-12 09:46:33'),
(6, '', 'Barranquilla, Soledad 2000', 80, 120, 'runite', '[\"football\",\"practice\"]', 600, 'active', 0, 'Alcaldia de Barranquilla', 'Estadio principal', '2025-11-12 09:49:05');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `field_operating_hours`
--

CREATE TABLE `field_operating_hours` (
  `id` int(11) NOT NULL,
  `field_id` int(11) NOT NULL,
  `day_of_week` tinyint(4) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL
) ;

--
-- Volcado de datos para la tabla `field_operating_hours`
--

INSERT INTO `field_operating_hours` (`id`, `field_id`, `day_of_week`, `start_time`, `end_time`) VALUES
(1, 5, 1, '06:00:00', '22:00:00'),
(2, 5, 2, '06:00:00', '22:00:00'),
(3, 5, 3, '06:00:00', '22:00:00'),
(4, 5, 4, '06:00:00', '22:00:00'),
(5, 5, 5, '06:00:00', '22:00:00'),
(6, 5, 6, '06:00:00', '22:00:00'),
(7, 5, 0, '06:00:00', '22:00:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `field_schedule_exceptions`
--

CREATE TABLE `field_schedule_exceptions` (
  `id` int(11) NOT NULL,
  `field_id` int(11) NOT NULL,
  `specific_date` date NOT NULL,
  `reason` varchar(100) NOT NULL,
  `overrides_regular` tinyint(1) NOT NULL DEFAULT 1,
  `special_start_time` time DEFAULT NULL,
  `special_end_time` time DEFAULT NULL
) ;

--
-- Volcado de datos para la tabla `field_schedule_exceptions`
--

INSERT INTO `field_schedule_exceptions` (`id`, `field_id`, `specific_date`, `reason`, `overrides_regular`, `special_start_time`, `special_end_time`) VALUES
(1, 5, '2025-12-25', 'Mantenimiento programado', 1, NULL, NULL),
(2, 5, '2026-01-01', 'Año Nuevo - Horario reducido', 1, '12:00:00', '20:00:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `holidays`
--

CREATE TABLE `holidays` (
  `id` int(11) NOT NULL,
  `holiday_date` date NOT NULL,
  `name` varchar(255) NOT NULL,
  `is_national` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `holidays`
--

INSERT INTO `holidays` (`id`, `holiday_date`, `name`, `is_national`) VALUES
(1, '2025-01-01', 'Año Nuevo', 1),
(2, '2025-01-06', 'Día de los Reyes Magos', 1),
(3, '2025-03-24', 'Día de San José', 1),
(4, '2025-04-17', 'Jueves Santo', 1),
(5, '2025-04-18', 'Viernes Santo', 1),
(6, '2025-05-01', 'Primero de Mayo', 1),
(7, '2025-06-02', 'Ascensión del señor', 1),
(8, '2025-06-23', 'Corpus Christi', 1),
(9, '2025-06-30', 'San Pedro y San Pablo', 1),
(10, '2025-07-20', 'Declaracion de la Independencia de Colombia', 1),
(11, '2025-08-07', 'Batalla de Boyacá', 1),
(12, '2025-08-18', 'La Asunción', 1),
(13, '2025-10-13', 'Día de la Raza', 1),
(14, '2025-11-03', 'Dia de los Santos', 1),
(15, '2025-11-17', 'Independencia de Cartagena', 1),
(16, '2025-12-08', 'La Inmaculada Concepción', 1),
(17, '2025-12-25', 'Navidad', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `manager_shifts`
--

CREATE TABLE `manager_shifts` (
  `id` int(11) NOT NULL,
  `manager_id` int(11) NOT NULL,
  `field_id` int(11) NOT NULL,
  `day_of_week` tinyint(4) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `note` text DEFAULT NULL
) ;

--
-- Volcado de datos para la tabla `manager_shifts`
--

INSERT INTO `manager_shifts` (`id`, `manager_id`, `field_id`, `day_of_week`, `start_time`, `end_time`, `active`, `note`) VALUES
(2, 16, 6, 1, '16:00:00', '20:00:00', 1, 'Turno tarde lunes'),
(3, 16, 4, 1, '08:00:00', '16:00:00', 1, 'Turno matutino lunes'),
(5, 16, 5, 3, '14:00:00', '18:00:00', 1, 'Turno miércoles tarde');

--
-- Disparadores `manager_shifts`
--
DELIMITER $$
CREATE TRIGGER `trg_validate_shift_manager` BEFORE INSERT ON `manager_shifts` FOR EACH ROW BEGIN
  -- manager_id debe tener rol field_manager (3)
  IF NOT EXISTS (SELECT 1 FROM users WHERE id = NEW.manager_id AND role_id = 3) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'manager_id debe tener rol field_manager';
  END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_validate_shift_manager_upd` BEFORE UPDATE ON `manager_shifts` FOR EACH ROW BEGIN
  IF NEW.manager_id <> OLD.manager_id THEN
    IF NOT EXISTS (SELECT 1 FROM users WHERE id = NEW.manager_id AND role_id = 3) THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'manager_id (update) debe tener rol field_manager';
    END IF;
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `matches`
--

CREATE TABLE `matches` (
  `id` int(11) NOT NULL,
  `reservation_id` int(11) NOT NULL,
  `team1_id` int(11) NOT NULL,
  `team2_id` int(11) NOT NULL,
  `is_friendly` tinyint(1) NOT NULL DEFAULT 1,
  `championship_id` int(11) DEFAULT NULL
) ;

--
-- Volcado de datos para la tabla `matches`
--

INSERT INTO `matches` (`id`, `reservation_id`, `team1_id`, `team2_id`, `is_friendly`, `championship_id`) VALUES
(2, 2, 1, 2, 1, NULL),
(3, 1, 1, 3, 0, NULL),
(4, 29, 1, 2, 0, 3);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `phinxlog`
--

CREATE TABLE `phinxlog` (
  `version` bigint(20) NOT NULL,
  `migration_name` varchar(100) DEFAULT NULL,
  `start_time` timestamp NULL DEFAULT NULL,
  `end_time` timestamp NULL DEFAULT NULL,
  `breakpoint` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `phinxlog`
--

INSERT INTO `phinxlog` (`version`, `migration_name`, `start_time`, `end_time`, `breakpoint`) VALUES
(20251113220000, 'InitialSchema', '2025-11-14 06:39:38', '2025-11-14 06:39:38', 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reservations`
--

CREATE TABLE `reservations` (
  `id` int(11) NOT NULL,
  `field_id` int(11) NOT NULL,
  `applicant_id` int(11) NOT NULL,
  `activity_type` varchar(100) NOT NULL,
  `start_datetime` datetime NOT NULL,
  `end_datetime` datetime NOT NULL,
  `duration_hours` decimal(4,2) NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'pending',
  `priority` int(11) NOT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `request_date` datetime NOT NULL DEFAULT current_timestamp(),
  `rejection_reason` text DEFAULT NULL,
  `rejected_at` datetime DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `cancelled_by` int(11) DEFAULT NULL,
  `cancellation_reason` varchar(500) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `soft_deleted` tinyint(1) NOT NULL DEFAULT 0
) ;

--
-- Volcado de datos para la tabla `reservations`
--

INSERT INTO `reservations` (`id`, `field_id`, `applicant_id`, `activity_type`, `start_datetime`, `end_datetime`, `duration_hours`, `status`, `priority`, `approved_by`, `approved_at`, `request_date`, `rejection_reason`, `rejected_at`, `cancelled_at`, `cancelled_by`, `cancellation_reason`, `notes`, `soft_deleted`) VALUES
(1, 5, 15, 'friendly_match', '2025-11-19 14:00:00', '2025-11-19 16:00:00', 2.00, 'approved', 5, 16, NULL, '2025-11-12 22:24:15', NULL, NULL, NULL, NULL, NULL, 'cuiden la canchita', 0),
(2, 5, 10, 'practice_group', '2025-11-20 15:00:00', '2025-11-20 17:00:00', 2.00, 'approved', 5, 14, NULL, '2025-11-12 22:44:19', NULL, NULL, NULL, NULL, NULL, NULL, 0),
(6, 3, 15, 'practice_individual', '2025-11-20 14:00:00', '2025-11-20 16:00:00', 2.00, 'cancelled', 4, 17, '2025-11-17 14:50:42', '2025-11-14 01:44:19', NULL, NULL, '2025-11-17 15:06:02', 15, 'No puedo asistir por motivos personales', NULL, 0),
(7, 3, 15, 'practice_individual', '2025-11-25 14:00:00', '2025-11-25 16:00:00', 2.00, 'approved', 4, 17, '2025-11-18 10:00:00', '2025-11-14 07:27:00', NULL, NULL, NULL, NULL, NULL, NULL, 0),
(8, 3, 22, 'practice_group', '2025-11-28 15:00:00', '2025-11-28 17:00:00', 2.00, 'approved', 2, 17, '2025-11-17 15:04:37', '2025-11-14 07:39:56', NULL, NULL, NULL, NULL, NULL, ' [Desplazada por campeonato ID 30]', 0),
(9, 3, 24342754, 'practice_individual', '2025-11-25 14:16:00', '2025-11-25 16:17:00', 2.02, 'approved', 3, 1, '2025-11-20 04:56:19', '2025-11-14 09:40:48', NULL, NULL, NULL, NULL, NULL, NULL, 0),
(10, 3, 32876609, 'practice_individual', '2025-11-25 14:16:00', '2025-11-25 16:17:00', 2.02, 'approved', 3, 17, '2025-11-20 13:38:24', '2025-11-14 09:42:34', NULL, NULL, NULL, NULL, NULL, NULL, 0),
(11, 3, 32876609, 'practice_individual', '2025-11-25 14:16:00', '2025-11-25 16:17:00', 2.02, 'approved', 3, 17, '2025-11-20 16:53:35', '2025-11-14 09:44:19', NULL, NULL, NULL, NULL, NULL, NULL, 0),
(12, 5, 10, 'practice_individual', '2025-11-25 13:00:00', '2025-11-25 15:00:00', 2.00, 'approved', 4, NULL, NULL, '2025-11-14 21:02:15', NULL, NULL, NULL, NULL, NULL, NULL, 0),
(13, 5, 15, 'practice_individual', '2025-11-25 14:00:00', '2025-11-25 16:00:00', 2.00, 'approved', 4, NULL, NULL, '2025-11-14 21:04:59', NULL, NULL, NULL, NULL, NULL, NULL, 0),
(14, 5, 10, 'practice_group', '2025-11-26 09:00:00', '2025-11-26 11:00:00', 2.00, 'approved', 3, NULL, NULL, '2025-11-14 21:05:54', NULL, NULL, NULL, NULL, NULL, NULL, 0),
(15, 5, 32876609, 'practice_individual', '2025-11-27 09:00:00', '2025-11-27 11:00:00', 2.00, 'approved', 3, NULL, NULL, '2025-11-14 21:07:25', NULL, NULL, NULL, NULL, NULL, NULL, 0),
(16, 5, 12, 'practice_individual', '2025-11-28 09:00:00', '2025-11-28 11:00:00', 2.00, 'approved', 3, NULL, NULL, '2025-11-14 21:08:35', NULL, NULL, NULL, NULL, NULL, NULL, 0),
(17, 5, 10, 'practice_individual', '2025-11-25 17:00:00', '2025-11-25 19:00:00', 2.00, 'approved', 4, NULL, NULL, '2025-11-14 22:00:45', NULL, NULL, NULL, NULL, NULL, NULL, 0),
(18, 5, 15, 'practice_individual', '2025-11-25 17:00:00', '2025-11-25 19:00:00', 2.00, 'approved', 4, NULL, NULL, '2025-11-14 22:01:44', NULL, NULL, NULL, NULL, NULL, NULL, 0),
(19, 5, 10, 'practice_individual', '2025-11-29 14:00:00', '2025-11-29 16:00:00', 2.00, 'approved', 4, NULL, NULL, '2025-11-14 22:14:50', NULL, NULL, NULL, NULL, NULL, NULL, 0),
(20, 5, 10, 'practice_individual', '2025-11-29 14:00:00', '2025-11-29 16:00:00', 2.00, 'approved', 4, NULL, NULL, '2025-11-14 22:15:11', NULL, NULL, NULL, NULL, NULL, NULL, 0),
(21, 5, 10, 'practice_individual', '2025-11-30 13:00:00', '2025-11-30 15:00:00', 2.00, 'approved', 4, NULL, NULL, '2025-11-14 22:20:49', NULL, NULL, NULL, NULL, NULL, NULL, 0),
(22, 5, 10, 'match_friendly', '2025-12-04 14:00:00', '2025-12-04 16:00:00', 2.00, 'rejected', 2, 17, NULL, '2025-11-14 22:30:16', 'Campo en mantenimiento ese día', NULL, NULL, NULL, NULL, NULL, 0),
(23, 3, 10, 'match_friendly', '2025-12-05 14:00:00', '2025-12-05 16:00:00', 2.00, 'approved', 2, 17, NULL, '2025-11-17 16:28:14', NULL, NULL, NULL, NULL, NULL, NULL, 0),
(24, 5, 21, 'match_friendly', '2025-12-08 14:00:00', '2025-12-08 16:00:00', 2.00, 'approved', 2, 17, '2025-11-17 15:30:12', '2025-11-17 20:29:08', NULL, NULL, NULL, NULL, NULL, NULL, 0),
(25, 5, 15, 'practice_individual', '2025-11-20 07:00:00', '2025-11-20 09:00:00', 2.00, 'approved', 4, NULL, NULL, '2025-11-19 15:07:25', NULL, NULL, NULL, NULL, NULL, NULL, 0),
(26, 5, 15, 'practice_individual', '2025-11-20 09:00:00', '2025-11-20 11:00:00', 2.00, 'cancelled', 4, NULL, NULL, '2025-11-20 10:22:40', NULL, NULL, '2025-11-20 05:24:58', 1, 'me apetecia jaja', NULL, 0),
(27, 5, 1008888888, 'practice_individual', '2025-11-21 15:30:00', '2025-11-21 17:30:00', 2.00, 'cancelled', 4, NULL, NULL, '2025-11-20 17:01:18', NULL, NULL, '2025-11-20 12:06:57', 16, 'I am trying to prove the endpoint', NULL, 0),
(28, 5, 12, 'match_championship', '2025-12-16 13:00:00', '2025-12-16 15:00:00', 2.00, 'pending', 1, NULL, NULL, '2025-11-21 19:22:41', NULL, NULL, NULL, NULL, NULL, NULL, 0),
(29, 5, 12, 'match_championship', '2025-12-11 13:00:00', '2025-12-13 15:00:00', 50.00, 'pending', 1, NULL, NULL, '2025-11-21 20:04:12', NULL, NULL, NULL, NULL, NULL, NULL, 0),
(30, 3, 12, 'match_championship', '2025-11-26 07:00:00', '2025-11-28 15:00:00', 56.00, 'approved', 1, 17, '2025-11-21 16:58:11', '2025-11-21 21:47:20', NULL, NULL, NULL, NULL, NULL, 'Probando solapamientode reservas', 0),
(31, 5, 2147483647, 'practice_individual', '2025-11-22 09:00:00', '2025-11-22 10:00:00', 1.00, 'approved', 4, NULL, NULL, '2025-11-22 02:51:30', NULL, NULL, NULL, NULL, NULL, NULL, 0),
(32, 3, 2147483647, 'practice_individual', '2025-11-29 09:00:00', '2025-11-29 10:00:00', 1.00, 'approved', 4, NULL, NULL, '2025-11-22 03:08:31', NULL, NULL, NULL, NULL, NULL, NULL, 0),
(33, 3, 15, 'practice_individual', '2025-12-16 13:00:00', '2025-12-16 14:00:00', 1.00, 'approved', 4, NULL, NULL, '2025-11-22 03:49:30', NULL, NULL, NULL, NULL, NULL, NULL, 0),
(34, 5, 15, 'practice_individual', '2025-12-20 14:00:00', '2025-12-20 15:00:00', 1.00, 'approved', 4, NULL, NULL, '2025-11-22 04:20:00', NULL, NULL, NULL, NULL, NULL, NULL, 0),
(35, 5, 18, 'practice_individual', '2025-12-17 08:00:00', '2025-12-17 09:00:00', 1.00, 'approved', 4, NULL, NULL, '2025-11-22 04:25:48', NULL, NULL, NULL, NULL, NULL, NULL, 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reservation_participants`
--

CREATE TABLE `reservation_participants` (
  `id` int(11) NOT NULL,
  `reservation_id` int(11) NOT NULL,
  `participant_id` int(11) NOT NULL,
  `participant_type` varchar(50) NOT NULL,
  `team_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `reservation_participants`
--

INSERT INTO `reservation_participants` (`id`, `reservation_id`, `participant_id`, `participant_type`, `team_id`) VALUES
(2, 6, 15, 'individual', NULL),
(3, 7, 15, 'individual', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`id`, `name`, `description`) VALUES
(1, 'athlete', 'Athlete user'),
(2, 'trainer', 'Trainer'),
(3, 'field_manager', 'Field manager'),
(4, 'super_admin', 'Super administrator');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `teams`
--

CREATE TABLE `teams` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `sport` varchar(100) NOT NULL,
  `type` varchar(100) NOT NULL,
  `trainer_id` int(11) NOT NULL,
  `locality` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `teams`
--

INSERT INTO `teams` (`id`, `name`, `sport`, `type`, `trainer_id`, `locality`, `created_at`) VALUES
(1, 'Los Halcones FC', 'tenis', 'club', 13, 'Barranquilla', '2025-11-12 12:44:16'),
(2, 'Junior de Barranquilla', 'futbol', 'seleccion', 12, 'Barranquilla', '2025-11-12 12:46:14'),
(3, 'Real Cartagena', 'basketball', 'informal', 14, 'Cartagena', '2025-11-12 13:01:29'),
(5, 'Los Tigres', 'futbol', 'club', 12, 'Cartagena', '2025-11-21 13:54:45'),
(6, 'Los Leonsitos', 'futbol', 'club', 12, 'Cartagena', '2025-11-21 13:59:18');

--
-- Disparadores `teams`
--
DELIMITER $$
CREATE TRIGGER `trg_validate_team_trainer` BEFORE INSERT ON `teams` FOR EACH ROW BEGIN
  -- trainer_id debe tener rol trainer (2)
  IF NOT EXISTS (SELECT 1 FROM users WHERE id = NEW.trainer_id AND role_id = 2) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'trainer_id debe tener rol trainer';
  END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_validate_team_trainer_upd` BEFORE UPDATE ON `teams` FOR EACH ROW BEGIN
  IF NEW.trainer_id <> OLD.trainer_id THEN
    IF NOT EXISTS (SELECT 1 FROM users WHERE id = NEW.trainer_id AND role_id = 2) THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'trainer_id (update) debe tener rol trainer';
    END IF;
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `team_memberships`
--

CREATE TABLE `team_memberships` (
  `id` int(11) NOT NULL,
  `team_id` int(11) NOT NULL,
  `athlete_id` int(11) NOT NULL,
  `join_date` date NOT NULL DEFAULT curdate(),
  `state` varchar(50) NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `team_memberships`
--

INSERT INTO `team_memberships` (`id`, `team_id`, `athlete_id`, `join_date`, `state`) VALUES
(1, 1, 10, '2025-01-15', 'active');

--
-- Disparadores `team_memberships`
--
DELIMITER $$
CREATE TRIGGER `trg_validate_membership_athlete` BEFORE INSERT ON `team_memberships` FOR EACH ROW BEGIN
  -- athlete_id debe tener rol athlete (1)
  IF NOT EXISTS (SELECT 1 FROM users WHERE id = NEW.athlete_id AND role_id = 1) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'athlete_id debe tener rol athlete';
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `first_name` varchar(150) DEFAULT NULL,
  `last_name` varchar(150) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `role_id` int(11) NOT NULL DEFAULT 1,
  `state_id` int(11) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `height` float DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `athlete_state_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `users`
--

INSERT INTO `users` (`id`, `email`, `first_name`, `last_name`, `phone`, `password_hash`, `role_id`, `state_id`, `created_at`, `height`, `birth_date`, `athlete_state_id`) VALUES
(1, 'admin@test.com', 'admin', 'test', '3000000000', NULL, 4, 1, '2025-11-18 12:31:18', NULL, NULL, NULL),
(2, 'manager@test.com', 'manager', 'test', '3000000001', '123', 3, 1, '2025-11-18 12:32:50', NULL, NULL, NULL),
(10, 'Farid@example.com', 'Farid', 'Mondragon', '3146547262', '$2y$10$Y5FRpX1gG9e75NNq9c967OHBSlliWRUBaPov6WU2T.qrxetYFFv.O', 1, 1, '2025-11-12 15:32:14', 1.85, '1980-07-12', NULL),
(12, 'Juan@example.com', 'Juan Diego', 'Bello', '3146547261', '$2y$10$FdZv.SXbfWJ2JyOzHicthuJiBENKClKtXKT5cOhAuTDRw9PoQttPq', 2, 1, '2025-11-12 15:33:49', 1.85, '2002-07-12', NULL),
(13, 'Jose@example.com', 'Jose', 'Villa', '3027654528', '$2y$10$oMO2fwIueffYz/zcLY42je2BYwjaiCYCYxHIOGRNJ6n1wcSL3sD76', 2, 1, '2025-11-12 17:42:31', 1.85, '1980-07-12', NULL),
(14, 'Perkeman@example.com', 'Jose', 'Pekerman', '3146547261', '$2y$10$yR6HiJN42pRvg5C5/CgSGOqxotT917Z7UIaAXxl6P.J3ywU8UuUwS', 2, 1, '2025-11-12 18:00:44', 1.75, '1980-07-12', NULL),
(15, 'Luis@example.com', 'Luis', 'Perez', '3146547362', '$2y$10$BslPXkflLsbIW7PqJooNtO960EiSFgU2tZNtfdggNgrMNUXJ0IQiW', 1, 1, '2025-11-12 18:16:17', 1.78, '1990-07-12', NULL),
(16, 'mejora@example.com', 'El mejor', 'Admin', '3146547265', '$2y$10$JvwEVzjNNu9SjiXo/FmJW.X3M9ZlCmEB0Wx05jrnY8ZwAxKYHU.iO', 3, 1, '2025-11-12 22:40:26', NULL, '1999-06-15', NULL),
(17, 'Boris@example.com', 'Boris', 'Mussolini', '3146547262', '$2y$10$gGoKKVigaR5kv64KNaMRJeVmoUDvFdd98WSgo0SMfR9naWytcQFWy', 4, 1, '2025-11-13 05:24:05', 1.92, '1953-07-12', NULL),
(18, 'carlos.martinez@example.com', 'Carlos', 'Martínez', '3001234567', '$2y$10$PV4Unk7uuHaAEzsCQeWCK.cCQmeN5qf5SWxSWRtwnELdf9epMBVpa', 1, 1, '2025-11-14 07:05:57', 1.75, '2000-05-15', 4),
(19, 'ana.lopez@example.com', 'Ana', 'López', '3209876543', '$2y$10$WUi2UXSP7nTT2x4H5VHhU.qbyMg03DzieHJbAIOOkbwNabjl8/jCq', 2, 1, '2025-11-14 07:07:03', NULL, '1985-08-20', NULL),
(20, 'pedro@example.com', 'Pedro', 'Sánchez', '3001111111', '$2y$10$vfT7O3EcmhOHaMSn0q32jOur9kkcamHlult53jz8Nno84vo1j2N5y', 1, 1, '2025-11-14 07:08:17', NULL, '1998-03-10', 4),
(21, 'nuevo@example.com', 'Otro', 'Usuario', '3003333333', '$2y$10$pflnxa50AOFjszVDuTTpMODXxHYwGgYQwbbi1Q4Ocs6yB9l8ACCaa', 1, 1, '2025-11-14 07:14:28', 1.7, '2001-01-01', 4),
(22, 'trainer@example.com', 'Juan', 'Trainer', '3005555555', '$2y$10$zcACU9kyqv.GmnUWsBqod.1kH9nos85oGAB8l8eau.iQkjQf6lbq6', 2, 1, '2025-11-14 07:39:00', NULL, '1980-01-01', NULL),
(764536, 'stringoso@example.com', 'stringoso', 'stringuito', '9861103789', '$2y$10$cYDVAR4qmrS1/V4rhoD/p.kO56A3Yx6605YjtB/Dq4E9Sc8aeHB8u', 1, 1, '2025-11-20 19:57:15', 1.7, '2015-10-20', NULL),
(24342754, 'potasio@example.com', 'Potasio', 'Manager', '3005555555', '$2y$10$RWS.NuZIomVQtvrErDOKzOqFPOEmRBTQp7KVUA7K4H.B098hXdXGW', 2, 1, '2025-11-14 09:21:47', NULL, '1999-01-07', NULL),
(32876609, 'Poloto@example.com', 'Polo', 'Polito', '3146547262', '$2y$10$UIMw0qwpeys/mDE4UMGXUOtTaLDBPxD6WMo1VFM4MA7WNvD/mOF06', 3, 1, '2025-11-14 09:14:00', 1.85, '1980-07-12', NULL),
(98764567, 'Contrera@test.com', 'Carlos', 'Contrera', '8276543263', '$2y$10$iIdyAaOw7oloZLlT45a2UOeQm4YVOETcuqCeJpduJ0zrFxRB5Uonu', 1, 1, '2025-11-20 20:38:42', 1.82, '2010-05-09', NULL),
(98765212, 'juan@test.com', 'Juan', 'Bello', '3215768938', '$2y$10$C4fBwGFbByaykonc5GnnKOXTSLcEUdnbutas46f2BTpSOZv8RmQeC', 1, 1, '2025-11-20 20:10:55', 1.64, '2002-06-01', NULL),
(765402192, 'rulfo@example.com', 'Juan', 'Rulfo', '3215768938', '$2y$10$JUO76cjzo6j7gfCOXkVe0ePguPKmgHKOqxg6kJBGkoBlYDQOePdLO', 2, 1, '2025-11-22 02:44:22', NULL, '2001-02-12', NULL),
(1008888888, 'sanchez@example.com', 'Pedro', 'Sánchez', '3001111111', '$2y$10$wT9V8WpY/4L5WOkdYkA1veG8CZXgtiuh0zCWdBKDCllsIGypbmTPK', 1, 1, '2025-11-14 09:26:37', 1.78, '1998-03-10', NULL),
(1037987564, 'Wilo@example.com', 'Papi', 'Wilou', '3146547262', '$2y$10$BqE8YqcR8/K9nfcmV8cUeuQ5sqj4pjWlIHsb/5v4HM4hDVR45Nk/.', 1, 1, '2025-11-20 07:06:23', 1.85, '1980-07-12', NULL),
(2147483647, 'pepita@example.com', 'Pepita', 'Jimenez', '3204342688', '$2y$10$RkAPgOwF43VIIig/ySOUZeZoZEy8KP.OipCEceamS08rPaAhJk7dS', 1, 1, '2025-11-22 02:38:00', 1.75, '1999-07-01', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `user_states`
--

CREATE TABLE `user_states` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `applies_to` varchar(50) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `user_states`
--

INSERT INTO `user_states` (`id`, `name`, `applies_to`, `description`) VALUES
(1, 'active', NULL, NULL),
(2, 'inactive', NULL, NULL),
(3, 'suspended', NULL, NULL),
(4, 'in_championship', 'athlete', NULL),
(5, 'injured', 'athlete', NULL);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `championships`
--
ALTER TABLE `championships`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_championship_organizer` (`organizer_id`),
  ADD KEY `idx_championship_dates` (`start_date`,`end_date`);

--
-- Indices de la tabla `championship_teams`
--
ALTER TABLE `championship_teams`
  ADD PRIMARY KEY (`championship_id`,`team_id`),
  ADD KEY `fk_champteams_team` (`team_id`);

--
-- Indices de la tabla `fields`
--
ALTER TABLE `fields`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_field_state` (`state`),
  ADD KEY `idx_field_location` (`location`);

--
-- Indices de la tabla `field_operating_hours`
--
ALTER TABLE `field_operating_hours`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ophours_field` (`field_id`);

--
-- Indices de la tabla `field_schedule_exceptions`
--
ALTER TABLE `field_schedule_exceptions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_field_date` (`field_id`,`specific_date`),
  ADD KEY `idx_exceptions_field` (`field_id`);

--
-- Indices de la tabla `holidays`
--
ALTER TABLE `holidays`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_holiday_date` (`holiday_date`);

--
-- Indices de la tabla `manager_shifts`
--
ALTER TABLE `manager_shifts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_shift_manager` (`manager_id`),
  ADD KEY `idx_shift_field` (`field_id`);

--
-- Indices de la tabla `matches`
--
ALTER TABLE `matches`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `reservation_id` (`reservation_id`),
  ADD KEY `fk_matches_team2` (`team2_id`),
  ADD KEY `idx_match_championship` (`championship_id`),
  ADD KEY `idx_match_teams` (`team1_id`,`team2_id`);

--
-- Indices de la tabla `phinxlog`
--
ALTER TABLE `phinxlog`
  ADD PRIMARY KEY (`version`);

--
-- Indices de la tabla `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_reservations_approver` (`approved_by`),
  ADD KEY `idx_res_field_dates` (`field_id`,`start_datetime`,`end_datetime`,`status`),
  ADD KEY `idx_reservation_field` (`field_id`),
  ADD KEY `idx_reservation_applicant` (`applicant_id`),
  ADD KEY `idx_reservation_status` (`status`),
  ADD KEY `idx_reservations_activity` (`activity_type`),
  ADD KEY `idx_approved_at` (`approved_at`),
  ADD KEY `idx_status_approved_at` (`status`,`approved_at`);

--
-- Indices de la tabla `reservation_participants`
--
ALTER TABLE `reservation_participants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_res_participant` (`reservation_id`,`participant_id`),
  ADD KEY `fk_participants_team` (`team_id`),
  ADD KEY `idx_participant_reservation` (`reservation_id`),
  ADD KEY `idx_participant_user` (`participant_id`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indices de la tabla `teams`
--
ALTER TABLE `teams`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_team_trainer` (`trainer_id`);

--
-- Indices de la tabla `team_memberships`
--
ALTER TABLE `team_memberships`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_team_athlete` (`team_id`,`athlete_id`),
  ADD KEY `idx_membership_team` (`team_id`),
  ADD KEY `idx_membership_athlete` (`athlete_id`),
  ADD KEY `idx_team_memberships_athlete` (`athlete_id`,`state`);

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_user_email` (`email`),
  ADD KEY `fk_users_state` (`state_id`),
  ADD KEY `fk_users_athlete_state` (`athlete_state_id`),
  ADD KEY `idx_users_created_at` (`created_at`),
  ADD KEY `idx_users_role` (`role_id`,`created_at`);

--
-- Indices de la tabla `user_states`
--
ALTER TABLE `user_states`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `championships`
--
ALTER TABLE `championships`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `fields`
--
ALTER TABLE `fields`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `field_operating_hours`
--
ALTER TABLE `field_operating_hours`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `field_schedule_exceptions`
--
ALTER TABLE `field_schedule_exceptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `holidays`
--
ALTER TABLE `holidays`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT de la tabla `manager_shifts`
--
ALTER TABLE `manager_shifts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `matches`
--
ALTER TABLE `matches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `reservations`
--
ALTER TABLE `reservations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `reservation_participants`
--
ALTER TABLE `reservation_participants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `teams`
--
ALTER TABLE `teams`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `team_memberships`
--
ALTER TABLE `team_memberships`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `user_states`
--
ALTER TABLE `user_states`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `championships`
--
ALTER TABLE `championships`
  ADD CONSTRAINT `fk_championships_organizer` FOREIGN KEY (`organizer_id`) REFERENCES `users` (`id`);

--
-- Filtros para la tabla `championship_teams`
--
ALTER TABLE `championship_teams`
  ADD CONSTRAINT `fk_champteams_championship` FOREIGN KEY (`championship_id`) REFERENCES `championships` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_champteams_team` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `field_operating_hours`
--
ALTER TABLE `field_operating_hours`
  ADD CONSTRAINT `fk_ophours_field` FOREIGN KEY (`field_id`) REFERENCES `fields` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `field_schedule_exceptions`
--
ALTER TABLE `field_schedule_exceptions`
  ADD CONSTRAINT `fk_exceptions_field` FOREIGN KEY (`field_id`) REFERENCES `fields` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `manager_shifts`
--
ALTER TABLE `manager_shifts`
  ADD CONSTRAINT `fk_shifts_field` FOREIGN KEY (`field_id`) REFERENCES `fields` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_shifts_manager` FOREIGN KEY (`manager_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `matches`
--
ALTER TABLE `matches`
  ADD CONSTRAINT `fk_matches_championship` FOREIGN KEY (`championship_id`) REFERENCES `championships` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_matches_reservation` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_matches_team1` FOREIGN KEY (`team1_id`) REFERENCES `teams` (`id`),
  ADD CONSTRAINT `fk_matches_team2` FOREIGN KEY (`team2_id`) REFERENCES `teams` (`id`);

--
-- Filtros para la tabla `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `fk_reservations_applicant` FOREIGN KEY (`applicant_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_reservations_approver` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_reservations_field` FOREIGN KEY (`field_id`) REFERENCES `fields` (`id`);

--
-- Filtros para la tabla `reservation_participants`
--
ALTER TABLE `reservation_participants`
  ADD CONSTRAINT `fk_participants_reservation` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_participants_team` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_participants_user` FOREIGN KEY (`participant_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `teams`
--
ALTER TABLE `teams`
  ADD CONSTRAINT `fk_teams_trainer` FOREIGN KEY (`trainer_id`) REFERENCES `users` (`id`);

--
-- Filtros para la tabla `team_memberships`
--
ALTER TABLE `team_memberships`
  ADD CONSTRAINT `fk_memberships_athlete` FOREIGN KEY (`athlete_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_memberships_team` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_athlete_state` FOREIGN KEY (`athlete_state_id`) REFERENCES `user_states` (`id`),
  ADD CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`),
  ADD CONSTRAINT `fk_users_state` FOREIGN KEY (`state_id`) REFERENCES `user_states` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
