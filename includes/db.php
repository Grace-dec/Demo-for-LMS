<?php
// includes/db.php  — Database connection (PDO)
// Change host/user/pass if your XAMPP differs.

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');          // Default XAMPP has no password
define('DB_NAME', 'test_project');

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    die('<div style="font-family:sans-serif;color:red;padding:20px;">
         <strong>Database connection failed:</strong> ' . htmlspecialchars($e->getMessage()) . '
         <br>Make sure XAMPP MySQL is running and you have run database.sql.
         </div>');
}