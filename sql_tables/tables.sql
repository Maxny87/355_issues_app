CREATE TABLE `iss_persons` (
    `id` int(11) NOT NULL,
    `fname` varchar(255) NOT NULL,
    `lname` varchar(255) NOT NULL,
    `mobile` varchar(255) NOT NULL,
    `email` varchar(255) NOT NULL,
    `pwd_hash` varchar(255) NOT NULL,
    `admin` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `iss_persons`
    ADD PRIMARY KEY (`id`);

ALTER TABLE `iss_persons`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
COMMIT;

-- Sample admin user
-- Email: test@svsu.edu
-- Password: test
-- This user is an admin and can access all functionality
INSERT INTO `iss_persons` (`fname`, `lname`, `mobile`, `email`, `pwd_hash`, `admin`) VALUES
    ('test', 'test', '111-111-1111', 'test@svsu.edu', '$2y$10$k40/Jqu48pcMvJn37/CxxOes1jQCFV7lHL.9DI.Iu1AwyLfxWA5bq', 'Y');

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
    `resolved` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `iss_issues`
    ADD PRIMARY KEY (`id`);

ALTER TABLE `iss_issues`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
COMMIT;

ALTER TABLE iss_issues ADD COLUMN pdf_attachment VARCHAR(255);

CREATE TABLE `iss_comments` (
    `id` int(11) NOT NULL,
    `per_id` int(11) NOT NULL,
    `iss_id` int(11) NOT NULL,
    `short_comment` varchar(255) NOT NULL,
    `long_comment` text NOT NULL,
    `posted_date` date NOT NULL,
    `resolved` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `iss_comments`
    ADD PRIMARY KEY (`id`);

ALTER TABLE `iss_comments`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
COMMIT;