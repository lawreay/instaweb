-- Marks an admin-owned website as a protected system portfolio sample.
-- It is safe to run on an existing database more than once.

DELIMITER $$
DROP PROCEDURE IF EXISTS add_system_website_template_flag$$
CREATE PROCEDURE add_system_website_template_flag()
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'websites'
          AND COLUMN_NAME = 'is_system_template'
    ) THEN
        ALTER TABLE websites
            ADD COLUMN is_system_template TINYINT(1) NOT NULL DEFAULT 0 AFTER template_key;
    END IF;
END$$
CALL add_system_website_template_flag()$$
DROP PROCEDURE IF EXISTS add_system_website_template_flag$$
DELIMITER ;
