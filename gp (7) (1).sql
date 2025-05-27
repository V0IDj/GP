-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 19, 2025 at 11:06 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `gp`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_last_visits`
--

CREATE TABLE `admin_last_visits` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `section` enum('rewards','researches','services') NOT NULL,
  `last_visit` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `askmessages`
--

CREATE TABLE `askmessages` (
  `message_id` int(11) NOT NULL,
  `user_id` varchar(255) NOT NULL,
  `user_name` varchar(255) NOT NULL,
  `message_text` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `classification`
--

CREATE TABLE `classification` (
  `classification_id` int(11) NOT NULL,
  `classification_type` enum('Clarivate','Scopus') NOT NULL,
  `classification_data` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `organization`
--

CREATE TABLE `organization` (
  `organization_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `location` enum('Local','Global') NOT NULL,
  `sector_type` enum('Private','Public') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `publication`
--

CREATE TABLE `publication` (
  `publication_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `publication_type` enum('Book Chapter','Journal','Conference') NOT NULL,
  `research_id` int(11) DEFAULT NULL,
  `publisher_id` int(11) DEFAULT NULL,
  `classification_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `publicationclassification`
--

CREATE TABLE `publicationclassification` (
  `publication_id` int(11) NOT NULL,
  `classification_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `publicationpublisher`
--

CREATE TABLE `publicationpublisher` (
  `publication_id` int(11) NOT NULL,
  `publisher_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `publisher`
--

CREATE TABLE `publisher` (
  `publisher_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `contact_info` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `research`
--

CREATE TABLE `research` (
  `research_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `research_type` enum('Practical','Theoretical') NOT NULL,
  `files` longblob DEFAULT NULL,
  `doi` varchar(255) DEFAULT NULL,
  `publish_date` date DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `researchparticipation`
--

CREATE TABLE `researchparticipation` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `participate1` int(11) DEFAULT NULL,
  `participate2` int(11) DEFAULT NULL,
  `participate3` int(11) DEFAULT NULL,
  `participate4` int(11) DEFAULT NULL,
  `participate5` int(11) DEFAULT NULL,
  `status` enum('rejected','wait','approved') DEFAULT 'wait',
  `category` varchar(100) DEFAULT NULL,
  `classification` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `submits_research_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `researchpublication`
--

CREATE TABLE `researchpublication` (
  `research_id` int(11) NOT NULL,
  `publication_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `researchreward`
--

CREATE TABLE `researchreward` (
  `reward_id` int(11) NOT NULL,
  `research_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `researchuser`
--

CREATE TABLE `researchuser` (
  `user_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `role` enum('Researcher','Admin','Student','Staff') NOT NULL,
  `organization_id` int(11) DEFAULT NULL,
  `password` varchar(1000) NOT NULL,
  `username` varchar(255) NOT NULL,
  `bio` text NOT NULL,
  `profile_image` longblob DEFAULT NULL,
  `college` varchar(250) NOT NULL,
  `major` varchar(250) NOT NULL,
  `specialty` varchar(255) DEFAULT NULL,
  `Number` int(11) NOT NULL,
  `PhoneNumber` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `research_history`
--

CREATE TABLE `research_history` (
  `id` int(11) NOT NULL,
  `research_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_name` varchar(255) NOT NULL,
  `action` text NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `research_reviewers`
--

CREATE TABLE `research_reviewers` (
  `id` int(11) NOT NULL,
  `research_id` int(11) NOT NULL,
  `reviewer_id` int(11) NOT NULL,
  `assigned_by` int(11) NOT NULL,
  `assigned_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','reviewed') DEFAULT 'pending',
  `comments` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Triggers `research_reviewers`
--
DELIMITER $$
CREATE TRIGGER `check_reviewer_is_staff` BEFORE INSERT ON `research_reviewers` FOR EACH ROW BEGIN
    DECLARE reviewer_role VARCHAR(50);
    
    -- Get the role of the reviewer
    SELECT role INTO reviewer_role FROM researchuser WHERE user_id = NEW.reviewer_id;
    
    -- Check if the reviewer has the Staff role
    IF reviewer_role != 'Staff' THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Reviewer must have the Staff role';
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `check_reviewer_is_staff_update` BEFORE UPDATE ON `research_reviewers` FOR EACH ROW BEGIN
    DECLARE reviewer_role VARCHAR(50);
    
    -- Skip the check if reviewer_id is not being changed
    IF NEW.reviewer_id <> OLD.reviewer_id THEN
        -- Get the role of the reviewer
        SELECT role INTO reviewer_role FROM researchuser WHERE user_id = NEW.reviewer_id;
        
        -- Check if the reviewer has the Staff role
        IF reviewer_role != 'Staff' THEN
            SIGNAL SQLSTATE '45000' 
            SET MESSAGE_TEXT = 'Reviewer must have the Staff role';
        END IF;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `reward`
--

CREATE TABLE `reward` (
  `reward_id` int(11) NOT NULL,
  `cost` decimal(10,2) NOT NULL,
  `approved_admin` enum('Yes','No') NOT NULL,
  `approved_vp_academic` enum('Yes','No') NOT NULL,
  `approved_president` enum('Yes','No') NOT NULL,
  `user_comments` text DEFAULT NULL,
  `research_id` int(11) DEFAULT NULL,
  `files` longblob DEFAULT NULL,
  `resercher_id` int(11) NOT NULL,
  `admins_lastComments` text NOT NULL,
  `status` enum('wait','approved','rejected','returned') DEFAULT 'wait',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `return_target` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Triggers `reward`
--
DELIMITER $$
CREATE TRIGGER `update_reward_status` BEFORE UPDATE ON `reward` FOR EACH ROW BEGIN
    IF NEW.approved_admin = 'yes' AND 
       NEW.approved_president = 'yes' AND 
       NEW.approved_vp_academic = 'yes' THEN
        SET NEW.status = 'approved';
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `reward_history`
--

CREATE TABLE `reward_history` (
  `id` int(11) NOT NULL,
  `reward_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_name` varchar(255) NOT NULL,
  `action` text NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `service`
--

CREATE TABLE `service` (
  `service_id` int(11) NOT NULL,
  `type` enum('Analysis','Translation','Proofreading','Accounting') DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `serviceprovider`
--

CREATE TABLE `serviceprovider` (
  `provider_id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `contact_info` varchar(255) DEFAULT NULL,
  `expertise` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `servicerequest`
--

CREATE TABLE `servicerequest` (
  `request_id` int(11) NOT NULL,
  `service_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `service_provider_id` int(11) DEFAULT NULL,
  `cost` decimal(10,2) DEFAULT NULL,
  `request_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `servicetemp`
--

CREATE TABLE `servicetemp` (
  `request_id` int(11) NOT NULL,
  `user_id` int(255) NOT NULL,
  `user_name` varchar(255) NOT NULL,
  `service_type` varchar(255) NOT NULL,
  `files` longblob DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('wait 1','approved','wait 2','rejected') DEFAULT 'wait 1',
  `assigned_staff_id` int(11) DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `staff_notes` text DEFAULT NULL,
  `completed_files` longblob DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Triggers `servicetemp`
--
DELIMITER $$
CREATE TRIGGER `after_servicetemp_approved` AFTER UPDATE ON `servicetemp` FOR EACH ROW BEGIN
    IF NEW.status = 'approved' AND OLD.status <> 'approved' THEN
        INSERT INTO servicerequest (request_id, user_id, request_date)
        VALUES (NEW.request_id, NEW.user_id, NEW.created_at);
        -- service_id is omitted here due to mismatch (varchar in servicetemp vs int in servicerequest)
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `service_history`
--

CREATE TABLE `service_history` (
  `id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_name` varchar(255) NOT NULL,
  `action` text NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `submitsresearch`
--

CREATE TABLE `submitsresearch` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `classification` enum('Q1','Q2','Q3','Q4') NOT NULL,
  `where_to_publish` enum('Journal','Book Chapter','Conference') NOT NULL,
  `college` varchar(100) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `user_notes` text DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `submission_date` datetime DEFAULT current_timestamp(),
  `status` enum('Pending 1','Pending 2','Approved','Rejected') DEFAULT 'Pending 1',
  `files` longblob NOT NULL,
  `r_type` enum('practical','theoretical') DEFAULT NULL,
  `is_shared` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Triggers `submitsresearch`
--
DELIMITER $$
CREATE TRIGGER `after_research_approval` AFTER UPDATE ON `submitsresearch` FOR EACH ROW BEGIN
    IF NEW.status = 'Approved' THEN
        INSERT INTO research (title, research_type, files, doi, publish_date, user_id)
        VALUES (
            NEW.title,
            'Theoretical',          -- default value for research_type
            NEW.files,
            NULL,                   -- no DOI in submitsresearch
            CURDATE(),              -- publish_date as current date
            NEW.user_id
        );
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `userorganization`
--

CREATE TABLE `userorganization` (
  `user_id` int(11) NOT NULL,
  `organization_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_last_visits`
--
ALTER TABLE `admin_last_visits`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `admin_section_unique` (`admin_id`,`section`);

--
-- Indexes for table `askmessages`
--
ALTER TABLE `askmessages`
  ADD PRIMARY KEY (`message_id`);

--
-- Indexes for table `classification`
--
ALTER TABLE `classification`
  ADD PRIMARY KEY (`classification_id`);

--
-- Indexes for table `organization`
--
ALTER TABLE `organization`
  ADD PRIMARY KEY (`organization_id`);

--
-- Indexes for table `publication`
--
ALTER TABLE `publication`
  ADD PRIMARY KEY (`publication_id`),
  ADD KEY `research_id` (`research_id`),
  ADD KEY `publisher_id` (`publisher_id`),
  ADD KEY `classification_id` (`classification_id`);

--
-- Indexes for table `publicationclassification`
--
ALTER TABLE `publicationclassification`
  ADD PRIMARY KEY (`publication_id`,`classification_id`),
  ADD KEY `classification_id` (`classification_id`);

--
-- Indexes for table `publicationpublisher`
--
ALTER TABLE `publicationpublisher`
  ADD PRIMARY KEY (`publication_id`,`publisher_id`),
  ADD KEY `publisher_id` (`publisher_id`);

--
-- Indexes for table `publisher`
--
ALTER TABLE `publisher`
  ADD PRIMARY KEY (`publisher_id`);

--
-- Indexes for table `research`
--
ALTER TABLE `research`
  ADD PRIMARY KEY (`research_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `researchparticipation`
--
ALTER TABLE `researchparticipation`
  ADD PRIMARY KEY (`id`),
  ADD KEY `participate1` (`participate1`),
  ADD KEY `participate2` (`participate2`),
  ADD KEY `participate3` (`participate3`),
  ADD KEY `participate4` (`participate4`),
  ADD KEY `participate5` (`participate5`),
  ADD KEY `fk_submits_research` (`submits_research_id`);

--
-- Indexes for table `researchpublication`
--
ALTER TABLE `researchpublication`
  ADD PRIMARY KEY (`research_id`,`publication_id`),
  ADD KEY `publication_id` (`publication_id`);

--
-- Indexes for table `researchreward`
--
ALTER TABLE `researchreward`
  ADD PRIMARY KEY (`reward_id`,`research_id`),
  ADD KEY `research_id` (`research_id`);

--
-- Indexes for table `researchuser`
--
ALTER TABLE `researchuser`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `asd` (`username`),
  ADD KEY `organization_id` (`organization_id`);

--
-- Indexes for table `research_history`
--
ALTER TABLE `research_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `research_id` (`research_id`);

--
-- Indexes for table `research_reviewers`
--
ALTER TABLE `research_reviewers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `research_id` (`research_id`),
  ADD KEY `reviewer_id` (`reviewer_id`),
  ADD KEY `fk_research_reviewers_admin` (`assigned_by`);

--
-- Indexes for table `reward`
--
ALTER TABLE `reward`
  ADD PRIMARY KEY (`reward_id`),
  ADD KEY `research_id` (`research_id`),
  ADD KEY `reward_ibfk_2` (`resercher_id`);

--
-- Indexes for table `reward_history`
--
ALTER TABLE `reward_history`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `service`
--
ALTER TABLE `service`
  ADD PRIMARY KEY (`service_id`);

--
-- Indexes for table `serviceprovider`
--
ALTER TABLE `serviceprovider`
  ADD PRIMARY KEY (`provider_id`);

--
-- Indexes for table `servicerequest`
--
ALTER TABLE `servicerequest`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `service_provider_id` (`service_provider_id`),
  ADD KEY `servicerequest_ibfk_1` (`service_id`);

--
-- Indexes for table `servicetemp`
--
ALTER TABLE `servicetemp`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `userfktemp` (`user_id`),
  ADD KEY `fk_staff_user` (`assigned_staff_id`);

--
-- Indexes for table `service_history`
--
ALTER TABLE `service_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `service_id` (`service_id`);

--
-- Indexes for table `submitsresearch`
--
ALTER TABLE `submitsresearch`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `userorganization`
--
ALTER TABLE `userorganization`
  ADD PRIMARY KEY (`user_id`,`organization_id`),
  ADD KEY `organization_id` (`organization_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_last_visits`
--
ALTER TABLE `admin_last_visits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `askmessages`
--
ALTER TABLE `askmessages`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `classification`
--
ALTER TABLE `classification`
  MODIFY `classification_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `organization`
--
ALTER TABLE `organization`
  MODIFY `organization_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `publication`
--
ALTER TABLE `publication`
  MODIFY `publication_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `publisher`
--
ALTER TABLE `publisher`
  MODIFY `publisher_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `research`
--
ALTER TABLE `research`
  MODIFY `research_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `researchparticipation`
--
ALTER TABLE `researchparticipation`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `researchuser`
--
ALTER TABLE `researchuser`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `research_history`
--
ALTER TABLE `research_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `research_reviewers`
--
ALTER TABLE `research_reviewers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reward`
--
ALTER TABLE `reward`
  MODIFY `reward_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reward_history`
--
ALTER TABLE `reward_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `service`
--
ALTER TABLE `service`
  MODIFY `service_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `serviceprovider`
--
ALTER TABLE `serviceprovider`
  MODIFY `provider_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `servicerequest`
--
ALTER TABLE `servicerequest`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `servicetemp`
--
ALTER TABLE `servicetemp`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `service_history`
--
ALTER TABLE `service_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `submitsresearch`
--
ALTER TABLE `submitsresearch`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_last_visits`
--
ALTER TABLE `admin_last_visits`
  ADD CONSTRAINT `admin_visits_fk_1` FOREIGN KEY (`admin_id`) REFERENCES `researchuser` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `publication`
--
ALTER TABLE `publication`
  ADD CONSTRAINT `publication_ibfk_1` FOREIGN KEY (`research_id`) REFERENCES `research` (`research_id`),
  ADD CONSTRAINT `publication_ibfk_2` FOREIGN KEY (`publisher_id`) REFERENCES `publisher` (`publisher_id`),
  ADD CONSTRAINT `publication_ibfk_3` FOREIGN KEY (`classification_id`) REFERENCES `classification` (`classification_id`);

--
-- Constraints for table `publicationclassification`
--
ALTER TABLE `publicationclassification`
  ADD CONSTRAINT `publicationclassification_ibfk_1` FOREIGN KEY (`publication_id`) REFERENCES `publication` (`publication_id`),
  ADD CONSTRAINT `publicationclassification_ibfk_2` FOREIGN KEY (`classification_id`) REFERENCES `classification` (`classification_id`);

--
-- Constraints for table `publicationpublisher`
--
ALTER TABLE `publicationpublisher`
  ADD CONSTRAINT `publicationpublisher_ibfk_1` FOREIGN KEY (`publication_id`) REFERENCES `publication` (`publication_id`),
  ADD CONSTRAINT `publicationpublisher_ibfk_2` FOREIGN KEY (`publisher_id`) REFERENCES `publisher` (`publisher_id`);

--
-- Constraints for table `research`
--
ALTER TABLE `research`
  ADD CONSTRAINT `research_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `researchuser` (`user_id`);

--
-- Constraints for table `researchparticipation`
--
ALTER TABLE `researchparticipation`
  ADD CONSTRAINT `fk_submits_research` FOREIGN KEY (`submits_research_id`) REFERENCES `submitsresearch` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `researchparticipation_ibfk_1` FOREIGN KEY (`participate1`) REFERENCES `researchuser` (`user_id`),
  ADD CONSTRAINT `researchparticipation_ibfk_2` FOREIGN KEY (`participate2`) REFERENCES `researchuser` (`user_id`),
  ADD CONSTRAINT `researchparticipation_ibfk_3` FOREIGN KEY (`participate3`) REFERENCES `researchuser` (`user_id`),
  ADD CONSTRAINT `researchparticipation_ibfk_4` FOREIGN KEY (`participate4`) REFERENCES `researchuser` (`user_id`),
  ADD CONSTRAINT `researchparticipation_ibfk_5` FOREIGN KEY (`participate5`) REFERENCES `researchuser` (`user_id`);

--
-- Constraints for table `researchpublication`
--
ALTER TABLE `researchpublication`
  ADD CONSTRAINT `researchpublication_ibfk_1` FOREIGN KEY (`research_id`) REFERENCES `research` (`research_id`),
  ADD CONSTRAINT `researchpublication_ibfk_2` FOREIGN KEY (`publication_id`) REFERENCES `publication` (`publication_id`);

--
-- Constraints for table `researchreward`
--
ALTER TABLE `researchreward`
  ADD CONSTRAINT `researchreward_ibfk_1` FOREIGN KEY (`reward_id`) REFERENCES `reward` (`reward_id`),
  ADD CONSTRAINT `researchreward_ibfk_2` FOREIGN KEY (`research_id`) REFERENCES `research` (`research_id`);

--
-- Constraints for table `researchuser`
--
ALTER TABLE `researchuser`
  ADD CONSTRAINT `researchuser_ibfk_1` FOREIGN KEY (`organization_id`) REFERENCES `organization` (`organization_id`);

--
-- Constraints for table `research_reviewers`
--
ALTER TABLE `research_reviewers`
  ADD CONSTRAINT `fk_research_reviewers_admin` FOREIGN KEY (`assigned_by`) REFERENCES `researchuser` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_research_reviewers_research` FOREIGN KEY (`research_id`) REFERENCES `submitsresearch` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_research_reviewers_reviewer` FOREIGN KEY (`reviewer_id`) REFERENCES `researchuser` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `reward`
--
ALTER TABLE `reward`
  ADD CONSTRAINT `reward_ibfk_1` FOREIGN KEY (`research_id`) REFERENCES `research` (`research_id`),
  ADD CONSTRAINT `reward_ibfk_2` FOREIGN KEY (`resercher_id`) REFERENCES `researchuser` (`user_id`);

--
-- Constraints for table `servicerequest`
--
ALTER TABLE `servicerequest`
  ADD CONSTRAINT `servicerequest_ibfk_1` FOREIGN KEY (`service_id`) REFERENCES `servicetemp` (`request_id`),
  ADD CONSTRAINT `servicerequest_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `researchuser` (`user_id`),
  ADD CONSTRAINT `servicerequest_ibfk_3` FOREIGN KEY (`service_provider_id`) REFERENCES `serviceprovider` (`provider_id`);

--
-- Constraints for table `servicetemp`
--
ALTER TABLE `servicetemp`
  ADD CONSTRAINT `fk_staff_user` FOREIGN KEY (`assigned_staff_id`) REFERENCES `researchuser` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `userfktemp` FOREIGN KEY (`user_id`) REFERENCES `researchuser` (`user_id`);

--
-- Constraints for table `submitsresearch`
--
ALTER TABLE `submitsresearch`
  ADD CONSTRAINT `submitsresearch_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `researchuser` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `userorganization`
--
ALTER TABLE `userorganization`
  ADD CONSTRAINT `userorganization_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `researchuser` (`user_id`),
  ADD CONSTRAINT `userorganization_ibfk_2` FOREIGN KEY (`organization_id`) REFERENCES `organization` (`organization_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
