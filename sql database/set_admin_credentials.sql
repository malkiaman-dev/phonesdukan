-- Helper: set_admin_credentials.sql
-- Instructions:
-- 1) Open in your browser: http://localhost/phonesdukan/admin/hash_password.php?pw=Admin@12345&username=admin
-- 2) Copy the SQL output and run it in your database (phpMyAdmin or mysql CLI).
-- Example (do NOT run this until you replace HASH_HERE with the generated hash):
-- UPDATE `admins` SET `password` = 'HASH_HERE', `username` = 'admin' WHERE `id` = 1;

-- If you prefer to directly update the dump file, replace the password value in
-- the INSERT for `admins` in the SQL dump with the generated hash and import the dump.
