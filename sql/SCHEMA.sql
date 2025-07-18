-- phpMyAdmin SQL Dump
-- version 5.2.1deb3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Erstellungszeit: 30. Jun 2025 um 15:59
-- Server-Version: 10.11.13-MariaDB-0ubuntu0.24.04.1
-- PHP-Version: 8.3.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Datenbank: `fluentdb`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `category`
--

CREATE TABLE `category` (
  `id` int(11) NOT NULL,
  `constant` varchar(100) NOT NULL,
  `title` text NOT NULL,
  `multivalue` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Daten für Tabelle `category`
--

INSERT INTO `category` (`id`, `constant`, `title`, `multivalue`) VALUES
(1, 'C__CATG__GLOBAL', 'Allgemein', 0),
(2, 'C__CATS__PERSON_LOGIN', 'Login', 0);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `category_field`
--

CREATE TABLE `category_field` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `constant` varchar(100) NOT NULL,
  `title` text NOT NULL,
  `type` text NOT NULL,
  `ro` tinyint(4) NOT NULL DEFAULT 0,
  `order` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Daten für Tabelle `category_field`
--

INSERT INTO `category_field` (`id`, `category_id`, `constant`, `title`, `type`, `ro`, `order`) VALUES
(1, 1, 'title', 'title', 'text', 0, 0),
(2, 1, 'general-sep1', '', 'separator', 0, 1),
(3, 1, 'category', 'category', 'dialog_plus', 0, 2),
(4, 1, 'purpose', 'purpose', 'dialog_plus', 0, 3),
(5, 1, 'cmdb_status', 'cmdb_status', 'dialog', 0, 4),
(6, 1, 'tag', 'tag', 'multiselect', 0, 5),
(7, 1, 'general-sep2', '', 'separator', 0, 6),
(8, 1, 'sysid', 'sysid', 'text', 0, 7),
(9, 1, 'created', 'created', 'datetime', 1, 8),
(10, 1, 'created_by', 'created_by', 'text', 1, 9),
(11, 1, 'changed', 'changed', 'datetime', 1, 10),
(12, 1, 'changed_by', 'changed_by', 'text', 1, 11),
(13, 1, 'general-sep3', '', 'separator', 0, 12),
(14, 1, 'description', 'description', 'text-multiline', 0, 13),
(15, 2, 'disabled_login', 'login_disabled', 'dialog', 0, 0),
(16, 2, 'username', 'username', 'text', 0, 1),
(17, 2, 'password', 'password', 'text', 1, 2),
(18, 2, 'uid', 'unique_identifier', 'text', 1, 3),
(19, 2, 'last_login', 'last_login', 'datetime', 1, 4);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `dialog_value`
--

CREATE TABLE `dialog_value` (
  `id` int(11) NOT NULL,
  `category_field_id` int(11) NOT NULL,
  `title` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Daten für Tabelle `dialog_value`
--

INSERT INTO `dialog_value` (`id`, `category_field_id`, `title`) VALUES
(1, 5, 'in_operation'),
(2, 5, 'inoperative');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `group`
--

CREATE TABLE `group` (
  `id` int(11) NOT NULL,
  `title` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `list_view`
--

CREATE TABLE `list_view` (
  `id` int(11) NOT NULL,
  `object_type_id` int(11) NOT NULL,
  `user_object_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `list_view_field`
--

CREATE TABLE `list_view_field` (
  `list_view_id` int(11) NOT NULL,
  `category_field_id` int(11) NOT NULL,
  `order` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `log`
--

CREATE TABLE `log` (
  `id` int(11) NOT NULL,
  `timestamp` datetime NOT NULL DEFAULT current_timestamp(),
  `level` tinyint(4) NOT NULL,
  `host` text NOT NULL,
  `user` text DEFAULT NULL,
  `object_id` int(11) DEFAULT NULL,
  `action` text NOT NULL,
  `data` longtext NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `logbook`
--

CREATE TABLE `logbook` (
  `timestamp` datetime NOT NULL DEFAULT current_timestamp(),
  `username` text NOT NULL,
  `user_object_id` int(11) DEFAULT NULL,
  `object_title` text NOT NULL,
  `object_id` int(11) NOT NULL,
  `method` varchar(100) NOT NULL,
  `data` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `object`
--

CREATE TABLE `object` (
  `id` int(11) NOT NULL,
  `object_type_id` int(11) NOT NULL,
  `status` int(11) NOT NULL DEFAULT 2
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `object_category_set`
--

CREATE TABLE `object_category_set` (
  `id` int(11) NOT NULL,
  `object_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 2
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `object_category_value`
--

CREATE TABLE `object_category_value` (
  `object_category_set_id` int(11) NOT NULL,
  `category_field_id` int(11) NOT NULL,
  `value` text NOT NULL,
  `linked_object_id` INT NULL DEFAULT NULL,
  `linked_dialog_value_id` INT NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `object_group`
--

CREATE TABLE `object_group` (
  `id` int(11) NOT NULL,
  `object_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `object_type_group`
--

CREATE TABLE `object_type_group` (
  `id` int(11) NOT NULL,
  `title` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Daten für Tabelle `object_type_group`
--

INSERT INTO `object_type_group` (`id`, `title`) VALUES
(1, 'master_data');

-- --------------------------------------------------------
--
-- Tabellenstruktur für Tabelle `object_type`
--

CREATE TABLE `object_type` (
  `id` int(11) NOT NULL,
  `object_type_group_id` int(11) DEFAULT NULL,
  `title` text NOT NULL,
  `image` mediumblob DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Daten für Tabelle `object_type`
--

INSERT INTO `object_type` (`id`, `object_type_group_id`, `title`, `image`) VALUES
(1, 1, 'person', NULL);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `object_type_category`
--

CREATE TABLE `object_type_category` (
  `id` int(11) NOT NULL,
  `object_type_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `order` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Daten für Tabelle `object_type_category`
--

INSERT INTO `object_type_category` (`id`, `object_type_id`, `category_id`, `order`) VALUES
(1, 1, 1, 0),
(2, 1, 2, 1);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `setting`
--

CREATE TABLE `setting` (
  `key` varchar(100) NOT NULL,
  `value` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Daten für Tabelle `setting`
--

INSERT INTO `setting` (`key`, `value`) VALUES
('api-enabled', '1'),
('api-key', '');

-- --------------------------------------------------------

--
-- Indizes der exportierten Tabellen
--

--
-- Indizes für die Tabelle `category`
--
ALTER TABLE `category`
  ADD PRIMARY KEY (`id`),
  ADD KEY `constant` (`constant`);

--
-- Indizes für die Tabelle `category_field`
--
ALTER TABLE `category_field`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `constant` (`constant`),
  ADD KEY `fk__category_field__category` (`category_id`);

--
-- Indizes für die Tabelle `group`
--
ALTER TABLE `group`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `list_view`
--
ALTER TABLE `list_view`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk__list_view__object_type` (`object_type_id`),
  ADD KEY `fk__list_view__system_user` (`user_object_id`);

--
-- Indizes für die Tabelle `list_view_field`
--
ALTER TABLE `list_view_field`
  ADD PRIMARY KEY (`list_view_id`,`category_field_id`),
  ADD KEY `fk__list_view_field__category_field` (`category_field_id`);

--
-- Indizes für die Tabelle `log`
--
ALTER TABLE `log`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `logbook`
--
ALTER TABLE `logbook`
  ADD KEY `timestamp` (`timestamp`),
  ADD KEY `method` (`method`),
  ADD KEY `user_object_id` (`user_object_id`),
  ADD KEY `object_id` (`object_id`);

--
-- Indizes für die Tabelle `object`
--
ALTER TABLE `object`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk__object__object_type` (`object_type_id`);

--
-- Indizes für die Tabelle `object_category_set`
--
ALTER TABLE `object_category_set`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk__object_catgeory_set__object` (`object_id`),
  ADD KEY `fk__object_category_set__category` (`category_id`);

--
-- Indizes für die Tabelle `object_category_value`
--
ALTER TABLE `object_category_value`
  ADD PRIMARY KEY (`object_category_set_id`,`category_field_id`),
  ADD KEY `fk__object_category_value__category_field` (`category_field_id`);

--
-- Indizes für die Tabelle `object_group`
--
ALTER TABLE `object_group`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `object_type`
--
ALTER TABLE `object_type`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk__object_type__object_type_group` (`object_type_group_id`);

--
-- Indizes für die Tabelle `object_type_category`
--
ALTER TABLE `object_type_category`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk__object_type_category__category` (`category_id`),
  ADD KEY `fk__object_type_category__object_type` (`object_type_id`);

--
-- Indizes für die Tabelle `object_type_group`
--
ALTER TABLE `object_type_group`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `setting`
--
ALTER TABLE `setting`
  ADD PRIMARY KEY (`key`);

--
-- AUTO_INCREMENT für exportierte Tabellen
--

--
-- AUTO_INCREMENT für Tabelle `category`
--
ALTER TABLE `category`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1000;

--
-- AUTO_INCREMENT für Tabelle `category_field`
--
ALTER TABLE `category_field`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1000;

--
-- AUTO_INCREMENT für Tabelle `group`
--
ALTER TABLE `group`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `list_view`
--
ALTER TABLE `list_view`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `log`
--
ALTER TABLE `log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `object`
--
ALTER TABLE `object`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `object_category_set`
--
ALTER TABLE `object_category_set`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `object_group`
--
ALTER TABLE `object_group`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `object_type`
--
ALTER TABLE `object_type`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1000;

--
-- AUTO_INCREMENT für Tabelle `object_type_category`
--
ALTER TABLE `object_type_category`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `object_type_group`
--
ALTER TABLE `object_type_group`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1000;

--
-- Constraints der exportierten Tabellen
--

--
-- Constraints der Tabelle `category_field`
--
ALTER TABLE `category_field`
  ADD CONSTRAINT `fk__category_field__category` FOREIGN KEY (`category_id`) REFERENCES `category` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints der Tabelle `dialog_value`
--
ALTER TABLE `dialog_value`
  ADD CONSTRAINT `fk__dialog_value__category_field` FOREIGN KEY (`category_field_id`) REFERENCES `category_field` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints der Tabelle `list_view`
--
ALTER TABLE `list_view`
  ADD CONSTRAINT `fk__list_view__object_type` FOREIGN KEY (`object_type_id`) REFERENCES `object_type` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk__list_view__system_user` FOREIGN KEY (`user_object_id`) REFERENCES `object` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints der Tabelle `list_view_field`
--
ALTER TABLE `list_view_field`
  ADD CONSTRAINT `fk__list_view_field__category_field` FOREIGN KEY (`category_field_id`) REFERENCES `category_field` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk__list_view_field__list_view` FOREIGN KEY (`list_view_id`) REFERENCES `list_view` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints der Tabelle `object`
--
ALTER TABLE `object`
  ADD CONSTRAINT `fk__object__object_type` FOREIGN KEY (`object_type_id`) REFERENCES `object_type` (`id`);

--
-- Constraints der Tabelle `object_category_set`
--
ALTER TABLE `object_category_set`
  ADD CONSTRAINT `fk__object_category_set__category` FOREIGN KEY (`category_id`) REFERENCES `category` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk__object_catgeory_set__object` FOREIGN KEY (`object_id`) REFERENCES `object` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints der Tabelle `object_category_value`
--
ALTER TABLE `object_category_value`
  ADD CONSTRAINT `fk__object_category_value__category_field` FOREIGN KEY (`category_field_id`) REFERENCES `category_field` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk__object_category_value__dialog_value` FOREIGN KEY (`linked_dialog_value_id`) REFERENCES `dialog_value` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk__object_category_value__object` FOREIGN KEY (`linked_object_id`) REFERENCES `object` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk__object_category_value__object_category_set` FOREIGN KEY (`object_category_set_id`) REFERENCES `object_category_set` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints der Tabelle `object_type`
--
ALTER TABLE `object_type`
  ADD CONSTRAINT `fk__object_type__object_type_group` FOREIGN KEY (`object_type_group_id`) REFERENCES `object_type_group` (`id`);

--
-- Constraints der Tabelle `object_type_category`
--
ALTER TABLE `object_type_category`
  ADD CONSTRAINT `fk__object_type_category__category` FOREIGN KEY (`category_id`) REFERENCES `category` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk__object_type_category__object_type` FOREIGN KEY (`object_type_id`) REFERENCES `object_type` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
