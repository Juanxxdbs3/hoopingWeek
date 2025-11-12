-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 12-11-2025 a las 04:59:26
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
  `request_date` datetime NOT NULL DEFAULT current_timestamp(),
  `rejection_reason` text DEFAULT NULL,
  `notes` text DEFAULT NULL
) ;

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
-- Indices de la tabla `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_reservations_approver` (`approved_by`),
  ADD KEY `idx_res_field_dates` (`field_id`,`start_datetime`,`end_datetime`,`status`),
  ADD KEY `idx_reservation_field` (`field_id`),
  ADD KEY `idx_reservation_applicant` (`applicant_id`),
  ADD KEY `idx_reservation_status` (`status`);

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
  ADD KEY `idx_membership_athlete` (`athlete_id`);

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_user_email` (`email`),
  ADD KEY `fk_users_role` (`role_id`),
  ADD KEY `fk_users_state` (`state_id`),
  ADD KEY `fk_users_athlete_state` (`athlete_state_id`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `teams`
--
ALTER TABLE `teams`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `team_memberships`
--
ALTER TABLE `team_memberships`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

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
