-- InstaWeb migration: persist the selected website template/runtime.
-- Apply after 20261022_001_create_portfolios.sql on an existing database.
-- Fresh installs already have template_key in schema.sql and schema_infinityfree.sql.
-- Back up the database before applying this migration.

DELIMITER $$
DROP PROCEDURE IF EXISTS add_website_template_key$$
CREATE PROCEDURE add_website_template_key()
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'websites'
          AND COLUMN_NAME = 'template_key'
    ) THEN
        ALTER TABLE websites
            ADD COLUMN template_key VARCHAR(40) NOT NULL DEFAULT 'blank' AFTER slug;
    END IF;
END$$
CALL add_website_template_key()$$
DROP PROCEDURE IF EXISTS add_website_template_key$$
DELIMITER ;

-- Backfill managed types using reliable existing records.
UPDATE websites w
INNER JOIN files f ON f.website_id = w.id AND f.filepath = 'wp-config.php'
SET w.template_key = 'wordpress';

UPDATE websites w
INNER JOIN portfolios p ON p.website_id = w.id
SET w.template_key = 'portfolio';
