-- MediFlow — Magazine module schema
-- Compatible with MariaDB 10.4+ / MySQL 8+
-- Assumes existing tables: `utilisateurs` (PK: id_PK), `roles` (owned by User module)
-- Magazine module tables: mag_categories, mag_posts, mag_comments

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- --------------------------------------------------------
-- Table 1: Categories
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `mag_categories` (
  `id_category` int(11) NOT NULL AUTO_INCREMENT,
  `name`        varchar(100) NOT NULL,
  `slug`        varchar(120) NOT NULL,
  `created_at`  timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_category`),
  UNIQUE KEY `uq_mag_categories_name` (`name`),
  UNIQUE KEY `uq_mag_categories_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table 2: Posts
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `mag_posts` (
  `id_post`       int(11) NOT NULL AUTO_INCREMENT,
  `title`         varchar(255) NOT NULL,
  `excerpt`       text DEFAULT NULL,
  `content`       longtext NOT NULL,
  `image_url`     varchar(512) DEFAULT NULL,
  `category_id`   int(11) NOT NULL,
  `author_id`     int(11) DEFAULT NULL,
  `status`        enum('draft','published','archived') NOT NULL DEFAULT 'published',
  `likes_count`   int(11) NOT NULL DEFAULT 0,
  `liked_user_ids` longtext DEFAULT NULL,        -- JSON array of user IDs who liked this post
  `views_count`   int(11) NOT NULL DEFAULT 0,    -- incremented on each article view
  `published_at`  datetime DEFAULT NULL,
  `created_at`    timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at`    timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_post`),
  KEY `idx_mag_posts_category_id`         (`category_id`),
  KEY `idx_mag_posts_author_id`           (`author_id`),
  KEY `idx_mag_posts_status_published_at` (`status`,`published_at`),
  KEY `idx_mag_posts_views`               (`views_count`),
  CONSTRAINT `fk_mag_posts_category` FOREIGN KEY (`category_id`) REFERENCES `mag_categories` (`id_category`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_mag_posts_author`   FOREIGN KEY (`author_id`)   REFERENCES `utilisateurs`   (`id_PK`)       ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add views_count if upgrading from an older version without it
ALTER TABLE `mag_posts` ADD COLUMN IF NOT EXISTS `views_count` int(11) NOT NULL DEFAULT 0;

-- --------------------------------------------------------
-- Table 3: Comments
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `mag_comments` (
  `id_comment` int(11) NOT NULL AUTO_INCREMENT,
  `post_id`    int(11) NOT NULL,
  `user_id`    int(11) DEFAULT NULL,
  `content`    text NOT NULL,
  `status`     enum('active','flagged','deleted') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_comment`),
  KEY `idx_mag_comments_post_id`               (`post_id`),
  KEY `idx_mag_comments_user_id`               (`user_id`),
  KEY `idx_mag_comments_status_created_at`     (`status`,`created_at`),
  CONSTRAINT `fk_mag_comments_post` FOREIGN KEY (`post_id`) REFERENCES `mag_posts`  (`id_post`) ON DELETE CASCADE  ON UPDATE CASCADE,
  CONSTRAINT `fk_mag_comments_user` FOREIGN KEY (`user_id`) REFERENCES `utilisateurs`(`id_PK`)  ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Seed: Categories
-- --------------------------------------------------------
INSERT IGNORE INTO `mag_categories` (`id_category`, `name`, `slug`) VALUES
  (1, 'Health News', 'health-news'),
  (2, 'Research',    'research'),
  (3, 'Journals',    'journals'),
  (4, 'Nutrition',   'nutrition'),
  (5, 'Science',     'science');

-- --------------------------------------------------------
-- Seed: Sample Posts  (author_id = 1 = Admin from User module)
-- --------------------------------------------------------
INSERT INTO `mag_posts`
  (`id_post`, `title`, `excerpt`, `content`, `image_url`, `category_id`, `author_id`, `status`, `views_count`, `published_at`)
VALUES
  (1,
   'The Future of Personalized Telemedicine: Beyond Video Calls',
   'Exploring how integrated biosensors and real-time data streaming are transforming the remote consultation experience.',
   'Exploring how integrated biosensors and real-time data streaming are transforming the remote consultation experience from simple conversations into clinical-grade assessments. Modern wearables now capture heart-rate variability, SpO2, skin temperature, and ECG data in real time, feeding this stream directly into the consultation platform so a physician can make evidence-based decisions without the patient leaving home. The next decade will see these tools become as routine as a stethoscope.',
   'https://lh3.googleusercontent.com/aida-public/AB6AXuAv9eS0xUUSXM5NLqZzjnyfe0lt47s72alqY9qrU2mL0jh79ufYVCR3mC4XLpC69GM0zx7d7yVt78K9PT1jee12_kSlPcY6CbMx8WmpevukwZ6byxANb8-xez3oIurTRI19Ul2npzwdPa75DqD1s06X4D_zU5rPVG1OzbNw7MieaLhJGqAvD1R8ZyKO7UXlV88kQX1zeBTsS1vJdfOj5B8swseYU0nLpZkgWEM_8yMAkFLjCP1YAHgoRXqx1yjTdlod2l777ukd4dE',
   1, 1, 'published', 128, DATE_SUB(NOW(), INTERVAL 1 DAY)),

  (2,
   'Advancements in Neural Plasticity',
   'A quick roundup of emerging research on how the brain adapts after injury and learning.',
   'A quick roundup of emerging research on how the brain adapts after injury and learning. Neuroplasticity — the brain\'s ability to reorganise itself by forming new neural connections — is at the forefront of modern neuroscience. Recent studies show that structured cognitive rehabilitation, combined with transcranial magnetic stimulation, can restore motor function in stroke patients by up to 60% compared with conventional physiotherapy alone. These findings are reshaping clinical protocols worldwide.',
   'https://lh3.googleusercontent.com/aida-public/AB6AXuD8mqmyBVnquEc1jiPd25M8mm0eTLn3Fcr9ungNMisV4ydtpdFZv9XDCrcG1RIq85sGfiIMoWmU4Wt6OB2nHowY3Ze18q1hAq4QvJochy-JQd4Qa0ZIQrPuWBA6NE-ZtHT2WfBLo4nT2TYfUp_KUs3Sm9t15RUlZnvWwOD3_5_aTk_yKwrzs5dZ7iTfxNMpXrv4pZx6iy27txQnT1AaLgXb88bTvstaQK4IBTkmlnXpbibjxQAgE14v7IA4HirOcixJhjd1fvJDEik',
   2, 1, 'published', 94, DATE_SUB(NOW(), INTERVAL 2 HOUR)),

  (3,
   'The Future of Sustainable Healthcare Infrastructure',
   'Modern hospital design is evolving toward greener, patient-first spaces.',
   'Modern hospital design is evolving toward greener, patient-first spaces. Architects and healthcare planners are now embedding living walls, rooftop gardens, and passive-cooling systems into hospital blueprints. Studies indicate that access to natural light reduces patient recovery times by 20% and lowers the need for pain medication. Zero-carbon hospitals are no longer aspirational — several flagship projects in northern Europe are already fully operational.',
   'https://lh3.googleusercontent.com/aida-public/AB6AXuAa-C_4qS9_amZ7jsRQBah-ZecNT3P1XQ1Db9BHW3GyBrFuv_utNRbc8o2i5pCPRUVQt2Obj9IUea0jQqgL-QiecrgGMcjursz09IuCiJiVukL_1vJJu2xkMf88hYoCQR8DzPHx1ZREeWrxtRsqsLgudkx81blEpJGmSf9jJksRhVbXz62s4VtQboPW5FQzPExP1Y8NNPtMjNa1iQeyXBSW9qTjTJceU33934QUhauvdYkjQ01j1syvNpljoxS7iB0GM_G7ozlxh6Y',
   1, 1, 'published', 76, DATE_SUB(NOW(), INTERVAL 1 DAY)),

  (4,
   'Cardiovascular Robotics: A New Frontier',
   'How robotics is supporting surgical precision and better outcomes in cardiology.',
   'How robotics is supporting surgical precision and better outcomes in cardiology. Robotic-assisted cardiac surgery has reduced post-operative complications by 35% in recent clinical trials. With sub-millimetre precision, robotic arms eliminate the tremor inherent in human hands, making procedures such as mitral valve repair accessible to a far wider patient population. Surgeons can now perform complex operations through incisions smaller than a centimetre, dramatically cutting recovery time.',
   'https://lh3.googleusercontent.com/aida-public/AB6AXuD57cqXHwUhJMPhDsKsskiLu1McvwFyfCiTSSOyN0VvZhd8g1V49Zx6P82c9ebCNLYDNR-lX69fTyeQlOOV8u1hshp60EK2JTJhMzsY6wMoW9aP5sccIAo_ldwDPWyR9cEvuptGM4YNJ_LDZIlklkD97eCGSsPsGp61N_7VAdIKs2wKFUnt05HkjSH_gZ9fennCAvDtT6pd0aCCTZ4cutNkCWMD8wzNm4ftOMMOTKEkiB5g10m7Jw94-LYRTxEMCtlojHpO9m6KxyE',
   3, 1, 'published', 52, DATE_SUB(NOW(), INTERVAL 3 DAY)),

  (5,
   '5 Superfoods for Brain Health',
   'Research indicates that a diet rich in specific nutrients can support cognitive longevity.',
   'Research indicates that a diet rich in specific nutrients can support cognitive longevity. Blueberries, fatty fish, broccoli, pumpkin seeds, and dark chocolate have each been shown in peer-reviewed studies to improve memory, enhance concentration, and slow age-related cognitive decline. A Mediterranean-style diet incorporating these foods regularly is associated with a 33% lower risk of Alzheimer\'s disease according to a 10-year longitudinal study published in JAMA Neurology.',
   NULL, 4, 1, 'published', 41, DATE_SUB(NOW(), INTERVAL 7 DAY)),

  (6,
   'Understanding Sleep Cycles',
   'How REM and deep sleep stages impact your body\'s daily recovery mechanisms.',
   'How REM and deep sleep stages impact your body\'s daily recovery mechanisms. During deep (slow-wave) sleep, the glymphatic system clears metabolic waste — including amyloid-beta proteins linked to Alzheimer\'s — from the brain at nearly 10 times the rate seen during wakefulness. REM sleep, meanwhile, consolidates emotional memories and supports creative problem-solving. Disrupting either phase through shift work or blue-light exposure before bed measurably impairs next-day cognitive performance.',
   NULL, 5, 1, 'published', 33, DATE_SUB(NOW(), INTERVAL 10 DAY))

ON DUPLICATE KEY UPDATE
  `title`       = VALUES(`title`),
  `excerpt`     = VALUES(`excerpt`),
  `content`     = VALUES(`content`),
  `image_url`   = VALUES(`image_url`),
  `category_id` = VALUES(`category_id`),
  `author_id`   = VALUES(`author_id`),
  `status`      = VALUES(`status`),
  `published_at`= VALUES(`published_at`);

-- --------------------------------------------------------
-- Seed: Sample Comments
-- --------------------------------------------------------
INSERT INTO `mag_comments` (`id_comment`, `post_id`, `user_id`, `content`, `status`, `created_at`)
VALUES
  (1, 1, 2, 'This data seems contradictory to the 2022 study. Can we get clarification on the sample size used?', 'active',  DATE_SUB(NOW(), INTERVAL 4 MINUTE)),
  (2, 1, 3, 'Great article! Very helpful for my thesis on neuro-rehabilitation.',                                 'active',  DATE_SUB(NOW(), INTERVAL 12 MINUTE)),
  (3, 1, 2, 'Check out this link for cheap medical supplies...',                                                 'flagged', DATE_SUB(NOW(), INTERVAL 1 HOUR))
ON DUPLICATE KEY UPDATE
  `post_id`  = VALUES(`post_id`),
  `user_id`  = VALUES(`user_id`),
  `content`  = VALUES(`content`),
  `status`   = VALUES(`status`);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
COMMIT;
