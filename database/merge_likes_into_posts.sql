-- MediFlow - Magazine likes merge
-- Merges `mag_post_likes` into `mag_posts` by adding:
--   - mag_posts.likes_count (INT)
--   - mag_posts.liked_user_ids (JSON-ish LONGTEXT array of user ids)
-- Then migrates existing data and drops `mag_post_likes`.
--
-- NOTE: This keeps the application ability to know if a given user liked a post,
-- while meeting the "one table" requirement (likes stored inside mag_posts).

START TRANSACTION;

ALTER TABLE `mag_posts`
  ADD COLUMN `likes_count` INT(11) NOT NULL DEFAULT 0,
  ADD COLUMN `liked_user_ids` LONGTEXT NULL;

UPDATE `mag_posts` p
LEFT JOIN (
  SELECT
    `post_id`,
    COUNT(*) AS `cnt`,
    CONCAT('[', GROUP_CONCAT(`user_id` ORDER BY `user_id` SEPARATOR ','), ']') AS `users_json`
  FROM `mag_post_likes`
  GROUP BY `post_id`
) l ON l.`post_id` = p.`id_post`
SET
  p.`likes_count` = COALESCE(l.`cnt`, 0),
  p.`liked_user_ids` = COALESCE(l.`users_json`, '[]');

UPDATE `mag_posts`
SET `liked_user_ids` = '[]'
WHERE `liked_user_ids` IS NULL OR `liked_user_ids` = '';

DROP TABLE `mag_post_likes`;

COMMIT;
