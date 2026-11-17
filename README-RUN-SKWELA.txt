SKWELA COLLEGE ENROLLMENT SYSTEM
================================

ROLES
-----
This project uses one login page and three roles:
- Admin
- Registrar
- Student

LOCAL XAMPP SETUP
-----------------
1. Keep a separate backup of your current project folder and database.
2. Copy the folder "college-enrollment-system" into:
   C:\xampp\htdocs\
3. Start Apache and MySQL in XAMPP.
   In the screenshots, Apache is configured for port 8080 and MySQL uses port 3306.
4. Confirm the database exists in phpMyAdmin:
   college_enrollment_db
5. The project database connection is configured in:
   backend/config/database.php
   Current settings: host=localhost, port=3306, database=college_enrollment_db,
   username=root, blank password.
   Change these only if your local MySQL credentials differ.
6. Open:
   http://localhost:8080/college-enrollment-system/

ONE-LOGIN FLOW
--------------
index.php is the single login page.
backend/routes/auth.php authenticates the account.
The returned role sends the user to the matching dashboard:
- frontend/admin/dashboard.php
- frontend/registrar/dashboard.php
- frontend/student/dashboard.php

DATABASE BACKUP
---------------
The SQL backup supplied by the user is stored at:
database/backups/college_enrollment_db_backup_2026-11-17.sql

IMPORTANT:
- The backup is included for safekeeping. It has NOT been imported automatically.
- Since the existing database is already visible in phpMyAdmin, do not import this
  backup over the live database unless restoring is specifically needed.
- Do not delete or replace files in C:\xampp\mysql\data.
- Keep the original project and database backup until login and all three dashboards
  have been tested.

CHECK PERFORMED
---------------
All 46 PHP files in the supplied project passed PHP syntax checks in this environment.
This does not replace testing against your local XAMPP, database records, browser,
or role-specific account credentials.
