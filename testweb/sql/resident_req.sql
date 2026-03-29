ALTER TABLE `resident_requests` 
ADD COLUMN `qr_token` VARCHAR(100) UNIQUE AFTER `admin_remarks`;