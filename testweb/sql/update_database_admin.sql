-- Step 1: Disable foreign key checks
SET FOREIGN_KEY_CHECKS = 0;

-- Step 2: Drop old tables we don't need anymore
DROP TABLE IF EXISTS role_permissions_backup;
DROP TABLE IF EXISTS roles_backup;
DROP TABLE IF EXISTS permissions_backup;
DROP TABLE IF EXISTS roles;
DROP TABLE IF EXISTS permissions;

-- Step 3: Remove the foreign key constraints from admins table
ALTER TABLE admins DROP FOREIGN KEY admins_ibfk_1;
ALTER TABLE admins DROP FOREIGN KEY admins_ibfk_2;

-- Step 4: Remove role_id column from admins
ALTER TABLE admins DROP COLUMN IF EXISTS role_id;

-- Step 5: Add admin_type column
ALTER TABLE admins ADD COLUMN admin_type ENUM('main_admin', 'staff_admin') NOT NULL DEFAULT 'staff_admin' AFTER password;

-- Step 6: Update existing admins
UPDATE admins SET admin_type = 'main_admin' WHERE username = 'mainadmin';
UPDATE admins SET admin_type = 'staff_admin' WHERE username IN ('staffadmin', 'subadmin');

-- Step 7: Remove the old role column if it exists
ALTER TABLE admins DROP COLUMN IF EXISTS role;

-- Step 8: Make sure is_active column exists and has default
ALTER TABLE admins MODIFY COLUMN is_active TINYINT(1) DEFAULT 1;

-- Step 9: Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- Step 10: Verify the changes
SELECT id, username, first_name, last_name, admin_type, is_active FROM admins;