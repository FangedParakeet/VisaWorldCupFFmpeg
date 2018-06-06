-- phpMyAdmin SQL Dump
-- version 4.7.7
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jun 06, 2018 at 12:02 PM
-- Server version: 5.6.38
-- PHP Version: 7.2.1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

--
-- Database: `visa_world_cup`
--
CREATE DATABASE IF NOT EXISTS `visa_world_cup` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin;
USE `visa_world_cup`;

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` int(11) NOT NULL,
  `jobId` varchar(32) COLLATE utf8mb4_bin NOT NULL,
  `status` text COLLATE utf8mb4_bin NOT NULL,
  `statusCode` int(1) NOT NULL DEFAULT '0',
  `name` varchar(255) COLLATE utf8mb4_bin DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_bin DEFAULT NULL,
  `webcamVideo` text COLLATE utf8mb4_bin,
  `arVideo` text COLLATE utf8mb4_bin,
  `combinedVideo` text COLLATE utf8mb4_bin,
  `finalVideo` text COLLATE utf8mb4_bin,
  `finalLink` text COLLATE utf8mb4_bin,
  `toBeDeleted` int(1) NOT NULL DEFAULT '0',
  `dateAdded` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `dateModified` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `jobId` (`jobId`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
