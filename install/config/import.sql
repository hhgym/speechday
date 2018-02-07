-- phpMyAdmin SQL Dump
-- version 2.11.11.3
-- http://www.phpmyadmin.net
--
-- Host: 127.0.0.3
-- Erstellungszeit: 06. Februar 2018 um 13:43
-- Server Version: 5.6.19
-- PHP-Version: 4.4.9

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Datenbank: `db357278_23`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `accessdata`
--

CREATE TABLE `accessdata` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userName` varchar(255) COLLATE utf8_bin NOT NULL,
  `password` varchar(255) COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `userName` (`userName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `event`
--

CREATE TABLE `event` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_bin NOT NULL,
  `dateFrom` int(11) NOT NULL,
  `dateTo` int(11) NOT NULL,
  `slotTimeMin` int(11) NOT NULL DEFAULT '5',
  `breakFrequency` int(11) NOT NULL DEFAULT '0',
  `isActive` int(11) NOT NULL DEFAULT '0',
  `startPostDate` int(11) DEFAULT NULL,
  `finalPostDate` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `log`
--

CREATE TABLE `log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) NOT NULL,
  `action` int(11) NOT NULL COMMENT '1 = logIn, 2 = logOut, 3 = bookSlot, 4 = deleteSlot',
  `info` varchar(255) COLLATE utf8_bin DEFAULT NULL,
  `date` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_log_user` (`userId`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `room`
--

CREATE TABLE `room` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `roomNumber` varchar(255) COLLATE utf8_bin NOT NULL,
  `roomName` varchar(255) COLLATE utf8_bin NOT NULL,
  `teacherId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `teacherId` (`teacherId`),
  KEY `fk_room_user` (`teacherId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `slot`
--

CREATE TABLE `slot` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `eventId` int(11) NOT NULL,
  `teacherId` int(11) NOT NULL,
  `studentId` int(11) DEFAULT NULL,
  `dateFrom` int(11) NOT NULL,
  `dateTo` int(11) NOT NULL,
  `type` int(1) NOT NULL DEFAULT '1' COMMENT '1 = normal, 2 = break',
  `available` int(11) NOT NULL DEFAULT '1' COMMENT '1 = available, 0 = not available',
  `bookedbyteacher` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `fk_slot_event` (`eventId`),
  KEY `fk_slot_teacher` (`teacherId`),
  KEY `fk_slot_student` (`studentId`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `user`
--

CREATE TABLE `user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userName` varchar(255) COLLATE utf8_bin NOT NULL,
  `passwordHash` varchar(255) COLLATE utf8_bin NOT NULL,
  `firstName` varchar(255) COLLATE utf8_bin NOT NULL,
  `lastName` varchar(255) COLLATE utf8_bin NOT NULL,
  `class` varchar(10) COLLATE utf8_bin DEFAULT NULL,
  `role` enum('student','teacher','admin') COLLATE utf8_bin NOT NULL DEFAULT 'student',
  `title` varchar(10) COLLATE utf8_bin DEFAULT '',
  `absent` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `userName` (`userName`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

--
-- Constraints der exportierten Tabellen
--

--
-- Constraints der Tabelle `accessdata`
--
ALTER TABLE `accessdata`
  ADD CONSTRAINT `fk_accessdata_username` FOREIGN KEY (`userName`) REFERENCES `user` (`userName`) ON DELETE CASCADE;

--
-- Constraints der Tabelle `log`
--
ALTER TABLE `log`
  ADD CONSTRAINT `fk_log_user` FOREIGN KEY (`userId`) REFERENCES `user` (`id`) ON DELETE CASCADE;

--
-- Constraints der Tabelle `room`
--
ALTER TABLE `room`
  ADD CONSTRAINT `fk_room_user` FOREIGN KEY (`teacherId`) REFERENCES `user` (`id`) ON DELETE CASCADE;

--
-- Constraints der Tabelle `slot`
--
ALTER TABLE `slot`
  ADD CONSTRAINT `fk_slot_event` FOREIGN KEY (`eventId`) REFERENCES `event` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_slot_student` FOREIGN KEY (`studentId`) REFERENCES `user` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_slot_teacher` FOREIGN KEY (`teacherId`) REFERENCES `user` (`id`) ON DELETE CASCADE;
