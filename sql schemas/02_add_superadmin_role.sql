-- Migration script to add 'superadmin' role to users table

-- 1. Alter the ENUM column to include 'superadmin'
ALTER TABLE `users` MODIFY COLUMN `role` ENUM('superadmin', 'admin', 'encoder', 'viewer') NOT NULL DEFAULT 'viewer';

-- 2. (Optional) Promote the default system admin (061920) to superadmin
UPDATE `users` SET `role` = 'superadmin' WHERE `username` = '061920';
