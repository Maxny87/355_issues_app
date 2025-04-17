CREATE TABLE `iss_comments` (
    `id` int(11) NOT NULL,
    `per_id` int(11) NOT NULL,
    `iss_id` int(11) NOT NULL,
    `short_comment` varchar(255) NOT NULL,
    `long_comment` text NOT NULL,
    `posted_date` date NOT NULL,
    `resolved` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `iss_issues` (
    `id` int(11) NOT NULL,
    `short_description` varchar(255) NOT NULL,
    `long_description` text NOT NULL,
    `open_date` date NOT NULL,
    `close_date` date NOT NULL,
    `priority` varchar(255) NOT NULL,
    `org` varchar(255) NOT NULL,
    `project` varchar(255) NOT NULL,
    `per_id` int(11) NOT NULL,
    `resolved` tinyint(1) DEFAULT 0,
    `pdf_attachment` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `iss_persons` (
    `id` int(11) NOT NULL,
    `fname` varchar(255) NOT NULL,
    `lname` varchar(255) NOT NULL,
    `mobile` varchar(255) NOT NULL,
    `email` varchar(255) NOT NULL,
    `pwd_hash` varchar(255) NOT NULL,
    `admin` varchar(255) NOT NULL,
    `active` int(11) DEFAULT 0,
    `activation_code` varchar(255) DEFAULT NULL,
    `activation_expiry` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `iss_persons`
INSERT INTO `iss_persons` (`id`, `fname`, `lname`, `mobile`, `email`, `pwd_hash`, `admin`, `active`, `activation_code`, `activation_expiry`) VALUES
    (1, 'test', 'test', '111-111-1111', 'test@svsu.edu', '$2y$10$k40/Jqu48pcMvJn37/CxxOes1jQCFV7lHL.9DI.Iu1AwyLfxWA5bq', 'Y', 0, NULL, NULL),
    (2, 'non', 'admin', '111-111-1111', 'non_admin@svsu.edu', '$2y$10$lPBlP0HuWzy/VLHm83/S6ebOPSK6c43Z634q2/PL6YZq/RcdA/Cze', 'N', 0, NULL, NULL);

ALTER TABLE `iss_comments`
    ADD PRIMARY KEY (`id`);

ALTER TABLE `iss_issues`
    ADD PRIMARY KEY (`id`);

ALTER TABLE `iss_persons`
    ADD PRIMARY KEY (`id`);

ALTER TABLE `iss_comments`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `iss_issues`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `iss_persons`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

COMMIT;
