# 355 Issues App

This is a PHP-based web application built for my **Server-Side Web Development** class at **Saginaw Valley State University (SVSU)**. It provides a lightweight issue-tracking system for managing project-related tasks within an organization.

The application includes user authentication, issue creation and tracking, comment functionality, filtering, and role-based permissions (admin vs. non-admin users).

---

## Default Test Logins

The included `tables.sql` file sets up demo accounts for testing:

### Admin User
- **Email:** `test@svsu.edu`
- **Password:** `test`

### Non-Admin User
- **Email:** `non_admin@svsu.edu`
- **Password:** `test`

Login via: [`login2.php`](login2.php)

---

## Database Configuration

The app uses **PDO** for database connections, and a PDO object is created in every PHP page via the required `./database/database.php` file.

### Example `database.php`:
```php
<?php
$db_name = "issues_app";
$db_user = "root";
$db_pass = "";
$server = "127.0.0.1";
$connstring = "mysql:host=" . $server . ";port=3307;dbname=" . $db_name . ";charset=utf8mb4;";
```
There is an `example_database.php` file located in the `ex_database/` folder in this repo.
To get started:
1.	Copy or rename `ex_database/example_database.php` to `database/database.php` (rename both directory and php file)
2. Update the credentials to match your local MySQL setup

**Note:** The real `database/` directory (and `database.php`) is excluded via .gitignore for security reasons.

