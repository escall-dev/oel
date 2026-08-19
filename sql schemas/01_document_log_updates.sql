-- Migration Script: Document Log System Updates
-- Note: Ensure you have selected the correct database in phpMyAdmin before running this.

-- 1. Add new remarks_action column and make document_type_id nullable
ALTER TABLE `documents` ADD COLUMN `remarks_action` ENUM('Acted upon', 'Routed to the responsible office', 'Completed', 'Released', 'Filed', 'Filed by the Director') NULL DEFAULT NULL AFTER `remarks`;
ALTER TABLE `documents` MODIFY COLUMN `document_type_id` INT NULL DEFAULT NULL;

-- 2. Update existing documents to point to category 1 (Incoming) to prevent foreign key errors when we delete old categories.
UPDATE `documents` SET `category_id` = 1 WHERE `category_id` > 2;

-- 3. Replace Categories 1 and 2 with "Incoming" and "Outgoing"
UPDATE `categories` SET `category_name` = 'Incoming' WHERE `id` = 1;
UPDATE `categories` SET `category_name` = 'Outgoing' WHERE `id` = 2;

-- 4. Map remaining document types to Incoming (1)
UPDATE `document_types` SET `category_id` = 1 WHERE `category_id` > 2;

-- 5. Remove the old categories (3-7)
DELETE FROM `categories` WHERE `id` > 2;
