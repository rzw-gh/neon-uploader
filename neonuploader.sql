-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Sep 17, 2024 at 12:25 AM
-- Server version: 5.7.44
-- PHP Version: 8.3.8

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `neonuploader`
--

-- --------------------------------------------------------

CREATE DATABASE IF NOT EXISTS neonuploader;

--
-- Table structure for table `config`
--

CREATE TABLE `config` (
  `id` int(11) NOT NULL,
  `developer_tid` bigint(11) NOT NULL,
  `bot_token` varchar(46) NOT NULL,
  `super_user_tid` mediumtext NOT NULL,
  `channel_id` mediumtext NOT NULL,
  `bot_username` varchar(64) NOT NULL,
  `content_channel_id` varchar(128) DEFAULT NULL,
  `admin_users_tid` mediumtext,
  `maintance` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `content`
--

CREATE TABLE `content` (
  `id` int(11) NOT NULL,
  `author` bigint(20) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `show_type` enum('film','serie') NOT NULL DEFAULT 'serie',
  `title` varchar(128) DEFAULT NULL,
  `description` text,
  `main_season_id` bigint(11) DEFAULT NULL,
  `season` int(11) DEFAULT NULL,
  `episode` int(11) DEFAULT NULL,
  `quality` enum('480','720','1080') DEFAULT NULL,
  `post_id` int(11) NOT NULL,
  `view` int(11) NOT NULL DEFAULT '0',
  `first_reaction` varchar(11) NOT NULL DEFAULT '?',
  `first_reaction_count` int(11) NOT NULL DEFAULT '0',
  `second_reaction` varchar(11) NOT NULL DEFAULT '?',
  `second_reaction_count` int(11) NOT NULL DEFAULT '0',
  `omit` tinyint(1) NOT NULL DEFAULT '1',
  `download` tinyint(1) NOT NULL DEFAULT '1',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `delayed_message`
--

CREATE TABLE `delayed_message` (
  `id` int(11) NOT NULL,
  `chat_id` bigint(11) NOT NULL,
  `message_id` bigint(11) NOT NULL,
  `content_id` bigint(11) NOT NULL,
  `type` enum('content','ad') NOT NULL DEFAULT 'content',
  `delete_time` varchar(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `favorite`
--

CREATE TABLE `favorite` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `content_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `sponser`
--

CREATE TABLE `sponser` (
  `id` int(11) NOT NULL,
  `chat_id` varchar(64) DEFAULT NULL,
  `invite_link` varchar(64) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `id` int(11) NOT NULL,
  `tid` bigint(11) NOT NULL,
  `phone_number` varchar(13) DEFAULT NULL,
  `username` varchar(128) DEFAULT NULL,
  `name` varchar(128) DEFAULT NULL,
  `score` float NOT NULL DEFAULT '0',
  `league_score` float NOT NULL DEFAULT '0',
  `show_sponser` tinyint(1) NOT NULL DEFAULT '1',
  `show_ad` tinyint(1) NOT NULL DEFAULT '1',
  `referrer_tid` bigint(11) DEFAULT NULL,
  `content_edit_type` enum('no','own','yes') NOT NULL DEFAULT 'no',
  `step` varchar(128) DEFAULT NULL,
  `charge_balance_amount` float DEFAULT NULL,
  `bot_msg_id` int(11) DEFAULT NULL,
  `joined_at` varchar(20) NOT NULL,
  `last_interaction` varchar(20) DEFAULT NULL,
  `chat_warning` int(11) NOT NULL DEFAULT '0',
  `blocked_by_user` tinyint(1) NOT NULL DEFAULT '0',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `chat_active` tinyint(4) NOT NULL DEFAULT '1',
  `chat_group_today_message_count` bigint(11) NOT NULL DEFAULT '0',
  `chat_group_total_message_count` bigint(11) NOT NULL DEFAULT '0',
  `chat_group_last_interaction` varchar(20) NOT NULL,
  `user_viewed_last_content_id` bigint(11) DEFAULT NULL,
  `live_statistics` tinyint(1) NOT NULL DEFAULT '0',
  `bot_text` text,
  `photo_id` varchar(128) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `user_content_reaction`
--

CREATE TABLE `user_content_reaction` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `content_id` int(11) NOT NULL,
  `first_reaction` tinyint(1) NOT NULL DEFAULT '0',
  `second_reaction` tinyint(1) NOT NULL DEFAULT '0',
  `date` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `config`
--
ALTER TABLE `config`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `content`
--
ALTER TABLE `content`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `delayed_message`
--
ALTER TABLE `delayed_message`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `favorite`
--
ALTER TABLE `favorite`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sponser`
--
ALTER TABLE `sponser`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user_content_reaction`
--
ALTER TABLE `user_content_reaction`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `content`
--
ALTER TABLE `content`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `delayed_message`
--
ALTER TABLE `delayed_message`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `favorite`
--
ALTER TABLE `favorite`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sponser`
--
ALTER TABLE `sponser`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_content_reaction`
--
ALTER TABLE `user_content_reaction`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
